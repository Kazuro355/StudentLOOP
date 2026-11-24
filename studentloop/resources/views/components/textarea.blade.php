@props(['name', 'label', 'value' => null, 'hint' => null])
<label class="field">
    <span class="field-label">{{ $label }}</span>
    <textarea name="{{ $name }}" {{ $attributes }}>{{ old($name, $value) }}</textarea>
    @if ($hint)<div class="hint">{{ $hint }}</div>@endif
    @error($name)<div class="err">{{ $message }}</div>@enderror
</label>
