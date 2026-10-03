<?php
/**
 * La ventana para elegir una imagen de la biblioteca.
 *
 * UNA sola por pantalla, compartida por todos los campos de imagen. Antes
 * cada campo traía su propia copia de la rejilla, y con catorce campos y 96
 * fotos eso eran 1 344 miniaturas y casi un mega de HTML en Páginas → Inicio
 * → Itinerario.
 *
 * Las miniaturas son «lazy» y la ventana nace cerrada: el navegador no pide
 * ni una hasta que alguien la abre. Así la rejilla no cuesta nada mientras no
 * se use, que es casi siempre.
 *
 * El buscador filtra en el navegador, sobre lo que ya está pintado, y no
 * vuelve al servidor: con menos de unos cientos de fotos es instantáneo y
 * evita una petición por cada letra.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var array $medios  la biblioteca
 */

use Intranet\Core\View;

$eV = static fn ($v) => View::e($v);
?>
<dialog class="elegir-img" data-ventana-biblioteca aria-label="Elegir una imagen">
  <div class="elegir-img__caja">
    <header class="elegir-img__cab">
      <h2 class="elegir-img__titulo">Elegir una imagen</h2>

      <label class="visualmente-oculto" for="elegir-buscar">Buscar</label>
      <input type="search" id="elegir-buscar" class="elegir-img__buscar"
             placeholder="Buscar por nombre o descripción" data-elegir-buscar>

      <button class="elegir-img__cerrar" type="button" data-cerrar aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
      </button>
    </header>

    <?php if ($medios === []): ?>
      <p class="vacio">
        Todavía no hay imágenes.
        <a href="<?= $eV($c->url('/medios')) ?>" target="_blank" rel="noopener">Sube la primera ↗</a>
      </p>
    <?php else: ?>
      <ul class="elegir-img__rejilla" data-elegir-rejilla>
        <?php foreach ($medios as $m): ?>
          <li data-nombre="<?= $eV(mb_strtolower($m['nombre_archivo'] . ' ' . ($m['alt'] ?? ''))) ?>">
            <button type="button" class="elegir-img__pieza"
                    data-elegir="<?= (int) $m['id'] ?>"
                    data-src="<?= $eV($c->urlSitio('/' . ltrim((string) $m['ruta'], '/'))) ?>"
                    data-alt="<?= $eV($m['alt'] ?? '') ?>"
                    data-nombre="<?= $eV($m['nombre_archivo']) ?>"
                    title="<?= $eV($m['nombre_archivo']) ?>">
              <img src="<?= $eV($c->urlSitio('/' . ltrim((string) $m['ruta'], '/'))) ?>"
                   alt="" loading="lazy" decoding="async" width="120" height="120">
              <span class="elegir-img__pie"><?= $eV($m['nombre_archivo']) ?></span>
            </button>
          </li>
        <?php endforeach; ?>
      </ul>

      <p class="vacio" data-elegir-sinresultados hidden>Ninguna imagen con ese nombre.</p>
    <?php endif; ?>

    <footer class="elegir-img__pie-ventana">
      <button class="btn btn--linea btn--mini" type="button" data-elegir-ninguna>Sin imagen</button>
      <a class="btn btn--mini" href="<?= $eV($c->url('/medios')) ?>" target="_blank" rel="noopener">
        Ir a la biblioteca ↗
      </a>
    </footer>
  </div>
</dialog>
