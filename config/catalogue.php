<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The ISO 4217 currency code applied to packages when one is not supplied.
    | Used as the source of truth by the packages migration, requests, seeders,
    | and the API options payload so the value is never hardcoded per call site.
    |
    */

    'default_currency' => env('CATALOGUE_DEFAULT_CURRENCY', 'ZAR'),

];
