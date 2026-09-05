<?php
/**
 * ============================================================================
 *  La Colecta Nacional: la llamada y las cuentas oficiales.
 * ============================================================================
 *
 *  Se pinta en dos sitios —la portada y /donativo/— y por eso vive aquí y no
 *  dentro de una vista.
 *
 *  ── Por qué un parcial y no el bloque copiado dos veces ────────────────────
 *
 *  Porque lo que se pinta son NÚMEROS DE CUENTA BANCARIA. Con el marcado
 *  duplicado, el día que alguien corrija un dígito en una página y se olvide de
 *  la otra, el sitio publica dos cuentas distintas para lo mismo y el dinero de
 *  alguien acaba donde no debe. Con un solo parcial leyendo una sola sección de
 *  la base, eso no puede pasar.
 *
 *  La sección vive en la portada («home» → «colecta») y /donativo/ la lee de
 *  ahí. No hay una segunda copia en la base.
 *
 *  ── Lo que espera ──────────────────────────────────────────────────────────
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var array $colectaSecciones    las secciones de la página que trae la
 *                                  clave «colecta», tal como las devuelve
 *                                  Sitio::contenido()
 *  @var bool  $colectaSoloCuentas  true para pintar sólo la lista de cuentas,
 *                                  sin rótulo, titular ni texto. Es lo que usa
 *                                  /donativo/, donde la sección de encima ya ha
 *                                  presentado la colecta y repetirlo sería
 *                                  decir dos veces lo mismo con dos titulares.
 *  @var bool  $colectaConAncla     true para poner id="colecta" en la sección.
 *                                  Sólo puede haber uno por documento.
 *  @var string $colectaTitulo      sólo en modo «solo cuentas»: el encabezado
 *                                  que va encima de la lista. Sin él las
 *                                  cuentas quedan flotando entre dos titulares
 *                                  de sección sin nada que las nombre.
 */

declare(strict_types=1);

use Intranet\Core\HtmlSeguro;
use Intranet\Publico\Sitio;

$colectaSecciones   = $colectaSecciones   ?? [];
$colectaSoloCuentas = $colectaSoloCuentas ?? false;
$colectaConAncla    = $colectaConAncla    ?? true;
$colectaTitulo      = $colectaTitulo      ?? '';

/* Si la sección no está o está apagada en el panel, aquí no se pinta nada. No
   hay texto de reserva a propósito: unas cuentas bancarias escritas a mano en
   el código serían imposibles de corregir sin un despliegue. */
if (!Sitio::activa($colectaSecciones, 'colecta')) {
    return;
}

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$colectaCampo = static fn (string $c, string $r = ''): string
    => Sitio::campo($colectaSecciones, 'colecta', $c, $r);

$colectaCuentas = Sitio::bloques($colectaSecciones, 'colecta');

/* El destino del botón: relativo se resuelve contra la raíz del sitio; lo que
   ya es una dirección completa o un ancla se deja intacto. */
$colectaDestino = static function (mixed $u) use ($sitio): string {
    $u = trim((string) $u);
    if ($u === '') { return ''; }

    return preg_match('~^(https?:)?//|^(mailto|tel):|^#~i', $u) === 1
        ? $u
        : $sitio->enlace(ltrim($u, '/'));
};

$colectaNota = (string) (Sitio::dato($colectaSecciones, 'colecta', 'nota', '') ?? '');

/* El encabezado del modo «solo cuentas» también se edita desde el panel. Lo que
   llega por $colectaTitulo es el respaldo, no la última palabra. */
$colectaTituloPanel = (string) (Sitio::dato($colectaSecciones, 'colecta', 'titulo_cuentas', '') ?? '');
if (trim($colectaTituloPanel) !== '') { $colectaTitulo = $colectaTituloPanel; }
?>
<?php
/* El nombre accesible de la sección: el encabezado si lo lleva, y si no una
   etiqueta escrita, para que no quede una región sin nombre en el índice de un
   lector de pantalla. */
