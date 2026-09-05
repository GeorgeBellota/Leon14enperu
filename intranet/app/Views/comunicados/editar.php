<?php
/**
 * Crear o editar un comunicado.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Csrf       $csrf
 * @var array|null                $com      el comunicado, o null si es nuevo
 * @var array<string,string>      $paginas
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

$esNuevo = $com === null;
$v = static fn (string $campo, $porDefecto = '') => $com[$campo] ?? $porDefecto;

/* En qué modo está la selección de páginas: en todas, sólo en algunas, o en
   todas menos algunas. Se deduce de cómo está guardado, para que al reabrir el
   formulario aparezca lo que de verdad hay puesto. */
$lista = array_filter(array_map('trim', explode(',', (string) $v('paginas'))));
$modo  = 'todas';
$marcadas = [];

if ($lista !== []) {
    $modo = str_starts_with($lista[array_key_first($lista)], '!') ? 'menos' : 'solo';
    $marcadas = array_map(static fn (string $p): string => ltrim($p, '!'), $lista);
}

$expira = $v('expira_en') ? substr((string) $v('expira_en'), 0, 10) : '';
?>

<header class="encabezado">
  <p class="rotulo"><a href="<?= $url('/comunicados') ?>">Comunicados</a></p>
  <h1><?= $esNuevo ? 'Nuevo comunicado' : $e($v('nombre')) ?></h1>
</header>

