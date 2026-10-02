<?php
/**
 * PaginaController — el CMS: páginas, secciones y sus bloques.
 */

declare(strict_types=1);

namespace Intranet\Controllers;

use Intranet\Cms\Plantillas;
use Intranet\Cms\Slug;
use Intranet\Core\Auditoria;
use Intranet\Core\Controller;
use Intranet\Core\Request;
use Intranet\Models\Catalogo;
use Intranet\Models\Medio;
use Intranet\Models\Pagina;

final class PaginaController extends Controller
{
    public function listar(Request $peticion): void
    {
        $modelo = new Pagina($this->c);

        $this->ver('paginas/listar', [
            'titulo'  => 'Páginas',
            'paginas' => $modelo->todas(),
            // El segundo nivel: las páginas que no son una página sino una
            // colección —Sedes contiene cuatro sedes, cada una con su propia
            // dirección—. Sin esto, quien buscaba dónde se edita
            // /sedes/chiclayo/ no lo encontraba en ninguna parte.
            'colecciones' => $modelo->piezasConPagina(),
        ]);
    }

    /** @param array<string, string> $params */
    public function secciones(Request $peticion, array $params): void
    {
        $modelo = new Pagina($this->c);
        $pagina = $modelo->porClave($params['clave']);

        if ($pagina === null) {
            $this->conError('Esa página no existe.', '/paginas');
        }

        $this->ver('paginas/secciones', [
            'titulo'     => $pagina['nombre'],
            'pagina'     => $pagina,
            'secciones'  => $modelo->secciones((int) $pagina['id']),
            // Las fichas con dirección propia de esta página, para poder
            // enseñarlas colgando de su sección en lugar de dejarlas dentro
            // de un formulario donde nadie las encuentra.
            'fichas'     => ($modelo->piezasConPagina()[$pagina['clave']] ?? []),
            'plantillas' => Plantillas::todas(),
        ]);
    }

    /**
     * Los datos que ve un buscador y los que ve quien comparte el enlace.
     *
     * Hasta ahora vivían escritos dentro de cada vista, así que cambiar el
     * título de una página en Google era desplegar código. Las columnas ya
     * estaban en la tabla desde el principio, sin que nadie las usara.
     *
     * @param array<string, string> $params
     */
    public function seo(Request $peticion, array $params): void
    {
        $modelo = new Pagina($this->c);
        $pagina = $modelo->porClave($params['clave'] ?? '');

        if ($pagina === null) {
            $this->conError('Esa página no existe.', '/paginas');
        }

        $this->ver('paginas/seo', [
            'titulo' => 'Buscadores · ' . $pagina['nombre'],
            'pagina' => $pagina,
            'medios' => (new Medio($this->c))->paraElegir(),
        ]);
    }

    /** @param array<string, string> $params */
    public function guardarSeo(Request $peticion, array $params): void
    {
        $this->exigirCsrf($peticion);

        $modelo = new Pagina($this->c);
        $pagina = $modelo->porClave($params['clave'] ?? '');

        if ($pagina === null) {
            $this->conError('Esa página no existe.', '/paginas');
        }

        $destino = '/paginas/' . $pagina['clave'] . '/seo';

        /* Vacío significa «usa lo que trae la página», no «déjalo en blanco».
           Por eso el 0 se convierte en null y no en cadena vacía. */
        $imagen = (int) $peticion->entero('og_imagen_id', 0);

        $modelo->guardarSeo(
            (string) $pagina['clave'],
            $peticion->texto('titulo_seo', ''),
            $peticion->texto('descripcion_seo', ''),
            $imagen > 0 ? $imagen : null,
            $this->c->auth()->id()
        );

        Auditoria::registrar($this->c, 'editar', 'paginas', (int) $pagina['id'], [
            'pagina' => $pagina['clave'],
            'accion' => 'datos para buscadores',
        ]);

        $this->conExito('Datos guardados. Ya salen en la página.', $destino);
    }

