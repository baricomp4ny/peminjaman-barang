@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-marian-950']) }}>
    {{ $value ?? $slot }}
</label>
