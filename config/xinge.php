<?php
return [
    'ios_app_id' => '3c8e40bb90698',
    'ios_secret_key' => 'f4e0307685975f9f044d228a3398db1e',
    'ios_access_id' => '2200320397',
    'ios_access_key' => 'IN49VCD141GK',

    'android_app_id' => '9290554b1f18e',
    'android_secret_key' => '23b409b96880cc0dc5362d41dce8e841',
    'android_access_id' => '2100320396',
    'android_access_key' => 'A7Z95YGZ19JE',

    'merchant_app' => [
        'ios_app_id' => '2118dc96bf62f',
        'ios_secret_key' => 'e5aed0d770d0f36ac19670d041e21e58',
        'ios_access_id' => '2200325800',
        'ios_access_key' => 'IBD9JZ28C17P',

        'android_app_id' => 'cab973607b371',
        'android_secret_key' => '71acb05f6441768d0a98eb0b5fdb1bf0',
        'android_access_id' => '2100325777',
        'android_access_key' => 'A2A1NT6W92GT',
    ],

    'environment' => env('XINGE_ENVIRONMENT', 'product'),
    'merchant_app_prefix' => 'merchant_app_',
];