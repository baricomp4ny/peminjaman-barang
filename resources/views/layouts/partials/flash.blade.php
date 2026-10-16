{{-- Pesan sukses/gagal. Pakai: redirect()->with('success', '...') atau ->with('error', '...') --}}
@if (session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
         class="mb-4 flex items-start justify-between gap-4 border border-l-4 border-marian-800 bg-white p-4 text-sm font-medium text-marian-900" role="alert">
        <span>{{ session('success') }}</span>
        <button type="button" @click="show = false" class="text-marian-700 hover:text-marian-900" aria-label="Tutup">&times;</button>
    </div>
@endif

@if (session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
         class="mb-4 flex items-start justify-between gap-4 border border-l-4 border-red-600 bg-red-50 p-4 text-sm font-medium text-red-800" role="alert">
        <span>{{ session('error') }}</span>
        <button type="button" @click="show = false" class="text-red-700 hover:text-red-900" aria-label="Tutup">&times;</button>
    </div>
@endif
