<?php
/**
 * ============================================================================
 *  Noticia — las entradas de /noticias/ y los vídeos de esa página.
 * ============================================================================
 *
 *  ── Por qué esto no son bloques ─────────────────────────────────────────
 *
 *  Lo eran, y de ahí salían las quejas del cliente. Un bloque da una
 *  fotografía, un titular y un campo de texto; no da fecha ordenable, ni
 *  cuerpo con imágenes dentro, ni SEO propio.
 *
 *  Lo decisivo es la FECHA. Antes vivía en el JSON de `datos` como texto
 *  libre —«Agosto de 2026», «5 de agosto de 2026»— y no se puede ordenar un
 *  texto así. Lo que se veía en la web era el orden manual de las flechas, y
 *  por eso el cliente escribió que «deben mostrarse en orden cronológico
 *  descendente». Aquí es una DATE y el orden sale solo.
 *
 *  ── La destacada ────────────────────────────────────────────────────────
 *
 *  El diseño tiene un hueco grande a la izquierda y una columna de tres a la
 *  derecha. La destacada es la que ocupa ese hueco. Sólo puede haber una: al
 *  marcar otra, se desmarca la anterior en la misma transacción, porque dos
 *  destacadas no caben y el fallo no se vería hasta publicar.
 */

declare(strict_types=1);

namespace Intranet\Models;

use Intranet\Cms\Slug;
use Intranet\Core\Model;

final class Noticia extends Model
{
    protected string $tabla = 'noticias';

    /** Cuántas caben en la página pública antes de paginar. */
    public const POR_PAGINA = 7;

    /** Cuántos vídeos se enseñan antes del «cargar más». */
    public const VIDEOS_VISIBLES = 6;

    // ── Para la web ────────────────────────────────────────────────────

    /**
     * Las publicadas, de la más reciente a la más antigua, paginadas.
     *
     * El desempate por `id` no es un adorno: dos noticias del mismo día —hoy
     * hay dos del 1 de agosto— saldrían en orden distinto en cada consulta
     * sin él, y la paginación repetiría una y se saltaría otra.
     *
     * @return array{filas: list<array<string,mixed>>, total:int, pagina:int, paginas:int, porPagina:int}
     */
    public function publicadas(int $pagina = 1, int $porPagina = self::POR_PAGINA): array
    {
        $select = 'SELECT n.*,
                          m.`ruta`      AS `imagen_ruta`,
                          m.`variantes` AS `imagen_variantes`,
                          m.`ancho`     AS `imagen_ancho`,
                          m.`alto`      AS `imagen_alto`,
                          m.`alt`       AS `imagen_alt`
                     FROM `noticias` n
                     LEFT JOIN `medios` m ON m.`id` = n.`imagen_id`
                    WHERE n.`estado` = :estado
                    ORDER BY n.`fecha` DESC, n.`id` DESC';

        return $this->paginar(
            $select,
            'SELECT COUNT(*) FROM `noticias` WHERE `estado` = :estado',
            ['estado' => 'publicada'],
            $pagina,
            $porPagina
        );
    }

    /** Una noticia por su dirección. Sólo publicada: un borrador no se ve. */
    public function porSlug(string $slug): ?array
    {
        return $this->bd()->fila(
            'SELECT n.*,
                    m.`ruta`      AS `imagen_ruta`,
                    m.`variantes` AS `imagen_variantes`,
                    m.`ancho`     AS `imagen_ancho`,
                    m.`alto`      AS `imagen_alto`,
                    m.`alt`       AS `imagen_alt`,
                    g.`ruta`      AS `og_ruta`
               FROM `noticias` n
               LEFT JOIN `medios` m ON m.`id` = n.`imagen_id`
               LEFT JOIN `medios` g ON g.`id` = n.`og_imagen_id`
              WHERE n.`slug` = :slug AND n.`estado` = :estado
              LIMIT 1',
            ['slug' => $slug, 'estado' => 'publicada']
        );
    }

    /**
     * El extracto del listado.
     *
     * Si no se escribió uno, se saca del cuerpo: se quitan las etiquetas y se
     * corta por la última palabra entera, no a mitad de una. Es lo que pidió
     * el cliente —«únicamente un extracto»— sin obligar a escribirlo dos
     * veces en cada noticia.
     */
    public static function extracto(array $noticia, int $largo = 180): string
    {
        $propio = trim((string) ($noticia['resumen'] ?? ''));

        if ($propio !== '') {
            return $propio;
        }

        $texto = trim(html_entity_decode(
            strip_tags((string) ($noticia['cuerpo'] ?? '')),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        ));

        $texto = (string) preg_replace('/\s+/u', ' ', $texto);

        if ($texto === '' || mb_strlen($texto) <= $largo) {
            return $texto;
        }

        $corte  = mb_substr($texto, 0, $largo);
        $ultimo = mb_strrpos($corte, ' ');

        return rtrim($ultimo !== false ? mb_substr($corte, 0, $ultimo) : $corte, " ,;:.") . '…';
    }

    // ── Para el panel ──────────────────────────────────────────────────

    /**
     * El listado del gestor: todas, borradores incluidos.
     *
     * @return array{filas: list<array<string,mixed>>, total:int, pagina:int, paginas:int, porPagina:int}
     */
    public function listar(string $estado = '', int $pagina = 1, int $porPagina = 20): array
    {
        $condiciones = [];
        $params      = [];

        if ($estado === 'borrador' || $estado === 'publicada') {
            $condiciones[]    = 'n.`estado` = :estado';
            $params['estado'] = $estado;
        }

        $where = 'WHERE ' . $this->donde($condiciones);

        return $this->paginar(
            "SELECT n.*, m.`ruta` AS `imagen_ruta`
               FROM `noticias` n
               LEFT JOIN `medios` m ON m.`id` = n.`imagen_id`
               {$where}
              ORDER BY n.`fecha` DESC, n.`id` DESC",
            "SELECT COUNT(*) FROM `noticias` n {$where}",
            $params,
            $pagina,
            $porPagina
        );
    }

