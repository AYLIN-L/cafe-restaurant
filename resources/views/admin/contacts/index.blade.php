@php
    // Libellés et couleurs des statuts — même système de badges que les avis.
    $stateMeta = [
        App\Models\ContactMessage::STATUS_UNREAD => [
            'label' => 'Non lu',
            'classes' => 'bg-amber-50 text-amber-dark ring-amber/200',
            'dot' => 'bg-amber',
            'icon' => 'mail',
        ],
        App\Models\ContactMessage::STATUS_READ => [
            'label' => 'Lu · à traiter',
            'classes' => 'bg-coffee/5 text-charcoal/70 ring-coffee/15',
            'dot' => 'bg-coffee/30',
            'icon' => 'mail-open',
        ],
        App\Models\ContactMessage::STATUS_PROCESSED => [
            'label' => 'Traité',
            'classes' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'dot' => 'bg-emerald-500',
            'icon' => 'circle-check',
        ],
    ];

    $hasFilters = ($status ?? null) !== null || ($search ?? null) !== null;
    $unprocessedTotal = ($stats[App\Models\ContactMessage::STATUS_UNREAD] ?? 0)
        + ($stats[App\Models\ContactMessage::STATUS_READ] ?? 0);
@endphp

<x-admin-layout title="Messages de contact">

    {{-- En-tête --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="font-display text-2xl text-coffee sm:text-3xl">Messages de contact</h1>
            <p class="mt-1 text-sm text-charcoal/60">
                Messages envoyés par les visiteurs et les clients depuis la page Contact.
            </p>
        </div>
        @unless($messages->isEmpty())
            <p class="text-sm text-charcoal/50">
                {{ $messages->total() }} message{{ $messages->total() > 1 ? 's' : '' }}
            </p>
        @endunless
    </div>

    {{-- Statistiques (cliquables = filtres) --}}
    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach (App\Models\ContactMessage::STATUSES as $value)
            @php $meta = $stateMeta[$value]; $count = $stats[$value] ?? 0; @endphp
            <a href="{{ route('admin.contacts.index', array_filter(['status' => $value, 'search' => $search])) }}"
                class="group flex items-center justify-between gap-3 rounded-2xl border bg-white px-4 py-3.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ ($status ?? null) === $value ? 'border-amber ring-1 ring-amber/40' : 'border-coffee/10' }}">
                <span class="flex items-center gap-2.5">
                    <span class="h-2 w-2 rounded-full {{ $meta['dot'] }}"></span>
                    <span class="text-sm font-medium text-charcoal/70">{{ $meta['label'] }}</span>
                </span>
                <span class="font-display text-2xl tabular-nums text-coffee">{{ $count }}</span>
            </a>
        @endforeach
    </div>

    {{-- Recherche & filtre --}}
    <form method="GET" action="{{ route('admin.contacts.index') }}" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-charcoal/40">
                <x-lucide-search class="h-4 w-4" />
            </span>
            <label for="search" class="sr-only">Rechercher un message</label>
            <input id="search" name="search" type="search" value="{{ $search ?? '' }}"
                placeholder="Rechercher par nom, e-mail, sujet ou contenu..."
                class="w-full rounded-xl border border-coffee/15 bg-white py-2.5 pl-10 pr-4 text-sm text-coffee placeholder:text-charcoal/40 transition focus:border-amber focus:outline-none focus:ring-2 focus:ring-amber/30">
        </div>

        <div class="relative sm:w-52">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-charcoal/40">
                <x-lucide-list-filter class="h-4 w-4" />
            </span>
            <label for="status" class="sr-only">Filtrer les messages</label>
            <select id="status" name="status" onchange="this.form.submit()"
                class="w-full appearance-none rounded-xl border border-coffee/15 bg-white py-2.5 pl-10 pr-9 text-sm text-coffee transition focus:border-amber focus:outline-none focus:ring-2 focus:ring-amber/30">
                <option value="">Tous les statuts</option>
                @foreach (App\Models\ContactMessage::STATUSES as $value)
                    <option value="{{ $value }}" @selected(($status ?? null) === $value)>{{ $stateMeta[$value]['label'] }}</option>
                @endforeach
            </select>
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-charcoal/40">
                <x-lucide-chevron-down class="h-4 w-4" />
            </span>
        </div>

        <button type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-coffee px-4 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
            <x-lucide-search class="h-4 w-4" />
            Rechercher
        </button>

        @if ($hasFilters)
            <a href="{{ route('admin.contacts.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-coffee/15 px-4 py-2.5 text-sm font-medium text-charcoal/70 transition hover:border-amber hover:text-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                <x-lucide-rotate-ccw class="h-4 w-4" />
                Réinitialiser
            </a>
        @endif
    </form>

    @if ($messages->isEmpty())
        {{-- État vide --}}
        <div class="mt-8 flex flex-col items-center gap-3 rounded-2xl border border-dashed border-coffee/15 bg-white px-6 py-16 text-center">
            <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-cream text-charcoal/40">
                @if ($hasFilters)
                    <x-lucide-search-x class="h-7 w-7" />
                @else
                    <x-lucide-inbox class="h-7 w-7" />
                @endif
            </span>
            @if ($hasFilters)
                <h2 class="font-display text-lg text-coffee">Aucun message ne correspond à votre recherche</h2>
                <p class="max-w-sm text-sm text-charcoal/60">Essayez un autre terme ou modifiez le filtre de statut.</p>
                <a href="{{ route('admin.contacts.index') }}"
                    class="mt-2 inline-flex items-center gap-2 rounded-xl bg-amber px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                    <x-lucide-rotate-ccw class="h-4 w-4" />
                    Réinitialiser les filtres
                </a>
            @else
                <h2 class="font-display text-lg text-coffee">Aucun message pour le moment</h2>
                <p class="max-w-sm text-sm text-charcoal/60">Les messages envoyés depuis la page Contact apparaîtront ici.</p>
            @endif
        </div>
    @else
        {{-- Tableau (écrans moyens et plus) --}}
        <div class="mt-6 hidden rounded-2xl border border-coffee/10 bg-white shadow-sm md:block">
            <div class="overflow-x-auto rounded-2xl">
                <table class="w-full min-w-[880px] divide-y divide-coffee/10">
                    <thead class="border-b border-coffee/10 bg-cream/60">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">Expéditeur</th>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">Sujet &amp; message</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">Reçu le</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">Statut</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-charcoal/50">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-coffee/10">
                        @foreach ($messages as $contactMessage)
                            <tr class="transition-colors hover:bg-cream/60 {{ $contactMessage->isUnread() ? 'bg-amber/5' : '' }}">
                                {{-- Expéditeur --}}
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-coffee">{{ $contactMessage->senderName() }}</div>
                                    <div class="text-xs text-charcoal/50">{{ $contactMessage->email }}</div>
                                    <div class="mt-1 text-[11px] {{ $contactMessage->hasLinkedAccount() ? 'text-emerald-700' : 'text-charcoal/40' }}">
                                        {{ $contactMessage->hasLinkedAccount() ? 'Client inscrit' : 'Visiteur' }}
                                    </div>
                                </td>

                                {{-- Sujet & aperçu --}}
                                <td class="max-w-[24rem] px-4 py-3">
                                    <p class="truncate font-semibold {{ $contactMessage->isUnread() ? 'text-coffee' : 'text-charcoal/80' }}">
                                        {{ $contactMessage->subject }}
                                    </p>
                                    <p class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-charcoal/50">
                                        {{ \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $contactMessage->message), 120) }}
                                    </p>
                                </td>

                                {{-- Date --}}
                                <td class="whitespace-nowrap px-4 py-3 text-charcoal">
                                    {{ $contactMessage->created_at->translatedFormat('d M Y') }}
                                    <span class="block text-xs text-charcoal/50 tabular-nums">{{ $contactMessage->created_at->format('H:i') }}</span>
                                </td>

                                {{-- Statut --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    @php $meta = $stateMeta[$contactMessage->status]; @endphp
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $meta['classes'] }}">
                                        @svg('lucide-' . $meta['icon'], 'h-3.5 w-3.5 shrink-0')
                                        {{ $meta['label'] }}
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Lire le message complet (passe un message non lu à « lu ») --}}
                                        <a href="{{ route('admin.contacts.show', $contactMessage) }}"
                                            title="Lire le message"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-coffee/15 px-2.5 py-1.5 text-xs font-semibold text-coffee transition hover:border-amber hover:text-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                                            <x-lucide-eye class="h-3.5 w-3.5" />
                                            Lire
                                        </a>

                                        @if (! $contactMessage->isProcessed())
                                            {{-- Marquer traité : sort le message de la file --}}
                                            <form method="POST" action="{{ route('admin.contacts.status', $contactMessage) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ App\Models\ContactMessage::STATUS_PROCESSED }}">
                                                <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40">
                                                    <x-lucide-circle-check class="h-3.5 w-3.5" />
                                                    Traiter
                                                </button>
                                            </form>
                                        @else
                                            {{-- Rouvrir : remet le message dans la file à traiter --}}
                                            <form method="POST" action="{{ route('admin.contacts.status', $contactMessage) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ App\Models\ContactMessage::STATUS_UNREAD }}">
                                                <button type="submit" title="Remettre dans la file à traiter"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-coffee/15 px-2.5 py-1.5 text-xs font-semibold text-charcoal/70 transition hover:border-amber hover:text-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                                                    <x-lucide-rotate-ccw class="h-3.5 w-3.5" />
                                                    Rouvrir
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Supprimer (définitif, avec confirmation) --}}
                                        <form id="contact-{{ $contactMessage->id }}-destroy" method="POST" action="{{ route('admin.contacts.destroy', $contactMessage) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                @click="$dispatch('open-confirm-modal', {
                                                    formId: @js('contact-'.$contactMessage->id.'-destroy'),
                                                    title: @js('Supprimer le message'),
                                                    message: @js('Le message de '.$contactMessage->senderName().' sera définitivement supprimé. Cette action est irréversible.'),
                                                    label: @js('Supprimer définitivement'),
                                                    tone: 'red'
                                                })"
                                                title="Supprimer définitivement"
                                                aria-label="Supprimer définitivement le message de {{ $contactMessage->senderName() }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-charcoal/40 transition hover:bg-red-50 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40">
                                                <x-lucide-trash-2 class="h-4 w-4" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Cartes (mobile) --}}
        <div class="mt-6 space-y-4 md:hidden">
            @foreach ($messages as $contactMessage)
                @php $meta = $stateMeta[$contactMessage->status]; @endphp
                <article class="rounded-2xl border border-coffee/10 bg-white p-4 shadow-sm {{ $contactMessage->isUnread() ? 'border-amber/30 bg-amber/5' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-coffee">{{ $contactMessage->senderName() }}</p>
                            <p class="truncate text-xs text-charcoal/50">{{ $contactMessage->email }}</p>
                        </div>
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $meta['classes'] }}">
                            @svg('lucide-' . $meta['icon'], 'h-3.5 w-3.5 shrink-0')
                            {{ $meta['label'] }}
                        </span>
                    </div>

                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Sujet</dt>
                            <dd class="mt-0.5 text-charcoal">{{ $contactMessage->subject }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Reçu le</dt>
                            <dd class="mt-0.5 tabular-nums text-charcoal">{{ $contactMessage->created_at->translatedFormat('d M Y H:i') }}</dd>
                        </div>
                    </dl>

                    <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-charcoal/70">
                        {{ \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $contactMessage->message), 140) }}
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <a href="{{ route('admin.contacts.show', $contactMessage) }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-coffee/15 px-3 py-2 text-xs font-semibold text-coffee transition hover:border-amber hover:text-amber-dark">
                            <x-lucide-eye class="h-3.5 w-3.5" />
                            Lire
                        </a>

                        @if (! $contactMessage->isProcessed())
                            <form method="POST" action="{{ route('admin.contacts.status', $contactMessage) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ App\Models\ContactMessage::STATUS_PROCESSED }}">
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">
                                    <x-lucide-circle-check class="h-3.5 w-3.5" />
                                    Traiter
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.contacts.status', $contactMessage) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ App\Models\ContactMessage::STATUS_UNREAD }}">
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-coffee/15 px-3 py-2 text-xs font-semibold text-charcoal/70 transition hover:border-amber hover:text-amber-dark">
                                    <x-lucide-rotate-ccw class="h-3.5 w-3.5" />
                                    Rouvrir
                                </button>
                            </form>
                        @endif

                        <form id="m-contact-{{ $contactMessage->id }}-destroy" method="POST" action="{{ route('admin.contacts.destroy', $contactMessage) }}" class="ml-auto">
                            @csrf
                            @method('DELETE')
                            <button type="button"
                                @click="$dispatch('open-confirm-modal', {
                                    formId: @js('m-contact-'.$contactMessage->id.'-destroy'),
                                    title: @js('Supprimer le message'),
                                    message: @js('Le message de '.$contactMessage->senderName().' sera définitivement supprimé. Cette action est irréversible.'),
                                    label: @js('Supprimer définitivement'),
                                    tone: 'red'
                                })"
                                title="Supprimer définitivement"
                                aria-label="Supprimer définitivement le message de {{ $contactMessage->senderName() }}"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-charcoal/40 transition hover:bg-red-50 hover:text-red-600">
                                <x-lucide-trash-2 class="h-4 w-4" />
                            </button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Pagination (conserve recherche et filtres) --}}
        @if ($messages->hasPages())
            <div class="mt-6">
                {{ $messages->links() }}
            </div>
        @endif
    @endif

    <x-confirm-action-modal />
</x-admin-layout>
