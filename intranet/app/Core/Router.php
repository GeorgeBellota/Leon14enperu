<?php
/**
 * ============================================================================
 *  Router — enrutador y controlador frontal.
 * ============================================================================
 *
 *  Registra rutas, las compara con la petición y llama al controlador. Admite
 *  parámetros con llaves y una restricción opcional por tipo:
 *
 *      $r->get('/voluntarios/{id:\d+}', [VoluntarioController::class, 'ver'])
 *        ->permiso('voluntarios.ver');
 *
 *  Cada ruta puede exigir sesión iniciada (->auth()) y un permiso concreto
 *  (->permiso('clave')). La comprobación ocurre AQUÍ, antes de instanciar el
 *  controlador: si el control de acceso se deja dentro de cada método, tarde o
 *  temprano hay un método que se olvida de llamarlo.
 *
 *  Ese es también el motivo de que las rutas sean explícitas y no se derive el
 *  controlador de la URL: con enrutado automático, subir una clase nueva a
 *  app/Controllers/ la publica sin que nadie lo haya decidido.
 */

declare(strict_types=1);

namespace Intranet\Core;

use RuntimeException;
use Throwable;

final class Router
{
    /** @var array<int, array<string, mixed>> */
    private array $rutas = [];

    /** Índice de la última ruta registrada, para los métodos encadenables. */
    private int $ultima = -1;

    private string $prefijoGrupo = '';

    /** @var array{auth: bool, permiso: ?string} */
    private array $opcionesGrupo = ['auth' => false, 'permiso' => null];

    public function __construct(
        private Contenedor $contenedor
    ) {
    }

    public function get(string $patron, array|callable $accion): self
    {
        return $this->agregar('GET', $patron, $accion);
    }

    public function post(string $patron, array|callable $accion): self
    {
        return $this->agregar('POST', $patron, $accion);
    }

    public function put(string $patron, array|callable $accion): self
    {
        return $this->agregar('PUT', $patron, $accion);
    }

    public function delete(string $patron, array|callable $accion): self
    {
        return $this->agregar('DELETE', $patron, $accion);
    }

    /** Atajo para el par ver-formulario / recibir-formulario. */
    public function formulario(string $patron, array $accionGet, array $accionPost): self
    {
        $this->agregar('GET', $patron, $accionGet);
        $primera = $this->ultima;
        $this->agregar('POST', $patron, $accionPost);

        // Devuelve la segunda, pero ->auth() y ->permiso() deben caer en las
        // dos: se replican al vuelo.
        $this->rutas[$primera]['gemela'] = $this->ultima;

        return $this;
    }

    /**
     * Agrupa rutas bajo un prefijo y unas opciones comunes.
     *
     *     $r->grupo('/voluntarios', ['auth' => true, 'permiso' => 'voluntarios.ver'],
     *         function (Router $r) { ... });
     */
    public function grupo(string $prefijo, array $opciones, callable $definir): void
    {
        $prefijoPrevio  = $this->prefijoGrupo;
        $opcionesPrevias = $this->opcionesGrupo;

        $this->prefijoGrupo  = $prefijoPrevio . rtrim($prefijo, '/');
        $this->opcionesGrupo = array_replace($this->opcionesGrupo, $opciones);

        $definir($this);

        $this->prefijoGrupo  = $prefijoPrevio;
        $this->opcionesGrupo = $opcionesPrevias;
    }

    /** Exige sesión iniciada en la última ruta registrada. */
    public function auth(bool $exigir = true): self
    {
        $this->marcarUltima('auth', $exigir);

        return $this;
    }

    /** Exige un permiso concreto. Implica auth. */
    public function permiso(string $clave): self
    {
        $this->marcarUltima('auth', true);
        $this->marcarUltima('permiso', $clave);

        return $this;
    }

    /** Nombre para poder generar la URL sin repetir el patrón por ahí suelto. */
    public function nombre(string $nombre): self
    {
        $this->marcarUltima('nombre', $nombre);

        return $this;
    }

    private function marcarUltima(string $clave, mixed $valor): void
    {
        if ($this->ultima < 0) {
            return;
        }

        $this->rutas[$this->ultima][$clave] = $valor;

        if (isset($this->rutas[$this->ultima]['gemela'])) {
            $this->rutas[$this->rutas[$this->ultima]['gemela']][$clave] = $valor;
        }
    }

    private function agregar(string $metodo, string $patron, array|callable $accion): self
    {
        $patron = $this->prefijoGrupo . $patron;
        $patron = '/' . trim($patron, '/');

        $this->rutas[] = [
            'metodo'  => $metodo,
            'patron'  => $patron,
            'regex'   => $this->compilar($patron),
            'accion'  => $accion,
            'auth'    => $this->opcionesGrupo['auth'],
            'permiso' => $this->opcionesGrupo['permiso'],
            'nombre'  => null,
        ];

        $this->ultima = array_key_last($this->rutas);

        return $this;
    }

