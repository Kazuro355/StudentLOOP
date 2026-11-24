<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Order;
use App\Models\Report;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index()
    {
        $reportCounts = Report::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('admin.dashboard', [
            'reportCounts' => $reportCounts,
            'activeUsers' => User::whereNull('suspended_at')->count(),
            'suspendedUsers' => User::whereNotNull('suspended_at')->count(),
            'availableItems' => Item::where('status', 'tersedia')->whereNull('moderated_at')->count(),
            'pendingPayoutCount' => Order::where('type', 'sewa')
                ->where('status', 'menunggu_persetujuan_admin')
                ->where('escrow_status', 'held')
                ->count(),
            'heldAmount' => Order::where('type', 'sewa')->where('escrow_status', 'held')->sum('total'),
            'recentReports' => $this->withTargets(Report::with('reporter')->latest()->limit(6)->get()),
        ]);
    }

    public function reports(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['baru', 'diproses', 'selesai', 'semua'])],
        ]);
        $status = $filters['status'] ?? 'baru';

        $query = Report::with('reporter')->latest();
        if ($status !== 'semua') {
            $query->where('status', $status);
        }

        return view('admin.reports', [
            'reports' => $this->withTargets($query->paginate(12)->withQueryString()),
            'status' => $status,
        ]);
    }

    public function users(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $term = trim($filters['q'] ?? '');

        return view('admin.users', [
            'users' => User::query()
                ->withCount('items')
                ->when($term !== '', function ($query) use ($term) {
                    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
                    $query->where(function ($search) use ($like) {
                        $search->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('nim', 'like', $like);
                    });
                })
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString(),
            'term' => $term,
        ]);
    }

    public function payouts()
    {
        return view('admin.payouts', [
            'pendingPayouts' => Order::with(['item', 'buyer', 'seller', 'latestPayment', 'photos.uploader'])
                ->where('type', 'sewa')
                ->where('status', 'menunggu_persetujuan_admin')
                ->where('escrow_status', 'held')
                ->oldest('returned_at')
                ->paginate(12, ['*'], 'pending_page'),
            'releasedPayouts' => Order::with(['item', 'buyer', 'seller'])
                ->where('type', 'sewa')
                ->where('escrow_status', 'released')
                ->latest('funds_released_at')
                ->limit(8)
                ->get(),
            'heldAmount' => Order::where('type', 'sewa')
                ->where('escrow_status', 'held')
                ->sum('total'),
        ]);
    }

    public function releaseFunds(Request $request, Order $order)
    {
        abort_unless($request->user()->isAdmin(), 403);

        DB::transaction(function () use ($request, $order) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            abort_unless(
                $locked->isRent()
                    && $locked->status === 'menunggu_persetujuan_admin'
                    && $locked->escrow_status === 'held'
                    && $locked->returned_at !== null,
                422,
                'Transaksi ini belum siap untuk pencairan.'
            );
            abort_unless(
                $locked->payments()
                    ->where('status', 'paid')
                    ->where('amount', $locked->total)
                    ->exists(),
                422,
                'Pembayaran lunas dengan nominal yang sesuai tidak ditemukan untuk transaksi ini.'
            );

            $locked->update([
                'status' => 'selesai',
                'escrow_status' => 'released',
                'funds_released_at' => now(),
                'funds_released_by' => $request->user()->id,
                'completed_at' => now(),
            ]);

            \App\Models\User::whereKey($locked->seller_id)->increment('balance', $locked->subtotal);
            \App\Models\User::whereKey($locked->buyer_id)->increment('balance', $locked->deposit);

            \Illuminate\Support\Facades\DB::table('wallet_transactions')->insert([
                [
                    'user_id' => $locked->seller_id,
                    'order_id' => $locked->id,
                    'type' => 'credit',
                    'status' => 'completed',
                    'amount' => $locked->subtotal,
                    'description' => 'Pencairan biaya sewa transaksi ' . $locked->code,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'user_id' => $locked->buyer_id,
                    'order_id' => $locked->id,
                    'type' => 'credit',
                    'status' => 'completed',
                    'amount' => $locked->deposit,
                    'description' => 'Pengembalian deposit transaksi ' . $locked->code,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            UserNotification::createFor($locked->seller_id, 'pembayaran', 'Pencairan sewa disetujui',
                'Admin menyetujui pencairan biaya sewa sebesar Rp' . number_format($locked->subtotal, 0, ',', '.') . ' untuk transaksi ' . $locked->code . '.',
                route('orders.show', $locked));
            UserNotification::createFor($locked->buyer_id, 'pembayaran', 'Deposit dikembalikan',
                'Admin menyetujui pengembalian deposit sebesar Rp' . number_format($locked->deposit, 0, ',', '.') . ' untuk transaksi ' . $locked->code . '.',
                route('orders.show', $locked));

            foreach ([$locked->buyer_id, $locked->seller_id] as $userId) {
                UserNotification::createFor($userId, 'ulasan', 'Transaksi sewa selesai',
                    'Dana transaksi ' . $locked->code . ' sudah disetujui admin. Beri ulasan untuk pengalaman sewamu.',
                    route('reviews.create', $locked));
            }
        });

        return back()->with('success', 'Pencairan disetujui. Biaya sewa dicatat untuk pemilik dan deposit untuk penyewa.');
    }

    public function updateReportStatus(Request $request, Report $report)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['baru', 'diproses', 'selesai'])],
        ]);

        $report->forceFill([
            'status' => $data['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        return back()->with('success', 'Status laporan diperbarui.');
    }

    public function resolveReport(Request $request, Report $report)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['dismiss', 'hide_item', 'restore_item', 'suspend_user'])],
        ]);

        $resolution = match ($data['action']) {
            'dismiss' => 'Laporan ditutup tanpa tindakan.',
            'hide_item' => 'Barang disembunyikan dari marketplace.',
            'restore_item' => 'Barang ditampilkan kembali di marketplace.',
            'suspend_user' => 'Akun pengguna dinonaktifkan.',
        };

        if (in_array($data['action'], ['hide_item', 'restore_item'], true)) {
            abort_unless($report->target_type === 'item', 422, 'Tindakan ini hanya berlaku untuk laporan barang.');
            $item = Item::findOrFail($report->target_id);
            $item->update(['moderated_at' => $data['action'] === 'hide_item' ? now() : null]);
        }

        if ($data['action'] === 'suspend_user') {
            abort_unless($report->target_type === 'user', 422, 'Tindakan ini hanya berlaku untuk laporan pengguna.');
            $user = User::findOrFail($report->target_id);
            abort_if($user->isAdmin() || $user->is($request->user()), 422, 'Akun admin atau akun sendiri tidak dapat dinonaktifkan dari laporan.');
            $user->forceFill(['suspended_at' => now()])->save();
        }

        $report->forceFill([
            'status' => 'selesai',
            'resolution' => $resolution,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        return back()->with('success', 'Laporan ditutup. ' . $resolution);
    }

    public function updateUserSuspension(Request $request, User $user)
    {
        $data = $request->validate([
            'suspended' => ['required', 'boolean'],
        ]);

        abort_if($user->isAdmin() || $user->is($request->user()), 422, 'Akun admin atau akun sendiri tidak dapat dinonaktifkan.');

        $user->forceFill([
            'suspended_at' => $data['suspended'] ? now() : null,
        ])->save();

        return back()->with('success', $data['suspended'] ? 'Akun dinonaktifkan.' : 'Akun diaktifkan kembali.');
    }

    private function withTargets($reports)
    {
        $collection = $reports instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
            ? $reports->getCollection()
            : $reports;

        $idsByType = $collection->groupBy('target_type')->map(fn ($items) => $items->pluck('target_id')->unique());
        $targets = collect();

        if ($idsByType->has('item')) {
            $targets = $targets->merge(Item::with('owner')->whereIn('id', $idsByType['item'])->get()->keyBy(fn ($item) => 'item:' . $item->id));
        }
        if ($idsByType->has('user')) {
            $targets = $targets->merge(User::whereIn('id', $idsByType['user'])->get()->keyBy(fn ($user) => 'user:' . $user->id));
        }
        if ($idsByType->has('order')) {
            $targets = $targets->merge(Order::with(['buyer', 'seller', 'item'])->whereIn('id', $idsByType['order'])->get()->keyBy(fn ($order) => 'order:' . $order->id));
        }

        $collection->each(function (Report $report) use ($targets) {
            $report->setAttribute('target', $targets->get($report->target_type . ':' . $report->target_id));
        });

        return $reports;
    }
}
