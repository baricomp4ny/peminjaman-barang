<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 border border-marian-800 bg-marian-800 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-marian-900 focus:outline-none focus:ring-2 focus:ring-marian-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
