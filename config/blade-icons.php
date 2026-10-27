<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jeux d'icônes (Icons Sets)
    |--------------------------------------------------------------------------
    |
    | Les icônes Lucide sont enregistrées automatiquement par le paquet
    | fuzzyfox/lucide-for-laravel (préfixe « lucide- »), donc aucun jeu
    | n'est déclaré ici.
    |
    */

    'sets' => [],

    /*
    |--------------------------------------------------------------------------
    | Classes et attributs globaux
    |--------------------------------------------------------------------------
    */

    'class' => '',

    'attributes' => [],

    'fallback' => '',

    /*
    |--------------------------------------------------------------------------
    | Composants Blade
    |--------------------------------------------------------------------------
    |
    | L'application utilise uniquement les composants Lucide explicites
    | (<x-lucide-calendar-days class="..."/>, etc.). Le composant générique
    | <x-icon name="..."> de blade-icons est donc désactivé (« default » =>
    | null) : aucune icône ambiguë ne peut se glisser dans les vues.
    |
    | « disabled » doit rester false pour que les composants Lucide préfixés
    | continuent d'être enregistrés.
    |
    */

    'components' => [
        'disabled' => false,
        'default' => null,
    ],

];
