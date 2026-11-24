<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    private const SECTIONS = [
        'popular' => 'Terpopuler',
        'newest' => 'Baru ditambahkan',
        'nearby' => 'Di dekatmu',
        'recommended' => 'Rekomendasi untukmu',
    ];

    public function index(Request $request)
    {
        $mode = $this->mode($request);

        return view('home.index', [
            'mode' => $mode,
            'categories' => Category::orderBy('id')->get(),
            'sections' => [
                'popular' => ['title' => self::SECTIONS['popular'], 'route' => 'items.popular', 'items' => $this->sectionQuery('popular', $request, $mode)->limit(5)->get()],
                'newest' => ['title' => self::SECTIONS['newest'], 'route' => 'items.newest', 'items' => $this->sectionQuery('newest', $request, $mode)->limit(4)->get()],
                'nearby' => ['title' => self::SECTIONS['nearby'], 'route' => 'items.nearby', 'items' => $this->sectionQuery('nearby', $request, $mode)->limit(4)->get()],
                'recommended' => ['title' => self::SECTIONS['recommended'], 'route' => 'items.recommended', 'items' => $this->sectionQuery('recommended', $request, $mode)->limit(4)->get()],
            ],
        ]);
    }

    /** Halaman "Lihat semua" untuk tiap bagian beranda. */
    public function section(Request $request)
    {
        $key = $request->route('key');
        abort_unless(isset(self::SECTIONS[$key]), 404);
        $mode = $this->mode($request);

        return view('home.section', [
            'title' => self::SECTIONS[$key],
            'key' => $key,
            'mode' => $mode,
            'items' => $this->sectionQuery($key, $request, $mode)->paginate(12)->withQueryString(),
        ]);
    }

    public function guide()
    {
        return view('home.guide');
    }

    public function search(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'mode' => ['nullable', 'in:semua,beli,sewa'],
            'kategori' => ['nullable', 'array'],
            'kategori.*' => ['integer'],
            'kondisi' => ['nullable', 'array'],
            'kondisi.*' => ['string'],
            'kampus' => ['nullable', 'array'],
            'kampus.*' => ['string'],
            'min' => ['nullable', 'integer', 'min:0'],
            'max' => ['nullable', 'integer', 'min:0'],
            'urut' => ['nullable', 'in:terbaru,termurah,termahal,populer'],
        ]);

        $query = Item::query()
            ->with(['photos', 'category', 'owner'])
            ->withCount('favoritedBy')
            ->browsable()
            ->visibleTo($request->user())
            ->search($data['q'] ?? null)
            ->when(($data['mode'] ?? 'semua') !== 'semua', fn (Builder $q) => $q->where('type', $data['mode']))
            ->when(! empty($data['kategori']), fn (Builder $q) => $q->whereIn('category_id', $data['kategori']))
            ->when(! empty($data['kondisi']), fn (Builder $q) => $q->whereIn('condition', $data['kondisi']))
            ->when(! empty($data['kampus']), fn (Builder $q) => $q->whereIn('kampus', $data['kampus']))
            ->when(isset($data['min']), fn (Builder $q) => $q->where('price', '>=', $data['min']))
            ->when(isset($data['max']), fn (Builder $q) => $q->where('price', '<=', $data['max']));

        match ($data['urut'] ?? 'terbaru') {
            'termurah' => $query->orderBy('price'),
            'termahal' => $query->orderByDesc('price'),
            'populer' => $query->orderByDesc('favorited_by_count')->orderByDesc('views'),
            default => $query->latest(),
        };

        return view('items.search', [
            'items' => $query->paginate(12)->withQueryString(),
            'categories' => Category::orderBy('id')->get(),
            'f' => $data,
        ]);
    }

    /* ---------- helpers ---------- */

    private function mode(Request $request): string
    {
        return in_array($request->query('mode'), ['beli', 'sewa'], true) ? $request->query('mode') : 'semua';
    }

    private function sectionQuery(string $key, Request $request, string $mode): Builder
    {
        $user = $request->user();

        $q = Item::query()
            ->with(['photos', 'category', 'owner'])
            ->withCount('favoritedBy')
            ->browsable()
            ->visibleTo($user)
            ->where('user_id', '!=', $user->id)
            ->when($mode !== 'semua', fn (Builder $b) => $b->where('type', $mode));

        return match ($key) {
            'popular' => $q->orderByDesc('favorited_by_count')->orderByDesc('views'),
            'nearby' => $q->where('kampus', $user->kampus)->latest(),
            'recommended' => $this->recommended($q, $user),
            default => $q->latest(),
        };
    }

    /** Rekomendasi sederhana: kategori yang pernah difavoritkan / dipesan pengguna. */
    private function recommended(Builder $q, $user): Builder
    {
        $categoryIds = $user->favorites()->pluck('items.category_id')
            ->merge(Order::where('buyer_id', $user->id)->with('item')->get()->pluck('item.category_id'))
            ->filter()->unique()->values()->all();

        return $q
            ->when($categoryIds, fn (Builder $b) => $b->orderByRaw(
                'CASE WHEN category_id IN (' . implode(',', array_map('intval', $categoryIds)) . ') THEN 0 ELSE 1 END'
            ))
            ->orderByDesc('views');
    }
}
