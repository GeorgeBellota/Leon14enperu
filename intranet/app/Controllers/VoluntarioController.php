<?php
/**
 * VoluntarioController — las inscripciones.
 *
 * Regla que atraviesa todo el archivo: los datos sensibles no salen a pantalla
 * salvo petición explícita, con permiso propio y dejando huella en auditoría.
 * El listado nunca los descifra; la ficha los muestra enmascarados hasta que
 * alguien pulsa «ver» y esa acción queda registrada.
 */

declare(strict_types=1);

namespace Intranet\Controllers;

use Intranet\Core\Auditoria;
use Intranet\Core\Controller;
use Intranet\Core\Request;
use Intranet\Core\Response;
use Intranet\Models\Catalogo;
use Intranet\Models\Ubigeo;
use Intranet\Models\Voluntario;

final class VoluntarioController extends Controller
{
    public function listar(Request $peticion): void
    {
        $modelo   = new Voluntario($this->c);
        $catalogo = new Catalogo($this->c);
        $filtros  = $this->filtros($peticion);

        $resultado = $modelo->listar($filtros, $peticion->entero('pagina', 1));

        $this->ver('voluntarios/listar', [
            'titulo'         => 'Inscripciones',
            'resultado'      => $resultado,
            'filtros'        => $filtros,
            'jurisdicciones' => $catalogo->jurisdicciones(false),
            'servicios'      => $catalogo->servicios(false),
            'estados'        => Voluntario::estados(),
            // Sólo los departamentos de los que ha llegado alguien: un
            // desplegable con los 25 del Perú obligaría a ir probando cuáles
            // devuelven algo.
            'departamentos'  => $modelo->departamentosConInscripciones(),
            'provincias'     => $modelo->provinciasConInscripciones($filtros['departamento_id']),
        ]);
    }

    /**
     * Ficha de una inscripción.
     *
     * Se llama `detalle` y no `ver` porque `ver()` es el método de la clase
     * base que pinta una vista: declararlo aquí con otra firma es un error
     * fatal de PHP, no una sobrecarga.
     *
     * @param array<string, string> $params
     */
    public function detalle(Request $peticion, array $params): void
    {
        $modelo = new Voluntario($this->c);
        $ficha  = $modelo->ficha((int) $params['id']);

        if ($ficha === null) {
            $this->conError('Esa inscripción no existe.', '/voluntarios');
        }

        // Los datos en claro sólo se calculan si se piden Y hay permiso.
        $revelado = null;
        if ($peticion->get('ver') === 'sensible' && $this->c->auth()->puede('voluntarios.datos_sensibles')) {
            $revelado = $modelo->revelar($ficha);
            Auditoria::accesoSensible($this->c, 'voluntarios', (int) $ficha['id'], 'dni,telefonos,direccion');
        }

        $ubigeo = new Ubigeo($this->c);

        // El nombre del departamento hace falta por separado para las
        // inscripciones en las que la provincia y el distrito se escribieron a
        // mano: ahí no hay cadena completa que resolver, pero el departamento
        // sí se eligió de la lista y merece mostrarse con su nombre.
        $departamentoNombre = '';
        if (!empty($ficha['ubigeo_departamento_id'])) {
            foreach ($ubigeo->departamentos() as $d) {
                if ((string) $d['id'] === (string) $ficha['ubigeo_departamento_id']) {
                    $departamentoNombre = (string) $d['nombre'];
                    break;
                }
            }
        }

        $this->ver('voluntarios/ficha', [
            'titulo'    => $ficha['codigo'],
            'v'         => $ficha,
            // Los nombres del ubigeo. Null en las inscripciones anteriores a
            // que existieran los desplegables, y también cuando la provincia o
            // el distrito se escribieron a mano y no se pudieron reconocer.
            'lugar'     => $ubigeo->porDistrito($ficha['ubigeo_distrito_id'] ?? null),
            'departamentoNombre' => $departamentoNombre,
            'revelado'  => $revelado,
            'historial' => $modelo->historial((int) $ficha['id']),
            'estados'   => Voluntario::estados(),
        ]);
    }

    /** @param array<string, string> $params */
    public function cambiarEstado(Request $peticion, array $params): void
    {
        $this->exigirCsrf($peticion);

        $id     = (int) $params['id'];
        $estado = (string) $peticion->post('estado', '');

        if (!array_key_exists($estado, Voluntario::estados())) {
            $this->conError('Ese estado no existe.', '/voluntarios/' . $id);
        }

        $nota = trim((string) $peticion->post('nota', ''));

        (new Voluntario($this->c))->cambiarEstado($id, $estado, $nota ?: null, $this->c->auth()->id());

        Auditoria::registrar($this->c, 'editar', 'voluntarios', $id, ['estado' => $estado]);

        $this->conExito('Estado actualizado.', '/voluntarios/' . $id);
    }

    /** @param array<string, string> $params */
    public function darDeBaja(Request $peticion, array $params): void
    {
        $this->exigirCsrf($peticion);

        $id     = (int) $params['id'];
        $motivo = trim((string) $peticion->post('motivo', ''));

        (new Voluntario($this->c))->darDeBaja($id, $this->c->auth()->id(), $motivo ?: null);

        Auditoria::registrar($this->c, 'borrar', 'voluntarios', $id, ['motivo' => $motivo]);

        $this->conExito('Inscripción dada de baja. Los datos se conservan en el histórico.', '/voluntarios');
    }

