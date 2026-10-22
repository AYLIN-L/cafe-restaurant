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
    | Important : par défaut, blade-icons enregistre un composant générique
    | <x-icon name="...">. Il entre en conflit avec le composant maison
    | « resources/views/components/icon.blade.php » et le masque.
    |
    | On désactive donc ce composant générique (« default » => null) afin de
    | conserver l'utilisation de <x-icon name="..."> dans l'application, tout
    | en gardant les icônes Lucide disponibles via <x-lucide-...> et
    | @svg('lucide-...'). « disabled » doit rester false pour que les
    | composants Lucide préfixés continuent d'être enregistrés.
    |
    */

    'components' => [
        'disabled' => false,
        'default' => null,
    ],

];
