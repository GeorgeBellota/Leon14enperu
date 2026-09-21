<?php
/**
 * ============================================================================
 *  Sitio — arranque del sitio PÚBLICO.
 * ============================================================================
 *
 *  Lo que necesita una página de la web para leer su contenido de la base:
 *  configuración, conexión y modelos. Nada de sesión, nada de autenticación y
 *  nada del panel.
 *
 *  Una página pública lo usa así:
 *
 *      $sitio   = require __DIR__ . '/../intranet/app/Publico/arranque.php';
 *      $pagina  = $sitio->contenido('voluntariado');
 *
 *  Si la base no responde, `contenido()` devuelve null y la página debe saber
 *  pintarse igualmente con sus textos de reserva. Una web informativa sobre un
 *  viaje papal no puede quedarse en blanco porque falle MySQL.
 */

declare(strict_types=1);

namespace Intranet\Publico;

use Intranet\Core\Contenedor;
use Intranet\Core\Request;
use Intranet\Models\Catalogo;
use Intranet\Models\Pagina;
use Throwable;

final class Sitio
{
    /* Las fechas anunciadas por la Santa Sede. Sólo se usan si la base no
       responde o si alguien deja el ajuste vacío: la fecha buena es la del
       panel, no ésta. */
    /* Por debajo de este ancho se sirve la foto de móvil. Es el mismo salto
       que usa la hoja de estilos, para que la imagen cambie en el mismo
       punto en que cambia la maqueta. */
    private const CORTE_MOVIL = '(max-width: 767px)';

    private const INICIO_POR_DEFECTO = '2026-11-11T00:00:00-05:00';
    private const FIN_POR_DEFECTO    = '2026-11-16T23:59:59-05:00';

    private Contenedor $c;

    /** Se calcula una vez por petición. Ver nonce(). */
    private ?string $nonce = null;
    private ?Pagina $paginas = null;
    private ?Catalogo $catalogo = null;
    private bool $bdCaida = false;

    /** Lo pone el parcial del directo al pintarse. Ver reproductorPintado(). */
    private bool $hayReproductor = false;

    public function __construct(array $config, Request $peticion)
    {
        $this->c = new Contenedor($config, $peticion);
    }

    public function contenedor(): Contenedor
    {
        return $this->c;
    }

    public function peticion(): Request
    {
        return $this->c->peticion();
    }

    public function config(string $ruta, mixed $porDefecto = null): mixed
    {
        return $this->c->config($ruta, $porDefecto);
    }

    /**
     * El número de un solo uso que autoriza a un <script> incrustado.
     *
     * La Content-Security-Policy del sitio no admite JavaScript incrustado
     * salvo que lleve este valor, que cambia en cada petición y viaja en la
     * cabecera. Un script inyectado en el contenido no puede adivinarlo, así
     * que aunque alguien lograra colar uno, el navegador se negaría a
     * ejecutarlo.
     *
     * Se genera una sola vez por petición: dos valores distintos en la misma
     * página dejarían fuera al segundo script.
     */
    public function nonce(): string
    {
        return $this->nonce ??= rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    }

    /**
     * Enlace interno a otra página del sitio, desde la raíz.
     *
     *     $sitio->enlace('voluntariado/')  →  /voluntariado/
     *
     * No es lo mismo que asset(): un enlace de navegación no lleva «?v=»
     * detrás. Y no es lo mismo que url(): no hace falta escribir el dominio
     * dentro del propio sitio, y así los enlaces siguen funcionando en
     * pruebas, en local y si algún día cambia el dominio.
     */
    public function enlace(string $ruta = '/'): string
    {
        $base = $this->c->peticion()->base();

        return ($base === '' ? '/' : $base . '/') . ltrim($ruta, '/');
    }

    /** URL absoluta dentro del sitio público. */
    public function url(string $ruta = '/'): string
    {
        return rtrim((string) $this->c->config('url.sitio', ''), '/') . '/' . ltrim($ruta, '/');
    }

