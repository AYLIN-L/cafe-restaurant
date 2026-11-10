<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-cream text-amber-dark ring-1 ring-coffee/10">
                <x-lucide-user-round class="h-5 w-5" aria-hidden="true" />
            </span>
            <div>
                <h2 class="font-display text-2xl leading-tight text-coffee">Mon profil</h2>
                <p class="text-xs text-charcoal/50">Gérez vos informations personnelles et votre sécurité.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="space-y-6">
                @include('profile.partials.update-profile-information-form')

                @include('profile.partials.update-password-form')

                @include('profile.partials.delete-user-form')
            </div>

            <p class="mt-8 flex items-center justify-center gap-2 text-center text-xs text-coffee/50">
                <x-lucide-shield-check class="h-4 w-4 text-amber-dark" aria-hidden="true" />
                Vos données sont conservées de manière sécurisée et ne sont jamais partagées.
            </p>
        </div>
    </div>
</x-app-layout>
