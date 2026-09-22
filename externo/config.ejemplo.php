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
 *   3. Pon en «remitente» una dirección del DOMINIO DE ESTE CPANEL. Ni la del
 *      visitante ni una de leon14enperu.com.
 *
 *      Esto no es una recomendación: es lo que hace que el correo llegue.
 *      leon14enperu.com tiene el correo en Google Workspace y publica
 *
 *          SPF    v=spf1 include:_spf.google.com include:zohomail.com ~all
 *          DMARC  v=DMARC1; p=reject; sp=reject; adkim=s; aspf=s
 *
 *      Es decir: sólo Google y Zoho pueden firmar como ese dominio, la
 *      alineación es estricta y lo que no cuadre se RECHAZA. Un correo que
 *      salga de este cPanel diciendo venir de leon14enperu.com lo descarta
 *      Gmail sin dejarlo siquiera en spam. Ya pasó: el registro decía
 *      «ACEPTADO» y no llegaba nada.
 *
 *      Con el remitente del dominio del cPanel, su propio SPF autoriza a esta
 *      IP y el mensaje entra. El nombre visible puede seguir diciendo «León
 *      XIV en el Perú», y la dirección de quien escribe va en Responder-a,
 *      que es donde sirve: se le contesta con un clic.
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

    /* Quién los firma. DEL DOMINIO DE ESTE CPANEL, no de leon14enperu.com:
       ese dominio tiene DMARC en «reject» y rechazaría el correo. */
    'remitente'        => 'no-responder@EL-DOMINIO-DE-TU-CPANEL.com',
    'remitente_nombre' => 'León XIV en el Perú',

    /* Sólo se atienden peticiones que digan venir de aquí. Es una barrera
       más, no la principal: la cabecera se puede falsear. La de verdad es el
       token. */
    'origenes' => [
        'https://leon14enperu.com',
        'https://www.leon14enperu.com',
    ],

    /* ── ¿Se pone la direccion del visitante en Responder-a? ──────────────
       Con «true» respondes al mensaje con un clic. Comodo, pero es la señal
       que mas puntua en los antispam de salida: un Responder-a de Gmail sobre
       un From de otro dominio es el patron exacto de un formulario
       secuestrado para reenviar spam. Aqui costo un rechazo con 21,78 puntos
       —el umbral suele estar en 5— y ni un mensaje entregado.

       Con «false» el correo sale limpio y la direccion de quien escribe viaja
       en el cuerpo, donde la pone la plantilla del panel con {correo}. Se
       responde copiandola: un paso mas, pero los mensajes llegan.

       Si el hosting mete el dominio en su lista blanca, se puede volver a
       poner en «true». */
    'responder_al_visitante' => false,

    /* Tope por IP y hora. Un formulario de contacto legítimo no manda diez
       mensajes seguidos; un robot con el token, sí. */
    'tope_por_hora' => 10,

    /* Dónde se anotan los envíos y los rechazos. Ruta absoluta o relativa a
       esta carpeta. Conviene que NO esté dentro de public_html. */
    'registro' => __DIR__ . '/registro.log',
];
