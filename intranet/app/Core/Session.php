<?php
/**
 * Session — sesión de PHP con las tuercas apretadas.
 *
 *  · Cookie HttpOnly (JavaScript no la lee) y SameSite=Lax (no viaja en
 *    peticiones cruzadas, que es media defensa contra CSRF por sí sola).
 *  · Caducidad por inactividad: hay datos personales en pantalla y un panel
 *    abierto en un equipo compartido es una fuga esperando a ocurrir.
 *  · Rotación periódica del identificador y rotación obligatoria al iniciar
 *    sesión, contra la fijación de sesión.
 *  · Nombre de cookie propio: con el nombre por defecto (PHPSESSID), el sitio
 *    público y el panel se pisarían la sesión en el mismo host.
 */

declare(strict_types=1);

namespace Intranet\Core;

final class Session
{
    private const CLAVE_ULTIMO   = '_ultimo_uso';
    private const CLAVE_ROTACION = '_rotado_en';
    private const CLAVE_HUELLA   = '_huella';

    private bool $iniciada = false;

    public function __construct(
        private array $config,
        private Request $peticion
    ) {
        $this->iniciar();
    }

    private function iniciar(): void
    {
        if ($this->iniciada || session_status() === PHP_SESSION_ACTIVE) {
            $this->iniciada = true;

            return;
        }

        session_name($this->config['nombre'] ?? 'LX14PANEL');

        /* Un identificador de sesión sólo vale si lo ha creado este servidor.
           Sin use_strict_mode, PHP acepta cualquier id que llegue en la cookie
           y crea la sesión con él: basta con plantarle a alguien un id
           conocido —un enlace, un subdominio, una red compartida— y esperar a
           que inicie sesión para entrar con esa misma sesión ya autenticada.
           Es fijación de sesión, y PHP la trae desactivada por defecto. */
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '5');

        session_set_cookie_params([
            'lifetime' => 0,                                   // se cierra al cerrar el navegador
            'path'     => ($this->peticion->base() ?: '') . '/',
            'domain'   => '',
            'secure'   => $this->exigirHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        $this->iniciada = true;

        $this->comprobarInactividad();
        $this->comprobarHuella();
        $this->rotarSiToca();

        $_SESSION[self::CLAVE_ULTIMO] = time();
    }

    /**
     * ¿La cookie de sesión debe marcarse como Secure?
     *
     * Antes esto salía de un ajuste de configuración con valor por defecto
     * `false` y un comentario que decía «ponerlo a true en producción». Ese
     * tipo de interruptor se queda sin pulsar: si nadie lo cambia, la cookie
     * de sesión del panel viaja también por HTTP, y ahí cualquiera en la misma
     * red puede leerla y entrar como quien la tenía.
     *
     * Ahora se detecta. Si la petición llegó por HTTPS —directa o a través de
     * Cloudflare, que lo indica en X-Forwarded-Proto—, la cookie se marca
     * Secure sola. El ajuste sigue existiendo pero sólo puede forzarla a
     * activarse, nunca a desactivarse.
     */
    private function exigirHttps(): bool
    {
        if ((bool) ($this->config['solo_https'] ?? false)) {
            return true;
        }

        if (($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
            return true;
        }

        return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    /** Cierra la sesión tras N minutos sin actividad. */
    private function comprobarInactividad(): void
    {
        $limite = (int) ($this->config['inactividad'] ?? 45) * 60;
        $ultimo = $_SESSION[self::CLAVE_ULTIMO] ?? null;

        if ($ultimo !== null && (time() - (int) $ultimo) > $limite) {
            $this->destruir();
            session_start();
            $this->guardar('aviso', 'La sesión se cerró por inactividad.');
        }
    }

    /**
     * Ata la sesión al navegador que la abrió. Si la cookie aparece en otro
     * agente, se descarta: es la señal barata de un robo de cookie.
     * No se incluye la IP: en móvil cambia sola y echaría a gente legítima.
     */
    private function comprobarHuella(): void
    {
        $huella = hash('sha256', $this->peticion->agente());

        if (!isset($_SESSION[self::CLAVE_HUELLA])) {
            $_SESSION[self::CLAVE_HUELLA] = $huella;

            return;
        }

        if (!hash_equals((string) $_SESSION[self::CLAVE_HUELLA], $huella)) {
            $this->destruir();
            session_start();
            $_SESSION[self::CLAVE_HUELLA] = $huella;
        }
    }

    private function rotarSiToca(): void
    {
        $cada   = (int) ($this->config['rotacion'] ?? 30) * 60;
        $ultima = $_SESSION[self::CLAVE_ROTACION] ?? null;

        if ($ultima === null) {
            $_SESSION[self::CLAVE_ROTACION] = time();

            return;
        }

        if ((time() - (int) $ultima) > $cada) {
            $this->rotar();
        }
    }

    /** Identificador nuevo conservando los datos. Obligatorio tras el login. */
    public function rotar(): void
    {
        session_regenerate_id(true);
        $_SESSION[self::CLAVE_ROTACION] = time();
    }

    public function guardar(string $clave, mixed $valor): void
    {
        $_SESSION[$clave] = $valor;
    }

    public function leer(string $clave, mixed $porDefecto = null): mixed
    {
        return $_SESSION[$clave] ?? $porDefecto;
    }

    public function tiene(string $clave): bool
    {
        return isset($_SESSION[$clave]);
    }

    public function olvidar(string $clave): void
    {
        unset($_SESSION[$clave]);
    }

    /** Lee y borra. Para los mensajes de «guardado correctamente». */
    public function sacar(string $clave, mixed $porDefecto = null): mixed
    {
        $valor = $_SESSION[$clave] ?? $porDefecto;
        unset($_SESSION[$clave]);

        return $valor;
    }

    /** Mensaje de una sola lectura: se muestra en la página siguiente. */
    public function destello(string $tipo, string $mensaje): void
    {
        $_SESSION['_destellos'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    /** @return array<int, array{tipo: string, mensaje: string}> */
    public function destellos(): array
    {
        $lista = $_SESSION['_destellos'] ?? [];
        unset($_SESSION['_destellos']);

        return $lista;
    }

    public function destruir(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }
}
