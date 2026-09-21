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

        // ── Bloques ─────────────────────────────────────────────────────
        $bloques = [];

        if ($plantilla['bloques'] !== null) {
            $entrada = $peticion->post('bloques', []);
            $entrada = is_array($entrada) ? $entrada : [];

            /* ¿Las piezas de esta sección tienen página propia? Lo dice su
               columna `datos`, no la plantilla: la misma plantilla de
               tarjetas se usa para las sedes —que sí la tienen— y para los
               accesos de la portada, que no. */
            $conDetalle  = !empty($seccion['datos']['detalle']);
            $slugsUsados = [];

            foreach ($entrada as $fila) {
                if (!is_array($fila)) {
                    continue;
                }

                // Una fila del todo vacía es una que el editor añadió y no
                // llegó a usar: se descarta en silencio en vez de guardar un
                // bloque en blanco que luego aparece como hueco en la web.
                // La imagen cuenta: una pieza que sólo lleva foto es legítima.
                $tieneAlgo = trim((string) ($fila['imagen_id'] ?? '')) !== '';
                foreach (['rotulo', 'titulo', 'texto'] as $c) {
                    if (trim((string) ($fila[$c] ?? '')) !== '') {
                        $tieneAlgo = true;
                    }
                }
                if (!$tieneAlgo) {
                    continue;
                }

                $bloque = ['activo' => !empty($fila['activo'])];

                /* ── La dirección propia de la pieza ──────────────────────
                 *
                 * Sólo las secciones marcadas con «detalle» tienen páginas
                 * por pieza. En las demás no se guarda slug: darle dirección
                 * a una lámina del carrusel sólo crearía una URL que nadie
                 * enlaza y que compite en Google con la página buena.
                 *
                 * Si el editor lo dejó vacío, se calcula del titular. Si lo
                 * escribió, se respeta —normalizado— porque a veces hace
                 * falta cambiarlo a mano. Lo que NO se hace nunca es
                 * recalcularlo solo al editar el titular: la dirección que ya
                 * se compartió tiene que seguir existiendo.
                 */
                if ($conDetalle) {
                    $suyo = Slug::normalizar((string) ($fila['slug'] ?? ''));

                    if ($suyo === '' || $suyo === 'pieza') {
                        $suyo = Slug::desde(
                            (string) ($fila['titulo'] ?? ''),
                            static fn (string $s): bool => isset($slugsUsados[$s])
                        );
                    }

                    while (isset($slugsUsados[$suyo])) {
                        $suyo = Slug::desde((string) ($fila['titulo'] ?? ''),
                            static fn (string $s): bool => isset($slugsUsados[$s]));
                    }

                    $slugsUsados[$suyo] = true;
                    $bloque['slug'] = $suyo;
                }

                foreach ($plantilla['bloques']['campos'] as $campo) {
                    /* Se pregunta por el TIPO, no por el nombre. Antes decía
                       «if ($campo === 'imagen')», así que al añadir un segundo
                       campo de imagen —el de móvil— se habría guardado como si
                       fuera texto, y en una columna que espera un número. */
                    if (Plantillas::campo($campo)['tipo'] === 'imagen') {
                        $columna = Plantillas::columna($campo);
                        $elegida = trim((string) ($fila[$columna] ?? ''));
                        $bloque[$columna] = $elegida === '' ? null : (int) $elegida;

                        continue;
                    }

                    /* Igual que arriba con las imágenes: la columna la dice la
                       plantilla. Así un campo puede llamarse «titulo_lineas»
                       en el panel —para salir como área y admitir renglones— y
                       seguir guardando en la columna `titulo` de siempre, sin
                       migración y sin tocar lo que ya hay escrito. */
                    $columna = Plantillas::columna($campo);

                    /* Los renglones se respetan, pero se normalizan: un
                       navegador manda CRLF y otro LF, y la vista pública los
                       pasa por nl2br(). Sin esto, el mismo texto guardado
                       desde dos equipos daría saltos distintos. */
                    $valor = str_replace(["\r\n", "\r"], "\n", (string) ($fila[$columna] ?? ''));

                    $bloque[$columna] = trim($valor);
                }

                /* Lo que la plantilla no declara vuelve tal cual desde el campo
                   oculto. Se decodifica con cuidado: llega del navegador, así
                   que si no es un objeto JSON válido se descarta en lugar de
                   escribir cualquier cosa en la columna. */
                $extra = json_decode((string) ($fila['datos_extra'] ?? ''), true);
                $datosBloque = is_array($extra) ? $extra : [];

                foreach ($plantilla['bloques']['datos'] ?? [] as $clave => $definicion) {
                    $valor = $definicion['tipo'] === 'lista'
                        ? $this->lineas((string) ($fila['datos'][$clave] ?? ''))
                        : trim((string) ($fila['datos'][$clave] ?? ''));

                    if ($valor !== '' && $valor !== []) {
                        $datosBloque[$clave] = $valor;
                    }
                }
                $bloque['datos'] = $datosBloque;

                $bloques[] = $bloque;

                if (isset($plantilla['bloques']['maximo']) && count($bloques) >= (int) $plantilla['bloques']['maximo']) {
                    break;
                }
            }
        }

        $modelo->guardarSeccion((int) $seccion['id'], $campos, $bloques, $this->c->auth()->id());

        Auditoria::registrar($this->c, 'editar', 'secciones', (int) $seccion['id'], [
            'pagina'  => $pagina['clave'],
            'seccion' => $seccion['clave'],
            'bloques' => count($bloques),
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
