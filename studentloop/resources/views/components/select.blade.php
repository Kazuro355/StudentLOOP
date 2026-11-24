@props(['name', 'label', 'options', 'value' => null, 'placeholder' => null])
@php($isList = array_is_list($options))
<label class="field">
    <span class="field-label">{{ $label }}</span>
    <select name="{{ $name }}" {{ $attributes }}>
        @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $k => $v)
            @php($val = $isList ? $v : $k)
            <option value="{{ $val }}" @selected((string) old($name, $value) === (string) $val)>{{ $v }}</option>
        @endforeach
    </select>
    @error($name)<div class="err">{{ $message }}</div>@enderror
</label>
