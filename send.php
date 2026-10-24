<?php
require __DIR__ . '/lib/helpers.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
$c = cfg();
$flash = function (string $type, string $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: index.php');
    exit;
};

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Honeypot
if (!empty($_POST['website'])) {
    $flash('err', 'Envio bloqueado.');
}

// CSRF
$csrf = (string)($_POST['csrf'] ?? '');
if (!csrf_check($csrf)) {
    $flash('err', 'Sesion invalida. Recarga la pagina.');
}

// Rate limit
$ip = client_ip();
if (!rate_limit_ok($ip)) {
    $flash('err', 'Has enviado demasiadas recomendaciones. Intentalo mas tarde.');
}

// Campos
$name    = trim((string)($_POST['name'] ?? ''));
$from    = trim((string)($_POST['from'] ?? ''));
$to      = trim((string)($_POST['to'] ?? ''));
$subject = trim((string)($_POST['subject'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

$maxName = $c['limits']['max_name'];
$maxCmt  = $c['limits']['max_comment'];

if ($name === '' || mb_strlen($name, 'UTF-8') > $maxName) {
    $flash('err', 'Nombre invalido.');
}
if (!valid_email($from)) {
    $flash('err', 'Tu email no es valido.');
}
if (!valid_email($to)) {
    $flash('err', 'El email de tu amigo no es valido.');
}
if ($from === $to) {
    $flash('err', 'Los dos emails no pueden ser iguales.');
}
if ($subject === '' || mb_strlen($subject, 'UTF-8') > 200) {
    $flash('err', 'Asunto invalido (maximo 200 caracteres).');
}
if ($message === '' || mb_strlen($message, 'UTF-8') > $maxCmt) {
    $flash('err', 'Mensaje invalido (maximo ' . $maxCmt . ' caracteres).');
}

// Sanitizar para cabeceras
$safeName    = clean_header_value($name);
$safeSubject = clean_header_value($subject);

require __DIR__ . '/vendor/PHPMailer/Exception.php';
require __DIR__ . '/vendor/PHPMailer/PHPMailer.php';
require __DIR__ . '/vendor/PHPMailer/SMTP.php';

$mail = new PHPMailer(true);
try {
    $smtp = $c['smtp'];
    $mail->isSMTP();
    $mail->Host       = $smtp['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp['username'];
    $mail->Password   = $smtp['password'];
    $mail->SMTPSecure = $smtp['encryption']; // 'tls' o 'ssl'
    $mail->Port       = $smtp['port'];
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 10;

    // ADVERTENCIA: se intenta enviar con From = email del visitante (spoofing).
    // Mailgun (y cualquier proveedor SMTP serio) RECHAZA esto porque el dominio
    // del visitante no esta verificado en tu cuenta. Resultado esperado:
    // "No se pudo enviar el correo." (error de autorizacion SMTP).
    // Solo funcionaria con un servidor SMTP propio que no valide remitentes.
    $safeFrom = clean_header_value($from);
    $mail->setFrom($from, $safeName);
    $mail->addAddress($to);

    $mail->Subject = $safeSubject;

    // El cuerpo se envia tal cual (HTML permitido: imagenes, enlaces, etc.)
    $mail->isHTML(true);
    $mail->Body    = '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"></head><body>' . $message . '</body></html>';
    $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $message));

    $mail->send();
    $flash('ok', 'Correo enviado correctamente.');
} catch (Exception $e) {
    error_log('Mail error: ' . $mail->ErrorInfo);
    $flash('err', 'No se pudo enviar el correo. Intentalo mas tarde.');
}
