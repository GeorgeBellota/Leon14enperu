<?php
/**
 * SEO y redes — la imagen y el texto que salen al compartir cada página.
 *
 * Cada fila enseña la tarjeta que sale DE VERDAD al pegar el enlace, no la
 * que se eligió: si una página no tiene imagen propia, aquí se ve la general
 * o la de por defecto, que es lo que verá quien la comparta.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var \Intranet\Core\Csrf       $csrf
 * @var array $paginas    con `tarjeta`, `url` y `fresco` ya resueltos
 * @var array $noticias   ídem, las publicadas
 * @var int   $generalId
 * @var array $general    la tarjeta general (o la de por defecto)
 * @var int   $version
 * @var array $medios
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

$puedeEditar = $auth->puede('paginas.editar');

$origenes = [
    'propia'  => ['Propia', 'pildora--acreditado'],
    'pagina'  => ['La de la página Noticias', 'pildora--nuevo'],
    'general' => ['General', 'pildora--nuevo'],
    'defecto' => ['Por defecto', 'pildora--baja'],
];

$depurador = static fn (string $u): string
    => 'https://developers.facebook.com/tools/debug/?q=' . rawurlencode($u);

/* La celda de la tarjeta y la del enlace fresco se repiten en las dos tablas. */
$miniatura = static function (array $fila) use ($e): void { ?>
    <a href="<?= $e($fila['tarjeta']['url']) ?>" target="_blank" rel="noopener" class="seo-miniatura"
       title="Abrir la imagen que se comparte">
      <img src="<?= $e($fila['tarjeta']['url']) ?>" alt="" loading="lazy" decoding="async"
           width="<?= (int) $fila['tarjeta']['ancho'] ?>" height="<?= (int) $fila['tarjeta']['alto'] ?>">
    </a>
<?php };

$fresco = static function (array $fila, string $id) use ($e, $depurador): void { ?>
    <div class="enlace-fresco">
      <label class="solo-lectores" for="<?= $e($id) ?>">Enlace para compartir</label>
      <input type="text" id="<?= $e($id) ?>" class="enlace-fresco__campo tabla__mono" readonly
             value="<?= $e($fila['fresco']) ?>" data-marcar-todo>
      <button type="button" class="btn btn--plano" data-copiar="#<?= $e($id) ?>">Copiar</button>
    </div>
    <a class="enlace-flecha" href="<?= $e($depurador($fila['url'])) ?>" target="_blank" rel="noopener">Revisar en Facebook ↗</a>
<?php };
?>

<header class="encabezado">
  <p class="rotulo">Contenidos</p>
  <h1>SEO y redes</h1>
  <p class="encabezado__pie">
    La imagen, el título y la descripción que salen al pegar un enlace en
    WhatsApp, Facebook, X o LinkedIn. Cada página puede tener los suyos; si no,
    usa la imagen general.
  </p>
</header>

<?php /* ══ CÓMO SE VE EL CAMBIO ═══════════════════════════════════════════ */ ?>
<section class="tarjeta">
  <header class="tarjeta__cabecera"><h2>Para que el cambio se vea ya</h2></header>
  <p>
    El cambio sale en la web al instante, pero <strong>WhatsApp puede tardar horas o
    incluso días</strong> en enseñarlo en el enlace de siempre: guarda su propia copia de
    la vista previa y la renueva cuando él decide. No depende de esta web.
  </p>
  <ul class="seo-pasos">
    <li><strong>WhatsApp:</strong> usa el <strong>enlace para compartir</strong> de la lista; sale ya con la imagen nueva.</li>
    <li><strong>Facebook e Instagram:</strong> pulsa <strong>Revisar en Facebook</strong> y luego «Volver a extraer».</li>
  </ul>
  <p class="vacio">
    Medida recomendada: <strong>1200 × 630 px</strong> (JPG o PNG), con el texto lejos de los bordes.
  </p>
</section>

