<?php
/**
 * ============================================================================
 *  Cabecera del sitio — rediseño 2026.
 * ============================================================================
 *
 *  La barra tiene 76 px de alto, la marca a la izquierda y la navegación a la
 *  derecha, exactamente como el editable. Se queda fija al desplazarse.
 *
 *  ── Lo que se decide desde el panel ──────────────────────────────────────
 *
 *   · Qué entradas se ven        → ajuste `menu.visibles`
 *   · El logotipo                → assets/img/marca/marca.(svg|png|webp|jpg)
 *   · La fecha de la cuenta atrás→ Configuración general → Fechas del viaje
 *
 *  ── La regla de las diez entradas ────────────────────────────────────────
 *
 *  El editable dibuja nueve enlaces y a 1440 px ocupan justo el ancho
 *  disponible. Como desde el panel se puede añadir cualquiera de las
 *  veintitantas páginas del sitio, hace falta una salida: a partir de ONCE
 *  entradas la navegación de escritorio se repliega en el mismo menú
 *  desplegable que ya se usa en móvil. Así se pueden publicar todas las
 *  páginas que se quiera sin que los enlaces se salgan de la pantalla ni haya
 *  que tocar una línea de código.
 *
 *  Variables que espera:
 *    $raiz     prefijo de URL hasta la raíz del sitio
 *    $activa   clave de la página actual, para marcar aria-current
 *
 *  @var string $raiz
 *  @var string $activa
 *  @var \Intranet\Publico\Sitio $sitio
 */

$raiz   = $raiz   ?? './';
$activa = $activa ?? '';

/* ── El catálogo completo ─────────────────────────────────────────────────
   TODAS las páginas que se pueden poner en el menú. No es lo que se muestra:
   es de dónde elige el panel.

   Vive en Publico\Menu y no aquí porque esta misma lista la necesitan las
   casillas de Configuración. Estuvo escrita por duplicado, las dos copias se
   separaron al renombrarse tres páginas en el rediseño, y el panel acabó
   guardando claves que la web ya no reconocía: la casilla salía marcada y la
   entrada no aparecía. Una sola lista y dos lectores. */
$catalogo = \Intranet\Publico\Menu::catalogo();

/* ── Qué entradas se ven ──────────────────────────────────────────────────
   El ajuste `menu.visibles` lleva las claves separadas por comas y manda
   sobre todo lo demás. Si está vacío —o si la base no responde— se muestran
   las nueve del diseño: es la navegación que se dibujó y la que cabe sin
   apretar. Antes, el vacío significaba «todas», y con veinte páginas
   publicadas eso llenaba la barra de enlaces. */
$porDefecto = \Intranet\Publico\Menu::porDefecto();

$menu = array_intersect_key($catalogo, array_flip($porDefecto));

if (isset($sitio) && $sitio instanceof \Intranet\Publico\Sitio) {
    /* El try es lo que hace cierto el párrafo de arriba: sin él, `ajuste()`
       con la base caída se llevaba por delante la página entera —se pintaba
       la cabeza del documento y ahí se cortaba todo—, y la reserva no llegaba
       a entrar nunca porque la excepción ocurría antes de poder usarla. */
    try {
        $visibles = (string) $sitio->catalogo()->ajuste('menu.visibles', '');

        /* normalizar() traduce las páginas que se renombraron en el rediseño
           y descarta lo que ya no existe: un ajuste guardado antes de la
           mudanza sigue valiendo y no deja la entrada fuera del menú. */
        $permitidas = \Intranet\Publico\Menu::normalizar(explode(',', $visibles));

        if ($permitidas !== []) {
            /* Se respeta el orden en el que se escribieron en el panel. */
            $menu = [];
            foreach ($permitidas as $clave) {
                $menu[$clave] = $catalogo[$clave];
            }
        }
    } catch (\Throwable $e) {
        error_log('[cabecera] no se pudo leer menu.visibles: ' . $e->getMessage());
    }
}

$esc = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

/* A partir de once entradas la barra de escritorio no da más de sí: se
   repliega en el desplegable. Ver la nota de arriba. */
$menuCompacto = count($menu) > 10;

