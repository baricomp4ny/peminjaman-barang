@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-sm border-marian-950/20 bg-white text-marian-950 focus:border-marian-800 focus:ring-marian-800']) }}>
    {{ $slot }}
</select>
