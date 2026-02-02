<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenancy Mode
    |--------------------------------------------------------------------------
    |
    | Controls how the package handles multi-tenancy.
    | 'auto' - Auto-detect tenancy package presence
    | 'single' - Force single-tenant mode
    | 'multi' - Force multi-tenant mode
    |
    */
    'tenancy_mode' => 'auto',

    /*
    |--------------------------------------------------------------------------
    | Address Types
    |--------------------------------------------------------------------------
    |
    | The list of allowed address types. Used for validation.
    |
    */
    'types' => ['home', 'work', 'billing', 'shipping', 'mailing', 'other'],

    /*
    |--------------------------------------------------------------------------
    | Default Type
    |--------------------------------------------------------------------------
    |
    | The default address type when none is specified.
    |
    */
    'default_type' => 'home',

    /*
    |--------------------------------------------------------------------------
    | Allow Custom Types
    |--------------------------------------------------------------------------
    |
    | When true, types not listed in 'types' array are still accepted.
    | When false, only types in the 'types' array are valid.
    |
    */
    'allow_custom_types' => true,

];
