@php($on = in_array($item->id, $favIds ?? [], true))
<article class="card item-card">
    <div class="thumb item-thumb-{{ $item->type }}">
        <a href="{{ route('items.show', $item) }}" style="position:absolute;inset:0;display:grid;place-items:center" aria-label="{{ $item->title }}">
            @if ($item->cover_url)
                <img src="{{ $item->cover_url }}" alt="{{ $item->title }}" loading="lazy">
            @else
                <span>{{ $item->category?->name ?? 'Barang' }}</span>
            @endif
        </a>
        <form class="fav-form" method="POST" action="{{ route('favorites.toggle', $item) }}">
            @csrf
            <button type="submit" class="fav-btn {{ $on ? 'on' : '' }}" aria-label="{{ $on ? 'Hapus dari favorit' : 'Simpan ke favorit' }}">
                <svg viewBox="0 0 24 24" fill="{{ $on ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M12 21s-7-4.6-9.3-9A5.3 5.3 0 0 1 12 6a5.3 5.3 0 0 1 9.3 6c-2.3 4.4-9.3 9-9.3 9z"/></svg>
            </button>
        </form>
    </div>
    <div class="item-body">
        <div><span class="badge item-mode-badge {{ $item->type }}">{{ $item->type_label }}</span> @if ($item->status !== 'tersedia')<span class="badge warn">{{ ucfirst($item->status) }}</span>@endif</div>
        <a class="item-title" href="{{ route('items.show', $item) }}">{{ $item->title }}</a>
        <div class="item-meta">{{ $item->kampus }} · {{ $item->owner?->firstName() }}</div>
        <div class="item-price">{{ $item->price_label }}</div>
        <a class="btn btn-outline item-action-{{ $item->type }}" href="{{ route('items.show', $item) }}">{{ $item->type === 'sewa' ? 'Sewa barang' : 'Beli barang' }}</a>
    </div>
</article>
