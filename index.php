<?php
/**
 * ============================================================================
 *  PUNTO DE ENTRADA ÚNICO DEL SITIO PÚBLICO
 * ============================================================================
 *
 *  Todas las direcciones se sirven desde aquí. Antes cada página era una
 *  carpeta con su index.php dentro —diecinueve carpetas, diecinueve copias
 *  del mismo <head>— y publicar una nueva exigía crear otra.
 *
 *  Ahora una página son dos cosas: una línea en Publico\Rutas y un archivo en
 *  views/. Ni carpeta, ni bootstrap repetido.
 *
 *  ── Lo que esto arregla de paso ──────────────────────────────────────────
 *
 *  El despachador anterior servía la página de inicio capturando su salida y
 *  reescribiendo «../» con una expresión regular, porque las internas
 *  escribían sus rutas a mano y desde la raíz apuntaban fuera del sitio. Ese
 *  parche desaparece: las rutas las construye Sitio::asset desde la raíz.
 *
 *  ── Lo que NO cambia ─────────────────────────────────────────────────────
 *
 *  Las direcciones. /voluntariado/ sigue siendo /voluntariado/. Nada de lo
 *  que esté indexado o compartido deja de funcionar.
 */

declare(strict_types=1);

/** @var \Intranet\Publico\Sitio $sitio */
$sitio = require __DIR__ . '/intranet/app/Publico/arranque.php';

use Intranet\Publico\Rutas;

$esc = static fn ($v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$peticion = $sitio->peticion();
$ruta     = trim($peticion->ruta(), '/');

// ── La raíz ───────────────────────────────────────────────────────────────
//
// Qué se muestra en «/» lo decide el ajuste `sitio.pagina_inicio`, que se
// cambia desde el panel. Es el equivalente a la página de inicio de
// WordPress. La dirección NO cambia: quien entra a leon14enperu.com sigue
// viendo leon14enperu.com, no una redirección a una interna. Eso importa
// para compartir el enlace y para que Google indexe el dominio.
if ($ruta === '') {
    $inicio = 'home';

    try {
        $valor = $sitio->catalogo()->ajuste('sitio.pagina_inicio');

        if (is_string($valor) && $valor !== '') {
            $inicio = $valor;
        }
    } catch (Throwable $e) {
        // Si la base no responde, la raíz muestra la portada. El ajuste es
        // una comodidad, no un requisito para que el sitio funcione.
        error_log('[inicio] no se pudo leer sitio.pagina_inicio: ' . $e->getMessage());
    }

    $destino = ($inicio === 'home' || $inicio === '' || !Rutas::existe($inicio))
        ? ['clave' => 'home', 'vista' => 'portada']
        : Rutas::resolver($inicio);
} else {
    $destino = Rutas::resolver($ruta);

    /* ── Una pieza con dirección propia ───────────────────────────────────
     *
     * Si la ruta entera no es una página, se prueba a leerla como
     * «página / slug»:
     *
     *     /sedes/lima/                     → la sede Lima
     *     /noticias/leon-xiv-regresa-…/    → esa noticia
     *     /cep/comisiones/liturgia/        → esa comisión
     *
     * El slug se busca sólo dentro de las secciones marcadas con
     * «detalle»: las láminas del carrusel o las jornadas del itinerario no
     * tienen página aunque se repitan, y no queremos que una dirección
     * inventada acabe pintando una tarjeta suelta.
     *
     * Se separa por la ÚLTIMA barra, para que /cep/comisiones/liturgia/
     * resuelva contra la página «cep/comisiones» y no contra «cep».
     */
    if ($destino === null && str_contains($ruta, '/')) {
        $corte  = strrpos($ruta, '/');
        $padre  = substr($ruta, 0, $corte);
        $slug   = substr($ruta, $corte + 1);
        $pagina = Rutas::resolver($padre);

        if ($pagina !== null && $slug !== '') {
            try {
                $modelo = new \Intranet\Models\Pagina($sitio->contenedor());
                $pieza  = $modelo->piezaPorSlug($pagina['clave'], $slug);

                if ($pieza !== null) {
                    // En «padre» va la FICHA de la base, no la entrada del mapa
                    // de rutas: la comprobación de «publicada» de más abajo mira
                    // su columna `activa`, y la entrada del mapa no la tiene.
                    $destino = [
                        'clave' => $pagina['clave'],
                        'vista' => 'detalle',
                        'padre' => $modelo->porClave($pagina['clave']),
                        'pieza' => $pieza,
                    ];
                }
            } catch (Throwable $e) {
                error_log('[rutas] no se pudo resolver la pieza «' . $slug . '»: ' . $e->getMessage());
            }
        }
    }
}

// ── ¿Está publicada? ──────────────────────────────────────────────────────
//
// La ruta existe en el mapa, pero una página puede estar preparada y todavía
// no abierta al público. Se decide desde el panel, con el interruptor de
// «Publicada» de cada página.
//
// Sin base de datos NO se oculta nada: si MySQL no responde, se sirve la
// página. Es deliberado —una web informativa que se apaga sola porque falló
// la base es peor que una página de más—, y además la portada del sitio
// depende de esto.
if ($destino !== null) {
    try {
        $ficha = $destino['padre']
            ?? (new \Intranet\Models\Pagina($sitio->contenedor()))->porClave($destino['clave']);

        if ($ficha !== null && array_key_exists('activa', $ficha)
            && (int) $ficha['activa'] === 0) {
            $destino = null;
        }
    } catch (Throwable $e) {
        error_log('[rutas] no se pudo comprobar si la página está publicada: ' . $e->getMessage());
    }
}

// ── No existe ─────────────────────────────────────────────────────────────
if ($destino === null) {
    http_response_code(404);

    $pagina404 = __DIR__ . '/404.html';

    if (is_file($pagina404)) {
        readfile($pagina404);
        exit;
    }

    echo '<!doctype html><meta charset="utf-8"><title>Página no encontrada</title>'
       . '<h1>Página no encontrada</h1>';
    exit;
}

// ── Se pinta ──────────────────────────────────────────────────────────────
//
// La vista escribe su <main> y declara su $meta; se recoge su salida y la
// plantilla la envuelve. Al ejecutarse las dos en este mismo ámbito, la
// plantilla ve el $meta que la vista acaba de declarar.
$activa = $destino['clave'];
$meta   = [];

/* Sólo la tiene la vista de detalle: es la pieza que se pidió por su slug. */
$pieza  = $destino['pieza'] ?? null;
$padre  = $destino['padre'] ?? null;

$archivoVista = __DIR__ . '/views/' . $destino['vista'] . '.php';

if (!is_file($archivoVista)) {
    error_log('[rutas] falta la vista: ' . $destino['vista']);
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><title>Error</title><h1>Error temporal</h1>';
    exit;
}

ob_start();
require $archivoVista;
$contenido = (string) ob_get_clean();

require __DIR__ . '/views/_plantilla.php';
