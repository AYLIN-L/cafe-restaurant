@php
    $restaurant = config('restaurant');
    $hoursLabel = app(\App\Services\RestaurantSchedule::class)->hoursLabel();
@endphp

<section id="contact" class="relative overflow-hidden bg-cream py-16 scroll-mt-20 sm:py-24">

    {{-- Lueurs décoratives --}}
    <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-amber/[0.07] blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-coffee/[0.05] blur-3xl" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

        <div class="reveal overflow-hidden rounded-3xl border border-coffee/10 bg-white/80 px-6 py-12 text-center shadow-sm sm:px-12 sm:py-14">

            <div class="inline-flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.24em] text-amber-dark sm:text-xs">
                <span class="h-px w-8 bg-amber/70" aria-hidden="true"></span>
                Une question, une envie ?
                <span class="h-px w-8 bg-amber/70" aria-hidden="true"></span>
            </div>

            <h2 class="mx-auto mt-5 max-w-2xl font-display text-4xl leading-[1.08] tracking-tight text-coffee sm:text-5xl">
                Parlons-en autour
                <span class="italic text-amber-dark">d'un café.</span>
            </h2>

            <p class="mx-auto mt-5 max-w-xl text-base leading-7 text-coffee/60 sm:leading-8">
                Une demande particulière, un événement à organiser ou simplement envie de nous écrire ?
                Notre équipe vous répond avec plaisir.
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('contact.create') }}"
                   class="inline-flex w-full items-center justify-center gap-2.5 rounded-lg bg-amber px-6 py-3.5 text-[15px] font-semibold text-white shadow-sm transition duration-200 hover:bg-amber-dark hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light focus-visible:ring-offset-2 motion-safe:hover:-translate-y-0.5 sm:w-auto">
                    Nous contacter
                    <x-lucide-arrow-right class="h-4 w-4" aria-hidden="true" />
                </a>

                <a href="{{ route('reservations.index') }}"
                   class="inline-flex w-full items-center justify-center rounded-lg border border-coffee/15 bg-white px-6 py-3.5 text-[15px] font-semibold text-coffee transition duration-200 hover:border-amber/60 hover:bg-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/50 focus-visible:ring-offset-2 sm:w-auto">
                    Réserver une table
                </a>
            </div>

            <div class="mt-10 flex flex-col items-center justify-center gap-x-8 gap-y-3 border-t border-coffee/10 pt-6 text-sm text-coffee/55 sm:flex-row">
                <span class="inline-flex items-center gap-2">
                    <x-lucide-map-pin class="h-4 w-4 text-amber-dark" aria-hidden="true" />
                    {{ $restaurant['address']['district'] }}, {{ $restaurant['address']['city'] }}
                </span>
                <span class="inline-flex items-center gap-2">
                    <x-lucide-clock class="h-4 w-4 text-amber-dark" aria-hidden="true" />
                    Tous les jours · {{ $hoursLabel }}
                </span>
            </div>

        </div>

    </div>
</section>
