<?php

use App\Models\User;

return [
    'teams' => [
        'is_enabled' => true,
    ],

    'models' => [
        User::class => [
            // \App\Models\Team1::class,
            // \App\Models\Team2::class,
        ],
        // \App\Models\Admin::class=> [
        //     \App\Models\Team3::class,
        //     \App\Models\Team4::class,
        // ],
    ],
];
