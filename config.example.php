<?php
// config.php - NO subir a repositorios. Rellena con tus datos de Mailgun.
return [
    'smtp' => [
        'host'       => 'smtp.eu.mailgun.org',  // Mailgun EU: smtp.eu.mailgun.org | US: smtp.mailgun.org
        'port'       => 587,                    // 587 (STARTTLS)
        'encryption' => 'tls',                  // 'tls'
        'username'   => 'postmaster@mg.tudominio.com',  // o tu usuario SMTP de Mailgun
        'password'   => 'TU_CONTRASENA_SMTP_MAILGUN',
    ],
    'sender' => [
        'email' => 'recomendaciones@tudominio.com', // debe pertenecer al dominio verificado en Mailgun
        'name'  => 'Recomendaciones',          // nombre base del remitente
    ],
    'site' => [
        'name'  => 'Mi Web',
        'url'   => 'https://tudominio.com',
        // Dominios permitidos para el enlace recomendado (evita phishing)
        'allowed_hosts' => ['tudominio.com', 'www.tudominio.com'],
    ],
    'limits' => [
        'per_ip_per_hour'   => 5,              // envios por IP y hora
        'max_comment'       => 20000,          // caracteres maximos del mensaje (HTML)
        'max_name'          => 60,
    ],
];
