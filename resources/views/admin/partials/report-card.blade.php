<article class="card admin-report">
    <div class="row-between wrap">
        <div class="row wrap">
            <span class="badge {{ $report->status === 'baru' ? 'bad' : ($report->status === 'diproses' ? 'warn' : 'ok') }}">
                {{ ucfirst($report->status) }}
            </span>
            <span class="muted small">{{ $report->created_at->format('d M Y, H:i') }}</span>
        </div>
        <span class="badge info">{{ ucfirst($report->target_type) }} #{{ $report->target_id }}</span>
    </div>

    <div class="stack admin-report-body">
        <div>
            <h3>{{ $report->reason }}</h3>
            <p class="muted small">Dilaporkan oleh {{ $report->reporter?->name ?? 'akun yang sudah dihapus' }}</p>
        </div>
        <div class="notice">
            <strong>
                @if ($report->target instanceof \App\Models\Item)
                    {{ $report->target->title }} · {{ $report->target->owner?->name ?? 'pemilik tidak tersedia' }}
                @elseif ($report->target instanceof \App\Models\User)
                    {{ $report->target->name }} · {{ $report->target->email }}
                    @if ($report->target->isSuspended())<span class="badge bad">Akun dinonaktifkan</span>@endif
                @elseif ($report->target instanceof \App\Models\Order)
                    Transaksi {{ $report->target->code }} · {{ $report->target->item?->title ?? 'barang tidak tersedia' }}
                @else
                    Konten ini sudah tidak tersedia.
                @endif
            </strong>
            @if ($report->details)<p class="small" style="margin-top:6px">{{ $report->details }}</p>@endif
        </div>
        @if ($report->resolution)
            <p class="small"><strong>Catatan admin:</strong> {{ $report->resolution }}</p>
        @endif
    </div>

    <div class="admin-report-actions">
        <form class="row wrap" method="POST" action="{{ route('admin.reports.status', $report) }}">
            @csrf
            @method('PATCH')
            <label class="sr-only" for="report-status-{{ $report->id }}">Status laporan</label>
            <select id="report-status-{{ $report->id }}" name="status">
                @foreach (['baru' => 'Baru', 'diproses' => 'Diproses', 'selesai' => 'Selesai'] as $value => $label)
                    <option value="{{ $value }}" @selected($report->status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline btn-sm" type="submit">Simpan status</button>
        </form>

        @if ($report->target_type === 'item' && $report->target instanceof \App\Models\Item)
            <form method="POST" action="{{ route('admin.reports.resolve', $report) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="action" value="{{ $report->target->moderated_at ? 'restore_item' : 'hide_item' }}">
                <button class="btn btn-sm {{ $report->target->moderated_at ? 'btn-outline' : 'btn-danger' }}" type="submit">
                    {{ $report->target->moderated_at ? 'Tampilkan barang' : 'Sembunyikan barang' }}
                </button>
            </form>
        @elseif ($report->target_type === 'user' && $report->target instanceof \App\Models\User && ! $report->target->isAdmin() && ! $report->target->isSuspended())
            <form method="POST" action="{{ route('admin.reports.resolve', $report) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="action" value="suspend_user">
                <button class="btn btn-danger btn-sm" type="submit">Nonaktifkan akun</button>
            </form>
        @endif

        <form method="POST" action="{{ route('admin.reports.resolve', $report) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="action" value="dismiss">
            <button class="link small" type="submit">Tutup tanpa tindakan</button>
        </form>
    </div>
</article>
