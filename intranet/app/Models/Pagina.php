<?php
/**
 * Pagina — lectura y escritura del contenido del CMS.
 *
 * Carga una página entera (secciones + bloques) en DOS consultas, no en una
 * por sección: con seis secciones eso serían siete viajes a la base en cada
 * visita a la página pública. Se traen las dos listas y se emparejan en PHP.
 *
 * `datos` viaja como JSON en la base y sale de aquí ya decodificado a array,
 * para que ni la vista pública ni el panel tengan que acordarse de hacerlo.
 */

declare(strict_types=1);

namespace Intranet\Models;

use Intranet\Core\Model;

final class Pagina extends Model
{
    protected string $tabla = 'paginas';

    /** @return array<int, array<string, mixed>> */
    public function todas(): array
    {
        return $this->bd()->filas(
            'SELECT p.*, u.nombre AS editor,
                    (SELECT COUNT(*) FROM secciones s WHERE s.pagina_id = p.id) AS secciones
               FROM paginas p
               LEFT JOIN usuarios u ON u.id = p.actualizado_por
              ORDER BY p.clave = "home" DESC, p.nombre'
        );
    }

    /** @return array<string, mixed>|null */
    public function porClave(string $clave): ?array
    {
        return $this->bd()->fila('SELECT * FROM paginas WHERE clave = :clave LIMIT 1', ['clave' => $clave]);
    }

    /**
     * La página con todo su contenido, listo para pintar.
     * Las secciones se devuelven indexadas por su clave: la plantilla pide
     * `$pagina['secciones']['servicios']` y no depende del orden ni de un id.
     *
     * @return array<string, mixed>|null
     */
    public function conContenido(string $clave, bool $soloActivas = true): ?array
    {
        $pagina = $this->porClave($clave);

        if ($pagina === null) {
            return null;
        }

        $filtro = $soloActivas ? 'AND s.activa = 1' : '';

        $secciones = $this->bd()->filas(
            "SELECT s.*,
                    m.ruta AS imagen_ruta, m.alt AS imagen_alt,
                    m.ancho AS imagen_ancho, m.alto AS imagen_alto,
                    m.variantes AS imagen_variantes,
                    mm.ruta AS imagen_movil_ruta, mm.alt AS imagen_movil_alt,
                    mm.variantes AS imagen_movil_variantes
               FROM secciones s
               LEFT JOIN medios m ON m.id = s.imagen_id
               LEFT JOIN medios mm ON mm.id = s.imagen_movil_id
              WHERE s.pagina_id = :pagina {$filtro}
              ORDER BY s.orden, s.id",
            ['pagina' => (int) $pagina['id']]
        );

        if ($secciones === []) {
            $pagina['secciones'] = [];

            return $pagina;
        }

        // Los ids salen de la consulta anterior, no de la petición: se pueden
        // interpolar sin riesgo. Aun así se fuerzan a entero.
        $ids = implode(',', array_map(static fn ($s) => (int) $s['id'], $secciones));

        $filtroBloques = $soloActivas ? 'AND b.activo = 1' : '';

        $bloques = $this->bd()->filas(
            "SELECT b.*,
                    m.ruta AS imagen_ruta, m.alt AS imagen_alt,
                    m.ancho AS imagen_ancho, m.alto AS imagen_alto,
                    m.variantes AS imagen_variantes,
                    mm.ruta AS imagen_movil_ruta, mm.alt AS imagen_movil_alt,
                    mm.variantes AS imagen_movil_variantes
               FROM bloques b
               LEFT JOIN medios m ON m.id = b.imagen_id
               LEFT JOIN medios mm ON mm.id = b.imagen_movil_id
              WHERE b.seccion_id IN ({$ids}) {$filtroBloques}
              ORDER BY b.orden, b.id"
        );

        $porSeccion = [];
        foreach ($bloques as $b) {
            $b['datos'] = $this->decodificar($b['datos'] ?? null);
            $porSeccion[(int) $b['seccion_id']][] = $b;
        }

        $indexadas = [];
        foreach ($secciones as $s) {
            $s['datos']   = $this->decodificar($s['datos'] ?? null);
            $s['bloques'] = $porSeccion[(int) $s['id']] ?? [];

            $indexadas[$s['clave']] = $s;
        }

        $pagina['secciones'] = $indexadas;

        return $pagina;
    }

