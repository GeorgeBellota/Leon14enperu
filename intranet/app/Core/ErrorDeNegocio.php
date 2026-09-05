<?php
/**
 * ErrorDeNegocio — un «no se puede hacer esto» que SÍ se le puede enseñar al
 * usuario final: el DNI ya está inscrito, la convocatoria está cerrada, el
 * servicio elegido ya no existe.
 *
 * Existe porque `RuntimeException` no sirve para distinguirlo: PDOException
 * también hereda de ella. Al capturar RuntimeException para mostrar el
 * mensaje, un fallo de base de datos acababa impreso en pantalla con el nombre
 * de la base, la tabla y la columna —eso ocurrió, y por eso está esta clase—.
 *
 * Regla: el mensaje de un ErrorDeNegocio se muestra tal cual. El de cualquier
 * otra excepción, jamás.
 */

declare(strict_types=1);

namespace Intranet\Core;

use Exception;

final class ErrorDeNegocio extends Exception
{
    /** Campo del formulario al que apunta el problema, si es uno concreto. */
    public function __construct(string $mensaje, private string $campo = '_')
    {
        parent::__construct($mensaje);
    }

    public function campo(): string
    {
        return $this->campo;
    }
}
