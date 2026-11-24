@extends('layouts.app')
@section('title', 'Notifikasi')

@section('content')
    @include('partials.pagehead', [
        'title' => 'Notifikasi',
        'subtitle' => 'Kabar transaksi, pembayaran, dan ulasan.',
        'action' => new \Illuminate\Support\HtmlString(
            '<form method="POST" action="' . e(route('notifications.readAll')) . '">' . csrf_field() .
            '<button class="btn btn-outline btn-sm" type="submit">Tandai semua dibaca</button></form>'
        ),
    ])

    <div class="chips-scroll" style="margin-bottom:16px">
        <a class="chip {{ ! $type ? 'active' : '' }}" href="{{ route('notifications.index') }}">Semua</a>
        @foreach (\App\Models\UserNotification::TYPES as $k => $label)
            <a class="chip {{ $type === $k ? 'active' : '' }}" href="{{ route('notifications.index', ['jenis' => $k]) }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="stack">
        @forelse ($notifications as $n)
            <a class="notif {{ $n->read_at ? '' : 'unread' }}" href="{{ route('notifications.open', $n) }}">
                <div class="row-between">
                    <div class="row"><span class="badge">{{ \App\Models\UserNotification::TYPES[$n->type] ?? $n->type }}</span><b>{{ $n->title }}</b></div>
                    <span class="when">{{ $n->created_at->diffForHumans() }}</span>
                </div>
                @if ($n->body)<p class="muted small" style="margin-top:4px">{{ $n->body }}</p>@endif
            </a>
        @empty
            <div class="card empty">
                <h3>Belum ada notifikasi</h3>
                <p class="small">Kabar terbaru tentang transaksimu akan muncul di sini.</p>
            </div>
        @endforelse
    </div>

    {{ $notifications->links() }}
@endsection
