@extends('layouts.app')
@section('title', 'Dicari')

@section('content')
    @include('partials.pagehead', [
        'title' => 'Dicari',
        'subtitle' => 'Permintaan barang dari sesama Binusian. Punya barangnya? Tawarkan.',
        'back' => route('beranda'),
        'action' => new \Illuminate\Support\HtmlString('<a class="btn btn-sm" href="' . e(route('wanted.create')) . '">+ Buat permintaan</a>'),
    ])

    <div class="row-between wrap" style="margin-bottom:16px">
        <div class="chips">
            @foreach ([null => 'Semua', 'sewa' => 'Sewa', 'beli' => 'Beli'] as $k => $label)
                <a class="chip {{ $mode === $k ? 'active' : '' }}" href="{{ route('wanted.index', array_filter(['mode' => $k, 'q' => request('q')])) }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('wanted.index') }}" class="search" style="order:0;flex:0 1 320px">
            @if ($mode)<input type="hidden" name="mode" value="{{ $mode }}">@endif
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari permintaan..." aria-label="Cari permintaan">
        </form>
    </div>

    @forelse ($wanted as $w)
        <a class="card tight stack" style="margin-bottom:12px;gap:6px" href="{{ route('wanted.show', $w) }}">
            <div class="row-between wrap">
                <div class="row wrap">
                    <span class="badge {{ $w->mode === 'sewa' ? '' : 'yellow' }}">{{ $w->mode === 'sewa' ? 'Sewa' : 'Beli' }}</span>
                    @if ($w->category)<span class="badge">{{ $w->category->name }}</span>@endif
                </div>
                <span class="muted xs">{{ $w->created_at->diffForHumans() }}</span>
            </div>
            <b>{{ $w->title }}</b>
            @if ($w->details)<p class="muted small">{{ \Illuminate\Support\Str::limit($w->details, 140) }}</p>@endif
            <p class="small"><span class="primary bold">{{ $w->budgetLabel() }}</span>
                @if ($w->needed_by) · dibutuhkan {{ $w->needed_by->translatedFormat('j M Y') }}@endif
                @if ($w->kampus) · {{ $w->kampus }}@endif
            </p>
            <p class="muted xs">oleh {{ $w->user->name }} · {{ $w->offers_count }} tawaran</p>
        </a>
    @empty
        <div class="card empty">
            <h3>Belum ada permintaan</h3>
            <p class="small">Cari barang yang belum ada di marketplace? Jadilah yang pertama.</p>
            <a class="btn" href="{{ route('wanted.create') }}" style="margin-top:12px">Buat permintaan Wanted</a>
        </div>
    @endforelse

    {{ $wanted->links() }}
@endsection
