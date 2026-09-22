<?php
/**
 * ============================================================================
 *  API de correo del formulario de contacto
 * ============================================================================
 *
 *  Vive en el cPanel, no en el VPS. La razón es simple: en un VPS recién
 *  montado, mail() o no sale o llega a la carpeta de spam, porque la IP no
 *  tiene reputación ni el dominio registros que la respalden. El cPanel ya
 *  tiene todo eso resuelto, así que el VPS le pide a este archivo que envíe.
 *
 *  ── Cómo se usa ──────────────────────────────────────────────────────────
 *
 *  POST con cuerpo JSON y la cabecera del token:
 *
 *      POST /externo/enviar.php
 *      X-Token: <el token de config.php>
 *      Content-Type: application/json
 *
 *      {
 *        "asunto":  "Contacto web · Voluntariado",
 *        "cuerpo":  "Nombre: ...\nCorreo: ...\n\nMensaje...",
 *        "responder_a":        "quien@escribio.com",
 *        "responder_a_nombre": "Nombre de quien escribió"
 *      }
 *
 *  Responde siempre JSON:
 *
 *      { "ok": true }
 *      { "ok": false, "error": "motivo en una línea" }
 *
 *  ── Lo que NO acepta, a propósito ────────────────────────────────────────
 *
 *  El destinatario. Lo fija config.php. Si viajara en la petición, el token
 *  filtrado convertiría esto en una máquina de mandar correo a cualquier
 *  parte firmada con tu dominio.
 *
 *  Tampoco acepta HTML: el cuerpo se envía como texto plano. Un correo con
 *  HTML que viene de un formulario público es una puerta que no hace falta
 *  abrir, y el aviso se lee igual de bien en texto.
 *
 *  ── Instalación ──────────────────────────────────────────────────────────
 *
 *   1. Sube esta carpeta al cPanel, por ejemplo a public_html/externo/.
 *   2. Copia config.ejemplo.php a config.php y rellénalo.
 *   3. Comprueba que responde:
 *
 *        curl -i -X POST https://TU-CPANEL/externo/enviar.php \
 *             -H "X-Token: EL-TOKEN" -H "Content-Type: application/json" \
 *             -d '{"asunto":"Prueba","cuerpo":"Funciona."}'
 *
 *      Debe devolver {"ok":true} y llegarte el correo.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
/* Esto no lo consume un navegador, lo consume el servidor del sitio. Sin
   permiso de origen cruzado, una página ajena no puede llamarlo desde el
   navegador de nadie aunque conociera el token. */
header('Access-Control-Allow-Origin: null');

