@extends('layouts.app')
@section('title', 'Rating & ulasan')

@section('content')
    @include('partials.pagehead', ['title' => 'Rating & ulasan', 'subtitle' => 'Ulasanmu membantu Binusian lain.', 'back' => route('orders.show', $order)])

    <form method="POST" action="{{ route('reviews.store', $order) }}" class="cols c-3-2" data-once>
        @csrf
        <div class="card stack-lg">
            <div>
                <h3>Bagaimana pengalamanmu dengan {{ $reviewee->name }}?</h3>
                <p class="muted small">{{ $order->item->title }} · {{ $order->code }}</p>
            </div>

            <div>
                <span class="field-label">Rating</span>
                <div class="stars">
                    @foreach ([5, 4, 3, 2, 1] as $n)
                        <input type="radio" id="star{{ $n }}" name="rating" value="{{ $n }}" @checked((int) old('rating') === $n)>
                        <label for="star{{ $n }}" title="{{ $n }} bintang">★</label>
                    @endforeach
                </div>
                @error('rating')<div class="err">{{ $message }}</div>@enderror
            </div>

            <x-textarea name="comment" label="Ulasan (opsional)" maxlength="500" placeholder="Ceritakan kondisi barang, keramahan, dan ketepatan waktu." />
            <button class="btn btn-block" type="submit">Kirim ulasan</button>
        </div>

        <aside class="card tint stack">
            <h3>Tips menulis ulasan</h3>
            <ul class="muted small" style="margin:0;padding-left:18px;display:grid;gap:6px">
                <li>Apakah barang sesuai deskripsi?</li>
                <li>Apakah serah terima tepat waktu?</li>
                <li>Bagaimana komunikasi dengan {{ $reviewee->firstName() }}?</li>
            </ul>
        </aside>
    </form>
@endsection
