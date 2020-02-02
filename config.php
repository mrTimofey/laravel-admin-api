<?php

return [
    /**
     * Admin frontend SPA HTML markup
     * Must be same with vue-admin-front buildDest + index.html
     */
    'frontend_entry' => public_path('admin-dist/index.html'),

    /**
     * Admin frontend SPA HTML markup, catch-all path prefix
     * Must be same with vue-admin-front basePath config
     */
    'frontend_path' => env('ADMIN_ENTRY', 'admin'),

    /**
     * API path prefix
     * Must be same with vue-admin-front apiRoot config
     */
    'api_prefix' => env('ADMIN_PATH', 'api/admin'),

    /**
     * API guard
     */
    'api_guard' => 'api',

    /**
     * API routes middleware
     */
    'api_middleware' => [
        'auth:api' // should use same guard as api_guard!
    ],

    /**
     * Middleware for authentication API
     */
    'auth_middleware' => ['throttle:5'],

    /**
     * WYSIWYG editor config
     */
    'wysiwyg' => [
        // width/height for uploaded images, remove to use original
        'image_upload_size' => [1280, null],
        // upload image validation
        'image_upload_rules' => ['image', 'max:4096'],
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
     * Pipe to resize thumbnails.
     * @see https://github.com/mrTimofey/laravel-aio-images
     */
    'thumbnail_pipe' => [['heighten', 120]],

    /**
     * Models used in API.
     * Each array item can have an optional string key to provide a URL chunk used in API routes.
     * Default url chunk is same as a table name with underscores replaced by dashes.
     * You can also define multiple items with same model class and different keys to provide different contexts.
     * Call ModelHandler::getName() to get current context.
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
            'icon' => 'fas fa-user'
        ],

        // custom absolute link
        [
            'title' => 'Back to the site',
            'path' => '/',
            'icon' => 'fas fa-arrow-left'
        ],

        // custom admin route
        [
            'title' => 'Edit user [1]',
            'route' => '/entity/users/item/1',
            'icon' => 'far fa-user'
        ],

        // link group
        [
            'title' => 'Group',
            'icon' => 'fas fa-plus-square',
            'items' => [
                [
                    'title' => 'Sublink',
                    'path' => '#'
                ]
            ]
        ]
    ]
];