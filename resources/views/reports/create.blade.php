@extends('layouts.app')
@section('title', 'Laporkan & keamanan')

@section('content')
    @include('partials.pagehead', ['title' => 'Laporkan & keamanan', 'subtitle' => 'Bantu jaga StudentLOOP tetap aman untuk sesama Binusian.', 'back' => route('profile.show')])

    @php
        $targetLabel = match (true) {
            $target instanceof \App\Models\Item => 'Barang: ' . $target->title,
            $target instanceof \App\Models\User => 'Pengguna: ' . $target->name,
            $target instanceof \App\Models\Order => 'Transaksi: ' . $target->code,
            default => null,
        };
    @endphp

    <div class="cols c-3-2">
        <div class="stack-lg">
            @if ($target)
                <form method="POST" action="{{ route('reports.store') }}" class="card stack" data-once>
                    @csrf
                    <input type="hidden" name="target_type" value="{{ $type }}">
                    <input type="hidden" name="target_id" value="{{ $target->id }}">
                    <h3>Buat laporan</h3>
                    <div class="notice"><b>{{ $targetLabel }}</b></div>

                    <div>
                        <span class="field-label">Alasan</span>
                        <div class="choices">
                            @foreach (\App\Models\Report::REASONS as $reason)
                                <label class="choice"><input type="radio" name="reason" value="{{ $reason }}" @checked(old('reason') === $reason)><span>{{ $reason }}</span></label>
                            @endforeach
                        </div>
                        @error('reason')<div class="err">{{ $message }}</div>@enderror
                    </div>

                    <x-textarea name="details" label="Detail (opsional)" maxlength="1000" placeholder="Ceritakan apa yang terjadi." />
                    <button class="btn btn-block" type="submit">Kirim laporan</button>
                </form>
            @else
                <div class="card stack">
                    <h3>Ada yang mencurigakan?</h3>
                    <p class="muted small">Buka halaman barang, profil pengguna, atau detail transaksi, lalu tekan <b>Laporkan</b>. Laporan akan ditinjau tim StudentLOOP.</p>
                    <a class="btn btn-outline" href="{{ route('beranda') }}" style="justify-self:start">Kembali ke beranda</a>
                </div>
            @endif

            <div class="card stack">
                <h3>Pengguna yang kamu blokir</h3>
                @forelse ($blocked as $u)
                    <div class="row-between">
                        <span class="row"><span class="avatar sm">{{ $u->initials() }}</span>{{ $u->name }}</span>
                        <form method="POST" action="{{ route('users.unblock', $u) }}">@csrf @method('DELETE')<button class="btn btn-outline btn-sm" type="submit">Buka blokir</button></form>
                    </div>
                @empty
                    <p class="muted small">Belum ada pengguna yang diblokir.</p>
                @endforelse
            </div>
        </div>

        <aside class="card tint stack">
            <h3>Transaksi lebih aman</h3>
            <ol class="steps-ol">
                <li>Bertemu di area kampus yang ramai.</li>
                <li>Bayar lewat StudentLOOP agar transaksi tercatat.</li>
                <li>Cek kondisi dan kelengkapan saat serah terima.</li>
                <li>Jangan bagikan kata sandi atau kode verifikasi ke siapa pun.</li>
                <li>Hubungi kami lewat laporan jika ada yang janggal.</li>
            </ol>
        </aside>
    </div>
@endsection
