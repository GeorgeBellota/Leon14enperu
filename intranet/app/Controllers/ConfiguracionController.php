<?php
/**
 * ConfiguracionController — configuración general del sitio.
 *
 * Lo que afecta a TODAS las páginas a la vez: qué se muestra en la raíz del
 * dominio, qué entradas se ven en el menú, cuánto pie se pinta y —lo más
 * visible de todo— las fechas del viaje, de las que cuelga la cuenta atrás.
 *
 * Por eso está reservado al administrador y no al rol de contenidos: vaciar el
 * menú o cambiar la página de inicio no es editar un texto.
 */

declare(strict_types=1);

namespace Intranet\Controllers;

use Intranet\Core\Auditoria;
use Intranet\Core\Controller;
use Intranet\Core\ErrorDeNegocio;
use Intranet\Core\Logotipo;
use Intranet\Core\Request;
use Intranet\Models\Ajuste;
use Intranet\Publico\Menu;

final class ConfiguracionController extends Controller
{
    /**
     * El cuerpo del aviso de contacto, cuando nadie ha escrito uno propio.
     *
     * Se guarda igual que lo escriba quien administre: los {marcadores} los
     * sustituye Publico\Contacto al enviar.
     */
    private const PLANTILLA_CONTACTO = <<<'TEXTO'
        Nuevo mensaje desde leon14enperu.com

        Nombre:  {nombre}
        Correo:  {correo}
        Motivo:  {motivo}
        Fecha:   {fecha}

        Mensaje:
        {mensaje}
        TEXTO;

    /**
     * Las páginas que pueden aparecer en el menú, con su rótulo.
     *
     * Delega en Publico\Menu, que es de donde lee también la cabecera del
     * sitio. Estuvo escrita aquí a mano, en paralelo a la de allí, con un
     * comentario que decía que las dos tenían que coincidir. No coincidieron:
     * el rediseño renombró tres páginas, actualizó la de la cabecera y dejó
     * ésta con los nombres viejos. La casilla «Papa León XIV» guardaba
     * «el-papa» mientras la web buscaba «papa-leon-xiv», así que salía marcada
     * y la entrada no aparecía; y como guardar esta pantalla reescribe el
     * ajuste entero, se perdía al tocar cualquier otra cosa.
     *
     * @return array<string, string>
     */
    public static function paginasDelMenu(): array
    {
        return Menu::catalogo();
    }

    public function panel(Request $peticion): void
    {
        $ajustes = new Ajuste($this->c);

        /* Las casillas tienen que enseñar EXACTAMENTE lo que hay en la web, o
           dejan de servir para decidir. Por eso se leen igual que allí:

            · normalizar() traduce las páginas que se renombraron, así que un
              ajuste anterior al rediseño marca la casilla correcta en vez de
              dejarla vacía y perderse al guardar.
            · Sin nada guardado, la web enseña las nueve del diseño; aquí se
              marcan esas nueve, y no todas, que es lo que se hacía antes y
              prometía un menú que la web no iba a pintar. */
        $visibles = Menu::normalizar(
            explode(',', (string) $ajustes->leer('menu.visibles', ''))
        );

        if ($visibles === []) {
            $visibles = Menu::porDefecto();
        }

        /* Los enlaces sueltos de la banda del pie. Aquí null y cadena vacía NO
           son lo mismo: null es «nunca se ha configurado» y se marcan los tres
           del diseño, que es lo que la web enseña; vacío es «ninguno», y
           entonces no se marca nada. Ver assets/parciales/pie.php. */
        $pieGuardado = $ajustes->leer('pie.enlaces');
        $pieEnlaces  = $pieGuardado === null
            ? Menu::porDefectoPie()
            : Menu::normalizar(explode(',', $pieGuardado));

        $this->ver('configuracion/panel', [
            'titulo'     => 'Configuración general',
            'paginas'    => self::paginasDelMenu(),
            'inicio'     => (string) $ajustes->leer('sitio.pagina_inicio', 'home'),
            'visibles'   => $visibles,
            'pie'        => (string) $ajustes->leer('pie.modo', 'completo'),
            'pieEnlaces' => $pieEnlaces,

            // Las fechas llegan al formulario en el formato que entiende
            // <input type="datetime-local">: «2026-11-11T00:00». En la base se
            // guardan con su huso («…-05:00») porque el navegador del visitante
            // puede estar en cualquier parte del mundo y la cuenta atrás tiene
            // que decirle lo mismo a todos.
            'inicioViaje' => self::paraFormulario((string) $ajustes->leer('viaje.inicio', '')),
            'finViaje'    => self::paraFormulario((string) $ajustes->leer('viaje.fin', '')),
            'fase'        => (string) $ajustes->leer('sitio.fase', 'auto'),
            'ga4'         => (string) $ajustes->leer('analitica.ga4', ''),
            'pixel'       => (string) $ajustes->leer('analitica.pixel', ''),
            'directo'     => (string) $ajustes->leer('directo.youtube', ''),
            'directoTitulo' => (string) $ajustes->leer('directo.titulo', ''),

            /* El correo del formulario de contacto. Quien envía de verdad es
               una API en el cPanel: desde el VPS, mail() acaba en spam. */
            'contactoUrl'       => (string) $ajustes->leer('contacto.api_url', ''),
            'contactoToken'     => (string) $ajustes->leer('contacto.api_token', ''),
            'contactoAsunto'    => (string) $ajustes->leer('contacto.asunto', 'Contacto web · {motivo}'),
            'contactoPlantilla' => (string) $ajustes->leer('contacto.plantilla', self::PLANTILLA_CONTACTO),

            // El logotipo se pregunta al disco, no a un ajuste: así, si alguien
            // lo borra por FTP, el panel enseña la verdad y no un recuerdo.
            'logotipo'  => (new Logotipo())->actual(),
            'rutaSitio' => rtrim((string) $this->c->config('url.sitio', ''), '/') . '/',
        ]);
    }

