<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /** Layar "Pilih tanggal sewa" / checkout sewa. */
    public function rent(Request $request, Item $item)
    {
        $this->guardBuyable($request, $item, 'sewa');

        return view('orders.checkout', [
            'item' => $item->load(['photos', 'owner', 'category']),
            'mode' => 'sewa',
            'booked' => $item->bookedRanges(),
            'days' => (int) min(max($request->query('days', $item->min_days), $item->min_days), $item->max_days),
        ]);
    }

    /** Layar "Detail & checkout pembelian". */
    public function buy(Request $request, Item $item)
    {
        $this->guardBuyable($request, $item, 'beli');

        return view('orders.checkout', [
            'item' => $item->load(['photos', 'owner', 'category']),
            'mode' => 'beli',
            'booked' => [],
            'days' => 1,
        ]);
    }

    public function store(Request $request, Item $item)
    {
        $this->guardBuyable($request, $item, $item->type);
        $user = $request->user();

        $rules = ['pickup_method' => ['required', 'in:ambil_kampus,pengiriman'], 'note' => ['nullable', 'string', 'max:300']];
        if ($item->isRent()) {
            $rules['start_date'] = ['required', 'date', 'after_or_equal:today'];
            $rules['end_date'] = ['required', 'date', 'after_or_equal:start_date'];
        }
        $data = $request->validate($rules, [
            'start_date.required' => 'Pilih tanggal mulai sewa.',
            'end_date.required' => 'Pilih tanggal selesai sewa.',
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        if ($item->isRent()) {
            $start = Carbon::parse($data['start_date'])->startOfDay();
            $end = Carbon::parse($data['end_date'])->startOfDay();
            $days = (int) $start->diffInDays($end) + 1;

            if ($days < $item->min_days || $days > $item->max_days) {
                return back()->withInput()->withErrors(['end_date' => "Durasi sewa harus {$item->min_days}–{$item->max_days} hari."]);
            }
            if ($item->isBookedBetween($start->toDateString(), $end->toDateString())) {
                return back()->withInput()->withErrors(['start_date' => 'Sebagian tanggal itu sudah terpakai. Pilih tanggal lain.']);
            }
        }

        $order = DB::transaction(function () use ($item, $user, $data, $request) {
            // Kunci baris barang supaya dua pembeli tidak memesan barang jual yang sama.
            $locked = Item::whereKey($item->id)->lockForUpdate()->first();
            abort_unless($locked->status === 'tersedia', 409, 'Barang ini sudah tidak tersedia.');

            $isRent = $locked->isRent();
            $days = $isRent ? (int) Carbon::parse($data["start_date"])->diffInDays(Carbon::parse($data["end_date"])) + 1 : 1;
            $subtotal = $locked->price * $days;
            $deposit = $isRent ? $locked->deposit : 0;

            if (! $isRent) {
                $locked->update(['status' => 'dipesan']);
            }

            return Order::create([
                'item_id' => $locked->id,
                'buyer_id' => $user->id,
                'seller_id' => $locked->user_id,
                'type' => $locked->type,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'days' => $days,
                'price' => $locked->price,
                'subtotal' => $subtotal,
                'deposit' => $deposit,
                'total' => $subtotal + $deposit,
                'pickup_method' => $data['pickup_method'],
                'note' => $data['note'] ?? null,
                'status' => $isRent ? 'menunggu_persetujuan' : 'menunggu_pembayaran',
            ]);
        });

        UserNotification::createFor(
            $order->seller_id, 'transaksi',
            $order->isRent() ? 'Permintaan sewa baru' : 'Pesanan baru',
            $user->firstName() . ' ' . ($order->isRent() ? 'ingin meminjam ' : 'membeli ') . $item->title . '.',
            route('orders.show', $order)
        );

        return $order->isRent()
            ? redirect()->route('orders.show', $order)->with('success', 'Pengajuan terkirim. Tunggu persetujuan pemilik ya.')
            : redirect()->route('payments.choose', $order)->with('success', 'Pesanan dibuat. Pilih metode pembayaran.');
    }

    /** Pinjaman saya / Transaksi & pengembalian. */
    public function index(Request $request)
    {
        $user = $request->user();
        $preset = $request->route('preset');

        $role = $request->query('peran', 'buyer') === 'seller' ? 'seller' : 'buyer';
        $status = in_array($request->query('status'), ['aktif', 'selesai'], true) ? $request->query('status') : 'aktif';
        $type = $preset === 'pinjaman' ? 'sewa' : (in_array($request->query('jenis'), ['beli', 'sewa'], true) ? $request->query('jenis') : null);

        $orders = Order::with(['item.photos', 'buyer', 'seller', 'latestPayment'])
            ->where($role === 'buyer' ? 'buyer_id' : 'seller_id', $user->id)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($status === 'aktif',
                fn ($q) => $q->whereNotIn('status', ['selesai', 'ditolak', 'dibatalkan']),
                fn ($q) => $q->whereIn('status', ['selesai', 'ditolak', 'dibatalkan']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders, 'role' => $role, 'status' => $status, 'type' => $type, 'loansOnly' => $preset === 'pinjaman',
        ]);
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        abort_unless($order->partyOf($user), 403);

        $order->load(['item.photos', 'item.category', 'buyer', 'seller', 'latestPayment', 'reviews']);

        return view('orders.show', [
            'order' => $order,
            'party' => $order->partyOf($user),
            'other' => $order->otherParty($user),
            'myReview' => $order->reviews->firstWhere('reviewer_id', $user->id),
        ]);
    }

    /** Pemilik menyetujui / menolak permintaan sewa. */
    public function respond(Request $request, Order $order)
    {
        $this->authorizeSeller($request, $order);
        abort_unless($order->status === 'menunggu_persetujuan', 422);

        $decision = $request->validate(['decision' => ['required', 'in:setuju,tolak']])['decision'];

        if ($decision === 'setuju') {
            // Cek ulang bentrok tanggal sebelum menyetujui.
            if ($order->item->isBookedBetween($order->start_date->toDateString(), $order->end_date->toDateString(), $order->id)) {
                return back()->with('error', 'Tanggal sewa bentrok dengan transaksi lain. Tolak atau hubungi peminjam.');
            }
            $order->update(['status' => 'menunggu_pembayaran']);
            UserNotification::createFor($order->buyer_id, 'transaksi', 'Permintaan sewa disetujui',
                $order->item->title . ' — silakan lanjut membayar.', route('payments.choose', $order));
            return back()->with('success', 'Permintaan disetujui. Peminjam diminta membayar.');
        }

        $order->update(['status' => 'ditolak']);
        UserNotification::createFor($order->buyer_id, 'transaksi', 'Permintaan sewa ditolak',
            $order->item->title . ' tidak bisa dipinjam saat ini.', route('orders.show', $order));

        return back()->with('success', 'Permintaan ditolak.');
    }

    public function cancel(Request $request, Order $order)
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);
        abort_unless(in_array($order->status, ['menunggu_persetujuan', 'menunggu_pembayaran'], true), 422);

        DB::transaction(function () use ($order) {
            $order->update(['status' => 'dibatalkan']);
            $order->payments()->where('status', 'pending')->update(['status' => 'expired']);
            if (! $order->isRent() && $order->item->status === 'dipesan') {
                $order->item->update(['status' => 'tersedia']);
            }
        });

        UserNotification::createFor($order->seller_id, 'transaksi', 'Pesanan dibatalkan',
            $request->user()->firstName() . ' membatalkan ' . $order->item->title . '.', route('orders.show', $order));

        return redirect()->route('orders.show', $order)->with('success', 'Transaksi dibatalkan.');
    }

    /** Pemilik menyerahkan barang setelah pembayaran diterima. */
    public function handover(Request $request, Order $order)
    {
        $this->authorizeSeller($request, $order);
        abort_unless($order->status === 'dibayar', 422);

        $order->update(['status' => 'diserahkan', 'handed_over_at' => now()]);
        UserNotification::createFor($order->buyer_id, 'pengembalian',
            $order->isRent() ? 'Barang sudah diserahkan' : 'Barang diserahkan',
            $order->isRent()
                ? 'Jangan lupa kembalikan ' . $order->item->title . ' pada ' . $order->end_date->translatedFormat('j F Y') . '.'
                : 'Konfirmasi setelah kamu menerima ' . $order->item->title . '.',
            route('orders.show', $order));

        return back()->with('success', 'Serah terima dicatat.');
    }

    /** Peminjam menandai barang sudah dikembalikan. */
    public function returned(Request $request, Order $order)
    {
        abort_unless($order->buyer_id === $request->user()->id && $order->isRent(), 403);
        abort_unless($order->status === 'diserahkan', 422);

        $order->update(['status' => 'pengembalian', 'returned_at' => now()]);
        UserNotification::createFor($order->seller_id, 'pengembalian', 'Barang dikembalikan',
            'Cek kondisi ' . $order->item->title . ' lalu konfirmasi pengembalian.', route('orders.show', $order));

        return back()->with('success', 'Menunggu konfirmasi pemilik.');
    }

    public function confirmReturn(Request $request, Order $order)
    {
        $this->authorizeSeller($request, $order);
        abort_unless($order->isRent() && $order->status === 'pengembalian', 422);
        abort_unless($order->escrow_status === 'held', 422, 'Dana sewa tidak tercatat dalam escrow.');

        DB::transaction(function () use ($order) {
            $order->update(['status' => 'menunggu_persetujuan_admin']);

            UserNotification::createFor($order->buyer_id, 'pembayaran', 'Pengembalian menunggu admin',
                'Kedua pihak sudah mengonfirmasi pengembalian ' . $order->item->title . '. Dana sewa dan deposit menunggu persetujuan admin.',
                route('orders.show', $order));
            UserNotification::createFor($order->seller_id, 'pembayaran', 'Pencairan menunggu admin',
                'Dana untuk transaksi ' . $order->code . ' menunggu pemeriksaan dan persetujuan admin.',
                route('orders.show', $order));
            User::where('role', 'admin')->get()->each(function (User $admin) use ($order) {
                UserNotification::createFor($admin, 'pembayaran', 'Pencairan sewa menunggu persetujuan',
                    'Periksa pengembalian barang untuk transaksi ' . $order->code . '.',
                    route('admin.payouts'));
            });
        });

        return back()->with('success', 'Pengembalian dikonfirmasi kedua pihak. Dana menunggu persetujuan admin.');
    }

    /** Pembeli mengonfirmasi barang sudah diterima. */
    public function received(Request $request, Order $order)
    {
        abort_unless($order->buyer_id === $request->user()->id && ! $order->isRent(), 403);
        abort_unless($order->status === 'diserahkan', 422);

        $this->finish($order);

        return back()->with('success', 'Transaksi selesai. Jangan lupa beri ulasan!');
    }

    /* ---------- helpers ---------- */

    private function finish(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'selesai', 'completed_at' => now()]);
            if (! $order->isRent()) {
                $order->item->update(['status' => 'terjual']);
            }
        });

        foreach ([$order->buyer_id, $order->seller_id] as $uid) {
            UserNotification::createFor($uid, 'ulasan', 'Transaksi selesai',
                'Beri ulasan untuk transaksi ' . $order->code . '.', route('reviews.create', $order));
        }
    }

    private function authorizeSeller(Request $request, Order $order): void
    {
        abort_unless($order->seller_id === $request->user()->id, 403);
    }

    private function guardBuyable(Request $request, Item $item, string $type): void
    {
        $user = $request->user();
        abort_if($item->user_id === $user->id, 403, 'Kamu tidak bisa memesan barang sendiri.');
        abort_if(in_array($item->user_id, $user->blockedIds(), true), 404);
        abort_unless($item->type === $type, 404);
        abort_unless($item->status === 'tersedia', 409, 'Barang ini sudah tidak tersedia.');
    }
}
