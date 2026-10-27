@props(['title' => "Confirmer l'action"])

{{--
    Dialogue de confirmation unique — thème café-restaurant.
    Tons : emerald (positif), amber (avertissement), red (destructif),
    coffee (neutre ; « gray » des pages existantes y est assimilé).
--}}
<div
    x-data="{
        open: false,
        submitting: false,
        formId: '',
        title: @js($title),
        message: '',
        label: 'Confirmer',
        tone: 'amber',
    }"
    x-on:open-confirm-modal.window="
        open = true;
        submitting = false;
        formId = $event.detail.formId;
        title = $event.detail.title || @js($title);
        message = $event.detail.message || '';
        label = $event.detail.label || 'Confirmer';
        tone = $event.detail.tone || 'amber';
    "
    x-on:keydown.escape.window="open = false; submitting = false"
    x-effect="if (!open) { submitting = false }"
    x-show="open"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="confirm-action-title"
>
    {{-- Fond --}}
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-coffee/50 backdrop-blur-sm" @click="open = false"></div>

    {{-- Panneau --}}
    <div
        x-show="open"
        x-transition
        class="relative mx-auto flex w-full max-w-md flex-col items-center gap-4 rounded-2xl border border-coffee/15 bg-cream p-6 text-center shadow-xl sm:my-16"
    >
        <span
            class="inline-flex h-12 w-12 items-center justify-center rounded-full"
            :class="{
                'bg-emerald-100 text-emerald-700': tone === 'emerald',
                'bg-amber/15 text-amber-dark': tone === 'amber',
                'bg-red-100 text-red-700': tone === 'red',
                'bg-coffee/10 text-coffee': tone === 'coffee' || tone === 'gray',
            }"
        >
            <x-lucide-circle-check class="h-6 w-6" x-show="tone === 'emerald'" />
            <x-lucide-eye-off class="h-6 w-6" x-show="tone === 'coffee' || tone === 'gray'" />
            <x-lucide-triangle-alert class="h-6 w-6" x-show="tone === 'red' || tone === 'amber'" />
        </span>

        <h3 id="confirm-action-title" class="font-display text-lg text-coffee" x-text="title"></h3>
        <p class="text-sm text-charcoal/70" x-text="message"></p>

        <div class="mt-2 flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-center">
            <button type="button" @click="open = false; submitting = false"
                class="inline-flex items-center justify-center rounded-xl border border-coffee/20 bg-white/70 px-4 py-2.5 text-sm font-medium text-charcoal/70 transition hover:border-coffee/40 hover:text-coffee focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/30">
                Retour
            </button>
            <button type="button"
                @click="if (!submitting) { submitting = true; const form = document.getElementById(formId); if (form) { form.requestSubmit(); } else { open = false; } }"
                :disabled="submitting"
                :aria-busy="submitting"
                class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 focus-visible:ring-offset-cream disabled:cursor-wait disabled:opacity-70"
                :class="{
                    'bg-emerald-600 hover:bg-emerald-700 focus-visible:ring-emerald-400': tone === 'emerald',
                    'bg-amber hover:bg-amber-dark focus-visible:ring-amber/60': tone === 'amber',
                    'bg-red-600 hover:bg-red-700 focus-visible:ring-red-400': tone === 'red',
                    'bg-coffee hover:bg-coffee/85 focus-visible:ring-coffee/50': tone === 'coffee' || tone === 'gray',
                }"
                x-text="submitting ? 'Traitement…' : label"
            ></button>
        </div>
    </div>
</div>