    /**
     * Una pieza por su slug, para pintar su página de detalle.
     *
     * Sólo se busca en secciones marcadas con «detalle» en su columna
     * `datos`. Sin ese filtro, una dirección inventada como /agenda/lima/
     * acabaría pintando la tarjeta de una jornada como si fuera una página,
     * y quedarían direcciones vivas que nadie enlaza compitiendo en Google
     * con la página buena.
     *
     * @return array<string, mixed>|null
     */
    public function piezaPorSlug(string $paginaClave, string $slug): ?array
    {
        $fila = $this->bd()->fila(
            "SELECT b.*,
                    s.clave  AS seccion_clave,
                    s.nombre AS seccion_nombre,
                    s.plantilla,
                    m.ruta   AS imagen_ruta,  m.alt   AS imagen_alt,
                    m.ancho  AS imagen_ancho, m.alto  AS imagen_alto,
                    m.variantes AS imagen_variantes,
                    mm.ruta AS imagen_movil_ruta, mm.alt AS imagen_movil_alt,
                    mm.variantes AS imagen_movil_variantes
               FROM bloques b
               JOIN secciones s ON s.id = b.seccion_id
               JOIN paginas   p ON p.id = s.pagina_id
               LEFT JOIN medios m ON m.id = b.imagen_id
               LEFT JOIN medios mm ON mm.id = b.imagen_movil_id
              WHERE p.clave = :pagina
                AND b.slug  = :slug
                AND b.activo = 1
                AND s.activa = 1
                AND JSON_EXTRACT(s.datos, '$.detalle') = TRUE
              LIMIT 1",
            ['pagina' => $paginaClave, 'slug' => $slug]
        );

        if ($fila === null) {
            return null;
        }

        $fila['datos'] = $this->decodificar($fila['datos'] ?? null);

        return $fila;
    }

    /**
     * Las piezas hermanas de una, para pintar «sigue leyendo» al pie.
     *
     * @return array<int, array<string, mixed>>
     */
    public function piezasHermanas(int $seccionId, int $excluir, int $tope = 3): array
    {
        return $this->bd()->filas(
            "SELECT b.slug, b.titulo, b.rotulo,
                    m.ruta AS imagen_ruta, m.alt AS imagen_alt,
                    mm.ruta AS imagen_movil_ruta, mm.variantes AS imagen_movil_variantes
               FROM bloques b
               LEFT JOIN medios m ON m.id = b.imagen_id
               LEFT JOIN medios mm ON mm.id = b.imagen_movil_id
              WHERE b.seccion_id = :s
                AND b.id <> :excluir
                AND b.activo = 1
                AND b.slug IS NOT NULL
              ORDER BY b.orden
              LIMIT " . max(1, min(12, $tope)),
            ['s' => $seccionId, 'excluir' => $excluir]
        );
    }

    /**
     * Publica u oculta una página entera.
     *
     * Una página oculta responde 404 en el sitio público: no es que se
     * esconda del menú, es que deja de existir para quien no la conozca. Lo
     * comprueba el despachador antes de pintar nada.
     *
     * El contenido no se toca: publicar de nuevo lo devuelve tal cual estaba.
     */
    public function alternarPublicacion(string $clave, ?int $usuarioId): ?bool
    {
        $pagina = $this->porClave($clave);

        if ($pagina === null) {
            return null;
        }

        $nuevo = ((int) $pagina['activa']) === 1 ? 0 : 1;

        $this->bd()->actualizar(
            'paginas',
            ['activa' => $nuevo, 'actualizado_por' => $usuarioId],
            'id = :id',
            ['id' => (int) $pagina['id']]
        );

        return $nuevo === 1;
    }

