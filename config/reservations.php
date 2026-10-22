<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Règles métier des réservations
    |--------------------------------------------------------------------------
    |
    | Cette configuration est la SEULE source de vérité pour les horaires,
    | la durée et les créneaux. Le formulaire client, l'endpoint AJAX, la
    | validation serveur et l'attribution de table lisent tous ces valeurs.
    |
    | duration_minutes   : durée d'une réservation.
    | buffer_minutes     : temps de préparation ajouté APRÈS chaque réservation.
    | opening_time       : ouverture du restaurant.
    | closing_time       : fermeture du restaurant (la réservation doit finir
    |                     au plus tard à cette heure).
    | slot_step_minutes  : pas des créneaux de réservation.
    | booking_window_days: nombre de jours maximum à l'avance.
    | retention_days     : seuil au-delà duquel une réservation peut être
    |                     masquée par l'administrateur. AUCUNE suppression
    |                     automatique n'est effectuée.
    | pending_expiration_hours : durée de vie d'une demande en attente avant
    |                     expiration automatique (elle libère la journée et
    |                     ne compte jamais comme une absence).
    | daily_reservation_limit  : nombre de réservations par client et par jour.
    | absence_threshold  : nombre d'absences vérifiées qui bloquent les
    |                     nouvelles réservations d'un client.
    |
    */

    'duration_minutes' => 120,
    'buffer_minutes' => 15,
    'opening_time' => '08:00',
    'closing_time' => '22:00',
    'slot_step_minutes' => 30,
    'booking_window_days' => 7,
    'retention_days' => 90,
    'pending_expiration_hours' => 24,
    'daily_reservation_limit' => 1,
    'absence_threshold' => 5,

    /*
    |--------------------------------------------------------------------------
    | Statuts des réservations
    |--------------------------------------------------------------------------
    |
    | Métadonnées d'affichage (libellé français, icône Lucide, tonalité) pour
    | chaque statut. Utilisées par les badges, les filtres et les modales.
    |
    | « completed » n'existe PAS en base : il est dérivé du temps écoulé
    | (voir Reservation::displayStatus()).
    |
    | « expired » n'existe pas non plus en base : une demande expirée est
    | stockée avec le statut « cancelled » et un horodatage « expired_at »
    | (voir Reservation::displayStatus()). Une réservation expirée n'a
    | jamais été confirmée : elle ne compte donc jamais comme une absence.
    |
    */

    'statuses' => [
        'pending' => [
            'label' => 'En attente',
            'icon' => 'clock',
            'tone' => 'amber',
        ],
        'confirmed' => [
            'label' => 'Confirmée',
            'icon' => 'circle-check',
            'tone' => 'emerald',
        ],
        'rejected' => [
            'label' => 'Refusée',
            'icon' => 'circle-x',
            'tone' => 'red',
        ],
        'cancelled' => [
            'label' => 'Annulée',
            'icon' => 'ban',
            'tone' => 'gray',
        ],
        'completed' => [
            'label' => 'Terminée',
            'icon' => 'circle-check',
            'tone' => 'gray',
        ],
        'expired' => [
            'label' => 'Expirée',
            'icon' => 'calendar-x',
            'tone' => 'gray',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Présence du client
    |--------------------------------------------------------------------------
    |
    | Une présence n'est enregistrée que par l'administrateur, et uniquement
    | sur une réservation confirmée. Seules les absences « absent » sont
    | vérifiées et compteront pour la limite d'absences.
    |
    */

    'attendance' => [
        'unknown' => [
            'label' => 'Non renseignée',
            'icon' => 'circle-help',
            'tone' => 'gray',
        ],
        'present' => [
            'label' => 'Client présent',
            'icon' => 'circle-check',
            'tone' => 'emerald',
        ],
        'absent' => [
            'label' => 'Client absent',
            'icon' => 'circle-x',
            'tone' => 'red',
        ],
        'excused' => [
            'label' => 'Absence excusée',
            'icon' => 'circle-slash',
            'tone' => 'amber',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transitions de statut autorisées
    |--------------------------------------------------------------------------
    |
    | Vérifié côté serveur : une transition interdite est refusée même si la
    | requête HTTP est fabriquée à la main.
    |
    */

    'transitions' => [
        'pending' => ['confirmed', 'rejected', 'cancelled'],
        'confirmed' => ['cancelled'],
        'rejected' => [],
        'cancelled' => [],
    ],

];
