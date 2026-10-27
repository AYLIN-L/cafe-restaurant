<x-admin-layout title="Tableau de bord">

    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Bonjour' : ($hour < 18 ? 'Bon après-midi' : 'Bonsoir');
        $firstName = Str::before(Auth::user()->name, ' ');

        $unavailable      = $totalMenus - $availableMenus;
        $availablePercent = $totalMenus > 0 ? round(($availableMenus / $totalMenus) * 100) : 0;
        $maxComments      = max($topMenus->max('comments_count'), 1);
    @endphp

    {{-- ========== EN-TÊTE ========== --}}
    <div class="reveal flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-charcoal/60">{{ ucfirst(now()->translatedFormat('l j F Y')) }}</p>
            <h1 class="mt-1 font-display text-3xl text-coffee sm:text-4xl">{{ $greeting }}, {{ $firstName }}.</h1>
        </div>
        <a href="{{ route('admin.menus.create') }}"
           class="inline-flex items-center gap-2 self-start rounded-lg bg-coffee px-4 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-amber-dark">
            <x-lucide-plus class="h-4 w-4" />
            Ajouter un plat
        </a>
    </div>

    {{-- ========== STATISTIQUES ========== --}}
    <div class="mt-8 grid gap-5 lg:grid-cols-12">

        {{-- Carte principale : réservations du jour --}}
        <div class="reveal relative overflow-hidden rounded-2xl bg-coffee p-6 text-cream shadow-sm lg:col-span-5" style="--d:.05s">
            <x-lucide-calendar-days class="pointer-events-none absolute -bottom-8 -right-8 h-48 w-48 text-cream/5" />

            <div class="relative flex h-full flex-col">
                <p class="flex items-center gap-2 text-sm text-cream/70">
                    <x-lucide-calendar-days class="h-4 w-4 text-amber-light" />
                    Réservations aujourd'hui
                </p>
                <p class="mt-4 font-display text-7xl leading-none">{{ $todayCount }}</p>

                <div class="mt-8 grid grid-cols-2 gap-4 border-t border-cream/15 pt-5">
                    <div title="Réservations planifiées du lundi au dimanche">
                        <p class="font-display text-3xl">{{ $weekCount }}</p>
                        <p class="text-xs text-cream/60">cette semaine</p>
                    </div>
                    <a href="{{ route('admin.reservations.index', ['status' => 'pending']) }}"
                       title="Demandes en attente, toutes dates"
                       class="group rounded-lg transition hover:text-amber-light">
                        <p class="font-display text-3xl text-amber-light">{{ $pendingReservations }}</p>
                        <p class="text-xs text-cream/60 group-hover:text-cream">demandes en attente</p>
                    </a>
                </div>

                <a href="{{ route('admin.reservations.index') }}"
                   class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-amber-light transition hover:text-cream">
                    Gérer les réservations
                    <x-lucide-arrow-right class="h-4 w-4" />
                </a>
            </div>
        </div>

        {{-- Quatre cartes secondaires --}}
        <div class="grid gap-5 sm:grid-cols-2 lg:col-span-7">

            {{-- Clients --}}
            <a href="{{ route('admin.users.index') }}"
               class="reveal group rounded-2xl border border-coffee/10 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-amber/50 hover:shadow-md"
               style="--d:.12s">
                <div class="flex items-center justify-between">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-cream text-amber-dark ring-1 ring-coffee/10">
                        <x-lucide-users class="h-5 w-5" />
                    </span>
                    <x-lucide-arrow-right class="h-4 w-4 text-charcoal/40 transition duration-300 group-hover:translate-x-0.5 group-hover:text-amber-dark" />
                </div>
                <p class="mt-5 font-display text-4xl text-coffee">{{ $totalClients }}</p>
                <p class="mt-1 text-sm text-charcoal/60">Clients inscrits</p>
            </a>

            {{-- Plats --}}
            <a href="{{ route('admin.menus.index') }}"
               class="reveal group rounded-2xl border border-coffee/10 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-amber/50 hover:shadow-md"
               style="--d:.18s">
                <div class="flex items-center justify-between">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-cream text-amber-dark ring-1 ring-coffee/10">
                        <x-lucide-utensils class="h-5 w-5" />
                    </span>
                    <x-lucide-arrow-right class="h-4 w-4 text-charcoal/40 transition duration-300 group-hover:translate-x-0.5 group-hover:text-amber-dark" />
                </div>
                <p class="mt-5 font-display text-4xl text-coffee">{{ $totalMenus }}</p>
                <p class="mt-1 text-sm text-charcoal/60">Plats au menu</p>
            </a>

            {{-- Catégories --}}
            <a href="{{ route('admin.categories.index') }}"
               class="reveal group rounded-2xl border border-coffee/10 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-amber/50 hover:shadow-md"
               style="--d:.24s">
                <div class="flex items-center justify-between">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-cream text-amber-dark ring-1 ring-coffee/10">
                        <x-lucide-tag class="h-5 w-5" />
                    </span>
                    <x-lucide-arrow-right class="h-4 w-4 text-charcoal/40 transition duration-300 group-hover:translate-x-0.5 group-hover:text-amber-dark" />
                </div>
                <p class="mt-5 font-display text-4xl text-coffee">{{ $totalCategories }}</p>
                <p class="mt-1 text-sm text-charcoal/60">Catégories</p>
            </a>

            {{-- Commentaires à modérer : teinte ambre s'il y en a --}}
            <a href="{{ route('admin.comments.index') }}"
               class="reveal group rounded-2xl border p-5 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md
                      {{ $pendingComments > 0 ? 'border-amber/40 bg-amber/5' : 'border-coffee/10 bg-white' }}"
               style="--d:.30s">
                <div class="flex items-center justify-between">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-cream text-amber-dark ring-1 ring-coffee/10">
                        <x-lucide-message-square class="h-5 w-5" />
                    </span>
                    <x-lucide-arrow-right class="h-4 w-4 text-charcoal/40 transition duration-300 group-hover:translate-x-0.5 group-hover:text-amber-dark" />
                </div>
                <p class="mt-5 font-display text-4xl text-coffee">{{ $pendingComments }}</p>
                <p class="mt-1 text-sm text-charcoal/60">Avis à modérer</p>
            </a>
        </div>
    </div>

    {{-- ========== CLASSEMENT + À TRAITER ========== --}}
    <div class="mt-5 grid gap-5 lg:grid-cols-12">

        {{-- Plats les plus commentés --}}
        <section class="reveal rounded-2xl border border-coffee/10 bg-white p-6 shadow-sm lg:col-span-7" style="--d:.36s">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="font-display text-xl text-coffee">Plats les plus commentés</h2>
                    <p class="mt-0.5 text-sm text-charcoal/50">Classement par nombre d'avis</p>
                </div>
                <a href="{{ route('admin.menus.index') }}" class="text-sm font-medium text-amber-dark hover:underline">Tous les plats</a>
            </div>

            <ul class="mt-6 space-y-5">
                @forelse($topMenus as $index => $menu)
                    @php $width = round(($menu->comments_count / $maxComments) * 100); @endphp
                    <li class="flex items-center gap-4">
                        <span class="w-6 font-display text-lg text-charcoal/30">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="mb-1.5 flex items-baseline justify-between gap-3">
                                <span class="truncate text-sm font-semibold text-charcoal">{{ $menu->title }}</span>
                                <span class="shrink-0 text-sm text-charcoal/60">
                                    {{ $menu->comments_count }} avis
                                </span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-coffee/10">
                                <div class="grow-x h-full rounded-full {{ $index === 0 ? 'bg-amber' : 'bg-amber-light' }}"
                                     style="width: {{ $width }}%; --d: {{ 0.5 + $index * 0.1 }}s"></div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="py-10 text-center text-sm text-charcoal/50">Aucun commentaire pour le moment.</li>
                @endforelse
            </ul>
        </section>

        {{-- Colonne de droite --}}
        <div class="space-y-5 lg:col-span-5">

            {{-- À traiter --}}
            <section class="reveal rounded-2xl border border-coffee/10 bg-white p-6 shadow-sm" style="--d:.42s">
                <h2 class="font-display text-xl text-coffee">À traiter</h2>

                <div class="mt-4 space-y-1">
                    {{-- Réservations en attente --}}
                    <a href="{{ route('admin.reservations.index', ['status' => 'pending']) }}"
                       class="group flex items-center gap-4 rounded-xl p-3 transition-colors hover:bg-cream">
                        <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber/10 text-amber-dark">
                            <x-lucide-clock class="h-5 w-5" />
                            @if($pendingReservations > 0)
                                <span class="absolute -right-0.5 -top-0.5 flex h-2.5 w-2.5">
                                    <span class="absolute inline-flex h-full w-full rounded-full bg-amber opacity-60 motion-safe:animate-ping"></span>
                                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-amber"></span>
                                </span>
                            @endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-coffee">
                                {{ $pendingReservations }} réservation{{ $pendingReservations > 1 ? 's' : '' }} en attente
                            </span>
                            <span class="block text-xs text-charcoal/60">
                                {{ $pendingReservations > 0 ? 'À confirmer ou à refuser' : 'Tout est à jour' }}
                            </span>
                        </span>
                        <x-lucide-arrow-right class="h-4 w-4 text-charcoal/40 transition duration-300 group-hover:translate-x-0.5 group-hover:text-amber-dark" />
                    </a>

                    {{-- Commentaires à modérer --}}
                    <a href="{{ route('admin.comments.index') }}"
                       class="group flex items-center gap-4 rounded-xl p-3 transition-colors hover:bg-cream">
                        <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-coffee/10 text-coffee">
                            <x-lucide-message-square class="h-5 w-5" />
                            @if($pendingComments > 0)
                                <span class="absolute -right-0.5 -top-0.5 flex h-2.5 w-2.5">
                                    <span class="absolute inline-flex h-full w-full rounded-full bg-amber opacity-60 motion-safe:animate-ping"></span>
                                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-amber"></span>
                                </span>
                            @endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-coffee">
                                {{ $pendingComments }} commentaire{{ $pendingComments > 1 ? 's' : '' }} à modérer
                            </span>
                            <span class="block text-xs text-charcoal/60">
                                {{ $pendingComments > 0 ? 'Approuver ou supprimer' : 'Aucun avis en attente' }}
                            </span>
                        </span>
                        <x-lucide-arrow-right class="h-4 w-4 text-charcoal/40 transition duration-300 group-hover:translate-x-0.5 group-hover:text-amber-dark" />
                    </a>
                </div>
            </section>

            {{-- Disponibilité du menu --}}
            <section class="reveal rounded-2xl border border-coffee/10 bg-white p-6 shadow-sm" style="--d:.48s">
                <div class="flex items-baseline justify-between">
                    <h2 class="font-display text-xl text-coffee">Disponibilité du menu</h2>
                    <span class="font-display text-2xl text-coffee">{{ $availablePercent }}%</span>
                </div>

                <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-coffee/10">
                    <div class="grow-x h-full rounded-full bg-amber" style="width: {{ $availablePercent }}%; --d:.7s"></div>
                </div>

                <div class="mt-4 flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2 text-charcoal/70">
                        <span class="h-2 w-2 rounded-full bg-amber"></span>
                        <strong class="font-semibold text-coffee">{{ $availableMenus }}</strong> disponibles
                    </span>
                    <span class="flex items-center gap-2 text-charcoal/70">
                        <span class="h-2 w-2 rounded-full bg-coffee/20"></span>
                        <strong class="font-semibold text-coffee">{{ $unavailable }}</strong> en rupture
                    </span>
                </div>
            </section>
        </div>
    </div>

</x-admin-layout>