    /** @return array<int, array<string, mixed>> */
    public function secciones(int $paginaId): array
    {
        return $this->bd()->filas(
            'SELECT s.*, (SELECT COUNT(*) FROM bloques b WHERE b.seccion_id = s.id) AS bloques,
                    u.nombre AS editor
               FROM secciones s
               LEFT JOIN usuarios u ON u.id = s.actualizado_por
              WHERE s.pagina_id = :pagina
              ORDER BY s.orden, s.id',
            ['pagina' => $paginaId]
        );
    }

    /**
     * Las piezas que tienen página propia, agrupadas por la página que las
     * contiene.
     *
     * ── Para qué ─────────────────────────────────────────────────────────
     *
     * El panel listaba veinticuatro páginas en una sola tabla, sin decir que
     * algunas —Sedes, Noticias, Tierra de santos, los obispos, las comisiones,
     * Prensa— no son una página sino una colección: dentro de cada una viven
     * cuatro sedes, cinco santos o trece comisiones, cada una con su propia
     * dirección. Quien buscaba dónde se edita /sedes/chiclayo/ no lo
     * encontraba, porque no aparecía por ninguna parte.
     *
     * Esto devuelve ese segundo nivel para poder pintarlo debajo de su página.
     *
     * @return array<string, array<int, array<string, mixed>>> por clave de página
     */
    public function piezasConPagina(): array
    {
        $filas = $this->bd()->filas(
            "SELECT p.clave AS pagina, p.ruta,
                    s.clave AS seccion, s.nombre AS seccion_nombre,
                    b.slug, b.titulo, b.rotulo, b.activo
               FROM bloques b
               JOIN secciones s ON s.id = b.seccion_id
               JOIN paginas   p ON p.id = s.pagina_id
              WHERE JSON_EXTRACT(s.datos, '\$.detalle') = TRUE
                AND b.slug IS NOT NULL AND b.slug <> ''
              ORDER BY p.nombre, s.orden, b.orden"
        );

        $porPagina = [];

        foreach ($filas as $f) {
            $porPagina[(string) $f['pagina']][] = $f;
        }

        return $porPagina;
    }

    /** @return array<string, mixed>|null */
    public function seccion(int $paginaId, string $clave): ?array
    {
        $seccion = $this->bd()->fila(
            'SELECT * FROM secciones WHERE pagina_id = :pagina AND clave = :clave LIMIT 1',
            ['pagina' => $paginaId, 'clave' => $clave]
        );

        if ($seccion === null) {
            return null;
        }

        $seccion['datos']   = $this->decodificar($seccion['datos'] ?? null);
        $seccion['bloques'] = array_map(
            function (array $b): array {
                $b['datos'] = $this->decodificar($b['datos'] ?? null);

                return $b;
            },
            $this->bd()->filas(
                'SELECT * FROM bloques WHERE seccion_id = :s ORDER BY orden, id',
                ['s' => (int) $seccion['id']]
            )
        );

        return $seccion;
    }

