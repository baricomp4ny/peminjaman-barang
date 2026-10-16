<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-marian-900 to-marian-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:from-marian-950 hover:to-marian-800 focus:outline-none focus:ring-2 focus:ring-marian-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
