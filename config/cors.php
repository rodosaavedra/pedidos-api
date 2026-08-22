<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // pedidos-app (cliente) y pedidos-admin (nuevo panel), en desarrollo local.
    // Ajusta los puertos si Vite te asignó otros al correr npm run dev.
    'allowed_origins' => [
        'http://localhost:5173', // pedidos-app
        'http://localhost:5175', // pedidos-admin
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5175',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];