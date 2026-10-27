<x-guest-layout title="Connexion — Café Resto">

    {{-- En-tête de la carte --}}
    <div class="border-b border-coffee/10 bg-gradient-to-br from-cream via-white to-amber/[0.04] px-6 py-7 sm:px-10">
        <div class="text-center">
            <div class="inline-flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.28em] text-amber-dark">
                <span class="h-px w-8 bg-amber/70" aria-hidden="true"></span>
                Bon retour
                <span class="h-px w-8 bg-amber/70" aria-hidden="true"></span>
            </div>

            <h1 class="mt-4 font-display text-3xl leading-tight text-coffee">Content de vous revoir</h1>

            <p class="mx-auto mt-2.5 max-w-xs text-sm leading-6 text-coffee/55">
                Connectez-vous pour gérer vos réservations et profiter de vos avantages.
            </p>
        </div>
    </div>

    <div class="px-6 py-7 sm:px-10 sm:py-8">

        {{-- Message de statut (session, vérification, etc.) --}}
        @if (session('status'))
            <div role="status"
                 class="mb-6 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-800">
                <x-lucide-circle-check class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" aria-hidden="true" />
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            {{-- Adresse e-mail --}}
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-coffee">
                    Adresse e-mail <span class="text-amber-dark" aria-hidden="true">*</span>
                </label>

                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       required autofocus autocomplete="username"
                       placeholder="vous@exemple.com"
                       aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                       @error('email') aria-describedby="email-error" @enderror
                       class="w-full rounded-lg border bg-white px-4 py-3 text-[15px] text-coffee
                              placeholder:text-coffee/45 transition duration-200
                              focus:outline-none focus:ring-2
                              @error('email') border-red-400 focus:border-red-500 focus:ring-red-200
                              @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror">

                @error('email')
                    <p id="email-error" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                        <x-lucide-circle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            {{-- Mot de passe --}}
            <div x-data="{ show: false }">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <label for="password" class="text-sm font-semibold text-coffee">
                        Mot de passe <span class="text-amber-dark" aria-hidden="true">*</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                           class="text-xs font-semibold text-amber-dark transition-colors hover:text-coffee focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-amber/50">
                            Mot de passe oublié ?
                        </a>
                    @endif
                </div>

                <div class="relative">
                    <input id="password" name="password" required autocomplete="current-password"
                           :type="show ? 'text' : 'password'"
                           placeholder="Votre mot de passe"
                           aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                           class="w-full rounded-lg border bg-white px-4 py-3 pr-11 text-[15px] text-coffee
                                  placeholder:text-coffee/45 transition duration-200
                                  focus:outline-none focus:ring-2
                                  @error('password') border-red-400 focus:border-red-500 focus:ring-red-200
                                  @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror">

                    <button type="button" id="password-toggle"
                            @click="show = !show"
                            :aria-label="show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                            :aria-pressed="show ? 'true' : 'false'"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-md p-1.5
                                   text-coffee/40 transition-colors duration-200
                                   hover:bg-coffee/5 hover:text-coffee
                                   focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/50">
                        <x-lucide-eye x-show="!show" class="h-4 w-4" aria-hidden="true" />
                        <x-lucide-eye-off x-show="show" x-cloak class="h-4 w-4" style="display: none;" aria-hidden="true" />
                    </button>
                </div>

                @error('password')
                    <p id="password-error" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                        <x-lucide-circle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            {{-- Se souvenir de moi --}}
            <label for="remember_me" class="inline-flex cursor-pointer select-none items-center gap-2.5">
                <input id="remember_me" type="checkbox" name="remember" @checked(old('remember'))
                       class="h-4 w-4 rounded border-coffee/25 text-amber
                              transition duration-200 focus:outline-none focus:ring-2 focus:ring-amber/25 focus:ring-offset-2">
                <span class="text-sm text-charcoal/75">Se souvenir de moi</span>
            </label>

            {{-- Envoi --}}
            <button type="submit"
                    class="group inline-flex w-full items-center justify-center gap-2.5 rounded-lg bg-amber px-6 py-3.5
                           text-[15px] font-semibold text-white shadow-sm transition duration-200
                           hover:bg-amber-dark hover:shadow-md disabled:cursor-not-allowed disabled:opacity-80
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light focus-visible:ring-offset-2
                           motion-safe:hover:-translate-y-0.5">
                Se connecter
                <x-lucide-arrow-right class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover:translate-x-0.5" aria-hidden="true" />
            </button>
        </form>

        {{-- Lien vers l'inscription --}}
        @if (Route::has('register'))
            <div class="mt-6 flex flex-wrap items-center justify-center gap-x-2 gap-y-1 border-t border-coffee/10 pt-5 text-sm">
                <span class="text-coffee/55">Pas encore de compte ?</span>
                <a href="{{ route('register') }}"
                   class="font-semibold text-amber-dark transition-colors duration-200 hover:text-coffee
                          focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-amber/50">
                    Créer un compte
                </a>
            </div>
        @endif

    </div>
</x-guest-layout>
