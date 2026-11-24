@extends('layouts.app')
@section('title', $mode === 'complete' ? 'Lengkapi profil' : 'Ubah profil')

@section('content')
    @if ($mode === 'edit')
        @include('partials.pagehead', ['title' => 'Ubah profil', 'back' => route('profile.show')])
    @else
        <div style="margin-bottom:16px">
            <h1>Lengkapi profil</h1>
            <p class="muted small" style="margin-top:4px">Lengkapi data Binusian-mu supaya pemilik dan peminjam bisa saling percaya.</p>
        </div>
    @endif

    <div class="cols c-3-2">
        <form method="POST" action="{{ $mode === 'complete' ? route('profile.complete.store') : route('profile.update') }}" class="card stack-lg" data-once>
            @csrf
            @if ($mode === 'edit') @method('PUT') @endif

            <div>
                <h2 style="margin-bottom:12px">Informasi Binusian</h2>
                <div class="form-grid two">
                    <x-field name="name" label="Nama lengkap" :value="$user->name" required />
                    <x-field name="nim" label="NIM" :value="$user->nim" required inputmode="numeric" />
                    <x-select name="jurusan" label="Jurusan" :options="\App\Models\User::JURUSAN" :value="$user->jurusan" placeholder="Pilih jurusan" required />
                    <x-field name="angkatan" label="Angkatan" :value="$user->angkatan" placeholder="Contoh: 2023" maxlength="4" inputmode="numeric" required />
                    <x-select name="kampus" label="Kampus" :options="\App\Models\Item::CAMPUSES" :value="$user->kampus" placeholder="Pilih kampus" required />
                    <x-field name="phone" type="tel" label="Nomor WhatsApp (opsional)" :value="$user->phone" placeholder="08xxxxxxxxxx" />
                </div>
            </div>

            <div class="form-grid">
                <x-field name="area" label="Area sekitar kampus (opsional)" :value="$user->area" placeholder="Contoh: Kemanggisan, dekat gerbang utama" />
                <x-textarea name="bio" label="Tentang kamu (opsional)" :value="$user->bio" maxlength="300" placeholder="Singkat saja, mis. mahasiswa semester 5 yang sering pinjam alat lab." />
            </div>

            <button class="btn btn-block" type="submit">{{ $mode === 'complete' ? 'Simpan & mulai' : 'Simpan perubahan' }}</button>
        </form>

        <aside class="card tint stack">
            <h3>Siap masuk StudentLOOP</h3>
            <p class="muted small">Setelah profil lengkap, kamu bisa:</p>
            <ul class="muted small" style="margin:0;padding-left:18px;display:grid;gap:6px">
                <li>Menjelajah barang dari sesama Binusian</li>
                <li>Mengajukan pinjam atau membeli barang</li>
                <li>Menjual atau menyewakan barangmu</li>
                <li>Chat langsung dengan pemilik</li>
            </ul>
            <p class="notice">Nomor telepon hanya dipakai kalau kamu membagikannya di chat. Tidak ditampilkan di profil publik.</p>
        </aside>
    </div>
@endsection
