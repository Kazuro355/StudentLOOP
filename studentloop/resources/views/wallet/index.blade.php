@extends('layouts.app')
@section('title', 'Saldo')

@section('content')
    @include('partials.pagehead', ['title' => 'Saldo & penarikan', 'subtitle' => 'Dana pencairan sewa dan deposit tersedia sebagai saldo.', 'back' => route('profile.show')])

    <div class="cols c-2-1">
        <div class="stack-lg">
            <div class="card tint stack">
                <span class="muted small">Saldo tersedia</span>
                <h1>@rp($user->balance)</h1>
            </div>

            <div class="card stack">
                <h3>Riwayat saldo</h3>
                @forelse ($transactions as $transaction)
                    <div class="kv">
                        <span><b>{{ $transaction->type === 'credit' ? 'Dana masuk' : 'Penarikan' }}</b><small class="muted" style="display:block">{{ $transaction->description }}</small></span>
                        <b>{{ $transaction->type === 'credit' ? '+' : '-' }}@rp($transaction->amount)</b>
                    </div>
                @empty
                    <p class="muted small">Belum ada riwayat saldo.</p>
                @endforelse
            </div>
        </div>

        <aside class="stack-lg sticky-side">
            <form method="POST" action="{{ route('wallet.bank') }}" class="card stack">
                @csrf @method('PUT')
                <h3>Rekening penarikan</h3>
                <x-field name="bank_name" label="Nama bank" value="{{ old('bank_name', $user->bank_name) }}" maxlength="50" required />
                <x-field name="bank_account_name" label="Nama pemilik rekening" value="{{ old('bank_account_name', $user->bank_account_name) }}" maxlength="100" required />
                <x-field name="bank_account_number" label="Nomor rekening" value="{{ old('bank_account_number', $user->bank_account_number) }}" maxlength="50" required />
                <button class="btn btn-outline btn-block" type="submit">Simpan rekening</button>
            </form>

            <form method="POST" action="{{ route('wallet.withdraw') }}" class="card stack" data-once>
                @csrf
                <h3>Tarik saldo</h3>
                <x-field name="amount" type="number" prefix="Rp" label="Jumlah penarikan" min="10000" max="{{ $user->balance }}" required />
                <button class="btn btn-block" type="submit">Ajukan penarikan</button>
                <p class="muted xs">Mode demo: permintaan tercatat dan saldo langsung dipotong.</p>
            </form>

            @if ($withdrawals->isNotEmpty())
                <div class="card stack">
                    <h3>Penarikan terakhir</h3>
                    @foreach ($withdrawals as $withdrawal)
                        <div class="kv"><span><b>{{ $withdrawal->reference }}</b><small class="muted" style="display:block">{{ ucfirst($withdrawal->status) }}</small></span><b>@rp($withdrawal->amount)</b></div>
                    @endforeach
                </div>
            @endif
        </aside>
    </div>
@endsection
