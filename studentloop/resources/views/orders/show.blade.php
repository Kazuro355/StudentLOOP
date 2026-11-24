@extends('layouts.app')
@section('title', 'Transaksi ' . $order->code)

@section('content')
    @php
        $isBuyer = $party === 'buyer';
        $isSeller = $party === 'seller';
        $steps = $order->steps();
        $keys = array_keys($steps);
        $currentIdx = array_search($order->status, $keys, true);
        $terminal = in_array($order->status, ['ditolak', 'dibatalkan'], true);
        $pay = $order->latestPayment;
    @endphp

    @include('partials.pagehead', ['title' => $order->item->title, 'subtitle' => $order->code, 'back' => route('orders.index')])

    <div class="cols c-2-1">
        <div class="stack-lg">
            <div class="card stack">
                <div class="row-between wrap">
                    <h3>Status</h3>
                    <span class="badge {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span>
                </div>

                @if ($terminal)
                    <p class="muted small">Transaksi ini {{ $order->status === 'ditolak' ? 'ditolak oleh pemilik' : 'dibatalkan' }}.</p>
                @else
                    <ol class="timeline">
                        @foreach ($steps as $key => $label)
                            @php($idx = array_search($key, $keys, true))
                            <li class="{{ $currentIdx !== false && $idx < $currentIdx ? 'done' : '' }} {{ $idx === $currentIdx ? ($order->status === 'selesai' ? 'done' : 'current') : '' }}">{{ $label }}</li>
                        @endforeach
                    </ol>
                @endif
            </div>

            <div class="card stack">
                <h3>Detail</h3>
                <div class="order-row">
                    <div class="order-thumb">@if ($order->item->cover_url)<img src="{{ $order->item->cover_url }}" alt="">@else {{ $order->item->category?->name }} @endif</div>
                    <div class="grow">
                        <a class="bold" href="{{ route('items.show', $order->item) }}">{{ $order->item->title }}</a>
                        <p class="muted small">{{ $order->isRent() ? 'Sewa' : 'Pembelian' }} · {{ \App\Models\Item::PICKUP[$order->pickup_method] ?? $order->pickup_method }}</p>
                    </div>
                </div>
                @if ($order->isRent())
                    <div class="kv"><span>Tanggal sewa</span><span>{{ $order->start_date->translatedFormat('j M Y') }} – {{ $order->end_date->translatedFormat('j M Y') }}</span></div>
                    <div class="kv"><span>Durasi</span><span>{{ $order->days }} hari</span></div>
                @endif
                <div class="kv"><span>{{ $isBuyer ? 'Pemilik' : 'Peminjam / pembeli' }}</span><a class="link" href="{{ route('users.show', $other) }}">{{ $other->name }}</a></div>
                @if ($order->item->meeting_point)<div class="kv"><span>Titik temu</span><span>{{ $order->item->meeting_point }}</span></div>@endif
                @if ($order->note)<div class="kv"><span>Catatan</span><span class="right">{{ $order->note }}</span></div>@endif
            </div>

            @if ($order->photos->isNotEmpty())
                <div class="card stack">
                    <h3>Foto bukti transaksi</h3>
                    <div class="row wrap">
                        @foreach ($order->photos as $photo)
                            <a href="{{ $photo->url }}" target="_blank" rel="noopener" class="stack" style="width:150px">
                                <img src="{{ $photo->url }}" alt="Foto {{ $photo->type }}" style="width:150px;height:110px;object-fit:cover;border-radius:8px">
                                <span class="small muted">{{ $photo->type === 'handover' ? 'Serah terima' : ($photo->type === 'return' ? 'Pengembalian' : 'Penerimaan') }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <aside class="stack-lg sticky-side">
            <div class="card stack">
                <h3>Rincian pembayaran</h3>
                <div class="kv"><span>{{ $order->isRent() ? 'Sewa (' . $order->days . ' hari)' : 'Harga barang' }}</span><span>@rp($order->subtotal)</span></div>
                @if ($order->deposit)<div class="kv"><span>Deposit (dikembalikan)</span><span>@rp($order->deposit)</span></div>@endif
                <div class="kv total"><span>Total</span><span>@rp($order->total)</span></div>
                @if ($pay)
                    <p class="muted small">Pembayaran: {{ $pay->methodLabel() }} · {{ $pay->status === 'paid' ? 'Lunas' : ($pay->status === 'pending' ? 'Menunggu' : 'Kedaluwarsa') }}</p>
                @endif
                @if ($order->isRent() && $order->escrow_status === 'held')
                    <div class="notice stack">
                        <strong>Seluruh dana sedang ditahan admin</strong>
                        <span class="small">Biaya sewa @rp($order->subtotal) dan deposit @rp($order->deposit) baru dicairkan setelah barang dikembalikan, kedua pihak mengonfirmasi, lalu admin menyetujui.</span>
                    </div>
                @elseif ($order->isRent() && $order->escrow_status === 'released')
                    <div class="notice stack">
                        <strong>Pencairan disetujui admin</strong>
                        <span class="small">Biaya sewa @rp($order->subtotal) untuk pemilik dan deposit @rp($order->deposit) untuk penyewa dicatat selesai{{ $order->funds_released_at ? ' pada ' . $order->funds_released_at->translatedFormat('j M Y, H:i') : '' }}.</span>
                    </div>
                @endif
            </div>

            <div class="card stack">
                <h3>Aksi</h3>

                @if ($isBuyer)
                    @switch($order->status)
                        @case('menunggu_persetujuan')
                            <p class="muted small">Menunggu pemilik menyetujui pengajuanmu.</p>
                            @break
                        @case('menunggu_pembayaran')
                            <a class="btn btn-block" href="{{ route('payments.choose', $order) }}">Bayar sekarang</a>
                            @break
                        @case('dibayar')
                            <p class="muted small">Pembayaran diterima. Atur waktu serah terima dengan pemilik lewat chat.</p>
                            @break
                        @case('diserahkan')
                            @if ($order->isRent())
                                <form method="POST" action="{{ route('orders.returned', $order) }}" enctype="multipart/form-data" data-confirm="Tandai barang sudah dikembalikan ke pemilik?">@csrf
                                    <label class="small"><b>Foto bukti pengembalian</b></label>
                                    <input class="input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
                                    <button class="btn btn-block" type="submit">Barang sudah kukembalikan</button></form>
                            @else
                                <form method="POST" action="{{ route('orders.received', $order) }}" enctype="multipart/form-data" data-confirm="Konfirmasi barang sudah kamu terima?">@csrf
                                    <label class="small"><b>Foto bukti penerimaan</b></label>
                                    <input class="input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
                                    <button class="btn btn-block" type="submit">Barang sudah diterima</button></form>
                            @endif
                            @break
                        @case('pengembalian')
                            <p class="muted small">Kamu sudah mengonfirmasi pengembalian. Menunggu pemilik memeriksa barang; seluruh dana tetap ditahan admin.</p>
                            @break
                        @case('menunggu_persetujuan_admin')
                            <p class="muted small">Kedua pihak sudah mengonfirmasi barang kembali. Admin sedang meninjau pencairan biaya sewa dan deposit.</p>
                            @break
                    @endswitch

                    @if (in_array($order->status, ['menunggu_persetujuan', 'menunggu_pembayaran'], true))
                        <form method="POST" action="{{ route('orders.cancel', $order) }}" data-confirm="Batalkan transaksi ini?">@csrf
                            <button class="btn btn-danger btn-block" type="submit">Batalkan</button></form>
                    @endif
                @endif

                @if ($isSeller)
                    @switch($order->status)
                        @case('menunggu_persetujuan')
                            <div class="btn-row">
                                <form method="POST" action="{{ route('orders.respond', $order) }}">@csrf<input type="hidden" name="decision" value="setuju"><button class="btn" type="submit">Setujui</button></form>
                                <form method="POST" action="{{ route('orders.respond', $order) }}" data-confirm="Tolak permintaan ini?">@csrf<input type="hidden" name="decision" value="tolak"><button class="btn btn-danger" type="submit">Tolak</button></form>
                            </div>
                            @break
                        @case('menunggu_pembayaran')
                            <p class="muted small">Menunggu pembayaran dari {{ $other->firstName() }}.</p>
                            @break
                        @case('dibayar')
                            <form method="POST" action="{{ route('orders.handover', $order) }}" enctype="multipart/form-data" data-confirm="Catat bahwa barang sudah diserahkan?">@csrf
                                <label class="small"><b>Foto bukti serah terima</b></label>
                                <input class="input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
                                <button class="btn btn-block" type="submit">Serahkan barang</button></form>
                            @break
                        @case('diserahkan')
                            <p class="muted small">{{ $order->isRent() ? 'Barang sedang dipinjam. Tunggu pengembalian.' : 'Menunggu pembeli mengonfirmasi barang diterima.' }}</p>
                            @break
                        @case('pengembalian')
                            <form method="POST" action="{{ route('orders.confirmReturn', $order) }}" data-confirm="Barang sudah kembali dalam kondisi baik?">@csrf
                                <button class="btn btn-block" type="submit">Konfirmasi barang kembali</button></form>
                            @break
                        @case('menunggu_persetujuan_admin')
                            <p class="muted small">Pengembalian sudah dikonfirmasi. Biaya sewa dan deposit masih ditahan sampai admin menyetujui pencairan.</p>
                            @break
                    @endswitch
                @endif

                @if ($order->status === 'selesai')
                    @if ($myReview)
                        <p class="small">Ulasanmu: <span class="star-static">{{ str_repeat('★', $myReview->rating) }}</span></p>
                    @else
                        <a class="btn btn-block" href="{{ route('reviews.create', $order) }}">Beri ulasan</a>
                    @endif
                @endif

                <a class="btn btn-outline btn-block" href="{{ route('conversations.user', $other) }}">Chat {{ $other->firstName() }}</a>
                <a class="link small center" href="{{ route('reports.create', ['type' => 'order', 'id' => $order->id]) }}">Laporkan masalah transaksi</a>
            </div>
        </aside>
    </div>
@endsection
