<?php
/**
 * ============================================================================
 *  Prueba de envío · diagnóstico de la API de correo
 * ============================================================================
 *
 *  Manda un correo de prueba y, antes, revisa lo que suele fallar. Se escribió
 *  después de perder un rato con un caso concreto: el registro decía que el
 *  mensaje había salido y a Gmail no llegaba nada, ni a la carpeta de spam.
 *  La causa era el remitente —un dominio cuyo DMARC está en «reject» y cuyo
 *  SPF no autoriza a este servidor—, y se habría visto en diez segundos con
 *  una comprobación como ésta.
 *
 *  ── Cómo se usa ──────────────────────────────────────────────────────────
 *
 *  Por consola, que es lo cómodo si tienes SSH:
 *
 *      php probar.php                      al destino de config.php
 *      php probar.php otro@ejemplo.com     a donde tú digas
 *
 *  Y para averiguar POR QUÉ un mensaje del formulario no pasa cuando la
 *  prueba sí —que es lo que pasó—, se puede imitar el mensaje real pieza a
 *  pieza. El filtro antispam de salida del hosting rechaza por el CONTENIDO,
 *  no por la configuración, así que hay que ir quitando partes hasta ver cuál
 *  es la que le molesta:
 *
 *      php probar.php micorreo@x.com formulario   el mensaje tal cual sale
 *      php probar.php micorreo@x.com sin-replyto  igual, sin Responder-a
 *      php probar.php micorreo@x.com sin-cuerpo   con el cuerpo de la prueba
 *
 *  El que pase es el que señala al culpable.
 *
 *  O desde el navegador, si no hay SSH. Pide el token, el mismo de config.php:
 *
 *      https://TU-CPANEL/externo/probar.php?token=EL-TOKEN
 *      https://TU-CPANEL/externo/probar.php?token=EL-TOKEN&para=otro@ejemplo.com
 *
 *  ── Qué NO prueba ────────────────────────────────────────────────────────
 *
 *  Que el correo LLEGUE. Nadie puede saberlo desde aquí: mail() sólo dice si
 *  el servidor se hizo cargo del mensaje, y el rechazo del destinatario ocurre
 *  después. Lo que sí hace es avisar de las dos razones por las que un correo
 *  aceptado acaba en la basura sin dejar rastro.
 *
 *  Cuando termine, mira en cPanel → Correo → Rastrear entrega: ahí está la
 *  verdad sobre cada mensaje.
 *
 *  ── Borradlo cuando termine ──────────────────────────────────────────────
 *
 *  Este archivo enseña el remitente, el destino y la configuración. No hace
 *  falta que viva en el servidor más allá de la puesta en marcha.
 */

declare(strict_types=1);

$porConsola = PHP_SAPI === 'cli';

/* ── Quién puede ejecutarlo ───────────────────────────────────────────────
   Por consola, quien tenga acceso al servidor. Por web hace falta el token:
   esto enseña configuración y manda correo, no puede quedar abierto. */
$config = @include __DIR__ . '/config.php';

if (!is_array($config)) {
    exit("No se pudo leer config.php. ¿Lo copiaste de config.ejemplo.php?\n");
}

if (!$porConsola) {
    header('Content-Type: text/plain; charset=utf-8');

    $dado = (string) ($_GET['token'] ?? '');

    if ($dado === '' || !hash_equals((string) ($config['token'] ?? ''), $dado)) {
        http_response_code(401);
        exit("Token no válido.\n");
    }
}

/** Una línea del informe. */
function linea(string $marca, string $texto): void
{
    echo sprintf("%-4s %s\n", $marca, $texto);
}

function titulo(string $texto): void
{
    echo "\n" . $texto . "\n" . str_repeat('─', mb_strlen($texto)) . "\n";
}

$problemas = 0;

echo "PRUEBA DE ENVÍO · " . date('d/m/Y H:i') . "\n";

/* ── 1 · La configuración ─────────────────────────────────────────────── */
titulo('1 · Configuración');

$token     = (string) ($config['token'] ?? '');
$remitente = (string) ($config['remitente'] ?? '');
$destinos  = array_values(array_filter((array) ($config['destino'] ?? [])));

if ($token === '' || str_starts_with($token, 'PON-AQUI')) {
    linea('MAL', 'El token sigue sin poner.');
    $problemas++;
} elseif (mb_strlen($token) < 24) {
    linea('OJO', 'El token tiene ' . mb_strlen($token) . ' caracteres. Conviene 40 o más.');
} else {
    linea('OK', 'Token puesto, ' . mb_strlen($token) . ' caracteres.');
}

if ($remitente === '' || !filter_var($remitente, FILTER_VALIDATE_EMAIL)) {
    linea('MAL', 'El remitente no es una dirección válida: «' . $remitente . '».');
    $problemas++;
} else {
    linea('OK', 'Remitente: ' . $remitente);
}

if ($destinos === []) {
    linea('MAL', 'No hay ningún destinatario configurado.');
    $problemas++;
} else {
    linea('OK', 'Destino: ' . implode(', ', $destinos));
}

