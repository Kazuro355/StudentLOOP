@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'prefix' => null])
<label class="field">
    <span class="field-label">{{ $label }}</span>
    @if ($prefix)<div class="input-prefix"><span>{{ $prefix }}</span>@endif
    <input type="{{ $type }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" {{ $attributes }}>
    @if ($prefix)</div>@endif
    @if ($hint)<div class="hint">{{ $hint }}</div>@endif
    @error($name)<div class="err">{{ $message }}</div>@enderror
</label>