    /**
     * Cambia el orden en que salen las secciones de una página.
     *
     * ── Dos formas de llegar aquí, y las dos valen ───────────────────────
     *
     *   · `orden[]` con la lista entera de claves. Es lo que manda el
     *     navegador después de arrastrar.
     *   · `mover` = subir|bajar más la `clave` de una. Es lo que mandan las
     *     flechas, que son botones normales dentro de un formulario normal y
     *     funcionan sin una línea de JavaScript.
     *
     * La segunda no es un resto del pasado: es la que sigue funcionando el día
     * que el JavaScript falle, y la única que puede usar quien se mueve por el
     * panel con el teclado.
     *
     * @param array<string, string> $params
     */
    public function ordenar(Request $peticion, array $params): void
    {
        $this->exigirCsrf($peticion);

        $modelo = new Pagina($this->c);
        $pagina = $modelo->porClave($params['clave'] ?? '');

        if ($pagina === null) {
            $this->conError('Esa página no existe.', '/paginas');
        }

        $destino = '/paginas/' . $pagina['clave'];
        $actual  = array_column($modelo->secciones((int) $pagina['id']), 'clave');
        $pedido  = $peticion->post('orden');

        if (is_array($pedido) && $pedido !== []) {
            $nuevo = $pedido;
        } else {
            /* El camino de las flechas. Cada botón lleva su propio `name` y la
               clave como valor, que es la forma que tiene el HTML de decir cuál
               de veinte botones se pulsó: sólo el pulsado se envía. */
            $hacia = $peticion->texto('subir', '') !== '' ? 'subir'
                   : ($peticion->texto('bajar', '') !== '' ? 'bajar' : '');
            $clave = trim((string) $peticion->texto($hacia ?: 'subir', ''));

            $indice = $hacia === '' ? false : array_search($clave, $actual, true);

            if ($indice === false) {
                $this->conError('No se pudo mover esa sección.', $destino);
            }

            $vecina = $hacia === 'subir' ? $indice - 1 : $indice + 1;

            if ($vecina < 0 || $vecina >= count($actual)) {
                // Ya estaba arriba del todo o abajo del todo. No es un error:
                // simplemente no hay a dónde moverla.
                $this->redirigir($destino);
                return;
            }

            $nuevo = $actual;
            [$nuevo[$indice], $nuevo[$vecina]] = [$nuevo[$vecina], $nuevo[$indice]];
        }

        $movidas = $modelo->reordenarSecciones((int) $pagina['id'], $nuevo, $this->c->auth()->id());

        if ($movidas === 0) {
            $this->conError('No se pudo guardar el orden.', $destino);
        }

        Auditoria::registrar($this->c, 'editar', 'paginas', null, [
            'pagina' => $pagina['clave'],
            'accion' => 'orden de secciones',
            'orden'  => $nuevo,
        ]);

        $this->conExito('Orden guardado. Así salen ahora en la página.', $destino);
    }

    /** @param array<string, string> $params */
    public function editar(Request $peticion, array $params): void
    {
        [$pagina, $seccion] = $this->cargar($params);

        $this->ver('paginas/editar', [
            'titulo'    => $seccion['nombre'],
            'pagina'    => $pagina,
            'seccion'   => $seccion,
            'plantilla' => Plantillas::de((string) $seccion['plantilla']),
            // Contexto para la sección de servicios, cuyas tarjetas no salen de
            // `bloques` sino del catálogo: hay que decir dónde se editan.
            'servicios' => (new Catalogo($this->c))->servicios(false),
            // La biblioteca, para los selectores de imagen.
            'medios'    => (new Medio($this->c))->paraElegir(),
        ]);
    }

    /**
     * Publica u oculta una página entera.
     *
     * Oculta significa 404 en el sitio público, no «fuera del menú»: quien
     * tenga la dirección tampoco la ve. Es lo que permite preparar una sección
     * entera con su contenido y abrirla el día que toque.
     *
     * @param array<string, string> $params
     */
    public function publicar(Request $peticion, array $params): void
    {
        $this->exigirCsrf($peticion);

        $clave  = $params['clave'] ?? '';
        $modelo = new Pagina($this->c);
        $ahora  = $modelo->alternarPublicacion($clave, $this->c->auth()->id());

        if ($ahora === null) {
            $this->conError('Esa página no existe.', '/paginas');
        }

        Auditoria::registrar($this->c, 'editar', 'paginas', null, [
            'pagina' => $clave,
            'accion' => $ahora ? 'publicada' : 'ocultada',
        ]);

        $this->conExito(
            $ahora
                ? 'Página publicada: ya se ve en la web.'
                : 'Página oculta. Su contenido se conserva y vuelve al publicarla.',
            '/paginas'
        );
    }

