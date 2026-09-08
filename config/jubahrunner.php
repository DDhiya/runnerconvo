<?php

/*
|--------------------------------------------------------------------------
| JubahRunner
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
$instagram = env('JR_INSTAGRAM', 'jubahrunner');

return [

    // Digits only, international format, no "+" — e.g. 60123456789.
    'whatsapp_number' => $whatsapp,
    'whatsapp_url' => 'https://wa.me/'.$whatsapp,

    'instagram' => $instagram,
    'instagram_url' => 'https://instagram.com/'.$instagram,

    'email' => env('JR_EMAIL', 'hello@jubahrunner.my'),

    // Point this at your registration form (Google Form for now, a real
    // Laravel form later). Falls back to WhatsApp so no CTA is ever dead.
    'register_url' => env('JR_REGISTER_URL') ?: 'https://wa.me/'.$whatsapp,

];
