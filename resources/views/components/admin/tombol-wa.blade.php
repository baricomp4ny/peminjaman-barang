@props(['peminjaman'])

{{-- Tombol WhatsApp ke peminjam terlambat; tidak tampil bila nomor HP kosong/tidak valid --}}
@php $tautan = \App\Support\WhatsApp::tautan($peminjaman->peminjam->no_hp, \App\Support\WhatsApp::pesanTerlambat($peminjaman)); @endphp
@if ($tautan)
    <a href="{{ $tautan }}" target="_blank" rel="noopener noreferrer"
       {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full bg-[#25D366] px-3 py-1.5 text-xs font-bold text-white transition hover:brightness-95']) }}
       title="Kirim pengingat lewat WhatsApp ke {{ $peminjaman->peminjam->no_hp }}">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.6.8-.8 1-.3.2-.5 0a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.3a.5.5 0 0 0 0-.5l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 11.9 11.9 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7a2.8 2.8 0 0 0 1.8-1.3 2.3 2.3 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z"/></svg>
        WhatsApp
    </a>
@endif
