<?php
/**
 * Model — base de los modelos.
 *
 * Deliberadamente delgado: da la conexión, el CRUD elemental y una paginación
 * que sirve para todos los listados. Todo lo demás (los filtros de
 * voluntarios, el árbol de secciones del CMS) se escribe como SQL explícito en
 * el modelo que lo necesita. Un ORM propio a medias acaba siendo más difícil
 * de leer que la consulta que oculta.
 *
 * Regla que no se rompe: los VALORES viajan siempre como parámetros. Lo único
 * que puede concatenarse es un nombre de columna, y sólo si sale de una lista
 * blanca declarada en el propio modelo (ver `columnaSegura`).
 */

declare(strict_types=1);

namespace Intranet\Core;

use InvalidArgumentException;

abstract class Model
{
    protected string $tabla = '';
    protected string $llave = 'id';

    /** Columnas por las que se permite ordenar. Lista blanca obligatoria. */
    protected array $ordenables = ['id'];

    public function __construct(protected Contenedor $c)
    {
    }

    protected function bd(): Database
    {
        return $this->c->bd();
    }

    /** @return array<string, mixed>|null */
    public function buscar(int $id): ?array
    {
        return $this->bd()->fila(
            "SELECT * FROM `{$this->tabla}` WHERE `{$this->llave}` = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /** @param array<string, mixed> $datos */
    public function crear(array $datos): int
    {
        return $this->bd()->insertar($this->tabla, $datos);
    }

    /** @param array<string, mixed> $datos */
    public function actualizar(int $id, array $datos): int
    {
        return $this->bd()->actualizar($this->tabla, $datos, "`{$this->llave}` = :id", ['id' => $id]);
    }

    public function eliminar(int $id): int
    {
        return $this->bd()->eliminar($this->tabla, "`{$this->llave}` = :id", ['id' => $id]);
    }

    /**
     * Valida un nombre de columna contra la lista blanca antes de meterlo en
     * un ORDER BY. Los identificadores no se pueden pasar como parámetro, así
     * que la lista blanca es la única defensa real aquí.
     */
    protected function columnaSegura(string $columna, string $porDefecto): string
    {
        return in_array($columna, $this->ordenables, true) ? $columna : $porDefecto;
    }

    protected function direccionSegura(string $direccion): string
    {
        return strtoupper($direccion) === 'ASC' ? 'ASC' : 'DESC';
    }

    /**
     * Cuántas filas como mucho por página EN PANTALLA.
     *
     * Una tabla de doscientas filas ya es más de lo que nadie lee de un
     * vistazo; más allá sólo se gasta memoria y ancho de banda.
     */
    protected const TOPE_PANTALLA = 200;

    /**
     * Paginación. Devuelve las filas y lo que necesita el pie del listado.
     *
     * LIMIT y OFFSET van interpolados como enteros ya convertidos con (int).
     * MySQL no admite marcadores ahí cuando la emulación de sentencias
     * preparadas está desactivada, que es justo nuestro caso.
     *
     * @param array<string, mixed> $params
     * @return array{filas: array<int, array<string,mixed>>, total: int, pagina: int, paginas: int, porPagina: int}
     */
    protected function paginar(
        string $sqlBase,
        string $sqlConteo,
        array $params,
        int $pagina,
        int $porPagina = 25,
        int $tope = self::TOPE_PANTALLA
    ): array {
        $pagina    = max(1, $pagina);
        $porPagina = max(1, min($tope, $porPagina));

        $total   = (int) $this->bd()->valor($sqlConteo, $params);
        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina  = min($pagina, $paginas);
        $salto   = ($pagina - 1) * $porPagina;

        $filas = $this->bd()->filas(
            $sqlBase . sprintf(' LIMIT %d OFFSET %d', $porPagina, $salto),
            $params
        );

        return [
            'filas'     => $filas,
            'total'     => $total,
            'pagina'    => $pagina,
            'paginas'   => $paginas,
            'porPagina' => $porPagina,
        ];
    }

    /**
     * Construye un WHERE a partir de condiciones opcionales ya escritas por el
     * modelo. Nunca recibe SQL venido de la petición.
     *
     * @param array<int, string> $condiciones
     */
    protected function donde(array $condiciones): string
    {
        $limpias = array_filter($condiciones, static fn ($c) => trim((string) $c) !== '');

        return $limpias === [] ? '1 = 1' : implode(' AND ', $limpias);
    }

    protected function exigirTabla(): void
    {
        if ($this->tabla === '') {
            throw new InvalidArgumentException(static::class . ' no ha declarado $tabla.');
        }
    }
}
