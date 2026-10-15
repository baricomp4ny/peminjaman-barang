<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold leading-tight text-gray-800">Notifikasi</h1>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('peminjam.notifikasi.baca-semua') }}">
                    @csrf
                    <button class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Tandai semua dibaca</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="divide-y rounded-lg bg-white shadow">
        @forelse ($notifikasi as $n)
            <a href="{{ route('peminjam.notifikasi.buka', $n->id) }}" class="flex gap-3 p-4 hover:bg-gray-50 {{ $n->read_at ? '' : 'bg-indigo-50/60' }}">
                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $n->read_at ? 'bg-transparent' : 'bg-indigo-600' }}"></span>
                <span class="flex-1">
                    <span class="block font-medium text-gray-800">{{ $n->data['judul'] ?? 'Notifikasi' }}</span>
                    <span class="block text-sm text-gray-600">{{ $n->data['pesan'] ?? '' }}</span>
                    <span class="mt-1 block text-xs text-gray-400">{{ $n->created_at->diffForHumans() }}</span>
                </span>
            </a>
        @empty
            <p class="p-8 text-center text-gray-500">Belum ada notifikasi.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $notifikasi->links() }}</div>
</x-app-layout>
