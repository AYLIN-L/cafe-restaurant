<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-lg border border-coffee/15 bg-white px-4 py-2.5 text-sm font-semibold text-coffee shadow-sm transition duration-200 hover:border-amber/60 hover:bg-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/50 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
