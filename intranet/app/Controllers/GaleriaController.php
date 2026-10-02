<?php
/**
 * ============================================================================
 *  GaleriaController — la galería de Multimedia.
 * ============================================================================
 *
 *  Dos pantallas:
 *
 *    /galeria              las actividades, con cuántas fotografías y fechas
 *    /galeria/{id}         una actividad: subir, describir, mover, ordenar
 *
 *  ── Por qué no vive en «Páginas» ────────────────────────────────────────
 *
 *  Porque el formulario de una sección no admite archivos, y aquí hace falta
 *  justo eso: subir muchas fotografías de una vez, describirlas y moverlas de
 *  fecha sin que lo ya escrito se mueva. Es el mismo motivo por el que
 *  Documentos tiene pantalla propia.
 *
 *  ── Por qué sube aquí y no manda a la biblioteca ────────────────────────
 *
 *  Para no obligar a dos viajes. Al subir, la fotografía entra en `medios`
 *  —la biblioteca de siempre, exactamente igual que si se hubiera subido en
 *  Imágenes— y además se apunta en la actividad y la fecha elegidas. Una sola
 *  operación, y la fotografía queda disponible para el resto del sitio.
 *
 *  También se puede traer una que YA esté en la biblioteca, sin volver a
 *  subirla: es lo que evita tener la misma foto dos veces en disco.
 */

declare(strict_types=1);

namespace Intranet\Controllers;

use Intranet\Core\Adjunto;
use Intranet\Core\Auditoria;
use Intranet\Core\Controller;
use Intranet\Core\ErrorDeNegocio;
use Intranet\Core\Imagen;
use Intranet\Core\Request;
use Intranet\Models\Galeria;
use Intranet\Models\Medio;

final class GaleriaController extends Controller
{
    private const CARPETA = 'assets/subidos/paginas';

    private function carpeta(): string
    {
        return dirname(__DIR__, 3) . '/' . self::CARPETA;
    }

    private function galeria(): Galeria
    {
        return new Galeria($this->c);
    }

    // ── Pantalla 1 · las actividades ───────────────────────────────────

    public function listar(Request $peticion): void
    {
        $this->ver('galeria/listar', [
            'titulo'      => 'Multimedia',
            'actividades' => $this->galeria()->actividades(),
        ]);
    }

    public function crearActividad(Request $peticion): void
    {
        $this->exigirCsrf($peticion);

        $nombre = trim($peticion->texto('nombre', ''));

        if ($nombre === '') {
            $this->conError('Escribe el nombre de la actividad.', '/galeria');
        }

        $id = $this->galeria()->crearActividad($nombre, $peticion->texto('rotulo', 'Actividades'));

        Auditoria::registrar($this->c, 'actividad.crear', 'galeria_actividades', $id, ['nombre' => $nombre]);

        $this->conExito('Actividad creada. Ya puedes subirle fotografías.', '/galeria/' . $id);
    }

    public function guardarActividad(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $id = (int) ($params['id'] ?? 0);

        if ($this->galeria()->actividad($id) === null) {
            $this->conError('Esa actividad ya no existe.', '/galeria');
        }

        $nombre = trim($peticion->texto('nombre', ''));

        if ($nombre === '') {
            $this->conError('La actividad necesita un nombre.', '/galeria/' . $id);
        }

        $this->galeria()->guardarActividad(
            $id,
            $nombre,
            $peticion->texto('rotulo', 'Actividades'),
            $peticion->casilla('activa')
        );

        Auditoria::registrar($this->c, 'actividad.editar', 'galeria_actividades', $id, ['nombre' => $nombre]);

        $this->conExito('Actividad guardada.', '/galeria/' . $id);
    }

    public function borrarActividad(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $id        = (int) ($params['id'] ?? 0);
        $actividad = $this->galeria()->actividad($id);

        if ($actividad === null) {
            $this->conError('Esa actividad ya no existe.', '/galeria');
        }

        $cuantas = $this->galeria()->cuantas($id);
        $this->galeria()->borrarActividad($id);

        Auditoria::registrar($this->c, 'actividad.borrar', 'galeria_actividades', $id, [
            'nombre' => $actividad['nombre'],
            'fotos'  => $cuantas,
        ]);

        /* Se dice expresamente que los archivos siguen: si no, quien borra una
           actividad con ochenta fotografías se queda con la duda de si acaba
           de vaciar la biblioteca. */
        $this->conExito(
            $cuantas === 0
                ? 'Actividad borrada.'
                : "Actividad borrada. Las {$cuantas} fotografías siguen en la biblioteca de imágenes.",
            '/galeria'
        );
    }