    /**
     * Guarda una sección y sus bloques de una vez, dejando antes una copia del
     * estado anterior en `secciones_versiones`. Sin esa copia, un error de
     * edición en una página pública sólo se arregla reescribiéndola de memoria.
     *
     * @param array<string, mixed>        $campos
     * @param array<int, array<string, mixed>> $bloques
     */
    public function guardarSeccion(int $seccionId, array $campos, array $bloques, ?int $usuarioId): void
    {
        $this->bd()->transaccion(function () use ($seccionId, $campos, $bloques, $usuarioId): void {
            $this->versionar($seccionId, $usuarioId);

            if (array_key_exists('datos', $campos) && is_array($campos['datos'])) {
                $campos['datos'] = $campos['datos'] === []
                    ? null
                    : json_encode($campos['datos'], JSON_UNESCAPED_UNICODE);
            }

            $campos['actualizado_por'] = $usuarioId;

            $this->bd()->actualizar('secciones', $campos, 'id = :id', ['id' => $seccionId]);

            // Los bloques se reemplazan enteros: es lo que espera un formulario
            // donde se pueden añadir, borrar y reordenar filas a la vez.
            // Comparar uno a uno para hacer altas/bajas/modificaciones daría el
            // mismo resultado con tres veces más código.
            $this->bd()->eliminar('bloques', 'seccion_id = :s', ['s' => $seccionId]);

            $orden = 0;
            foreach ($bloques as $b) {
                $datos = $b['datos'] ?? null;

                $this->bd()->insertar('bloques', [
                    'seccion_id'   => $seccionId,
                    'orden'        => $orden += 10,
                    'activo'       => !empty($b['activo']) ? 1 : 0,
                    'rotulo'       => $this->oNulo($b['rotulo'] ?? null),
                    'titulo'       => $this->oNulo($b['titulo'] ?? null),
                    // El slug viaja en el formulario y se reinserta tal cual.
                    // Los bloques se borran y se vuelven a crear al guardar, así
                    // que si no se arrastrara, editar una sección cambiaría la
                    // dirección de todas sus piezas y rompería los enlaces ya
                    // compartidos.
                    'slug'         => $this->oNulo($b['slug'] ?? null),
                    'texto'        => $this->oNulo($b['texto'] ?? null),
                    'icono'        => $this->oNulo($b['icono'] ?? null),
                    // Las dos fotografías: la de escritorio y la de móvil. Van
                    // las dos escritas porque esta lista de columnas es
                    // explícita a propósito —un formulario no puede escribir
                    // una columna que no esté aquí—, y por eso mismo añadir un
                    // campo nuevo obliga a acordarse de este sitio: la primera
                    // vez, la de móvil se guardaba y el siguiente «Guardar» la
                    // borraba sin decir nada.
                    'imagen_id'       => !empty($b['imagen_id']) ? (int) $b['imagen_id'] : null,
                    'imagen_movil_id' => !empty($b['imagen_movil_id']) ? (int) $b['imagen_movil_id'] : null,
                    'enlace_texto' => $this->oNulo($b['enlace_texto'] ?? null),
                    'enlace_url'   => $this->oNulo($b['enlace_url'] ?? null),
                    'datos'        => is_array($datos) && $datos !== []
                        ? json_encode($datos, JSON_UNESCAPED_UNICODE)
                        : null,
                ]);
            }

            $this->bd()->actualizar(
                'paginas',
                ['actualizado_por' => $usuarioId],
                'id = (SELECT pagina_id FROM secciones WHERE id = :s)',
                ['s' => $seccionId]
            );
        });
    }

    /** Copia del estado actual antes de pisarlo. */
    private function versionar(int $seccionId, ?int $usuarioId): void
    {
        $seccion = $this->bd()->fila('SELECT * FROM secciones WHERE id = :id', ['id' => $seccionId]);

        if ($seccion === null) {
            return;
        }

        $seccion['bloques'] = $this->bd()->filas(
            'SELECT * FROM bloques WHERE seccion_id = :s ORDER BY orden, id',
            ['s' => $seccionId]
        );

        $this->bd()->insertar('secciones_versiones', [
            'seccion_id' => $seccionId,
            'usuario_id' => $usuarioId,
            'contenido'  => json_encode($seccion, JSON_UNESCAPED_UNICODE),
        ]);

        // Se conservan las diez últimas por sección. Sin poda, una página que
        // se edita a diario deja miles de copias que nadie va a mirar.
        $this->bd()->consultar(
            'DELETE FROM secciones_versiones
              WHERE seccion_id = :s
                AND id NOT IN (
                  SELECT id FROM (
                    SELECT id FROM secciones_versiones
                     WHERE seccion_id = :s2 ORDER BY id DESC LIMIT 10
                  ) AS ultimas
                )',
            ['s' => $seccionId, 's2' => $seccionId]
        );
    }

    /** @return array<string, mixed> */
    private function decodificar(mixed $json): array
    {
        if (!is_string($json) || $json === '') {
            return [];
        }

        $datos = json_decode($json, true);

        return is_array($datos) ? $datos : [];
    }

    private function oNulo(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : $texto;
    }
}
