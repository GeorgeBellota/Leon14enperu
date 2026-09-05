<?php
/**
 * ============================================================================
 *  Ubigeo · datos para los desplegables en cascada
 * ============================================================================
 *
 *  Devuelve JSON:
 *
 *      ubigeo.php?departamento=14   →  provincias de Lambayeque
 *      ubigeo.php?provincia=1401    →  distritos de Chiclayo
 *
 *  Sin sesión y sin testigo: son datos públicos —la división administrativa
 *  del Perú— y el formulario los necesita antes de que exista nada que
 *  proteger. No se expone ninguna otra tabla por aquí.
 *
 *  Cuando NO hay JavaScript, este archivo no se usa: el formulario recarga la
 *  página y los desplegables se rellenan desde el servidor.
 * ============================================================================
 */

declare(strict_types=1);

/** @var \Intranet\Publico\Sitio $sitio */
$sitio = require __DIR__ . '/intranet/app/Publico/arranque.php';

use Intranet\Models\Ubigeo;

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
// La división administrativa no cambia de un día para otro: se puede cachear
// un día entero y ahorrar la consulta en cada cambio de desplegable.
header('Cache-Control: public, max-age=86400');

try {
    $ubigeo = new Ubigeo($sitio->contenedor());

    $departamento = isset($_GET['departamento']) ? trim((string) $_GET['departamento']) : '';
    $provincia    = isset($_GET['provincia']) ? trim((string) $_GET['provincia']) : '';

    // Los identificadores de ubigeo son dígitos y nada más: dos para el
    // departamento, cuatro para la provincia. Se filtran aquí aunque después
    // vayan como parámetro preparado.
    //
    // Un parámetro presente pero con formato inválido se responde con una lista
    // VACÍA, no cayendo al caso siguiente. La primera versión encadenaba los
    // if y, al pedir «distritos de la provincia ‹basura›», devolvía la lista de
    // departamentos: una respuesta que no corresponde a la pregunta y que al
    // otro lado se pinta como si fuera correcta.
    if ($provincia !== '') {
        $valida = preg_match('/^\d{4}$/', $provincia) === 1;

        if (!$valida) {
            http_response_code(400);
        }

        echo json_encode([
            'ok'    => $valida,
            'items' => $valida ? $ubigeo->distritos($provincia) : [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($departamento !== '') {
        $valida = preg_match('/^\d{2}$/', $departamento) === 1;

        if (!$valida) {
            http_response_code(400);
        }

        echo json_encode([
            'ok'    => $valida,
            'items' => $valida ? $ubigeo->provincias($departamento) : [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Sin parámetros: la lista de departamentos, que es por donde se empieza.
    echo json_encode(['ok' => true, 'items' => $ubigeo->departamentos()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[ubigeo] ' . $e->getMessage());

    http_response_code(500);
    echo json_encode(['ok' => false, 'items' => []], JSON_UNESCAPED_UNICODE);
}
