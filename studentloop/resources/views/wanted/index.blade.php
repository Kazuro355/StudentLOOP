@extends('layouts.app')
@section('title', 'Dicari')

@section('content')
    <section class="wanted-hero">
        <div>
            <span class="wanted-kicker">COMMUNITY MARKETPLACE</span>
            <h1>Barang yang dicari</h1>
            <p>Temukan kebutuhan sesama mahasiswa, tawarkan barangmu, lalu lanjutkan ke transaksi jual atau sewa.</p>
        </div>
        <a class="btn" href="{{ route('wanted.create') }}">+ Buat permintaan</a>
    </section>

    <div class="wanted-toolbar">
        <div class="wanted-tabs">
            @foreach ([null => 'Semua', 'sewa' => 'Sewa', 'beli' => 'Beli'] as $k => $label)
                <a class="{{ $mode === $k ? 'active' : '' }}" href="{{ route('wanted.index', array_filter(['mode' => $k, 'q' => request('q')])) }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('wanted.index') }}" class="wanted-search">
            @if ($mode)<input type="hidden" name="mode" value="{{ $mode }}">@endif
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari permintaan..." aria-label="Cari permintaan">
        </form>
    </div>

    <div class="wanted-results-head">
        <h2>{{ $wanted->total() }} permintaan</h2>
        <span>{{ $mode ? ucfirst($mode) : 'Semua jenis' }}</span>
    </div>

    <div class="wanted-grid">
        @forelse ($wanted as $w)
            <a class="wanted-card" href="{{ route('wanted.show', $w) }}">
                <div class="wanted-card-top">
                    <span class="wanted-mode-badge {{ $w->mode === 'sewa' ? 'rent' : 'buy' }}">
                        {{ $w->mode === 'sewa' ? 'Dicari untuk sewa' : 'Dicari untuk beli' }}
                    </span>
                    <span class="wanted-time">{{ $w->created_at->diffForHumans() }}</span>
                </div>

                <div class="wanted-card-body">
                    @if ($w->category)<span class="wanted-category">{{ $w->category->name }}</span>@endif
                    <h3>{{ $w->title }}</h3>
                    @if ($w->details)<p>{{ \Illuminate\Support\Str::limit($w->details, 110) }}</p>@endif
                </div>

                <div class="wanted-info">
                    <div><small>Anggaran</small><b>{{ $w->budgetLabel() }}</b></div>
                    <div><small>Lokasi</small><b>{{ $w->kampus ?: 'Semua kampus' }}</b></div>
                </div>

                <div class="wanted-card-footer">
                    <span class="wanted-person">
                        <span class="avatar sm">{{ $w->user->initials() }}</span>
                        <span><small>Dibuat oleh</small><b>{{ $w->user->name }}</b></span>
                    </span>
                    <span class="wanted-offers">{{ $w->offers_count }} tawaran</span>
                </div>
            </a>
        @empty
            <div class="card empty wanted-empty">
                <h3>Belum ada permintaan</h3>
                <p class="small">Belum ada barang yang dicari untuk filter ini.</p>
                <a class="btn" href="{{ route('wanted.create') }}">Buat permintaan Wanted</a>
            </div>
        @endforelse
    </div>

    {{ $wanted->links() }}
@endsection
