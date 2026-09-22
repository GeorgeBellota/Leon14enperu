<?php
/**
 * ============================================================================
 *  Contacto — recibe el formulario y se lo pasa a la API de correo.
 * ============================================================================
 *
 *  El envío no sale de este servidor. En el VPS, mail() o no sale o cae en la
 *  carpeta de spam: la IP no tiene reputación y el dominio no la respalda
 *  desde aquí. Quien envía es un pequeño archivo en el cPanel —externo/
 *  enviar.php—, que sí tiene todo eso resuelto. Este objeto valida lo que
 *  llega, compone el asunto y el cuerpo con las plantillas del panel y le pide
 *  a esa API que lo mande.
 *
 *  ── Qué NO hace, y conviene saberlo ──────────────────────────────────────
 *
 *  No guarda el mensaje en la base. Si la API no responde, el mensaje se
 *  pierde y a la persona se le dice la verdad —con los correos directos
 *  delante— en vez de darle las gracias por algo que no llegó. Guardarlos
 *  pediría una tabla nueva; queda anotado como lo siguiente que haría falta.
 *
 *  ── Por qué el destinatario no viaja en la petición ──────────────────────
 *
 *  Lo fija el config.php del cPanel. Si lo mandara este lado, el token
 *  filtrado convertiría la API en una máquina de enviar correo a cualquier
 *  parte firmada con el dominio.
 */

declare(strict_types=1);

namespace Intranet\Publico;

use Intranet\Models\Ajuste;
use Throwable;

final class Contacto
{
    /** Lo que tarda como mucho en rendirse la llamada a la API, en segundos. */
    private const ESPERA = 12;

    /** @var array<string, string> */
    private array $errores = [];

    private string $fallo = '';

    public function __construct(private Sitio $sitio)
    {
    }

    /** @return array<string, string> */
    public function errores(): array
    {
        return $this->errores;
    }

    /** El motivo por el que no salió, para enseñárselo a quien escribió. */
    public function fallo(): string
    {
        return $this->fallo;
    }

    /** ¿Está configurada la API? Sin esto el formulario ni se pinta activo. */
    public function configurado(): bool
    {
        $c = $this->ajustes();

        return $c['url'] !== '' && $c['token'] !== '';
    }

    /**
     * Procesa el envío. Devuelve true sólo si la API confirmó el correo.
     *
     * @param array<string, mixed> $campos lo que llegó por POST
     */
    public function enviar(array $campos): bool
    {
        $nombre  = $this->limpia($campos['nombre']  ?? '', 120);
        $correo  = $this->limpia($campos['correo']  ?? '', 160);
        $motivo  = $this->limpia($campos['motivo']  ?? '', 120);
        $mensaje = trim((string) ($campos['mensaje'] ?? ''));
        $mensaje = mb_substr($mensaje, 0, 5000);

        if ($nombre === '')                                              { $this->errores['nombre']  = 'Escribe tu nombre.'; }
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) { $this->errores['correo'] = 'Revisa tu correo electrónico.'; }
        if ($motivo === '')                                              { $this->errores['motivo']  = 'Elige un motivo.'; }
        if (mb_strlen($mensaje) < 10)                                    { $this->errores['mensaje'] = 'Cuéntanos un poco más.'; }
        if (empty($campos['privacidad']))                                { $this->errores['privacidad'] = 'Hay que aceptar la política de privacidad.'; }

        /* La trampa: un campo que ningún humano ve y casi todo robot rellena.
           Si viene con algo, se descarta el envío SIN decirlo, para no
           enseñarle al robot cómo esquivarla. Se devuelve true: que crea que
           salió. */
        if (trim((string) ($campos['sitio-web'] ?? '')) !== '') {
            return true;
        }

        /* ── El testigo ───────────────────────────────────────────────────
           Ata el envío a esta página: sin él, cualquier sitio podría publicar
           un formulario que dispara correos desde el nuestro. Se comprueba
           DESPUÉS de la trampa para no contarle a un robot cuál de las dos
           barreras le paró.

           Si la clave de la aplicación no está configurada, Token no puede
           firmar nada y se deja pasar: preferible un formulario que funciona
           sin esta barrera a uno que no envía nunca y nadie sabe por qué.
           Queda anotado en el registro. */
        $clave = (string) $this->sitio->config('app.clave', '');

        if ($clave !== '') {
            try {
                if (!(new Token($clave))->valido((string) ($campos['_testigo'] ?? ''))) {
                    $this->fallo = 'El formulario caducó. Vuelve a cargar la página e inténtalo otra vez.';

                    return false;
                }
            } catch (Throwable $e) {
                error_log('[contacto] no se pudo comprobar el testigo: ' . $e->getMessage());
            }
        } else {
            error_log('[contacto] sin app.clave: el envío va sin testigo');
        }

