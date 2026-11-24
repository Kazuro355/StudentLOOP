@foreach (['success' => 'success', 'error' => 'error', 'info' => 'info', 'status' => 'success'] as $key => $class)
    @if (session($key))
        <div class="flash {{ $class }}" role="status">{{ session($key) }}</div>
    @endif
@endforeach