    /**
     * Un destino escrito en el panel, listo para un href.
     *
     * En el panel los destinos se escriben cortos —«subsidios/»— y hay que
     * colgarlos de la raíz del sitio: desde /subsidios/ un enlace relativo
     * apuntaría a /subsidios/subsidios/. Pero también se pega ahí lo que da
     * Google Drive, un correo o un ancla, y eso se respeta tal cual.
     *
     * Se admite sólo lo que puede vivir en un enlace de una página: http,
     * https, mailto, tel, una ruta de este sitio o un ancla. Cualquier otra
     * cosa se trata como ruta interna, y con eso «javascript:…» pegado en el
     * panel acaba siendo un enlace roto a una página que no existe en vez de
     * código ejecutándose en el navegador de un visitante.
     */
    public function enlaceDelPanel(string $destino): string
    {
        $destino = trim($destino);

        if ($destino === '') {
            return '';
        }

        return preg_match('~^(?:https?:|mailto:|tel:|/|#)~i', $destino) === 1
            ? $destino
            : $this->enlace($destino);
    }

    /**
     * ¿Ese destino saca al visitante de este sitio?
     *
     * Lo que sale fuera se abre en otra pestaña con rel="noopener noreferrer",
     * que es lo que ya hacen las noticias, los comunicados y el directo. Aquí
     * sólo se responde a la pregunta; los atributos los pone cada vista.
     */
    public function esExterno(string $destino): bool
    {
        return preg_match('~^https?://~i', trim($destino)) === 1;
    }

    /**
     * Ruta de un CSS o un JS con su versión pegada detrás:
     *
     *     assets/js/form.js  →  ../assets/js/form.js?v=1787251590
     *
     * El número es la fecha de modificación del archivo. Cuando el archivo
     * cambia, cambia la dirección, y el navegador se ve obligado a pedirlo de
     * nuevo en lugar de usar su copia.
     *
     * Esto NO es un adorno. El sitio cachea CSS y JS durante un mes, y sin
     * versión eso significa que una corrección puede tardar un mes en llegar a
     * quien ya visitó la página. Pasó de verdad: tras conectar el formulario a
     * la base, el navegador siguió ejecutando el JavaScript viejo —el que
     * simulaba el envío— y mostraba la confirmación sin guardar nada. El
     * servidor tenía el archivo correcto; el navegador no lo pidió.
     *
     * @param string $prefijo Camino hasta la raíz del sitio desde la página
     *                        que lo pinta: '../' desde una subcarpeta.
     */
    public function asset(string $relativa, ?string $prefijo = null): string
    {
        $relativa = ltrim($relativa, '/');
        $fisica   = dirname(__DIR__, 3) . '/' . $relativa;
        $version  = '?v=' . self::version($fisica);

        // Con un solo punto de entrada, la dirección del navegador ya no dice
        // en qué carpeta está el archivo que la pinta: /prensa/ y /sedes/ los
        // sirve el mismo index.php de la raíz. Una ruta relativa como
        // «../assets/…» resolvería distinto según la URL —y desde /prensa/
        // apuntaría fuera del sitio—, así que se construye desde la raíz.
        //
        // El prefijo sigue aceptándose para lo que aún no se haya movido,
        // pero no debería hacer falta.
        if ($prefijo !== null && $prefijo !== '') {
            return $prefijo . $relativa . $version;
        }

        $base = $this->c->peticion()->base();

        return ($base === '' ? '/' : $base . '/') . $relativa . $version;
    }

    /**
     * La versión de un archivo: los ocho primeros dígitos de su hash.
     *
     * Antes se usaba la fecha de modificación, y tiene un agujero: muchos
     * clientes FTP conservan la fecha original del archivo al subirlo. Si el
     * servidor recibe un form.js nuevo pero con la fecha del viejo, el `?v=`
     * no cambia, el navegador da por buena su copia guardada y sigue
     * ejecutando el JavaScript anterior. Es exactamente lo que ya pasó una vez
     * con este formulario.
     *
     * El hash depende del CONTENIDO: si cambia una sola línea, cambia la
     * dirección, y el navegador no tiene más remedio que pedirlo de nuevo.
     *
     * Se calcula una vez por archivo y petición: md5_file sobre un archivo de
     * 30 KB es despreciable comparado con lo que cuesta servir la página.
     *
     * @var array<string, string>
     */
    private static array $versiones = [];