    /**
     * Exportación a CSV.
     *
     * El archivo NO lleva DNI ni teléfonos, ni siquiera con el permiso de datos
     * sensibles: una hoja de cálculo con tres mil DNI viaja por correo, se
     * copia a un pendrive y acaba en un escritorio compartido. Si hiciera falta
     * para la acreditación, tiene que ser una exportación distinta, firmada y
     * con su propio permiso.
     */
    public function exportar(Request $peticion): void
    {
        $filtros = $this->filtros($peticion);

        // `exportar()` y no `listar(..., 1, 5000)`.
        //
        // Aquí estuvo el fallo: se pedían cinco mil filas y el paginador las
        // recortaba a doscientas —su tope para pantallas— sin decir nada. El
        // CSV salía con doscientas líneas y con toda la pinta de estar
        // completo, así que quien lo abría creía tener el listado entero.
        /* ── El CSV lleva TODO ────────────────────────────────────────────
           Antes se dejaban fuera el DNI y los teléfonos a propósito. Se
           incluyen porque la organización los necesita para trabajar: sin el
           teléfono no se puede convocar a nadie, y sin el DNI no se acredita.

           Lo que no cambia es quién puede: hace falta el permiso
           `voluntarios.datos_sensibles`, el mismo que se exige para ver un
           número en la ficha. Quien no lo tenga sigue exportando las máscaras.

           Y queda registrado en auditoría con esa marca: un archivo con miles
           de documentos que sale del sistema tiene que dejar rastro de quién
           lo sacó y cuándo. */
        $conSensibles = $this->c->auth()->puede('voluntarios.datos_sensibles');
        $modelo = new Voluntario($this->c);

        // Se cuenta ANTES de empezar a escribir, para dejarlo en auditoría. Una
        // vez arranca la descarga ya no se puede registrar nada: las cabeceras
        // están enviadas y el archivo va saliendo.
        $total = $modelo->contar($filtros);

        Auditoria::registrar($this->c, 'exportar', 'voluntarios', null, [
            'filas'     => $total,
            'total'     => $total,
            'sensibles' => $conSensibles,
            'filtros'   => array_filter($filtros),
        ]);

        $estados = Voluntario::estados();

        /* El generador va traduciendo el estado sobre la marcha, sin construir
           una segunda lista con todo dentro: eso sería volver a cargar la tabla
           en memoria justo después de haber evitado hacerlo. */
        $filas = (static function () use ($modelo, $filtros, $conSensibles, $estados): iterable {
            foreach ($modelo->exportar($filtros, $conSensibles) as $f) {
                $f['estado'] = $estados[$f['estado']] ?? $f['estado'];

                yield $f;
            }
        })();

        // Las columnas, en el orden en que se leen en una hoja de cálculo:
        // quién es, cómo se le localiza, dónde vive y en qué está.
        $columnas = ['codigo' => 'Código', 'nombres' => 'Nombres y apellidos'];

        if ($conSensibles) {
            $columnas['dni']        = 'DNI';
            $columnas['nacimiento'] = 'Fecha de nacimiento';
        } else {
            $columnas['dni_mascara'] = 'DNI (oculto)';
        }

        $columnas['correo'] = 'Correo';

        if ($conSensibles) {
            $columnas['telefono']            = 'Teléfono';
            $columnas['emergencia_nombre']   = 'Contacto de emergencia';
            $columnas['emergencia_telefono'] = 'Teléfono de emergencia';
        } else {
            $columnas['telefono_mascara'] = 'Teléfono (oculto)';
        }

        $columnas['ubigeo_departamento'] = 'Departamento';
        $columnas['ubigeo_provincia']    = 'Provincia';
        $columnas['ubigeo_distrito']     = 'Distrito';

        // Provincia y distrito escritos a mano, cuando el ubigeo no se pudo
        // reconocer. Sin estas dos columnas, esas inscripciones saldrían con
        // la ubicación en blanco y parecerían incompletas cuando no lo están.
        $columnas['provincia'] = 'Provincia (escrita)';
        $columnas['distrito']  = 'Distrito (escrito)';

        if ($conSensibles) {
            $columnas['direccion'] = 'Dirección';
        }

        $columnas['jurisdiccion'] = 'Jurisdicción';
        $columnas['servicio']     = 'Servicio';
        $columnas['talla']        = 'Talla';
        $columnas['estado']       = 'Estado';
        $columnas['fase']         = 'Fase';
        $columnas['creado_en']    = 'Fecha de inscripción';

        Response::csv('inscripciones-' . date('Y-m-d') . '.csv', $filas, $columnas);
    }

    /** @return array<string, string> */
    private function filtros(Request $peticion): array
    {
        return [
            'texto'           => $peticion->texto('texto'),
            'dni'             => preg_replace('/\D/', '', $peticion->texto('dni')) ?? '',
            'jurisdiccion_id' => $peticion->texto('jurisdiccion_id'),
            'servicio_id'     => $peticion->texto('servicio_id'),
            'estado'          => $peticion->texto('estado'),

            // Los identificadores de ubigeo son dígitos: dos para el
            // departamento, cuatro para la provincia. Lo que no tenga esa
            // forma no llega a la consulta.
            'departamento_id' => $this->ubigeo($peticion->texto('departamento_id'), 2),
            'provincia_id'    => $this->ubigeo($peticion->texto('provincia_id'), 4),
            'desde'           => $this->fecha($peticion->texto('desde')),
            'hasta'           => $this->fecha($peticion->texto('hasta')),
            'orden'           => $peticion->texto('orden', 'creado_en'),
            'dir'             => $peticion->texto('dir', 'DESC'),
        ];
    }

    /** Una fecha que no tenga forma de fecha no entra en la consulta. */
    private function fecha(string $valor): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1 ? $valor : '';
    }

    /** Un código de ubigeo del número exacto de dígitos, o nada. */
    private function ubigeo(string $valor, int $digitos): string
    {
        return preg_match('/^\d{' . $digitos . '}$/', $valor) === 1 ? $valor : '';
    }
}
