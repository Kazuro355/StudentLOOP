@extends('layouts.app')
@section('title', 'Pengguna · Admin')

@section('content')
    @include('partials.pagehead', ['title' => 'Kelola pengguna', 'subtitle' => 'Cari akun dan kelola akses marketplace.', 'back' => route('admin.dashboard')])

    <form class="card admin-search" method="GET" action="{{ route('admin.users') }}">
        <label class="sr-only" for="user-search">Cari nama, email, atau NIM</label>
        <input id="user-search" name="q" type="search" value="{{ $term }}" placeholder="Cari nama, email, atau NIM">
        <button class="btn btn-sm" type="submit">Cari</button>
        @if ($term !== '')<a class="btn btn-outline btn-sm" href="{{ route('admin.users') }}">Hapus</a>@endif
    </form>

    <div class="stack section">
        @forelse ($users as $user)
            <article class="card admin-user">
                <div class="row grow">
                    <span class="avatar">{{ $user->initials() }}</span>
                    <div class="grow stack">
                        <div class="row wrap"><strong>{{ $user->name }}</strong>@if($user->isAdmin())<span class="badge yellow">Admin</span>@endif
                            @if($user->isSuspended())<span class="badge bad">Dinonaktifkan</span>@else<span class="badge ok">Aktif</span>@endif
                        </div>
                        <span class="muted small">{{ $user->email }} · NIM {{ $user->nim ?: '—' }} · {{ $user->items_count }} barang</span>
                    </div>
                </div>
                @unless ($user->isAdmin() || $user->is(auth()->user()))
                    <form method="POST" action="{{ route('admin.users.suspension', $user) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="suspended" value="{{ $user->isSuspended() ? '0' : '1' }}">
                        <button class="btn btn-sm {{ $user->isSuspended() ? 'btn-outline' : 'btn-danger' }}" type="submit">
                            {{ $user->isSuspended() ? 'Aktifkan akun' : 'Nonaktifkan' }}
                        </button>
                    </form>
                @endunless
            </article>
        @empty
            <div class="card empty"><h3>Pengguna tidak ditemukan</h3><p>Coba kata kunci lain.</p></div>
        @endforelse
    </div>

    {{ $users->links() }}
@endsection
