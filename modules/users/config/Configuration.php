<?php

return [
    'ICONS' => [
        'main' => 'fa fa-newspaper-o',
        'BLOCKS' => [
            'users' => 'fa fa-list',
        ],
    ],
    'FIELDS' => [
        'TYPES' => [
            'visibility' => 'dropdown',
            'password' => 'hidden',
        ],
        'CLASSES' => [
        ],
        'WRAP' => [
            'created_at' => 'col-xlg-6 col-md-12 p-l-5 p-r-10 m-t-10',
            'visibility' => 'col-xlg-6 col-md-12 p-l-5 p-r-10 m-t-10',
        ],
        'ORDER' => [
            'username' => 1,
            'first_name' => 10,
            'last_name' => 20,
            'visibility' => 30,
//            'confirm_password' => 40,
            'email' => 50,
            'address' => 60,
            'post_code' => 70,
//            'password' => 80,
            'main_image' => 90,

        ],
        'RIGHT' => [
        ],
    ],
    'visibility_values' => Registry()->localizer->get('VISIBILITY'),
];