        if ($this->errores !== []) {
            return false;
        }

        $c = $this->ajustes();

        if ($c['url'] === '' || $c['token'] === '') {
            $this->fallo = 'El formulario todavía no está conectado.';

            return false;
        }

        $valores = [
            '{nombre}'  => $nombre,
            '{correo}'  => $correo,
            '{motivo}'  => $motivo,
            '{mensaje}' => $mensaje,
            '{fecha}'   => date('d/m/Y H:i'),
        ];

        $asunto = strtr($c['asunto'], $valores);
        $cuerpo = strtr($c['plantilla'], $valores);

        return $this->llamar($c, $asunto, $cuerpo, $correo, $nombre);
    }

    /**
     * La llamada a la API. Devuelve true si respondió {"ok":true}.
     *
     * @param array<string, string> $c
     */
    private function llamar(array $c, string $asunto, string $cuerpo, string $correo, string $nombre): bool
    {
        $json = json_encode([
            'asunto'             => $asunto,
            'cuerpo'             => $cuerpo,
            'responder_a'        => $correo,
            'responder_a_nombre' => $nombre,
        ], JSON_UNESCAPED_UNICODE);

        try {
            $ch = curl_init($c['url']);

            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $json,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => self::ESPERA,
                CURLOPT_CONNECTTIMEOUT => 6,
                /* Sin esto, un certificado cualquiera valdría y el token
                   viajaría a quien se pusiera en medio. */
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-Token: ' . $c['token'],
                    'Origin: ' . rtrim((string) $this->sitio->config('url.sitio', ''), '/'),
                ],
            ]);

            $respuesta = curl_exec($ch);
            $codigo    = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $errorCurl = curl_error($ch);

            curl_close($ch);
        } catch (Throwable $e) {
            error_log('[contacto] no se pudo llamar a la API: ' . $e->getMessage());
            $this->fallo = 'No pudimos enviar tu mensaje en este momento.';

            return false;
        }

        if ($respuesta === false || $codigo === 0) {
            error_log('[contacto] la API no respondió: ' . $errorCurl);
            $this->fallo = 'No pudimos enviar tu mensaje en este momento.';

            return false;
        }

        $datos = json_decode((string) $respuesta, true);

        if ($codigo === 200 && is_array($datos) && ($datos['ok'] ?? false) === true) {
            return true;
        }

        /* El motivo exacto va al log, no a la pantalla: a quien escribe no le
           sirve «Token no válido» y a quien husmea sí. */
        error_log('[contacto] la API rechazó el envío · HTTP ' . $codigo . ' · ' . (string) $respuesta);

        $this->fallo = $codigo === 429
            ? 'Has enviado varios mensajes seguidos. Espera un momento e inténtalo otra vez.'
            : 'No pudimos enviar tu mensaje en este momento.';

        return false;
    }

    /**
     * Lo que hay configurado en el panel.
     *
     * @return array<string, string>
     */
    private function ajustes(): array
    {
        $porDefecto = [
            'url'       => '',
            'token'     => '',
            'asunto'    => 'Contacto web · {motivo}',
            'plantilla' => "Nuevo mensaje desde leon14enperu.com\n\n"
                         . "Nombre:  {nombre}\n"
                         . "Correo:  {correo}\n"
                         . "Motivo:  {motivo}\n"
                         . "Fecha:   {fecha}\n\n"
                         . "Mensaje:\n{mensaje}\n",
        ];

        try {
            $a = new Ajuste($this->sitio->contenedor());

            return [
                'url'       => trim((string) $a->leer('contacto.api_url', '')),
                'token'     => trim((string) $a->leer('contacto.api_token', '')),
                'asunto'    => trim((string) $a->leer('contacto.asunto', '')) ?: $porDefecto['asunto'],
                'plantilla' => trim((string) $a->leer('contacto.plantilla', '')) ?: $porDefecto['plantilla'],
            ];
        } catch (Throwable $e) {
            error_log('[contacto] no se pudieron leer los ajustes: ' . $e->getMessage());

            return $porDefecto;
        }
    }

    /** Una línea de texto: sin saltos, recortada y de largo acotado. */
    private function limpia(mixed $valor, int $tope): string
    {
        $texto = trim((string) preg_replace('/[\r\n]+/', ' ', (string) $valor));

        return mb_substr($texto, 0, $tope);
    }
}
