<section class="reveal overflow-hidden rounded-2xl border border-coffee/10 bg-white shadow-sm"
         x-data="{ showCurrent: false, showNew: false, showConfirm: false }"
         style="--d:.08s">
    <div class="flex items-start gap-4 border-b border-coffee/10 bg-gradient-to-br from-cream via-white to-amber/[0.04] px-6 py-5 sm:px-8">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber/10 text-amber-dark ring-1 ring-amber/15">
            <x-lucide-lock class="h-5 w-5" aria-hidden="true" />
        </span>
        <div>
            <h2 class="font-display text-xl leading-tight text-coffee">Mot de passe</h2>
            <p class="mt-1 text-sm leading-6 text-coffee/55">
                Choisissez un mot de passe long et unique pour garantir la sécurité de votre compte.
            </p>
        </div>
    </div>

    <div class="px-6 py-6 sm:px-8 sm:py-7">

        <form method="post" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            @method('put')

            {{-- Mot de passe actuel --}}
            <div>
                <label for="update_password_current_password" class="mb-2 block text-sm font-semibold text-coffee">
                    Mot de passe actuel <span class="text-amber-dark" aria-hidden="true">*</span>
                </label>

                <div class="relative">
                    <input id="update_password_current_password" name="current_password"
                           :type="showCurrent ? 'text' : 'password'"
                           required autocomplete="current-password" maxlength="255"
                           placeholder="Votre mot de passe actuel"
                           aria-invalid="{{ $errors->updatePassword->has('current_password') ? 'true' : 'false' }}"
                           class="w-full rounded-lg border bg-white px-4 py-3 pr-11 text-[15px] text-coffee
                                  placeholder:text-coffee/45 transition duration-200
                                  focus:outline-none focus:ring-2
                                  @error('current_password', 'updatePassword') border-red-400 focus:border-red-500 focus:ring-red-200
                                  @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror">

                    <button type="button" aria-label="Afficher ou masquer le mot de passe actuel"
                            @click="showCurrent = !showCurrent"
                            :aria-pressed="showCurrent ? 'true' : 'false'"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-coffee/40
                                   transition-colors duration-200 hover:bg-coffee/5 hover:text-coffee
                                   focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/50">
                        <x-lucide-eye x-show="!showCurrent" class="h-4 w-4" aria-hidden="true" />
                        <x-lucide-eye-off x-show="showCurrent" x-cloak style="display: none;" class="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>

                @error('current_password', 'updatePassword')
                    <p class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                        <x-lucide-circle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">

                {{-- Nouveau mot de passe --}}
                <div>
                    <label for="update_password_password" class="mb-2 block text-sm font-semibold text-coffee">
                        Nouveau mot de passe <span class="text-amber-dark" aria-hidden="true">*</span>
                    </label>

                    <div class="relative">
                        <input id="update_password_password" name="password"
                               :type="showNew ? 'text' : 'password'"
                               required autocomplete="new-password" maxlength="255"
                               placeholder="8 caractères min."
                               aria-invalid="{{ $errors->updatePassword->has('password') ? 'true' : 'false' }}"
                               class="w-full rounded-lg border bg-white px-4 py-3 pr-11 text-[15px] text-coffee
                                      placeholder:text-coffee/45 transition duration-200
                                      focus:outline-none focus:ring-2
                                      @error('password', 'updatePassword') border-red-400 focus:border-red-500 focus:ring-red-200
                                      @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror">

                        <button type="button" aria-label="Afficher ou masquer le nouveau mot de passe"
                                @click="showNew = !showNew"
                                :aria-pressed="showNew ? 'true' : 'false'"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-coffee/40
                                       transition-colors duration-200 hover:bg-coffee/5 hover:text-coffee
                                       focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/50">
                            <x-lucide-eye x-show="!showNew" class="h-4 w-4" aria-hidden="true" />
                            <x-lucide-eye-off x-show="showNew" x-cloak style="display: none;" class="h-4 w-4" aria-hidden="true" />
                        </button>
                    </div>

                    @error('password', 'updatePassword')
                        <p class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                            <x-lucide-circle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                            <span>{{ $message }}</span>
                        </p>
                    @else
                        <p class="mt-1.5 text-xs text-coffee/50">
                            8 caractères minimum.
                        </p>
                    @enderror
                </div>

                {{-- Confirmation --}}
                <div>
                    <label for="update_password_password_confirmation" class="mb-2 block text-sm font-semibold text-coffee">
                        Confirmation <span class="text-amber-dark" aria-hidden="true">*</span>
                    </label>

                    <div class="relative">
                        <input id="update_password_password_confirmation" name="password_confirmation"
                               :type="showConfirm ? 'text' : 'password'"
                               required autocomplete="new-password" maxlength="255"
                               placeholder="Répétez le mot de passe"
                               aria-invalid="{{ $errors->updatePassword->has('password_confirmation') ? 'true' : 'false' }}"
                               class="w-full rounded-lg border bg-white px-4 py-3 pr-11 text-[15px] text-coffee
                                      placeholder:text-coffee/45 transition duration-200
                                      focus:outline-none focus:ring-2
                                      @error('password_confirmation', 'updatePassword') border-red-400 focus:border-red-500 focus:ring-red-200
                                      @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror">

                        <button type="button" aria-label="Afficher ou masquer la confirmation"
                                @click="showConfirm = !showConfirm"
                                :aria-pressed="showConfirm ? 'true' : 'false'"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-coffee/40
                                       transition-colors duration-200 hover:bg-coffee/5 hover:text-coffee
                                       focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/50">
                            <x-lucide-eye x-show="!showConfirm" class="h-4 w-4" aria-hidden="true" />
                            <x-lucide-eye-off x-show="showConfirm" x-cloak style="display: none;" class="h-4 w-4" aria-hidden="true" />
                        </button>
                    </div>

                    @error('password_confirmation', 'updatePassword')
                        <p class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                            <x-lucide-circle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

            </div>

            {{-- Enregistrement --}}
            <div class="flex flex-wrap items-center gap-4 border-t border-coffee/10 pt-5">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-coffee px-4 py-2.5 text-sm font-semibold text-cream
                               shadow-sm transition duration-200 hover:bg-amber-dark hover:shadow-md
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light focus-visible:ring-offset-2
                               disabled:cursor-not-allowed disabled:opacity-80 motion-safe:hover:-translate-y-0.5">
                    <x-lucide-send class="h-4 w-4" aria-hidden="true" />
                    Mettre à jour
                </button>

                @if (session('status') === 'password-updated')
                    <p aria-live="polite"
                       x-data="{ show: true }"
                       x-show="show"
                       x-transition
                       x-init="setTimeout(() => show = false, 3000)"
                       class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-800">
                        <x-lucide-circle-check class="h-3.5 w-3.5" aria-hidden="true" />
                        Mot de passe mis à jour.
                    </p>
                @endif
            </div>
        </form>

    </div>
</section>
