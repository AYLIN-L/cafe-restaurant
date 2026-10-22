@extends('layouts.public')
@section('content')

    @php
        // Note moyenne (sur 5) -> pourcentage (sur 100)
        $percentage = ($averageRating ?? 0) * 20;

        // 100% = 5 étoiles, 80% = 4, 60% = 3, 40% = 2, 20% = 1
        $stars = (int) floor($percentage / 20);
    @endphp

    <div class="bg-cream min-h-screen py-10"
         x-data="{ tab: '{{ $errors->any() ? 'add' : '' }}' }">
        <div class="max-w-6xl mx-auto px-4">

            {{-- Lien retour --}}
            <a href="{{ route('home') }}#menu" class="text-sm text-amber-dark hover:underline">
                ← Retour au menu
            </a>

            {{-- Message de succès --}}
            @if(session('success'))
                <div class="bg-green-100 text-green-700 p-3 rounded-lg mt-4">
                    {{ session('success') }}
                </div>
            @endif

            {{-- ========== FICHE DU PLAT ========== --}}
            <div class="grid md:grid-cols-2 gap-10 mt-6 bg-white rounded-2xl shadow p-6 md:p-8">

                {{-- Image (gauche) --}}
          {{-- Image (gauche) --}}
                    <div class="flex justify-center md:justify-start">
                        @if($menu->image)
                            <img src="{{ asset('storage/' . $menu->image) }}"
                                alt="{{ $menu->title }}"
                                class="w-full max-w-sm h-64 md:h-72 object-cover rounded-xl">
                        @else
                            <div class="w-full max-w-sm h-64 md:h-72 bg-gray-200 rounded-xl flex items-center justify-center text-gray-400">
                                Pas d'image
                            </div>
                        @endif
                    </div>

                {{-- Informations (droite) --}}
                <div class="flex flex-col">

                    {{-- Titre --}}
                    <h1 class="font-display text-3xl md:text-4xl text-coffee">{{ $menu->title }}</h1>

                    {{-- Catégorie | Stock | Note moyenne --}}
                    <div class="flex flex-wrap items-center gap-3 mt-3 text-sm">
                        <span class="text-charcoal font-medium">{{ $menu->category->name }}</span>
                        <span class="text-gray-300">|</span>

                        @if($menu->is_available)
                            <span class="text-green-600 font-medium">En stock</span>
                        @else
                            <span class="text-red-600 font-medium">Rupture</span>
                        @endif

                        <span class="text-gray-300">|</span>

                        {{-- Étoiles --}}
                        <div class="flex items-center gap-1" title="Note moyenne">
                            <span class="text-lg text-amber">
                                @for($i = 1; $i <= 5; $i++)
                                    {{ $i <= $stars ? '★' : '☆' }}
                                @endfor
                            </span>
                            <span class="text-gray-500">
                                {{ number_format($averageRating ?? 0, 1) }}/5
                                ({{ $comments->count() }} avis)
                            </span>
                        </div>
                    </div>

                    {{-- Description --}}
                    <p class="text-charcoal/80 leading-relaxed mt-5">
                        {{ $menu->description ?? 'Aucune description pour ce plat.' }}
                    </p>

                    {{-- Prix --}}
                    <p class="text-3xl font-bold text-amber mt-6">
                        {{ number_format($menu->price, 2) }} DH
                    </p>

                    {{-- Boutons --}}
                    <div class="flex flex-col sm:flex-row gap-3 mt-8">
                        <a href="#comments"
                           @click="tab = (tab === 'comments' ? '' : 'comments')"
                           class="flex-1 text-center border-2 border-coffee text-coffee hover:bg-coffee hover:text-cream px-5 py-3 rounded-lg font-semibold transition">
                            Voir commentaires ({{ $comments->count() }})
                        </a>

                        <a href="#comments"
                           @click="tab = (tab === 'add' ? '' : 'add')"
                           class="flex-1 text-center bg-amber hover:bg-amber-dark text-white px-5 py-3 rounded-lg font-semibold transition">
                            + Ajouter un commentaire
                        </a>
                    </div>
                </div>
            </div>

            {{-- ========== ZONE COMMENTAIRES ========== --}}
            <div id="comments" class="mt-8 scroll-mt-6">

                {{-- Liste des commentaires --}}
                <div x-show="tab === 'comments'" style="display: none;"
                     class="bg-white rounded-2xl shadow p-6 md:p-8">
                    <h2 class="font-display text-2xl text-coffee mb-5">Avis des clients</h2>

                    @forelse($comments as $comment)
                        @php
                            // Étoiles de ce commentaire : note de 1 à 5 -> pourcentage -> étoiles
                            $commentStars = (int) floor(($comment->rating * 20) / 20);
                        @endphp

                        <div class="border-b border-gray-100 py-4 last:border-0">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-charcoal">{{ $comment->user->name }}</span>
                                <span class="text-sm text-gray-400">{{ $comment->created_at->format('d/m/Y') }}</span>
                            </div>
                            <div class="text-amber">
                                @for($i = 1; $i <= 5; $i++)
                                    {{ $i <= $commentStars ? '★' : '☆' }}
                                @endfor
                            </div>
                            <p class="text-charcoal/80 mt-1">{{ $comment->content }}</p>
                        </div>
                    @empty
                        <p class="text-gray-500">Aucun avis pour le moment. Soyez le premier !</p>
                    @endforelse
                </div>

                {{-- Formulaire d'ajout --}}
                <div x-show="tab === 'add'" style="display: none;"
                     class="bg-white rounded-2xl shadow p-6 md:p-8">
                    <h2 class="font-display text-2xl text-coffee mb-5">Donnez votre avis</h2>

                    @auth
                        <form method="POST" action="{{ route('comments.store', $menu) }}">
                            @csrf

                            {{-- Note --}}
                            <div class="mb-4">
                                <label for="rating" class="block text-sm font-medium text-charcoal mb-1">Note</label>
                                <select id="rating" name="rating"
                                        class="w-full rounded-lg border-gray-300 focus:border-amber focus:ring-amber">
                                    <option value="">-- Choisir une note --</option>
                                    @for($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}" @selected(old('rating') == $i)>
                                            {{ str_repeat('★', $i) . str_repeat('☆', 5 - $i) }} ({{ $i }}/5)
                                        </option>
                                    @endfor
                                </select>
                                @error('rating')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Commentaire --}}
                            <div class="mb-4">
                                <label for="content" class="block text-sm font-medium text-charcoal mb-1">Commentaire</label>
                                <textarea id="content" name="content" rows="4"
                                          class="w-full rounded-lg border-gray-300 focus:border-amber focus:ring-amber"
                                          placeholder="Votre avis sur ce plat...">{{ old('content') }}</textarea>
                                @error('content')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit"
                                    class="bg-amber hover:bg-amber-dark text-white px-6 py-2 rounded-lg font-semibold">
                                Envoyer
                            </button>
                            <p class="text-xs text-gray-400 mt-2">Votre avis sera publié après validation par l'administrateur.</p>
                        </form>
                    @else
                        <p class="text-charcoal">
                            Vous devez être connecté pour laisser un avis.
                            <a href="{{ route('login') }}" class="text-amber-dark font-semibold hover:underline">Se connecter</a>
                            ou
                            <a href="{{ route('register') }}" class="text-amber-dark font-semibold hover:underline">créer un compte</a>.
                        </p>
                    @endauth
                </div>
            </div>

        </div>
    </div>

@endsection