<?php /* ══ IMAGEN GENERAL ═════════════════════════════════════════════════ */ ?>
<section class="tarjeta">
  <header class="tarjeta__cabecera"><h2>Imagen general</h2></header>

  <div class="seo-general">
    <figure class="seo-general__vista">
      <img src="<?= $e($general['url']) ?>" alt="La imagen general que se comparte"
           width="<?= (int) $general['ancho'] ?>" height="<?= (int) $general['alto'] ?>" loading="lazy">
      <figcaption class="vacio">
        <?= $general['origen'] === 'general'
            ? 'La que has elegido.'
            : 'La de por defecto: el pantallazo del banner de la portada.' ?>
      </figcaption>
    </figure>

    <?php if ($puedeEditar): ?>
      <form method="post" action="<?= $url('/seo/general') ?>" class="formulario-cms seo-general__formulario">
        <?= $csrf->campo() ?>
        <div class="campo">
          <label class="campo__etiqueta" for="seo-general">Cambiar la imagen general</label>
          <?php
            $nombre  = 'og_imagen_id';
            $idCampo = 'seo-general';
            $elegida = $generalId > 0 ? $generalId : null;
            $vacio   = 'El banner de la portada (por defecto)';
            require __DIR__ . '/../_comunes/_selector-imagen.php';
          ?>
          <p class="campo__ayuda">
            Sale en todas las páginas que no tengan imagen propia. Déjala vacía para
            volver al pantallazo del banner de la portada.
          </p>
        </div>
        <button class="btn btn--primario" type="submit">Guardar la imagen general</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php if ($puedeEditar): ?>
  <section class="tarjeta">
    <header class="tarjeta__cabecera"><h2>Forzar la actualización</h2></header>
    <p>
      Cambia a la vez la dirección de todas las imágenes para compartir. No borra
      nada ni cambia ninguna imagen: obliga a las redes a descargarlas de nuevo la
      próxima vez que lean una página, y renueva todos los enlaces para compartir.
    </p>
    <form method="post" action="<?= $url('/seo/refrescar') ?>" class="en-linea"
          data-confirmar="¿Forzar la actualización? Los «enlaces para compartir» de la lista cambiarán.">
      <?= $csrf->campo() ?>
      <button class="btn btn--linea" type="submit">Forzar la actualización</button>
      <span class="vacio">Versión actual: <?= (int) $version ?></span>
    </form>
  </section>
<?php endif; ?>

<?php /* ══ PÁGINAS ════════════════════════════════════════════════════════ */ ?>
<section class="tarjeta">
  <header class="tarjeta__cabecera"><h2>Páginas</h2></header>

  <div class="tabla-envoltorio">
    <table class="tabla tabla--seo">
      <thead>
        <tr>
          <th scope="col">Se comparte así</th>
          <th scope="col">Página</th>
          <th scope="col">Enlace para compartir</th>
          <th scope="col"><span class="solo-lectores">Acciones</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($paginas as $p): ?>
          <?php
          // En una página, la elegida para ella ES la propia.
          $origen = $p['tarjeta']['origen'] === 'pagina' ? 'propia' : $p['tarjeta']['origen'];
          [$texto, $clase] = $origenes[$origen] ?? $origenes['defecto'];
          ?>
          <tr>
            <td><?php $miniatura($p); ?></td>
            <td>
              <strong><?= $e($p['nombre']) ?></strong>
              <?php if ((int) $p['activa'] !== 1): ?><span class="pildora pildora--baja">Oculta</span><?php endif; ?>
              <br><span class="tabla__mono"><?= $e($p['ruta']) ?></span>
              <br><span class="pildora <?= $e($clase) ?>">Imagen: <?= $e($texto) ?></span>
              <?php if (trim((string) ($p['titulo_seo'] ?? '')) !== ''): ?>
                <p class="seo-titulo"><?= $e(View::recortar((string) $p['titulo_seo'], 80)) ?></p>
              <?php else: ?>
                <p class="seo-titulo vacio">Título: el que trae la página</p>
              <?php endif; ?>
            </td>
            <td><?php $fresco($p, 'fresco-' . $p['clave']); ?></td>
            <td>
              <?php if ($puedeEditar): ?>
                <a class="btn btn--linea" href="<?= $url('/paginas/' . $p['clave'] . '/seo') ?>">Cambiar</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php /* ══ NOTICIAS ═══════════════════════════════════════════════════════ */ ?>
<section class="tarjeta">
  <header class="tarjeta__cabecera"><h2>Noticias</h2></header>
  <p class="vacio">
    Cada noticia sale con su «Imagen al compartir»; si no tiene, con su portada,
    y si tampoco, con la de la página Noticias o la general. Se cambia dentro de
    la noticia, en el apartado de SEO.
  </p>

  <?php if ($noticias === []): ?>
    <p class="vacio">Todavía no hay noticias publicadas.</p>
  <?php else: ?>
    <div class="tabla-envoltorio">
      <table class="tabla tabla--seo">
        <thead>
          <tr>
            <th scope="col">Se comparte así</th>
            <th scope="col">Noticia</th>
            <th scope="col">Enlace para compartir</th>
            <th scope="col"><span class="solo-lectores">Acciones</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($noticias as $n): ?>
            <?php [$texto, $clase] = $origenes[$n['tarjeta']['origen']] ?? $origenes['defecto']; ?>
            <tr>
              <td><?php $miniatura($n); ?></td>
              <td>
                <strong><?= $e(View::recortar((string) $n['titulo'], 90)) ?></strong>
                <br><span class="pildora <?= $e($clase) ?>">Imagen: <?= $e($texto) ?></span>
              </td>
              <td><?php $fresco($n, 'fresco-noticia-' . (int) $n['id']); ?></td>
              <td>
                <?php if ($puedeEditar): ?>
                  <a class="btn btn--linea" href="<?= $url('/noticias/' . (int) $n['id']) ?>">Cambiar</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php if ($puedeEditar): ?>
  <?php require __DIR__ . '/../_comunes/_biblioteca-ventana.php'; ?>
<?php endif; ?>
