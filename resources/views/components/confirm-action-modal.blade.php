@props(['title' => "Confirmer l'action"])

<div
    x-data="{
        open: false,
        formId: '',
        title: @js($title),
        message: '',
        label: 'Confirmer',
        tone: 'amber',
    }"
    x-on:open-confirm-modal.window="
        open = true;
        formId = $event.detail.formId;
        title = $event.detail.title || @js($title);
        message = $event.detail.message || '';
        label = $event.detail.label || 'Confirmer';
        tone = $event.detail.tone || 'amber';
    "
    x-on:keydown.escape.window="open = false"
    x-show="open"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="confirm-action-title"
>
    {{-- Fond --}}
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-charcoal/60 backdrop-blur-sm" @click="open = false"></div>

    {{-- Panneau --}}
    <div
        x-show="open"
        x-transition
        class="relative mx-auto flex w-full max-w-md flex-col items-center gap-4 rounded-2xl border border-coffee/10 bg-white p-6 text-center shadow-xl sm:my-16"
    >
        <span
            class="inline-flex h-12 w-12 items-center justify-center rounded-full"
            :class="{
                'bg-red-50 text-red-600': tone === 'red',
                'bg-amber/10 text-amber-dark': tone === 'amber',
                'bg-gray-100 text-gray-600': tone === 'gray',
            }"
        >
            <x-lucide-triangle-alert class="h-6 w-6" />
        </span>

        <h3 id="confirm-action-title" class="font-display text-lg text-coffee" x-text="title"></h3>
        <p class="text-sm text-charcoal/60" x-text="message"></p>

        <div class="mt-2 flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-center">
            <button type="button" @click="open = false"
                class="inline-flex items-center justify-center rounded-xl border border-coffee/15 px-4 py-2.5 text-sm font-medium text-charcoal/70 transition hover:border-coffee/30 hover:text-coffee focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/30">
                Retour
            </button>
            <button type="button"
                @click="document.getElementById(formId)?.requestSubmit(); open = false"
                class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1"
                :class="{
                    'bg-red-600 hover:bg-red-700 focus-visible:ring-red-400': tone === 'red',
                    'bg-amber hover:bg-amber-dark focus-visible:ring-amber-light': tone === 'amber',
                    'bg-gray-700 hover:bg-gray-800 focus-visible:ring-gray-400': tone === 'gray',
                }"
                x-text="label"
            ></button>
        </div>
    </div>
</div>
