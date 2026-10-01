<?php
/**
 * ============================================================================
 *  Galeria — las actividades y las fotografías de Multimedia.
 * ============================================================================
 *
 *  Dos tablas, `galeria_actividades` y `galeria_fotos`, y una regla que las
 *  explica: **la fecha vive en la FOTOGRAFÍA, no en la actividad**.
 *
 *  Parece un detalle y es la decisión de fondo. Una actividad ocurre varios
 *  días —la Conferencia Episcopal se reúne el 12 y el 14—, así que si la
 *  fecha colgara de la actividad habría que crear una actividad por día y
 *  repetir el mismo titular tres veces. Colgando de la fotografía, el
 *  desplegable «Fecha» de la página se arma solo con las fechas que tengan
 *  fotografías, y mover una de día es cambiar una celda.
 *
 *  ── Lo que NO guarda ────────────────────────────────────────────────────
 *
 *  El archivo. Eso vive en `medios`, la biblioteca que comparten todas las
 *  páginas, y aquí sólo se apunta su id. Una fotografía puede estar en la
 *  galería y en el carrusel de la portada sin duplicarse, y quitarla de la
 *  galería no la borra de la biblioteca.
 *
 *  Tampoco la fecha de captura del archivo (EXIF). A propósito: el reloj de
 *  la cámara suele estar mal, Photoshop y WhatsApp se lo llevan por delante,
 *  y la fecha de captura no siempre es la del acto. La pone el editor.
 */

declare(strict_types=1);

namespace Intranet\Models;

use Intranet\Core\Model;

final class Galeria extends Model
{
    protected string $tabla = 'galeria_fotos';

    /** Tope de fotografías por actividad. No es técnico: es para que una
     *  página no acabe pidiendo mil imágenes al navegador. */
    public const TOPE_POR_ACTIVIDAD = 400;

    // ── Actividades ────────────────────────────────────────────────────

