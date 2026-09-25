<?php

return [

    'meta' => [
        'title' => 'Daftar - JubahPanda',
    ],

    'eyebrow' => 'Pendaftaran',
    'title' => 'Tempah pengambilan jubah anda',
    'subtitle' => 'Ia mengambil masa kira-kira dua minit. Pasukan kami akan WhatsApp anda untuk mengesahkan butiran dan pembayaran.',

    'sections' => [
        'about' => 'Tentang anda',
        'robe' => 'Jubah anda',
        'delivery' => 'Cara anda terima jubah',
    ],

    'choose' => 'Pilih...',
    'submit' => 'Hantar pendaftaran',

    'fields' => [
        'full_name' => [
            'label' => 'Nama penuh',
            'hint' => 'Seperti dalam rekod universiti anda.',
        ],
        'matric_no' => [
            'label' => 'Nombor matrik',
            'hint' => 'Contoh: CB22001',
        ],
        'email' => [
            'label' => 'Emel (pilihan)',
            'hint' => 'Kami akan emel nombor rujukan tempahan anda. Biarkan kosong jika anda mahu WhatsApp sahaja.',
        ],
        'phone' => [
            'label' => 'Nombor WhatsApp',
            'hint' => 'Nombor mudah alih Malaysia, cth. 012-345 6789. Jika anda di luar negara, sila WhatsApp kami.',
        ],
        'programme_level' => [
            'label' => 'Tahap pengajian',
        ],
        'faculty_id' => [
            'label' => 'Fakulti',
        ],
        'robe_size_id' => [
            'label' => 'Saiz jubah',
        ],
        'convocation_session_id' => [
            'label' => 'Sesi konvokesyen',
        ],
        'delivery_method' => [
            'label' => 'Bagaimana anda mahu terima jubah?',
        ],
        'delivery_address' => [
            'label' => 'Alamat penghantaran (Kuantan sahaja)',
            'hint' => 'Hanya diperlukan untuk bayar semasa terima.',
        ],
        'notes' => [
            'label' => 'Ada apa-apa yang perlu kami tahu? (pilihan)',
            'hint' => 'Sehingga 500 aksara.',
        ],
        'documents_ack' => [
            'label' => 'Saya faham saya mungkin perlu menghantar dokumen pengambilan atau surat kebenaran yang diperlukan oleh UMPSA, dan akan berbuat demikian melalui WhatsApp.',
        ],
        'consent' => [
            'label' => 'Saya telah membaca :link dan bersetuju JubahPanda menggunakan butiran ini untuk mengambil jubah saya daripada UMPSA bagi pihak saya dan menghubungi saya melalui WhatsApp mengenainya.',
            'link' => 'notis privasi',
        ],
    ],

    'options' => [
        'programme_level' => [
            'diploma' => 'Diploma',
            'bachelor' => 'Ijazah Sarjana Muda',
            'master' => 'Ijazah Sarjana',
            'phd' => 'PhD',
        ],
    ],

    'delivery' => [
        'pickup' => [
            'title' => 'Ambil sendiri',
            'body' => 'Ambil di lokasi pengambilan kami di Kuantan. Kami akan sahkan slot anda melalui WhatsApp.',
        ],
        'cod' => [
            'title' => 'Penghantaran Kuantan',
            'body' => 'Kami hantar terus dalam Kuantan dan anda bayar di tempat.',
        ],
    ],

    'errors' => [
        'summary' => 'Sila betulkan perkara berikut:',
        'duplicate' => 'Tempahan dengan nombor matrik ini sudah wujud. Jika itu bukan anda, atau anda perlu menukar sesuatu, sila WhatsApp kami.',
        'generic' => 'Kami tidak dapat menghantar borang itu. Sila cuba lagi sebentar lagi.',
        'accept' => 'Sila tandakan kotak ini untuk meneruskan.',
        'required' => ':attribute wajib diisi.',
        'delivery_required' => 'Sila pilih ambil sendiri atau penghantaran Kuantan.',
        'address_required' => 'Sila masukkan alamat penghantaran anda di Kuantan.',
        'expired' => 'Halaman ini terbuka terlalu lama. Jawapan anda masih ada, sila hantar semula.',
        'matric_format' => 'Gunakan format CB22001: dua huruf, tahun kemasukan dua digit dan nombor tiga digit.',
        'email_format' => 'Masukkan alamat emel yang sah, atau biarkan kosong.',
        'phone_format' => 'Masukkan nombor mudah alih Malaysia, cth. 012-345 6789 atau +60123456789.',
    ],

    'closed' => [
        'title' => 'Pendaftaran telah ditutup',
        'body' => 'Pendaftaran ditutup pada :date. Jika anda masih perlukan bantuan dengan jubah anda, mesej kami di WhatsApp dan kami akan lihat apa yang boleh dilakukan.',
    ],

    'soon' => [
        'title' => 'Pendaftaran akan dibuka tidak lama lagi',
        'body' => 'Kami masih menyediakan pengambilan ini. Semak semula sebentar lagi, atau mesej kami di WhatsApp.',
    ],

    'done' => [
        'title' => 'Pendaftaran diterima',
        'subtitle' => 'Terima kasih, :name. Simpan nombor rujukan anda.',
        'reference_label' => 'Nombor rujukan',
        'session_label' => 'Sesi',
        'delivery_label' => 'Penerimaan',
        'next_title' => 'Apa berlaku seterusnya',
        'next' => [
            'Kami akan WhatsApp anda untuk mengesahkan butiran dan dokumen pengambilan yang diperlukan.',
            'Kami akan kongsikan butiran pembayaran. Ambil sendiri dibayar sebelum kami mengambil jubah anda; penghantaran Kuantan dibayar semasa terima.',
            'Kami ambil jubah anda di UMPSA dan simpan dengan selamat sehingga hari pengambilan atau penghantaran.',
        ],
        'emailed' => 'Kami juga sedang menghantar nombor rujukan anda ke :email. Jika tidak sampai, semak folder spam; halaman ini dan WhatsApp sudah memadai.',
        'whatsapp_cta' => 'Mesej kami di WhatsApp',
        'whatsapp_message' => 'Hai JubahPanda, saya :name. Nombor rujukan tempahan saya: :reference.',
        'back' => 'Kembali ke laman utama',
    ],

    'error_pages' => [
        'server_title' => 'Ada masalah di pihak kami',
        'server_body' => 'Jika anda sedang mendaftar, tempahan anda TIDAK disimpan. Sila cuba lagi, atau WhatsApp kami.',
        'throttle_title' => 'Terlalu banyak percubaan',
        'throttle_body' => 'Sila tunggu seminit dan cuba lagi.',
        'contact' => 'WhatsApp kami',
    ],

    // Dihantar OLEH pasukan KEPADA pendaftar, dalam bahasa yang mereka gunakan semasa mendaftar.
    'admin_greeting' => 'Hai :name, ini JubahPanda mengenai tempahan jubah anda :reference.',

];
