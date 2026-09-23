<?php

/*
|--------------------------------------------------------------------------
| JubahPanda
|--------------------------------------------------------------------------
|
| Every placeholder the landing page needs lives here, so going live is a
| matter of filling in .env rather than hunting through Blade templates.
|
| TODO before launch: set JR_WHATSAPP_NUMBER, JR_INSTAGRAM, JR_EMAIL and
| JR_REGISTER_URL in .env. Until JR_REGISTER_URL is set, every "Register"
| button falls back to opening a WhatsApp chat.
|
*/

$whatsapp = env('JR_WHATSAPP_NUMBER', '60123456789');
$instagram = env('JR_INSTAGRAM', 'jubahpanda');
$address = env('JR_ADDRESS', 'No. 15, Lorong IM 2/6, Bandar Indera Mahkota, 25200 Kuantan, Pahang');

return [

    // Digits only, international format, no "+" — e.g. 60123456789.
    'whatsapp_number' => $whatsapp,
    'whatsapp_url' => 'https://wa.me/'.$whatsapp,

    'instagram' => $instagram,
    'instagram_url' => 'https://instagram.com/'.$instagram,

    'email' => env('JR_EMAIL', 'hello@jubahpanda.my'),

    // Point this at your registration form (Google Form for now, a real
    // Laravel form later). Falls back to WhatsApp so no CTA is ever dead.
    'register_url' => env('JR_REGISTER_URL') ?: 'https://wa.me/'.$whatsapp,

    // Pickup point. The embed URL needs no API key — Google serves a basic
    // "for development purposes" pin from a plain ?q= query.
    'address' => $address,
    'map_url' => env('JR_MAP_URL', 'https://maps.app.goo.gl/aewJWH1vxBm4xGkz8'),
    'map_embed_url' => 'https://www.google.com/maps?q='.rawurlencode($address).'&output=embed',

];
