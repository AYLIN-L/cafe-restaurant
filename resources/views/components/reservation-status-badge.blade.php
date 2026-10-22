@props(['status'])

@php
    $meta = config('reservations.statuses')[$status] ?? config('reservations.statuses')['pending'];

    // Tonalité -> classes (couleur + libellé, jamais la couleur seule).
    $tones = [
        'amber' => 'bg-amber/10 text-amber-dark ring-amber/20',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'red' => 'bg-red-50 text-red-700 ring-red-200',
        'gray' => 'bg-gray-100 text-gray-600 ring-gray-200',
    ];
    $tone = $tones[$meta['tone']] ?? $tones['gray'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset $tone"]) }}>
    @svg('lucide-' . $meta['icon'], 'h-3.5 w-3.5 shrink-0')
    <span>{{ $meta['label'] }}</span>
</span>
