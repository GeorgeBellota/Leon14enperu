<?php
/**
 * ComunicadoController — los avisos que aparecen sobre la web.
 *
 * Publicar, retirar y consultar las cifras. Un comunicado retirado no se
 * borra: queda en la lista con sus números, que es lo que permite comparar
 * unos con otros.
 */

declare(strict_types=1);

namespace Intranet\Controllers;

use Intranet\Core\Adjunto;
use Intranet\Core\Auditoria;
use Intranet\Core\Controller;
use Intranet\Core\ErrorDeNegocio;
use Intranet\Core\Request;
use Intranet\Models\Comunicado;
use Throwable;

final class ComunicadoController extends Controller
{
    /** Dónde van las imágenes y los documentos que se suben aquí. */
    private function almacen(): Adjunto
    {
        return new Adjunto(dirname(__DIR__, 3) . '/assets/subidos/comunicados', 20);
    }

    public function listar(Request $peticion): void
    {
        $modelo = new Comunicado($this->c);

        $this->ver('comunicados/listar', [
            'titulo'      => 'Comunicados',
            'comunicados' => $modelo->todos(),
            'paginas'     => self::paginasDelSitio(),
        ]);
    }

    public function nuevo(Request $peticion): void
    {
        $this->ver('comunicados/editar', [
            'titulo' => 'Nuevo comunicado',
            // `com` y no `c`: en las vistas, $c es el contenedor. Pasar el
            // comunicado con esa clave lo machacaría y la vista se quedaría
            // sin poder construir una sola URL.
            'com'    => null,
            'paginas' => self::paginasDelSitio(),
        ]);
    }

    public function editar(Request $peticion, array $params = []): void
    {
        $modelo = new Comunicado($this->c);
        $fila = $modelo->buscar((int) ($params['id'] ?? 0));

        if ($fila === null) {
            $this->conError('Ese comunicado no existe.', '/comunicados');
        }

        $this->ver('comunicados/editar', [
            'titulo'  => $fila['nombre'],
            'com'     => $fila,
            'paginas' => self::paginasDelSitio(),
        ]);
    }