/* ── 2 · El remitente y el DNS ────────────────────────────────────────── */
titulo('2 · ¿Puede este servidor firmar como el remitente?');

$dominio = $remitente !== '' ? (string) substr(strrchr($remitente, '@') ?: '', 1) : '';
$miIp    = (string) ($_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname() ?: 'localhost'));

linea('··', 'Dominio del remitente: ' . ($dominio ?: '(ninguno)'));
linea('··', 'IP de este servidor:   ' . $miIp);

if ($dominio !== '' && function_exists('dns_get_record')) {
    $spf = '';

    foreach ((array) @dns_get_record($dominio, DNS_TXT) as $r) {
        $t = (string) ($r['txt'] ?? '');

        if (stripos($t, 'v=spf1') === 0) {
            $spf = $t;
            break;
        }
    }

    if ($spf === '') {
        linea('OJO', 'Ese dominio no publica SPF. Muchos servidores lo exigen.');
    } else {
        linea('··', 'SPF: ' . $spf);

        /* No se resuelve el SPF entero —haría falta seguir los «include» uno a
           uno—, pero sí se mira lo evidente: que la IP de este servidor esté
           nombrada. Si no aparece y el dominio delega en terceros, es la
           señal de que el correo saldrá sin respaldo. */
        if (str_contains($spf, $miIp)) {
            linea('OK', 'La IP de este servidor aparece en el SPF.');
        } else {
            linea('OJO', 'La IP de este servidor NO aparece literalmente en el SPF. '
                       . 'Puede estar dentro de un «include»; si no lo está, el correo no pasará.');
        }
    }

    $dmarc = '';

    foreach ((array) @dns_get_record('_dmarc.' . $dominio, DNS_TXT) as $r) {
        $t = (string) ($r['txt'] ?? '');

        if (stripos($t, 'v=DMARC1') === 0) {
            $dmarc = $t;
            break;
        }
    }

    if ($dmarc === '') {
        linea('··', 'Sin DMARC publicado.');
    } else {
        linea('··', 'DMARC: ' . $dmarc);

        if (preg_match('/p\s*=\s*(reject|quarantine)/i', $dmarc, $m) === 1) {
            linea('MAL', 'Ese dominio tiene DMARC en «' . strtolower($m[1]) . '». Si este servidor '
                       . 'no está autorizado en su SPF, el correo se DESCARTA y no llega '
                       . 'ni a la carpeta de spam. Usa como remitente una dirección del '
                       . 'dominio de este cPanel.');
            $problemas++;
        }
    }
}

/* ── 3 · El envío ─────────────────────────────────────────────────────── */
titulo('3 · Envío de prueba');

$para = $porConsola
    ? (string) ($argv[1] ?? '')
    : (string) ($_GET['para'] ?? '');

if ($para !== '' && !filter_var($para, FILTER_VALIDATE_EMAIL)) {
    linea('MAL', 'El destino que pasaste no es una dirección válida.');
    exit(1);
}

$aQuien = $para !== '' ? [$para] : $destinos;

if ($aQuien === [] || $remitente === '') {
    linea('··', 'No se envía nada: falta configuración.');
    exit($problemas > 0 ? 1 : 0);
}

$marca  = bin2hex(random_bytes(4));
$asunto = 'Prueba de la API de correo · ' . $marca;
$cuerpo = "Si lees esto, el envío funciona.\n\n"
        . "Marca:      {$marca}\n"
        . "Remitente:  {$remitente}\n"
        . "Servidor:   " . (gethostname() ?: '?') . " ({$miIp})\n"
        . "Fecha:      " . date('c') . "\n\n"
        . "Este correo lo manda externo/probar.php. Bórralo del servidor\n"
        . "cuando termines la puesta en marcha.\n";

$cabeceras = implode("\r\n", [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: ' . mb_encode_mimeheader((string) ($config['remitente_nombre'] ?? 'Prueba'), 'UTF-8')
             . ' <' . $remitente . '>',
]);

linea('··', 'Enviando a ' . implode(', ', $aQuien) . '…');

$ok = @mail(implode(', ', $aQuien), mb_encode_mimeheader($asunto, 'UTF-8'), $cuerpo, $cabeceras, '-f' . $remitente);

if ($ok) {
    linea('OK', 'El servidor de correo ACEPTÓ el mensaje. Marca: ' . $marca);
    linea('··', 'Aceptado no es entregado. Búscalo en cPanel → Correo → Rastrear entrega,');
    linea('··', 'filtrando por esa marca, para ver si llegó o quién lo rechazó.');
} else {
    linea('MAL', 'mail() falló: el servidor ni siquiera se hizo cargo del mensaje.');
    $problemas++;
}

/* ── Resumen ──────────────────────────────────────────────────────────── */
titulo('Resumen');

if ($problemas === 0) {
    linea('OK', 'Nada que objetar por aquí. Si aun así no llega, la respuesta está');
    linea('··', 'en Rastrear entrega: dirá si lo rechazaron y por qué.');
} else {
    linea('MAL', $problemas . ' cosa(s) que arreglar, arriba.');
}

echo "\n";
exit($problemas > 0 ? 1 : 0);
