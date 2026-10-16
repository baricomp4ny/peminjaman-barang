<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-marian-900 leading-tight">
            Profil Saya
        </h2>
    </x-slot>

    <div class="space-y-6">
        <div class="p-4 sm:p-8 bg-white border border-marian-950/15 sm:rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div id="ganti-password" class="p-4 sm:p-8 bg-white border border-marian-950/15 sm:rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>
    </div>
</x-app-layout>