    public function moverActividad(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $this->galeria()->moverActividad(
            (int) ($params['id'] ?? 0),
            $peticion->texto('direccion', 'bajar') === 'subir' ? -1 : 1
        );

        $this->redirigir('/galeria');
    }

    // ── Pantalla 2 · una actividad ─────────────────────────────────────

    public function ver1(Request $peticion, array $params = []): void
    {
        $id        = (int) ($params['id'] ?? 0);
        $galeria   = $this->galeria();
        $actividad = $galeria->actividad($id);

        if ($actividad === null) {
            $this->conError('Esa actividad ya no existe.', '/galeria');
        }

        /* La fecha que se está mirando. Vacío = todas. Se valida aquí y no en
           la vista: lo que llega por la URL no decide una consulta. */
        $fecha = $this->fechaValida($peticion->texto('fecha', ''));

        $this->ver('galeria/actividad', [
            'titulo'      => $actividad['nombre'],
            'actividad'   => $actividad,
            'fechas'      => $galeria->fechas($id, false),
            'fotos'       => $galeria->fotos($id, $fecha),
            'fechaActiva' => $fecha,
            'actividades' => $galeria->actividades(),
            'biblioteca'  => (new Medio($this->c))->paraElegir(),
            'carpeta'     => self::CARPETA,
            'tope'        => Galeria::TOPE_POR_ACTIVIDAD,
        ]);
    }

    /**
     * Sube fotografías nuevas y las mete en la actividad.
     *
     * Admite varias de una vez: es lo que convierte «subir las ochenta fotos
     * del acto» en una operación en lugar de ochenta.
     */
    public function subir(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $id      = (int) ($params['id'] ?? 0);
        $galeria = $this->galeria();

        if ($galeria->actividad($id) === null) {
            $this->conError('Esa actividad ya no existe.', '/galeria');
        }

        $destino = '/galeria/' . $id;

        /* Igual que en la biblioteca: un $_FILES vacío con CONTENT_LENGTH por
           encima de cero significa que PHP descartó la petición por tamaño, no
           que no se eligiera archivo. Decirlo ahorra diez reintentos. */
        $archivos = $_FILES['fotos'] ?? null;

        if (!is_array($archivos) || !isset($archivos['name'])) {
            if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && $_POST === []) {
                $this->conError(
                    'Las fotografías pesan más de lo que admite el servidor. Súbelas en '
                    . 'tandas más pequeñas, o pide que suban «post_max_size» en el PHP.',
                    $destino
                );
            }

            $this->conError('Elige al menos una fotografía.', $destino);
        }

        $fecha  = $this->fechaDelFormulario($peticion);
        $cuenta = is_array($archivos['name']) ? count($archivos['name']) : 1;
        $hueco  = Galeria::TOPE_POR_ACTIVIDAD - $galeria->cuantas($id);

        if ($hueco <= 0) {
            $this->conError(
                'Esta actividad ya tiene ' . Galeria::TOPE_POR_ACTIVIDAD . ' fotografías, '
                . 'que es el máximo. Crea otra actividad o reparte por fechas.',
                $destino
            );
        }

        $subidas = 0;
        $fallos  = [];

        for ($i = 0; $i < $cuenta && $subidas < $hueco; $i++) {
            $archivo = [
                'name'     => $archivos['name'][$i]     ?? '',
                'tmp_name' => $archivos['tmp_name'][$i] ?? '',
                'size'     => $archivos['size'][$i]     ?? 0,
                'error'    => $archivos['error'][$i]    ?? UPLOAD_ERR_NO_FILE,
            ];

            if ((int) $archivo['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            try {
                $medioId = $this->guardarUna($archivo);
                $galeria->anadirFoto($id, $medioId, $fecha, '');
                $subidas++;
            } catch (ErrorDeNegocio $e) {
                /* Una que falle no tumba la tanda: se anota y se sigue. Con
                   ochenta fotografías, perderlas todas porque la número nueve
                   estaba corrupta sería insufrible. */
                $fallos[] = Adjunto::nombreLegible((string) $archivo['name']) . ': ' . $e->getMessage();
            }
        }

        Auditoria::registrar($this->c, 'subir', 'galeria_actividades', $id, [
            'subidas' => $subidas,
            'fallos'  => count($fallos),
            'fecha'   => $fecha,
        ]);

        if ($subidas === 0) {
            $this->conError(
                $fallos === [] ? 'No se subió ninguna fotografía.' : implode(' · ', array_slice($fallos, 0, 3)),
                $destino
            );
        }

        $aviso = $subidas . ($subidas === 1 ? ' fotografía subida' : ' fotografías subidas');

        if ($fallos !== []) {
            $aviso .= '. ' . count($fallos) . ' no se pudieron: ' . implode(' · ', array_slice($fallos, 0, 3));
        }

        $this->conExito($aviso . '.', $destino . ($fecha !== null ? '?fecha=' . $fecha : ''));
    }

