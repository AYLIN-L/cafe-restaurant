<x-guest-layout title="Mot de passe oublié — Café Resto">

    {{-- En-tête de la carte --}}
    <div class="border-b border-coffee/10 bg-gradient-to-br from-cream via-white to-amber/[0.04] px-6 py-7 sm:px-10">
        <div class="text-center">
            <div class="inline-flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.28em] text-amber-dark">
                <span class="h-px w-8 bg-amber/70" aria-hidden="true"></span>
                Récupération d'accès
                <span class="h-px w-8 bg-amber/70" aria-hidden="true"></span>
            </div>

            <h1 class="mt-4 font-display text-3xl leading-tight text-coffee">Mot de passe oublié ?</h1>

            <p class="mx-auto mt-2.5 max-w-xs text-sm leading-6 text-coffee/55">
                Indiquez votre adresse e-mail et nous vous enverrons un lien pour choisir un nouveau mot de passe.
            </p>
        </div>
    </div>

    <div class="px-6 py-7 sm:px-10 sm:py-8">

        {{-- Message de statut (lien envoyé) --}}
        @if (session('status'))
            <div role="status"
                 class="mb-6 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-800">
                <x-lucide-circle-check class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" aria-hidden="true" />
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div role="alert"
                 class="mb-6 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800">
                <x-lucide-circle-alert class="mt-0.5 h-5 w-5 shrink-0 text-red-600" aria-hidden="true" />
                <span>{{ $errors->first('email') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            {{-- Adresse e-mail --}}
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-coffee">
                    Adresse e-mail <span class="text-amber-dark" aria-hidden="true">*</span>
                </label>

                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       required autofocus autocomplete="username" maxlength="255"
                       placeholder="vous@exemple.com"
                       aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                       class="w-full rounded-lg border bg-white px-4 py-3 text-[15px] text-coffee
                              placeholder:text-coffee/45 transition duration-200
                              focus:outline-none focus:ring-2
                              @error('email') border-red-400 focus:border-red-500 focus:ring-red-200
                              @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror">

                <p class="mt-1.5 text-xs text-coffee/50">
                    L'adresse utilisée lors de la création de votre compte.
                </p>
            </div>

            {{-- Envoi : bouton brun --}}
            <button type="submit"
                    class="group inline-flex w-full items-center justify-center gap-2.5 rounded-lg bg-coffee px-6 py-3.5
                           text-[15px] font-semibold text-cream shadow-sm transition duration-200
                           hover:bg-amber-dark hover:shadow-md disabled:cursor-not-allowed disabled:opacity-80
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light focus-visible:ring-offset-2
                           motion-safe:hover:-translate-y-0.5">
                Envoyer le lien de réinitialisation
                <x-lucide-send class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover:translate-x-0.5" aria-hidden="true" />
            </button>
        </form>

        {{-- Lien retour --}}
        @if (Route::has('login'))
            <div class="mt-6 border-t border-coffee/10 pt-5 text-center text-sm">
                <a href="{{ route('login') }}"
                   class="group inline-flex items-center gap-1.5 font-semibold text-amber-dark transition-colors duration-200 hover:text-coffee
                          focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-amber/50">
                    <x-lucide-arrow-left class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover:-translate-x-0.5" aria-hidden="true" />
                    Retour à la connexion
                </a>
            </div>
        @endif

    </div>
</x-guest-layout>