    /**
     * Las actividades, con cuántas fotografías tiene cada una.
     *
     * @return list<array<string, mixed>>
     */
    public function actividades(bool $soloActivas = false): array
    {
        $donde = $soloActivas ? 'WHERE a.`activa` = 1' : '';

        return $this->bd()->filas(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM `galeria_fotos` f
                      WHERE f.`actividad_id` = a.`id` AND f.`activa` = 1) AS `fotos`,
                    (SELECT COUNT(DISTINCT f.`fecha`) FROM `galeria_fotos` f
                      WHERE f.`actividad_id` = a.`id` AND f.`activa` = 1
                        AND f.`fecha` IS NOT NULL) AS `fechas`
               FROM `galeria_actividades` a
               {$donde}
              ORDER BY a.`orden`, a.`id`"
        );
    }

    /** @return array<string, mixed>|null */
    public function actividad(int $id): ?array
    {
        return $this->bd()->fila(
            'SELECT * FROM `galeria_actividades` WHERE `id` = ?',
            [$id]
        ) ?: null;
    }

    public function crearActividad(string $nombre, string $rotulo = 'Actividades'): int
    {
        // El orden por defecto deja la nueva al final, de diez en diez, para
        // que quepa otra entre dos sin renumerar todas.
        $ultimo = (int) $this->bd()->valor('SELECT MAX(`orden`) FROM `galeria_actividades`');

        return $this->bd()->insertar('galeria_actividades', [
            'nombre' => mb_substr(trim($nombre), 0, 160),
            'rotulo' => mb_substr(trim($rotulo), 0, 80) ?: 'Actividades',
            'orden'  => $ultimo + 10,
            'activa' => 1,
        ]);
    }

    public function guardarActividad(int $id, string $nombre, string $rotulo, bool $activa): void
    {
        $this->bd()->actualizar('galeria_actividades', [
            'nombre' => mb_substr(trim($nombre), 0, 160),
            'rotulo' => mb_substr(trim($rotulo), 0, 80) ?: 'Actividades',
            'activa' => $activa ? 1 : 0,
        ], '`id` = :id', ['id' => $id]);
    }

    /** Borra la actividad. Sus fotografías se van con ella por la clave
     *  foránea, pero los ARCHIVOS se quedan en la biblioteca. */
    public function borrarActividad(int $id): void
    {
        $this->bd()->consultar('DELETE FROM `galeria_actividades` WHERE `id` = ?', [$id]);
    }

    public function moverActividad(int $id, int $direccion): void
    {
        $actual = $this->actividad($id);

        if ($actual === null) {
            return;
        }

        $comparador = $direccion < 0 ? '<' : '>';
        $orden      = $direccion < 0 ? 'DESC' : 'ASC';

        $vecina = $this->bd()->fila(
            "SELECT `id`, `orden` FROM `galeria_actividades`
              WHERE `orden` {$comparador} ? ORDER BY `orden` {$orden} LIMIT 1",
            [(int) $actual['orden']]
        );

        if (!$vecina) {
            return;
        }

        $this->bd()->actualizar('galeria_actividades', ['orden' => (int) $vecina['orden']], '`id` = :id', ['id' => $id]);
        $this->bd()->actualizar('galeria_actividades', ['orden' => (int) $actual['orden']], '`id` = :id', ['id' => (int) $vecina['id']]);
    }

    // ── Fechas ─────────────────────────────────────────────────────────

    /**
     * Las fechas que tienen fotografías dentro de una actividad, con su
     * recuento. Es lo que alimenta el desplegable de la página y el selector
     * del panel: así el editor elige una fecha que YA existe en lugar de
     * teclearla, que es como se crea una pestaña huérfana de una sola foto.
     *
     * @return list<array{fecha:?string, fotos:int}>
     */
    public function fechas(int $actividadId, bool $soloActivas = true): array
    {
        $filtro = $soloActivas ? 'AND `activa` = 1' : '';

        return $this->bd()->filas(
            "SELECT `fecha`, COUNT(*) AS `fotos`
               FROM `galeria_fotos`
              WHERE `actividad_id` = ? {$filtro}
              GROUP BY `fecha`
              ORDER BY `fecha` IS NULL, `fecha`",
            [$actividadId]
        );
    }

    // ── Fotografías ────────────────────────────────────────────────────

    /**
     * Las fotografías de una actividad, con la ficha de su archivo.
     *
     * @return list<array<string, mixed>>
     */
    public function fotos(int $actividadId, ?string $fecha = null, bool $soloActivas = false): array
    {
        $donde  = ['f.`actividad_id` = ?'];
        $params = [$actividadId];

        if ($fecha !== null) {
            $donde[]  = 'f.`fecha` = ?';
            $params[] = $fecha;
        }

        if ($soloActivas) {
            $donde[] = 'f.`activa` = 1';
        }

        return $this->bd()->filas(
            'SELECT f.*,
                    m.`ruta`, m.`variantes`, m.`original`, m.`peso_original`,
                    m.`ancho`, m.`alto`, m.`alt`, m.`nombre_archivo`, m.`mime`
               FROM `galeria_fotos` f
               JOIN `medios` m ON m.`id` = f.`medio_id`
              WHERE ' . implode(' AND ', $donde) . '
              ORDER BY f.`fecha` IS NULL, f.`fecha`, f.`orden`, f.`id`',
            $params
        );
    }

    /**
     * Mete una fotografía de la biblioteca en una actividad.
     *
     * Devuelve false si ya estaba en esa actividad y esa fecha: repetirla no
     * aporta nada y en la cuadrícula se vería dos veces.
     */
    public function anadirFoto(int $actividadId, int $medioId, ?string $fecha, string $descripcion = ''): bool
    {
        $repetida = $this->bd()->valor(
            'SELECT `id` FROM `galeria_fotos`
              WHERE `actividad_id` = ? AND `medio_id` = ?
                AND (`fecha` <=> ?)',
            [$actividadId, $medioId, $fecha]
        );

        if ($repetida) {
            return false;
        }

        $ultimo = (int) $this->bd()->valor(
            'SELECT MAX(`orden`) FROM `galeria_fotos` WHERE `actividad_id` = ?',
            [$actividadId]
        );

        $this->bd()->insertar('galeria_fotos', [
            'actividad_id' => $actividadId,
            'medio_id'     => $medioId,
            'fecha'        => $fecha,
            'descripcion'  => mb_substr(trim($descripcion), 0, 500) ?: null,
            'orden'        => $ultimo + 10,
            'activa'       => 1,
        ]);

        return true;
    }

    public function guardarFoto(int $id, ?string $descripcion, bool $activa): void
    {
        $this->bd()->actualizar('galeria_fotos', [
            'descripcion' => $descripcion === null || trim($descripcion) === ''
                ? null
                : mb_substr(trim($descripcion), 0, 500),
            'activa'      => $activa ? 1 : 0,
        ], '`id` = :id', ['id' => $id]);
    }

    /**
     * Mueve fotografías a otra fecha, a otra actividad, o a las dos cosas.
     *
     * No toca el archivo: cambia la fila. Por eso es instantáneo y por eso la
     * misma fotografía puede acabar en otra actividad sin duplicarse en disco.
     *
     * @param list<int> $ids
     */
    public function mover(array $ids, ?int $actividadId, ?string $fecha, bool $cambiarFecha): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if ($ids === []) {
            return 0;
        }

        $cambios = [];

        if ($actividadId !== null) {
            $cambios['actividad_id'] = $actividadId;
        }

        /* La fecha se cambia sólo si lo piden, porque «null» es un valor
           legítimo —una fotografía sin día— y no se distingue de «no la
           toques» mirando sólo el parámetro. */
        if ($cambiarFecha) {
            $cambios['fecha'] = $fecha;
        }

        if ($cambios === []) {
            return 0;
        }

        $huecos = implode(',', array_fill(0, count($ids), '?'));
        $sets   = implode(', ', array_map(static fn (string $c): string => "`{$c}` = ?", array_keys($cambios)));

        $this->bd()->consultar(
            "UPDATE `galeria_fotos` SET {$sets} WHERE `id` IN ({$huecos})",
            [...array_values($cambios), ...$ids]
        );

        return count($ids);
    }

    /**
     * Saca fotografías de la galería.
     *
     * NO borra el archivo: sigue en la biblioteca, por si lo usa el carrusel
     * o Prensa. Para borrarlo de verdad hay que ir a Imágenes, que es donde
     * se ve si alguien más lo está usando.
     *
     * @param list<int> $ids
     */
    public function quitar(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if ($ids === []) {
            return 0;
        }

        $huecos = implode(',', array_fill(0, count($ids), '?'));
        $this->bd()->consultar("DELETE FROM `galeria_fotos` WHERE `id` IN ({$huecos})", $ids);

        return count($ids);
    }

    public function ordenar(int $id, int $direccion): void
    {
        $actual = $this->bd()->fila('SELECT * FROM `galeria_fotos` WHERE `id` = ?', [$id]);

        if (!$actual) {
            return;
        }

        $comparador = $direccion < 0 ? '<' : '>';
        $orden      = $direccion < 0 ? 'DESC' : 'ASC';

        // Dentro de la MISMA actividad y la MISMA fecha: reordenar no puede
        // cambiar de pestaña a una fotografía sin querer.
        $vecina = $this->bd()->fila(
            "SELECT `id`, `orden` FROM `galeria_fotos`
              WHERE `actividad_id` = ? AND (`fecha` <=> ?) AND `orden` {$comparador} ?
              ORDER BY `orden` {$orden} LIMIT 1",
            [(int) $actual['actividad_id'], $actual['fecha'], (int) $actual['orden']]
        );

        if (!$vecina) {
            return;
        }

        $this->bd()->actualizar('galeria_fotos', ['orden' => (int) $vecina['orden']], '`id` = :id', ['id' => $id]);
        $this->bd()->actualizar('galeria_fotos', ['orden' => (int) $actual['orden']], '`id` = :id', ['id' => (int) $vecina['id']]);
    }

    /** ¿Cuántas fotografías tiene ya esta actividad? Para el tope. */
    public function cuantas(int $actividadId): int
    {
        return (int) $this->bd()->valor(
            'SELECT COUNT(*) FROM `galeria_fotos` WHERE `actividad_id` = ?',
            [$actividadId]
        );
    }

    // ── Para la página pública ─────────────────────────────────────────

    /**
     * Todo lo que la página necesita, en una estructura ya agrupada:
     * actividad → fecha → fotografías.
     *
     * Se arma en dos consultas y no en una por actividad: con diez
     * actividades serían once viajes a la base para pintar una página.
     *
     * @return list<array<string, mixed>>
     */
    public function paraLaWeb(): array
    {
        $actividades = $this->actividades(true);

        if ($actividades === []) {
            return [];
        }

        $ids    = array_map(static fn (array $a): int => (int) $a['id'], $actividades);
        $huecos = implode(',', array_fill(0, count($ids), '?'));

        $fotos = $this->bd()->filas(
            /* Las columnas de la imagen salen DOS veces y no es un descuido:
               Sitio::imagen() —el que arma el <picture> con sus variantes—
               las busca con el prefijo «imagen_», que es como llegan desde
               una sección o un bloque. Y la descarga necesita la ruta y las
               variantes con su nombre de siempre. Aliasarlas aquí evita que
               la vista tenga que rebautizar cada fila a mano. */
            "SELECT f.`id`, f.`actividad_id`, f.`fecha`, f.`descripcion`,
                    m.`ruta`      AS `imagen_ruta`,
                    m.`variantes` AS `imagen_variantes`,
                    m.`ancho`     AS `imagen_ancho`,
                    m.`alto`      AS `imagen_alto`,
                    m.`alt`       AS `imagen_alt`,
                    m.`ruta`, m.`variantes`, m.`original`, m.`peso_original`,
                    m.`ancho`, m.`alto`, m.`alt`, m.`nombre_archivo`
               FROM `galeria_fotos` f
               JOIN `medios` m ON m.`id` = f.`medio_id`
              WHERE f.`actividad_id` IN ({$huecos}) AND f.`activa` = 1
              ORDER BY f.`fecha` IS NULL, f.`fecha`, f.`orden`, f.`id`",
            $ids
        );

        $porActividad = [];

        foreach ($fotos as $foto) {
            $porActividad[(int) $foto['actividad_id']][(string) ($foto['fecha'] ?? '')][] = $foto;
        }

        $salida = [];

        foreach ($actividades as $actividad) {
            $suyas = $porActividad[(int) $actividad['id']] ?? [];

            // Una actividad sin fotografías no se pinta: sería un titular con
            // un hueco debajo.
            if ($suyas === []) {
                continue;
            }

            $actividad['por_fecha'] = $suyas;
            $salida[] = $actividad;
        }

        return $salida;
    }
}