    public static function version(string $fisica): string
    {
        if (isset(self::$versiones[$fisica])) {
            return self::$versiones[$fisica];
        }

        $hash = is_file($fisica) ? @md5_file($fisica) : false;

        // Si el archivo no se puede leer, se usa la hora: es preferible que el
        // navegador pida de más a que se quede con una copia vieja.
        return self::$versiones[$fisica] = $hash === false
            ? (string) time()
            : substr($hash, 0, 8);
    }

    /**
     * Contenido de una página con sus secciones y bloques.
     * Devuelve null si la base no está disponible; nunca lanza.
     *
     * @return array<string, mixed>|null
     */
    public function contenido(string $clave): ?array
    {
        try {
            $this->paginas ??= new Pagina($this->c);

            return $this->paginas->conContenido($clave);
        } catch (Throwable $e) {
            error_log('[sitio] no se pudo cargar la página «' . $clave . '»: ' . $e->getMessage());
            $this->bdCaida = true;

            return null;
        }
    }

    public function catalogo(): Catalogo
    {
        return $this->catalogo ??= new Catalogo($this->c);
    }

    /**
     * Los identificadores de medición configurados en el panel.
     *
     * ── Por qué se vuelve a validar aquí ─────────────────────────────────
     *
     * Porque el valor sale de la base, y entre la base y el <script> no puede
     * haber ni un carácter sin comprobar. El panel ya valida el formato al
     * guardar, pero una fila puede llegar de otro sitio —una migración, una
     * edición a mano, un volcado restaurado— y ésta es la última puerta antes
     * de que el valor entre en una página.
     *
     * Si el formato no cuadra, se ignora. Preferible perder la medición a
     * inyectar algo que no sabemos qué es.
     *
     * @return array{ga4:string, pixel:string}
     */
    public function medicion(): array
    {
        if ($this->bdCaida) {
            return ['ga4' => '', 'pixel' => ''];
        }

        $ga4   = strtoupper(trim($this->ajuste('analitica.ga4', '')));
        $pixel = trim($this->ajuste('analitica.pixel', ''));

        return [
            'ga4'   => preg_match('/^G-[A-Z0-9]{6,14}$/', $ga4) === 1 ? $ga4 : '',
            'pixel' => preg_match('/^\d{10,20}$/', $pixel) === 1 ? $pixel : '',
        ];
    }

    /** ¿Hay algo que medir? Decide el aviso de cookies y la apertura de la CSP. */
    public function mide(): bool
    {
        $m = $this->medicion();

        return $m['ga4'] !== '' || $m['pixel'] !== '';
    }

    /**
     * La transmisión en directo configurada en el panel.
     *
     * ── Se guarda el IDENTIFICADOR, nunca el <iframe> ────────────────────
     *
     * YouTube da un bloque de HTML para pegar. Un campo de texto libre cuyo
     * contenido acaba dentro de la página deja que quien entre al panel meta
     * lo que quiera en todas las visitas. Aquí sólo entran letras, números,
     * guiones y rayas bajas, y el reproductor lo compone el servidor.
     *
     * ── Dos formas de emitir, y las dos valen ────────────────────────────
     *
     *   · Un vídeo concreto: 11 caracteres. Sirve para un directo con fecha
     *     y hora, y sigue funcionando cuando termina y queda grabado.
     *   · Un canal: «UC» y 22 caracteres. Emite lo que el canal esté dando
     *     en ese momento, así que no hay que tocar el panel cada día.
     *
     * Si el formato no cuadra, se devuelve vacío. Preferible no emitir a
     * emitir cualquier cosa.
     *
     * @return array{tipo:string, id:string, titulo:string}
     */
    public function directo(): array
    {
        $vacio = ['tipo' => '', 'id' => '', 'titulo' => ''];

        if ($this->bdCaida) {
            return $vacio;
        }

        $id     = trim($this->ajuste('directo.youtube', ''));
        $titulo = trim($this->ajuste('directo.titulo', ''));

        if (preg_match('/^UC[A-Za-z0-9_-]{22}$/', $id) === 1) {
            return ['tipo' => 'canal', 'id' => $id, 'titulo' => $titulo];
        }

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1) {
            return ['tipo' => 'video', 'id' => $id, 'titulo' => $titulo];
        }

