@extends('layouts.public')

@section('title', 'Contact — Café Resto')

@section('content')

    <style>
        @keyframes contactDrawLine {
            to {
                stroke-dashoffset: 0;
            }
        }

        @keyframes contactReveal {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .contact-draw-line {
            stroke-dasharray: 220;
            stroke-dashoffset: 220;
            animation: contactDrawLine 1.1s 0.25s ease-out forwards;
        }

        .contact-reveal {
            opacity: 0;
            animation: contactReveal 0.6s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            animation-delay: var(--d, 0s);
        }

        [x-cloak] {
            display: none !important;
        }

        @media (prefers-reduced-motion: reduce) {
            .contact-draw-line {
                animation: none;
                stroke-dashoffset: 0;
            }

            .contact-reveal {
                animation: none;
                opacity: 1;
                transform: none;
            }
        }
    </style>

    <div class="relative isolate min-h-screen overflow-hidden bg-[#FBF8F2] pb-16 sm:pb-24">

        {{-- Fond discret --}}
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <div class="absolute -right-40 top-24 h-80 w-80 rounded-full bg-amber/[0.06] blur-3xl"></div>
            <div class="absolute -left-40 top-[36rem] h-96 w-96 rounded-full bg-coffee/[0.04] blur-3xl"></div>
            <div class="absolute right-0 top-[68rem] h-80 w-80 rounded-full bg-amber/[0.04] blur-3xl"></div>
        </div>

        {{-- =====================================================
             EN-TÊTE ÉDITORIAL
        ====================================================== --}}
        <header class="relative overflow-hidden">

            <div class="pointer-events-none absolute -right-24 -top-28 h-80 w-80 rounded-full border border-amber/10" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -right-10 -top-14 h-56 w-56 rounded-full border border-amber/10" aria-hidden="true"></div>

            <div class="relative mx-auto max-w-7xl px-4 pb-10 pt-6 sm:px-6 sm:pb-14 sm:pt-8 lg:px-8">

                {{-- Retour à l'accueil --}}
                <a href="{{ route('home') }}"
                   class="group inline-flex items-center gap-2 rounded-full border border-coffee/15 bg-white/80 px-4 py-2 text-sm font-medium text-coffee transition duration-200 hover:border-amber/60 hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/50 focus-visible:ring-offset-2 focus-visible:ring-offset-[#FBF8F2]">
                    <x-lucide-arrow-left class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover:-translate-x-1" aria-hidden="true" />
                    Retour à l'accueil
                </a>

                {{-- Titre principal --}}
                <div class="mx-auto mt-8 max-w-3xl text-center sm:mt-10">

                    <div class="contact-reveal inline-flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.28em] text-amber-dark sm:text-xs">
                        <span class="h-px w-8 bg-amber/70" aria-hidden="true"></span>
                        Une équipe à votre écoute
                        <span class="h-px w-8 bg-amber/70" aria-hidden="true"></span>
                    </div>

                    <h1 class="contact-reveal mt-5 font-display text-4xl font-medium leading-[1.08] tracking-tight text-coffee sm:mt-6 sm:text-6xl lg:text-7xl"
                        style="--d: .1s">
                        Contactez<span class="relative inline-block italic text-amber-dark">-nous
                            <svg class="absolute -bottom-3 left-0 w-full" viewBox="0 0 200 14" fill="none"
                                 preserveAspectRatio="none" aria-hidden="true">
                                <path class="contact-draw-line" d="M3 9c30-8 60-8 95-3s65 4 99-2"
                                      stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                            </svg>
                        </span>
                    </h1>

                    <p class="contact-reveal mx-auto mt-6 max-w-xl text-sm leading-7 text-coffee/65 sm:mt-7 sm:text-base sm:leading-8"
                       style="--d: .2s">
                        Une question, une envie particulière ou simplement le souhait de nous écrire ?
                        Laissez-nous un message, nous vous répondrons avec attention.
                    </p>

                    {{-- Séparateur typographique --}}
                    <div class="contact-reveal mt-6 flex items-center justify-center gap-2 sm:mt-7"
                         style="--d: .3s" aria-hidden="true">
                        <span class="h-1 w-1 rounded-full bg-amber/50"></span>
                        <span class="h-px w-12 bg-coffee/15"></span>
                        <span class="h-1.5 w-1.5 rounded-full bg-amber"></span>
                        <span class="h-px w-12 bg-coffee/15"></span>
                        <span class="h-1 w-1 rounded-full bg-amber/50"></span>
                    </div>

                </div>

            </div>
        </header>

        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">

            <div class="grid items-start gap-8 lg:grid-cols-12 lg:gap-10">

                {{-- =================================================
                     COLONNE GAUCHE : FORMULAIRE
                ================================================== --}}
                <section class="contact-reveal lg:col-span-7" style="--d: .1s"
                         aria-labelledby="contact-form-title">

                    <div class="overflow-hidden rounded-2xl border border-coffee/10 bg-white shadow-[0_18px_60px_-32px_rgba(59,35,20,0.35)]">

                        <div class="border-b border-coffee/10 bg-gradient-to-br from-cream via-white to-amber/[0.04] px-6 py-6 sm:px-8">
                            <div class="flex items-start gap-4">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber/10 text-amber-dark ring-1 ring-amber/15">
                                    <x-lucide-mail class="h-5 w-5" aria-hidden="true" />
                                </span>

                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-amber-dark">
                                        Écrivez-nous
                                    </p>
                                    <h2 id="contact-form-title" class="mt-1.5 font-display text-2xl leading-tight text-coffee sm:text-[1.65rem]">
                                        Un message à nous adresser ?
                                    </h2>
                                    <p class="mt-2 text-sm leading-6 text-coffee/55">
                                        Renseignez les informations ci-dessous. Les champs marqués d'un astérisque sont obligatoires.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 sm:p-8">

                            {{-- Message de succès --}}
                            @if (session('success'))
                                <div role="status"
                                     class="mb-6 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-800">
                                    <x-lucide-circle-check class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" aria-hidden="true" />
                                    <span>{{ session('success') }}</span>
                                </div>
                            @endif

                            {{-- Message d'erreur serveur --}}
                            @if (session('error'))
                                <div role="alert"
                                     class="mb-6 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800">
                                    <x-lucide-circle-alert class="mt-0.5 h-5 w-5 shrink-0 text-red-600" aria-hidden="true" />
                                    <span>{{ session('error') }}</span>
                                </div>
                            @endif

                            {{-- Résumé des erreurs de validation --}}
                            @if ($errors->any())
                                <div role="alert"
                                     class="mb-6 flex items-start gap-3 rounded-lg border border-amber/25 bg-amber/[0.07] px-4 py-3 text-sm leading-6 text-coffee">
                                    <x-lucide-circle-alert class="mt-0.5 h-5 w-5 shrink-0 text-amber-dark" aria-hidden="true" />
                                    <div>
                                        <p class="font-semibold">Quelques informations demandent votre attention.</p>
                                        <p class="mt-0.5 text-coffee/60">Merci de vérifier les champs signalés ci-dessous avant de renvoyer votre message.</p>
                                    </div>
                                </div>
                            @endif

                            <form action="{{ route('contact.store') }}" method="POST"
                                  class="space-y-5"
                                  x-data="{ submitting: false, message: @js(old('message', '')) }"
                                  @submit="submitting = true">
                                @csrf

                                <div class="grid gap-5 sm:grid-cols-2">

                                    {{-- Nom complet --}}
                                    <div>
                                        <label for="name" class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-coffee">
                                            Nom complet
                                            <span class="text-amber-dark" aria-hidden="true">*</span>
                                        </label>

                                        @auth
                                            <div class="relative">
                                                <input
                                                    type="text"
                                                    id="name"
                                                    value="{{ auth()->user()->name }}"
                                                    readonly
                                                    aria-readonly="true"
                                                    aria-describedby="name-account-hint"
                                                    class="w-full rounded-lg border border-coffee/10 bg-coffee/[0.04] px-4 py-3 pr-10 text-[15px] text-coffee/75 focus:border-coffee/20 focus:outline-none focus:ring-0"
                                                >
                                                <x-lucide-lock class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-coffee/35" aria-hidden="true" />
                                            </div>
                                            <p id="name-account-hint" class="mt-1.5 text-xs text-coffee/55">
                                                Renseigné depuis votre compte.
                                            </p>
                                        @else
                                            <input
                                                type="text"
                                                id="name"
                                                name="name"
                                                value="{{ old('name') }}"
                                                required
                                                maxlength="100"
                                                autocomplete="name"
                                                placeholder="Votre nom et prénom"
                                                aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                                                @error('name') aria-describedby="name-error" @enderror
                                                class="w-full rounded-lg border bg-white px-4 py-3 text-[15px] text-coffee placeholder:text-coffee/45 transition duration-200 focus:outline-none focus:ring-2 @error('name') border-red-400 focus:border-red-500 focus:ring-red-200 @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror"
                                            >
                                            @error('name')
                                                <p id="name-error" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                                                    <x-lucide-circle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                                    <span>{{ $message }}</span>
                                                </p>
                                            @enderror
                                        @endauth
                                    </div>

                                    {{-- Adresse e-mail --}}
                                    <div>
                                        <label for="email" class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-coffee">
                                            Adresse e-mail
                                            <span class="text-amber-dark" aria-hidden="true">*</span>
                                        </label>

                                        @auth
                                            <div class="relative">
                                                <input
                                                    type="email"
                                                    id="email"
                                                    value="{{ auth()->user()->email }}"
                                                    readonly
                                                    aria-readonly="true"
                                                    aria-describedby="email-account-hint"
                                                    class="w-full rounded-lg border border-coffee/10 bg-coffee/[0.04] px-4 py-3 pr-10 text-[15px] text-coffee/75 focus:border-coffee/20 focus:outline-none focus:ring-0"
                                                >
                                                <x-lucide-lock class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-coffee/35" aria-hidden="true" />
                                            </div>
                                            <p id="email-account-hint" class="mt-1.5 text-xs text-coffee/55">
                                                Renseigné depuis votre compte.
                                            </p>
                                        @else
                                            <input
                                                type="email"
                                                id="email"
                                                name="email"
                                                value="{{ old('email') }}"
                                                required
                                                maxlength="255"
                                                autocomplete="email"
                                                placeholder="vous@exemple.com"
                                                aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                                @error('email') aria-describedby="email-error" @enderror
                                                class="w-full rounded-lg border bg-white px-4 py-3 text-[15px] text-coffee placeholder:text-coffee/45 transition duration-200 focus:outline-none focus:ring-2 @error('email') border-red-400 focus:border-red-500 focus:ring-red-200 @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror"
                                            >
                                            @error('email')
                                                <p id="email-error" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                                                    <x-lucide-circle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                                    <span>{{ $message }}</span>
                                                </p>
                                            @enderror
                                        @endauth
                                    </div>

                                </div>

                                {{-- Sujet --}}
                                <div>
                                    <label for="subject" class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-coffee">
                                        Sujet
                                        <span class="text-amber-dark" aria-hidden="true">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="subject"
                                        name="subject"
                                        value="{{ old('subject') }}"
                                        required
                                        maxlength="255"
                                        placeholder="Ex. : demande d'information, événement privé..."
                                        aria-invalid="{{ $errors->has('subject') ? 'true' : 'false' }}"
                                        @error('subject') aria-describedby="subject-error" @enderror
                                        class="w-full rounded-lg border bg-white px-4 py-3 text-[15px] text-coffee placeholder:text-coffee/45 transition duration-200 focus:outline-none focus:ring-2 @error('subject') border-red-400 focus:border-red-500 focus:ring-red-200 @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror"
                                    >
                                    @error('subject')
                                        <p id="subject-error" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                                            <x-lucide-circle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                            <span>{{ $message }}</span>
                                        </p>
                                    @enderror
                                </div>

                                {{-- Message --}}
                                <div>
                                    <label for="message" class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-coffee">
                                        Message
                                        <span class="text-amber-dark" aria-hidden="true">*</span>
                                    </label>
                                    <textarea
                                        id="message"
                                        name="message"
                                        rows="6"
                                        required
                                        maxlength="2000"
                                        x-model="message"
                                        placeholder="Écrivez votre message ici..."
                                        aria-invalid="{{ $errors->has('message') ? 'true' : 'false' }}"
                                        @error('message') aria-describedby="message-error" @enderror
                                        class="w-full resize-none rounded-lg border bg-white px-4 py-3 text-[15px] leading-relaxed text-coffee placeholder:text-coffee/45 transition duration-200 focus:outline-none focus:ring-2 @error('message') border-red-400 focus:border-red-500 focus:ring-red-200 @else border-coffee/15 focus:border-amber focus:ring-amber/25 @enderror"
                                    >{{ old('message') }}</textarea>

                                    <div class="mt-1.5 flex items-start justify-between gap-3">
                                        @error('message')
                                            <p id="message-error" class="flex items-start gap-1.5 text-xs font-medium text-red-600">
                                                <x-lucide-circle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                                <span>{{ $message }}</span>
                                            </p>
                                        @else
                                            <p class="text-xs text-coffee/50">
                                                Décrivez votre demande en quelques lignes.
                                            </p>
                                        @enderror

                                        <p class="shrink-0 text-xs tabular-nums text-coffee/50" aria-hidden="true">
                                            <span x-text="message.length"></span> / 2000
                                        </p>
                                    </div>
                                </div>

                                {{-- Envoi --}}
                                <div class="flex flex-col gap-4 border-t border-coffee/10 pt-5 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="flex items-start gap-2 text-xs leading-5 text-coffee/55">
                                        <x-lucide-shield-check class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-dark" aria-hidden="true" />
                                        <span>Vos coordonnées sont utilisées uniquement pour traiter votre message.</span>
                                    </p>

                                    <button
                                        type="submit"
                                        :disabled="submitting"
                                        class="group inline-flex w-full items-center justify-center gap-2.5 rounded-lg bg-amber px-6 py-3.5 text-[15px] font-semibold text-white shadow-sm transition duration-200 hover:bg-amber-dark hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-80 motion-safe:hover:-translate-y-0.5 sm:w-auto"
                                    >
                                        <span>Envoyer le message</span>
                                        <x-lucide-send class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover:translate-x-0.5" aria-hidden="true" />
                                        <x-lucide-loader-circle x-show="submitting" x-cloak
                                                               class="h-4 w-4 animate-spin motion-reduce:animate-none"
                                                               aria-hidden="true" />
                                    </button>
                                </div>

                                @auth
                                    <p class="text-xs leading-5 text-coffee/50">
                                        Vous êtes connecté en tant que <span class="font-medium text-coffee/60">{{ auth()->user()->name }}</span>.
                                        Votre message sera associé à votre compte.
                                    </p>
                                @endauth

                            </form>

                        </div>

                    </div>

                </section>

                {{-- =================================================
                     COLONNE DROITE : INFORMATIONS
                ================================================== --}}
                <aside class="space-y-6 lg:col-span-5" aria-label="Informations pratiques du restaurant">

                    {{-- Coordonnées --}}
                    <div class="contact-reveal overflow-hidden rounded-2xl bg-coffee p-6 text-cream shadow-[0_18px_50px_-30px_rgba(59,35,20,0.75)] sm:p-8"
                         style="--d: .2s">

                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-cream/55">
                                    Informations pratiques
                                </p>
                                <h2 class="mt-2 font-display text-2xl text-cream">Nous trouver</h2>
                            </div>

                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $status['is_open'] ? 'border-emerald-300/25 bg-emerald-400/10 text-emerald-200' : 'border-red-300/25 bg-red-400/10 text-red-200' }}">
                                <span class="relative flex h-1.5 w-1.5">
                                    @if ($status['is_open'])
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-300 opacity-70 motion-reduce:animate-none"></span>
                                    @endif
                                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full {{ $status['is_open'] ? 'bg-emerald-300' : 'bg-red-300' }}"></span>
                                </span>
                                {{ $status['label'] }}
                            </span>
                        </div>

                        <p class="mt-2 text-sm text-cream/55">{{ $status['detail'] }}</p>

                        <dl class="mt-7 space-y-5">

                            {{-- Adresse --}}
                            <div class="flex gap-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cream/[0.08] text-amber-light ring-1 ring-cream/10">
                                    <x-lucide-map-pin class="h-[18px] w-[18px]" aria-hidden="true" />
                                </span>
                                <div class="min-w-0">
                                    <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cream/55">Adresse</dt>
                                    <dd class="mt-1 text-sm leading-6 text-cream/85">
                                        @if ($directionsUrl)
                                            <a href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer"
                                               class="transition-colors duration-200 hover:text-amber-light focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-amber-light">
                                                {{ $restaurant['address']['line1'] }}<br>
                                                {{ $restaurant['address']['district'] }}<br>
                                                {{ $restaurant['address']['postal_code'] }} {{ $restaurant['address']['city'] }}, {{ $restaurant['address']['country'] }}
                                            </a>
                                        @else
                                            {{ $restaurant['address']['line1'] }}<br>
                                            {{ $restaurant['address']['district'] }}<br>
                                            {{ $restaurant['address']['postal_code'] }} {{ $restaurant['address']['city'] }}, {{ $restaurant['address']['country'] }}
                                        @endif
                                    </dd>
                                </div>
                            </div>

                            {{-- Téléphone --}}
                            <div class="flex gap-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cream/[0.08] text-amber-light ring-1 ring-cream/10">
                                    <x-lucide-phone class="h-[18px] w-[18px]" aria-hidden="true" />
                                </span>
                                <div class="min-w-0">
                                    <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cream/55">Téléphone</dt>
                                    <dd class="mt-1 text-sm leading-6 text-cream/85">
                                        @if (($restaurant['phone'] ?? '') !== '' && $restaurant['phone'] !== 'XXXXXX')
                                            <a href="tel:{{ preg_replace('/\s+/', '', $restaurant['phone']) }}"
                                               class="transition-colors duration-200 hover:text-amber-light focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-amber-light">
                                                {{ $restaurant['phone'] }}
                                            </a>
                                        @else
                                            <span>{{ $restaurant['phone'] ?? 'XXXXXX' }}</span>
                                            <span class="ml-1 text-[11px] text-cream/50">(à confirmer)</span>
                                        @endif
                                    </dd>
                                </div>
                            </div>

                            {{-- E-mail --}}
                            <div class="flex gap-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cream/[0.08] text-amber-light ring-1 ring-cream/10">
                                    <x-lucide-mail class="h-[18px] w-[18px]" aria-hidden="true" />
                                </span>
                                <div class="min-w-0">
                                    <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cream/55">E-mail</dt>
                                    <dd class="mt-1 break-words text-sm leading-6 text-cream/85">
                                        @if (($restaurant['email'] ?? '') !== '' && $restaurant['email'] !== 'XXXXXX')
                                            <a href="mailto:{{ $restaurant['email'] }}"
                                               class="transition-colors duration-200 hover:text-amber-light focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-amber-light">
                                                {{ $restaurant['email'] }}
                                            </a>
                                        @else
                                            <span>{{ $restaurant['email'] ?? 'XXXXXX' }}</span>
                                            <span class="ml-1 text-[11px] text-cream/50">(à confirmer)</span>
                                        @endif
                                    </dd>
                                </div>
                            </div>

                            {{-- Horaires --}}
                            <div class="flex gap-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cream/[0.08] text-amber-light ring-1 ring-cream/10">
                                    <x-lucide-clock class="h-[18px] w-[18px]" aria-hidden="true" />
                                </span>
                                <div class="min-w-0">
                                    <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cream/55">Horaires</dt>
                                    <dd class="mt-1 text-sm leading-6 text-cream/85">
                                        Tous les jours
                                        <span class="block text-cream/60">{{ $hoursLabel }}</span>
                                    </dd>
                                </div>
                            </div>

                        </dl>

                        @if ($directionsUrl)
                            <div class="mt-7 border-t border-cream/10 pt-5">
                                <a href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer"
                                   class="group inline-flex items-center justify-center gap-2.5 rounded-lg border border-cream/20 bg-cream/[0.06] px-5 py-2.5 text-sm font-semibold text-cream transition duration-200 hover:border-amber-light/60 hover:bg-cream/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light focus-visible:ring-offset-2 focus-visible:ring-offset-coffee">
                                    Itinéraire
                                    <x-lucide-navigation class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover:translate-x-0.5" aria-hidden="true" />
                                </a>
                            </div>
                        @endif

                    </div>

                    {{-- Carte --}}
                    <div class="contact-reveal overflow-hidden rounded-2xl border border-coffee/10 bg-white shadow-sm"
                         style="--d: .3s">

                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-coffee/10 px-5 py-4">
                            <div class="flex items-center gap-2.5 text-coffee">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-amber/10 text-amber-dark">
                                    <x-lucide-map class="h-4 w-4" aria-hidden="true" />
                                </span>
                                <h2 class="text-sm font-semibold">Nous situer</h2>
                            </div>

                            @if ($directionsUrl)
                                <a href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-dark transition-colors duration-200 hover:text-coffee focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-amber/50">
                                    Ouvrir l'itinéraire
                                    <x-lucide-arrow-up-right class="h-3.5 w-3.5" aria-hidden="true" />
                                </a>
                            @endif
                        </div>

                        @if ($mapEmbedUrl)
                            <div class="relative aspect-[16/11] w-full overflow-hidden bg-coffee/5 sm:aspect-[16/10]">
                                <iframe
                                    src="{{ $mapEmbedUrl }}"
                                    title="Carte — localisation du restaurant à Casablanca"
                                    class="absolute inset-0 h-full w-full border-0"
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                    allowfullscreen
                                ></iframe>
                            </div>
                        @else
                            <div class="px-5 py-8 text-center">
                                <p class="text-sm text-coffee/55">
                                    La carte sera disponible dès que l'adresse exacte sera confirmée.
                                </p>
                            </div>
                        @endif

                        <div class="flex items-start gap-2 border-t border-coffee/10 px-5 py-3 text-xs leading-5 text-coffee/55">
                            <x-lucide-info class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-dark" aria-hidden="true" />
                            <p>{{ $restaurant['address']['line1'] }}, {{ $restaurant['address']['city'] }}</p>
                        </div>

                    </div>

                    {{-- Réservation --}}
                    <div class="contact-reveal rounded-2xl border border-amber/20 bg-amber/[0.07] p-5 sm:p-6"
                         style="--d: .4s">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/70 text-amber-dark ring-1 ring-amber/15">
                                <x-lucide-calendar-check class="h-[18px] w-[18px]" aria-hidden="true" />
                            </span>
                            <div>
                                <h2 class="font-display text-xl leading-tight text-coffee">Envie de réserver une table ?</h2>
                                <p class="mt-1.5 text-sm leading-6 text-coffee/60">
                                    Réservez en ligne, nous vous confirmerons votre table avec plaisir.
                                </p>
                                <a href="{{ route('reservations.index') }}"
                                   class="group mt-4 inline-flex items-center gap-2 text-sm font-semibold text-amber-dark transition-colors duration-200 hover:text-coffee focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-amber/50">
                                    Réserver une table
                                    <x-lucide-arrow-right class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover:translate-x-0.5" aria-hidden="true" />
                                </a>
                            </div>
                        </div>
                    </div>

                </aside>

            </div>

        </div>

    </div>

@endsection
