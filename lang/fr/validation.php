<?php

return [
    'required_with_all' => 'Le champ :attribute est obligatoire quand :values est présent.',
    'filled' => 'Le champ :attribute doit être renseigné.',
    'nullable' => 'Le champ :attribute peut être vide.',
    'unique' => 'La valeur du champ :attribute est déjà utilisée.',
    'confirmed' => 'La confirmation de :attribute ne correspond pas.',
    'accepted' => 'Vous devez accepter :attribute.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'numeric' => 'Le champ :attribute doit contenir un nombre.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'lowercase' => 'Le champ :attribute doit être en minuscules.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'max' => [
        'numeric' => 'Le champ :attribute ne peut pas dépasser :max.',
        'file' => 'Le fichier :attribute ne peut pas dépasser :max kilo-octets.',
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
        'array' => 'Le tableau :attribute ne peut contenir plus de :max éléments.',
    ],
    'min' => [
        'numeric' => 'Le champ :attribute doit être au minimum :min.',
        'file' => 'Le fichier :attribute doit peser au minimum :min kilo-octets.',
        'string' => 'Le champ :attribute doit contenir au minimum :min caractères.',
        'array' => 'Le tableau :attribute doit contenir au moins :min éléments.',
    ],
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'required' => 'Le champ :attribute est obligatoire.',
    'phone' => 'Le champ :attribute doit être un numéro de téléphone valide.',
    'attributes' => [
        'name' => 'nom complet',
        'email' => 'adresse e-mail',
        'phone' => 'téléphone',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'remember' => 'se souvenir de moi',
    ],
];
