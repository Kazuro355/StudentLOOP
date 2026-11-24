@extends('layouts.auth')
@section('title', 'Lupa kata sandi')

@section('content')
    <h1>Lupa kata sandi</h1>
    <p class="muted small" style="margin-top:4px">Masukkan email akunmu. Kami kirim tautan untuk mengatur ulang kata sandi.</p>

    <form method="POST" action="{{ route('password.email') }}" class="stack" style="margin-top:18px" data-once>
        @csrf
        <x-field name="email" type="email" label="Email" placeholder="nama@email.com" required autofocus />
        <button class="btn btn-block" type="submit">Kirim tautan reset</button>
        <p class="center small"><a class="link" href="{{ route('login') }}">Kembali masuk</a></p>
    </form>
@endsection
