@extends('layouts.app')
@section('title', 'Cari & Filter')

@section('content')
    @include('partials.pagehead', [
        'title' => ! empty($f['q']) ? 'Hasil untuk "' . $f['q'] . '"' : 'Cari & Filter',
        'subtitle' => $items->total() . ' barang ditemukan',
        'back' => route('beranda'),
    ])

    <div class="cols c-sidebar">
        <form method="GET" action="{{ route('search') }}" class="card stack" id="filter">
            <input type="hidden" name="q" value="{{ $f['q'] ?? '' }}">

            <div>
                <span class="field-label">Jenis transaksi</span>
                <div class="chips">
                    @foreach (['semua' => 'Semua', 'beli' => 'Beli', 'sewa' => 'Sewa'] as $k => $label)
                        <label class="check-chip"><input type="radio" name="mode" value="{{ $k }}" @checked(($f['mode'] ?? 'semua') === $k)><span>{{ $label }}</span></label>
                    @endforeach
                </div>
            </div>

            <div>
                <span class="field-label">Kategori</span>
                <div class="chips">
                    @foreach ($categories as $category)
                        <label class="check-chip"><input type="checkbox" name="kategori[]" value="{{ $category->id }}" @checked(in_array($category->id, $f['kategori'] ?? []))><span>{{ $category->name }}</span></label>
                    @endforeach
                </div>
            </div>

            <div>
                <span class="field-label">Harga</span>
                <div class="row">
                    <input type="number" name="min" min="0" placeholder="Min" value="{{ $f['min'] ?? '' }}" aria-label="Harga minimum">
                    <span class="muted">–</span>
                    <input type="number" name="max" min="0" placeholder="Maks" value="{{ $f['max'] ?? '' }}" aria-label="Harga maksimum">
                </div>
            </div>

            <div>
                <span class="field-label">Kondisi</span>
                <div class="chips">
                    @foreach (\App\Models\Item::CONDITIONS as $c)
                        <label class="check-chip"><input type="checkbox" name="kondisi[]" value="{{ $c }}" @checked(in_array($c, $f['kondisi'] ?? []))><span>{{ $c }}</span></label>
                    @endforeach
                </div>
            </div>

            <div>
                <span class="field-label">Kampus</span>
                <div class="chips">
                    @foreach (\App\Models\Item::CAMPUSES as $c)
                        <label class="check-chip"><input type="checkbox" name="kampus[]" value="{{ $c }}" @checked(in_array($c, $f['kampus'] ?? []))><span>{{ $c }}</span></label>
                    @endforeach
                </div>
            </div>

            <x-select name="urut" label="Urutkan" :options="['terbaru' => 'Terbaru', 'termurah' => 'Harga termurah', 'termahal' => 'Harga termahal', 'populer' => 'Terpopuler']" :value="$f['urut'] ?? 'terbaru'" />

            <div class="btn-row">
                <a class="btn btn-outline" href="{{ route('search', array_filter(['q' => $f['q'] ?? null])) }}">Reset</a>
                <button class="btn" type="submit">Tampilkan hasil</button>
            </div>
        </form>

        <div>
            @if ($items->isEmpty())
                <div class="card empty">
                    <h3>Tidak ada hasil</h3>
                    <p class="small">Coba kurangi filter atau <a class="link" href="{{ route('wanted.create') }}">buat permintaan Wanted</a>.</p>
                </div>
            @else
                <div class="grid-items">
                    @foreach ($items as $item) @include('partials.item-card', ['item' => $item]) @endforeach
                </div>
                {{ $items->links() }}
            @endif
        </div>
    </div>
@endsection