    public function guardar(Request $peticion): void
    {
        $this->exigirCsrf($peticion);

        $ajustes = new Ajuste($this->c);
        $paginas = self::paginasDelMenu();

        // ── Página de inicio ────────────────────────────────────────────
        $inicio = $peticion->texto('pagina_inicio', 'home');

        if ($inicio !== 'home' && !array_key_exists($inicio, $paginas)) {
            $this->conError('Esa página no existe.', '/configuracion');
        }

        // ── Entradas del menú ───────────────────────────────────────────
        $marcadas = $peticion->post('visibles', []);
        $marcadas = Menu::normalizar(is_array($marcadas) ? $marcadas : []);

        // La página de inicio tiene que seguir estando en el menú. Sin ella,
        // quien entre a una interna se queda sin forma de volver a la portada,
        // que es justo la dirección donde vive.
        if ($inicio !== 'home' && !in_array($inicio, $marcadas, true)) {
            $marcadas[] = $inicio;
        }

        if ($marcadas === []) {
            $this->conError(
                'Deja al menos una entrada en el menú: un sitio sin navegación no se puede recorrer.',
                '/configuracion'
            );
        }

        // ── Fechas del viaje ────────────────────────────────────────────
        //
        // De aquí sale la cuenta atrás de la portada. Se validan las dos antes
        // de escribir ninguna: media configuración guardada —un inicio nuevo
        // con un fin viejo— dejaría el sitio diciendo que el viaje termina
        // antes de empezar.
        $inicioViaje = self::aIso($peticion->texto('viaje_inicio', ''));
        $finViaje    = self::aIso($peticion->texto('viaje_fin', ''));

        if ($inicioViaje === null || $finViaje === null) {
            $this->conError(
                'Revisa las fechas del viaje: alguna no se entiende. Las dos son obligatorias.',
                '/configuracion'
            );
        }

        if (strtotime($finViaje) <= strtotime($inicioViaje)) {
            $this->conError(
                'El viaje no puede terminar antes de empezar: revisa las dos fechas.',
                '/configuracion'
            );
        }

        // ── Fase del sitio ──────────────────────────────────────────────
        $fase = $peticion->texto('fase', 'auto');

        if (!in_array($fase, ['auto', 'pre', 'live', 'post'], true)) {
            $fase = 'auto';
        }

        // ── Pie ─────────────────────────────────────────────────────────
        $pie = $peticion->texto('pie_modo', 'completo');

        if (!in_array($pie, ['completo', 'simple', 'simple_en_internas'], true)) {
            $pie = 'completo';
        }

        /* Los enlaces sueltos de la banda. Aquí sí se admite no marcar
           ninguno: a diferencia del menú, un pie sin estos enlaces no deja el
           sitio sin navegación, sólo con el copyright, que es un modo
           legítimo. Se escribe siempre, aunque sea vacío: esa cadena vacía es
           la que le dice al pie «los quiero fuera» en lugar de «todavía no lo
           has tocado». */
        $pieEnlaces = $peticion->post('pie_enlaces', []);
        $pieEnlaces = Menu::normalizar(is_array($pieEnlaces) ? $pieEnlaces : []);

        // ── Logotipo ────────────────────────────────────────────────────
        // Se resuelve ANTES de guardar los ajustes: si la imagen se rechaza,
        // el envío se corta con un motivo y no queda medio guardado.
        $logotipo = new Logotipo();
        $cambioLogo = 'sin cambios';

        $subido = $_FILES['logotipo'] ?? null;

        if (is_array($subido) && (int) ($subido['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $cambioLogo = 'nuevo: ' . $logotipo->guardar($subido);
            } catch (ErrorDeNegocio $e) {
                $this->conError($e->getMessage(), '/configuracion');
            }
        } elseif ($peticion->texto('logotipo_borrar', '') !== '') {
            $logotipo->borrar();
            $cambioLogo = 'retirado';
        }

        /* ── Medición ──────────────────────────────────────────────────────
           Se guarda el IDENTIFICADOR, nunca el fragmento de código que Google
           y Meta dan para pegar.

           No es comodidad: ese fragmento es JavaScript, y un campo de texto
           libre cuyo contenido acaba dentro de un <script> es una puerta
           abierta a que quien entre al panel ejecute lo que quiera en todas
           las páginas del sitio. Guardando sólo el identificador, y con el
           formato validado, lo único que puede llegar al HTML son letras,
           números y guiones. El fragmento lo compone el servidor.

           Vacío = apagado, y apagado de verdad: sin script, sin aviso de
           cookies y con la Content-Security-Policy cerrada como hoy. */
        $ga4   = strtoupper(trim($peticion->texto('analitica_ga4', '')));
        $pixel = trim($peticion->texto('analitica_pixel', ''));

        if ($ga4 !== '' && preg_match('/^G-[A-Z0-9]{6,14}$/', $ga4) !== 1) {
            $this->conError(
                'El identificador de Google Analytics no tiene el formato correcto. '
                . 'Es del tipo G-XXXXXXXXXX, y lo encuentras en Administrar → Flujos de datos.',
                '/configuracion'
            );
        }

        if ($pixel !== '' && preg_match('/^\d{10,20}$/', $pixel) !== 1) {
            $this->conError(
                'El identificador del píxel de Meta son sólo números, entre 10 y 20 dígitos. '
                . 'Lo encuentras en el Administrador de eventos.',
                '/configuracion'
            );
        }

        /* ── La transmisión en directo ─────────────────────────────────────
           Mismo criterio que la medición: se guarda el IDENTIFICADOR, no el
           bloque <iframe> que da YouTube. El reproductor lo compone el
           servidor, así que por aquí sólo pueden entrar letras, números,
           guiones y rayas bajas.

           Se acepta pegar la dirección entera porque es lo que todo el mundo
           tiene a mano, pero lo que se guarda es el identificador que hay
           dentro. */
        $directo       = trim($peticion->texto('directo_youtube', ''));
        $directoTitulo = trim($peticion->texto('directo_titulo', ''));

        if ($directo !== '') {
            foreach ([
                '~[?&]v=([A-Za-z0-9_-]{11})~',          // watch?v=…
                '~youtu\.be/([A-Za-z0-9_-]{11})~',       // youtu.be/…
                '~/live/([A-Za-z0-9_-]{11})~',           // /live/…
                '~/embed/([A-Za-z0-9_-]{11})~',          // /embed/…
                '~channel/(UC[A-Za-z0-9_-]{22})~',       // channel/UC…
            ] as $patron) {
                if (preg_match($patron, $directo, $m) === 1) {
                    $directo = $m[1];
                    break;
                }
            }

            $esCanal = preg_match('/^UC[A-Za-z0-9_-]{22}$/', $directo) === 1;
            $esVideo = preg_match('/^[A-Za-z0-9_-]{11}$/', $directo) === 1;

            if (!$esCanal && !$esVideo) {
                $this->conError(
                    'No reconozco ese vídeo de YouTube. Pega la dirección completa de la '
                    . 'transmisión, o sólo el identificador: 11 caracteres para un vídeo, '
                    . 'o el del canal, que empieza por UC.',
                    '/configuracion'
                );
            }
        }

        $directoTitulo = mb_substr($directoTitulo, 0, 120);

        /* ── El correo de contacto ──────────────────────────────────────
           La dirección tiene que ser https: por ahí viaja el token en una
           cabecera, y en claro lo leería cualquiera en el camino. Si no lo
           es, se descarta el valor entero en vez de guardar algo que
           filtraría el token en cada envío. */
        $contactoUrl = trim($peticion->texto('contacto_api_url', ''));

        if ($contactoUrl !== '' && !preg_match('~^https://~i', $contactoUrl)) {
            $this->conError(
                'La dirección de la API de correo tiene que empezar por «https://»: por ahí '
                . 'viaja el token y en claro lo leería cualquiera.',
                '/configuracion'
            );
        }

        $contactoToken     = trim($peticion->texto('contacto_api_token', ''));
        $contactoAsunto    = mb_substr(trim($peticion->texto('contacto_asunto', '')), 0, 200);
        $contactoPlantilla = mb_substr(trim($peticion->texto('contacto_plantilla', '')), 0, 4000);

        $ajustes->escribir('sitio.pagina_inicio', $inicio);
        /* Siempre la lista explícita, aunque estén todas marcadas. Antes, en
           ese caso se guardaba vacío para que una página nueva entrara sola en
           el menú. Esa promesa dejó de ser cierta: para la cabecera, vacío ya
           no significa «todas» sino «las nueve del diseño». Guardar vacío con
           dieciséis marcadas habría enseñado nueve. */
        $ajustes->escribir('menu.visibles', implode(',', $marcadas));
        $ajustes->escribir('pie.modo', $pie);
        $ajustes->escribir('pie.enlaces', implode(',', $pieEnlaces));
        $ajustes->escribir('viaje.inicio', $inicioViaje);
        $ajustes->escribir('viaje.fin', $finViaje);
        $ajustes->escribir('sitio.fase', $fase);
        $ajustes->escribir('analitica.ga4', $ga4);
        $ajustes->escribir('analitica.pixel', $pixel);
        $ajustes->escribir('directo.youtube', $directo);
        $ajustes->escribir('directo.titulo', $directoTitulo);
        $ajustes->escribir('contacto.api_url', $contactoUrl);
        $ajustes->escribir('contacto.api_token', $contactoToken);
        $ajustes->escribir('contacto.asunto', $contactoAsunto);
        $ajustes->escribir('contacto.plantilla', $contactoPlantilla);

        Auditoria::registrar($this->c, 'editar', 'ajustes', null, [
            'pagina_inicio' => $inicio,
            'menu'          => implode(',', $marcadas),
            'pie'           => $pie,
            'pie_enlaces'   => implode(',', $pieEnlaces) ?: '(ninguno)',
            'viaje_inicio'  => $inicioViaje,
            'viaje_fin'     => $finViaje,
            'fase'          => $fase,
            'logotipo'      => $cambioLogo,
        ]);

        $this->conExito('Configuración guardada. Los cambios ya se ven en la web.', '/configuracion');
    }

    /**
     * De lo que guarda la base a lo que espera <input type="datetime-local">.
     *
     * El input NO acepta el huso: si se le da «2026-11-11T00:00:00-05:00» se
     * queda vacío, sin avisar. Y un campo obligatorio que aparece vacío hace
     * que quien entre a cambiar el pie de página se lleve por delante la fecha
     * del viaje sin haberla tocado.
     */
    private static function paraFormulario(string $iso): string
    {
        $t = strtotime($iso);

        return $t === false ? '' : date('Y-m-d\TH:i', $t);
    }

    /**
     * Y de vuelta: del formulario a la base, siempre con el huso de Lima.
     *
     * Va escrito y no se toma del servidor a propósito. El VPS está en
     * Alemania: si la fecha se guardara con el huso de la máquina, la cuenta
     * atrás iría seis horas adelantada y nadie sabría por qué.
     *
     * Devuelve null si no se entiende, para que quien llama decida qué hacer.
     */
    private static function aIso(string $local): ?string
    {
        $local = trim($local);
        if ($local === '') { return null; }

        // «2026-11-11T00:00» o «2026-11-11T00:00:00», que es lo que mandan los
        // distintos navegadores para el mismo campo.
        if (preg_match('~^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$~', $local) !== 1) {
            return null;
        }

        $t = strtotime($local);
        if ($t === false) { return null; }

        return date('Y-m-d\TH:i:s', $t) . '-05:00';
    }
}
