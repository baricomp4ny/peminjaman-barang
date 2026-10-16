@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'rounded-sm border-marian-950/20 bg-white text-marian-950 placeholder:text-marian-950/35 focus:border-marian-800 focus:ring-marian-800']) !!}>
