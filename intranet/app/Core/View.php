<?php
/**
 * View — plantillas en PHP plano.
 *
 * Sin motor de plantillas: PHP ya lo es. Lo que sí hace falta es que escapar
 * la salida sea más cómodo que no escaparla, y de ahí el helper `e()`, que se
 * usa en TODA interpolación de las vistas. Un nombre de voluntario con un
 * `<script>` dentro no puede llegar crudo al HTML del panel.
 *
 * `renderizar()` devuelve el HTML en lugar de imprimirlo, para que el
 * controlador decida el código de respuesta y las cabeceras.
 */

declare(strict_types=1);

namespace Intranet\Core;

use RuntimeException;

final class View
{
    private string $rutaVistas;

    /** @var array<string, mixed> */
    private array $compartidas = [];

    public function __construct(private Contenedor $c)
    {
        $this->rutaVistas = dirname(__DIR__) . '/Views';
    }

    /** Variables disponibles en todas las vistas (usuario, menú activo…). */
    public function compartir(string $clave, mixed $valor): void
    {
        $this->compartidas[$clave] = $valor;
    }

    /**
     * @param array<string, mixed> $datos
     * @param string|null $plantilla  Nombre del layout, o null para no envolver.
     */
    public function renderizar(string $vista, array $datos = [], ?string $plantilla = 'panel'): string
    {
        $contenido = $this->capturar("{$vista}.php", $datos);

        if ($plantilla === null) {
            return $contenido;
        }

        return $this->capturar("layouts/{$plantilla}.php", $datos + ['contenido' => $contenido]);
    }

    /** @param array<string, mixed> $datos */
    public function parcial(string $vista, array $datos = []): string
    {
        return $this->capturar("parciales/{$vista}.php", $datos);
    }

    /** @param array<string, mixed> $datos */
    private function capturar(string $relativa, array $datos): string
    {
        $archivo = $this->rutaVistas . '/' . $relativa;
        $real    = realpath($archivo);

        // El nombre de la vista nunca debe poder salir de Views/.
        if ($real === false || !str_starts_with($real, realpath($this->rutaVistas) ?: '')) {
            throw new RuntimeException("No existe la vista «{$relativa}».");
        }

        // Nombres reservados del propio renderizador: que un dato llamado
        // «vista» o «datos» no pise las variables internas.
        $vistaDatos = array_merge($this->compartidas, $datos);
        unset($vistaDatos['archivo'], $vistaDatos['real'], $vistaDatos['relativa']);

        $c    = $this->c;
        $auth = $this->c->auth();
        $csrf = new Csrf($this->c->sesion());

        extract($vistaDatos, EXTR_SKIP);

        ob_start();

        try {
            require $real;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }

    // ── Helpers usados desde las vistas ──────────────────────────────────

    /** Escape para HTML. La función más importante del archivo. */
    public static function e(mixed $valor): string
    {
        return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Fecha en el formato que se lee en el panel. */
    public static function fecha(?string $fecha, bool $conHora = false): string
    {
        if ($fecha === null || $fecha === '' || str_starts_with($fecha, '0000')) {
            return '—';
        }

        $marca = strtotime($fecha);
        if ($marca === false) {
            return '—';
        }

        return date($conHora ? 'd/m/Y H:i' : 'd/m/Y', $marca);
    }

    /** Recorta sin partir palabras, para las celdas de las tablas. */
    public static function recortar(?string $texto, int $largo = 80): string
    {
        $texto = trim((string) $texto);

        if (mb_strlen($texto) <= $largo) {
            return $texto;
        }

        return mb_substr($texto, 0, $largo) . '…';
    }
}
