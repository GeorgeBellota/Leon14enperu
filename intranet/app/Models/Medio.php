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
     * @return array{secciones: int, bloques: int, paginas: int, total: int, donde: array<int, string>}
     */
    public function usos(int $id): array
    {
        $secciones = $this->bd()->filas(
            'SELECT p.nombre AS pagina, s.nombre AS seccion
               FROM secciones s
               JOIN paginas p ON p.id = s.pagina_id
              WHERE s.imagen_id = :id',
            ['id' => $id]
        );

        $bloques = $this->bd()->filas(
            'SELECT p.nombre AS pagina, s.nombre AS seccion
               FROM bloques b
               JOIN secciones s ON s.id = b.seccion_id
               JOIN paginas p ON p.id = s.pagina_id
              WHERE b.imagen_id = :id',
            ['id' => $id]
        );

        $paginas = $this->bd()->filas(
            'SELECT nombre AS pagina FROM paginas WHERE og_imagen_id = :id',
            ['id' => $id]
        );

        $donde = [];

        foreach ($secciones as $s) {
            $donde[] = $s['pagina'] . ' · ' . $s['seccion'];
        }
        foreach ($bloques as $b) {
            $donde[] = $b['pagina'] . ' · ' . $b['seccion'] . ' (pieza)';
        }
        foreach ($paginas as $p) {
            $donde[] = $p['pagina'] . ' · imagen para redes';
        }

        return [
            'secciones' => count($secciones),
            'bloques'   => count($bloques),
            'paginas'   => count($paginas),
            'total'     => count($secciones) + count($bloques) + count($paginas),
            'donde'     => array_values(array_unique($donde)),
        ];
    }

    /**
     * Registra una imagen ya procesada por Core\Imagen.
     *
     * @param array{ruta:string, ancho:int, alto:int, peso:int, mime:string, variantes:array<string,mixed>|null} $archivo
     */
    public function registrar(array $archivo, string $nombre, string $alt, bool $decorativa, ?int $usuarioId): int
    {
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
