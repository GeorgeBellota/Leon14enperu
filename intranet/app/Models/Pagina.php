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

    /**
     * Los datos de buscador de una página, con la ruta de la imagen ya resuelta.
     *
     * El JOIN evita una segunda consulta para traducir `og_imagen_id` a una
     * ruta: esto se llama en CADA página pública que se pinta, y es la clase de
     * sitio donde una consulta de más se nota.
     *
     * @return array<string, mixed>|null
     */
    public function seoDe(string $clave): ?array
    {
        return $this->bd()->fila(
            'SELECT p.titulo_seo, p.descripcion_seo, m.ruta AS og_imagen_ruta
               FROM paginas p
               LEFT JOIN medios m ON m.id = p.og_imagen_id
              WHERE p.clave = :clave
              LIMIT 1',
            ['clave' => $clave]
        );
    }

    /**
     * Guarda los datos de buscador de una página.
     *
     * Los campos vacíos se guardan como NULL y no como cadena vacía: es la
     * forma de decir «aquí no hay nada puesto», y es lo que hace que la página
     * vuelva a usar el texto que trae escrito la vista.
     */
    public function guardarSeo(
        string $clave,
        string $titulo,
        string $descripcion,
        ?int $imagenId,
        ?int $usuarioId
    ): int {
        return $this->bd()->actualizar(
            'paginas',
            [
                'titulo_seo'      => trim($titulo) === '' ? null : mb_substr(trim($titulo), 0, 190),
                'descripcion_seo' => trim($descripcion) === '' ? null : mb_substr(trim($descripcion), 0, 300),
                'og_imagen_id'    => $imagenId,
                'actualizado_por' => $usuarioId,
            ],
            'clave = :clave',
            ['clave' => $clave]
        );
    }

    /**
     * Reescribe el orden de las secciones de una página.
     *
     * ── Por qué se numera de diez en diez ────────────────────────────────
     *
     * Porque deja hueco. Si una migración futura tiene que meter una sección
     * entre la segunda y la tercera, le pone el 25 y no hay que renumerar las
     * que vienen detrás. Es como estaban numeradas desde el principio.
     *
     * ── Por qué sólo mueve las que ya existen ────────────────────────────
     *
     * La lista de claves llega del navegador. Se cruza con lo que la página
     * tiene de verdad y lo que no cuadre se ignora: una clave inventada no
     * puede tocar la sección de otra página, porque el WHERE lleva siempre el
     * id de ésta.
     *
     * @param  array<int, string> $claves en el orden deseado
     * @return int                cuántas secciones se movieron
     */
    public function reordenarSecciones(int $paginaId, array $claves, ?int $usuarioId): int
    {
        $suyas = $this->bd()->columna(
            'SELECT clave FROM secciones WHERE pagina_id = :pagina',
            ['pagina' => $paginaId]
        );

        if ($suyas === []) {
            return 0;
        }

        $validas = array_values(array_filter(
            array_unique(array_map('strval', $claves)),
            static fn (string $c): bool => in_array($c, $suyas, true)
        ));

        if ($validas === []) {
            return 0;
        }

        return (int) $this->bd()->transaccion(function () use ($paginaId, $validas, $usuarioId): int {
            $movidas = 0;

            foreach ($validas as $posicion => $clave) {
                $movidas += $this->bd()->actualizar(
                    'secciones',
                    ['orden' => ($posicion + 1) * 10, 'actualizado_por' => $usuarioId],
                    'pagina_id = :pagina AND clave = :clave',
                    ['pagina' => $paginaId, 'clave' => $clave]
                );
            }

            return $movidas;
        });
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
                    b.id, b.slug, b.titulo, b.rotulo, b.activo
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
    public function guardarSeccion(int $seccionId, array $campos, ?array $bloques, ?int $usuarioId): void
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

            /* `null` no es lo mismo que una lista vacía: significa «no toques
               las piezas». El editor de la sección ya no las trae —cada una
               tiene su propia pantalla— y si esto lo tomara por una lista
               vacía, cambiar el titular de Itinerario borraría sus seis
               jornadas sin preguntar y sin un solo mensaje. */
            if ($bloques === null) {
                $this->bd()->actualizar(
                    'paginas',
                    ['actualizado_por' => $usuarioId],
                    'id = (SELECT pagina_id FROM secciones WHERE id = :s)',
                    ['s' => $seccionId]
                );

                return;
            }

            /* ── Las piezas ──────────────────────────────────────────────
             *
             * Se actualizan una a una, no se borran y se vuelven a crear.
             *
             * Antes era un DELETE de todas y un INSERT de todas: menos
             * código, mismo resultado en pantalla y un id nuevo para cada
             * pieza en cada Guardar. Con el editor en sábana daba igual
             * —nadie nombraba una pieza— pero en cuanto cada pieza tiene su
             * propia pantalla, su dirección es su id: si cambia al guardar,
             * el enlace que alguien dejó abierto en otra pestaña apunta a
             * una pieza que ya no existe. Por eso ahora el id viaja en el
             * formulario y manda.
             *
             * Se nota también fuera: `bloques` iba por el id 809 con 80
             * filas vivas, porque cada Guardar quemaba una tanda entera.
             */
            $vivas = array_map('intval', $this->bd()->columna(
                'SELECT id FROM bloques WHERE seccion_id = :s',
                ['s' => $seccionId]
            ));

            /* Qué piezas de las que hay siguen viniendo. Un id que no sea de
               esta sección se ignora: el formulario llega del navegador, y no
               puede servir para editar la pieza de otra página. */
            $siguen = [];

            foreach ($bloques as $b) {
                $id = (int) ($b['id'] ?? 0);

                if ($id > 0 && in_array($id, $vivas, true)) {
                    $siguen[] = $id;
                }
            }

            // Las que ya no vienen, se van.
            $sobran = array_values(array_diff($vivas, $siguen));

            if ($sobran !== []) {
                $huecos = implode(',', array_fill(0, count($sobran), '?'));
                $this->bd()->eliminar('bloques', "seccion_id = ? AND id IN ({$huecos})",
                    array_merge([$seccionId], $sobran));
            }

            /* Y a las que se quedan se les suelta el slug antes de reescribir.
               `uq_bloques_slug` es único por sección: si dos sedes
               intercambian dirección, al escribir la primera chocaría con la
               segunda, que todavía no se ha tocado. Borrando y reinsertando
               esto no podía pasar; actualizando, sí. */
            if ($siguen !== []) {
                $huecos = implode(',', array_fill(0, count($siguen), '?'));
                $this->bd()->consultar(
                    "UPDATE `bloques` SET `slug` = NULL WHERE `id` IN ({$huecos})",
                    $siguen
                );
            }

            $orden = 0;

            foreach ($bloques as $b) {
                $datos = $b['datos'] ?? null;

                /* La lista de columnas es explícita a propósito: un
                   formulario no puede escribir una columna que no esté aquí.
                   Y por eso mismo, añadir un campo obliga a acordarse de este
                   sitio: la primera vez, la imagen de móvil se guardaba y el
                   siguiente «Guardar» la borraba sin decir nada. */
                $fila = [
                    'orden'  => $orden += 10,
                    'activo' => !empty($b['activo']) ? 1 : 0,
                    'rotulo' => $this->oNulo($b['rotulo'] ?? null),
                    'titulo' => $this->oNulo($b['titulo'] ?? null),
                    // El slug viaja en el formulario y se escribe tal cual: se
                    // fija al crear la pieza y no se recalcula al editar el
                    // titular, porque la dirección que ya se compartió tiene
                    // que seguir existiendo.
                    'slug'   => $this->oNulo($b['slug'] ?? null),
                    'texto'  => $this->oNulo($b['texto'] ?? null),
                    'icono'  => $this->oNulo($b['icono'] ?? null),
                    'imagen_id'       => !empty($b['imagen_id']) ? (int) $b['imagen_id'] : null,
                    'imagen_movil_id' => !empty($b['imagen_movil_id']) ? (int) $b['imagen_movil_id'] : null,
                    'enlace_texto'    => $this->oNulo($b['enlace_texto'] ?? null),
                    'enlace_url'      => $this->oNulo($b['enlace_url'] ?? null),
                    'datos'  => is_array($datos) && $datos !== []
                        ? json_encode($datos, JSON_UNESCAPED_UNICODE)
                        : null,
                ];

                $id = (int) ($b['id'] ?? 0);

                if ($id > 0 && in_array($id, $siguen, true)) {
                    $this->bd()->actualizar('bloques', $fila, 'id = :id', ['id' => $id]);

                    continue;
                }

                $fila['seccion_id'] = $seccionId;
                $this->bd()->insertar('bloques', $fila);
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
    /* ══════════════════════════════════════════════════════════════════
       UNA PIEZA
       ------------------------------------------------------------------
       Cada pieza de una sección —una jornada del itinerario, una sede, un
       santo— tiene ahora su propia pantalla. Antes se editaban las trece a
       la vez en un formulario de sábana donde corregir una coma obligaba a
       bajar por las otras doce, y donde cada Guardar reescribía las trece.

       Todas estas operaciones piden el id de la sección además del de la
       pieza, y comprueban que la pieza sea suya. Los dos vienen de la URL:
       sin esa comprobación, quien puede editar una página podría tocar las
       piezas de cualquier otra escribiendo otro número.
       ══════════════════════════════════════════════════════════════════ */

    /** Una pieza de esta sección, con su `datos` ya decodificado. */
    public function pieza(int $seccionId, int $id): ?array
    {
        $fila = $this->bd()->fila(
            'SELECT * FROM bloques WHERE id = :id AND seccion_id = :s',
            ['id' => $id, 's' => $seccionId]
        );

        if ($fila === null) {
            return null;
        }

        $fila['datos'] = $this->decodificar($fila['datos'] ?? null);

        return $fila;
    }

    /**
     * Crea una pieza vacía al final y devuelve su id.
     *
     * Vacía a propósito: se crea para entrar a rellenarla, y así su pantalla
     * nace con una dirección estable en lugar de tener que inventar un «id
     * provisional» que cambiaría al primer Guardar.
     */
    public function crearPieza(int $seccionId, ?int $usuarioId): int
    {
        return (int) $this->bd()->transaccion(function () use ($seccionId, $usuarioId): int {
            $this->versionar($seccionId, $usuarioId);

            $ultimo = (int) ($this->bd()->valor(
                'SELECT MAX(orden) FROM bloques WHERE seccion_id = :s',
                ['s' => $seccionId]
            ) ?? 0);

            return $this->bd()->insertar('bloques', [
                'seccion_id' => $seccionId,
                'orden'      => $ultimo + 10,
                // Nace oculta: una pieza en blanco no debe salir en la web
                // entre que se crea y se termina de rellenar.
                'activo'     => 0,
            ]);
        });
    }

    /** Guarda una sola pieza. El resto de la sección no se toca. */
    public function guardarPieza(int $seccionId, int $id, array $campos, ?int $usuarioId): bool
    {
        return (bool) $this->bd()->transaccion(
            function () use ($seccionId, $id, $campos, $usuarioId): bool {
                if ($this->pieza($seccionId, $id) === null) {
                    return false;
                }

                $this->versionar($seccionId, $usuarioId);

                if (array_key_exists('datos', $campos)) {
                    $campos['datos'] = is_array($campos['datos']) && $campos['datos'] !== []
                        ? json_encode($campos['datos'], JSON_UNESCAPED_UNICODE)
                        : null;
                }

                $this->bd()->actualizar('bloques', $campos, 'id = :id', ['id' => $id]);

                $this->bd()->actualizar(
                    'paginas',
                    ['actualizado_por' => $usuarioId],
                    'id = (SELECT pagina_id FROM secciones WHERE id = :s)',
                    ['s' => $seccionId]
                );

                return true;
            }
        );
    }

    /**
     * Sube o baja una pieza un puesto.
     *
     * Se intercambia el `orden` con el de su vecina en vez de renumerar la
     * sección entera: son dos escrituras en lugar de trece, y las demás
     * piezas conservan el número que ya tenían.
     */
    public function moverPieza(int $seccionId, int $id, string $hacia, ?int $usuarioId): bool
    {
        return (bool) $this->bd()->transaccion(
            function () use ($seccionId, $id, $hacia, $usuarioId): bool {
                $esta = $this->pieza($seccionId, $id);

                if ($esta === null) {
                    return false;
                }

                $arriba = $hacia === 'subir';

                /* El desempate por id va en la consulta porque dos piezas
                   pueden compartir `orden` —se crearon a la vez, o vienen de
                   datos viejos— y la lista se pinta con ORDER BY orden, id.
                   Sin desempatar aquí, la vecina que busca este método no
                   sería la que se ve encima en pantalla. */
                $vecina = $this->bd()->fila(
                    $arriba
                        ? 'SELECT id, orden FROM bloques
                            WHERE seccion_id = :s
                              AND (orden < :o OR (orden = :o2 AND id < :i))
                            ORDER BY orden DESC, id DESC LIMIT 1'
                        : 'SELECT id, orden FROM bloques
                            WHERE seccion_id = :s
                              AND (orden > :o OR (orden = :o2 AND id > :i))
                            ORDER BY orden ASC, id ASC LIMIT 1',
                    ['s' => $seccionId, 'o' => (int) $esta['orden'],
                     'o2' => (int) $esta['orden'], 'i' => $id]
                );

                // Ya está la primera o la última: no hay nada que hacer, y no
                // es un error.
                if ($vecina === null) {
                    return true;
                }

                $this->versionar($seccionId, $usuarioId);

                $mio  = (int) $esta['orden'];
                $suyo = (int) $vecina['orden'];

                // Si empatan, intercambiarlos no movería nada: se separa.
                if ($mio === $suyo) {
                    $suyo = $arriba ? $mio + 1 : $mio - 1;
                }

                $this->bd()->actualizar('bloques', ['orden' => $mio],
                    'id = :id', ['id' => (int) $vecina['id']]);
                $this->bd()->actualizar('bloques', ['orden' => $suyo],
                    'id = :id', ['id' => $id]);

                return true;
            }
        );
    }

    /** Borra una pieza de esta sección. */
    public function borrarPieza(int $seccionId, int $id, ?int $usuarioId): bool
    {
        return (bool) $this->bd()->transaccion(
            function () use ($seccionId, $id, $usuarioId): bool {
                if ($this->pieza($seccionId, $id) === null) {
                    return false;
                }

                $this->versionar($seccionId, $usuarioId);

                $this->bd()->eliminar('bloques', 'id = :id AND seccion_id = :s',
                    ['id' => $id, 's' => $seccionId]);

                return true;
            }
        );
    }

    /**
     * ¿Hay ya otra pieza de esta sección con esa dirección?
     *
     * Se excluye la propia: al guardar sin tocar el slug chocaría consigo
     * misma, y el formulario diría que está ocupada.
     */
    public function slugDePiezaOcupado(int $seccionId, string $slug, int $excepto = 0): bool
    {
        return (bool) $this->bd()->valor(
            'SELECT 1 FROM bloques
              WHERE seccion_id = :s AND slug = :g AND id <> :e LIMIT 1',
            ['s' => $seccionId, 'g' => $slug, 'e' => $excepto]
        );
    }

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
