<?php
/**
 * Medio — la biblioteca de imágenes del sitio.
 *
 * Una fila de `medios` no es un archivo: es una familia. `ruta` apunta al que
 * va en el <img src> —el respaldo— y `variantes` guarda el nombre base, los
 * anchos y los formatos con los que se construye el <picture>. Ver
 * Core\Imagen y la migración 0010 para el porqué.
 */

declare(strict_types=1);

namespace Intranet\Models;

use Intranet\Core\Model;

final class Medio extends Model
{
    protected string $tabla = 'medios';

    /**
     * Listado del gestor, paginado y con búsqueda por nombre.
     *
     * @param  array{buscar?: string} $filtros
     * @return array{filas: array<int, array<string,mixed>>, total: int, pagina: int, paginas: int, porPagina: int}
     */
    public function listar(array $filtros, int $pagina, int $porPagina = 24): array
    {
        $condiciones = [];
        $params      = [];

        $buscar = trim((string) ($filtros['buscar'] ?? ''));

        if ($buscar !== '') {
            // Dos marcadores distintos para el mismo valor: con la emulación de
            // sentencias preparadas desactivada, PDO no admite repetir un
            // nombre dentro de la misma consulta.
            $condiciones[]        = '(m.nombre_archivo LIKE :buscar_n OR m.alt LIKE :buscar_a)';
            $params['buscar_n']   = '%' . $buscar . '%';
            $params['buscar_a']   = '%' . $buscar . '%';
        }

        // donde() devuelve la condición sin el WHERE: lo pone quien la usa.
        $where = 'WHERE ' . $this->donde($condiciones);

        return $this->paginar(
            "SELECT m.*, u.nombre AS autor
               FROM medios m
               LEFT JOIN usuarios u ON u.id = m.creado_por
               {$where}
              ORDER BY m.id DESC",
            "SELECT COUNT(*) FROM medios m {$where}",
            $params,
            $pagina,
            $porPagina
        );
    }

    /**
     * Las imágenes para el selector de una sección o un bloque.
     *
     * Sin paginar y sin buscar: el selector filtra en el navegador, que con
     * unos cientos de imágenes es instantáneo y evita un viaje al servidor
     * cada vez que se escribe una letra.
     *
     * @return array<int, array<string, mixed>>
     */
    public function paraElegir(): array
    {
        return $this->bd()->filas(
            'SELECT id, ruta, nombre_archivo, alt, ancho, alto
               FROM medios
              ORDER BY nombre_archivo'
        );
    }

    /** @return array<string, mixed>|null */
    public function conVariantes(int $id): ?array
    {
        $fila = $this->buscar($id);

        if ($fila === null) {
            return null;
        }

        $fila['variantes'] = self::decodificar($fila['variantes'] ?? null);

        return $fila;
    }

