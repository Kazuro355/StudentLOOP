@extends('layouts.app')
@section('title', $current ? 'Chat' : 'Pesan')

@section('content')
    <div class="chat-shell {{ $current ? 'has-current' : '' }}">
        {{-- Daftar percakapan (layar "Pesan") --}}
        <section class="conv-wrap">
            <h1 style="margin-bottom:14px">Pesan</h1>
            <div class="conv-list">
                @forelse ($conversations as $c)
                    @php($o = $c->other(auth()->user()))
                    @php($unread = $c->unreadFor(auth()->user()))
                    <a class="conv {{ $current && $current->id === $c->id ? 'active' : '' }}" href="{{ route('conversations.show', $c) }}">
                        <span class="avatar">{{ $o->initials() }}</span>
                        <span class="grow">
                            <span class="row-between"><span class="name">{{ $o->name }}</span><span class="muted xs">{{ $c->last_message_at?->diffForHumans(null, true) }}</span></span>
                            @if ($c->item)<span class="xs primary" style="display:block">{{ $c->item->title }}</span>@endif
                            <span class="preview" style="display:block">{{ $c->lastMessage?->body }}</span>
                        </span>
                        @if ($unread)<span class="badge" style="background:var(--primary);color:#fff">{{ $unread }}</span>@endif
                    </a>
                @empty
                    <div class="card empty">
                        <h3>Belum ada percakapan</h3>
                        <p class="small">Buka barang lalu tekan <b>Chat pemilik</b> untuk memulai.</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Percakapan terbuka (layar "Chat") --}}
        <section class="chat-wrap">
            @if ($current)
                <div class="chat-box">
                    <div class="chat-head">
                        <a class="chip hide-desktop" href="{{ route('conversations.index') }}">Kembali</a>
                        <a class="avatar" href="{{ route('users.show', $other) }}">{{ $other->initials() }}</a>
                        <div class="grow">
                            <a class="bold" href="{{ route('users.show', $other) }}">{{ $other->name }}</a>
                            @if ($current->item)<br><a class="xs primary" href="{{ route('items.show', $current->item) }}">{{ $current->item->title }} · {{ $current->item->price_label }}</a>@endif
                        </div>
                        <a class="link small" href="{{ route('reports.create', ['type' => 'user', 'id' => $other->id]) }}">Laporkan</a>
                    </div>

                    <div class="chat-log" aria-live="polite">
                        @forelse ($messages as $m)
                            <div class="bubble {{ $m->sender_id === auth()->id() ? 'me' : '' }}">{{ $m->body }}<time>{{ $m->created_at->format('H:i') }}</time></div>
                        @empty
                            <p class="muted small center" style="margin:auto">Mulai percakapan dengan menyapa {{ $other->firstName() }}.</p>
                        @endforelse
                    </div>

                    <form class="chat-form" method="POST" action="{{ route('messages.store', $current) }}" data-once>
                        @csrf
                        <input type="text" name="body" placeholder="Tulis pesan..." maxlength="2000" required autocomplete="off" aria-label="Pesan">
                        <button class="btn" type="submit">Kirim</button>
                    </form>
                </div>
            @else
                <div class="card empty hide-mobile" style="min-height:50vh;display:grid;place-content:center">
                    <h3>Pilih percakapan</h3>
                    <p class="small">Pesanmu dengan pemilik dan peminjam tampil di sini.</p>
                </div>
            @endif
        </section>
    </div>
@endsection
