@extends('layouts.app')
@section('title', 'Dana sewa · Admin')

@section('content')
    @include('partials.pagehead', ['title' => 'Dana sewa & pencairan', 'subtitle' => 'Seluruh pembayaran sewa dan deposit ditahan sampai pengembalian dikonfirmasi kedua pihak dan admin menyetujui.', 'back' => route('admin.dashboard')])

    <div class="card tint admin-escrow-summary row-between wrap">
        <div class="stack">
            <span class="muted small">Total dana sewa yang masih ditahan</span>
            <h2 class="admin-stat">@rp($heldAmount)</h2>
        </div>
        <span class="badge warn">{{ $pendingPayouts->total() }} menunggu pencairan</span>
    </div>

    <section class="section">
        <div class="section-head"><h2>Perlu persetujuan</h2></div>
        <div class="stack-lg">
            @forelse ($pendingPayouts as $order)
                <article class="card admin-payout">
                    <div class="row-between wrap">
                        <div>
                            <h3>{{ $order->item->title }}</h3>
                            <p class="muted small">{{ $order->code }} · kembali {{ $order->returned_at->translatedFormat('j M Y, H:i') }}</p>
                            <p class="muted small">{{ $order->start_date->translatedFormat('j M Y') }} – {{ $order->end_date->translatedFormat('j M Y') }} · {{ $order->days }} hari</p>
                        </div>
                        <span class="badge warn">Dana ditahan</span>
                    </div>

                    <div class="admin-payout-parties">
                        <div class="notice stack"><strong>Pemilik · {{ $order->seller->name }}</strong><span class="small">Biaya sewa yang akan dicairkan: <b>@rp($order->subtotal)</b></span></div>
                        <div class="notice stack"><strong>Penyewa · {{ $order->buyer->name }}</strong><span class="small">Deposit yang akan dikembalikan: <b>@rp($order->deposit)</b></span></div>
                    </div>

                    <div class="kv"><span>Total pembayaran ditahan</span><b>@rp($order->total)</b></div>
                    <div class="kv"><span>Pembayaran</span><span>{{ $order->latestPayment?->methodLabel() ?? 'Metode tidak tersedia' }} · {{ $order->latestPayment?->reference ?? '—' }}</span></div>
                    <div class="kv"><span>Dana ditahan sejak</span><span>{{ $order->latestPayment?->paid_at?->translatedFormat('j M Y, H:i') ?? '—' }}</span></div>

                    @if ($order->photos->isNotEmpty())
                        <div class="stack">
                            <strong class="small">Foto serah terima &amp; pengembalian</strong>
                            <div class="row wrap">
                                @foreach ($order->photos as $photo)
                                    <a href="{{ $photo->url }}" target="_blank" rel="noopener" class="stack" style="width:150px">
                                        <img src="{{ $photo->url }}" alt="Foto {{ $photo->type }}" style="width:150px;height:110px;object-fit:cover;border-radius:8px">
                                        <span class="small muted">{{ $photo->type === 'handover' ? 'Serah terima' : ($photo->type === 'return' ? 'Pengembalian' : 'Penerimaan') }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="notice small">Belum ada foto bukti pada transaksi ini.</div>
                    @endif

                    <div class="admin-report-actions">
                        <form method="POST" action="{{ route('admin.payouts.release', $order) }}" data-confirm="Setujui pencairan? Biaya sewa dicatat untuk pemilik dan deposit untuk penyewa.">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-sm" type="submit">Setujui & catat pencairan</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="card empty"><h3>Belum ada pencairan</h3><p>Transaksi sewa yang sudah dikembalikan oleh kedua pihak akan muncul di sini untuk ditinjau.</p></div>
            @endforelse
        </div>
        {{ $pendingPayouts->links() }}
    </section>

    <section class="section">
        <div class="section-head"><h2>Pencairan terakhir</h2></div>
        <div class="stack">
            @forelse ($releasedPayouts as $order)
                <div class="card admin-payout-history">
                    <div class="row-between wrap">
                        <strong>{{ $order->item->title }} · {{ $order->code }}</strong>
                        <span class="badge ok">{{ $order->funds_released_at->translatedFormat('j M Y, H:i') }}</span>
                    </div>
                    <p class="muted small">Pemilik {{ $order->seller->name }} menerima pencatatan biaya sewa @rp($order->subtotal); penyewa {{ $order->buyer->name }} menerima pengembalian deposit @rp($order->deposit).</p>
                </div>
            @empty
                <div class="card"><p class="muted small">Belum ada pencairan yang disetujui.</p></div>
            @endforelse
        </div>
    </section>

    <p class="notice small section">Catatan: aplikasi ini masih menggunakan pembayaran demo. Tombol persetujuan hanya mencatat keputusan dan nominal; belum mengirim uang ke rekening atau payment gateway.</p>
@endsection
