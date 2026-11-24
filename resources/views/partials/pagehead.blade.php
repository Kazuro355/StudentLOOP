{{-- Judul halaman dalam dengan tombol kembali (sama di mobile & desktop). --}}
<div class="row" style="margin-bottom:16px">
    @isset($back)
        <a class="chip" href="{{ $back }}">Kembali</a>
    @endisset
    <div class="grow">
        <h1 style="font-size:24px">{{ $title }}</h1>
        @isset($subtitle)<p class="muted small" style="margin-top:2px">{{ $subtitle }}</p>@endisset
    </div>
    @isset($action){{ $action }}@endisset
</div>
