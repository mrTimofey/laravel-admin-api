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
        // external css file path for wysiwyg (use .cke_editable selector as root within this file)
        'css' => null
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