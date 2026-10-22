<section id="contact" class="relative overflow-hidden bg-cream py-6 sm:py-24 scroll-mt-16">

    {{-- Lueur décorative douce en arrière-plan --}}
    <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-amber/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-coffee/10 blur-3xl"></div>

    <div class="relative mx-auto grid max-w-6xl items-start gap-12 px-4 lg:grid-cols-5">

        {{-- ========== COLONNE GAUCHE : TEXTE D'INTRODUCTION ========== --}}
        <div class="lg:col-span-2">

          

            {{-- Titre --}}
            <h1 class="mt-6 font-display text-5xl leading-[1.05] tracking-tight text-coffee sm:text-6xl">
                Contactez-nous
            </h1>

            {{-- Description --}}
            <p class="mt-6 max-w-md text-lg leading-relaxed text-gray-500">
                Une question, une suggestion ou un souhait particulier ?
                Écrivez-nous, nous vous répondrons avec plaisir.
            </p>

          

        </div>

        {{-- ========== COLONNE DROITE : FORMULAIRE ========== --}}
        <div class="lg:col-span-3">
            <div class="rounded-2xl border border-coffee/10 bg-white p-6 shadow-xl sm:p-10">

                {{-- Message de succès (après l'envoi) --}}
                @if (session('success'))
                    <div class="mb-8 flex items-start gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 6 9 17l-5-5" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <form action="{{ route('contact.store') }}" method="POST" class="space-y-6">
                    @csrf

                    {{-- Champ : Sujet --}}
                    <div>
                        <label for="subject" class="mb-2 block text-sm font-semibold text-coffee">
                            Sujet
                        </label>
                        <input
                            type="text"
                            id="subject"
                            name="subject"
                            value="{{ old('subject') }}"
                            maxlength="255"
                            required
                            placeholder="Ex : Réservation pour un anniversaire"
                            class="w-full rounded-xl border bg-white px-4 py-3.5 text-[15px] text-coffee placeholder:text-gray-400 transition duration-200 focus:border-amber focus:ring-2 focus:ring-amber/30 @error('subject') border-red-400 @else border-coffee/15 @enderror"
                        >
                        @error('subject')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Champ : Message --}}
                    <div>
                        <label for="message" class="mb-2 block text-sm font-semibold text-coffee">
                            Message
                        </label>
                        <textarea
                            id="message"
                            name="message"
                            rows="6"
                            maxlength="2000"
                            required
                            placeholder="Écrivez votre message ici..."
                            class="w-full resize-none rounded-xl border bg-white px-4 py-3.5 text-[15px] leading-relaxed text-coffee placeholder:text-gray-400 transition duration-200 focus:border-amber focus:ring-2 focus:ring-amber/30 @error('message') border-red-400 @else border-coffee/15 @enderror"
                        >{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Bouton d'envoi --}}
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2.5 rounded-xl bg-amber px-6 py-3.5 text-[15px] font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light focus-visible:ring-offset-2"
                    >
                        <span>Envoyer le message</span>
                        {{-- Icône flèche --}}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6" />
                        </svg>
                    </button>

                </form>
            </div>
        </div>

    </div>
</section>