@extends('layouts.auth')
@section('title', 'Masuk')

@section('content')
    <h1>Masuk</h1>
    <form method="POST" action="{{ route('login.post') }}" class="stack" style="margin-top:18px" data-once>
        @csrf
        <x-field name="email" type="email" label="Email" placeholder="nama@email.com" required autofocus autocomplete="email" />
        <x-field name="password" type="password" label="Kata sandi" placeholder="Kata sandi" required autocomplete="current-password" />

        <div class="row-between">
            <label class="row small"><input type="checkbox" name="remember" value="1" style="accent-color:var(--primary)"> Ingat saya</label>
            <a class="link small" href="{{ route('password.request') }}">Lupa kata sandi?</a>
        </div>

        <button class="btn btn-block" type="submit">Masuk</button>
        <p class="center muted small">Belum punya akun? <a class="link" href="{{ route('register') }}">Daftar</a></p>
    </form>
@endsection
