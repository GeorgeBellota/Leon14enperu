<?php
/**
 * Contenedor — el pegamento del núcleo.
 *
 * Sostiene la configuración y las cuatro piezas que necesitan todos los
 * controladores: base de datos, sesión, autenticación y construcción de URLs.
 * Se pasa por constructor a controladores y vistas, en vez de recurrir a
 * variables globales o a llamadas estáticas repartidas: así se ve de un
 * vistazo de qué depende cada clase.
 *
 * No es un contenedor de inyección de dependencias con reflexión ni nada
 * parecido. Para un proyecto de este tamaño eso sería maquinaria sin uso.
 */

declare(strict_types=1);

namespace Intranet\Core;

final class Contenedor
{
    private ?Database $bd = null;
    private ?Session $sesion = null;
    private ?Auth $auth = null;
    private ?Cripto $cripto = null;

    public function __construct(
        private array $config,
        private Request $peticion
    ) {
    }

    /** Lectura con notación de punto: config('bd.host'). */
    public function config(string $ruta, mixed $porDefecto = null): mixed
    {
        $actual = $this->config;

        foreach (explode('.', $ruta) as $parte) {
            if (!is_array($actual) || !array_key_exists($parte, $actual)) {
                return $porDefecto;
            }
            $actual = $actual[$parte];
        }

        return $actual;
    }

    public function peticion(): Request
    {
        return $this->peticion;
    }

    public function bd(): Database
    {
        return $this->bd ??= Database::instancia($this->config['bd']);
    }

    public function sesion(): Session
    {
        return $this->sesion ??= new Session($this->config['sesion'], $this->peticion);
    }

    public function cripto(): Cripto
    {
        return $this->cripto ??= new Cripto(
            (string) $this->config('app.clave', ''),
            (bool) $this->config('seguridad.cifrar_sensibles', true)
        );
    }

    public function auth(): Auth
    {
        return $this->auth ??= new Auth($this);
    }

    /** URL dentro del panel: url('/voluntarios') → /leon14peru/intranet/public/voluntarios */
    public function url(string $ruta = '/'): string
    {
        return $this->peticion->base() . '/' . ltrim($ruta, '/');
    }

    /** URL del sitio público, para los enlaces «ver en la web». */
    public function urlSitio(string $ruta = '/'): string
    {
        return rtrim((string) $this->config('url.sitio', ''), '/') . '/' . ltrim($ruta, '/');
    }
    /**
     * La dirección de un archivo estático del panel, con su versión colgando.
     *
     * ── Por qué ──────────────────────────────────────────────────────────
     *
     * Sin versión, subir una hoja de estilos nueva no cambia nada para quien
     * ya tenía la vieja: el navegador la da por buena durante horas y
     * Cloudflare la sirve por su cuenta. Lo que se ve entonces es una
     * pantalla a medio maquetar y ninguna pista de por qué.
     *
     * Con un trozo del hash del contenido, cambiar el archivo cambia la
     * dirección, y una dirección nueva no está en ninguna caché. Es hash y no
     * fecha porque la fecha cambia al copiar por FTP aunque el contenido sea
     * idéntico, y eso obligaría a todo el mundo a descargarlo otra vez sin
     * motivo.
     *
     * Si el archivo no está donde se espera se devuelve la dirección pelada:
     * un panel sin estilos es feo, pero un panel que revienta al pintar la
     * cabecera no se puede usar para arreglarlo.
     */
    public function urlAsset(string $relativa): string
    {
        $ruta = ltrim($relativa, '/');
        $disco = dirname(__DIR__, 2) . '/public/' . $ruta;

        $url = $this->url('/' . $ruta);

        if (!is_file($disco)) {
            return $url;
        }

        // crc32b y no algo más fino: esto sólo tiene que cambiar cuando cambia
        // el archivo, no resistir a nadie. Y existe en toda versión de PHP,
        // así que la cabecera del panel no puede reventar por un algoritmo
        // que falte en el servidor.
        return $url . '?v=' . hash_file('crc32b', $disco);
    }
}