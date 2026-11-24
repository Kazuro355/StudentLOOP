@extends('layouts.app')
@section('title', 'Admin')

@section('content')
    @include('partials.pagehead', ['title' => 'Ruang admin', 'subtitle' => 'Ringkasan aktivitas, laporan, dan dana sewa StudentLOOP.'])

    <div class="grid-items admin-stats">
        <div class="card"><span class="muted small">Laporan baru</span><h2 class="admin-stat">{{ $reportCounts['baru'] ?? 0 }}</h2><a class="link small" href="{{ route('admin.reports', ['status' => 'baru']) }}">Tinjau laporan</a></div>
        <div class="card"><span class="muted small">Sedang ditinjau</span><h2 class="admin-stat">{{ $reportCounts['diproses'] ?? 0 }}</h2><a class="link small" href="{{ route('admin.reports', ['status' => 'diproses']) }}">Lihat antrean</a></div>
        <div class="card"><span class="muted small">Pencairan menunggu admin</span><h2 class="admin-stat">{{ $pendingPayoutCount }}</h2><a class="link small" href="{{ route('admin.payouts') }}">Tinjau pencairan</a></div>
        <div class="card"><span class="muted small">Total dana sewa ditahan</span><h2 class="admin-stat admin-stat-money">@rp($heldAmount)</h2><a class="link small" href="{{ route('admin.payouts') }}">Kelola escrow demo</a></div>
        <div class="card"><span class="muted small">Pengguna aktif</span><h2 class="admin-stat">{{ number_format($activeUsers) }}</h2><a class="link small" href="{{ route('admin.users') }}">Kelola akun</a></div>
        <div class="card"><span class="muted small">Barang tampil</span><h2 class="admin-stat">{{ number_format($availableItems) }}</h2><span class="muted small">{{ $suspendedUsers }} akun dinonaktifkan</span></div>
    </div>

    <section class="section">
        <div class="section-head"><h2>Laporan terbaru</h2><a class="link" href="{{ route('admin.reports', ['status' => 'semua']) }}">Lihat semua</a></div>
        <div class="stack">
            @forelse ($recentReports as $report)
                @include('admin.partials.report-card', ['report' => $report])
            @empty
                <div class="card empty"><h3>Belum ada laporan</h3><p>Jika ada laporan baru, laporan akan muncul di sini.</p></div>
            @endforelse
        </div>
    </section>

    <section class="section">
        <div class="card tint row-between wrap">
            <div class="stack"><h3>Perlu ditinjau dengan cepat?</h3><p class="muted small">Periksa dana sewa, tinjau laporan, atau kelola akun.</p></div>
            <div class="btn-row admin-shortcuts"><a class="btn btn-outline btn-sm" href="{{ route('admin.payouts') }}">Dana sewa</a><a class="btn btn-outline btn-sm" href="{{ route('admin.reports') }}">Antrean laporan</a><a class="btn btn-outline btn-sm" href="{{ route('admin.users') }}">Kelola pengguna</a></div>
        </div>
    </section>
@endsection
