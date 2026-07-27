<?php

return [
    /*
     * Default visibility for each baseline profile field, seeded into
     * profile_field_privacies when a Person is created. The person (or their
     * managing parent) can change these afterwards.
     */
    'defaults' => [
        'profile_photo_path' => 'everyone',
        'full_name' => 'everyone',
        'email' => 'family',
        'mobile' => 'family',
        'address' => 'family',
        'social_links' => 'family',
        'bio' => 'everyone',
        'date_of_birth' => 'family',
    ],
];
