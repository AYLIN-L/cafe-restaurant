<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Coordonnées du restaurant
    |--------------------------------------------------------------------------
    |
    | Centralise les informations affichées sur la page Contact et dans le
    | pied de page. Remplacez les valeurs « XXXXXX » dès que les coordonnées
    | réelles sont disponibles. L'adresse ci-dessous est celle confirmée pour
    | la démonstration (Rick's Café, Casablanca).
    |
    */

    'name' => 'Café Resto',

    // Fuseau utilisé pour le statut « Ouvert / Fermé ».
    'timezone' => env('RESTAURANT_TIMEZONE', 'Africa/Casablanca'),

    'address' => [
        'line1' => env('RESTAURANT_ADDRESS_LINE', '248 Boulevard Sour Jdid'),
        'district' => env('RESTAURANT_DISTRICT', 'Ancienne Médina'),
        'city' => env('RESTAURANT_CITY', 'Casablanca'),
        'postal_code' => env('RESTAURANT_POSTAL_CODE', '20004'),
        'country' => env('RESTAURANT_COUNTRY', 'Maroc'),
        'latitude' => (float) env('RESTAURANT_LATITUDE', 33.6049950),
        'longitude' => (float) env('RESTAURANT_LONGITUDE', -7.6204060),
        'map_query' => env(
            'RESTAURANT_MAP_QUERY',
            "Rick's Café, 248 Boulevard Sour Jdid, Casablanca 20004, Maroc"
        ),
    ],

    // Valeurs temporaires : à remplacer par les coordonnées réelles.
    'phone' => env('RESTAURANT_PHONE', 'XXXXXX'),
    'email' => env('RESTAURANT_EMAIL', 'XXXXXX'),

    /*
    |--------------------------------------------------------------------------
    | Horaires d'ouverture
    |--------------------------------------------------------------------------
    |
    | Laissez ce tableau vide pour utiliser les horaires de
    | config/reservations.php (08:00–22:00) tous les jours.
    |
    | Pour un horaire différent selon le jour, ajoutez par exemple :
    | 'monday' => ['open' => '09:00', 'close' => '21:00'],
    |
    | Les jours listés dans « closed_days » sont considérés comme fermés.
    |
    */

    'hours' => [],

    'closed_days' => [],

];
