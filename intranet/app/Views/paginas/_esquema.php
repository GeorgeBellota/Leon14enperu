<?php
/**
 * El dibujo de una plantilla — qué forma tiene esa sección en la página.
 *
 * ── Por qué existe ─────────────────────────────────────────────────────────
 *
 * El listado de secciones enseñaba una píldora con el nombre de la plantilla.
 * El problema es que 47 de las 107 secciones del sitio usan «texto_lectura»:
 * la píldora ocupaba el sitio más visible de la fila y no distinguía nada.
 *
 * Un dibujo sí. No hay que leerlo: cuatro rayas apiladas son un bloque de
 * texto, tres rectángulos con cabecera son tarjetas, un marco con puntos
 * debajo es el carrusel. Se reconoce de un vistazo y en cualquier idioma.
 *
 * ── Cómo está hecho ────────────────────────────────────────────────────────
 *
 * SVG en línea, sin archivo externo: son veinte dibujos de pocos trazos y
 * pesan menos que la petición que costaría traerlos. Todo en `currentColor`,
 * así que heredan el color del tema y del estado —una sección apagada los
 * pinta en gris sin una sola regla extra.
 *
 * El viewBox es siempre 24×18 para que todos ocupen lo mismo y la columna
 * quede alineada.
 *
 * Recibe `$plantillaEsquema` y no `$plantilla` a propósito: quien lo incluye
 * suele tener ya un `$plantilla` con la DEFINICIÓN de la plantilla —un array—,
 * y aquí hace falta sólo su clave.
 *
 * @var string $plantillaEsquema  clave de la plantilla
 */

$trazo = 'fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"';
$macizo = 'fill="currentColor" stroke="none"';