    /** Trae a la actividad fotografías que YA están en la biblioteca. */
    public function anadirDeBiblioteca(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $id      = (int) ($params['id'] ?? 0);
        $galeria = $this->galeria();

        if ($galeria->actividad($id) === null) {
            $this->conError('Esa actividad ya no existe.', '/galeria');
        }

        $destino = '/galeria/' . $id;
        $medios  = $peticion->post('medios', []);
        $medios  = is_array($medios) ? array_map('intval', $medios) : [];

        if ($medios === []) {
            $this->conError('No elegiste ninguna imagen de la biblioteca.', $destino);
        }

        $fecha    = $this->fechaValida($peticion->texto('fecha', ''));
        $anadidas = 0;
        $repes    = 0;

        foreach ($medios as $medioId) {
            if ($galeria->cuantas($id) >= Galeria::TOPE_POR_ACTIVIDAD) {
                break;
            }

            $galeria->anadirFoto($id, $medioId, $fecha, '') ? $anadidas++ : $repes++;
        }

        Auditoria::registrar($this->c, 'anadir', 'galeria_actividades', $id, [
            'anadidas' => $anadidas,
            'repetidas' => $repes,
        ]);

        $aviso = $anadidas === 0
            ? 'Esas fotografías ya estaban en esta fecha.'
            : $anadidas . ($anadidas === 1 ? ' fotografía añadida' : ' fotografías añadidas')
              . ($repes > 0 ? '. ' . $repes . ' ya estaban.' : '.');

        $this->conExito($aviso, $destino . ($fecha !== null ? '?fecha=' . $fecha : ''));
    }

    /** La descripción que se lee en el modal, y si la fotografía se publica. */
    public function guardarFoto(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $id      = (int) ($params['id'] ?? 0);
        $fotoId  = (int) ($params['foto'] ?? 0);

        $this->galeria()->guardarFoto(
            $fotoId,
            $peticion->texto('descripcion', ''),
            $peticion->casilla('activa')
        );

        $this->redirigir('/galeria/' . $id . $this->vuelta($peticion));
    }

    /** Mueve las seleccionadas a otra fecha, a otra actividad, o a las dos. */
    public function mover(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $id      = (int) ($params['id'] ?? 0);
        $destino = '/galeria/' . $id;

        $ids = $peticion->post('fotos', []);
        $ids = is_array($ids) ? array_map('intval', $ids) : [];

        if ($ids === []) {
            $this->conError('No seleccionaste ninguna fotografía.', $destino . $this->vuelta($peticion));
        }

        /* «Cambiar la fecha» y «poner fecha vacía» son cosas distintas y no se
           distinguen mirando sólo el valor: una fotografía sin día es legítima.
           Por eso el formulario manda una casilla aparte. */
        $cambiarFecha = $peticion->texto('cambiar_fecha', '') !== '';
        $fecha        = $this->fechaValida($peticion->texto('fecha_destino', ''));

        $otraActividad = (int) $peticion->texto('actividad_destino', '0');
        $otraActividad = $otraActividad > 0 && $otraActividad !== $id ? $otraActividad : null;

        if ($otraActividad !== null && $this->galeria()->actividad($otraActividad) === null) {
            $this->conError('La actividad de destino ya no existe.', $destino);
        }

        $movidas = $this->galeria()->mover($ids, $otraActividad, $fecha, $cambiarFecha);

        if ($movidas === 0) {
            $this->conError('No elegiste a dónde moverlas.', $destino . $this->vuelta($peticion));
        }

        Auditoria::registrar($this->c, 'mover', 'galeria_actividades', $id, [
            'fotos'     => $movidas,
            'a_fecha'   => $cambiarFecha ? ($fecha ?? '(sin fecha)') : null,
            'a_actividad' => $otraActividad,
        ]);

        $this->conExito(
            $movidas . ($movidas === 1 ? ' fotografía movida.' : ' fotografías movidas.'),
            '/galeria/' . ($otraActividad ?? $id)
        );
    }

