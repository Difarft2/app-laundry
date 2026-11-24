<?php

return [
    /*
     * Kunci bersama dengan landing page. Kunci ini hanya dikenal oleh backend
     * dan server Nuxt (private runtimeConfig), tidak pernah dikirim ke browser.
     */
    'api_key' => env('LANDING_API_KEY'),
];
