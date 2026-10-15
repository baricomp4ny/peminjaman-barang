@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'rounded-xl border-marian-200 bg-white text-marian-950 placeholder:text-marian-300 shadow-sm focus:border-marian-500 focus:ring-marian-500']) !!}>