    /** Las saca de la galería. NO borra el archivo de la biblioteca. */
    public function quitar(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $id      = (int) ($params['id'] ?? 0);
        $destino = '/galeria/' . $id . $this->vuelta($peticion);

        $ids = $peticion->post('fotos', []);
        $ids = is_array($ids) ? array_map('intval', $ids) : [];

        if ($ids === []) {
            $this->conError('No seleccionaste ninguna fotografía.', $destino);
        }

        $quitadas = $this->galeria()->quitar($ids);

        Auditoria::registrar($this->c, 'quitar', 'galeria_actividades', $id, ['fotos' => $quitadas]);

        $this->conExito(
            $quitadas . ($quitadas === 1 ? ' fotografía quitada de la galería. Sigue' : ' fotografías quitadas de la galería. Siguen')
            . ' en la biblioteca de imágenes.',
            $destino
        );
    }

    public function ordenar(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $this->galeria()->ordenar(
            (int) ($params['foto'] ?? 0),
            $peticion->texto('direccion', 'bajar') === 'subir' ? -1 : 1
        );

        $this->redirigir('/galeria/' . (int) ($params['id'] ?? 0) . $this->vuelta($peticion));
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    /**
     * Guarda UNA fotografía en la biblioteca, igual que haría Imágenes.
     *
     * Es el mismo camino que MedioController::subir, con el mismo guardado del
     * original: la fotografía queda disponible para el resto del sitio, no
     * sólo para la galería.
     */
    private function guardarUna(array $archivo): int
    {
        $almacen  = new Adjunto($this->carpeta(), 20);
        $subida   = $almacen->imagen($archivo, 'img');
        $completa = dirname(__DIR__, 3) . '/' . $subida;

        $base     = pathinfo($subida, PATHINFO_FILENAME);
        $motor    = new Imagen($this->carpeta());
        $esVector = str_ends_with(strtolower($subida), '.svg');

        $procesada = $esVector
            ? $motor->vector($completa, $base)
            : $motor->derivar($completa, $base);

        /* El original se conserva: es lo que se entrega cuando alguien pulsa
           «Descargar» en la galería. En los SVG el original ES el archivo que
           sirve la web, así que no se apunta aparte. */
        $original     = $esVector ? null : $subida;
        $pesoOriginal = $esVector ? null : (int) @filesize($completa);

        $nombre = Adjunto::nombreLegible((string) ($archivo['name'] ?? 'imagen'));

        return (new Medio($this->c))->registrar(
            $procesada,
            $nombre,
            '',          // el texto alternativo se escribe después, por fotografía
            false,
            $this->c->auth()->id(),
            $original,
            $pesoOriginal
        );
    }

    /**
     * La fecha elegida en el formulario de subida.
     *
     * Son dos campos y no uno: un grupo de opciones con las fechas que YA
     * existen —para que elegir la de siempre sea un clic— y, al final, «Otra
     * fecha» con su calendario. Teclear la fecha pudiendo elegirla es como se
     * crea una pestaña huérfana con una sola fotografía dentro.
     */
    private function fechaDelFormulario(Request $peticion): ?string
    {
        $modo = trim($peticion->texto('fecha_modo', ''));

        if ($modo === 'nueva') {
            return $this->fechaValida($peticion->texto('fecha_nueva', ''));
        }

        return $this->fechaValida($modo);
    }

    /**
     * Una fecha de la URL o del formulario, o null.
     *
     * Se exige el formato exacto y que la fecha EXISTA: «2026-02-31» pasa un
     * preg_match y no es un día del calendario.
     */
    private function fechaValida(string $valor): ?string
    {
        $valor = trim($valor);

        if ($valor === '' || preg_match('~^\d{4}-\d{2}-\d{2}$~', $valor) !== 1) {
            return null;
        }

        [$a, $m, $d] = array_map('intval', explode('-', $valor));

        return checkdate($m, $d, $a) ? $valor : null;
    }

    /** Conserva la fecha que se estaba mirando al volver de una acción. */
    private function vuelta(Request $peticion): string
    {
        $fecha = $this->fechaValida($peticion->texto('fecha_vista', ''));

        return $fecha !== null ? '?fecha=' . $fecha : '';
    }
}
