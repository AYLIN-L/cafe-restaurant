<section class="reveal overflow-hidden rounded-2xl border border-coffee/10 bg-white shadow-sm">
    <div class="flex items-start gap-4 border-b border-coffee/10 bg-gradient-to-br from-cream via-white to-amber/[0.04] px-6 py-5 sm:px-8">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber/10 text-amber-dark ring-1 ring-amber/15">
            <x-lucide-user-round class="h-5 w-5" aria-hidden="true" />
        </span>
        <div>
            <h2 class="font-display text-xl leading-tight text-coffee">Informations du profil</h2>
            <p class="mt-1 text-sm leading-6 text-coffee/55">
                Mettez à jour votre nom complet et votre adresse e-mail.
            </p>
        </div>
    </div>

    <div class="px-6 py-6 sm:px-8 sm:py-7">

        {{-- Formulaire séparé pour le renvoi de l'e-mail de vérification --}}
        <form id="send-verification" method="post" action="{{ route('verification.send') }}">
            @csrf
        </form>

        <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
            @csrf
            @method('patch')

            {{-- Nom complet --}}
            <div>
                <label for="name" class="mb-2 block text-sm font-semibold text-coffee">
                    Nom complet <span class="text-amber-dark" aria-hidden="true">*</span>
                </label>

                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                       required autofocus autocomplete="name" maxlength="255"
                       aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                       class="w-full rounded-lg border bg-white px-4 py-3 text-[15px] text-coffee
                              transition duration-200 focus:outline-none focus:ring-2
                              @error('name') border-red-400 focus:border-red-500 focus:ring-red-200
                              @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror">

                @error('name')
                    <p class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                        <x-lucide-circle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            {{-- Adresse e-mail --}}
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-coffee">
                    Adresse e-mail <span class="text-amber-dark" aria-hidden="true">*</span>
                </label>

                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                       required autocomplete="username" maxlength="255"
                       aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                       class="w-full rounded-lg border bg-white px-4 py-3 text-[15px] text-coffee
                              transition duration-200 focus:outline-none focus:ring-2
                              @error('email') border-red-400 focus:border-red-500 focus:ring-red-200
                              @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror">

                @error('email')
                    <p class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                        <x-lucide-circle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror

                {{-- E-mail non vérifié (affiché uniquement si la vérification est active pour le modèle User) --}}
                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="mt-3 flex items-start gap-3 rounded-lg border border-amber/25 bg-amber/[0.07] px-4 py-3 text-sm leading-6 text-coffee">
                        <x-lucide-mail class="mt-0.5 h-4 w-4 shrink-0 text-amber-dark" aria-hidden="true" />
                        <div>
                            <p>Votre adresse e-mail n'est pas encore vérifiée.</p>
                            <button form="send-verification"
                                    class="mt-0.5 font-semibold text-amber-dark underline decoration-amber/40 underline-offset-2 transition-colors hover:text-coffee
                                           focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-amber/50">
                                Cliquez ici pour recevoir un nouvel e-mail de vérification.
                            </button>

                            @if (session('status') === 'verification-link-sent')
                                <p class="mt-1 font-medium text-emerald-700">
                                    Un nouvel e-mail de vérification vient de vous être envoyé.
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Enregistrement --}}
            <div class="flex flex-wrap items-center gap-4 border-t border-coffee/10 pt-5">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-coffee px-4 py-2.5 text-sm font-semibold text-cream
                               shadow-sm transition duration-200 hover:bg-amber-dark hover:shadow-md
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light focus-visible:ring-offset-2
                               disabled:cursor-not-allowed disabled:opacity-80 motion-safe:hover:-translate-y-0.5">
                    <x-lucide-send class="h-4 w-4" aria-hidden="true" />
                    Enregistrer
                </button>

                @if (session('status') === 'profile-updated')
                    <p aria-live="polite"
                       x-data="{ show: true }"
                       x-show="show"
                       x-transition
                       x-init="setTimeout(() => show = false, 3000)"
                       class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-800">
                        <x-lucide-circle-check class="h-3.5 w-3.5" aria-hidden="true" />
                        Modifications enregistrées.
                    </p>
                @endif
            </div>
        </form>

    </div>
</section>