/* Cada dibujo es la silueta de lo que esa sección pinta en la página. */
$dibujo = match ($plantillaEsquema) {

    // Una banda ancha arriba —el titular de la página— y el texto debajo.
    'cabecera_pagina' => '<rect x="1" y="1" width="22" height="7" rx="1" ' . $macizo . ' opacity=".85"/>'
                       . '<path d="M1 12h16M1 15.5h12" ' . $trazo . '/>',

    // Rayas apiladas: el párrafo corriente.
    'texto_lectura' => '<path d="M1 3h22M1 7h22M1 11h22M1 15h14" ' . $trazo . '/>',

    // Apartados: un titularcito y su párrafo, dos veces.
    'texto_apartados' => '<path d="M1 2.5h8" ' . $trazo . ' stroke-width="2"/>'
                       . '<path d="M1 6.5h22M1 9.5h17" ' . $trazo . '/>'
                       . '<path d="M1 13h8" ' . $trazo . ' stroke-width="2"/>'
                       . '<path d="M1 16.5h20" ' . $trazo . '/>',

    // Tres tarjetas con su fotografía arriba.
    'tarjetas_foto' => '<rect x="1"    y="2" width="6.6" height="14" rx="1" ' . $trazo . '/>'
                     . '<rect x="8.7"  y="2" width="6.6" height="14" rx="1" ' . $trazo . '/>'
                     . '<rect x="16.4" y="2" width="6.6" height="14" rx="1" ' . $trazo . '/>'
                     . '<path d="M1 8h6.6M8.7 8h6.6M16.4 8h6.6" ' . $trazo . ' stroke-width="3.4" opacity=".5"/>',

    // Tres tarjetas, cada una con su icono.
    'tarjetas_icono', 'destinos_aporte'
        => '<rect x="1"    y="2" width="6.6" height="14" rx="1" ' . $trazo . '/>'
         . '<rect x="8.7"  y="2" width="6.6" height="14" rx="1" ' . $trazo . '/>'
         . '<rect x="16.4" y="2" width="6.6" height="14" rx="1" ' . $trazo . '/>'
         . '<circle cx="4.3" cy="6.5" r="1.6" ' . $macizo . '/>'
         . '<circle cx="12"  cy="6.5" r="1.6" ' . $macizo . '/>'
         . '<circle cx="19.7" cy="6.5" r="1.6" ' . $macizo . '/>',

    // Retratos: una cabeza y su nombre debajo.
    'personas' => '<circle cx="4.5"  cy="6" r="3.2" ' . $trazo . '/>'
                . '<circle cx="12"   cy="6" r="3.2" ' . $trazo . '/>'
                . '<circle cx="19.5" cy="6" r="3.2" ' . $trazo . '/>'
                . '<path d="M1.8 13h5.4M9.3 13h5.4M16.8 13h5.4M2.8 16h3.4M10.3 16h3.4M17.8 16h3.4" ' . $trazo . '/>',

    // Noticias: miniatura a la izquierda, titular y entradilla a la derecha.
    'noticias' => '<rect x="1" y="2" width="6" height="6" rx="1" ' . $trazo . '/>'
                . '<path d="M9 4h14M9 7h9" ' . $trazo . '/>'
                . '<rect x="1" y="11" width="6" height="6" rx="1" ' . $trazo . '/>'
                . '<path d="M9 13h14M9 16h9" ' . $trazo . '/>',

    // Jornadas: una columna de horas con su actividad al lado.
    'jornadas' => '<path d="M4 2v15" ' . $trazo . '/>'
                . '<circle cx="4" cy="4"  r="1.8" ' . $macizo . '/>'
                . '<circle cx="4" cy="9.5" r="1.8" ' . $macizo . '/>'
                . '<circle cx="4" cy="15" r="1.8" ' . $macizo . '/>'
                . '<path d="M8.5 4h14M8.5 9.5h10M8.5 15h12" ' . $trazo . '/>',

    // Hitos: una línea de tiempo horizontal.
    'hitos' => '<path d="M1 9h22" ' . $trazo . '/>'
             . '<circle cx="3.5"  cy="9" r="2.2" ' . $macizo . '/>'
             . '<circle cx="10"   cy="9" r="2.2" ' . $macizo . '/>'
             . '<circle cx="16.5" cy="9" r="2.2" ' . $macizo . '/>'
             . '<circle cx="22"   cy="9" r="2.2" ' . $macizo . '/>',

    // Fases: cajas encadenadas, en secuencia.
    'fases' => '<rect x="1"    y="5" width="6" height="8" rx="1" ' . $trazo . '/>'
             . '<rect x="9"    y="5" width="6" height="8" rx="1" ' . $trazo . '/>'
             . '<rect x="17"   y="5" width="6" height="8" rx="1" ' . $trazo . '/>'
             . '<path d="M7.4 9h1.2M15.4 9h1.2" ' . $trazo . '/>',

    // Pasos numerados: el número y su explicación al lado.
    'pasos_numerados' => '<circle cx="3.5" cy="4"  r="2.6" ' . $trazo . '/>'
                       . '<circle cx="3.5" cy="11" r="2.6" ' . $trazo . '/>'
                       . '<path d="M8.5 3h14M8.5 6h9M8.5 10h14M8.5 13h9" ' . $trazo . '/>',

    // Accesos: botones grandes, uno al lado del otro.
    'accesos' => '<rect x="1"  y="4" width="10" height="10" rx="1.5" ' . $trazo . '/>'
               . '<rect x="13" y="4" width="10" height="10" rx="1.5" ' . $trazo . '/>'
               . '<path d="M3.5 11h5M15.5 11h5" ' . $trazo . '/>',

    // El carrusel: un marco grande y sus puntos de paso.
    'carrusel_hero' => '<rect x="1" y="1.5" width="22" height="12" rx="1" ' . $trazo . '/>'
                     . '<path d="M4 10h9" ' . $trazo . ' stroke-width="2.2" opacity=".55"/>'
                     . '<circle cx="9.5"  cy="16.4" r="1.3" ' . $macizo . '/>'
                     . '<circle cx="12"   cy="16.4" r="1.3" ' . $macizo . ' opacity=".4"/>'
                     . '<circle cx="14.5" cy="16.4" r="1.3" ' . $macizo . ' opacity=".4"/>',

    // La cuenta atrás: los recuadros de las cifras.
    'contador' => '<rect x="1"    y="5" width="4.6" height="8" rx="1" ' . $trazo . '/>'
                . '<rect x="7"    y="5" width="4.6" height="8" rx="1" ' . $trazo . '/>'
                . '<rect x="13"   y="5" width="4.6" height="8" rx="1" ' . $trazo . '/>'
                . '<rect x="18.8" y="5" width="4.2" height="8" rx="1" ' . $trazo . '/>',

    // El formulario: dos campos y su botón.
    'formulario' => '<rect x="1" y="2"  width="22" height="4.5" rx="1" ' . $trazo . '/>'
                  . '<rect x="1" y="8"  width="22" height="4.5" rx="1" ' . $trazo . '/>'
                  . '<rect x="1" y="14" width="9"  height="3.5" rx="1" ' . $macizo . ' opacity=".85"/>',

    // La colecta: la ficha de una cuenta bancaria.
    'colecta' => '<rect x="1" y="3" width="22" height="12" rx="1.5" ' . $trazo . '/>'
               . '<path d="M1 7h22" ' . $trazo . '/>'
               . '<path d="M4 11h8M16 11h4" ' . $trazo . '/>',

    // Descargas: la hoja con su flecha.
    'descargas' => '<path d="M3 1.5h9l5 5v11H3z" ' . $trazo . ' stroke-linejoin="round"/>'
                 . '<path d="M12 1.5v5h5" ' . $trazo . ' stroke-linejoin="round"/>'
                 . '<path d="M10 9.5v5M7.6 12.2 10 14.6l2.4-2.4" ' . $trazo . ' stroke-linejoin="round"/>',

    // Destacado: un bloque con su barra a un lado.
    'destacado' => '<rect x="1" y="3" width="22" height="12" rx="1" ' . $trazo . '/>'
                 . '<path d="M1 3v12" ' . $trazo . ' stroke-width="3"/>'
                 . '<path d="M6 7.5h13M6 11h9" ' . $trazo . '/>',

    // Todo lo demás: una caja sin forma definida.
    default => '<rect x="1" y="2" width="22" height="14" rx="1" ' . $trazo . ' stroke-dasharray="2.6 2.2"/>',
};
?>
<svg class="esquema" viewBox="0 0 24 18" width="24" height="18" aria-hidden="true" focusable="false"><?= $dibujo ?></svg>
