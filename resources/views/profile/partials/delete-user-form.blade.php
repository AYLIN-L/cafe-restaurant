<section class="reveal overflow-hidden rounded-2xl border border-red-200 bg-white shadow-sm"
         style="--d:.16s">
    <div class="flex items-start gap-4 border-b border-red-100 bg-gradient-to-br from-red-50/60 via-white to-white px-6 py-5 sm:px-8">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600 ring-1 ring-red-200">
            <x-lucide-trash-2 class="h-5 w-5" aria-hidden="true" />
        </span>
        <div class="flex w-full flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-display text-xl leading-tight text-coffee">Supprimer le compte</h2>
                <p class="mt-1 text-sm leading-6 text-coffee/55">
                    Toutes vos données personnelles seront définitivement anonymisées. Cette action est irréversible.
                </p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-red-700">
                <x-lucide-circle-alert class="h-3.5 w-3.5" aria-hidden="true" />
                Irréversible
            </span>
        </div>
    </div>

    <div class="px-6 py-6 sm:px-8 sm:py-7">

        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="max-w-xl text-sm leading-6 text-coffee/55">
                Si vous souhaitez télécharger vos données avant la suppression, contactez-nous par le formulaire de contact.
            </p>

            <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
                <x-lucide-trash-2 class="h-4 w-4" aria-hidden="true" />
                Supprimer le compte
            </x-danger-button>
        </div>

        {{-- Fenêtre de confirmation --}}
        <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
            <form method="post" action="{{ route('profile.destroy') }}" class="p-6 sm:p-7">
                @csrf
                @method('delete')

                <div class="flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600 ring-1 ring-red-200">
                        <x-lucide-trash-2 class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 class="font-display text-xl leading-tight text-coffee">Confirmer la suppression</h2>
                        <p class="mt-1.5 text-sm leading-6 text-coffee/60">
                            Votre compte et toutes vos données seront définitivement supprimés.
                            Saisissez votre mot de passe pour confirmer cette action.
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    <label for="password" class="mb-2 block text-sm font-semibold text-coffee">
                        Mot de passe
                    </label>

                    <input id="password" name="password" type="password"
                           autocomplete="current-password" maxlength="255"
                           placeholder="Votre mot de passe"
                           aria-invalid="{{ $errors->userDeletion->has('password') ? 'true' : 'false' }}"
                           class="w-full rounded-lg border bg-white px-4 py-3 pr-11 text-[15px] text-coffee
                                  placeholder:text-coffee/45 transition duration-200
                                  focus:outline-none focus:ring-2
                                  @error('password', 'userDeletion') border-red-400 focus:border-red-500 focus:ring-red-200
                                  @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror">

                    @error('password', 'userDeletion')
                        <p class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                            <x-lucide-circle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <div class="mt-6 flex flex-col justify-end gap-3 sm:flex-row">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        Annuler
                    </x-secondary-button>

                    <x-danger-button>
                        Supprimer définitivement
                    </x-danger-button>
                </div>
            </form>
        </x-modal>

    </div>
</section>
