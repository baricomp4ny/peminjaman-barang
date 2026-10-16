<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-full border border-marian-200 bg-white px-5 py-2.5 text-sm font-semibold text-marian-800 shadow-sm transition hover:bg-marian-50 focus:outline-none focus:ring-2 focus:ring-marian-400 focus:ring-offset-2 disabled:opacity-40']) }}>
    {{ $slot }}
</button>
