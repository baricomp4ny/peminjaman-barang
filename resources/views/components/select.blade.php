@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-marian-200 bg-white text-marian-950 shadow-sm focus:border-marian-500 focus:ring-marian-500']) }}>
    {{ $slot }}
</select>
