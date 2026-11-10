@php
    $meta = match ($contactMessage->status) {
        App\Models\ContactMessage::STATUS_PROCESSED => [
            'label' => 'Traité',
            'classes' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'icon' => 'circle-check',
        ],
        App\Models\ContactMessage::STATUS_READ => [
            'label' => 'Lu · à traiter',
            'classes' => 'bg-coffee/5 text-charcoal/70 ring-coffee/15',
            'icon' => 'mail-open',
        ],
        default => [
            'label' => 'Non lu',
            'classes' => 'bg-amber-50 text-amber-dark ring-amber/200',
            'icon' => 'mail',
        ],
    };
@endphp

<x-admin-layout title="Message de contact">

    {{-- Fil d'ariane de retour --}}
    <a href="{{ route('admin.contacts.index') }}"
        class="inline-flex items-center gap-1.5 text-sm font-medium text-charcoal/60 transition hover:text-amber-dark">
        <x-lucide-arrow-left class="h-4 w-4" />
        Tous les messages
    </a>

    {{--
        La carte principale comprend le message en entier + les actions.
        La page a été ouverte : un message « non lu » est automatiquement
        passé à « lu » par le contrôleur.
    --}}
    <article class="mt-4 rounded-2xl border border-coffee/10 bg-white shadow-sm">
        <header class="flex flex-col gap-4 border-b border-coffee/10 px-5 py-5 sm:px-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="font-display text-2xl text-coffee">{{ $contactMessage->subject }}</h1>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $meta['classes'] }}">
                        @svg('lucide-' . $meta['icon'], 'h-3.5 w-3.5 shrink-0')
                        {{ $meta['label'] }}
                    </span>
                </div>

                <dl class="mt-3 grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
                    <div class="min-w-0">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Expéditeur</dt>
                        <dd class="mt-0.5 font-semibold text-coffee">{{ $contactMessage->senderName() }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">E-mail</dt>
                        <dd class="mt-0.5 truncate">
                            <a href="mailto:{{ $contactMessage->email }}" class="text-charcoal/80 underline decoration-coffee/20 underline-offset-2 transition hover:text-amber-dark hover:decoration-amber">
                                {{ $contactMessage->email }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Reçu le</dt>
                        <dd class="mt-0.5 tabular-nums text-charcoal">{{ $contactMessage->created_at->translatedFormat('l j F Y à H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Type d'expéditeur</dt>
                        <dd class="mt-0.5">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $contactMessage->hasLinkedAccount() ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200' : 'bg-coffee/5 text-charcoal/60 ring-1 ring-inset ring-coffee/15' }}">
                                @svg('lucide-' . ($contactMessage->hasLinkedAccount() ? 'user' : 'user-round'), 'h-3.5 w-3.5 shrink-0')
                                {{ $contactMessage->hasLinkedAccount() ? 'Client inscrit' : 'Visiteur' }}
                            </span>
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Répondre</dt>
                        <dd class="mt-0.5">
                            <a href="mailto:{{ $contactMessage->email }}?subject={{ rawurlencode('Re : '.$contactMessage->subject) }}"
                                class="inline-flex items-center gap-1.5 text-charcoal/80 transition hover:text-amber-dark">
                                <x-lucide-reply class="h-4 w-4" />
                                Répondre directement par e-mail
                            </a>
                        </dd>
                    </div>
                </dl>
            </div>
        </header>

        {{-- Contenu du message --}}
        <div class="px-5 py-6 sm:px-6">
            <p class="whitespace-pre-line text-sm leading-relaxed text-charcoal/80 sm:text-base">{{ $contactMessage->message }}</p>
        </div>

        {{-- Actions --}}
        <footer class="flex flex-wrap items-center gap-2 border-t border-coffee/10 bg-cream/50 px-5 py-4 sm:px-6">
            @if (! $contactMessage->isProcessed())
                <form method="POST" action="{{ route('admin.contacts.status', $contactMessage) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ App\Models\ContactMessage::STATUS_PROCESSED }}">
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40">
                        <x-lucide-circle-check class="h-4 w-4" />
                        Marquer comme traité
                    </button>
                </form>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700">
                    <x-lucide-circle-check class="h-4 w-4" />
                    Message traité
                </span>
                {{-- Rouvrir le dossier : remet le message dans la file --}}
                <form method="POST" action="{{ route('admin.contacts.status', $contactMessage) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ App\Models\ContactMessage::STATUS_UNREAD }}">
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-coffee/15 px-4 py-2.5 text-sm font-semibold text-charcoal/70 transition hover:border-amber hover:text-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                        <x-lucide-rotate-ccw class="h-4 w-4" />
                        Rouvrir le dossier
                    </button>
                </form>
            @endif

            {{-- Supprimer (définitif, avec confirmation) --}}
            <form id="contact-show-destroy" method="POST" action="{{ route('admin.contacts.destroy', $contactMessage) }}" class="ml-auto">
                @csrf
                @method('DELETE')
                <button type="button"
                    @click="$dispatch('open-confirm-modal', {
                        formId: @js('contact-show-destroy'),
                        title: @js('Supprimer le message'),
                        message: @js('Le message de '.$contactMessage->senderName().' sera définitivement supprimé. Cette action est irréversible.'),
                        label: @js('Supprimer définitivement'),
                        tone: 'red'
                    })"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40">
                    <x-lucide-trash-2 class="h-4 w-4" />
                    Supprimer
                </button>
            </form>
        </footer>
    </article>

    <x-confirm-action-modal />
</x-admin-layout>
