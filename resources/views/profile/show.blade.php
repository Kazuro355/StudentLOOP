@extends('layouts.app')
@section('title', 'Profil')

@section('content')
    <div class="cols c-sidebar">
        <aside class="stack">
            <div class="card center stack">
                <span class="avatar lg" style="margin:0 auto">{{ $user->initials() }}</span>
                <div>
                    <h2>{{ $user->name }}</h2>
                    <p class="muted small">{{ $user->jurusan }} · Angkatan {{ $user->angkatan }}</p>
                    <p class="muted small">{{ $user->kampus }} · NIM {{ $user->nim }}</p>
                </div>
                @if ($user->bio)<p class="small">{{ $user->bio }}</p>@endif
                <a class="btn btn-outline btn-block" href="{{ route('profile.edit') }}">Ubah profil</a>
            </div>

            <div class="card tight">
                <div class="facts" style="grid-template-columns:repeat(3,1fr);text-align:center">
                    <div class="fact"><small>Barang</small><b>{{ $stats['barang'] }}</b></div>
                    <div class="fact"><small>Transaksi</small><b>{{ $stats['transaksi'] }}</b></div>
                    <div class="fact"><small>Rating</small><b>{{ $stats['rating'] ? $stats['rating'] . '★' : '–' }}</b></div>
                </div>
            </div>
        </aside>

        <div class="stack-lg">
            <div class="card stack">
                <h3>Menu</h3>
                <div class="chips">
                    <a class="chip soft" href="{{ route('items.mine') }}">Barang saya</a>
                    <a class="chip soft" href="{{ route('orders.loans') }}">Pinjaman saya</a>
                    <a class="chip soft" href="{{ route('favorites.index') }}">Favorit</a>
                    <a class="chip soft" href="{{ route('wanted.index') }}">Wanted</a>
                    <a class="chip soft" href="{{ route('notifications.index') }}">Notifikasi</a>
                    <a class="chip soft" href="{{ route('reports.create') }}">Laporkan &amp; keamanan</a>
                </div>
                <div class="btn-row">
                    <a class="btn" href="{{ route('items.create.sell') }}">Jual barang</a>
                    <a class="btn btn-yellow" href="{{ route('items.create.rent') }}">Sewakan barang</a>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.password') }}" class="card stack" data-once>
                @csrf @method('PUT')
                <h3>Pengaturan akun</h3>
                <p class="muted small">Email akun: {{ $user->email }}</p>
                <div class="form-grid three">
                    <x-field name="current_password" type="password" label="Kata sandi saat ini" autocomplete="current-password" required />
                    <x-field name="password" type="password" label="Kata sandi baru" autocomplete="new-password" required />
                    <x-field name="password_confirmation" type="password" label="Ulangi kata sandi baru" autocomplete="new-password" required />
                </div>
                <button class="btn" type="submit" style="justify-self:start">Ganti kata sandi</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-danger btn-block" type="submit">Keluar</button>
            </form>
        </div>
    </div>
@endsection
