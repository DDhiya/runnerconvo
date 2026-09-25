<?php

/*
|--------------------------------------------------------------------------
| JubahPanda
|--------------------------------------------------------------------------
|
| Every placeholder the landing page needs lives here, so going live is a
| matter of filling in .env rather than hunting through Blade templates.
|
| TODO before launch: set JP_WHATSAPP_NUMBER, JP_INSTAGRAM and JP_EMAIL in
| .env. The Register buttons point at the in-app form (/register) unless
| JP_REGISTER_URL overrides them.
|
*/

$whatsapp = env('JP_WHATSAPP_NUMBER', '60123456789');
$instagram = env('JP_INSTAGRAM', 'jubahpanda');
$address = env('JP_ADDRESS', 'No. 15, Lorong IM 2/6, Bandar Indera Mahkota, 25200 Kuantan, Pahang');

return [

    // The main business line. Per-runner numbers live in the `runners` table and
    // are managed at /admin — this value is deliberately independent of that
    // table so every CTA on the page keeps working even if the database is
    // unavailable. Digits only, international format, no "+" — e.g. 60123456789.
    'whatsapp_number' => $whatsapp,
    'whatsapp_url' => 'https://wa.me/'.$whatsapp,

    'instagram' => $instagram,
    'instagram_url' => 'https://instagram.com/'.$instagram,

    'email' => env('JP_EMAIL', 'support@jubahpanda.my'),

    // Blank (the default) means the in-app form at /register. Set it only as an
    // override — e.g. back to https://wa.me/... as a KILL SWITCH if the form
    // misbehaves; flipping it is an .env edit + config:cache, no deploy. route()
    // cannot be called here: config is evaluated, and cached, before routes exist,
    // so the fallback is resolved in AppServiceProvider's view composer.
    'register_url' => env('JP_REGISTER_URL') ?: null,

    // Registration closes at this moment, read as Kuala Lumpur time
    // (e.g. "2026-10-21 23:59"). Blank = open indefinitely.
    'registration_closes_at' => env('JP_REGISTRATION_CLOSES_AT') ?: null,

    // App storage stays UTC (config/app.php); this is for parsing the closing date
    // and for displaying times in the admin.
    'timezone' => 'Asia/Kuala_Lumpur',

    // Snapshotted onto each booking. Must match pricing.price in lang/*/landing.php;
    // LandingPageTest enforces it.
    'price_sen' => 4500,

    // Bump whenever lang/*/privacy.php changes materially; stored on each booking.
    'privacy_version' => '2026-09-25',
    'retention_days' => 365,

    // Pickup point. The embed URL needs no API key — Google serves a basic
    // "for development purposes" pin from a plain ?q= query.
    'address' => $address,
    'map_url' => env('JP_MAP_URL', 'https://maps.app.goo.gl/aewJWH1vxBm4xGkz8'),
    'map_embed_url' => 'https://www.google.com/maps?q='.rawurlencode($address).'&output=embed',

];
