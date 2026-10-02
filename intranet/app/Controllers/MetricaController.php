<?php
/**
 * MetricaController — el tablero de métricas, detrás de su propio portón.
 *
 *  ── Por qué una contraseña aparte ────────────────────────────────────────
 *
 *  Porque no es un permiso más. Un permiso lo reparte el panel entre roles, y
 *  cualquiera con acceso a la pantalla de usuarios puede dárselo a quien
 *  quiera. Esto se abre sólo con una contraseña que no está en la base: quien
 *  la sepa entra y quien no, no, por mucho rol que tenga.
 *
 *  Dicho claro, porque conviene no engañarse: es un secreto compartido, no un
 *  segundo factor. Protege de que alguien del equipo entre sin que se la den;
 *  no protege de alguien que ya tenga la sesión abierta en el ordenador.
 *
 *  ── El hash, no la contraseña ────────────────────────────────────────────
 *
 *  En la configuración vive el `password_hash`. La contraseña no se escribe
 *  en ningún archivo del proyecto: `config.php` está en el repositorio, y lo
 *  que se escriba ahí queda también en los commits viejos aunque después se
 *  cambie.
 *
 *  Y hay una razón más, concreta de esta contraseña: empieza por «$». En PHP,
 *  "$sistemas2026" entre comillas DOBLES es una variable que no existe, o sea
 *  la cadena vacía, y el portón se abriría pulsando Enter sin escribir nada.
 *  Con `password_verify` contra un hash eso no puede pasar.
 *
 *  ── Vacío es cerrado ─────────────────────────────────────────────────────
 *
 *  Si no hay hash configurado, el módulo no existe: ni enseña el formulario
 *  ni se puede abrir. Una puerta que acepta la cadena vacía es peor que no
 *  tener puerta.
 */

declare(strict_types=1);

namespace Intranet\Controllers;

use Intranet\Core\Auditoria;
use Intranet\Core\Controller;
use Intranet\Core\Metricas;
use Intranet\Core\Request;

final class MetricaController extends Controller
{
    private const LLAVE = 'metricas_abierto_hasta';

    /** El portón: pide la contraseña. */
    public function porton(Request $peticion): void
    {
        if (!$this->configurado()) {
            $this->conError('El tablero de métricas no está configurado.', '/');
        }

        if ($this->abierto()) {
            $this->redirigir('/metricas/tablero');
        }

        $this->ver('metricas/porton', [
            'titulo'   => 'Métricas',
            'bloqueo'  => $this->minutosDeBloqueo(),
        ]);
    }

    /** Comprueba la contraseña y abre durante un rato. */
    public function abrir(Request $peticion): void
    {
        $this->exigirCsrf($peticion);

        if (!$this->configurado()) {
            $this->conError('El tablero de métricas no está configurado.', '/');
        }

        /* El candado va ANTES de comprobar nada: si no, cada intento fallido
           cuesta un `password_verify`, que es lento a propósito, y mil
           intentos seguidos tumbarían el panel aunque ninguno acertara. */
        if (($espera = $this->minutosDeBloqueo()) > 0) {
            $this->conError(
                "Demasiados intentos. Espera {$espera} minutos.",
                '/metricas'
            );
        }

        $hash = (string) $this->c->config('metricas.clave_hash', '');

        if (!password_verify((string) $peticion->post('clave', ''), $hash)) {
            $this->anotarFallo();

            Auditoria::registrar($this->c, 'rechazar', 'metricas', 0, [
                'motivo' => 'contraseña incorrecta',
            ]);

            $this->conError('Contraseña incorrecta.', '/metricas');
        }

        $this->limpiarFallos();

        $minutos = max(1, (int) $this->c->config('metricas.minutos', 30));
        $this->c->sesion()->guardar(self::LLAVE, time() + $minutos * 60);

        Auditoria::registrar($this->c, 'abrir', 'metricas', 0, []);

        $this->redirigir('/metricas/tablero');
    }

