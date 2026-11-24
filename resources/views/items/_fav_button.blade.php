<form method="POST" action="{{ route('favorites.toggle', $item) }}">
    @csrf
    <button class="btn btn-outline btn-block" type="submit">{{ $isFavorite ? 'Hapus dari favorit' : 'Simpan' }}</button>
</form>