    /** @param array<string, string> $params */
    public function guardar(Request $peticion, array $params): void
    {
        $this->exigirCsrf($peticion);

        [$pagina, $seccion] = $this->cargar($params);

        $plantilla = Plantillas::de((string) $seccion['plantilla']);
        $modelo    = new Pagina($this->c);

        // ── Campos propios de la sección ────────────────────────────────
        // Sólo se aceptan los que declara la plantilla. Un POST con campos de
        // más no puede escribir columnas que esta pantalla no muestra.
        $campos = ['activa' => $peticion->casilla('activa') ? 1 : 0];

        foreach ($plantilla['campos'] as $campo) {
            $columna = Plantillas::columna($campo);
            $valor   = trim((string) $peticion->post($columna, ''));

            // La imagen no es texto: es la clave de una fila de `medios`. Si no
            // se eligió ninguna, queda a null y la página pública se pinta sin
            // foto, que es exactamente lo que pasaba antes de tener biblioteca.
            if (Plantillas::campo($campo)['tipo'] === 'imagen') {
                $campos[$columna] = $valor === '' ? null : (int) $valor;

                continue;
            }

            $campos[$columna] = $valor === '' ? null : $valor;
        }

        // ── Claves de la columna JSON ───────────────────────────────────
        //
        // Se PARTE de lo que ya había, no de un array vacío.
        //
        // En `datos` conviven dos cosas: las claves que el formulario edita
        // —las que declara la plantilla— y marcas estructurales que pone una
        // migración y que ninguna pantalla muestra. La más importante es
        // «detalle», que es la que hace que las piezas de una sección tengan
        // página propia.
        //
        // Empezando de cero, esas marcas se perdían al guardar. En la práctica:
        // alguien entraba a Sedes, corregía una coma, pulsaba Guardar y
        // /sedes/lima/, /sedes/chiclayo/, /sedes/cusco/ y /sedes/pucallpa/
        // dejaban de existir —404— sin un solo mensaje de error. Y como el
        // formulario deja de pintar el campo de la dirección cuando «detalle»
        // no está, el siguiente Guardar se llevaba también los slugs.
        $datos = is_array($seccion['datos'] ?? null) ? $seccion['datos'] : [];

        foreach ($plantilla['datos'] as $clave => $definicion) {
            $datos[$clave] = $definicion['tipo'] === 'lista'
                ? $this->lineas((string) $peticion->post('datos_' . $clave, ''))
                : trim((string) $peticion->post('datos_' . $clave, ''));

            if ($datos[$clave] === '' || $datos[$clave] === []) {
                unset($datos[$clave]);
            }
        }
        $campos['datos'] = $datos;

        /* ── Las piezas ya no vienen por aquí ─────────────────────────
         *
         * Cada una tiene su pantalla y se guarda sola. Se pasa `null`, que
         * para el modelo significa «no las toques»: pasar una lista vacía
         * las borraría todas, y entonces corregir una coma en el titular de
         * Itinerario se llevaría por delante sus seis jornadas.
         */
        $modelo->guardarSeccion((int) $seccion['id'], $campos, null, $this->c->auth()->id());

        Auditoria::registrar($this->c, 'editar', 'secciones', (int) $seccion['id'], [
            'pagina'  => $pagina['clave'],
            'seccion' => $seccion['clave'],
        ]);

        $this->conExito(
            'Contenido guardado. Los cambios ya se ven en la web.',
            '/paginas/' . $pagina['clave']
        );
    }

    /**
     * @param array<string, string> $params
     * @return array{0: array<string,mixed>, 1: array<string,mixed>}
     */
    /* ══════════════════════════════════════════════════════════════════
       EL HISTORIAL
       ------------------------------------------------------------------
       Cada «Guardar» deja una copia del estado anterior y se conservan las
       diez últimas. Eso ya pasaba desde el principio; lo que faltaba era
       poder verlas y volver a una.

       La cuenta importa para que la pantalla no mienta: la copia fechada el
       martes no es «lo que se guardó el martes», es «cómo estaba justo antes
       de guardar el martes». Restaurarla deshace aquel cambio.
       ══════════════════════════════════════════════════════════════════ */

