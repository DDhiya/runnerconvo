<?php

/*
|--------------------------------------------------------------------------
| JubahPanda
|--------------------------------------------------------------------------
|
| Every placeholder the landing page needs lives here, so going live is a
| matter of filling in .env rather than hunting through Blade templates.
|
| TODO before launch: set JP_WHATSAPP_NUMBER, JP_INSTAGRAM, JP_EMAIL and
| JP_REGISTER_URL in .env. Until JP_REGISTER_URL is set, every "Register"
| button falls back to opening a WhatsApp chat.
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

    'email' => env('JP_EMAIL', 'hello@jubahpanda.my'),

    // Point this at your registration form (Google Form for now, a real
    // Laravel form later). Falls back to WhatsApp so no CTA is ever dead.
    'register_url' => env('JP_REGISTER_URL') ?: 'https://wa.me/'.$whatsapp,

    // Pickup point. The embed URL needs no API key — Google serves a basic
    // "for development purposes" pin from a plain ?q= query.
    'address' => $address,
    'map_url' => env('JP_MAP_URL', 'https://maps.app.goo.gl/aewJWH1vxBm4xGkz8'),
    'map_embed_url' => 'https://www.google.com/maps?q='.rawurlencode($address).'&output=embed',

];