    public function guardar(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $modelo = new Comunicado($this->c);
        $id = (int) ($params['id'] ?? 0);
        $anterior = $id > 0 ? $modelo->buscar($id) : null;

        if ($id > 0 && $anterior === null) {
            $this->conError('Ese comunicado no existe.', '/comunicados');
        }

        $nombre = trim($peticion->texto('nombre', ''));

        if ($nombre === '') {
            $this->conError('Ponle un nombre para reconocerlo en esta lista.', $id > 0 ? "/comunicados/{$id}" : '/comunicados/nuevo');
        }

        $tipo = $peticion->texto('boton_tipo', 'enlace');
        $tipo = in_array($tipo, ['enlace', 'descarga'], true) ? $tipo : 'enlace';

        $datos = [
            'nombre'        => mb_substr($nombre, 0, 120),
            'descripcion'   => mb_substr(trim($peticion->texto('descripcion', '')), 0, 2000),
            'boton_texto'   => mb_substr(trim($peticion->texto('boton_texto', 'Ver más')) ?: 'Ver más', 0, 80),
            'boton_tipo'    => $tipo,
            'paginas'       => $this->paginasElegidas($peticion),
            'veces_max'     => max(0, min(50, (int) $peticion->texto('veces_max', '3'))),
            'retraso_ms'    => max(0, min(60000, (int) $peticion->texto('retraso_ms', '3000'))),
            'autocierre_ms' => max(0, min(120000, (int) $peticion->texto('autocierre_ms', '0'))),
            'activo'        => $peticion->texto('activo', '') !== '' ? 1 : 0,
            'expira_en'     => $this->fechaExpiracion($peticion),
        ];

        try {
            // ── La imagen ────────────────────────────────────────────────
            $imagen = $_FILES['imagen'] ?? null;

            if (is_array($imagen) && (int) ($imagen['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $datos['imagen'] = $this->almacen()->imagen($imagen, 'com');

                // Se borra la anterior DESPUÉS de guardar la nueva: si la
                // subida falla, el comunicado se queda con la que tenía en vez
                // de quedarse sin ninguna.
                if ($anterior !== null) {
                    $this->almacen()->borrar($anterior['imagen'] ?? null);
                }
            }

            // ── El destino del botón ─────────────────────────────────────
            if ($tipo === 'descarga') {
                $archivo = $_FILES['archivo'] ?? null;

                if (is_array($archivo) && (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $datos['boton_destino']  = $this->almacen()->documento($archivo, 'doc');
                    $datos['archivo_nombre'] = Adjunto::nombreLegible((string) $archivo['name']);

                    if ($anterior !== null && ($anterior['boton_tipo'] ?? '') === 'descarga') {
                        $this->almacen()->borrar($anterior['boton_destino'] ?? null);
                    }
                } elseif ($anterior === null || ($anterior['boton_tipo'] ?? '') !== 'descarga') {
                    throw new ErrorDeNegocio('Sube el archivo que quieres que se descargue.', 'archivo');
                }
            } else {
                $enlace = trim($peticion->texto('boton_destino', ''));

                if ($enlace === '') {
                    throw new ErrorDeNegocio('Escribe la dirección a la que lleva el botón.', 'boton_destino');
                }

                // Sólo http y https. Un `javascript:` en ese campo convertiría
                // el botón del comunicado en un script que se ejecuta en el
                // navegador de cada visitante.
                if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $enlace)
                    && !preg_match('#^https?://#i', $enlace)) {
                    throw new ErrorDeNegocio(
                        'La dirección tiene que empezar por http:// o https://, o ser una página de este sitio.',
                        'boton_destino'
                    );
                }

                $datos['boton_destino']  = mb_substr($enlace, 0, 500);
                $datos['archivo_nombre'] = null;
            }
        } catch (ErrorDeNegocio $e) {
            $this->conError($e->getMessage(), $id > 0 ? "/comunicados/{$id}" : '/comunicados/nuevo');
        }

        if ($id > 0) {
            $modelo->actualizar($id, $datos);
        } else {
            $datos['creado_por'] = $this->c->auth()->id();
            $id = $modelo->crear($datos);
        }

        Auditoria::registrar($this->c, $anterior === null ? 'crear' : 'editar', 'comunicados', $id, [
            'nombre' => $datos['nombre'],
            'activo' => $datos['activo'],
            'tipo'   => $tipo,
        ]);

        $this->conExito(
            $datos['activo'] ? 'Comunicado guardado y publicado.' : 'Comunicado guardado, sin publicar.',
            '/comunicados'
        );
    }

    /** Publicar o retirar sin abrir el formulario entero. */
    public function alternar(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $modelo = new Comunicado($this->c);
        $id = (int) ($params['id'] ?? 0);
        $fila = $modelo->buscar($id);

        if ($fila === null) {
            $this->conError('Ese comunicado no existe.', '/comunicados');
        }

        $nuevo = ((int) $fila['activo']) === 1 ? 0 : 1;
        $modelo->actualizar($id, ['activo' => $nuevo]);

        Auditoria::registrar($this->c, 'editar', 'comunicados', $id, [
            'accion' => $nuevo ? 'publicado' : 'retirado',
        ]);

        $this->conExito(
            $nuevo ? 'Comunicado publicado: ya se ve en la web.' : 'Comunicado retirado. Sus cifras se conservan.',
            '/comunicados'
        );
    }

    /**
     * La lista de páginas del sitio, para elegir dónde aparece.
     *
     * @return array<string, string>
     */
    public static function paginasDelSitio(): array
    {
        return [
            'home'                 => 'Portada',
            'voluntariado'         => 'Voluntariado',
            'agenda'               => 'Agenda',
            'sedes'                => 'Sedes',
            'noticias'             => 'Noticias',
            'el-papa'              => 'El Papa',
            'cep'                  => 'Conferencia Episcopal',
            'preguntas-frecuentes' => 'Preguntas frecuentes',
            'en-directo'           => 'En directo',
            'prensa'               => 'Prensa',
            'patrocinios'          => 'Patrocinios',
            'donativo'             => 'Donativo',
            'contacto'             => 'Contacto',
        ];
    }

    /** Lo elegido en el formulario, en el formato que guarda la tabla. */
    private function paginasElegidas(Request $peticion): string
    {
        $modo = $peticion->texto('paginas_modo', 'todas');

        if ($modo === 'todas') {
            return '';
        }

        $marcadas = $peticion->post('paginas', []);
        $marcadas = is_array($marcadas)
            ? array_values(array_intersect($marcadas, array_keys(self::paginasDelSitio())))
            : [];

        if ($marcadas === []) {
            return '';
        }

        // «menos» guarda las claves con «!» delante: el modelo lo entiende como
        // «en todas salvo en éstas».
        if ($modo === 'menos') {
            $marcadas = array_map(static fn (string $p): string => '!' . $p, $marcadas);
        }

        return implode(',', $marcadas);
    }

    /** La fecha de caducidad, o null si no se puso. */
    private function fechaExpiracion(Request $peticion): ?string
    {
        $dia = trim($peticion->texto('expira_en', ''));

        if ($dia === '') {
            return null;
        }

        $f = \DateTimeImmutable::createFromFormat('!Y-m-d', $dia);

        if ($f === false || $f->format('Y-m-d') !== $dia) {
            return null;
        }

        // Hasta el final de ese día: quien escribe «31 de agosto» quiere que se
        // vea el 31, no que desaparezca a medianoche del 30.
        return $f->format('Y-m-d') . ' 23:59:59';
    }
}
