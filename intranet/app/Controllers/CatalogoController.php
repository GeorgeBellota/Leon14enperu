<?php
/**
 * CatalogoController — las listas del formulario de voluntariado.
 *
 * ── Por qué aparece ahora ──────────────────────────────────────────────────
 *
 * La ruta `/catalogos` llevaba declarada desde el principio y apuntaba a esta
 * clase, que no existía: entrar daba 404. No se notó porque el permiso
 * `catalogos.editar` no estaba asignado a nadie.
 *
 * ── Qué gestiona ───────────────────────────────────────────────────────────
 *
 * De momento sólo el CUPO de cada jurisdicción. Los nombres y el orden siguen
 * viniendo de las migraciones: cambiarlos es raro y tiene consecuencias
 * —quedan escritos en 36 000 inscripciones—, así que no se abre esa puerta
 * hasta que alguien la pida.
 */

declare(strict_types=1);

namespace Intranet\Controllers;

use Intranet\Core\Auditoria;
use Intranet\Core\Controller;
use Intranet\Core\Request;
use Intranet\Models\Catalogo;

final class CatalogoController extends Controller
{
    public function listar(Request $peticion): void
    {
        $this->ver('catalogos/listar', [
            'titulo'         => 'Catálogos',
            'jurisdicciones' => (new Catalogo($this->c))->jurisdiccionesConCupo(false),
        ]);
    }

    /**
     * Guarda los topes.
     *
     * ── Bajar un tope por debajo de lo inscrito NO borra a nadie ──────────
     *
     * Poner 5 000 en una jurisdicción que lleva 20 000 la deja cerrada, y ya
     * está. Las 20 000 inscripciones siguen donde estaban. Es lo que pidió la
     * Conferencia Episcopal: un tope es una puerta, no una tijera.
     */
    public function guardar(Request $peticion): void
    {
        $this->exigirCsrf($peticion);

        $modelo  = new Catalogo($this->c);
        $enviado = $peticion->post('limite', []);
        $enviado = is_array($enviado) ? $enviado : [];

        $cambios = [];

        foreach ($modelo->jurisdicciones(false) as $j) {
            $id = (int) $j['id'];

            if (!array_key_exists($id, $enviado)) {
                continue;
            }

            $crudo = trim((string) $enviado[$id]);

            /* Vacío significa «sin tope», que no es lo mismo que cero: cero
               cerraría la jurisdicción a cal y canto, y para eso está el
               interruptor de activo. */
            if ($crudo === '') {
                $nuevo = null;
            } elseif (preg_match('/^\d{1,7}$/', $crudo) === 1) {
                $nuevo = (int) $crudo;
            } else {
                $this->conError(
                    'El tope de «' . $j['nombre'] . '» tiene que ser un número entero, o quedar vacío para no poner tope.',
                    '/catalogos'
                );

                return;
            }

            $antes = $j['limite'] === null ? null : (int) $j['limite'];

            if ($antes !== $nuevo) {
                $modelo->guardarLimite($id, $nuevo);
                $cambios[$j['clave']] = ($antes ?? 'sin tope') . ' → ' . ($nuevo ?? 'sin tope');
            }
        }

        if ($cambios === []) {
            $this->conExito('No había nada que cambiar.', '/catalogos');

            return;
        }

        Auditoria::registrar($this->c, 'editar', 'catalogos', null, [
            'accion' => 'cupos de jurisdicción',
            'topes'  => $cambios,
        ]);

        $this->conExito('Cupos guardados. El formulario ya lo refleja.', '/catalogos');
    }
}
