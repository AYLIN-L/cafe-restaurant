<x-guest-layout title="Inscription — Café Resto">

    {{-- En-tête de la carte --}}
    <div class="border-b border-coffee/10 bg-gradient-to-br from-cream via-white to-amber/[0.04] px-6 py-7 sm:px-10">
        <div class="text-center">
            <div class="inline-flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.28em] text-amber-dark">
                <span class="h-px w-8 bg-amber/70" aria-hidden="true"></span>
                Nouveau ici
                <span class="h-px w-8 bg-amber/70" aria-hidden="true"></span>
            </div>

            <h1 class="mt-4 font-display text-3xl leading-tight text-coffee">Créer votre compte</h1>

            <p class="mx-auto mt-2.5 max-w-xs text-sm leading-6 text-coffee/55">
                Rejoignez-nous et réservez votre table en quelques clics.
            </p>
        </div>
    </div>

    <div class="px-6 py-7 sm:px-10 sm:py-8">

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf

            {{-- Nom complet --}}
            <div>
                <label for="name" class="mb-2 block text-sm font-semibold text-coffee">
                    Nom complet <span class="text-amber-dark" aria-hidden="true">*</span>
                </label>

                <input type="text" id="name" name="name" value="{{ old('name') }}"
                       required autofocus autocomplete="name" maxlength="255"
                       placeholder="Votre nom et prénom"
                       aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                       @error('name') aria-describedby="name-error" @enderror
                       class="w-full rounded-lg border bg-white px-4 py-3 text-[15px] text-coffee
                              placeholder:text-coffee/45 transition duration-200
                              focus:outline-none focus:ring-2
                              @error('name') border-red-400 focus:border-red-500 focus:ring-red-200
                              @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror">

                @error('name')
                    <p id="name-error" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                        <x-lucide-circle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            {{-- Téléphone (facultatif) + Adresse e-mail --}}
            <div class="grid gap-5 sm:grid-cols-2">

                <div>
                    <label for="phone" class="mb-2 block text-sm font-semibold text-coffee">
                        Téléphone
                        <span class="font-normal text-coffee/50">(facultatif)</span>
                    </label>

                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                           autocomplete="tel" maxlength="20"
                           placeholder="06 12 34 56 78"
                           aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}"
                           @error('phone') aria-describedby="phone-error" @enderror
                           class="w-full rounded-lg border bg-white px-4 py-3 text-[15px] text-coffee
                                  placeholder:text-coffee/45 transition duration-200
                                  focus:outline-none focus:ring-2
                                  @error('phone') border-red-400 focus:border-red-500 focus:ring-red-200
                                  @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror">

                    @error('phone')
                        <p id="phone-error" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                            <x-lucide-circle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-coffee">
                        Adresse e-mail <span class="text-amber-dark" aria-hidden="true">*</span>
                    </label>

                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           required autocomplete="username" maxlength="255"
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

            </div>

            {{-- Mot de passe + Confirmation --}}
            <div class="grid gap-5 sm:grid-cols-2" x-data="{ show: false, showConfirm: false }">

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-coffee">
                        Mot de passe <span class="text-amber-dark" aria-hidden="true">*</span>
                    </label>

                    <div class="relative">
                        <input id="password" name="password" required autocomplete="new-password"
                               :type="show ? 'text' : 'password'"
                               placeholder="8 caractères min."
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
                    @else
                        <p class="mt-1.5 text-xs text-coffee/50">
                            8 caractères minimum, mélangeant lettres et chiffres si possible.
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-coffee">
                        Confirmation <span class="text-amber-dark" aria-hidden="true">*</span>
                    </label>

                    <div class="relative">
                        <input id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                               :type="showConfirm ? 'text' : 'password'"
                               placeholder="Répétez le mot de passe"
                               aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}"
                               class="w-full rounded-lg border bg-white px-4 py-3 pr-11 text-[15px] text-coffee
                                      placeholder:text-coffee/45 transition duration-200
                                      focus:outline-none focus:ring-2
                                      @error('password_confirmation') border-red-400 focus:border-red-500 focus:ring-red-200
                                      @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror">

                        <button type="button" id="password-confirmation-toggle"
                                @click="showConfirm = !showConfirm"
                                :aria-label="showConfirm ? 'Masquer la confirmation' : 'Afficher la confirmation'"
                                :aria-pressed="showConfirm ? 'true' : 'false'"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-md p-1.5
                                       text-coffee/40 transition-colors duration-200
                                       hover:bg-coffee/5 hover:text-coffee
                                       focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/50">
                            <x-lucide-eye x-show="!showConfirm" class="h-4 w-4" aria-hidden="true" />
                            <x-lucide-eye-off x-show="showConfirm" x-cloak class="h-4 w-4" style="display: none;" aria-hidden="true" />
                        </button>
                    </div>

                    @error('password_confirmation')
                        <p id="password-confirmation-error" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                            <x-lucide-circle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

            </div>

            {{-- Envoi --}}
            <div class="pt-1">
                <button type="submit"
                        class="group inline-flex w-full items-center justify-center gap-2.5 rounded-lg bg-amber px-6 py-3.5
                               text-[15px] font-semibold text-white shadow-sm transition duration-200
                               hover:bg-amber-dark hover:shadow-md disabled:cursor-not-allowed disabled:opacity-80
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light focus-visible:ring-offset-2
                               motion-safe:hover:-translate-y-0.5">
                    Créer mon compte
                    <x-lucide-user-round-plus class="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
        </form>

        {{-- Lien vers la connexion --}}
        <div class="mt-6 flex flex-wrap items-center justify-center gap-x-2 gap-y-1 border-t border-coffee/10 pt-5 text-sm">
            <span class="text-coffee/55">Déjà client ?</span>
            <a href="{{ route('login') }}"
               class="font-semibold text-amber-dark transition-colors duration-200 hover:text-coffee
                      focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-amber/50">
                Se connecter
            </a>
        </div>

    </div>
</x-guest-layout>
