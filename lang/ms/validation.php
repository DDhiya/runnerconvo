<?php

/*
| Deliberately PARTIAL: only the rules the public registration form uses. Any rule
| not listed here falls back to the framework English text (APP_FALLBACK_LOCALE=en),
| so a missing key never prints as a raw key. There is no lang/en/validation.php -
| the framework ships its own.
|
| Field names come from RegistrationRequest::attributes(), not from here.
*/

return [

    'accepted' => ':attribute mesti ditandakan.',
    'boolean' => ':attribute mesti benar atau palsu.',
    'exists' => ':attribute yang dipilih tidak sah.',
    'in' => ':attribute yang dipilih tidak sah.',
    'max' => [
        'string' => ':attribute tidak boleh melebihi :max aksara.',
    ],
    'regex' => 'Format :attribute tidak sah.',
    'required' => ':attribute wajib diisi.',
    'required_if' => ':attribute wajib diisi apabila :other ialah :value.',
    'string' => ':attribute mestilah teks.',
    'unique' => ':attribute ini telah digunakan.',

    'custom' => [],

    'attributes' => [],

];
