<?php
/**
 * ============================================================================
 *  API de correo · CONFIGURACIÓN
 * ============================================================================
 *
 *  Este archivo va en el cPanel, junto a enviar.php, y NO se sube al
 *  repositorio: lleva el token. Hay un config.ejemplo.php al lado con la misma
 *  forma y sin secretos, que es el que sí viaja en git.
 *
 *  ── Antes de subirlo ─────────────────────────────────────────────────────
 *
 *   1. Genera un token largo y pégalo abajo. En el servidor:
 *
 *          php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
 *
 *      Ese MISMO token se pega luego en la intranet, en
 *      Configuración → Correo de contacto.
 *
 *   2. Pon en «destino» el buzón que debe recibir los mensajes.
 *   3. Pon en «remitente» una dirección REAL del dominio del cPanel. No vale
 *      poner la del visitante: los servidores rechazan o marcan como spam un
 *      correo que dice venir de un dominio que no es el que lo envía. La
 *      dirección del visitante va en Responder-a, que es donde sirve.
 *
 *  ── Por qué el destino se decide AQUÍ y no lo manda la web ───────────────
 *
 *  Porque si el destinatario viajara en la petición, cualquiera que consiga
 *  el token tendría una máquina de enviar correo a donde quisiera, firmada
 *  con tu dominio. Fijándolo aquí, lo peor que puede pasar con el token
 *  filtrado es que te llenen TU buzón: molesto, pero tuyo y acotado.
 */

declare(strict_types=1);

return [
    /* El token compartido. Mínimo 32 caracteres. Cámbialo por el tuyo. */
    'token' => 'PON-AQUI-EL-TOKEN-QUE-GENERES',

    /* A quién llegan los mensajes. Puede ser uno o varios. */
    'destino' => [
        'contacto@leon14enperu.com',
    ],

    /* Quién los firma. Tiene que existir en el dominio del cPanel. */
    'remitente'        => 'no-responder@leon14enperu.com',
    'remitente_nombre' => 'León XIV en el Perú',

    /* Sólo se atienden peticiones que digan venir de aquí. Es una barrera
       más, no la principal: la cabecera se puede falsear. La de verdad es el
       token. */
    'origenes' => [
        'https://leon14enperu.com',
        'https://www.leon14enperu.com',
    ],

    /* Tope por IP y hora. Un formulario de contacto legítimo no manda diez
       mensajes seguidos; un robot con el token, sí. */
    'tope_por_hora' => 10,

    /* Dónde se anotan los envíos y los rechazos. Ruta absoluta o relativa a
       esta carpeta. Conviene que NO esté dentro de public_html. */
    'registro' => __DIR__ . '/registro.log',
];
