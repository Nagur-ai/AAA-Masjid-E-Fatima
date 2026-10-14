<?php

/*
|--------------------------------------------------------------------------
| Website languages
|--------------------------------------------------------------------------
|
| Languages visitors can choose on the public site. To add one, add it here
| and put its translations in lang/<code>.json (copy lang/te.json and
| translate the values). Languages listed under "rtl" are right-to-left.
|
*/

return [
    'supported' => [
        'en' => 'English',
        'te' => 'తెలుగు',
        'ur' => 'اردو',
    ],

    'rtl' => ['ur'],

    'default' => env('APP_LOCALE', 'en'),
];
