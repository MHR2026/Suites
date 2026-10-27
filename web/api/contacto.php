<?php
// Recibe el formulario de contacto de la web y lo envía por email.
// Se ejecuta en el hosting de IONOS (PHP). Sin JavaScript: redirige a la página
// de gracias o vuelve al formulario con ?error=1.
//
// Envío: si existe el fichero de configuración SMTP (ver config.sample.php) se envía
// a través del servidor de correo de IONOS con usuario y contraseña; si no, con mail().
// Diagnóstico: abrir /api/contacto.php?check=1 en el navegador.
// Los errores se guardan en api/errores.log (no accesible desde la web).

const TO = 'info@sonsampoli.com';
const FROM_DEFAULT = 'info@sonsampoli.com';
const SITE_DOMAIN = 'suites.sonsampoli.com';
define('LANGS', explode('|', 'es|en|de|fr|it'));
const LOG = __DIR__ . '/errores.log';

// La configuración se busca, por orden: fuera de la carpeta de la web (no se borra al
// subir una versión nueva), en la raíz de la web y junto a este fichero.
function config(): ?array {
    $files = [dirname(__DIR__, 2) . '/mhr-form-config.php', dirname(__DIR__) . '/mhr-form-config.php', __DIR__ . '/config.php'];
    foreach ($files as $file) {
        if (is_file($file)) {
            $cfg = require $file;
            if (is_array($cfg)) return $cfg;
        }
    }
    return null;
}

// Remitente: el buzón con el que se entra en el SMTP (IONOS solo permite enviar como ese buzón).
function from(?array $cfg): string {
    return $cfg['user'] ?? FROM_DEFAULT;
}

function logError(string $msg): void {
    @file_put_contents(LOG, date('c') . ' ' . $msg . "\n", FILE_APPEND | LOCK_EX);
}

// Cliente SMTP mínimo con STARTTLS y AUTH LOGIN.
function smtp(array $cfg, string $to, string $data, ?string &$error = null): bool {
    $host = $cfg['host'] ?? 'smtp.ionos.es';
    $port = (int)($cfg['port'] ?? 587);
    $verify = $cfg['verify_tls'] ?? true;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => $verify, 'verify_peer_name' => $verify, 'peer_name' => $host]]);
    $fp = @stream_socket_client(($port === 465 ? 'ssl' : 'tcp') . "://$host:$port", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) { $error = "conexión a $host:$port: $errstr"; return false; }
    stream_set_timeout($fp, 15);
    $read = function () use ($fp): string {
        $out = '';
        while (($line = fgets($fp, 515)) !== false) {
            $out .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') break;
        }
        return $out;
    };
    // $label: qué se envía, para el mensaje de error (nunca el contenido de usuario o contraseña)
    $cmd = function (?string $line, string $expect, string $label = '') use ($fp, $read, &$error): bool {
        if ($line !== null) fwrite($fp, $line . "\r\n");
        $reply = $read();
        if (strncmp($reply, $expect, strlen($expect)) !== 0) {
            $error = trim(($label !== '' ? "$label: " : '') . $reply);
            return false;
        }
        return true;
    };
    $ok = $cmd(null, '220', 'saludo') && $cmd('EHLO ' . SITE_DOMAIN, '250', 'EHLO');
    if ($ok && $port !== 465) {
        $ok = $cmd('STARTTLS', '220', 'STARTTLS')
            && stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)
            && $cmd('EHLO ' . SITE_DOMAIN, '250', 'EHLO');
    }
    $ok = $ok && $cmd('AUTH LOGIN', '334', 'AUTH') && $cmd(base64_encode($cfg['user'] ?? ''), '334', 'usuario');
    if ($ok && !$cmd(base64_encode($cfg['password'] ?? ''), '235')) {
        $error = 'usuario o contraseña SMTP incorrectos: ' . $error;
        $ok = false;
    }
    if ($data !== '') {
        $ok = $ok && $cmd('MAIL FROM:<' . from($cfg) . '>', '250', 'remitente')
            && $cmd("RCPT TO:<$to>", '250', 'destinatario')
            && $cmd('DATA', '354', 'DATA')
            && $cmd(preg_replace('/^\./m', '..', $data) . "\r\n.", '250', 'mensaje');
    }
    @fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return $ok;
}

