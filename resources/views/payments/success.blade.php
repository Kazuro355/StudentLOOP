@extends('layouts.app')
@section('title', 'Pembayaran berhasil')

@section('content')
    <div class="cols c-3-2" style="max-width:900px;margin:0 auto">
        <div class="card center stack">
            <div class="success-mark">✓</div>
            <h1>Pembayaran berhasil!</h1>
            <p class="muted">Pembayaran <b>@rp($payment->amount)</b> untuk <b>{{ $order->item->title }}</b> sudah tercatat.</p>
            @if ($order->isRent())
                <div class="notice small">Seluruh pembayaran sewa dan deposit ditahan sampai barang dikembalikan, kedua pihak mengonfirmasi, dan admin menyetujui pencairan.</div>
            @endif

            <div class="card tint" style="text-align:left">
                <div class="kv"><span>Kode transaksi</span><b>{{ $order->code }}</b></div>
                <div class="kv"><span>Metode</span><span>{{ $payment->methodLabel() }}</span></div>
                <div class="kv"><span>Dibayar</span><span>{{ $payment->paid_at->translatedFormat('j M Y, H:i') }}</span></div>
            </div>

            <div class="btn-row">
                <a class="btn" href="{{ route('conversations.user', $order->seller) }}">Chat pemilik</a>
                <a class="btn btn-outline" href="{{ route('orders.show', $order) }}">Lihat transaksi</a>
            </div>
        </div>

        <aside class="card tint stack">
            <h3>Selanjutnya</h3>
            <ol class="steps-ol">
                <li>Atur waktu dan tempat serah terima dengan {{ $order->seller->firstName() }}.</li>
                <li>Cek kondisi dan kelengkapan barang saat bertemu.</li>
                <li>{{ $order->isRent() ? 'Kembalikan barang sesuai tanggal sewa agar deposit kembali.' : 'Konfirmasi di StudentLOOP setelah barang kamu terima.' }}</li>
            </ol>
        </aside>
    </div>
@endsection
