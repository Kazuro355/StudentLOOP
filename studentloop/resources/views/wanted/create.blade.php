@extends('layouts.app')
@section('title', 'Buat permintaan Wanted')

@section('content')
    @include('partials.pagehead', ['title' => 'Buat permintaan Wanted', 'subtitle' => 'Ceritakan barang yang kamu butuhkan.', 'back' => route('wanted.index')])

    <form method="POST" action="{{ route('wanted.store') }}" class="cols c-2-1" data-once>
        @csrf
        <div class="card stack">
            <div>
                <span class="field-label">Mau beli atau sewa?</span>
                <div class="chips">
                    @foreach (['sewa' => 'Sewa', 'beli' => 'Beli'] as $k => $label)
                        <label class="check-chip"><input type="radio" name="mode" value="{{ $k }}" @checked(old('mode', 'sewa') === $k)><span>{{ $label }}</span></label>
                    @endforeach
                </div>
                @error('mode')<div class="err">{{ $message }}</div>@enderror
            </div>

            <x-field name="title" label="Barang yang dicari" placeholder="Contoh: Tripod kamera untuk tugas akhir" required maxlength="120" />
            <x-select name="category_id" label="Kategori" :options="$categories->pluck('name', 'id')->all()" placeholder="Pilih kategori" required />
            <x-select name="condition" label="Kondisi yang diharapkan" :options="\App\Models\Item::CONDITIONS" placeholder="Fleksibel" />
            <x-textarea name="details" label="Detail kebutuhan (opsional)" maxlength="1000" placeholder="Spesifikasi, lama pemakaian, atau catatan lain." />

            <div class="form-grid two">
                <x-field name="budget_min" type="number" prefix="Rp" label="Budget minimal" min="0" />
                <x-field name="budget_max" type="number" prefix="Rp" label="Budget maksimal" min="0" />
                <x-field name="needed_by" type="date" label="Dibutuhkan sebelum" min="{{ now()->toDateString() }}" />
                <x-select name="kampus" label="Kampus" :options="\App\Models\Item::CAMPUSES" :value="auth()->user()->kampus" placeholder="Semua kampus" />
            </div>
        </div>

        <aside class="card stack sticky-side">
            <h3>Bagaimana cara kerjanya?</h3>
            <ol class="steps-ol">
                <li>Pilih <b>Beli</b> atau <b>Sewa</b> lalu isi barang, kategori, anggaran, kondisi, dan lokasi.</li>
                <li>Permintaan dipublikasikan dan pemilik barang mengirim tawaran.</li>
                <li>Terima tawaran, diskusi lewat chat, lalu lanjutkan transaksi.</li>
                <li>Setelah selesai, kedua pihak dapat memberi rating dan review.</li>
            </ol>
            <button class="btn btn-block" type="submit">Publikasikan permintaan</button>
        </aside>
    </form>
@endsection
