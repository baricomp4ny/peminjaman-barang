<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 border border-marian-950/20 bg-white px-5 py-2.5 text-sm font-medium text-marian-900 transition hover:border-marian-800 focus:outline-none focus:ring-2 focus:ring-marian-400 focus:ring-offset-2 disabled:opacity-40']) }}>
    {{ $slot }}
</button>