<form method="post"
      action="<?= $url($esNuevo ? '/comunicados/nuevo' : '/comunicados/' . (int) $v('id')) ?>"
      enctype="multipart/form-data" data-avisar-cambios>
  <?= $csrf->campo() ?>

  <?php /* ── Lo que se ve en la web ─────────────────────────────────────── */ ?>
  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Contenido</h2>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="nombre">Nombre <span class="mascara">(sólo para esta lista)</span></label>
      <input type="text" id="nombre" name="nombre" required maxlength="120"
             value="<?= $e($v('nombre')) ?>" placeholder="Comunicado de la peregrinación">
      <p class="campo__ayuda">No se muestra en la web. Sirve para reconocerlo aquí y en el historial.</p>
    </div>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="imagen">Imagen</label>

      <?php if (!empty($v('imagen'))): ?>
        <img src="<?= $e($c->urlSitio('/' . ltrim((string) $v('imagen'), '/'))) ?>"
             alt="Imagen actual" style="max-width:280px;height:auto;border-radius:4px" class="sep-s">
      <?php endif; ?>

      <input type="file" id="imagen" name="imagen" accept="image/png,image/jpeg,image/webp,image/svg+xml">
      <p class="campo__ayuda">
        PNG, JPG o WEBP, hasta 20 MB. Se muestra arriba del todo, a lo ancho del aviso.
        Se recomienda apaisada, sobre 1000 × 600.
        <?= !empty($v('imagen')) ? 'Si no subes otra, se conserva la actual.' : '' ?>
      </p>
    </div>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="descripcion">Descripción</label>
      <textarea id="descripcion" name="descripcion" rows="4" maxlength="2000"><?= $e($v('descripcion')) ?></textarea>
      <p class="campo__ayuda">Va debajo de la imagen. Dos o tres líneas: lo que no se lee de un vistazo, no se lee.</p>
    </div>
  </section>

  <?php /* ── El botón ────────────────────────────────────────────────────── */ ?>
  <section class="tarjeta">
    <h2 class="tarjeta__titulo">El botón</h2>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="boton_texto">Texto del botón</label>
      <input type="text" id="boton_texto" name="boton_texto" maxlength="80"
             value="<?= $e($v('boton_texto', 'Ver más')) ?>" placeholder="Descargar el programa">
    </div>

    <div class="campo sep-m">
      <span class="campo__etiqueta">¿Qué hace al pulsarlo?</span>

      <label class="opcion">
        <input type="radio" name="boton_tipo" value="enlace" data-tipo-boton
               <?= $v('boton_tipo', 'enlace') !== 'descarga' ? 'checked' : '' ?>>
        <span>
          <strong>Abrir una página</strong>
          <span class="opcion__nota">Se abre en una pestaña nueva, para no sacar a nadie del sitio.</span>
        </span>
      </label>

      <label class="opcion">
        <input type="radio" name="boton_tipo" value="descarga" data-tipo-boton
               <?= $v('boton_tipo') === 'descarga' ? 'checked' : '' ?>>
        <span>
          <strong>Descargar un archivo</strong>
          <span class="opcion__nota">PDF, MP3, MP4, DOCX o XLSX. Se descarga con su nombre, no con uno inventado.</span>
        </span>
      </label>
    </div>

    <?php /* Los dos destinos posibles. El JavaScript enseña el que toca; sin
             JavaScript se ven los dos, con su rótulo, y sigue funcionando. */ ?>
    <div class="campo sep-m" data-si-tipo="enlace">
      <label class="campo__etiqueta" for="boton_destino">Dirección de la página</label>
      <input type="text" id="boton_destino" name="boton_destino" maxlength="500"
             value="<?= $v('boton_tipo') === 'descarga' ? '' : $e($v('boton_destino')) ?>"
             placeholder="https://…  o  voluntariado/">
      <p class="campo__ayuda">Una dirección completa con https://, o una página de este sitio como <code>voluntariado/</code>.</p>
    </div>

    <div class="campo sep-m" data-si-tipo="descarga">
      <label class="campo__etiqueta" for="archivo">Archivo</label>

      <?php if ($v('boton_tipo') === 'descarga' && !empty($v('archivo_nombre'))): ?>
        <p class="campo__ayuda sep-s">
          Ahora mismo: <strong><?= $e($v('archivo_nombre')) ?></strong>.
          Si no subes otro, se conserva.
        </p>
      <?php endif; ?>

      <input type="file" id="archivo" name="archivo" accept=".pdf,.mp3,.mp4,.docx,.xlsx">
      <p class="campo__ayuda">Hasta 20 MB. Cada descarga se cuenta y se ve en esta lista y en el escritorio.</p>
    </div>
  </section>

  <?php /* ── Dónde y cuándo ──────────────────────────────────────────────── */ ?>
  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Dónde y cuándo aparece</h2>

    <div class="campo sep-m">
      <span class="campo__etiqueta">Páginas</span>

      <label class="opcion">
        <input type="radio" name="paginas_modo" value="todas" <?= $modo === 'todas' ? 'checked' : '' ?>>
        <span><strong>En todas</strong></span>
      </label>
      <label class="opcion">
        <input type="radio" name="paginas_modo" value="solo" <?= $modo === 'solo' ? 'checked' : '' ?>>
        <span><strong>Sólo en las marcadas</strong></span>
      </label>
      <label class="opcion">
        <input type="radio" name="paginas_modo" value="menos" <?= $modo === 'menos' ? 'checked' : '' ?>>
        <span>
          <strong>En todas menos en las marcadas</strong>
          <span class="opcion__nota">Útil para no enseñar un aviso de inscripción encima del propio formulario.</span>
        </span>
      </label>

      <div class="casillas-rejilla sep-s">
        <?php foreach ($paginas as $clave => $rotulo): ?>
          <label class="opcion">
            <input type="checkbox" name="paginas[]" value="<?= $e($clave) ?>"
                   <?= in_array($clave, $marcadas, true) ? 'checked' : '' ?>>
            <span><?= $e($rotulo) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="expira_en">Caduca el</label>
      <input type="text" id="expira_en" name="expira_en" data-fecha
             value="<?= $e($expira) ?>" placeholder="dd/mm/aaaa">
      <p class="campo__ayuda">
        Deja vacío para que no caduque. Ese día se muestra entero: desaparece al día siguiente.
      </p>
    </div>

    <div class="paso__rejilla paso__rejilla--tres sep-m">
      <div class="campo">
        <label class="campo__etiqueta campo__etiqueta--menor" for="veces_max">Veces por visitante</label>
        <input type="number" id="veces_max" name="veces_max" min="0" max="50"
               value="<?= (int) $v('veces_max', 3) ?>">
        <p class="campo__ayuda">0 = sin límite.</p>
      </div>
      <div class="campo">
        <label class="campo__etiqueta campo__etiqueta--menor" for="retraso_ms">Tarda en salir</label>
        <input type="number" id="retraso_ms" name="retraso_ms" min="0" max="60000" step="500"
               value="<?= (int) $v('retraso_ms', 3000) ?>">
        <p class="campo__ayuda">Milisegundos. 3000 = 3 s.</p>
      </div>
      <div class="campo">
        <label class="campo__etiqueta campo__etiqueta--menor" for="autocierre_ms">Se cierra solo</label>
        <input type="number" id="autocierre_ms" name="autocierre_ms" min="0" max="120000" step="1000"
               value="<?= (int) $v('autocierre_ms', 0) ?>">
        <p class="campo__ayuda">0 = no se cierra solo.</p>
      </div>
    </div>

    <label class="opcion sep-m">
      <input type="checkbox" name="activo" value="1" <?= (int) $v('activo', 0) === 1 ? 'checked' : '' ?>>
      <span>
        <strong>Publicado</strong>
        <span class="opcion__nota">Al guardar marcado, empieza a verse en la web de inmediato.</span>
      </span>
    </label>
  </section>

  <div class="barra-guardar">
    <button class="btn btn--primario" type="submit">Guardar</button>
    <a class="btn btn--linea" href="<?= $url('/comunicados') ?>">Cancelar</a>
  </div>
</form>
