<?php

return [
    // Teks footer halaman login.
    'login_footer' => env('SIMPEL_LOGIN_FOOTER', env('APP_NAME', 'SIMPEL')),

    // Password awal akun yang dibuat dari menu Guru (tombol Buat Akun Login). Wajib diganti setelah login pertama.
    'default_password' => env('SIMPEL_DEFAULT_PASSWORD', 'Guru@12345'),
];
