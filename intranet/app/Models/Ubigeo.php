<?php
/**
 * Ubigeo — departamentos, provincias y distritos del Perú.
 *
 * Alimenta los tres desplegables en cascada del formulario de voluntariado.
 *
 * Las tablas vienen de una fuente externa y conservan su nomenclatura
 * original: `name` en departamento pero `nombre_provincia` y
 * `nombre_distrito` en las otras dos, y los campos de relación en inglés
 * (`department_id`, `province_id`). No se renombran para no separarlas de la
 * fuente de la que se actualizan; lo que hace este modelo es devolverlas
 * siempre con las mismas claves —`id` y `nombre`— para que ni el formulario ni
 * el panel tengan que saber nada de eso.
 */

declare(strict_types=1);

namespace Intranet\Models;

use Intranet\Core\Model;

final class Ubigeo extends Model
{
    /** @return array<int, array{id: string, nombre: string}> */
    public function departamentos(): array
    {
        return $this->bd()->filas(
            'SELECT id, name AS nombre FROM ubigeo_departamento ORDER BY name'
        );
    }

    /** @return array<int, array{id: string, nombre: string}> */
    public function provincias(string $departamentoId): array
    {
        if ($departamentoId === '') {
            return [];
        }

        return $this->bd()->filas(
            'SELECT id, nombre_provincia AS nombre
               FROM ubigeo_provincia
              WHERE department_id = :d
              ORDER BY nombre_provincia',
            ['d' => $departamentoId]
        );
    }

    /** @return array<int, array{id: string, nombre: string}> */
    public function distritos(string $provinciaId): array
    {
        if ($provinciaId === '') {
            return [];
        }

        return $this->bd()->filas(
            'SELECT id, nombre_distrito AS nombre
               FROM ubigeo_distrito
              WHERE province_id = :p
              ORDER BY nombre_distrito',
            ['p' => $provinciaId]
        );
    }

    /**
     * Busca una provincia por su NOMBRE dentro de un departamento.
     *
     * Provincia y distrito se escriben a mano, no se eligen de una lista. La
     * mayoría escribe el nombre correcto aunque el navegador no llegara a
     * cargar las sugerencias, así que vale la pena intentar reconocerlo y
     * recuperar el código oficial: se gana un dato limpio sin pedirle nada más
     * a la persona.
     *
     * Si no se reconoce no pasa nada, se guarda el texto tal cual. Esto añade
     * calidad cuando acierta; nunca rechaza.
     *
     * @return array{id: string, nombre: string}|null
     */
    public function provinciaPorNombre(string $departamentoId, string $nombre): ?array
    {
        return $this->porNombre(
            'SELECT id, nombre_provincia AS nombre FROM ubigeo_provincia WHERE department_id = :d',
            ['d' => $departamentoId],
            $departamentoId,
            $nombre
        );
    }

    /**
     * Busca un distrito por su NOMBRE dentro de una provincia.
     *
     * @return array{id: string, nombre: string}|null
     */
    public function distritoPorNombre(string $provinciaId, string $nombre): ?array
    {
        return $this->porNombre(
            'SELECT id, nombre_distrito AS nombre FROM ubigeo_distrito WHERE province_id = :p',
            ['p' => $provinciaId],
            $provinciaId,
            $nombre
        );
    }

    /**
     * La comparación se hace en PHP y no en SQL a propósito.
     *
     * En SQL habría que apoyarse en la intercalación de la tabla para que
     * «CAÑETE» encuentre «canete» y «HUAROCHIRÍ» encuentre «huarochiri», y esa
     * intercalación es distinta en el servidor local y en el del hosting. Una
     * comparación que depende de cómo esté configurada la base es una
     * comparación que funciona en las pruebas y falla en producción.
     *
     * Aquí se normaliza igual que en el navegador —sin tildes, sin mayúsculas,
     * sin signos— y las dos mitades del sistema coinciden siempre. Son como
     * mucho 200 filas: el coste no se nota.
     *
     * @param array<string, string> $parametros
     * @return array{id: string, nombre: string}|null
     */
    private function porNombre(string $sql, array $parametros, string $padre, string $nombre): ?array
    {
        if ($padre === '' || trim($nombre) === '') {
            return null;
        }

        $buscado = self::aplanar($nombre);
        if ($buscado === '') {
            return null;
        }

        foreach ($this->bd()->filas($sql, $parametros) as $fila) {
            if (self::aplanar((string) $fila['nombre']) === $buscado) {
                return ['id' => (string) $fila['id'], 'nombre' => trim((string) $fila['nombre'])];
            }
        }

        return null;
    }

    /**
     * Deja un nombre en su forma comparable: minúsculas, sin tildes y sin
     * signos. Tiene que dar exactamente lo mismo que la función `plano()` de
     * form.js, o el navegador y el servidor reconocerían cosas distintas.
     */
    public static function aplanar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');

        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o',
            'ñ' => 'n',

            // La ç aparece de verdad en el ubigeo peruano: OMACHAÇ, en Cusco.
            // Salió al comparar esta función con la del navegador sobre los
            // 2095 nombres del país, y era el único desacuerdo entre las dos.
            // Sin esto, quien escribiera «omachac» no habría encontrado su
            // distrito.
            'ç' => 'c',
        ]);

        $texto = preg_replace('/[^a-z0-9 ]/', ' ', $texto) ?? '';

        return trim((string) preg_replace('/\s+/', ' ', $texto));
    }

    /**
     * Comprueba que los tres niveles existan Y encajen entre sí.
     *
     * No basta con que cada uno exista por separado: un envío manipulado
     * podría mandar el distrito de Cusco con el departamento de Lima y quedaría
     * guardado un domicilio imposible. Se valida la cadena entera de una vez.
     *
     * @return array{nombre_departamento: string, nombre_provincia: string, nombre_distrito: string}|null
     */
    public function verificar(string $departamentoId, string $provinciaId, string $distritoId): ?array
    {
        $fila = $this->bd()->fila(
            'SELECT d.name             AS nombre_departamento,
                    p.nombre_provincia AS nombre_provincia,
                    x.nombre_distrito  AS nombre_distrito
               FROM ubigeo_distrito x
               JOIN ubigeo_provincia p    ON p.id = x.province_id
               JOIN ubigeo_departamento d ON d.id = p.department_id
              WHERE x.id = :distrito
                AND p.id = :provincia
                AND d.id = :departamento
              LIMIT 1',
            [
                'distrito'     => $distritoId,
                'provincia'    => $provinciaId,
                'departamento' => $departamentoId,
            ]
        );

        return $fila === null ? null : [
            'nombre_departamento' => (string) $fila['nombre_departamento'],
            'nombre_provincia'    => (string) $fila['nombre_provincia'],
            'nombre_distrito'     => (string) $fila['nombre_distrito'],
        ];
    }

    /**
     * Los tres nombres de un distrito, para pintar una ficha ya guardada.
     *
     * @return array<string, string>|null
     */
    public function porDistrito(?string $distritoId): ?array
    {
        if ($distritoId === null || $distritoId === '') {
            return null;
        }

        return $this->bd()->fila(
            'SELECT x.id, x.nombre_distrito AS distrito,
                    p.nombre_provincia AS provincia,
                    d.name AS departamento
               FROM ubigeo_distrito x
               JOIN ubigeo_provincia p    ON p.id = x.province_id
               JOIN ubigeo_departamento d ON d.id = p.department_id
              WHERE x.id = :id
              LIMIT 1',
            ['id' => $distritoId]
        );
    }
}