// ---- Diagnóstico -------------------------------------------------------------
if (isset($_GET['check'])) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex');
    $cfg = config();
    echo "PHP: " . PHP_VERSION . "\n";
    echo "mail(): " . (function_exists('mail') ? 'disponible' : 'NO disponible') . "\n";
    echo "mbstring: " . (function_exists('mb_substr') ? 'sí' : 'NO') . "\n";
    echo "openssl: " . (extension_loaded('openssl') ? 'sí' : 'NO') . "\n";
    echo "Configuración SMTP: " . ($cfg ? 'encontrada' : 'no encontrada (se usa mail())') . "\n";
    if ($cfg) {
        echo "Prueba de conexión y login SMTP: " . (smtp($cfg, TO, '', $err) ? 'OK' : "ERROR - $err") . "\n";
    }
    echo "Registro de errores: " . (is_file(LOG) ? 'hay errores guardados en api/errores.log' : 'sin errores') . "\n";
    exit;
}

// ---- Formulario --------------------------------------------------------------
$lang = in_array($_POST['lang'] ?? '', LANGS, true) ? $_POST['lang'] : LANGS[0];
$back = "/$lang/contacto/";

function fail(string $back, string $reason): void {
    logError($reason);
    header('Location: ' . $back . '?error=1#formulario', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $back, true, 303);
    exit;
}

// Campo trampa para bots: si viene relleno, fingimos éxito y no enviamos nada.
if (!empty($_POST['website'])) {
    header("Location: /$lang/contacto/gracias/", true, 303);
    exit;
}

// Una sola línea, sin saltos (evita inyección de cabeceras) y con longitud limitada.
function field(string $key, int $max = 200): string {
    $value = trim((string)($_POST[$key] ?? ''));
    $value = preg_replace('/[\r\n\t]+/', ' ', $value);
    return mb_substr($value, 0, $max);
}

$name = field('name');
$email = field('email');
$phone = field('phone', 50);
$dates = field('dates', 100);
$message = mb_substr(trim((string)($_POST['message'] ?? '')), 0, 5000);

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '' || empty($_POST['consent'])) {
    fail($back, 'datos incompletos o email no válido');
}

$body = implode("\r\n", [
    'Nuevo mensaje desde la web ' . SITE_DOMAIN,
    '',
    "Nombre: $name",
    "Email: $email",
    "Teléfono: $phone",
    "Fechas: $dates",
    "Idioma: $lang",
    '',
    'Mensaje:',
    str_replace(["\r\n", "\r"], "\n", $message),
]);
$body = str_replace("\n", "\r\n", str_replace("\r\n", "\n", $body));

$subject = '=?UTF-8?B?' . base64_encode("Contacto web " . SITE_DOMAIN . ": $name") . '?=';
$cfg = config();
$headers = [
    'From: Web ' . SITE_DOMAIN . ' <' . from($cfg) . '>',
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
];

if ($cfg) {
    $data = implode("\r\n", array_merge(
        ['Date: ' . date('r'), 'To: ' . TO, "Subject: $subject", 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . SITE_DOMAIN . '>'],
        $headers
    )) . "\r\n\r\n" . $body;
    if (!smtp($cfg, TO, $data, $err)) fail($back, "SMTP: $err");
} elseif (!mail(TO, $subject, $body, implode("\r\n", $headers), '-f' . from($cfg))) {
    fail($back, 'mail() devolvió error (probar con la configuración SMTP)');
}

header("Location: /$lang/contacto/gracias/", true, 303);
