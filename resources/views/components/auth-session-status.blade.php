@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-marian-700']) }}>
        {{ $status }}
    </div>
@endif
