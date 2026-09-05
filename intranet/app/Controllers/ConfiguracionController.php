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

final class ConfiguracionController extends Controller
{
    /**
     * Las páginas que pueden aparecer en el menú, con su rótulo.
     *
     * Es la MISMA lista que hay en assets/parciales/cabecera.php, y las dos
     * tienen que decir lo mismo: una página que esté aquí y no allí se puede
     * marcar pero no se muestra; una que esté allí y no aquí se muestra sin
     * que haya forma de quitarla.
     *
     * No sale de la tabla `paginas` a propósito. El menú de una web pública no
     * es el índice de todo lo que existe: hay páginas —privacidad, cookies,
     * aviso legal— que viven en el pie y no deben poder subir a la navegación
     * principal por el hecho de existir en la base.
     *
     * @return array<string, string>
     */
    public static function paginasDelMenu(): array
    {
        return [
            'el-papa'              => 'Papa León XIV',
            'sedes'                => 'Sedes',
            'agenda'               => 'Agenda',
            'tierra-de-santos'     => 'Tierra de santos',
            'cep'                  => 'CEP',
            'noticias'             => 'Noticias',
            'voluntariado'         => 'Voluntariado',
            'participa'            => 'Participa',
            'multimedia'           => 'Multimedia',
            'prensa'               => 'Prensa',
            'materiales'           => 'Materiales',
            'guia-del-peregrino'   => 'Guía del peregrino',
            'preguntas-frecuentes' => 'Preguntas frecuentes',
            'patrocinios'          => 'Patrocinios',
            'donativo'             => 'Donaciones',
            'contacto'             => 'Contacto',
        ];
    }

    public function panel(Request $peticion): void
    {
        $ajustes = new Ajuste($this->c);

        $visibles = array_filter(array_map('trim',
            explode(',', (string) $ajustes->leer('menu.visibles', ''))));

        $this->ver('configuracion/panel', [
            'titulo'   => 'Configuración general',
            'paginas'  => self::paginasDelMenu(),
            'inicio'   => (string) $ajustes->leer('sitio.pagina_inicio', 'home'),
            'visibles' => $visibles,
            'todas'    => $visibles === [],
            'pie'      => (string) $ajustes->leer('pie.modo', 'completo'),

            // Las fechas llegan al formulario en el formato que entiende
            // <input type="datetime-local">: «2026-11-11T00:00». En la base se
            // guardan con su huso («…-05:00») porque el navegador del visitante
            // puede estar en cualquier parte del mundo y la cuenta atrás tiene
            // que decirle lo mismo a todos.
            'inicioViaje' => self::paraFormulario((string) $ajustes->leer('viaje.inicio', '')),
            'finViaje'    => self::paraFormulario((string) $ajustes->leer('viaje.fin', '')),
            'fase'        => (string) $ajustes->leer('sitio.fase', 'auto'),

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
        $marcadas = is_array($marcadas) ? array_intersect($marcadas, array_keys($paginas)) : [];

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

        // Si están todas marcadas se guarda vacío: así, cuando en el futuro se
        // añada una página nueva, aparecerá en el menú sola en lugar de quedar
        // fuera por no estar en una lista escrita hace meses.
        $todas = count($marcadas) === count($paginas);

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

        $ajustes->escribir('sitio.pagina_inicio', $inicio);
        $ajustes->escribir('menu.visibles', $todas ? '' : implode(',', $marcadas));
        $ajustes->escribir('pie.modo', $pie);
        $ajustes->escribir('viaje.inicio', $inicioViaje);
        $ajustes->escribir('viaje.fin', $finViaje);
        $ajustes->escribir('sitio.fase', $fase);

        Auditoria::registrar($this->c, 'editar', 'ajustes', null, [
            'pagina_inicio' => $inicio,
            'menu'          => $todas ? 'todas' : implode(',', $marcadas),
            'pie'           => $pie,
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
