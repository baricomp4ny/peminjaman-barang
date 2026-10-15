@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-marian-900']) }}>
    {{ $value ?? $slot }}
</label>