/** Responde y termina. El código HTTP importa: el sitio lo mira. */
function responder(int $codigo, array $cuerpo): never
{
    http_response_code($codigo);
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Anota una línea en el registro. Nunca interrumpe el envío. */
function anotar(string $archivo, string $texto): void
{
    @file_put_contents(
        $archivo,
        sprintf("[%s] %s  %s\n", date('c'), $_SERVER['REMOTE_ADDR'] ?? '-', $texto),
        FILE_APPEND | LOCK_EX
    );
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responder(405, ['ok' => false, 'error' => 'Sólo se admite POST.']);
}

$config = @include __DIR__ . '/config.php';

if (!is_array($config) || ($config['token'] ?? '') === '' || str_starts_with((string) $config['token'], 'PON-AQUI')) {
    responder(500, ['ok' => false, 'error' => 'La API no está configurada.']);
}

$registro = (string) ($config['registro'] ?? __DIR__ . '/registro.log');

/* ── El token ─────────────────────────────────────────────────────────────
   hash_equals y no «===»: comparar cadenas con == devuelve antes cuando los
   primeros caracteres no coinciden, y midiendo esos tiempos se puede llegar a
   adivinar el token carácter a carácter. hash_equals tarda lo mismo siempre. */
$token = (string) ($_SERVER['HTTP_X_TOKEN'] ?? '');

if ($token === '' || !hash_equals((string) $config['token'], $token)) {
    anotar($registro, 'RECHAZADO token incorrecto');
    /* 401 y no 403: el sitio distingue «no me dejaron» de «falló el envío». */
    responder(401, ['ok' => false, 'error' => 'Token no válido.']);
}

/* ── El origen ────────────────────────────────────────────────────────────
   Una comprobación más, no la principal: una cabecera se falsea. Sirve para
   que una llamada despistada desde otro sitio no pase sin dejar rastro. */
$origenes = (array) ($config['origenes'] ?? []);
$origen   = (string) ($_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '');

if ($origenes !== [] && $origen !== '') {
    $vale = false;

    foreach ($origenes as $permitido) {
        if (str_starts_with($origen, (string) $permitido)) {
            $vale = true;
            break;
        }
    }

    if (!$vale) {
        anotar($registro, 'RECHAZADO origen ' . $origen);
        responder(403, ['ok' => false, 'error' => 'Origen no admitido.']);
    }
}

/* ── El tope por IP ───────────────────────────────────────────────────────
   Un contador por hora en un archivo. No es infalible —detrás de Cloudflare
   muchos visitantes comparten IP—, por eso el tope es holgado: frena a un
   robot insistente sin estorbar a nadie. */
$tope = (int) ($config['tope_por_hora'] ?? 10);

if ($tope > 0) {
    $ip      = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $marca   = sys_get_temp_dir() . '/api-correo-' . md5($ip . date('YmdH')) . '.cnt';
    $cuantos = (int) @file_get_contents($marca);

    if ($cuantos >= $tope) {
        anotar($registro, 'RECHAZADO tope por hora');
        responder(429, ['ok' => false, 'error' => 'Demasiados envíos seguidos. Inténtalo más tarde.']);
    }

    @file_put_contents($marca, (string) ($cuantos + 1), LOCK_EX);
}

/* ── El cuerpo ────────────────────────────────────────────────────────────
   Se lee de php://input y no de $_POST porque llega como JSON. El tope de
   tamaño evita que alguien intente atragantar al servidor con un megabyte. */
$crudo = (string) file_get_contents('php://input', false, null, 0, 64 * 1024);
$datos = json_decode($crudo, true);

if (!is_array($datos)) {
    responder(400, ['ok' => false, 'error' => 'El cuerpo no es JSON válido.']);
}

$asunto = trim((string) ($datos['asunto'] ?? ''));
$cuerpo = trim((string) ($datos['cuerpo'] ?? ''));

if ($asunto === '' || $cuerpo === '') {
    responder(400, ['ok' => false, 'error' => 'Faltan el asunto o el cuerpo.']);
}

/* ── Inyección de cabeceras ───────────────────────────────────────────────
   Un salto de línea dentro del asunto o del Responder-a permite añadir
   cabeceras propias —un Bcc, por ejemplo— y convertir esto en un relé de
   spam. Se cortan los saltos ANTES de construir nada. */
$sinSaltos = static fn (string $v): string => trim((string) preg_replace('/[\r\n]+/', ' ', $v));

$asunto = mb_substr($sinSaltos($asunto), 0, 200);
$cuerpo = mb_substr($cuerpo, 0, 20000);

/* ── El pie del aviso ─────────────────────────────────────────────────────
   Un correo de 171 bytes con una direccion ajena dentro y cuatro etiquetas
   con dos puntos es, literalmente, la forma de un volcado de formulario: lo
   que buscan las reglas que aqui puntuaron. Este cierre lo convierte en un
   aviso con remite, que es lo que es.

   Va aqui y no en la plantilla del panel a proposito: la plantilla es para el
   contenido, y esto tiene que estar SIEMPRE, lo escriba quien lo escriba. */
$pie = "

-- 
"
     . "Aviso automatico del formulario de contacto de leon14enperu.com,
"
     . "el sitio oficial de la Visita Apostolica de Su Santidad el Papa
"
     . "Leon XIV al Peru, del 11 al 16 de noviembre de 2026.
"
     . "Conferencia Episcopal Peruana.
";

if (!str_contains($cuerpo, 'leon14enperu.com')) {
    $cuerpo .= $pie;
} else {
    /* Ya lo nombra la plantilla: se añade sólo la firma, sin repetir. */
    $cuerpo .= "

-- 
Aviso automatico del formulario de contacto."
             . "
Conferencia Episcopal Peruana.
";
}

$responderA       = $sinSaltos((string) ($datos['responder_a'] ?? ''));
$responderANombre = mb_substr($sinSaltos((string) ($datos['responder_a_nombre'] ?? '')), 0, 120);

if ($responderA !== '' && filter_var($responderA, FILTER_VALIDATE_EMAIL) === false) {
    $responderA = '';
}

/* ── El envío ─────────────────────────────────────────────────────────────
   El remitente es del dominio del cPanel; la dirección de quien escribió va
   en Responder-a, que es donde de verdad sirve: se le contesta pulsando
   «Responder» y el correo sale bien firmado. */
$remitente = (string) ($config['remitente'] ?? ('no-responder@' . ($_SERVER['HTTP_HOST'] ?? 'localhost')));
$nombre    = (string) ($config['remitente_nombre'] ?? 'Formulario web');

/* ── Las cabeceras, con lo que espera un correo transaccional ─────────────
   Cada una de estas responde a algo concreto del informe de rechazo que dio
   el antispam de salida:

    · Message-ID con el dominio DEL REMITENTE. Exim lo generaba con el del
      servidor —caroni.tepuyserver.net— mientras el From decía
      iglobalgroup.net.pe. Esa discordancia la miran varias reglas, y aquí
      salia como HEADER_MISMATCH.

    · Date propia y en formato RFC. La ponía Exim, pero un correo legítimo la
      trae desde el programa que lo compone.

    · X-Mailer. Su ausencia la anotaba MISSING_XM_UA. Vale 0 puntos, pero es
      de lo que mira el clasificador bayesiano para decidir si algo parece
      escrito por un programa serio o por un guion de spam.

    · Auto-Submitted, del RFC 3834. Dice «esto es un aviso generado por un
      sistema, no correo masivo ni una respuesta». Es la etiqueta correcta
      para lo que esto es, y evita que nadie le conteste con un automático.

    · quoted-printable en vez de 8bit. Con 8bit el texto con tildes viaja en
      crudo y depende de que todos los saltos del camino lo admitan; si uno
      no, el mensaje se degrada o se rechaza. quoted-printable lo entiende
      todo el mundo desde hace treinta años. */
$idMensaje = bin2hex(random_bytes(12)) . '.' . time()
           . '@' . (substr(strrchr($remitente, '@') ?: '@localhost', 1));

$cabeceras = [
    'MIME-Version: 1.0',
    'Date: ' . date('r'),
    'Message-ID: <' . $idMensaje . '>',
    'From: ' . mb_encode_mimeheader($nombre, 'UTF-8') . ' <' . $remitente . '>',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: quoted-printable',
    'Auto-Submitted: auto-generated',
    'X-Mailer: Formulario de contacto de leon14enperu.com',
];

/* El Responder-a sólo si config.php lo permite. Ver ahí el porqué: un
   Responder-a de un correo gratuito sobre un From de otro dominio es lo que
   más puntúa en los antispam de salida, y aquí costó que no llegara ni un
   mensaje. La dirección del visitante va igualmente en el cuerpo. */
if ($responderA !== '' && ($config['responder_al_visitante'] ?? false) === true) {
    $cabeceras[] = $responderANombre !== ''
        ? 'Reply-To: ' . mb_encode_mimeheader($responderANombre, 'UTF-8') . ' <' . $responderA . '>'
        : 'Reply-To: ' . $responderA;
}

$destinos = array_filter((array) ($config['destino'] ?? []));

if ($destinos === []) {
    responder(500, ['ok' => false, 'error' => 'No hay destinatario configurado.']);
}

/* El «-f» le dice al servidor de correo quién es el remitente de sobre. Sin
   él, muchos cPanel firman con el usuario del sistema y el correo acaba en
   spam. */
$enviado = @mail(
    implode(', ', $destinos),
    mb_encode_mimeheader($asunto, 'UTF-8'),
    $cuerpo,
    implode("\r\n", $cabeceras),
    '-f' . $remitente
);

$rastro = sprintf('de %s para %s · %s', $remitente, implode(',', $destinos), $asunto);

if (!$enviado) {
    anotar($registro, 'FALLO al entregar al servidor de correo · ' . $rastro);
    responder(502, ['ok' => false, 'error' => 'El servidor de correo no aceptó el mensaje.']);
}

/* ── «ACEPTADO», no «ENVIADO» ─────────────────────────────────────────────
   mail() devuelve cierto en cuanto el Exim del servidor se hace cargo del
   mensaje. Lo que pase después —que el destinatario lo rechace— ocurre más
   tarde y aquí no se sabe.

   La distincion no es pedante: paso de verdad. El remitente era del dominio
   del sitio, cuyo DMARC esta en «p=reject» con alineacion estricta y cuyo SPF
   no autoriza a este servidor. Gmail lo descartaba sin dejarlo ni en spam, y
   este registro decia «ENVIADO». Que ponga «ACEPTADO» y con qué remitente
   ahorra esa media hora de buscar donde no era.

   Para saber si llegó de verdad: cPanel -> Correo -> Rastrear entrega. */
anotar($registro, 'ACEPTADO por el servidor de correo · ' . $rastro);
responder(200, ['ok' => true]);
