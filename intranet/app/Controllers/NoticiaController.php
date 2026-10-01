<?php
/**
 * ============================================================================
 *  NoticiaController — el gestor de noticias y de los vídeos de esa página.
 * ============================================================================
 *
 *  Pantalla propia, fuera de «Páginas», como pidió el cliente. No es sólo
 *  orden: una noticia no se parece a una sección. Tiene fecha, estado,
 *  dirección propia, SEO y un cuerpo con imágenes dentro. El editor de
 *  secciones guarda borrando y recreando las piezas, y eso con un texto
 *  largo es jugársela en cada «Guardar».
 *
 *  ── Lo que entra por el formulario no se guarda tal cual ────────────────
 *
 *  El cuerpo viene de un editor enriquecido, o sea HTML escrito en el
 *  navegador. Pasa por HtmlSeguro antes de tocar la base: deja <p>, <strong>,
 *  <a>, <img> y poco más, y tira <script>, los atributos «on…» y los enlaces
 *  «javascript:». Un panel con un editor de HTML sin filtro es una puerta
 *  abierta a la web pública.
 *
 *  ── Los vídeos ──────────────────────────────────────────────────────────
 *
 *  Se pega el enlace de YouTube y el servidor trae el título y DESCARGA la
 *  portada a la biblioteca. No se enlaza a i.ytimg.com: la CSP del sitio es
 *  «img-src 'self'» y abrirla haría que el navegador del visitante pidiera
 *  esa imagen a Google nada más abrir /noticias/, antes de aceptar nada.
 */

declare(strict_types=1);

namespace Intranet\Controllers;

use Intranet\Core\Adjunto;
use Intranet\Core\Auditoria;
use Intranet\Core\Controller;
use Intranet\Core\ErrorDeNegocio;
use Intranet\Core\HtmlSeguro;
use Intranet\Core\Imagen;
use Intranet\Core\Request;
use Intranet\Core\YouTube;
use Intranet\Models\Medio;
use Intranet\Models\Noticia;

final class NoticiaController extends Controller
{
    private const CARPETA = 'assets/subidos/paginas';

    private function noticias(): Noticia
    {
        return new Noticia($this->c);
    }

    // ── Listado ────────────────────────────────────────────────────────

    public function listar(Request $peticion): void
    {
        $modelo = $this->noticias();
        $estado = $peticion->texto('estado', '');

        $this->ver('noticias/listar', [
            'titulo'      => 'Noticias',
            'listado'     => $modelo->listar($estado, (int) $peticion->texto('pagina', '1')),
            'estado'      => $estado,
            'aproximadas' => $modelo->conFechaAproximada(),
        ]);
    }

    // ── Crear y editar ─────────────────────────────────────────────────

    public function nueva(Request $peticion): void
    {
        $this->ver('noticias/editar', [
            'titulo'     => 'Nueva noticia',
            'noticia'    => null,
            'biblioteca' => (new Medio($this->c))->paraElegir(),
            'carpeta'    => self::CARPETA,
        ]);
    }

    public function editar(Request $peticion, array $params = []): void
    {
        $noticia = $this->noticias()->porId((int) ($params['id'] ?? 0));

        if ($noticia === null) {
            $this->conError('Esa noticia ya no existe.', '/noticias');
        }

        $this->ver('noticias/editar', [
            'titulo'     => $noticia['titulo'],
            'noticia'    => $noticia,
            'biblioteca' => (new Medio($this->c))->paraElegir(),
            'carpeta'    => self::CARPETA,
        ]);
    }

    public function guardar(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $id     = (int) ($params['id'] ?? 0);
        $modelo = $this->noticias();

        $existente = $id > 0 ? $modelo->porId($id) : null;

        if ($id > 0 && $existente === null) {
            $this->conError('Esa noticia ya no existe.', '/noticias');
        }

        $destino = $id > 0 ? '/noticias/' . $id : '/noticias/nueva';

        $titular = trim($peticion->texto('titulo', ''));

        if ($titular === '') {
            $this->conError('La noticia necesita un titular.', $destino);
        }

        $fecha = $this->fechaValida($peticion->texto('fecha', ''));

        if ($fecha === null) {
            $this->conError('Pon una fecha de publicación válida.', $destino);
        }

        $imagenId = (int) $peticion->texto('imagen_id', '0');
        $ogId     = (int) $peticion->texto('og_imagen_id', '0');

        $datos = [
            'titulo'  => mb_substr($titular, 0, 255),
            'slug'    => $modelo->slugLibre($titular, $peticion->texto('slug', ''), $id > 0 ? $id : null),
            'resumen' => mb_substr(trim($peticion->texto('resumen', '')), 0, 500) ?: null,

            /* ── El filtro ────────────────────────────────────────────────
               Aquí es donde el HTML del editor deja de ser lo que escribió
               el navegador y pasa a ser lo que esta web admite. */
            'cuerpo'  => HtmlSeguro::limpiar($peticion->post('cuerpo', '')) ?: null,

            'imagen_id' => $imagenId > 0 ? $imagenId : null,
            'fecha'     => $fecha,

            /* Si alguien le pone fecha a mano, deja de ser aproximada. Es lo
               que vacía la lista de «por revisar» sin un botón aparte. */
            'fecha_aproximada' => 0,

            'fuente' => mb_substr(trim($peticion->texto('fuente', '')), 0, 160) ?: null,
            'estado' => $peticion->texto('estado', 'borrador') === 'publicada' ? 'publicada' : 'borrador',

            'seo_titulo'      => mb_substr(trim($peticion->texto('seo_titulo', '')), 0, 190) ?: null,
            'seo_descripcion' => mb_substr(trim($peticion->texto('seo_descripcion', '')), 0, 255) ?: null,
            'og_imagen_id'    => $ogId > 0 ? $ogId : null,
        ];

        if ($id > 0) {
            $modelo->actualizar($id, $datos);
            Auditoria::registrar($this->c, 'editar', 'noticias', $id, ['titulo' => $titular]);
        } else {
            $datos['creado_por'] = $this->c->auth()->id();
            $id = $modelo->crear($datos);
            Auditoria::registrar($this->c, 'crear', 'noticias', $id, ['titulo' => $titular]);
        }

        if ($peticion->casilla('destacada')) {
            $modelo->destacar($id);
        }

        $this->conExito(
            $datos['estado'] === 'publicada'
                ? 'Noticia publicada.'
                : 'Guardada como borrador: todavía no se ve en la web.',
            '/noticias/' . $id
        );
    }

