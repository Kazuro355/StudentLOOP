@extends('layouts.app')
@php($editing = $item->exists)
@section('title', $editing ? 'Ubah barang' : ($item->type === 'sewa' ? 'Sewakan barang' : 'Jual barang'))

@section('content')
    @include('partials.pagehead', [
        'title' => $editing ? 'Ubah barang' : ($item->type === 'sewa' ? 'Sewakan barang' : 'Jual barang'),
        'subtitle' => 'Isi detail supaya sesama Binusian mudah memutuskan.',
        'back' => $editing ? route('items.show', $item) : route('beranda'),
    ])

    <form method="POST" enctype="multipart/form-data" data-once
          action="{{ $editing ? route('items.update', $item) : route('items.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="cols c-2-1">
            <div class="stack-lg">
                <div class="card stack">
                    <h3>Detail barang</h3>
                    <div>
                        <span class="field-label">Jenis</span>
                        <div class="chips">
                            @foreach (\App\Models\Item::TYPES as $k => $label)
                                <label class="check-chip"><input type="radio" name="type" value="{{ $k }}" @checked(old('type', $item->type) === $k)><span>{{ $k === 'beli' ? 'Jual' : 'Sewakan' }}</span></label>
                            @endforeach
                        </div>
                        @error('type')<div class="err">{{ $message }}</div>@enderror
                    </div>

                    <x-field name="title" label="Nama barang" :value="$item->title" placeholder="Contoh: Kalkulator Scientific Casio fx-991" required maxlength="120" />

                    <div class="form-grid two">
                        <x-select name="category_id" label="Kategori" :options="$categories->pluck('name', 'id')->all()" :value="$item->category_id" placeholder="Pilih kategori" required />
                        <x-select name="condition" label="Kondisi" :options="\App\Models\Item::CONDITIONS" :value="$item->condition" placeholder="Pilih kondisi" required />
                    </div>

                    <x-textarea name="description" label="Deskripsi" :value="$item->description" required maxlength="2000" placeholder="Kondisi, riwayat pemakaian, dan catatan penting." />
                    <x-field name="kelengkapan" label="Kelengkapan (opsional)" :value="$item->kelengkapan" placeholder="Contoh: Casing, baterai cadangan, buku manual" />
                </div>

                <div class="card stack">
                    <h3>Harga</h3>
                    <div class="form-grid two">
                        <x-field name="price" type="number" prefix="Rp" label="Harga" :value="$item->price" min="0" required hint="Untuk sewa, isi harga per hari." />
                        <div data-only="sewa"><x-field name="deposit" type="number" prefix="Rp" label="Deposit (opsional)" :value="$item->deposit ?: ''" min="0" hint="Dikembalikan setelah barang kembali dengan baik." /></div>
                    </div>
                    <div class="form-grid two" data-only="sewa">
                        <x-field name="min_days" type="number" label="Sewa minimal (hari)" :value="$item->min_days" min="1" max="60" />
                        <x-field name="max_days" type="number" label="Sewa maksimal (hari)" :value="$item->max_days" min="1" max="60" />
                    </div>
                </div>

                <div class="card stack">
                    <h3>Pengambilan</h3>
                    <div class="choices">
                        @foreach (\App\Models\Item::PICKUP as $k => $label)
                            <label class="choice"><input type="radio" name="pickup_method" value="{{ $k }}" @checked(old('pickup_method', $item->pickup_method ?: 'ambil_kampus') === $k)><span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                    @error('pickup_method')<div class="err">{{ $message }}</div>@enderror
                    <div class="form-grid two">
                        <x-select name="kampus" label="Kampus" :options="\App\Models\Item::CAMPUSES" :value="$item->kampus" required />
                        <x-field name="meeting_point" label="Titik temu (opsional)" :value="$item->meeting_point" placeholder="Contoh: Lobi gedung Anggrek" />
                    </div>
                </div>
            </div>

            <aside class="card stack sticky-side">
                <h3>Foto barang</h3>
                @if ($editing && $item->photos->isNotEmpty())
                    <div class="gallery-thumbs" style="grid-template-columns:repeat(2,1fr)">
                        @foreach ($item->photos as $photo)
                            <label style="display:block;position:relative">
                                <img src="{{ $photo->url }}" alt="" style="aspect-ratio:1;object-fit:cover;border-radius:14px;width:100%">
                                <span class="xs" style="display:flex;gap:6px;align-items:center;margin-top:4px"><input type="checkbox" name="remove_photos[]" value="{{ $photo->id }}"> Hapus</span>
                            </label>
                        @endforeach
                    </div>
                @endif
                <label class="field">
                    <span class="field-label">{{ $editing ? 'Tambah foto' : 'Unggah foto' }} (maks. 4)</span>
                    <input type="file" name="photos[]" accept="image/*" multiple>
                </label>
                @error('photos')<div class="err">{{ $message }}</div>@enderror
                @error('photos.*')<div class="err">{{ $message }}</div>@enderror
                <p class="hint">JPG/PNG, maksimal 4 MB per foto.</p>
                <button class="btn btn-block" type="submit">{{ $editing ? 'Simpan perubahan' : 'Publikasikan' }}</button>
            </aside>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        var radios = document.querySelectorAll('input[name="type"]');
        function sync() {
            var checked = document.querySelector('input[name="type"]:checked');
            var rent = checked && checked.value === 'sewa';
            document.querySelectorAll('[data-only="sewa"]').forEach(function (el) {
                el.style.display = rent ? '' : 'none';
                el.querySelectorAll('input').forEach(function (i) { i.disabled = !rent; });
            });
        }
        radios.forEach(function (r) { r.addEventListener('change', sync); });
        sync();
    })();
</script>
@endpush
