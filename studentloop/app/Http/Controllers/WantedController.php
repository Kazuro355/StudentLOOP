<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\UserNotification;
use App\Models\WantedOffer;
use App\Models\WantedRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WantedController extends Controller
{
    /** Layar "Dicari": permintaan barang dari mahasiswa lain. */
    public function index(Request $request)
    {
        $user = $request->user();
        $mode = in_array($request->query('mode'), ['beli', 'sewa'], true) ? $request->query('mode') : null;
        $blocked = $user->blockedIds();

        $wanted = WantedRequest::with(['user', 'category'])->withCount('offers')
            ->where('status', 'aktif')
            ->when($blocked, fn ($q) => $q->whereNotIn('user_id', $blocked))
            ->when($mode, fn ($q) => $q->where('mode', $mode))
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%' . $term . '%')->orWhere('details', 'like', '%' . $term . '%')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('wanted.index', ['wanted' => $wanted, 'mode' => $mode]);
    }

    public function create()
    {
        return view('wanted.create', ['categories' => Category::orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'category_id' => ['required', 'exists:categories,id'],
            'details' => ['nullable', 'string', 'max:1000'],
            'condition' => ['nullable', Rule::in(Item::CONDITIONS)],
            'mode' => ['required', 'in:beli,sewa'],
            'budget_min' => ['nullable', 'integer', 'min:0'],
            'budget_max' => ['nullable', 'integer', 'min:0', 'gte:budget_min'],
            'needed_by' => ['nullable', 'date', 'after_or_equal:today'],
            'kampus' => ['nullable', Rule::in(Item::CAMPUSES)],
        ], ['budget_max.gte' => 'Budget maksimal harus lebih besar dari minimal.']);

        $wanted = $request->user()->hasMany(WantedRequest::class)->create($data + ['status' => 'aktif']);

        return redirect()->route('wanted.show', $wanted)->with('success', 'Permintaan dipublikasikan. Pemilik barang bisa menawarkan.');
    }

    public function show(Request $request, WantedRequest $wanted)
    {
        $user = $request->user();
        abort_if(in_array($wanted->user_id, $user->blockedIds(), true), 404);

        $isOwner = $wanted->user_id === $user->id;
        $wanted->load(['user', 'category', 'offers.user', 'offers.item.photos']);

        return view('wanted.show', [
            'wanted' => $wanted,
            'isOwner' => $isOwner,
            'myItems' => $isOwner ? collect() : $user->items()->browsable()->where('type', $wanted->mode)->get(),
            'myOffer' => $isOwner ? null : $wanted->offers->firstWhere('user_id', $user->id),
        ]);
    }

    public function close(Request $request, WantedRequest $wanted)
    {
        abort_unless($wanted->user_id === $request->user()->id, 403);

        $wanted->update(['status' => $request->input('status') === 'terpenuhi' ? 'terpenuhi' : 'ditutup']);

        return redirect()->route('items.mine', ['tab' => 'wanted'])->with('success', 'Permintaan ditutup.');
    }

    /** Pemilik barang menawarkan barangnya untuk sebuah permintaan. */
    public function offer(Request $request, WantedRequest $wanted)
    {
        $user = $request->user();
        abort_if($wanted->user_id === $user->id, 403);
        abort_unless($wanted->status === 'aktif', 422);
        abort_if(in_array($wanted->user_id, $user->blockedIds(), true), 404);

        $data = $request->validate([
            'item_id' => ['nullable', Rule::exists('items', 'id')->where(fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('type', $wanted->mode)
                ->where('status', 'tersedia'))],
            'price' => ['nullable', 'integer', 'min:0'],
            'message' => ['required', 'string', 'max:500'],
        ], ['message.required' => 'Tulis pesan singkat untuk peminta.']);

        if ($wanted->offers()->where('user_id', $user->id)->where('status', 'menunggu')->exists()) {
            return back()->with('info', 'Kamu sudah mengirim tawaran untuk permintaan ini.');
        }

        $wanted->offers()->create($data + ['user_id' => $user->id, 'status' => 'menunggu']);

        UserNotification::createFor($wanted->user_id, 'wanted', 'Tawaran baru untuk "' . $wanted->title . '"',
            $user->firstName() . ' menawarkan barangnya.', route('wanted.show', $wanted));

        return back()->with('success', 'Tawaran terkirim.');
    }

    /** Peminta menerima / menolak tawaran. */
    public function respond(Request $request, WantedOffer $offer)
    {
        $wanted = $offer->request;
        abort_unless($wanted->user_id === $request->user()->id, 403);
        abort_unless($offer->status === 'menunggu', 422);

        $decision = $request->validate(['decision' => ['required', 'in:terima,tolak']])['decision'];
        $offer->update(['status' => $decision === 'terima' ? 'diterima' : 'ditolak']);

        UserNotification::createFor($offer->user_id, 'wanted',
            $decision === 'terima' ? 'Tawaranmu diterima' : 'Tawaranmu ditolak',
            '"' . $wanted->title . '" — ' . $request->user()->firstName(),
            $decision === 'terima' ? route('conversations.user', $offer->user_id) : route('wanted.show', $wanted));

        if ($decision === 'terima') {
            return redirect()->route('conversations.user', $offer->user_id)->with('success', 'Tawaran diterima. Lanjutkan obrolan dengan pemilik barang.');
        }

        return back()->with('success', 'Tawaran ditolak.');
    }
}
