@extends('layouts.app')
@section('title', 'Barang saya & permintaan')

@section('content')
    @include('partials.pagehead', ['title' => 'Barang saya & permintaan', 'back' => route('profile.show')])

    <div class="tabs" style="margin-bottom:16px">
        <a class="tab {{ $tab === 'barang' ? 'active' : '' }}" href="{{ route('items.mine') }}">Barang saya</a>
        <a class="tab {{ $tab === 'permintaan' ? 'active' : '' }}" href="{{ route('items.mine', ['tab' => 'permintaan']) }}">Permintaan masuk @if ($incoming->isNotEmpty())({{ $incoming->count() }})@endif</a>
        <a class="tab {{ $tab === 'wanted' ? 'active' : '' }}" href="{{ route('items.mine', ['tab' => 'wanted']) }}">Wanted saya</a>
    </div>

    @if ($tab === 'barang')
        <div class="btn-row" style="margin-bottom:16px;max-width:420px">
            <a class="btn" href="{{ route('items.create.sell') }}">Jual barang</a>
            <a class="btn btn-yellow" href="{{ route('items.create.rent') }}">Sewakan barang</a>
        </div>

        @forelse ($items as $item)
            <div class="card tight order-row" style="margin-bottom:12px">
                <div class="order-thumb">@if ($item->cover_url)<img src="{{ $item->cover_url }}" alt="">@else {{ $item->category?->name }} @endif</div>
                <div class="grow stack" style="--x:0">
                    <div class="row wrap">
                        <span class="badge">{{ $item->type_label }}</span>
                        <span class="badge {{ $item->status === 'tersedia' ? 'ok' : 'warn' }}">{{ ucfirst($item->status) }}</span>
                    </div>
                    <a class="bold" href="{{ route('items.show', $item) }}">{{ $item->title }}</a>
                    <p class="muted small">{{ $item->price_label }} · {{ $item->views }}× dilihat · {{ $item->favorited_by_count }} favorit</p>
                    <div class="btn-row">
                        <a class="btn btn-outline btn-sm" href="{{ route('items.edit', $item) }}">Ubah</a>
                        @if (in_array($item->status, ['tersedia', 'nonaktif'], true))
                            <form method="POST" action="{{ route('items.toggle', $item) }}">@csrf
                                <button class="btn btn-outline btn-sm" type="submit">{{ $item->status === 'tersedia' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('items.destroy', $item) }}" data-confirm="Hapus barang ini?">@csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="card empty"><h3>Belum ada barang</h3><p class="small">Jual atau sewakan barang yang jarang kamu pakai.</p></div>
        @endforelse

    @elseif ($tab === 'permintaan')
        @forelse ($incoming as $order)
            <a class="card tight order-row" style="margin-bottom:12px" href="{{ route('orders.show', $order) }}">
                <div class="grow">
                    <div class="row-between">
                        <b>{{ $order->item->title }}</b>
                        <span class="badge {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span>
                    </div>
                    <p class="muted small">{{ $order->buyer->name }} · {{ $order->isRent() ? $order->start_date->translatedFormat('j M') . ' – ' . $order->end_date->translatedFormat('j M Y') : 'Pembelian' }} · @rp($order->total)</p>
                    @if ($order->status === 'menunggu_persetujuan')<p class="small primary bold">Perlu keputusanmu</p>@endif
                </div>
            </a>
        @empty
            <div class="card empty"><h3>Tidak ada permintaan aktif</h3><p class="small">Permintaan sewa dan pesanan baru akan muncul di sini.</p></div>
        @endforelse

    @else
        <a class="btn" href="{{ route('wanted.create') }}" style="margin-bottom:16px">Buat permintaan Wanted</a>
        @forelse ($wanted as $w)
            <a class="card tight order-row" style="margin-bottom:12px" href="{{ route('wanted.show', $w) }}">
                <div class="grow">
                    <div class="row-between"><b>{{ $w->title }}</b><span class="badge {{ $w->status === 'aktif' ? 'ok' : '' }}">{{ ucfirst($w->status) }}</span></div>
                    <p class="muted small">{{ $w->mode === 'sewa' ? 'Sewa' : 'Beli' }} · {{ $w->budgetLabel() }} · {{ $w->offers_count }} tawaran</p>
                </div>
            </a>
        @empty
            <div class="card empty"><h3>Belum ada permintaan</h3><p class="small">Cari barang yang belum ada? Buat permintaan Wanted.</p></div>
        @endforelse
    @endif
@endsection
