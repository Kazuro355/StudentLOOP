@extends('layouts.app')
@section('title', $item->title)

@section('content')
    @php
        $available = $item->status === 'tersedia';
        $durations = collect([1, 3, 5, 7, 14, 30])->filter(fn ($d) => $d >= $item->min_days && $d <= $item->max_days)->values();
        if ($durations->isEmpty()) { $durations = collect([$item->min_days]); }
    @endphp

    <div class="cols c-1-1">
        {{-- Foto --}}
        <div data-gallery>
            <div class="gallery-main" data-gallery-main>
                @if ($item->photos->isNotEmpty())
                    <img src="{{ $item->photos->first()->url }}" alt="{{ $item->title }}">
                @else
                    <span>{{ $item->category?->name }} · belum ada foto</span>
                @endif
            </div>
            @if ($item->photos->count() > 1)
                <div class="gallery-thumbs">
                    @foreach ($item->photos as $i => $photo)
                        <button type="button" class="{{ $i === 0 ? 'active' : '' }}" data-src="{{ $photo->url }}" aria-label="Foto {{ $i + 1 }}"><img src="{{ $photo->url }}" alt=""></button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Detail --}}
        <div class="stack-lg">
            <div class="card stack">
                <div class="row wrap">
                    <span class="badge">{{ $item->type_label }}</span>
                    @if ($item->views >= 50)<span class="badge yellow">Terpopuler</span>@endif
                    @unless ($available)<span class="badge warn">{{ ucfirst($item->status) }}</span>@endunless
                    <span class="badge">{{ $item->category?->name }}</span>
                </div>

                <h1>{{ $item->title }}</h1>
                <div class="price-big">{{ $item->price_label }}</div>
                @if ($item->isRent() && $item->deposit)
                    <p class="muted small">Deposit <b>@rp($item->deposit)</b> dikembalikan setelah barang kembali. Sewa {{ $item->min_days }}–{{ $item->max_days }} hari.</p>
                @endif

                <a class="row" href="{{ route('users.show', $item->owner) }}">
                    <span class="avatar">{{ $item->owner->initials() }}</span>
                    <span>
                        <b>{{ $item->isRent() ? 'Dipinjamkan oleh' : 'Dijual oleh' }} {{ $item->owner->name }}</b><br>
                        <span class="muted xs">{{ $item->owner->jurusan }} · {{ $item->kampus }}@if ($rating) · ★ {{ $rating }}@endif</span>
                    </span>
                </a>

                <div class="tabs">
                    <button type="button" class="tab active" data-tab="detail:desc">Deskripsi</button>
                    <button type="button" class="tab" data-tab="detail:ulasan">Ulasan {{ $item->reviews->count() }}</button>
                    <button type="button" class="tab" data-tab="detail:tanya">Pertanyaan {{ $item->questions->count() }}</button>
                </div>

                <div data-tabpanel="detail:desc" class="stack">
                    <p class="muted">{!! nl2br(e($item->description)) !!}</p>
                    <div class="facts">
                        <div class="fact"><small>Kondisi</small><b>{{ $item->condition }}</b></div>
                        <div class="fact"><small>Kelengkapan</small><b>{{ $item->kelengkapan ?: '–' }}</b></div>
                        <div class="fact"><small>Serah terima</small><b>{{ $item->meeting_point ?: \App\Models\Item::PICKUP[$item->pickup_method] }}</b></div>
                    </div>
                </div>

                <div data-tabpanel="detail:ulasan" class="stack" hidden>
                    @forelse ($item->reviews as $review)
                        <div class="card tint tight">
                            <div class="row-between"><b>{{ $review->reviewer->name }}</b><span class="star-static">{{ str_repeat('★', $review->rating) }}</span></div>
                            @if ($review->comment)<p class="small" style="margin-top:4px">{{ $review->comment }}</p>@endif
                        </div>
                    @empty
                        <p class="muted small">Belum ada ulasan untuk barang ini.</p>
                    @endforelse
                </div>

                <div data-tabpanel="detail:tanya" class="stack" id="tanya" hidden>
                    @forelse ($item->questions as $q)
                        <div class="card tint tight stack">
                            <div><b>{{ $q->user->firstName() }}</b> <span class="muted xs">{{ $q->created_at->diffForHumans() }}</span><p>{{ $q->body }}</p></div>
                            @if ($q->answer)
                                <div style="padding-left:12px;border-left:3px solid var(--primary)"><b class="primary small">Pemilik</b><p class="small">{{ $q->answer }}</p></div>
                            @elseif ($isOwner)
                                <form method="POST" action="{{ route('questions.answer', $q) }}" class="row" data-once>
                                    @csrf
                                    <input type="text" name="answer" placeholder="Tulis jawaban" required maxlength="500">
                                    <button class="btn btn-sm" type="submit">Jawab</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="muted small">Belum ada pertanyaan.</p>
                    @endforelse

                    @unless ($isOwner)
                        <form method="POST" action="{{ route('questions.store', $item) }}" class="row" data-once>
                            @csrf
                            <input type="text" name="body" placeholder="Tanya pemilik soal barang ini" required maxlength="500">
                            <button class="btn btn-sm" type="submit">Kirim</button>
                        </form>
                        @error('body')<div class="err">{{ $message }}</div>@enderror
                    @endunless
                </div>
            </div>

            {{-- Aksi --}}
            <div class="card stack sticky-cta">
                @if ($isOwner)
                    <p class="muted small">Ini barang milikmu.</p>
                    <div class="btn-row">
                        <a class="btn" href="{{ route('items.edit', $item) }}">Ubah barang</a>
                        @if (in_array($item->status, ['tersedia', 'nonaktif'], true))
                            <form method="POST" action="{{ route('items.toggle', $item) }}">@csrf
                                <button class="btn btn-outline" type="submit">{{ $item->status === 'tersedia' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        @endif
                    </div>
                @elseif (! $available)
                    <div class="notice">Barang ini sedang tidak tersedia.</div>
                    <div class="btn-row">
                        <a class="btn btn-outline" href="{{ route('conversations.item', $item) }}">Chat pemilik</a>
                        @include('items._fav_button')
                    </div>
                @elseif ($item->isRent())
                    <form method="GET" action="{{ route('orders.rent', $item) }}" class="stack">
                        <div>
                            <span class="field-label">Durasi pinjam</span>
                            <div class="chips">
                                @foreach ($durations as $d)
                                    <label class="check-chip"><input type="radio" name="days" value="{{ $d }}" @checked($d == $days || ($loop->first && ! $durations->contains($days)))><span>{{ $d }} hari</span></label>
                                @endforeach
                            </div>
                        </div>
                        <div class="btn-row">
                            <button class="btn" type="submit">Ajukan pinjam</button>
                            <a class="btn btn-outline" href="{{ route('conversations.item', $item) }}">Chat pemilik</a>
                        </div>
                    </form>
                    @include('items._fav_button')
                @else
                    <div class="btn-row">
                        <a class="btn" href="{{ route('orders.buy', $item) }}">Beli sekarang</a>
                        <a class="btn btn-outline" href="{{ route('conversations.item', $item) }}">Chat pemilik</a>
                    </div>
                    @include('items._fav_button')
                @endif

                @unless ($isOwner)
                    <a class="link small center" href="{{ route('reports.create', ['type' => 'item', 'id' => $item->id]) }}">Laporkan barang ini</a>
                @endunless
            </div>
        </div>
    </div>
@endsection
