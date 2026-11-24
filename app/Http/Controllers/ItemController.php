<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Order;
use App\Models\WantedRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function show(Request $request, Item $item)
    {
        $user = $request->user();

        // Barang milik pengguna yang saling memblokir tidak bisa dibuka.
        abort_if(in_array($item->user_id, $user->blockedIds(), true), 404);
        abort_if($item->status === 'nonaktif' && $item->user_id !== $user->id && ! $user->isAdmin(), 404);
        abort_if(($item->moderated_at || $item->owner->isSuspended()) && ! $user->isAdmin(), 404);

        if ($item->user_id !== $user->id) {
            $item->increment('views');
        }

        $item->load(['photos', 'category', 'owner', 'questions.user', 'reviews.reviewer']);

        return view('items.show', [
            'item' => $item,
            'rating' => $item->ratingAvg(),
            'isFavorite' => $user->favorites()->where('items.id', $item->id)->exists(),
            'isOwner' => $item->user_id === $user->id,
            'days' => (int) min(max($request->query('days', $item->min_days), $item->min_days), $item->max_days),
        ]);
    }

    public function create(Request $request)
    {
        $type = $request->route('type') === 'sewa' ? 'sewa' : 'beli';

        return view('items.form', $this->formData(new Item(['type' => $type, 'kampus' => $request->user()->kampus, 'min_days' => 1, 'max_days' => 14])));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $item = $request->user()->items()->create($data + ['status' => 'tersedia']);
        $this->storePhotos($request, $item);

        return redirect()->route('items.show', $item)->with('success', 'Barang berhasil dipublikasikan.');
    }

    public function edit(Request $request, Item $item)
    {
        $this->authorizeOwner($request, $item);
        $item->load('photos');

        return view('items.form', $this->formData($item));
    }

    public function update(Request $request, Item $item)
    {
        $this->authorizeOwner($request, $item);

        $item->update($this->validated($request));

        // Hapus foto yang dicentang, lalu tambahkan foto baru.
        foreach ($item->photos()->whereIn('id', (array) $request->input('remove_photos', []))->get() as $photo) {
            Storage::disk('public')->delete($photo->path);
            $photo->delete();
        }
        $this->storePhotos($request, $item);

        return redirect()->route('items.show', $item)->with('success', 'Barang diperbarui.');
    }

    /** Aktifkan / nonaktifkan listing. */
    public function toggle(Request $request, Item $item)
    {
        $this->authorizeOwner($request, $item);
        abort_if(in_array($item->status, ['dipesan', 'terjual'], true), 422, 'Barang sedang dalam transaksi.');

        $item->update(['status' => $item->status === 'tersedia' ? 'nonaktif' : 'tersedia']);

        return back()->with('success', $item->status === 'tersedia' ? 'Barang diaktifkan.' : 'Barang dinonaktifkan.');
    }

    public function destroy(Request $request, Item $item)
    {
        $this->authorizeOwner($request, $item);

        // Barang yang pernah bertransaksi tidak dihapus agar riwayat tetap utuh.
        if ($item->orders()->exists()) {
            $item->update(['status' => 'nonaktif']);
            return redirect()->route('items.mine')->with('info', 'Barang sudah pernah bertransaksi, jadi hanya dinonaktifkan.');
        }

        foreach ($item->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }
        $item->delete();

        return redirect()->route('items.mine')->with('success', 'Barang dihapus.');
    }

    /** Layar "Barang saya & permintaan". */
    public function mine(Request $request)
    {
        $user = $request->user();
        $tab = in_array($request->query('tab'), ['barang', 'permintaan', 'wanted'], true) ? $request->query('tab') : 'barang';

        return view('items.mine', [
            'tab' => $tab,
            'items' => $user->items()->with(['photos', 'category'])->withCount('favoritedBy')->latest()->get(),
            'incoming' => Order::with(['item', 'buyer'])->where('seller_id', $user->id)
                ->whereIn('status', ['menunggu_persetujuan', 'menunggu_pembayaran', 'dibayar', 'diserahkan', 'pengembalian', 'menunggu_persetujuan_admin'])
                ->latest()->get(),
            'wanted' => WantedRequest::withCount('offers')->where('user_id', $user->id)->latest()->get(),
        ]);
    }

    /* ---------- helpers ---------- */

    private function formData(Item $item): array
    {
        return ['item' => $item, 'categories' => Category::orderBy('id')->get()];
    }

    private function authorizeOwner(Request $request, Item $item): void
    {
        abort_unless($item->user_id === $request->user()->id, 403);
    }

    private function validated(Request $request): array
    {
        $request->merge([
            'min_days' => $request->input('min_days') ?: 1,
            'max_days' => $request->input('max_days') ?: 14,
            'deposit' => $request->input('deposit') ?: 0,
        ]);

        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(Item::TYPES))],
            'title' => ['required', 'string', 'max:120'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['required', 'string', 'max:2000'],
            'condition' => ['required', Rule::in(Item::CONDITIONS)],
            'kelengkapan' => ['nullable', 'string', 'max:200'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'deposit' => ['integer', 'min:0', 'max:100000000'],
            'min_days' => ['integer', 'min:1', 'max:60'],
            'max_days' => ['integer', 'min:1', 'max:60', 'gte:min_days'],
            'pickup_method' => ['required', Rule::in(array_keys(Item::PICKUP))],
            'meeting_point' => ['nullable', 'string', 'max:150'],
            'kampus' => ['required', Rule::in(Item::CAMPUSES)],
            'photos' => ['nullable', 'array', 'max:4'],
            'photos.*' => ['image', 'max:4096'],
        ], [
            'photos.max' => 'Maksimal 4 foto.',
            'photos.*.image' => 'File harus berupa gambar.',
            'photos.*.max' => 'Ukuran tiap foto maksimal 4 MB.',
            'max_days.gte' => 'Durasi maksimal harus lebih besar dari minimal.',
        ]);

        // Foto ditangani terpisah (storePhotos), bukan kolom tabel items.
        return Arr::except($validated, ['photos']);
    }

    private function storePhotos(Request $request, Item $item): void
    {
        $position = (int) $item->photos()->max('position');
        foreach ((array) $request->file('photos', []) as $file) {
            if ($item->photos()->count() >= 4) {
                break;
            }
            $item->photos()->create(['path' => $file->store('items', 'public'), 'position' => ++$position]);
        }
    }
}