    /**
     * Dónde se está usando. Se consulta ANTES de borrar: la clave foránea es
     * ON DELETE SET NULL, así que borrar una imagen en uso no daría error —
     * dejaría la página pública sin foto y sin avisar a nadie.
     *
     * @return array{total: int, donde: list<string>}
     */
    public function usos(int $id): array
    {
        /* ── Dónde se usa una imagen ──────────────────────────────────────
           Esta lista TIENE que cubrir todas las columnas que apuntan a
           `medios`. Si falta una, el panel dirá «no se usa en ninguna parte»
           y quien lo lea borrará una imagen que sí se está viendo.

           Ya pasó: al añadir la galería y las noticias en octubre de 2026,
           este método se quedó mirando sólo secciones, bloques y portadas de
           página. Borrar una foto de la galería la quitaba de Multimedia sin
           avisar —la clave foránea es ON DELETE CASCADE— y una noticia se
           quedaba sin portada en silencio.

           Al crear una tabla nueva con una columna que apunte a `medios`,
           hay que añadirla aquí. */
        $consultas = [
            // Secciones: la imagen principal y la de móvil, que es otra
            // columna y antes no se miraba.
            ['SELECT p.nombre AS pagina, s.nombre AS donde, \'\' AS nota
                FROM secciones s JOIN paginas p ON p.id = s.pagina_id
               WHERE s.imagen_id = :id'],
            ['SELECT p.nombre AS pagina, s.nombre AS donde, \'versión móvil\' AS nota
                FROM secciones s JOIN paginas p ON p.id = s.pagina_id
               WHERE s.imagen_movil_id = :id'],

            // Piezas dentro de una sección.
            ['SELECT p.nombre AS pagina, s.nombre AS donde, \'pieza\' AS nota
                FROM bloques b
                JOIN secciones s ON s.id = b.seccion_id
                JOIN paginas p ON p.id = s.pagina_id
               WHERE b.imagen_id = :id'],
            ['SELECT p.nombre AS pagina, s.nombre AS donde, \'pieza · versión móvil\' AS nota
                FROM bloques b
                JOIN secciones s ON s.id = b.seccion_id
                JOIN paginas p ON p.id = s.pagina_id
               WHERE b.imagen_movil_id = :id'],

            // La imagen al compartir una página.
            ['SELECT nombre AS pagina, \'\' AS donde, \'imagen para redes\' AS nota
                FROM paginas WHERE og_imagen_id = :id'],

            // La galería de Multimedia. Ojo: su clave foránea es CASCADE, así
            // que borrar aquí la saca de la galería sin preguntar.
            ['SELECT \'Multimedia\' AS pagina, a.nombre AS donde, \'galería\' AS nota
                FROM galeria_fotos f
                JOIN galeria_actividades a ON a.id = f.actividad_id
               WHERE f.medio_id = :id'],

            // Noticias: la portada, la de redes y la miniatura de un vídeo.
            ['SELECT \'Noticias\' AS pagina, n.titulo AS donde, \'portada\' AS nota
                FROM noticias n WHERE n.imagen_id = :id'],
            ['SELECT \'Noticias\' AS pagina, n.titulo AS donde, \'imagen para redes\' AS nota
                FROM noticias n WHERE n.og_imagen_id = :id'],
            ['SELECT \'Noticias\' AS pagina, COALESCE(v.titulo, v.youtube_id) AS donde, \'portada del vídeo\' AS nota
                FROM noticias_videos v WHERE v.miniatura_id = :id'],
        ];

        $donde = [];

        foreach ($consultas as [$sql]) {
            /* Una tabla puede no existir todavía en una instalación a medio
               migrar. Que falte no puede impedir contestar: se sigue con las
               demás y, como mucho, se informa de menos usos, nunca de más
               seguridad de la que hay. */
            try {
                $filas = $this->bd()->filas($sql, ['id' => $id]);
            } catch (\Throwable $e) {
                continue;
            }

            foreach ($filas as $f) {
                $texto = trim((string) $f['pagina']);

                if (trim((string) $f['donde']) !== '') {
                    $texto .= ' · ' . $f['donde'];
                }

                if (trim((string) $f['nota']) !== '') {
                    $texto .= ' (' . $f['nota'] . ')';
                }

                $donde[] = $texto;
            }
        }

        $donde = array_values(array_unique($donde));

        return [
            'total' => count($donde),
            'donde' => $donde,
        ];
    }

    /**
     * Registra una imagen ya procesada por Core\Imagen.
     *
     * @param array{ruta:string, ancho:int, alto:int, peso:int, mime:string, variantes:array<string,mixed>|null} $archivo
     */
    public function registrar(
        array $archivo,
        string $nombre,
        string $alt,
        bool $decorativa,
        ?int $usuarioId,
        ?string $original = null,
        ?int $pesoOriginal = null
    ): int {
        return $this->bd()->insertar('medios', [
            'ruta'           => $archivo['ruta'],
            'nombre_archivo' => mb_substr($nombre, 0, 190),
            'mime'           => $archivo['mime'],
            'ancho'          => $archivo['ancho'] ?: null,
            'alto'           => $archivo['alto'] ?: null,
            'peso'           => $archivo['peso'],
            'variantes'      => $archivo['variantes'] === null
                ? null
                : json_encode($archivo['variantes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            /* El archivo tal cual lo subió el editor, sin recortar ni
               recomprimir. Es lo que se entrega en «Descargar» de la galería.
               Nulo en los SVG —ahí el original es el que ya sirve la web— y
               en las 95 fotografías anteriores a octubre de 2026, cuyo
               original se borró al subirlas. */
            'original'       => $original,
            'peso_original'  => $pesoOriginal,
            'alt'            => mb_substr($alt, 0, 255),
            'decorativa'     => $decorativa ? 1 : 0,
            'creado_por'     => $usuarioId,
        ]);
    }

    /** @return array<string, mixed> */
    public static function decodificar(mixed $json): array
    {
        if (!is_string($json) || $json === '') {
            return [];
        }

        $datos = json_decode($json, true);

        return is_array($datos) ? $datos : [];
    }
}
