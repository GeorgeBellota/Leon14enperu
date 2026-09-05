<?php
/**
 * Cabecera y menú móvil, comunes a todas las páginas.
 *
 * Variables que espera:
 *   $raiz     prefijo de URL hasta la raíz del sitio ('../' desde una
 *             subcarpeta, './' desde la portada).
 *   $activa   clave de la página actual, para marcar aria-current.
 *
 * @var string $raiz
 * @var string $activa
 */

$raiz   = $raiz   ?? './';
$activa = $activa ?? '';

/* El menú en un array: añadir una entrada es una línea, y la versión de
   escritorio y la de móvil no pueden descuadrarse entre sí. */
$menu = [
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

/* ── Qué entradas se muestran ─────────────────────────────────────────────
   El ajuste `menu.visibles` de la intranet lleva las claves separadas por
   comas; vacío significa mostrarlas todas.

   La lista de arriba se queda como está a propósito: es el menú COMPLETO y
   sirve de reserva. Si la base no responde, el sitio se queda con toda su
   navegación en lugar de sin ninguna, que es lo que pasaría si las entradas
   vinieran únicamente de la consulta. */
if (isset($sitio) && $sitio instanceof \Intranet\Publico\Sitio) {
    // El try es lo que hace cierto el párrafo de arriba.
    //
    // Sin él, `ajuste()` lanzaba con la base caída y se llevaba por delante la
    // página ENTERA: se pintaba la cabeza del documento y ahí se cortaba todo,
    // sin cuerpo, sin formulario y sin mensaje. La reserva existía y no
    // entraba nunca, porque la excepción ocurría antes de poder usarla.
    try {
        $visibles = (string) $sitio->catalogo()->ajuste('menu.visibles', '');

        if (trim($visibles) !== '') {
            $permitidas = array_filter(array_map('trim', explode(',', $visibles)));
            $menu = array_intersect_key($menu, array_flip($permitidas));
        }
    } catch (\Throwable $e) {
        error_log('[cabecera] no se pudo leer menu.visibles: ' . $e->getMessage());
    }
}

$esc = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

/* ── El logotipo ───────────────────────────────────────────────────────────
   Aquí había el lirio del escudo pontificio. Se quitó: el escudo y sus
   elementos son símbolos heráldicos del Santo Padre y su uso no está
   aprobado. Hasta que lo esté, la marca es sólo su nombre.

   Si desde el panel se sube un logotipo, se pinta en su lugar. Y si no hay
   ninguno —que es el caso mientras nadie suba nada—, la cabecera se queda con
   el texto, que es correcto por sí solo y no depende de que nadie apruebe
   nada.

   Se comprueba el ARCHIVO, no un ajuste guardado: si alguien lo borra por FTP,
   la cabecera vuelve al texto sola en lugar de enseñar una imagen rota. */
$logotipo = null;
$rutaLogo = dirname(__DIR__, 2) . '/assets/img/marca';

foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
    if (is_file($rutaLogo . '/marca.' . $ext)) {
        $logotipo = 'assets/img/marca/marca.' . $ext;
        break;
    }
}

/* Un solo sitio donde se decide qué es la marca, para que la cabecera y el
   menú móvil no puedan acabar enseñando cosas distintas. */
$pintarMarca = static function () use ($logotipo, $esc, $raiz): void {
    if ($logotipo !== null) {
        echo '<img class="marca__logo" src="' . $esc($raiz . $logotipo) . '" alt="León XIV en el Perú" width="180" height="48">';
        return;
    }
    ?>
    <span class="marca__texto">
      <span class="marca__nombre">León XIV</span>
      <span class="marca__lugar">En el Perú</span>
    </span>
    <?php
};
?>
<header class="cabecera" data-cabecera>
  <div class="cabecera__fondo"></div>
  <div class="contenedor">
    <div class="cabecera__interior">
      <a class="marca" href="<?= $esc($raiz) ?>" aria-label="León XIV en el Perú, ir al inicio">
        <?php $pintarMarca(); ?>
      </a>
      <nav class="nav" aria-label="Navegación principal">
        <?php foreach ($menu as $clave => $rotulo): ?>
          <a class="nav__enlace" href="<?= $esc($raiz . $clave . '/') ?>"<?= $clave === $activa ? ' aria-current="page"' : '' ?>><?= $esc($rotulo) ?></a>
        <?php endforeach; ?>
      </nav>
      <div class="cabecera__acciones">
        <a class="btn btn--primario" href="<?= $esc($raiz) ?>voluntariado/">Sé voluntario</a>
        <button class="boton-menu" type="button" data-abrir-menu aria-expanded="false" aria-controls="menu-movil" aria-label="Abrir el menú">
          <svg aria-hidden="true"><use href="#i-menu"/></svg>
        </button>
      </div>
    </div>
  </div>
</header>

<div class="menu-movil" id="menu-movil">
  <div class="contenedor">
    <div class="menu-movil__cabecera">
      <span class="marca">
        <?php $pintarMarca(); ?>
      </span>
      <button class="boton-menu" type="button" data-cerrar-menu aria-label="Cerrar el menú"><svg aria-hidden="true"><use href="#i-cerrar"/></svg></button>
    </div>
    <nav class="menu-movil__lista" aria-label="Navegación principal, versión móvil">
      <?php foreach ($menu as $clave => $rotulo): ?>
        <a href="<?= $esc($raiz . $clave . '/') ?>"<?= $clave === $activa ? ' aria-current="page"' : '' ?>><?= $esc($rotulo) ?></a>
      <?php endforeach; ?>
    </nav>
    <a class="btn btn--primario btn--bloque" href="<?= $esc($raiz) ?>voluntariado/">Sé voluntario</a>
  </div>
</div>
