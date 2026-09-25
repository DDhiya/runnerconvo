<?php

return [

    'meta' => [
        'title' => 'Register - JubahPanda',
    ],

    'eyebrow' => 'Registration',
    'title' => 'Book your robe collection',
    'subtitle' => 'It takes about two minutes. Our team will WhatsApp you to confirm your details and payment.',

    'sections' => [
        'about' => 'About you',
        'robe' => 'Your robe',
        'delivery' => 'Getting it to you',
    ],

    'choose' => 'Choose...',
    'submit' => 'Submit registration',

    'fields' => [
        'full_name' => [
            'label' => 'Full name',
            'hint' => 'As it appears on your university records.',
        ],
        'matric_no' => [
            'label' => 'Matric number',
            'hint' => 'Example: CB22001',
        ],
        'email' => [
            'label' => 'Email (optional)',
            'hint' => 'We will email you your booking reference. Leave blank if you prefer WhatsApp only.',
        ],
        'phone' => [
            'label' => 'WhatsApp number',
            'hint' => 'A Malaysian mobile number, e.g. 012-345 6789. If you are overseas, please WhatsApp us instead.',
        ],
        'programme_level' => [
            'label' => 'Programme level',
        ],
        'faculty_id' => [
            'label' => 'Faculty',
        ],
        'robe_size_id' => [
            'label' => 'Robe size',
        ],
        'convocation_session_id' => [
            'label' => 'Convocation session',
        ],
        'delivery_method' => [
            'label' => 'How will you get your robe?',
        ],
        'delivery_address' => [
            'label' => 'Delivery address (Kuantan only)',
            'hint' => 'Only needed for cash-on-delivery.',
        ],
        'notes' => [
            'label' => 'Anything we should know? (optional)',
            'hint' => 'Up to 500 characters.',
        ],
        'documents_ack' => [
            'label' => 'I understand I may need to send the collection document or authorisation letter that UMPSA requires, and I will do so on WhatsApp.',
        ],
        'consent' => [
            'label' => 'I have read the :link and agree to JubahPanda using these details to collect my robe from UMPSA on my behalf and to contact me about it on WhatsApp.',
            'link' => 'privacy notice',
        ],
    ],

    'options' => [
        'programme_level' => [
            'diploma' => 'Diploma',
            'bachelor' => 'Bachelor degree',
            'master' => 'Master',
            'phd' => 'PhD',
        ],
    ],

    'delivery' => [
        'pickup' => [
            'title' => 'Self-pickup',
            'body' => 'Collect it from our pickup point in Kuantan. We will confirm your slot on WhatsApp.',
        ],
        'cod' => [
            'title' => 'Kuantan delivery',
            'body' => 'We hand-deliver within Kuantan and you pay on the spot.',
        ],
    ],

    'errors' => [
        'summary' => 'Please fix the following:',
        'duplicate' => 'A booking with this matric number already exists. If that was not you, or you need to change something, please WhatsApp us.',
        'generic' => 'We could not submit that. Please try again in a moment.',
        'accept' => 'Please tick this box to continue.',
        'required' => ':attribute is required.',
        'delivery_required' => 'Please choose self-pickup or Kuantan delivery.',
        'address_required' => 'Please enter your Kuantan delivery address.',
        'expired' => 'This page was open for too long. Your answers are still here, please submit again.',
        'matric_format' => 'Use the format CB22001: two letters, a two-digit intake year and a three-digit number.',
        'email_format' => 'Enter a valid email address, or leave it blank.',
        'phone_format' => 'Enter a Malaysian mobile number, e.g. 012-345 6789 or +60123456789.',
    ],

    'closed' => [
        'title' => 'Registration has closed',
        'body' => 'Registration closed on :date. If you still need help with your robe, message us on WhatsApp and we will see what we can do.',
    ],

    'soon' => [
        'title' => 'Registration opens soon',
        'body' => 'We are still setting up this intake. Check back shortly, or message us on WhatsApp.',
    ],

    'done' => [
        'title' => 'Registration received',
        'subtitle' => 'Thank you, :name. Keep your reference number handy.',
        'reference_label' => 'Reference number',
        'session_label' => 'Session',
        'delivery_label' => 'Delivery',
        'next_title' => 'What happens next',
        'next' => [
            'We will WhatsApp you to confirm your details and any collection documents needed.',
            'We will share payment details. Self-pickup is paid before we collect your robe; Kuantan delivery is paid on delivery.',
            'We collect your robe from UMPSA and keep it safe until your pickup or delivery.',
        ],
        'emailed' => 'We are also emailing your reference to :email. If it does not arrive, check your spam folder; this page and WhatsApp are enough on their own.',
        'whatsapp_cta' => 'Message us on WhatsApp',
        'whatsapp_message' => 'Hi JubahPanda, I am :name. My booking reference is :reference.',
        'back' => 'Back to home page',
    ],

    'error_pages' => [
        'server_title' => 'Something went wrong on our side',
        'server_body' => 'If you were registering, your booking was NOT saved. Please try again, or WhatsApp us.',
        'throttle_title' => 'Too many attempts',
        'throttle_body' => 'Please wait a minute and try again.',
        'contact' => 'WhatsApp us',
    ],

    // Sent BY the team TO the registrant, in the language they registered in.
    'admin_greeting' => 'Hi :name, this is JubahPanda about your robe booking :reference.',

];
