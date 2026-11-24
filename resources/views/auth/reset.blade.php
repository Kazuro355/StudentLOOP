@extends('layouts.auth')
@section('title', 'Atur ulang kata sandi')

@section('content')
    <h1>Atur ulang kata sandi</h1>

    <form method="POST" action="{{ route('password.update') }}" class="stack" style="margin-top:18px" data-once>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field name="email" type="email" label="Email" :value="$email" required />
        <x-field name="password" type="password" label="Kata sandi baru" placeholder="Minimal 8 karakter" required autocomplete="new-password" />
        <x-field name="password_confirmation" type="password" label="Ulangi kata sandi baru" required autocomplete="new-password" />
        <button class="btn btn-block" type="submit">Simpan kata sandi</button>
    </form>
@endsection