    /** Una noticia por su id, publicada o no. */
    public function porId(int $id): ?array
    {
        return $this->bd()->fila(
            'SELECT n.*, m.`ruta` AS `imagen_ruta`
               FROM `noticias` n
               LEFT JOIN `medios` m ON m.`id` = n.`imagen_id`
              WHERE n.`id` = :id LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * Un slug libre, derivado del titular.
     *
     * `$exceptoId` existe para editar: al guardar una noticia sin cambiarle
     * el titular, su propio slug no debe contar como ocupado y convertirse en
     * «mi-titular-2» cada vez que se pulsa Guardar.
     */
    public function slugLibre(string $titular, ?string $escrito = null, ?int $exceptoId = null): string
    {
        $base = trim((string) $escrito) !== ''
            ? Slug::normalizar((string) $escrito)
            : Slug::normalizar($titular);

        if ($base === '' || $base === 'pieza') {
            $base = 'noticia';
        }

        $slug = $base;
        $n    = 2;

        while ($this->slugOcupado($slug, $exceptoId)) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    private function slugOcupado(string $slug, ?int $exceptoId): bool
    {
        $sql    = 'SELECT `id` FROM `noticias` WHERE `slug` = :slug';
        $params = ['slug' => $slug];

        if ($exceptoId !== null) {
            $sql           .= ' AND `id` <> :yo';
            $params['yo']   = $exceptoId;
        }

        /* valor() devuelve null cuando no hay fila. Una sola consulta y una
           sola comparación: la versión anterior preguntaba dos veces y
           encadenaba dos negaciones que se anulaban entre sí. */
        return (bool) $this->bd()->valor($sql . ' LIMIT 1', $params);
    }

    /**
     * Marca una como destacada y desmarca el resto.
     *
     * Las dos operaciones van juntas: si se quedara a medias habría dos
     * destacadas, y el hueco grande del diseño sólo admite una.
     */
    public function destacar(int $id): void
    {
        $this->bd()->transaccion(function () use ($id): void {
            $this->bd()->consultar('UPDATE `noticias` SET `destacada` = 0');
            $this->bd()->actualizar('noticias', ['destacada' => 1], '`id` = :id', ['id' => $id]);
        });
    }

    /** Las que se migraron sin día y conviene repasar. */
    public function conFechaAproximada(): int
    {
        return (int) $this->bd()->valor('SELECT COUNT(*) FROM `noticias` WHERE `fecha_aproximada` = 1');
    }

    // ── Vídeos ─────────────────────────────────────────────────────────

    /**
     * Los vídeos activos, en orden.
     *
     * @return list<array<string, mixed>>
     */
    public function videos(bool $soloActivos = true): array
    {
        $filtro = $soloActivos ? 'WHERE v.`activo` = 1' : '';

        return $this->bd()->filas(
            "SELECT v.*,
                    m.`ruta`      AS `imagen_ruta`,
                    m.`variantes` AS `imagen_variantes`,
                    m.`ancho`     AS `imagen_ancho`,
                    m.`alto`      AS `imagen_alto`,
                    m.`alt`       AS `imagen_alt`
               FROM `noticias_videos` v
               LEFT JOIN `medios` m ON m.`id` = v.`miniatura_id`
               {$filtro}
              ORDER BY v.`orden`, v.`id`"
        );
    }

    public function video(int $id): ?array
    {
        return $this->bd()->fila('SELECT * FROM `noticias_videos` WHERE `id` = :id', ['id' => $id]);
    }

    public function videoPorYoutube(string $youtubeId): ?array
    {
        return $this->bd()->fila(
            'SELECT * FROM `noticias_videos` WHERE `youtube_id` = :y LIMIT 1',
            ['y' => $youtubeId]
        );
    }

    /** @param array<string, mixed> $datos */
    public function crearVideo(array $datos): int
    {
        $ultimo = (int) $this->bd()->valor('SELECT MAX(`orden`) FROM `noticias_videos`');

        return $this->bd()->insertar('noticias_videos', $datos + ['orden' => $ultimo + 10, 'activo' => 1]);
    }

    /** @param array<string, mixed> $datos */
    public function guardarVideo(int $id, array $datos): void
    {
        $this->bd()->actualizar('noticias_videos', $datos, '`id` = :id', ['id' => $id]);
    }

    public function borrarVideo(int $id): void
    {
        $this->bd()->eliminar('noticias_videos', '`id` = :id', ['id' => $id]);
    }

    public function moverVideo(int $id, int $direccion): void
    {
        $actual = $this->video($id);

        if ($actual === null) {
            return;
        }

        $comparador = $direccion < 0 ? '<' : '>';
        $orden      = $direccion < 0 ? 'DESC' : 'ASC';

        $vecino = $this->bd()->fila(
            "SELECT `id`, `orden` FROM `noticias_videos`
              WHERE `orden` {$comparador} :o ORDER BY `orden` {$orden} LIMIT 1",
            ['o' => (int) $actual['orden']]
        );

        if (!$vecino) {
            return;
        }

        $this->bd()->actualizar('noticias_videos', ['orden' => (int) $vecino['orden']], '`id` = :id', ['id' => $id]);
        $this->bd()->actualizar('noticias_videos', ['orden' => (int) $actual['orden']], '`id` = :id', ['id' => (int) $vecino['id']]);
    }
}
