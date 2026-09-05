<?php
/**
 * ============================================================================
 *  Comunicado — el aviso que aparece sobre la web.
 * ============================================================================
 *
 *  Una imagen, un texto y un botón que lleva a algún sitio o descarga un
 *  documento. Se publica y se retira desde el panel, y cada uno lleva su
 *  cuenta de cuántas veces se ha visto y cuántas se ha pulsado.
 *
 *  Al retirarlo NO se borra: se queda con sus cifras. Comparar el comunicado
 *  de la peregrinación con el de las inscripciones sólo se puede si los dos
 *  siguen ahí.
 */

declare(strict_types=1);

namespace Intranet\Models;

use Intranet\Core\Model;

final class Comunicado extends Model
{
    protected string $tabla = 'comunicados';

    protected array $ordenables = ['creado_en', 'nombre', 'vistas', 'clics', 'id'];

    /**
     * El comunicado que toca mostrar en una página, o null.
     *
     * ── Las tres condiciones ────────────────────────────────────────────
     *   · activo
     *   · no caducado —la caducidad se comprueba AQUÍ, contra la hora de la
     *     base, y no con una tarea programada: un hosting compartido no
     *     siempre deja programar nada, y un aviso que sigue saliendo tres días
     *     después de su fecha es peor que uno que no salió—
     *   · y que esta página esté entre las suyas
     *
     * Si hay varios que cumplen, se muestra el más reciente. Dos modales a la
     * vez se taparían el uno al otro.
     *
     * @return array<string, mixed>|null
     */
    public function vigentePara(string $pagina): ?array
    {
        $filas = $this->bd()->filas(
            'SELECT * FROM comunicados
              WHERE activo = 1
                AND (expira_en IS NULL OR expira_en > NOW())
              ORDER BY creado_en DESC, id DESC'
        );

        foreach ($filas as $c) {
            if ($this->apareceEn($c, $pagina)) {
                return $c;
            }
        }

        return null;
    }

    /**
     * ¿Este comunicado se muestra en esta página?
     *
     * `paginas` vacío significa «en todas». Una lista significa sólo esas.
     * Y una entrada que empiece por «!» excluye: `!voluntariado` es «en todas
     * menos en voluntariado», que es el caso que hacía falta —un aviso que
     * invita a inscribirse no debe salir encima del formulario de
     * inscripción—.
     *
     * @param array<string, mixed> $comunicado
     */
    public function apareceEn(array $comunicado, string $pagina): bool
    {
        $lista = array_filter(array_map('trim', explode(',', (string) $comunicado['paginas'])));

        if ($lista === []) {
            return true;
        }

        $excluidas = [];
        $incluidas = [];

        foreach ($lista as $p) {
            if (str_starts_with($p, '!')) {
                $excluidas[] = substr($p, 1);
            } else {
                $incluidas[] = $p;
            }
        }

        if (in_array($pagina, $excluidas, true)) {
            return false;
        }

        // Si sólo se escribieron exclusiones, se entiende «en todas menos
        // ésas». Exigir además una lista de inclusión obligaría a enumerar las
        // diecinueve páginas para excluir una.
        return $incluidas === [] || in_array($pagina, $incluidas, true);
    }

    /**
     * Suma uno a un contador.
     *
     * `SET vistas = vistas + 1` y no leer-sumar-guardar: dos visitas
     * simultáneas leerían el mismo número y una de las dos se perdería. Con la
     * suma dentro del UPDATE, la cuenta la lleva la base y no se pierde
     * ninguna.
     *
     * El nombre de la columna viene de una lista cerrada: es lo único que no
     * puede viajar como parámetro en SQL, así que no puede salir de fuera.
     */
    public function sumar(int $id, string $contador): void
    {
        if (!in_array($contador, ['vistas', 'clics'], true)) {
            return;
        }

        $this->bd()->consultar(
            "UPDATE comunicados SET `{$contador}` = `{$contador}` + 1 WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Todos, para el panel: primero los que están saliendo, luego el resto.
     *
     * @return array<int, array<string, mixed>>
     */
    public function todos(): array
    {
        return $this->bd()->filas(
            'SELECT c.*, u.nombre AS autor,
                    (c.activo = 1 AND (c.expira_en IS NULL OR c.expira_en > NOW())) AS vigente
               FROM comunicados c
               LEFT JOIN usuarios u ON u.id = c.creado_por
              ORDER BY vigente DESC, c.creado_en DESC'
        );
    }

    /**
     * Los que tienen alguna cifra, para el escritorio.
     *
     * Se incluyen los retirados: ahí está la gracia del historial. Lo que se
     * deja fuera es el que nunca llegó a publicarse, porque un comunicado con
     * cero vistas y cero clics no dice nada.
     *
     * @return array<int, array<string, mixed>>
     */
    public function conCifras(): array
    {
        return $this->bd()->filas(
            'SELECT id, nombre, activo, expira_en, vistas, clics, boton_tipo, creado_en,
                    (activo = 1 AND (expira_en IS NULL OR expira_en > NOW())) AS vigente
               FROM comunicados
              WHERE vistas > 0 OR clics > 0
              ORDER BY vigente DESC, creado_en DESC'
        );
    }
}
