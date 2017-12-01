<?php

return [
    /**
     * Admin frontend SPA HTML markup
     */
    'frontend_entry' => public_path('admin-dist/index.html'),

    /**
     * Admin frontend SPA HTML markup, catch-all path prefix
     */
    'frontend_path' => env('ADMIN_ENTRY', 'admin'),

    /**
     * API path prefix
     */
    'api_prefix' => env('ADMIN_PATH', 'api/admin'),

    /**
     * API routes middleware
     */
    'api_middleware' => ['auth:api'],

    /**
     * WYSYWIG editor config
     */
    'wysiwyg' => [
        // width/height for uploaded images, remove to use original
        'image_upload_size' => [1280, null]
    ],

    'upload' => [
        /**
         * Where to upload files
         * IMPORTANT: images will not be uploaded here! Images are controlled by mr-timofey/laravel-aio-images package!
         * @see config('aio_images')
         */
        'path' => public_path('storage/uploads'),

        /**
         * HTTP accessible path for uploaded files
         */
        'public_path' => '/storage/uploads',
    ],

    /**
     * List model classes here
     */
    'models' => [
        // App\User::class
    ],

    /**
     * Main navigation
     */
    'nav' => [
        // header
        'Header',

        // entity listing link
        [
            'title' => 'Users',
            'entity' => 'users',
            'fa' => 'user'
        ],

        // custom absolute link
        [
            'title' => 'Back to the site',
            'path' => '/',
            'fa' => 'arrow-left'
        ],

        // custom admin route
        [
            'title' => 'Edit user [1]',
            'route' => '/entity/users/item/1',
            'fa' => 'user-o'
        ],

        // link group
        [
            'title' => 'Group',
            'fa' => 'plus-square',
            'items' => [
                [
                    'title' => 'Sublink',
                    'path' => '#'
                ]
            ]
        ]
    ]
];