    /** El tablero. */
    public function tablero(Request $peticion): void
    {
        if (!$this->configurado() || !$this->abierto()) {
            $this->redirigir('/metricas');
        }

        /* dirname(__DIR__, 3) es la raíz del proyecto, que es como la
           resuelven los demás controladores que tocan archivos. */
        $metricas = new Metricas(dirname(__DIR__, 3) . '/intranet/almacen/metricas');
        $dias     = $metricas->dias();

        /* Por defecto, los últimos siete días: menos no deja ver una
           tendencia y más tarda en sumarse sin aportar nada que no se vea
           igual. */
        $hasta = $this->fecha($peticion->get('hasta'), $dias[0] ?? gmdate('Y-m-d'));
        $desde = $this->fecha(
            $peticion->get('desde'),
            gmdate('Y-m-d', strtotime($hasta . ' -6 days'))
        );

        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $this->ver('metricas/tablero', [
            'titulo'       => 'Métricas',
            'desde'        => $desde,
            'hasta'        => $hasta,
            'dias'         => $dias,
            /* «datos» NO: es un nombre reservado del renderizador —su
               extract() va con EXTR_SKIP y lo descarta— y la vista recibiría
               el array interno en su lugar. */
            'resumen'      => $metricas->resumen($desde, $hasta),
            'permanencia'  => $metricas->permanencia($desde, $hasta),
            'abiertoHasta' => (int) $this->c->sesion()->leer(self::LLAVE, 0),
        ]);
    }

    /** Cierra el portón a mano, sin esperar a que caduque. */
    public function cerrar(Request $peticion): void
    {
        $this->exigirCsrf($peticion);
        $this->c->sesion()->olvidar(self::LLAVE);
        $this->conExito('Tablero cerrado.', '/');
    }

    // ── Lo de dentro ─────────────────────────────────────────────────────

    private function configurado(): bool
    {
        return (string) $this->c->config('metricas.clave_hash', '') !== '';
    }

    private function abierto(): bool
    {
        return (int) $this->c->sesion()->leer(self::LLAVE, 0) > time();
    }

    /** Una fecha del formulario, o la de por defecto si no vale. */
    private function fecha(mixed $valor, string $porDefecto): string
    {
        $valor = (string) ($valor ?? '');

        return preg_match('~^\d{4}-\d{2}-\d{2}$~', $valor) === 1 ? $valor : $porDefecto;
    }

    /**
     * Quién llama a la puerta, para contarle los intentos.
     *
     * Se usa la misma tabla que el login —y sus mismos límites— en vez de
     * inventar otra: el bloqueo ya está probado y no hace falta mantener dos.
     */
    private function quien(): string
    {
        return 'metricas:' . (int) $this->c->auth()->id();
    }

    /** Minutos que quedan de castigo, 0 si no hay. */
    private function minutosDeBloqueo(): int
    {
        $max     = (int) $this->c->config('seguridad.intentos_max', 5);
        $minutos = (int) $this->c->config('seguridad.bloqueo_minutos', 15);

        $fallos = (int) $this->c->bd()->valor(
            'SELECT COUNT(*) FROM intentos_login
              WHERE correo = :c AND exito = 0
                AND creado_en > DATE_SUB(NOW(), INTERVAL :m MINUTE)',
            ['c' => $this->quien(), 'm' => $minutos]
        );

        return $fallos >= $max ? $minutos : 0;
    }

    private function anotarFallo(): void
    {
        $this->c->bd()->insertar('intentos_login', [
            'correo' => $this->quien(),
            'ip'     => @inet_pton($this->c->peticion()->ip()) ?: null,
            'exito'  => 0,
        ]);
    }

    private function limpiarFallos(): void
    {
        $this->c->bd()->eliminar(
            'intentos_login',
            'correo = :c AND exito = 0',
            ['c' => $this->quien()]
        );
    }
}
