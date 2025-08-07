<?php

return [
    "teams" => [
        "is_enabled" => true,
    ],

    "models" => [
        /*
         * Each of below models will authorise with roles and permission at global level and at team level.
         */
        "users" => [
            \App\Models\Users\Admin::class,
            \App\Models\User::class,
        ],

        /*
         * Each of below models will have roles and permissions for their members at team level.
         */
        "teams" => [
//            \App\Models\Organization::class,
//            \App\Models\Division::class,
        ]
    ]
];
