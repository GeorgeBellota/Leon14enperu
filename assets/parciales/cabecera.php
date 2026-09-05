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

/* Con muchas entradas visibles la navegación de escritorio ya va justa a
   1100 px. La cabecera se marca para que el CSS retire la cuenta atrás en esa
   franja en lugar de empujar los enlaces fuera de la pantalla. El umbral son
   seis entradas: con las ocho que hubo en su día la barra ya desbordaba. */
$menuLargo = count($menu) > 6;

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

/* ── La cuenta atrás, en la cabecera ──────────────────────────────────────
   Estaba sólo en la portada, en una tira bajo el carrusel. Ahí la veía quien
   entraba por la home y nadie más: quien llega a «Voluntariado» desde un
   enlace compartido —que es por donde entra la mayoría— no sabía cuántos días
   faltan. En la cabecera está en las veinticuatro páginas y no se va con el
   scroll, porque la cabecera es fija.

   La fecha sale del panel (Configuración general → Fechas del viaje), igual
   que antes. Si $sitio no está disponible o la base no responde, el atributo
   no se escribe y countdown.js usa su fecha de reserva: el 11 de noviembre de
   2026, la anunciada por la Santa Sede. Un contador con la fecha oficial es
   mejor que un hueco vacío.

   Las tres fases —antes, durante y después del viaje— se resuelven con las
   clases fase-pre / fase-live / fase-post, que enciende y apaga el CSS a
   partir del data-phase del <body>. El 11 de noviembre el contador se retira
   solo y deja el saludo, sin que nadie tenga que desplegar nada. */
$objetivo = '';

if (isset($sitio) && $sitio instanceof \Intranet\Publico\Sitio) {
    try {
        $objetivo = $sitio->objetivoCuentaAtras();
    } catch (\Throwable $e) {
        error_log('[cabecera] no se pudo leer la fecha del viaje: ' . $e->getMessage());
    }
}

/* ── Cómo se escribe el contador ──────────────────────────────────────────
   La solicitud de la Conferencia Episcopal lo pide así, literal:

       Faltan
       XX DÍAS · XX HORAS · XX MINUTOS · XX SEGUNDOS
       para recibir al Papa León XIV en el Perú

   Esa forma entera son tres renglones, y en una barra fija de 64 px de alto no
   caben: al lado del logotipo y del botón de menú quedan unos 220 px. Así que
   la barra lleva la forma corta —66 d, 15 h— y el panel del menú, que ocupa la
   pantalla completa, lleva la redacción del documento tal cual.

   No es una versión aguada: la frase de cierre y las palabras enteras están en
   el sitio donde se pueden leer, y quien usa lector de pantalla oye siempre la
   forma larga, porque countdown.js la mantiene en [data-contador-lectura]. */
$pintarCuenta = static function (string $variante = "cabecera") use ($esc, $objetivo): void {
    $unidades = [
        'dias'     => ['corta' => 'd', 'larga' => 'días'],
        'horas'    => ['corta' => 'h', 'larga' => 'horas'],
        'minutos'  => ['corta' => 'm', 'larga' => 'minutos'],
        'segundos' => ['corta' => 's', 'larga' => 'segundos'],
    ];
    $larga = $variante === 'menu';
    ?>
    <div class="cuenta-cab cuenta-cab--<?= $esc($variante) ?>" data-contador<?= $objetivo !== '' ? ' data-objetivo="' . $esc($objetivo) . '"' : '' ?>>
      <p class="cuenta-cab__reloj fase-pre">
        <span class="cuenta-cab__rotulo">Faltan</span>
        <?php foreach ($unidades as $unidad => $palabra): ?>
          <span class="cuenta-cab__par cuenta-cab__par--<?= $esc($unidad) ?>">
            <span class="cuenta-cab__num" data-unidad="<?= $esc($unidad) ?>">—</span><span class="cuenta-cab__uni"><?= $esc($larga ? $palabra['larga'] : $palabra['corta']) ?></span>
          </span>
        <?php endforeach; ?>
      </p>
      <?php if ($larga): ?>
        <p class="cuenta-cab__cierre fase-pre">para recibir al Papa León XIV en el Perú</p>
      <?php endif; ?>
      <p class="cuenta-cab__aviso fase-live">El Santo Padre está en el Perú</p>
      <p class="cuenta-cab__aviso fase-post">Gracias, Santo Padre</p>
      <?php /* El texto para lector de pantalla lo lleva SÓLO la copia de la
               barra: las dos se actualizan igual y anunciarlo dos veces
               convierte una ayuda en un estorbo. */ ?>
      <?php if ($variante === "cabecera"): ?><span class="solo-lectores" data-contador-lectura></span><?php endif; ?>
    </div>
    <?php
};
?>
<header class="cabecera<?= $menuLargo ? " cabecera--menu-largo" : "" ?>" data-cabecera>
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
        <?php $pintarCuenta(); ?>
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
    <?php $pintarCuenta("menu"); ?>
    <nav class="menu-movil__lista" aria-label="Navegación principal, versión móvil">
      <?php foreach ($menu as $clave => $rotulo): ?>
        <a href="<?= $esc($raiz . $clave . '/') ?>"<?= $clave === $activa ? ' aria-current="page"' : '' ?>><?= $esc($rotulo) ?></a>
      <?php endforeach; ?>
    </nav>
    <a class="btn btn--primario btn--bloque" href="<?= $esc($raiz) ?>voluntariado/">Sé voluntario</a>
  </div>
</div>
