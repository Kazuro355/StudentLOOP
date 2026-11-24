@extends('layouts.app')
@section('title', $mode === 'sewa' ? 'Pilih tanggal sewa' : 'Checkout pembelian')

@section('content')
    @php($isRent = $mode === 'sewa')
    @include('partials.pagehead', [
        'title' => $isRent ? 'Pilih tanggal sewa' : 'Detail & checkout pembelian',
        'subtitle' => $isRent ? 'Pengajuan dikirim ke pemilik dulu. Kamu bayar setelah disetujui.' : 'Periksa pesananmu lalu lanjut ke pembayaran.',
        'back' => route('items.show', $item),
    ])

    <form method="POST" action="{{ route('orders.store', $item) }}" data-once>
        @csrf
        <div class="cols c-2-1">
            <div class="stack-lg">
                <div class="card order-row">
                    <div class="order-thumb">@if ($item->cover_url)<img src="{{ $item->cover_url }}" alt="">@else {{ $item->category?->name }} @endif</div>
                    <div class="grow">
                        <span class="badge">{{ $item->type_label }}</span>
                        <h3 style="margin-top:4px">{{ $item->title }}</h3>
                        <p class="muted small">{{ $item->isRent() ? 'Dipinjamkan' : 'Dijual' }} oleh {{ $item->owner->name }} · {{ $item->kampus }}</p>
                        <p class="primary bold">{{ $item->price_label }}</p>
                    </div>
                </div>

                @if ($isRent)
                    <div class="card stack">
                        <div class="row-between wrap">
                            <h3>Durasi &amp; tanggal</h3>
                            <div class="chips">
                                @foreach (collect([1, 3, 5, 7, 14, 30])->filter(fn ($d) => $d >= $item->min_days && $d <= $item->max_days) as $d)
                                    <button type="button" class="chip" data-days="{{ $d }}">{{ $d }} hari</button>
                                @endforeach
                            </div>
                        </div>
                        <p class="muted small">Pilih tanggal mulai, tanggal selesai terisi otomatis sesuai durasi. Klik tanggal lain untuk mengubahnya. Sewa {{ $item->min_days }}–{{ $item->max_days }} hari.</p>

                        <div id="rent-cal" class="cal"
                             data-booked='@json($booked)' data-min="{{ $item->min_days }}" data-max="{{ $item->max_days }}"
                             data-price="{{ $item->price }}" data-deposit="{{ $item->deposit }}" data-days="{{ $days }}"></div>
                        <input type="hidden" name="start_date" id="start_date" value="{{ old('start_date') }}">
                        <input type="hidden" name="end_date" id="end_date" value="{{ old('end_date') }}">
                        <div id="cal-msg" class="err" role="alert"></div>
                        @error('start_date')<div class="err">{{ $message }}</div>@enderror
                        @error('end_date')<div class="err">{{ $message }}</div>@enderror
                    </div>
                @endif

                <div class="card stack">
                    <h3>Pengambilan</h3>
                    <div class="choices">
                        @foreach (\App\Models\Item::PICKUP as $k => $label)
                            <label class="choice">
                                <input type="radio" name="pickup_method" value="{{ $k }}" @checked(old('pickup_method', $item->pickup_method) === $k)>
                                <span>{{ $label }}@if ($k === 'ambil_kampus' && $item->meeting_point)<small>Titik temu: {{ $item->meeting_point }}</small>@endif</span>
                            </label>
                        @endforeach
                    </div>
                    @error('pickup_method')<div class="err">{{ $message }}</div>@enderror
                    <x-textarea name="note" label="Catatan untuk pemilik (opsional)" maxlength="300" placeholder="Contoh: Saya ambil jam 10 di lobi." style="min-height:80px" />
                </div>
            </div>

            <aside class="card stack sticky-side">
                <h3>Ringkasan</h3>
                @if ($isRent)
                    <div class="kv"><span>Tanggal</span><span data-sum="range" class="right">Pilih tanggal di kalender</span></div>
                    <div class="kv"><span>Durasi</span><b data-sum="days">–</b></div>
                    <div class="kv"><span>Harga sewa</span><span>@rp($item->price)/hari</span></div>
                    <div class="kv"><span>Subtotal</span><span data-sum="subtotal">–</span></div>
                    <div class="kv"><span>Deposit (dikembalikan)</span><span>@rp($item->deposit)</span></div>
                    <div class="kv total"><span>Total</span><span data-sum="total">–</span></div>
                    <button id="rent-submit" class="btn btn-block" type="submit" disabled>Ajukan sewa</button>
                    <p class="hint center">Belum ada pembayaran sekarang. Pemilik menyetujui dulu.</p>
                @else
                    <div class="kv"><span>Harga barang</span><span>@rp($item->price)</span></div>
                    <div class="kv total"><span>Total</span><span>@rp($item->price)</span></div>
                    <button class="btn btn-block" type="submit">Lanjut ke pembayaran</button>
                @endif
            </aside>
        </div>
    </form>
@endsection
