<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Pembayaran DEMO: instruksi QRIS / e-wallet / transfer bank dibuat lokal dan tombol
 * "Saya sudah bayar" menandai lunas. Untuk produksi, ganti bagian pay() dengan webhook
 * payment gateway (mis. Midtrans / Xendit) yang memverifikasi pembayaran sebenarnya.
 */
class PaymentController extends Controller
{
    public function choose(Request $request, Order $order)
    {
        $this->authorizeBuyer($request, $order);

        if ($order->status !== 'menunggu_pembayaran') {
            return redirect()->route('orders.show', $order);
        }

        // Kalau sudah ada pembayaran pending yang belum kedaluwarsa, lanjutkan.
        $pending = $order->payments()->where('status', 'pending')->first();
        if ($pending && ! $pending->isExpired() && ! $request->boolean('ganti')) {
            return redirect()->route('payments.show', $pending);
        }

        return view('payments.choose', ['order' => $order->load('item.photos')]);
    }

    public function store(Request $request, Order $order)
    {
        $this->authorizeBuyer($request, $order);
        abort_unless($order->status === 'menunggu_pembayaran', 422);

        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'ewallet' => ['required_if:method,ewallet', 'nullable', Rule::in(array_keys(Payment::EWALLETS))],
            'phone' => ['required_if:method,ewallet', 'nullable', 'regex:/^[0-9+\- ]{8,16}$/'],
            'bank' => ['required_if:method,bank', 'nullable', Rule::in(array_keys(Payment::BANKS))],
        ], [
            'method.required' => 'Pilih metode pembayaran.',
            'ewallet.required_if' => 'Pilih e-wallet.',
            'phone.required_if' => 'Isi nomor e-wallet.',
            'phone.regex' => 'Nomor e-wallet tidak valid.',
            'bank.required_if' => 'Pilih bank.',
        ]);

        $payment = DB::transaction(function () use ($order, $data) {
            // Pembayaran pending sebelumnya diganti.
            $order->payments()->where('status', 'pending')->update(['status' => 'expired']);

            $provider = match ($data['method']) {
                'ewallet' => $data['ewallet'],
                'bank' => $data['bank'],
                default => null,
            };

            return $order->payments()->create([
                'method' => $data['method'],
                'provider' => $provider,
                'phone' => $data['method'] === 'ewallet' ? $data['phone'] : null,
                'account_number' => $data['method'] === 'bank'
                    ? Payment::BANKS[$data['bank']][1] . str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT)
                    : null,
                'reference' => 'PAY-' . strtoupper(Str::random(10)),
                'amount' => $order->total,
                'status' => 'pending',
                'expires_at' => now()->addHours(2),
            ]);
        });

        return redirect()->route('payments.show', $payment);
    }

    /** Instruksi pembayaran (QRIS menunggu / e-wallet / transfer bank). */
    public function show(Request $request, Payment $payment)
    {
        $this->authorizeBuyer($request, $payment->order);

        if ($payment->status === 'paid') {
            return redirect()->route('payments.success', $payment);
        }
        if ($payment->isExpired()) {
            $payment->update(['status' => 'expired']);
        }

        return view('payments.show', ['payment' => $payment->load('order.item.photos'), 'order' => $payment->order]);
    }

    public function pay(Request $request, Payment $payment)
    {
        $order = $payment->order;
        $this->authorizeBuyer($request, $order);
        abort_unless($payment->status === 'pending' && $order->status === 'menunggu_pembayaran', 422);

        if ($payment->isExpired()) {
            $payment->update(['status' => 'expired']);
            return redirect()->route('payments.choose', ['order' => $order, 'ganti' => 1])
                ->with('error', 'Waktu pembayaran habis. Pilih metode lagi.');
        }

        DB::transaction(function () use ($payment, $order) {
            $payment->update(['status' => 'paid', 'paid_at' => now()]);
            $order->update([
                'status' => 'dibayar',
                'escrow_status' => $order->isRent() ? 'held' : 'none',
            ]);
        });

        UserNotification::createFor($order->seller_id, 'pembayaran', 'Pembayaran diterima',
            $order->code . ' sudah dibayar. Siapkan serah terima ' . $order->item->title . '.', route('orders.show', $order));
        UserNotification::createFor($order->buyer_id, 'pembayaran', 'Pembayaran berhasil',
            'Pembayaran ' . $order->code . ' tercatat. Atur serah terima dengan pemilik.', route('orders.show', $order));

        return redirect()->route('payments.success', $payment);
    }

    public function success(Request $request, Payment $payment)
    {
        $this->authorizeBuyer($request, $payment->order);
        abort_unless($payment->status === 'paid', 404);

        return view('payments.success', ['payment' => $payment, 'order' => $payment->order->load('item', 'seller')]);
    }

    private function authorizeBuyer(Request $request, Order $order): void
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);
    }
}