    /**
     * «/voluntarios/{id:\d+}» → «#^/voluntarios/(?P<id>\d+)$#»
     * Sin restricción, un parámetro acepta cualquier cosa menos la barra.
     */
    private function compilar(string $patron): string
    {
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/',
            static function (array $m): string {
                $nombre      = $m[1];
                $restriccion = $m[2] ?? '[^/]+';

                return "(?P<{$nombre}>{$restriccion})";
            },
            $patron
        );

        return '#^' . $regex . '$#u';
    }

    /**
     * Punto de entrada. Empareja, comprueba acceso y ejecuta.
     */
    public function despachar(Request $peticion): void
    {
        $ruta      = $peticion->ruta();
        $metodo    = $peticion->metodo();
        $hayPatron = false;

        foreach ($this->rutas as $definicion) {
            if (!preg_match($definicion['regex'], $ruta, $coincidencias)) {
                continue;
            }

            // La ruta existe pero con otro verbo. Es un 405, no un 404: la
            // diferencia importa cuando algo falla en producción.
            $hayPatron = true;
            if ($definicion['metodo'] !== $metodo) {
                continue;
            }

            $parametros = [];
            foreach ($coincidencias as $clave => $valor) {
                if (!is_int($clave)) {
                    $parametros[$clave] = $valor;
                }
            }

            $this->ejecutar($definicion, $parametros, $peticion);

            return;
        }

        if ($hayPatron) {
            $this->error(405, 'Método no permitido', $peticion);

            return;
        }

        $this->error(404, 'Página no encontrada', $peticion);
    }

    /**
     * @param array<string, mixed> $definicion
     * @param array<string, string> $parametros
     */
    private function ejecutar(array $definicion, array $parametros, Request $peticion): void
    {
        $auth = $this->contenedor->auth();

        // ── Puerta 1: sesión ────────────────────────────────────────────
        if ($definicion['auth'] && !$auth->autenticado()) {
            $this->contenedor->sesion()->guardar('destino', $peticion->ruta());
            Response::redirigir($this->contenedor->url('/login'));

            return;
        }

        // ── Puerta 2: contraseña caducada ───────────────────────────────
        // Quien todavía tiene la contraseña que le pusieron al crear la cuenta
        // no pasa de la pantalla de cambio, salvo que sea esa misma pantalla.
        if ($definicion['auth']
            && $auth->autenticado()
            && $auth->debeCambiarClave()
            && !str_starts_with($peticion->ruta(), '/clave')
        ) {
            Response::redirigir($this->contenedor->url('/clave'));

            return;
        }

        // ── Puerta 3: permiso ───────────────────────────────────────────
        if ($definicion['permiso'] !== null && !$auth->puede($definicion['permiso'])) {
            $this->error(403, 'No tienes permiso para entrar en esta sección.', $peticion);

            return;
        }

        try {
            $accion = $definicion['accion'];

            if (is_callable($accion)) {
                $accion($peticion, $parametros);

                return;
            }

            [$clase, $metodo] = $accion;

            if (!class_exists($clase)) {
                throw new RuntimeException("No existe el controlador {$clase}.");
            }

            $controlador = new $clase($this->contenedor);

            if (!method_exists($controlador, $metodo)) {
                throw new RuntimeException("El controlador {$clase} no tiene el método {$metodo}().");
            }

            $controlador->$metodo($peticion, $parametros);
        } catch (Throwable $e) {
            $this->fallo($e, $peticion);
        }
    }

    private function error(int $codigo, string $mensaje, Request $peticion): void
    {
        http_response_code($codigo);

        if ($peticion->esAjax()) {
            Response::json(['error' => $mensaje], $codigo);

            return;
        }

        $vista = new View($this->contenedor);
        echo $vista->renderizar('errores/generico', [
            'codigo'  => $codigo,
            'mensaje' => $mensaje,
        ], 'limpio');
    }

    /**
     * Un error no controlado. En desarrollo se enseña; en producción se
     * registra y el usuario ve una página neutra, porque una traza de PHP
     * revela rutas del servidor y a veces credenciales.
     */
    private function fallo(Throwable $e, Request $peticion): void
    {
        error_log(sprintf(
            "[%s] %s en %s:%d\n%s",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));

        http_response_code(500);

        if ($this->contenedor->config('app.entorno') === 'desarrollo') {
            header('Content-Type: text/plain; charset=utf-8');
            echo "ERROR 500\n\n";
            echo get_class($e) . ': ' . $e->getMessage() . "\n";
            echo $e->getFile() . ':' . $e->getLine() . "\n\n";
            echo $e->getTraceAsString() . "\n";

            return;
        }

        $this->error(500, 'Ha ocurrido un error. Ya ha quedado registrado.', $peticion);
    }
}
