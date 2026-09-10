<?php
/**
 * Biblioteca de documentos: subir un PDF y copiar su ruta.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var \Intranet\Core\Csrf       $csrf
 * @var array  $documentos
 * @var string $carpeta
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

/* Se reutiliza el permiso de la biblioteca de imágenes en lugar de inventar
   uno nuevo: quien puede subir una fotografía puede subir un PDF, y un permiso
   más significa una fila más en `permisos`, otra en `rol_permiso` y una
   migración para repartirlo. */
$puedeSubir = $auth->puede('medios.subir');
?>

<h1><?= $e($titulo ?? 'Documentos') ?></h1>

<p class="ayuda">
  Los PDF que se enlazan desde las páginas: los subsidios, las guías y lo que
  venga. Sube el archivo aquí y luego <strong>pega su ruta</strong> en el campo
  «Archivo PDF» de la pieza que lo enlaza, en Páginas.
</p>

<?php if ($puedeSubir): ?>
  <form class="tarjeta" method="post" action="<?= $url('/documentos') ?>" enctype="multipart/form-data">
    <?= $csrf->campo() ?>
    <div class="campo">
      <label class="campo__etiqueta" for="documento">Documento</label>
      <input type="file" id="documento" name="documento" accept="application/pdf,.pdf" required>
      <p class="campo__ayuda">
        PDF, hasta 20 MB. El servidor comprueba que el contenido sea de verdad un
        PDF y le pone él mismo el nombre en disco.
      </p>
    </div>
    <button class="btn btn--primario" type="submit">Subir documento</button>
  </form>
<?php endif; ?>

<?php if ($documentos === []): ?>
  <p class="vacio">Todavía no hay ningún documento subido desde el panel.</p>
<?php else: ?>
  <table class="tabla">
    <caption class="solo-lectores">Documentos subidos, del más reciente al más antiguo</caption>
    <thead>
      <tr>
        <th scope="col">Archivo</th>
        <th scope="col">Ruta para pegar en la pieza</th>
        <th scope="col">Peso</th>
        <th scope="col">Subido</th>
        <?php if ($puedeSubir): ?><th scope="col">&nbsp;</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($documentos as $d): ?>
        <tr>
          <td>
            <a href="<?= $e($c->urlSitio('/' . $d['ruta'])) ?>" target="_blank" rel="noopener">
              <?= $e($d['nombre']) ?>
            </a>
          </td>
          <?php /* La ruta se escribe en un input de sólo lectura y no en un
                   <code>: así se selecciona entera de una pasada, que es lo que
                   hay que hacer con ella. */ ?>
          <td>
            <input type="text" readonly value="<?= $e($d['ruta']) ?>"
                   onclick="this.select()" aria-label="Ruta de <?= $e($d['nombre']) ?>">
          </td>
          <td><?= $e($d['peso']) ?></td>
          <td><?= $e($d['fecha']) ?></td>
          <?php if ($puedeSubir): ?>
            <td>
              <form method="post" action="<?= $url('/documentos/borrar') ?>"
                    onsubmit="return confirm('¿Borrar este documento? Si alguna pieza lo enlaza, su botón de descarga dejará de aparecer.');">
                <?= $csrf->campo() ?>
                <input type="hidden" name="nombre" value="<?= $e($d['nombre']) ?>">
                <button class="btn btn--linea" type="submit">Borrar</button>
              </form>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<p class="ayuda">
  Los subsidios que vinieron con el sitio viven en <code>assets/docs/subsidios/</code>
  y no aparecen en esta lista: son parte del código y se actualizan con un
  despliegue. Para sustituir uno sin esperar, sube aquí el nuevo y cambia la
  ruta en su pieza.
</p>
