@extends('layouts.app')
@section('title', 'Laporan · Admin')

@section('content')
    @include('partials.pagehead', ['title' => 'Antrean laporan', 'subtitle' => 'Tinjau laporan dan ambil tindakan yang sesuai.', 'back' => route('admin.dashboard')])

    <div class="chips">
        @foreach (['baru' => 'Baru', 'diproses' => 'Diproses', 'selesai' => 'Selesai', 'semua' => 'Semua'] as $key => $label)
            <a class="chip {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.reports', ['status' => $key]) }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="stack-lg section">
        @forelse ($reports as $report)
            @include('admin.partials.report-card', ['report' => $report])
        @empty
            <div class="card empty"><h3>Antrean kosong</h3><p>Tidak ada laporan pada status ini.</p></div>
        @endforelse
    </div>

    {{ $reports->links() }}
@endsection