    /** @param array<string, string> $params */
    public function historial(Request $peticion, array $params): void
    {
        [$pagina, $seccion] = $this->cargar($params);

        $modelo    = new Pagina($this->c);
        $versiones = $modelo->versiones((int) $seccion['id'], true);

        /* Qué cambió en cada paso. La copia más reciente se compara con lo
           que hay publicado ahora; cada una de las demás, con la copia
           siguiente —que es el estado en el que la dejó su propio cambio—. */
        $antesQue = $seccion;

        foreach ($versiones as $i => $v) {
            $versiones[$i]['cambios'] = $v['contenido'] === null
                ? ['No se pudo leer esta copia']
                : $modelo->diferencias($v['contenido'], $antesQue);

            if ($v['contenido'] !== null) {
                $antesQue = $v['contenido'];
            }

            // Ya no hace falta y abulta: diez copias enteras en la vista.
            unset($versiones[$i]['contenido']);
        }

        $this->ver('paginas/historial', [
            'titulo'    => 'Historial · ' . $seccion['nombre'],
            'pagina'    => $pagina,
            'seccion'   => $seccion,
            'plantilla' => Plantillas::de((string) $seccion['plantilla']),
            'versiones' => $versiones,
        ]);
    }

    /** @param array<string, string> $params */
    public function version(Request $peticion, array $params): void
    {
        [$pagina, $seccion] = $this->cargar($params);

        $modelo  = new Pagina($this->c);
        $version = $modelo->version((int) $seccion['id'], (int) $params['id']);
        $vuelta  = '/paginas/' . $pagina['clave'] . '/' . $seccion['clave'] . '/historial';

        if ($version === null) {
            $this->conError('Esa copia no existe o no es de esta sección.', $vuelta);
        }

        $this->ver('paginas/version', [
            'titulo'    => 'Copia del ' . $version['creado_en'],
            'pagina'    => $pagina,
            'seccion'   => $seccion,
            'plantilla' => Plantillas::de((string) $seccion['plantilla']),
            'version'   => $version,
            'cambios'   => $modelo->diferencias($version['contenido'], $seccion),
            // Para poder enseñar las fotos de aquella versión, no sus números.
            'medios'    => (new Medio($this->c))->paraElegir(),
        ]);
    }

    /** @param array<string, string> $params */
    public function restaurarVersion(Request $peticion, array $params): void
    {
        $this->exigirCsrf($peticion);

        [$pagina, $seccion] = $this->cargar($params);

        $modelo = new Pagina($this->c);
        $id     = (int) $params['id'];
        $enSec  = '/paginas/' . $pagina['clave'] . '/' . $seccion['clave'];

        $avisos = $modelo->restaurar((int) $seccion['id'], $id, $this->c->auth()->id());

        if ($avisos === null) {
            $this->conError('Esa copia no existe o no es de esta sección.', $enSec . '/historial');
        }

        Auditoria::registrar($this->c, 'restaurar', 'secciones', (int) $seccion['id'], [
            'pagina'  => $pagina['clave'],
            'seccion' => $seccion['clave'],
            'version' => $id,
        ]);

        /* Se dice que esto también se deshace. Es la pregunta que se hace
           cualquiera al pulsar «Restaurar», y la respuesta tranquiliza. */
        $mensaje = 'Sección restaurada. Antes de hacerlo se guardó una copia de '
                 . 'cómo estaba, así que esto también se puede deshacer.';

        if ($avisos !== []) {
            $mensaje .= ' ' . implode(' ', $avisos);
        }

        $this->conExito($mensaje, $enSec);
    }

    /* ══════════════════════════════════════════════════════════════════
       UNA PIEZA POR PANTALLA
       ------------------------------------------------------------------
       Una sección con trece comisiones era un formulario de 161 campos en
       el que corregir una coma obligaba a bajar por las otras doce y a
       volver a guardarlas todas. Ahora la sección enseña la lista y cada
       pieza se abre, se corrige y se guarda sola.
       ══════════════════════════════════════════════════════════════════ */

    /** @param array<string, string> $params */
    public function pieza(Request $peticion, array $params): void
    {
        [$pagina, $seccion] = $this->cargar($params);

        $modelo = new Pagina($this->c);
        $pieza  = $modelo->pieza((int) $seccion['id'], (int) $params['id']);

        if ($pieza === null) {
            $this->conError('Esa pieza no existe o no es de esta sección.',
                '/paginas/' . $pagina['clave'] . '/' . $seccion['clave']);
        }

        $plantilla = Plantillas::de((string) $seccion['plantilla']);

        if ($plantilla['bloques'] === null) {
            $this->conError('Esta sección no tiene piezas.',
                '/paginas/' . $pagina['clave'] . '/' . $seccion['clave']);
        }

        $hermanas = $seccion['bloques'];
        $posicion = 0;

        foreach ($hermanas as $n => $h) {
            if ((int) $h['id'] === (int) $pieza['id']) {
                $posicion = $n + 1;
            }
        }

        $this->ver('paginas/pieza', [
            'titulo'    => $pieza['titulo'] ?: $plantilla['bloques']['nombre'],
            'pagina'    => $pagina,
            'seccion'   => $seccion,
            'plantilla' => $plantilla,
            'pieza'     => $pieza,
            'posicion'  => $posicion,
            'cuantas'   => count($hermanas),
            'medios'    => (new Medio($this->c))->paraElegir(),
        ]);
    }

