<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Catalogue
    |--------------------------------------------------------------------------
    |
    | A merchant connects their own store (an "origin") from the dashboard, and
    | its credentials are stored per-business in the database. The settings below
    | are platform-level defaults shared across all merchants, plus an optional
    | local development origin used when a request arrives without a widget key.
    |
    */

    'catalogue' => [

        // Pre-selected driver when a merchant connects a new store.
        'default_driver' => env('CLOSR_CATALOGUE_DRIVER', 'woocommerce'),

        // Non-secret, platform-level defaults per driver.
        'drivers' => [
            'woocommerce' => [
                'timeout' => env('WOOCOMMERCE_TIMEOUT', 15),
            ],
            'dummyjson' => [
                'timeout' => env('DUMMYJSON_TIMEOUT', 15),
            ],
        ],

        // Fallback origin for local development / the demo widget, used only
        // when a chat request carries no widget key. Defaults to the public
        // DummyJSON catalogue so the demo works with no credentials. Real
        // merchants resolve via their own connected origin.
        'dev_origin' => [
            'driver' => env('CLOSR_DEV_ORIGIN_DRIVER', 'dummyjson'),
            'config' => [
                'url' => env('CLOSR_DEV_ORIGIN_URL', 'https://dummyjson.com'),
                'key' => env('CLOSR_DEV_ORIGIN_KEY'),
                'secret' => env('CLOSR_DEV_ORIGIN_SECRET'),
            ],
        ],

    ],

];