/* ── El logotipo ───────────────────────────────────────────────────────────
   Si desde el panel se sube uno, se pinta en lugar del texto. Se comprueba el
   ARCHIVO y no un ajuste guardado: si alguien lo borra por FTP, la cabecera
   vuelve al texto sola en vez de enseñar una imagen rota.

   El texto no es un apaño: «León XIV / EN PERÚ» compuesto en Neulis es la
   marca del editable. */
$logotipo = null;
$rutaLogo = dirname(__DIR__, 2) . '/assets/img/marca';

foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
    if (is_file($rutaLogo . '/marca.' . $ext)) {
        $logotipo = 'assets/img/marca/marca.' . $ext;
        break;
    }
}

$pintarMarca = static function () use ($logotipo, $esc, $raiz): void {
    if ($logotipo !== null) {
        echo '<img class="brand__logo" src="' . $esc($raiz . $logotipo)
           . '" alt="León XIV en el Perú" width="180" height="48">';
        return;
    }
    ?>
    <span class="brand__name">León XIV</span>
    <span class="brand__sub">EN PERÚ</span>
    <?php
};

/* ── La cuenta atrás ──────────────────────────────────────────────────────
   El editable la dibuja grande, en la banda dorada de la portada. Pero quien
   entra por un enlace compartido a «Voluntariado» no pasa por la portada, así
   que en el resto de páginas se conserva la tira fina de arriba: la misma
   información, sin robarle sitio a la cabecera de 76 px.

   La fecha sale del panel. Si la base no responde, el atributo no se escribe
   y rediseno.js usa su fecha de reserva —el 11 de noviembre de 2026, la
   anunciada por la Santa Sede—, que es mejor que un hueco.

   Las tres fases del viaje (antes, durante y después) las enciende el CSS a
   partir del data-phase del <body>: el 11 de noviembre la tira cambia sola. */
$objetivo = '';

if (isset($sitio) && $sitio instanceof \Intranet\Publico\Sitio) {
    try {
        $objetivo = $sitio->objetivoCuentaAtras();
    } catch (\Throwable $e) {
        error_log('[cabecera] no se pudo leer la fecha del viaje: ' . $e->getMessage());
    }
}

$tiraCuenta = $activa !== 'home';
?>
<?php if ($tiraCuenta): ?>
<div class="tira-cuenta" data-countdown<?= $objetivo !== '' ? ' data-objetivo="' . $esc($objetivo) . '"' : '' ?>>
  <div class="tira-cuenta__inner">
    <p class="tira-cuenta__reloj fase-pre">
      <span class="tira-cuenta__rotulo">Faltan</span>
      <span class="tira-cuenta__par"><b data-cd="dias">00</b><span>días</span></span>
      <span class="tira-cuenta__par"><b data-cd="horas">00</b><span>horas</span></span>
      <span class="tira-cuenta__par"><b data-cd="minutos">00</b><span>minutos</span></span>
      <span class="tira-cuenta__par"><b data-cd="segundos">00</b><span>segundos</span></span>
      <span class="tira-cuenta__cierre">para recibir al Papa León XIV en el Perú</span>
    </p>
    <p class="tira-cuenta__aviso fase-live">El Santo Padre está en el Perú</p>
    <p class="tira-cuenta__aviso fase-post">Gracias, Santo Padre</p>
  </div>
</div>
<?php endif; ?>

<header class="site-header<?= $menuCompacto ? ' site-header--compacto' : '' ?>">
  <div class="site-header__inner">
    <a class="brand" href="<?= $esc($raiz) ?>" aria-label="León XIV en el Perú, ir al inicio">
      <?php $pintarMarca(); ?>
    </a>

    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Abrir menú">
      <span class="nav-toggle__bars"></span>
    </button>

    <nav class="nav" id="site-nav" aria-label="Navegación principal">
      <?php foreach ($menu as $clave => $rotulo): ?>
      <a class="nav__link<?= $clave === $activa ? ' is-active' : '' ?>"
         href="<?= $esc($raiz . $clave . '/') ?>"<?= $clave === $activa ? ' aria-current="page"' : '' ?>><?= $esc($rotulo) ?></a>
      <?php endforeach; ?>
      <?php /* En el desplegable, el botón de voluntariado va dentro; en la barra
               de escritorio no aparece, porque el editable no lo dibuja. */ ?>
      <a class="btn nav__cta" href="<?= $esc($raiz) ?>voluntariado/">Sé voluntario</a>
    </nav>
  </div>
</header>