    /** @param array<string, string> $params */
    public function guardarPieza(Request $peticion, array $params): void
    {
        $this->exigirCsrf($peticion);

        [$pagina, $seccion] = $this->cargar($params);

        $modelo = new Pagina($this->c);
        $id     = (int) $params['id'];
        $pieza  = $modelo->pieza((int) $seccion['id'], $id);
        $vuelta = '/paginas/' . $pagina['clave'] . '/' . $seccion['clave'];

        if ($pieza === null) {
            $this->conError('Esa pieza no existe o no es de esta sección.', $vuelta);
        }

        $plantilla = Plantillas::de((string) $seccion['plantilla']);

        if ($plantilla['bloques'] === null) {
            $this->conError('Esta sección no tiene piezas.', $vuelta);
        }

        $def    = $plantilla['bloques'];
        $campos = ['activo' => $peticion->casilla('activo') ? 1 : 0];

        // ── La dirección propia ─────────────────────────────────────────
        //
        // Sólo en las secciones marcadas con «detalle». En las demás no se
        // guarda slug: darle dirección a una lámina del carrusel crearía una
        // URL que nadie enlaza y que compite en Google con la página buena.
        if (!empty($seccion['datos']['detalle'])) {
            $suyo = Slug::normalizar((string) $peticion->post('slug', ''));

            if ($suyo === '' || $suyo === 'pieza') {
                $suyo = Slug::desde(
                    (string) $peticion->post('titulo', ''),
                    fn (string $g): bool => $modelo->slugDePiezaOcupado((int) $seccion['id'], $g, $id)
                );
            }

            /* Si lo escribió a mano y ya está cogido, no se corrige por
               detrás: se le dice. Cambiarlo en silencio dejaría la pieza en
               una dirección que no es la que pidió, y sin avisar. */
            if ($suyo !== '' && $modelo->slugDePiezaOcupado((int) $seccion['id'], $suyo, $id)) {
                $this->conError(
                    'Ya hay otra ficha de esta sección en la dirección «' . $suyo . '».',
                    $vuelta . '/piezas/' . $id
                );
            }

            $campos['slug'] = $suyo !== '' ? $suyo : null;
        }

        // ── Los campos que declara la plantilla ─────────────────────────
        foreach ($def['campos'] as $campo) {
            $columna = Plantillas::columna($campo);

            if (Plantillas::campo($campo)['tipo'] === 'imagen') {
                $elegida = trim((string) $peticion->post($columna, ''));
                $campos[$columna] = $elegida === '' ? null : (int) $elegida;

                continue;
            }

            /* Los renglones se respetan, pero se normalizan: un navegador
               manda CRLF y otro LF, y la vista pública los pasa por nl2br().
               Sin esto, el mismo texto escrito desde dos equipos daría saltos
               distintos. */
            $valor = str_replace(["\r\n", "\r"], "\n", (string) $peticion->post($columna, ''));
            $valor = trim($valor);

            $campos[$columna] = $valor === '' ? null : $valor;
        }

        // ── La columna JSON ─────────────────────────────────────────────
        //
        // Se parte de lo que trae el campo oculto: en `datos` puede haber
        // claves que pone una migración y que esta pantalla no enseña. Si se
        // empezara de cero, se perderían al primer Guardar.
        $extra = json_decode((string) $peticion->post('datos_extra', ''), true);
        $datos = is_array($extra) ? $extra : [];

        foreach ($def['datos'] ?? [] as $clave => $definicion) {
            $entrada = (string) $peticion->post('datos_' . $clave, '');

            $valor = $definicion['tipo'] === 'lista'
                ? $this->lineas($entrada)
                : trim(str_replace(["\r\n", "\r"], "\n", $entrada));

            if ($valor !== '' && $valor !== []) {
                $datos[$clave] = $valor;
            } else {
                unset($datos[$clave]);
            }
        }

        $campos['datos'] = $datos;

        if (!$modelo->guardarPieza((int) $seccion['id'], $id, $campos, $this->c->auth()->id())) {
            $this->conError('No se pudo guardar esa pieza.', $vuelta);
        }

        Auditoria::registrar($this->c, 'editar', 'bloques', $id, [
            'pagina'  => $pagina['clave'],
            'seccion' => $seccion['clave'],
        ]);

        $this->conExito('Guardado. El cambio ya se ve en la web.',
            $vuelta . '#pieza-' . $id);
    }

