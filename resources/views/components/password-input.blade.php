@props(['disabled' => false])

{{-- Input password + tombol mata untuk menampilkan/menyembunyikan isi. Kelas tata letak (mt-1, w-full) dipasang di pembungkus. --}}
<div class="relative {{ $attributes->get('class') }}" x-data="{ lihat: false }">
    <input {{ $disabled ? 'disabled' : '' }} :type="lihat ? 'text' : 'password'" type="password"
           {!! $attributes->except('class')->merge(['class' => 'block w-full rounded-xl border-marian-200 bg-white pr-11 text-marian-950 placeholder:text-marian-300 shadow-sm focus:border-marian-500 focus:ring-marian-500']) !!}>
    <button type="button" @click="lihat = ! lihat" tabindex="-1"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-marian-400 hover:text-marian-700"
            :aria-label="lihat ? 'Sembunyikan password' : 'Tampilkan password'" aria-label="Tampilkan password">
        {{-- Mata terbuka --}}
        <svg x-show="! lihat" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" /><circle cx="12" cy="12" r="3" /></svg>
        {{-- Mata dicoret --}}
        <svg x-show="lihat" style="display: none;" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.1A10.4 10.4 0 0112 5c6.5 0 10 7 10 7a17 17 0 01-3.2 4.2M6.6 6.6A17 17 0 002 12s3.5 7 10 7a10 10 0 005.4-1.6" /></svg>
    </button>
</div>
