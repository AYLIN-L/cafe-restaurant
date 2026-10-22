<div
    x-data="{ open: false, r: {} }"
    x-on:open-reservation-details.window="open = true; r = $event.detail;"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="reservation-details-title"
>
    {{-- Fond --}}
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-charcoal/60 backdrop-blur-sm" @click="open = false"></div>

    {{-- Panneau --}}
    <div x-show="open" x-transition
        class="relative mx-auto w-full max-w-lg overflow-hidden rounded-2xl border border-coffee/10 bg-white shadow-xl sm:my-16">
        <div class="flex items-start justify-between gap-4 border-b border-coffee/10 bg-cream/60 px-6 py-4">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-amber/10 text-amber-dark">
                    <x-lucide-calendar-clock class="h-5 w-5" />
                </span>
                <h3 id="reservation-details-title" class="font-display text-lg text-coffee">Détails de la réservation</h3>
            </div>
            <button type="button" @click="open = false"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-charcoal/40 transition hover:bg-coffee/5 hover:text-charcoal focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/30"
                aria-label="Fermer">
                <x-lucide-x class="h-5 w-5" />
            </button>
        </div>

        <div class="space-y-5 px-6 py-5 text-sm">
            {{-- Client --}}
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Client</dt>
                <dd class="mt-1 font-semibold text-coffee" x-text="r.name"></dd>
                <dd class="inline-flex items-center gap-1.5 text-charcoal/60">
                    <x-lucide-mail class="h-3.5 w-3.5 text-charcoal/40" />
                    <a :href="'mailto:' + r.email" class="hover:text-amber-dark" x-text="r.email"></a>
                </dd>
            </div>

            {{-- Date / heure / personnes --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Date</dt>
                    <dd class="mt-1 text-charcoal" x-text="r.date"></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Heure</dt>
                    <dd class="mt-1 tabular-nums text-charcoal" x-text="r.time"></dd>
                    <dd class="text-xs text-charcoal/50" x-show="r.end" x-text="'jusqu’à ' + r.end"></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Personnes</dt>
                    <dd class="mt-1 text-charcoal" x-text="r.guests"></dd>
                </div>
            </div>

            {{-- Table attribuée --}}
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Table</dt>
                <dd class="mt-1">
                    <span class="inline-flex items-center gap-1.5 font-semibold text-coffee" x-show="r.table">
                        <x-lucide-armchair class="h-4 w-4 text-amber-dark" />
                        <span x-text="'N°' + r.table"></span>
                    </span>
                    <span class="text-charcoal/40" x-show="!r.table">Non attribuée</span>
                </dd>
            </div>

            {{-- Statut --}}
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Statut</dt>
                <dd class="mt-1">
                    <span class="inline-flex items-center gap-2 text-charcoal">
                        <span class="h-2.5 w-2.5 rounded-full"
                            :class="{
                                'bg-amber': r.tone === 'amber',
                                'bg-emerald-500': r.tone === 'emerald',
                                'bg-red-500': r.tone === 'red',
                                'bg-gray-400': r.tone === 'gray',
                            }"></span>
                        <span x-text="r.statusLabel"></span>
                    </span>
                </dd>
            </div>

            {{-- Demande spéciale --}}
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Demande spéciale</dt>
                <dd class="mt-1 flex items-start gap-2 rounded-xl bg-cream/70 px-4 py-3 text-charcoal/70">
                    <x-lucide-message-square class="mt-0.5 h-4 w-4 shrink-0 text-charcoal/40" />
                    <span x-text="r.request || 'Aucune demande particulière.'"></span>
                </dd>
            </div>
        </div>

        <div class="flex justify-end border-t border-coffee/10 bg-cream/40 px-6 py-4">
            <button type="button" @click="open = false"
                class="inline-flex items-center justify-center rounded-xl bg-coffee px-4 py-2.5 text-sm font-semibold text-cream transition hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                Fermer
            </button>
        </div>
    </div>
</div>
