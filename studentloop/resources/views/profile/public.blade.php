@extends('layouts.app')
@section('title', $user->name)

@section('content')
    @include('partials.pagehead', ['title' => 'Profil pengguna', 'back' => url()->previous() !== url()->current() ? url()->previous() : route('beranda')])

    <div class="cols c-sidebar">
        <aside class="stack">
            <div class="card center stack">
                <span class="avatar lg" style="margin:0 auto">{{ $user->initials() }}</span>
                <div>
                    <h2>{{ $user->name }}</h2>
                    <p class="muted small">{{ $user->jurusan }} · Angkatan {{ $user->angkatan }}</p>
                    <p class="muted small">{{ $user->kampus }}</p>
                    <p style="margin-top:6px"><span class="star-static">★</span> <b>{{ $user->ratingAvg() ?? '–' }}</b> <span class="muted small">({{ $reviews->count() }} ulasan)</span></p>
                </div>
                @if ($user->bio)<p class="small">{{ $user->bio }}</p>@endif

                @if ($user->id !== auth()->id())
                    <a class="btn btn-block" href="{{ route('conversations.user', $user) }}">Chat</a>
                    <a class="btn btn-outline btn-block" href="{{ route('reports.create', ['type' => 'user', 'id' => $user->id]) }}">Laporkan</a>
                    <form method="POST" action="{{ $isBlocked ? route('users.unblock', $user) : route('users.block', $user) }}" data-confirm="{{ $isBlocked ? 'Buka blokir pengguna ini?' : 'Blokir pengguna ini? Kalian tidak akan saling melihat barang dan pesan.' }}">
                        @csrf @if ($isBlocked) @method('DELETE') @endif
                        <button class="btn btn-danger btn-block" type="submit">{{ $isBlocked ? 'Buka blokir' : 'Blokir pengguna' }}</button>
                    </form>
                @endif
            </div>
        </aside>

        <div class="stack-lg">
            <section>
                <div class="section-head"><h2>Barang tersedia</h2></div>
                @if ($items->isEmpty())
                    <div class="card empty"><p>Belum ada barang yang tersedia.</p></div>
                @else
                    <div class="grid-items">@foreach ($items as $item) @include('partials.item-card', ['item' => $item->setRelation('owner', $user)]) @endforeach</div>
                @endif
            </section>

            <section>
                <div class="section-head"><h2>Ulasan</h2></div>
                <div class="stack">
                    @forelse ($reviews as $review)
                        <div class="card tight">
                            <div class="row-between"><b>{{ $review->reviewer->name }}</b><span class="star-static">{{ str_repeat('★', $review->rating) }}</span></div>
                            <p class="muted xs">{{ $review->item->title }} · {{ $review->created_at->translatedFormat('j M Y') }}</p>
                            @if ($review->comment)<p style="margin-top:6px">{{ $review->comment }}</p>@endif
                        </div>
                    @empty
                        <div class="card empty"><p>Belum ada ulasan.</p></div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
