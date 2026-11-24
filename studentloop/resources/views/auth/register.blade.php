@extends('layouts.auth')
@section('title', 'Daftar')

@section('content')
    <h1>Buat akun</h1>
    <p class="muted small" style="margin-top:4px">Cukup email aktif apa saja. Tidak perlu verifikasi email kampus.</p>

    <form method="POST" action="{{ route('register.post') }}" class="stack" style="margin-top:18px" data-once>
        @csrf
        <x-field name="name" label="Nama lengkap" placeholder="Nama lengkap" required autocomplete="name" />
        <x-field name="nim" label="NIM" placeholder="Contoh: 2602123456" required inputmode="numeric" />
        <x-field name="email" type="email" label="Email" placeholder="nama@email.com" required autocomplete="email" />
        <x-field name="password" type="password" label="Kata sandi" placeholder="Minimal 8 karakter" required autocomplete="new-password" />
        <x-field name="password_confirmation" type="password" label="Ulangi kata sandi" placeholder="Ulangi kata sandi" required autocomplete="new-password" />

        <button class="btn btn-block" type="submit">Daftar &amp; lengkapi profil</button>
        <p class="center muted small">Sudah punya akun? <a class="link" href="{{ route('login') }}">Masuk</a></p>
    </form>
@endsection
