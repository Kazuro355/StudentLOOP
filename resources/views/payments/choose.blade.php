@extends('layouts.app')
@section('title', 'Pembayaran')

@section('content')
    @include('partials.pagehead', ['title' => 'Pembayaran', 'subtitle' => $order->code . ' · pilih metode pembayaran', 'back' => route('orders.show', $order)])

    <form method="POST" action="{{ route('payments.store', $order) }}" data-once>
        @csrf
        <div class="cols c-2-1">
            <div class="stack-lg">
                <div class="card stack">
                    <h3>Metode pembayaran</h3>
                    <div class="choices">
                        <label class="choice"><input type="radio" name="method" value="qris" @checked(old('method') === 'qris')><span>QRIS<small>Scan dari aplikasi bank atau e-wallet apa pun</small></span></label>
                        <label class="choice"><input type="radio" name="method" value="ewallet" @checked(old('method') === 'ewallet')><span>E-wallet<small>GoPay, OVO, DANA, ShopeePay</small></span></label>
                        <label class="choice"><input type="radio" name="method" value="bank" @checked(old('method') === 'bank')><span>Transfer bank<small>Virtual account BCA, Mandiri, BNI, BRI</small></span></label>
                    </div>
                    @error('method')<div class="err">{{ $message }}</div>@enderror

                    <div data-method-fields="ewallet" class="stack" hidden>
                        <div>
                            <span class="field-label">Pilih e-wallet</span>
                            <div class="chips">
                                @foreach (\App\Models\Payment::EWALLETS as $k => $label)
                                    <label class="check-chip"><input type="radio" name="ewallet" value="{{ $k }}" @checked(old('ewallet') === $k)><span>{{ $label }}</span></label>
                                @endforeach
                            </div>
                            @error('ewallet')<div class="err">{{ $message }}</div>@enderror
                        </div>
                        <x-field name="phone" type="tel" label="Nomor e-wallet" placeholder="08xxxxxxxxxx" />
                    </div>

                    <div data-method-fields="bank" class="stack" hidden>
                        <div>
                            <span class="field-label">Pilih bank</span>
                            <div class="chips">
                                @foreach (\App\Models\Payment::BANKS as $k => $b)
                                    <label class="check-chip"><input type="radio" name="bank" value="{{ $k }}" @checked(old('bank') === $k)><span>{{ $b[0] }}</span></label>
                                @endforeach
                            </div>
                            @error('bank')<div class="err">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="notice"><b>Mode demo.</b> Instruksi pembayaran dibuat otomatis dan tombol "Saya sudah bayar" menandai lunas. Hubungkan ke payment gateway (mis. Midtrans atau Xendit) sebelum dipakai sungguhan.</div>
            </div>

            <aside class="card stack sticky-side">
                <h3>Ringkasan</h3>
                <div class="row">
                    <div class="order-thumb" style="width:56px;height:56px">@if ($order->item->cover_url)<img src="{{ $order->item->cover_url }}" alt="">@else {{ $order->item->title }} @endif</div>
                    <div class="grow"><b>{{ $order->item->title }}</b><p class="muted xs">{{ $order->isRent() ? 'Sewa ' . $order->days . ' hari' : 'Pembelian' }}</p></div>
                </div>
                <div class="kv"><span>Subtotal</span><span>@rp($order->subtotal)</span></div>
                @if ($order->deposit)<div class="kv"><span>Deposit (dikembalikan)</span><span>@rp($order->deposit)</span></div>@endif
                <div class="kv total"><span>Total bayar</span><span>@rp($order->total)</span></div>
                <button class="btn btn-block" type="submit">Bayar @rp($order->total)</button>
            </aside>
        </div>
    </form>
@endsection