    /** @param array<string, string> $params */
    public function nuevaPieza(Request $peticion, array $params): void
    {
        $this->exigirCsrf($peticion);

        [$pagina, $seccion] = $this->cargar($params);

        $modelo = new Pagina($this->c);
        $vuelta = '/paginas/' . $pagina['clave'] . '/' . $seccion['clave'];

        $plantilla = Plantillas::de((string) $seccion['plantilla']);

        if ($plantilla['bloques'] === null) {
            $this->conError('Esta sección no tiene piezas.', $vuelta);
        }

        $tope    = (int) ($plantilla['bloques']['maximo'] ?? 20);
        $cuantas = count($seccion['bloques']);

        if ($cuantas >= $tope) {
            $this->conError(
                'Esta sección admite ' . $tope . ' como máximo. Quita alguna antes de añadir otra.',
                $vuelta
            );
        }

        $id = $modelo->crearPieza((int) $seccion['id'], $this->c->auth()->id());

        Auditoria::registrar($this->c, 'crear', 'bloques', $id, [
            'pagina'  => $pagina['clave'],
            'seccion' => $seccion['clave'],
        ]);

        // Derecho a su pantalla: se crea vacía y oculta, y lo siguiente que
        // hace falta es rellenarla.
        $this->conExito(
            'Ficha creada. Rellénala y marca «Visible» cuando esté lista.',
            $vuelta . '/piezas/' . $id
        );
    }

    /** @param array<string, string> $params */
    public function moverPieza(Request $peticion, array $params): void
    {
        $this->exigirCsrf($peticion);

        [$pagina, $seccion] = $this->cargar($params);

        $hacia  = $peticion->post('hacia') === 'subir' ? 'subir' : 'bajar';
        $vuelta = '/paginas/' . $pagina['clave'] . '/' . $seccion['clave'];
        $modelo = new Pagina($this->c);

        if (!$modelo->moverPieza((int) $seccion['id'], (int) $params['id'], $hacia, $this->c->auth()->id())) {
            $this->conError('Esa pieza no existe o no es de esta sección.', $vuelta);
        }

        $this->conExito('Orden cambiado.', $vuelta . '#pieza-' . (int) $params['id']);
    }

    /** @param array<string, string> $params */
    public function borrarPieza(Request $peticion, array $params): void
    {
        $this->exigirCsrf($peticion);

        [$pagina, $seccion] = $this->cargar($params);

        $vuelta = '/paginas/' . $pagina['clave'] . '/' . $seccion['clave'];
        $modelo = new Pagina($this->c);
        $id     = (int) $params['id'];

        if (!$modelo->borrarPieza((int) $seccion['id'], $id, $this->c->auth()->id())) {
            $this->conError('Esa pieza no existe o no es de esta sección.', $vuelta);
        }

        Auditoria::registrar($this->c, 'borrar', 'bloques', $id, [
            'pagina'  => $pagina['clave'],
            'seccion' => $seccion['clave'],
        ]);

        $this->conExito('Ficha borrada.', $vuelta);
    }

    private function cargar(array $params): array
    {
        $modelo = new Pagina($this->c);
        $pagina = $modelo->porClave($params['clave']);

        if ($pagina === null) {
            $this->conError('Esa página no existe.', '/paginas');
        }

        $seccion = $modelo->seccion((int) $pagina['id'], $params['seccion']);

        if ($seccion === null) {
            $this->conError('Esa sección no existe.', '/paginas/' . $pagina['clave']);
        }

        return [$pagina, $seccion];
    }

    /**
     * Un textarea con una línea por elemento → array. Es la forma más simple
     * de editar una lista sin montar un widget de arrastrar y soltar.
     *
     * @return array<int, string>
     */
    private function lineas(string $texto): array
    {
        $lineas = preg_split('/\r\n|\r|\n/', $texto) ?: [];

        return array_values(array_filter(array_map('trim', $lineas), static fn ($l) => $l !== ''));
    }
}
