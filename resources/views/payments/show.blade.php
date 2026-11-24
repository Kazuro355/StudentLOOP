@extends('layouts.app')
@section('title', 'Instruksi pembayaran')

@section('content')
    @php($expired = $payment->status === 'expired')
    @include('partials.pagehead', [
        'title' => match ($payment->method) { 'qris' => 'Pembayaran QRIS', 'ewallet' => 'Pembayaran e-wallet', default => 'Pembayaran transfer bank' },
        'subtitle' => $order->code . ' · ' . $payment->methodLabel(),
        'back' => route('orders.show', $order),
    ])

    <div class="cols c-2-1">
        <div class="stack-lg">
            @if ($expired)
                <div class="card center stack">
                    <h2>Waktu pembayaran habis</h2>
                    <p class="muted">Pembayaran ini sudah kedaluwarsa. Pilih metode lagi untuk melanjutkan.</p>
                    <a class="btn" href="{{ route('payments.choose', ['order' => $order, 'ganti' => 1]) }}" style="justify-self:center">Pilih metode pembayaran</a>
                </div>
            @else
                <div class="card stack center">
                    <p class="muted small">Selesaikan pembayaran dalam <span class="countdown" data-countdown="{{ $payment->expires_at->toIso8601String() }}">--:--:--</span></p>
                    <div class="price-big">@rp($payment->amount)</div>

                    @if ($payment->method === 'qris')
                        @php($cells = $payment->qrCells(25))
                        <div class="qr">
                            <svg viewBox="0 0 25 25" shape-rendering="crispEdges" role="img" aria-label="Kode QRIS">
                                <rect width="25" height="25" fill="#fff"/>
                                @foreach ($cells as $y => $row)@foreach ($row as $x => $on)@if ($on)<rect x="{{ $x }}" y="{{ $y }}" width="1" height="1" fill="#1b1633"/>@endif @endforeach @endforeach
                            </svg>
                        </div>
                        <p class="muted small">Scan dengan aplikasi bank atau e-wallet kamu. (QR demo)</p>
                    @elseif ($payment->method === 'ewallet')
                        <div class="notice" style="text-align:left">
                            <b>{{ \App\Models\Payment::EWALLETS[$payment->provider] ?? $payment->provider }}</b> · {{ $payment->phone }}<br>
                            Buka aplikasinya, cari permintaan pembayaran dari StudentLOOP, lalu konfirmasi.
                        </div>
                    @else
                        <div class="stack" style="text-align:left">
                            <span class="muted small">Bank {{ \App\Models\Payment::BANKS[$payment->provider][0] ?? '' }} · nomor virtual account</span>
                            <div class="copy-box">
                                <span class="va">{{ $payment->account_number }}</span>
                                <button type="button" class="btn btn-outline btn-sm" data-copy="{{ $payment->account_number }}">Salin</button>
                            </div>
                        </div>
                    @endif

                    <p class="muted xs">Referensi: {{ $payment->reference }}</p>
                </div>

                <div class="card stack">
                    <h3>Cara membayar</h3>
                    <ol class="steps-ol">
                        @if ($payment->method === 'qris')
                            <li>Buka aplikasi bank atau e-wallet, pilih <b>Scan / Bayar QRIS</b>.</li>
                            <li>Arahkan kamera ke kode QR di atas.</li>
                            <li>Pastikan nominal <b>@rp($payment->amount)</b>, lalu konfirmasi.</li>
                        @elseif ($payment->method === 'ewallet')
                            <li>Buka aplikasi {{ \App\Models\Payment::EWALLETS[$payment->provider] ?? 'e-wallet' }} di ponselmu.</li>
                            <li>Setujui permintaan pembayaran sebesar <b>@rp($payment->amount)</b>.</li>
                            <li>Kembali ke sini dan tekan <b>Saya sudah bayar</b>.</li>
                        @else
                            <li>Buka m-banking atau ATM, pilih <b>Transfer ke Virtual Account</b>.</li>
                            <li>Masukkan nomor VA di atas dan pastikan nominal <b>@rp($payment->amount)</b>.</li>
                            <li>Setelah berhasil, tekan <b>Saya sudah bayar</b>.</li>
                        @endif
                    </ol>
                </div>
            @endif
        </div>

        <aside class="card stack sticky-side">
            <h3>Ringkasan</h3>
            <b>{{ $order->item->title }}</b>
            <div class="kv"><span>Subtotal</span><span>@rp($order->subtotal)</span></div>
            @if ($order->deposit)<div class="kv"><span>Deposit (dikembalikan)</span><span>@rp($order->deposit)</span></div>@endif
            <div class="kv total"><span>Total</span><span>@rp($order->total)</span></div>

            @unless ($expired)
                <form method="POST" action="{{ route('payments.pay', $payment) }}" data-once>@csrf
                    <button class="btn btn-block" type="submit">Saya sudah bayar</button>
                </form>
                <a class="btn btn-outline btn-block" href="{{ route('payments.choose', ['order' => $order, 'ganti' => 1]) }}">Ganti metode</a>
                <p class="hint center">Mode demo: tombol ini menandai pembayaran lunas.</p>
            @endunless
        </aside>
    </div>
@endsection
