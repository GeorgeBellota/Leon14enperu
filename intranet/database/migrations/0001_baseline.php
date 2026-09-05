<?php
/**
 * 0001 · BASELINE
 *
 * No define nada por su cuenta: ejecuta database/schema.sql, que es la única
 * fuente de verdad del esquema inicial. Así no hay dos copias del DDL que
 * puedan acabar diciendo cosas distintas.
 *
 * A partir de aquí, cada cambio del esquema es una migración .sql nueva y
 * schema.sql ya no se toca.
 */

declare(strict_types=1);

return static function (PDO $pdo, array $config): void {
    $ruta = dirname(__DIR__) . '/schema.sql';

    $sql = file_get_contents($ruta);
    if ($sql === false) {
        throw new RuntimeException("No se pudo leer {$ruta}");
    }

    // schema.sql trae su propio CREATE DATABASE / USE porque también se importa
    // a mano desde phpMyAdmin. Aquí la conexión ya está posicionada en la base
    // correcta, así que esas dos líneas sobran: si el nombre de la base en
    // config.local.php no es «leon14peru», dejarlas escribiría en la base
    // equivocada.
    $sql = preg_replace('/^\s*CREATE\s+DATABASE\b.*?;\s*$/ims', '', $sql) ?? $sql;
    $sql = preg_replace('/^\s*USE\s+`?\w+`?\s*;\s*$/im', '', $sql) ?? $sql;

    $pdo->exec($sql);
};