$colectaConTitulo = $colectaSoloCuentas && trim($colectaTitulo) !== '';
?>
<section class="seccion colecta seccion--tinte"<?= $colectaConAncla ? ' id="colecta"' : '' ?><?= !$colectaSoloCuentas || $colectaConTitulo ? ' aria-labelledby="t-colecta"' : ' aria-label="Cuentas de la Colecta Nacional"' ?>>
  <div class="contenedor">
    <div class="colecta__interior<?= $colectaSoloCuentas ? ' colecta__interior--cuentas' : '' ?>">

      <?php if ($colectaConTitulo): ?>
        <header class="seccion__encabezado colecta__encabezado">
          <span class="rotulo"><?= $e($colectaCampo('rotulo', 'Colecta Nacional')) ?></span>
          <h2 class="titular--mayor" id="t-colecta"><?= $e($colectaTitulo) ?></h2>
          <?php if ($colectaCampo('subtitulo') !== ''): ?>
            <p class="colecta__sub"><?= $e($colectaCampo('subtitulo')) ?></p>
          <?php endif; ?>
        </header>
      <?php endif; ?>

      <?php if (!$colectaSoloCuentas): ?>
        <div class="colecta__dicho" data-reveal="fade-rise">
          <span class="rotulo"><?= $e($colectaCampo('rotulo', 'Colecta Nacional')) ?></span>
          <h2 class="colecta__titulo" id="t-colecta">
            <?= $e($colectaCampo('titulo', 'Súmate con tu donación')) ?>
          </h2>
          <?php if ($colectaCampo('subtitulo') !== ''): ?>
            <p class="colecta__sub"><?= $e($colectaCampo('subtitulo')) ?></p>
          <?php endif; ?>
          <div class="colecta__texto">
            <?= HtmlSeguro::limpiar($colectaCampo('texto',
                '<p>Con tu aporte ayudamos a preparar este gran encuentro de fe, unidad y esperanza.</p>')) ?>
          </div>
          <?php if ($colectaCampo('cta_texto') !== ''): ?>
            <p class="colecta__pie">
              <a class="btn btn--primario" href="<?= $e($colectaDestino($colectaCampo('cta_url', 'donativo/'))) ?>">
                <?= $e($colectaCampo('cta_texto')) ?>
              </a>
            </p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($colectaCuentas !== []): ?>
        <?php /* Una lista de descripción y no una tabla: cada cuenta es un
                 rótulo con sus dos datos, no una matriz de filas y columnas.
                 Los dígitos van en un <span> con cifras tabulares, para que se
                 lean de un vistazo y sin que la fuente los junte. */ ?>
        <ul class="colecta__cuentas" data-reveal="fade-rise" data-reveal-delay="0.08">
          <?php foreach ($colectaCuentas as $c): ?>
            <?php
            $numero = trim((string) ($c['datos']['numero'] ?? ''));
            $cci    = trim((string) ($c['datos']['cci'] ?? ''));
            ?>
            <li class="cuenta">
              <p class="cuenta__banco"><?= $e($c['rotulo'] ?? '') ?></p>
              <?php if (($c['titulo'] ?? '') !== ''): ?>
                <p class="cuenta__titular"><?= $e($c['titulo']) ?></p>
              <?php endif; ?>
              <dl class="cuenta__datos">
                <?php if ($numero !== ''): ?>
                  <div class="cuenta__linea">
                    <dt>Cuenta</dt>
                    <dd><span class="cifras" data-copiar="<?= $e($numero) ?>"><?= $e($numero) ?></span></dd>
                  </div>
                <?php endif; ?>
                <?php if ($cci !== ''): ?>
                  <div class="cuenta__linea">
                    <dt>CCI</dt>
                    <dd><span class="cifras" data-copiar="<?= $e($cci) ?>"><?= $e($cci) ?></span></dd>
                  </div>
                <?php endif; ?>
              </dl>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if (trim($colectaNota) !== ''): ?>
        <p class="colecta__nota"><?= $e($colectaNota) ?></p>
      <?php endif; ?>

    </div>
  </div>
</section>
