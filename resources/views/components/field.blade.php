@props(['name', 'label', 'type' => 'text', 'optional' => false, 'hint' => null, 'value' => null, 'autocomplete' => null, 'inputmode' => null, 'required' => null])
@php
    $id = $attributes->get('id') ?? str_replace(['.', '[', ']'], '-', $name);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($errors->has($name) ? $id.'-error' : ''));
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }} @if($optional)<span class="opt">(optional)</span>@endif</label>
    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" class="input" rows="4" @if($required ?? ! $optional) required @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif @error($name) aria-invalid="true" @enderror {{ $attributes->except(['class', 'id']) }}>{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' || $type === 'file' ? '' : old($name, $value) }}" class="input" @if($required ?? ! $optional) required @endif @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif @if($inputmode) inputmode="{{ $inputmode }}" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif @error($name) aria-invalid="true" @enderror {{ $attributes->except(['class', 'id']) }}>
    @endif
    @if ($hint)<span id="{{ $id }}-hint" class="hint">{{ $hint }}</span>@endif
    <x-error :for="$name" />
</div>