        return $vacio;
    }

    /** ¿Hay transmisión configurada? Decide si la CSP admite el iframe. */
    public function emite(): bool
    {
        return $this->directo()['id'] !== '';
    }

    /**
     * ¿Ha pintado esta página el reproductor?
     *
     * Lo marca assets/parciales/directo.php al pintarse, y lo lee la plantilla
     * para decidir si pide directo.js.
     *
     * Hace falta porque las dos cosas no coinciden: la transmisión se
     * configura una vez y vale para todo el sitio, pero el reproductor sólo
     * sale en la página que lo incluye. Sin esto, las otras veintitrés pedían
     * un guion que se cierra en su primera línea al no encontrar nada.
     *
     * Funciona porque la plantilla se pinta DESPUÉS de la vista: cuando llega
     * a los scripts, el parcial ya se ejecutó.
     */
    public function reproductorPintado(?bool $marcar = null): bool
    {
        if ($marcar === true) {
            $this->hayReproductor = true;
        }

        return $this->hayReproductor;
    }

    /**
     * Los datos de buscador de una página, si el panel los tiene puestos.
     *
     * ── Qué devuelve ─────────────────────────────────────────────────────
     *
     * Sólo las claves con valor. Un campo vacío en el panel NO se devuelve, y
     * así la plantilla conserva el texto que la vista traía escrito. Es la
     * diferencia entre «el panel manda cuando dice algo» y «el panel borra el
     * título de la página en cuanto alguien guarda sin rellenarlo».
     *
     * ── Si la base no responde ───────────────────────────────────────────
     *
     * Devuelve un array vacío y el sitio sigue con los textos de la vista. El
     * SEO es importante, pero no tanto como que la página se pinte.
     *
     * @return array{titulo?:string, descripcion?:string, og_imagen?:string}
     */
    public function seo(string $clave): array
    {
        if ($clave === '' || $this->bdCaida) {
            return [];
        }

        try {
            $this->paginas ??= new Pagina($this->c);
            $fila = $this->paginas->seoDe($clave);
        } catch (Throwable $e) {
            error_log('[sitio] no se pudo leer el seo de «' . $clave . '»: ' . $e->getMessage());
            $this->bdCaida = true;

            return [];
        }

        if ($fila === null) {
            return [];
        }

        $seo = [];

        foreach (['titulo' => 'titulo_seo', 'descripcion' => 'descripcion_seo'] as $salida => $columna) {
            $valor = trim((string) ($fila[$columna] ?? ''));
            if ($valor !== '') {
                $seo[$salida] = $valor;
            }
        }

        $imagen = trim((string) ($fila['og_imagen_ruta'] ?? ''));
        if ($imagen !== '') {
            $seo['og_imagen'] = ltrim($imagen, '/');
        }

        return $seo;
    }

    /**
     * El instante al que apunta la cuenta atrás, en ISO 8601 con huso.
     *
     * Sale del ajuste `viaje.inicio`, que se edita en Configuración general.
     * El JavaScript lo lee del atributo `data-objetivo` y no lleva ninguna
     * fecha escrita: cambiar el día del viaje es entrar al panel, no desplegar.
     *
     * Si la base no responde se devuelve la fecha anunciada por la Santa Sede.
     * Un contador en blanco es peor que un contador con la fecha oficial.
     */
    public function objetivoCuentaAtras(): string
    {
        return $this->fecha('viaje.inicio', self::INICIO_POR_DEFECTO);
    }

    public function finDelViaje(): string
    {
        return $this->fecha('viaje.fin', self::FIN_POR_DEFECTO);
    }

    /**
     * En qué momento está el sitio: «pre», «live» o «post».
     *
     * ── Por qué no es sólo un ajuste ─────────────────────────────────────
     *
     * Antes lo era, y eso significaba que a las 00:00 del 11 de noviembre
     * alguien tenía que entrar al panel a cambiarlo. La noche anterior al
     * viaje apostólico no es el momento de depender de que alguien se acuerde.
     *
     * Con `auto` —el valor recomendado— lo decide el calendario. Los otros
     * tres valores siguen existiendo para poder ver cómo quedará cada fase
     * antes de que llegue, y para poder forzarla si algo se tuerce.
     */
    public function fase(): string
    {
        $elegida = (string) $this->ajuste('sitio.fase', 'auto');

        if (in_array($elegida, ['pre', 'live', 'post'], true)) {
            return $elegida;
        }

        $ahora  = time();
        $inicio = strtotime($this->objetivoCuentaAtras());
        $fin    = strtotime($this->finDelViaje());

        // Con las fechas ilegibles se elige «pre»: es la única fase que no
        // afirma nada que pueda ser falso —el viaje siempre está por llegar
        // hasta que llega— y es la que enseña el formulario de voluntariado.
        if ($inicio === false || $fin === false) {
            return 'pre';
        }

        if ($ahora < $inicio) { return 'pre'; }

        return $ahora <= $fin ? 'live' : 'post';
    }

    /** Un ajuste, sin que una base caída se lleve por delante la página. */
    private function ajuste(string $clave, string $porDefecto): string
    {
        try {
            $valor = (string) $this->catalogo()->ajuste($clave, $porDefecto);
        } catch (Throwable $e) {
            error_log('[sitio] no se pudo leer el ajuste «' . $clave . '»: ' . $e->getMessage());
            $this->bdCaida = true;

            return $porDefecto;
        }

        return trim($valor) === '' ? $porDefecto : $valor;
    }

    /** Un ajuste de fecha, comprobando que se entienda antes de devolverlo. */
    private function fecha(string $clave, string $porDefecto): string
    {
        $valor = $this->ajuste($clave, $porDefecto);

        return strtotime($valor) === false ? $porDefecto : $valor;
    }

    public function bdCaida(): bool
    {
        return $this->bdCaida;
    }

    /**
     * Lectura cómoda desde la plantilla, con texto de reserva.
     *
     *     $sitio->campo($secciones, 'cabecera', 'titulo', 'Los amigos de León')
     *
     * El tercer argumento evita que un campo vaciado por error en el panel
     * deje un hueco mudo en la página pública.
     *
     * @param array<string, mixed> $secciones
     */
    public static function campo(array $secciones, string $seccion, string $campo, string $reserva = ''): string
    {
        $valor = $secciones[$seccion][$campo] ?? null;

        return ($valor === null || trim((string) $valor) === '') ? $reserva : (string) $valor;
    }

    /**
     * Un valor de la columna `datos` (JSON) de una sección.
     *
     * @param array<string, mixed> $secciones
     */
    public static function dato(array $secciones, string $seccion, string $clave, mixed $reserva = null): mixed
    {
        $valor = $secciones[$seccion]['datos'][$clave] ?? null;

        // `false` cuenta como vacío, no sólo null y ''.
        //
        // Parece un detalle y no lo es: una migración guardó por error el
        // texto legal del consentimiento como `false`, y como false no era ni
        // null ni cadena vacía, este método lo dio por bueno y devolvió false
        // en lugar del texto de reserva. Resultado: la casilla que acepta el
        // tratamiento de datos personales se mostró SIN texto. El respaldo
        // existía y no entró porque la comprobación era demasiado estrecha.
        //
        // Un 0 legítimo tampoco tiene sentido como contenido de texto, así que
        // se descarta igual y se cae al valor de reserva.
        if ($valor === null || $valor === '' || $valor === false || $valor === 0) {
            return $reserva;
        }

        return $valor;
    }

    /**
     * Los bloques de una sección, o la lista de reserva si no hay ninguno.
     *
     * @param array<string, mixed> $secciones
     * @return array<int, array<string, mixed>>
     */
    public static function bloques(array $secciones, string $seccion, array $reserva = []): array
    {
        $bloques = $secciones[$seccion]['bloques'] ?? [];

        return $bloques === [] ? $reserva : $bloques;
    }

    /** ¿Está activa esta sección? Una sección apagada no se pinta. */
    public static function activa(array $secciones, string $seccion): bool
    {
        return isset($secciones[$seccion]);
    }

    /**
     * El <picture> de una imagen del CMS, con sus variantes.
     *
     * ── Por qué no basta un <img src> ────────────────────────────────────
     *
     * El sitio sirve cada foto en dos formatos y varios anchos, y deja que el
     * navegador elija. Devolver una sola ruta convertiría una página cuidada
     * en una que manda el archivo grande a todo el mundo. Con la mayoría del
     * tráfico llegando desde móviles, eso se nota.
     *
     * ── Por qué hay valor de reserva ─────────────────────────────────────
     *
     * `$reserva` es el HTML que la página ya tenía escrito a mano. Mientras
     * nadie elija una imagen en el panel, la página sigue mostrando
     * exactamente lo de antes. Así una página se puede pasar al CMS sin que
     * el visitante note nada el día del despliegue, y la foto se cambia
     * cuando alguien quiera, no cuando se sube el código.
     *
     * @param array<string, mixed> $fuente  una sección o un bloque ya cargado
     * @param array{sizes?:string, clase?:string, carga?:string, prioridad?:bool} $opciones
     */
    public function imagen(array $fuente, string $reserva = '', array $opciones = []): string
    {
        $ruta = $fuente['imagen_ruta'] ?? null;

        if (!is_string($ruta) || $ruta === '') {
            return $reserva;
        }

        $esc   = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
        $alt   = (string) ($fuente['imagen_alt'] ?? '');
        $sizes = (string) ($opciones['sizes'] ?? '100vw');
        $clase = (string) ($opciones['clase'] ?? '');

        // Las decorativas llevan alt vacío a propósito: un lector de pantalla
        // debe saltárselas, no leer «imagen» y perder el hilo.
        $atributos = ' alt="' . $esc($alt) . '"';

        if ($clase !== '') {
            $atributos .= ' class="' . $esc($clase) . '"';
        }

        if (!empty($fuente['imagen_ancho']) && !empty($fuente['imagen_alto'])) {
            $atributos .= ' width="' . (int) $fuente['imagen_ancho'] . '"'
                        . ' height="' . (int) $fuente['imagen_alto'] . '"';
        }

        // La imagen que se ve nada más entrar no debe cargarse en diferido: eso
        // retrasa justo lo que el visitante está esperando.
        $atributos .= empty($opciones['prioridad'])
            ? ' loading="lazy" decoding="async"'
            : ' fetchpriority="high" decoding="async"';

        $variantes = $fuente['imagen_variantes'] ?? null;
        $variantes = is_string($variantes) ? json_decode($variantes, true) : $variantes;

        // Sin familia —un SVG, o una imagen registrada a mano— se sirve tal cual.
        if (!is_array($variantes) || empty($variantes['anchos']) || empty($variantes['base'])) {
            return '<img src="' . $esc($this->url($ruta)) . '"' . $atributos . '>';
        }

        $base     = (string) $variantes['base'];
        $anchos   = array_map('intval', (array) $variantes['anchos']);
        $formatos = array_values((array) ($variantes['formatos'] ?? ['webp']));
        $respaldo = (string) end($formatos);

        $conjunto = function (string $formato) use ($base, $anchos, $esc): string {
            $partes = [];

            foreach ($anchos as $a) {
                $partes[] = $esc($this->url($base . '-' . $a . '.' . $formato)) . ' ' . $a . 'w';
            }

            return implode(', ', $partes);
        };

        $html = '<picture>';

        /* ── La versión de móvil va PRIMERO ────────────────────────────────
           El navegador se queda con el primer <source> cuyo «media» encaje,
           así que las reglas más estrechas tienen que ir arriba: si las de
           escritorio fueran antes, ganarían siempre y la de móvil no se
           serviría nunca.

           Si nadie ha elegido foto de móvil, esto no emite nada y el
           <picture> queda exactamente como estaba. */
        $html .= $this->fuentesMovil($fuente, $esc);

        foreach ($formatos as $formato) {
            if ($formato === $respaldo) {
                continue;   // el respaldo va en el <img>, no en un <source>
            }

            $html .= '<source type="' . $esc(self::mime($formato)) . '"'
                   . ' sizes="' . $esc($sizes) . '"'
                   . ' srcset="' . $conjunto($formato) . '">';
        }

        $html .= '<img src="' . $esc($this->url($ruta)) . '"'
               . ' sizes="' . $esc($sizes) . '"'
               . ' srcset="' . $conjunto($respaldo) . '"'
               . $atributos . '>';

        return $html . '</picture>';
    }
    /**
     * Los <source> de la fotografía de móvil, si la hay.
     *
     * ── Por qué una segunda imagen y no un recorte por CSS ───────────────
     *
     * Con object-fit el teléfono se descarga igual la imagen apaisada de 1920
     * y luego tira dos tercios. Aquí no se descarga siquiera: el navegador ve
     * el «media» y pide sólo la que le toca.
     *
     * ── El corte ─────────────────────────────────────────────────────────
     *
     * 767px. Por debajo, teléfono; por encima, tableta y escritorio. Coincide
     * con el salto que usa el resto de la hoja de estilos, así que la foto
     * cambia en el mismo punto en que cambia la maqueta.
     *
     * @param array<string, mixed> $fuente
     */
    private function fuentesMovil(array $fuente, callable $esc): string
    {
        $ruta = $fuente['imagen_movil_ruta'] ?? null;

        if (!is_string($ruta) || $ruta === '') {
            return '';
        }

        $variantes = $fuente['imagen_movil_variantes'] ?? null;
        $variantes = is_string($variantes) ? json_decode($variantes, true) : $variantes;

        // Sin familia —un SVG— se sirve el archivo tal cual, en un solo source.
        if (!is_array($variantes) || empty($variantes['anchos']) || empty($variantes['base'])) {
            return '<source media="' . self::CORTE_MOVIL . '" srcset="' . $esc($this->url($ruta)) . '">';
        }

        $base     = (string) $variantes['base'];
        $anchos   = array_map('intval', (array) $variantes['anchos']);
        $formatos = array_values((array) ($variantes['formatos'] ?? ['webp']));

        $html = '';

        foreach ($formatos as $formato) {
            $partes = [];

            foreach ($anchos as $a) {
                $partes[] = $esc($this->url($base . '-' . $a . '.' . $formato)) . ' ' . $a . 'w';
            }

            $html .= '<source media="' . self::CORTE_MOVIL . '"'
                   . ' type="' . $esc(self::mime($formato)) . '"'
                   . ' sizes="100vw"'
                   . ' srcset="' . implode(', ', $partes) . '">';
        }

        return $html;
    }

    /**
     * El tipo MIME de un formato de imagen.
     *
     * «image/jpg» NO existe: el correcto es «image/jpeg». Y no es cosmético:
     * un navegador que no reconoce el tipo declarado en un <source> se salta
     * esa fuente entera, así que con la familia webp+jpg quien no soportara
     * webp se quedaba sin la versión de móvil y caía a la de escritorio.
     */
    private static function mime(string $formato): string
    {
        return match (strtolower($formato)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'svg'         => 'image/svg+xml',
            default       => 'image/' . strtolower($formato),
        };
    }
}