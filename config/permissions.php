<?php

return [
    "teams" => [
        "is_enabled" => true,
    ],

    "models" => [
        /*
         * Each of the below models will authorize with roles and permission at global level and at team level.
         */
        "users" => [
            \App\Models\User::class,
//            \App\Models\Admin::class,
        ],

        /*
         * Each of below models will have roles and permissions for their members at team level.
         */
        "teams" => [
//            \App\Models\Team1::class,
//            \App\Models\Team2::class,
        ]
    ]
];
