<?php
/**
 * Las piezas de una sección, en lista.
 *
 * Esto sustituye a la sábana: antes la sección pintaba el formulario entero
 * de cada una de sus piezas, una debajo de otra. Trece comisiones eran 161
 * campos en una pantalla, y corregir una coma en la segunda obligaba a bajar
 * por las once siguientes.
 *
 * Aquí sólo se ve lo que sirve para encontrar la que se busca —su foto, su
 * titular, si está visible— y para ponerlas en orden. Lo demás se edita
 * entrando.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Csrf       $csrf
 * @var array $pagina
 * @var array $seccion
 * @var array $def     la definición de bloques de la plantilla
 * @var array $medios  la biblioteca, para sacar la miniatura
 */

use Intranet\Core\View;

$eL   = static fn ($v) => View::e($v);
$urlL = static fn (string $r) => View::e($c->url($r));

$enSecL = '/paginas/' . $pagina['clave'] . '/' . $seccion['clave'];
$piezas = $seccion['bloques'];
$tope   = (int) ($def['maximo'] ?? 20);

/* La miniatura sale de la biblioteca, que ya viene cargada para los
   selectores de imagen: se indexa por id en vez de pedir una consulta por
   pieza. */
$porId = [];

foreach ($medios as $mL) {
    $porId[(int) $mL['id']] = $mL;
}
?>
<section class="tarjeta">
  <header class="tarjeta__cabecera">
    <h2><?= $eL($def['plural']) ?></h2>

    <?php if (count($piezas) < $tope): ?>
      <?php /* Va en su propio formulario, fuera del de la sección: crea una
               ficha en el servidor y lleva a su pantalla. Antes era un botón
               de JavaScript que clonaba un <template>. */ ?>
      <form method="post" action="<?= $urlL($enSecL . '/piezas') ?>">
        <?= $csrf->campo() ?>
        <button class="btn btn--linea" type="submit">
          Añadir <?= $eL(mb_strtolower($def['nombre'])) ?>
        </button>
      </form>
    <?php endif; ?>
  </header>

  <?php if ($piezas === []): ?>
    <p class="vacio">
      Todavía no hay <?= $eL(mb_strtolower($def['plural'])) ?>.
      Pulsa «Añadir» para crear la primera.
    </p>
  <?php else: ?>
    <p class="vacio">
      Se publican en este orden. Entra en una para editarla.
      <?php if (count($piezas) >= $tope): ?>
        <strong>Esta sección admite <?= $tope ?> como máximo.</strong>
      <?php endif; ?>
    </p>

    <ul class="piezas">
      <?php foreach ($piezas as $nL => $bL): ?>
        <?php
        $idL   = (int) $bL['id'];
        $fotoL = $porId[(int) ($bL['imagen_id'] ?? 0)] ?? null;
        $suyaL = $enSecL . '/piezas/' . $idL;
        ?>
        <?php /* El ancla con la que se vuelve aquí después de guardar o de
                 mover, para no aparecer arriba del todo buscando cuál era. */ ?>
        <li class="pieza-fila<?= $bL['activo'] ? '' : ' es-oculta' ?>" id="pieza-<?= $idL ?>">
          <span class="pieza-fila__num"><?= $nL + 1 ?></span>

          <span class="pieza-fila__foto">
            <?php if ($fotoL !== null): ?>
              <img src="<?= $eL($c->urlSitio('/' . ltrim((string) $fotoL['ruta'], '/'))) ?>"
                   alt="" loading="lazy" decoding="async" width="64" height="64">
            <?php else: ?>
              <span class="pieza-fila__sinfoto" aria-hidden="true">—</span>
            <?php endif; ?>
          </span>

          <span class="pieza-fila__texto">
            <a class="pieza-fila__titulo" href="<?= $urlL($suyaL) ?>">
              <?= $eL($bL['titulo'] ?: ($bL['rotulo'] ?: 'Ficha sin título')) ?>
            </a>

            <span class="pieza-fila__pie">
              <?php if (!$bL['activo']): ?><strong>Oculta</strong> · <?php endif; ?>
              <?php if (!empty($bL['slug'])): ?>
                <?= $eL(rtrim((string) ($pagina['ruta'] ?? '/'), '/') . '/' . $bL['slug'] . '/') ?>
              <?php else: ?>
                <?= $eL(mb_strimwidth(trim((string) ($bL['texto'] ?? '')), 0, 70, '…')) ?>
              <?php endif; ?>
            </span>
          </span>

          <?php /* Subir, bajar y entrar. Cada flecha es un formulario porque
                   cambia algo en el servidor: un enlace no debe hacerlo, y
                   así funciona también sin JavaScript. */ ?>
          <span class="pieza-fila__mandos">
            <?php if ($nL > 0): ?>
              <form method="post" action="<?= $urlL($suyaL . '/orden') ?>">
                <?= $csrf->campo() ?>
                <input type="hidden" name="hacia" value="subir">
                <button class="mando-mini" type="submit"
                        aria-label="Subir <?= $eL($bL['titulo'] ?: 'esta ficha') ?>">↑</button>
              </form>
            <?php endif; ?>

            <?php if ($nL < count($piezas) - 1): ?>
              <form method="post" action="<?= $urlL($suyaL . '/orden') ?>">
                <?= $csrf->campo() ?>
                <input type="hidden" name="hacia" value="bajar">
                <button class="mando-mini" type="submit"
                        aria-label="Bajar <?= $eL($bL['titulo'] ?: 'esta ficha') ?>">↓</button>
              </form>
            <?php endif; ?>

            <a class="btn btn--mini" href="<?= $urlL($suyaL) ?>">Editar</a>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
