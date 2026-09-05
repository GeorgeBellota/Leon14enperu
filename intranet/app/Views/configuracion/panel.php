<?php
/**
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Csrf       $csrf
 * @var array<string,string> $paginas
 * @var string $inicio
 * @var array  $visibles
 * @var bool   $todas
 * @var string $pie
 * @var string $inicioViaje
 * @var string $finViaje
 * @var string $fase
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

$estaVisible = static fn (string $clave): bool => $todas || in_array($clave, $visibles, true);
?>

<header class="encabezado">
  <p class="rotulo">Administración</p>
  <h1>Configuración general</h1>
  <p class="encabezado__pie">
    Lo que afecta a todo el sitio a la vez. Los cambios se ven en la web al guardar.
  </p>
</header>

<?php /* enctype multipart: sin él, el navegador manda los campos pero NO el
         archivo, y el logotipo se subiría en silencio a ninguna parte. */ ?>
<form method="post" action="<?= $url('/configuracion') ?>" enctype="multipart/form-data" data-avisar-cambios>
  <?= $csrf->campo() ?>

  <!-- ── Página de inicio ── -->
  <section class="tarjeta">
    <header class="tarjeta__cabecera">
      <h2>Página de inicio</h2>
      <a class="enlace-flecha" href="<?= $e($c->urlSitio('/')) ?>" target="_blank" rel="noopener">Ver la raíz ↗</a>
    </header>

    <p class="vacio">
      Qué se muestra cuando alguien escribe el dominio a secas. La dirección no cambia:
      sigue siendo el dominio, no una redirección a otra ruta.
    </p>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="pagina_inicio">Mostrar en la raíz</label>
      <select id="pagina_inicio" name="pagina_inicio">
        <option value="home"<?= $inicio === 'home' ? ' selected' : '' ?>>Portada completa (la de siempre)</option>
        <?php foreach ($paginas as $clave => $rotulo): ?>
          <option value="<?= $e($clave) ?>"<?= $inicio === $clave ? ' selected' : '' ?>><?= $e($rotulo) ?></option>
        <?php endforeach; ?>
      </select>
      <p class="campo__ayuda">
        Si eliges una interna, seguirá estando también en su propia dirección.
      </p>
    </div>
  </section>

  <!-- ── Menú ── -->
  <section class="tarjeta">
    <header class="tarjeta__cabecera"><h2>Entradas del menú</h2></header>

    <p class="vacio">
      Lo que se desmarca desaparece del menú, arriba y en el móvil. La página sigue
      existiendo y sigue siendo accesible por su dirección: esto sólo la quita de la
      navegación.
    </p>

    <div class="lista-casillas sep-m">
      <?php foreach ($paginas as $clave => $rotulo): ?>
        <label class="interruptor">
          <input type="checkbox" name="visibles[]" value="<?= $e($clave) ?>"<?= $estaVisible($clave) ? ' checked' : '' ?>>
          <span>
            <?= $e($rotulo) ?>
            <?php if ($inicio === $clave): ?>
              <span class="pildora pildora--validado">es la de inicio</span>
            <?php endif; ?>
          </span>
        </label>
      <?php endforeach; ?>
    </div>

    <p class="campo__ayuda sep-m">
      La página de inicio se mantiene en el menú aunque la desmarques: sin ella, quien
      entre a una interna se queda sin forma de volver.
    </p>
  </section>

  <!-- ── Fechas del viaje ── -->
  <section class="tarjeta">
    <header class="tarjeta__cabecera"><h2>Fechas del viaje</h2></header>

    <p class="vacio">
      De aquí sale la <strong>cuenta atrás de la portada</strong>. Cambia la fecha de
      inicio y el contador cambia al instante, sin tocar nada más y sin esperar a
      ningún despliegue.
    </p>

    <div class="rejilla-dos sep-m">
      <div class="campo">
        <label class="campo__etiqueta" for="viaje_inicio">Empieza el viaje</label>
        <input type="datetime-local" id="viaje_inicio" name="viaje_inicio"
               value="<?= $e($inicioViaje) ?>" required>
        <p class="campo__ayuda">Hasta este momento cuenta el temporizador.</p>
      </div>

      <div class="campo">
        <label class="campo__etiqueta" for="viaje_fin">Termina el viaje</label>
        <input type="datetime-local" id="viaje_fin" name="viaje_fin"
               value="<?= $e($finViaje) ?>" required>
        <p class="campo__ayuda">Marca el final de los días de visita.</p>
      </div>
    </div>

    <p class="campo__ayuda sep-m">
      Las dos van en <strong>hora de Lima</strong>. Quien mire la web desde España o
      desde Roma verá exactamente la misma cuenta que quien la mire desde aquí.
    </p>
  </section>

  <!-- ── Fase ── -->
  <section class="tarjeta">
    <header class="tarjeta__cabecera"><h2>Momento del sitio</h2></header>

    <p class="vacio">
      Varias secciones cambian según el momento: antes del viaje se ve la cuenta
      atrás, durante los días de visita se ven las crónicas, y después queda el
      recuerdo. Normalmente esto se resuelve solo con las fechas de arriba.
    </p>

    <div class="opciones sep-m">
      <?php
      $fases = [
          'auto' => ['Automático (recomendado)',
              'Lo decide el calendario a partir de las fechas de arriba. Es lo que debe estar puesto: nadie tiene que acordarse de entrar aquí el 11 de noviembre a medianoche.'],
          'pre'  => ['Antes del viaje',
              'Fuerza la cuenta atrás aunque la fecha ya haya pasado.'],
          'live' => ['Durante el viaje',
              'Fuerza el modo de cobertura en directo. Útil para revisar cómo quedará antes de que llegue el día.'],
          'post' => ['Después del viaje',
              'Fuerza el modo recuerdo.'],
      ];
      ?>
      <?php foreach ($fases as $valor => [$nombre, $explica]): ?>
        <label class="opcion">
          <input type="radio" name="fase" value="<?= $e($valor) ?>"<?= $fase === $valor ? ' checked' : '' ?>>
          <span>
            <strong><?= $e($nombre) ?></strong>
            <span class="opcion__nota"><?= $e($explica) ?></span>
          </span>
        </label>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ── Pie ── -->
  <section class="tarjeta">
    <header class="tarjeta__cabecera"><h2>Pie de página</h2></header>

    <div class="opciones">
      <?php
      $modos = [
          'completo' => ['Completo',
              'Las cuatro columnas de enlaces y el bloque de redes, en todas las páginas.'],
          'simple' => ['Sólo el copyright',
              'Sin columnas ni redes. Es lo indicado mientras la mayoría de las páginas estén a medias: un mapa del sitio con veinte enlaces es un mapa de páginas sin terminar.'],
          'simple_en_internas' => ['Completo en la portada, simple en el resto',
              'La portada conserva el mapa del sitio y las internas se quedan con el copyright.'],
      ];
      ?>
      <?php foreach ($modos as $valor => [$nombre, $explica]): ?>
        <label class="opcion">
          <input type="radio" name="pie_modo" value="<?= $e($valor) ?>"<?= $pie === $valor ? ' checked' : '' ?>>
          <span>
            <strong><?= $e($nombre) ?></strong>
            <span class="opcion__nota"><?= $e($explica) ?></span>
          </span>
        </label>
      <?php endforeach; ?>
    </div>

    <p class="campo__ayuda sep-m">
      El copyright se muestra siempre: es la única línea del pie que cumple una función
      legal y no de navegación.
    </p>
  </section>

  <?php /* ── Logotipo ────────────────────────────────────────────────────
           Mientras no haya ninguno, la cabecera de la web muestra sólo el
           nombre. Antes llevaba el lirio del escudo pontificio y se retiró:
           es un símbolo heráldico del Santo Padre y su uso no está aprobado.
           El texto solo es correcto y no depende de que nadie apruebe nada. */ ?>
  <section class="tarjeta sep-l">
    <h2 class="tarjeta__titulo">Logotipo</h2>
    <p class="campo__ayuda">
      Se muestra en la cabecera de todas las páginas. Si no subes ninguno, aparece
      el nombre escrito: <strong>León XIV · En el Perú</strong>.
    </p>

    <?php if ($logotipo !== null): ?>
      <div class="logotipo-actual sep-m">
        <img src="<?= $e($rutaSitio . $logotipo) ?>" alt="Logotipo actual" style="max-height:56px;width:auto">
        <label class="opcion sep-s">
          <input type="checkbox" name="logotipo_borrar" value="1">
          <span>
            <strong>Quitar el logotipo</strong>
            <span class="opcion__nota">La cabecera vuelve a mostrar el nombre escrito.</span>
          </span>
        </label>
      </div>
    <?php endif; ?>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="logotipo">
        <?= $logotipo !== null ? 'Sustituir por otra imagen' : 'Subir un logotipo' ?>
      </label>
      <input type="file" id="logotipo" name="logotipo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
      <p class="campo__ayuda">
        PNG, JPG, WEBP o SVG, hasta 3 MB. Se recomienda PNG con fondo transparente
        y unos 360 × 96 píxeles: la cabecera lo muestra a 48 de alto, y el doble de
        tamaño hace que se vea nítido en pantallas de alta resolución.
      </p>
    </div>
  </section>

  <div class="barra-guardar">
    <button class="btn btn--primario" type="submit">Guardar</button>
  </div>
</form>