    public function borrar(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $id      = (int) ($params['id'] ?? 0);
        $noticia = $this->noticias()->porId($id);

        if ($noticia === null) {
            $this->conError('Esa noticia ya no existe.', '/noticias');
        }

        $this->noticias()->eliminar($id);

        Auditoria::registrar($this->c, 'borrar', 'noticias', $id, ['titulo' => $noticia['titulo']]);

        $this->conExito('Noticia borrada. Su fotografía sigue en la biblioteca.', '/noticias');
    }

    /**
     * Sube una imagen desde el editor y devuelve su ruta en JSON.
     *
     * El editor la pide por detrás para poder insertarla en el cuerpo sin
     * sacar a nadie del formulario: salir a la biblioteca a media redacción
     * significaría perder lo escrito.
     */
    public function subirDelCuerpo(Request $peticion): void
    {
        $this->exigirCsrf($peticion);

        header('Content-Type: application/json; charset=utf-8');

        $archivo = $_FILES['imagen'] ?? null;

        if (!is_array($archivo) || (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            http_response_code(422);
            echo json_encode(['error' => 'No llegó ninguna imagen.'], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {
            $carpeta  = dirname(__DIR__, 3) . '/' . self::CARPETA;
            $almacen  = new Adjunto($carpeta, 20);
            $subida   = $almacen->imagen($archivo, 'img');
            $completa = dirname(__DIR__, 3) . '/' . $subida;

            $base     = pathinfo($subida, PATHINFO_FILENAME);
            $motor    = new Imagen($carpeta);
            $esVector = str_ends_with(strtolower($subida), '.svg');

            $procesada = $esVector
                ? $motor->vector($completa, $base)
                : $motor->derivar($completa, $base);

            $medioId = (new Medio($this->c))->registrar(
                $procesada,
                Adjunto::nombreLegible((string) ($archivo['name'] ?? 'imagen')),
                trim($peticion->texto('alt', '')),
                false,
                $this->c->auth()->id(),
                $esVector ? null : $subida,
                $esVector ? null : (int) @filesize($completa)
            );
        } catch (ErrorDeNegocio $e) {
            http_response_code(422);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);

            return;
        }

        Auditoria::registrar($this->c, 'crear', 'medios', $medioId, ['origen' => 'editor de noticias']);

        echo json_encode([
            // Ruta relativa a la raíz: es la única forma que HtmlSeguro
            // admite en un src, y la que sobrevive a un cambio de dominio.
            'src' => '/' . ltrim($procesada['ruta'], '/'),
            'id'  => $medioId,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // ── Vídeos ─────────────────────────────────────────────────────────

    public function videos(Request $peticion): void
    {
        $this->ver('noticias/videos', [
            'titulo' => 'Vídeos de Noticias',
            'videos' => $this->noticias()->videos(false),
            'tope'   => Noticia::VIDEOS_VISIBLES,
        ]);
    }

    /**
     * Añade un vídeo pegando su enlace.
     *
     * El título y la portada se traen solos, que es lo que pidió el cliente:
     * «sin necesidad de subir imágenes manualmente».
     */
    public function anadirVideo(Request $peticion): void
    {
        $this->exigirCsrf($peticion);

        $url = trim($peticion->texto('url', ''));
        $yt  = YouTube::identificador($url);

        if ($yt === null) {
            $this->conError(
                'Ese enlace no parece de YouTube. Pega la dirección del vídeo, '
                . 'como «https://www.youtube.com/watch?v=…».',
                '/noticias/videos'
            );
        }

        $modelo = $this->noticias();

        if ($modelo->videoPorYoutube($yt) !== null) {
            $this->conError('Ese vídeo ya está en la lista.', '/noticias/videos');
        }

        /* Si YouTube no contesta, el vídeo se guarda igual con el título a
           mano: que una red lenta impida añadirlo sería absurdo. */
        $ficha = YouTube::ficha($yt);

        $datos = [
            'url'         => mb_substr($url, 0, 255),
            'youtube_id'  => $yt,
            'titulo'      => $ficha['titulo'] ?? mb_substr(trim($peticion->texto('titulo', '')), 0, 255) ?: null,
            'autor'       => $ficha['autor'] ?? null,
            'descripcion' => trim($peticion->texto('descripcion', '')) ?: null,
            'miniatura_id' => $this->guardarPortada($yt, $ficha['titulo'] ?? $yt),
        ];

        $id = $modelo->crearVideo($datos);

        Auditoria::registrar($this->c, 'crear', 'noticias_videos', $id, ['youtube' => $yt]);

        $aviso = 'Vídeo añadido.';

        if (($datos['titulo'] ?? null) === null) {
            $aviso .= ' YouTube no devolvió el título: escríbelo abajo.';
        }

        if ($datos['miniatura_id'] === null) {
            $aviso .= ' No se pudo traer la portada: elige una imagen a mano.';
        }

        $this->conExito($aviso, '/noticias/videos');
    }

    public function guardarVideo(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $id = (int) ($params['id'] ?? 0);

        if ($this->noticias()->video($id) === null) {
            $this->conError('Ese vídeo ya no existe.', '/noticias/videos');
        }

        $this->noticias()->guardarVideo($id, [
            'titulo'      => mb_substr(trim($peticion->texto('titulo', '')), 0, 255) ?: null,
            'descripcion' => trim($peticion->texto('descripcion', '')) ?: null,
            'activo'      => $peticion->casilla('activo') ? 1 : 0,
        ]);

        $this->redirigir('/noticias/videos');
    }

    /** Vuelve a pedir a YouTube el título y la portada. */
    public function refrescarVideo(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $id    = (int) ($params['id'] ?? 0);
        $video = $this->noticias()->video($id);

        if ($video === null) {
            $this->conError('Ese vídeo ya no existe.', '/noticias/videos');
        }

        $yt    = (string) $video['youtube_id'];
        $ficha = YouTube::ficha($yt);

        if ($ficha === []) {
            $this->conError(
                'YouTube no respondió. Puede que el vídeo sea privado o se haya borrado.',
                '/noticias/videos'
            );
        }

        $cambios = [
            'titulo' => $ficha['titulo'] ?? $video['titulo'],
            'autor'  => $ficha['autor'] ?? $video['autor'],
        ];

        $nueva = $this->guardarPortada($yt, (string) $cambios['titulo']);

        if ($nueva !== null) {
            $cambios['miniatura_id'] = $nueva;
        }

        $this->noticias()->guardarVideo($id, $cambios);

        $this->conExito('Título y portada actualizados desde YouTube.', '/noticias/videos');
    }

    public function borrarVideo(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $id = (int) ($params['id'] ?? 0);
        $this->noticias()->borrarVideo($id);

        Auditoria::registrar($this->c, 'borrar', 'noticias_videos', $id, []);

        $this->conExito('Vídeo quitado. Su portada sigue en la biblioteca.', '/noticias/videos');
    }

    public function ordenarVideo(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $this->noticias()->moverVideo(
            (int) ($params['id'] ?? 0),
            $peticion->texto('direccion', 'bajar') === 'subir' ? -1 : 1
        );

        $this->redirigir('/noticias/videos');
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    /**
     * Descarga la portada de YouTube y la mete en la biblioteca.
     *
     * Devuelve el id del medio, o null si no se pudo. Null no es un error
     * fatal: el vídeo se guarda igual y se avisa para poner una a mano.
     */
    private function guardarPortada(string $youtubeId, string $nombre): ?int
    {
        $temporal = YouTube::portada($youtubeId);

        if ($temporal === null) {
            return null;
        }

        try {
            $carpeta = dirname(__DIR__, 3) . '/' . self::CARPETA;
            $base    = 'yt-' . $youtubeId . '-' . bin2hex(random_bytes(3));
            $destino = $carpeta . '/' . $base . '.jpg';

            if (!@copy($temporal, $destino)) {
                return null;
            }

            @chmod($destino, 0644);

            $procesada = (new Imagen($carpeta))->derivar($destino, $base);

            return (new Medio($this->c))->registrar(
                $procesada,
                mb_substr($nombre, 0, 190) . '.jpg',
                'Portada del vídeo «' . mb_substr($nombre, 0, 150) . '»',
                false,
                $this->c->auth()->id(),
                self::CARPETA . '/' . $base . '.jpg',
                (int) @filesize($destino)
            );
        } catch (ErrorDeNegocio) {
            return null;
        } finally {
            @unlink($temporal);
        }
    }

    /** Una fecha del formulario, o null si no es un día del calendario. */
    private function fechaValida(string $valor): ?string
    {
        $valor = trim($valor);

        if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $valor) !== 1) {
            return null;
        }

        [$a, $m, $d] = array_map('intval', explode('-', $valor));

        return checkdate($m, $d, $a) ? $valor : null;
    }
}
