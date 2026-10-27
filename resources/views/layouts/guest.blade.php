<!DOCTYPE html>
@props(['title' => null])
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Café Resto' }}</title>

    <!-- Polices -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cream font-sans text-charcoal antialiased">

    <div class="relative isolate flex min-h-dvh flex-col items-center justify-center px-4 py-10 sm:px-6">

        {{-- Fond décoratif discret --}}
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <div class="absolute -right-40 top-24 h-80 w-80 rounded-full bg-amber/[0.07] blur-3xl"></div>
            <div class="absolute -left-40 bottom-16 h-96 w-96 rounded-full bg-coffee/[0.05] blur-3xl"></div>
            <div class="absolute -right-24 -top-24 h-80 w-80 rounded-full border border-amber/10"></div>
            <div class="absolute -left-16 -bottom-16 h-56 w-56 rounded-full border border-amber/10"></div>
        </div>

        {{-- Marque --}}
        <a href="{{ route('home') }}" class="group flex shrink-0 items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" alt="Logo Café Resto"
                 class="h-12 w-auto transition-transform duration-200 motion-safe:group-hover:scale-105">
            <span class="font-display text-2xl leading-none text-coffee">Café Resto</span>
        </a>

        {{-- Carte --}}
        <div class="mt-8 w-full max-w-md overflow-hidden rounded-2xl border border-coffee/10 bg-white
                    shadow-[0_18px_60px_-32px_rgba(59,35,20,0.35)]">
            {{ $slot }}
        </div>

        {{-- Note de bas de page --}}
        <p class="mt-8 flex items-center gap-2 text-xs text-coffee/50">
            <span class="h-px w-6 bg-coffee/15" aria-hidden="true"></span>
            <span class="font-display italic text-coffee/60">Bon goût. Bons moments.</span>
            <span class="h-px w-6 bg-coffee/15" aria-hidden="true"></span>
        </p>

    </div>

    @stack('scripts')

</body>
</html>
