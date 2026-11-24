@extends('layouts.app')
@section('title', $wanted->title)

@section('content')
    @include('partials.pagehead', ['title' => 'Permintaan Wanted', 'back' => route('wanted.index')])

    <div class="cols c-2-1">
        <div class="stack-lg">
            <div class="card stack">
                <div class="row wrap">
                    <span class="badge {{ $wanted->mode === 'sewa' ? '' : 'yellow' }}">{{ $wanted->mode === 'sewa' ? 'Sewa' : 'Beli' }}</span>
                    @if ($wanted->category)<span class="badge">{{ $wanted->category->name }}</span>@endif
                    <span class="badge {{ $wanted->status === 'aktif' ? 'ok' : 'warn' }}">{{ ucfirst($wanted->status) }}</span>
                </div>
                <h1>{{ $wanted->title }}</h1>
                <a class="row" href="{{ route('users.show', $wanted->user) }}">
                    <span class="avatar">{{ $wanted->user->initials() }}</span>
                    <span><b>{{ $wanted->user->name }}</b><br><span class="muted xs">{{ $wanted->user->jurusan }} · {{ $wanted->created_at->diffForHumans() }}</span></span>
                </a>
                @if ($wanted->details)<p class="muted">{!! nl2br(e($wanted->details)) !!}</p>@endif
                <div class="facts">
                    <div class="fact"><small>Budget</small><b>{{ $wanted->budgetLabel() }}</b></div>
                    <div class="fact"><small>Dibutuhkan</small><b>{{ $wanted->needed_by?->translatedFormat('j M Y') ?? 'Fleksibel' }}</b></div>
                    <div class="fact"><small>Kampus</small><b>{{ $wanted->kampus ?: 'Semua' }}</b></div>
                </div>
            </div>

            <section class="stack">
                <h2>Tawaran ({{ $wanted->offers->count() }})</h2>
                @forelse ($wanted->offers as $offer)
                    @if ($isOwner || $offer->user_id === auth()->id())
                        <div class="card tight stack">
                            <div class="row-between wrap">
                                <a class="row" href="{{ route('users.show', $offer->user) }}"><span class="avatar sm">{{ $offer->user->initials() }}</span><b>{{ $offer->user->name }}</b></a>
                                <span class="badge {{ ['menunggu' => 'warn', 'diterima' => 'ok', 'ditolak' => 'bad'][$offer->status] ?? '' }}">{{ ucfirst($offer->status) }}</span>
                            </div>
                            <p class="small">{{ $offer->message }}</p>
                            @if ($offer->item)<a class="link small" href="{{ route('items.show', $offer->item) }}">Lihat barang: {{ $offer->item->title }} ({{ $offer->item->price_label }})</a>@endif
                            @if ($offer->price)<p class="small">Harga tawaran: <b class="primary">@rp($offer->price)</b></p>@endif

                            @if ($isOwner && $offer->status === 'menunggu')
                                <div class="btn-row">
                                    <form method="POST" action="{{ route('wanted.respond', $offer) }}">@csrf<input type="hidden" name="decision" value="terima"><button class="btn btn-sm" type="submit">Terima &amp; chat</button></form>
                                    <form method="POST" action="{{ route('wanted.respond', $offer) }}">@csrf<input type="hidden" name="decision" value="tolak"><button class="btn btn-danger btn-sm" type="submit">Tolak</button></form>
                                </div>
                            @endif
                        </div>
                    @endif
                @empty
                    <div class="card empty"><p class="small">Belum ada tawaran.</p></div>
                @endforelse
                @if (! $isOwner && $wanted->offers->isNotEmpty() && ! $myOffer)
                    <p class="muted xs">Tawaran dari pengguna lain hanya terlihat oleh peminta.</p>
                @endif
            </section>
        </div>

        <aside class="stack-lg sticky-side">
            @if ($isOwner)
                <div class="card stack">
                    <h3>Kelola permintaan</h3>
                    @if ($wanted->status === 'aktif')
                        <form method="POST" action="{{ route('wanted.close', $wanted) }}">@csrf<input type="hidden" name="status" value="terpenuhi"><button class="btn btn-block" type="submit">Tandai terpenuhi</button></form>
                        <form method="POST" action="{{ route('wanted.close', $wanted) }}" data-confirm="Tutup permintaan ini?">@csrf<button class="btn btn-danger btn-block" type="submit">Tutup permintaan</button></form>
                    @else
                        <p class="muted small">Permintaan ini sudah {{ $wanted->status }}.</p>
                    @endif
                </div>
            @elseif ($wanted->status === 'aktif')
                <form method="POST" action="{{ route('wanted.offer', $wanted) }}" class="card stack" data-once>
                    @csrf
                    <h3>Punya barangnya?</h3>
                    @if ($myOffer && $myOffer->status === 'menunggu')
                        <p class="muted small">Tawaranmu sudah terkirim. Tunggu respons {{ $wanted->user->firstName() }}.</p>
                    @else
                        @if ($myItems->isNotEmpty())
                            <x-select name="item_id" label="Tawarkan barangmu (opsional)" :options="$myItems->pluck('title', 'id')->all()" placeholder="Pilih dari barang saya" />
                        @endif
                        <x-field name="price" type="number" prefix="Rp" label="Harga tawaran (opsional)" min="0" />
                        <x-textarea name="message" label="Pesan" maxlength="500" placeholder="Contoh: Saya punya, kondisi baik, bisa diambil di Kemanggisan." style="min-height:90px" required />
                        <button class="btn btn-block" type="submit">Kirim tawaran</button>
                    @endif
                    <a class="btn btn-outline btn-block" href="{{ route('conversations.user', $wanted->user) }}">Chat peminta</a>
                </form>
            @else
                <div class="notice">Permintaan ini sudah {{ $wanted->status }}.</div>
            @endif
        </aside>
    </div>
@endsection
