<?php
/**
 * Un campo de lista cerrada.
 *
 * Lo usan la pantalla de una pieza y la de su sección, para las claves de
 * `datos` que la plantilla declara con tipo «opciones».
 *
 * ── Por qué nunca descarta lo que no conoce ──────────────────────────────
 *
 * Porque estas listas se estrechan con el tiempo. «Plantilla» ofrecía ocho
 * valores y tres de ellos no existían en la vista pública; al dejar sólo los
 * cinco de verdad, una lámina guardada con uno de los otros se habría quedado
 * sin su valor en el <select>, y el primer «Guardar» —aunque fuera para
 * corregir una coma— se lo habría cambiado en silencio.
 *
 * Así que si el valor guardado no está en la lista, se añade arriba, marcado,
 * y queda elegido. Abrir y guardar sin tocar el desplegable deja la pieza
 * exactamente igual que estaba.
 *
 * @var array  $defCampo    la definición de la plantilla
 * @var string $nombreCampo name del <select>
 * @var string $idCampo     id del <select>
 * @var string $valorCampo  lo que hay guardado
 */

use Intranet\Core\View;

$eOp = static fn ($v) => View::e($v);

$opcionesCampo = $defCampo['opciones'] ?? [];
$valorCampo    = (string) $valorCampo;
$esConocido    = array_key_exists($valorCampo, $opcionesCampo);

if (!$esConocido) {
    /* El «+» conserva las claves de la izquierda, así que lo guardado queda
       el primero y lo demás detrás, en su orden. */
    $opcionesCampo = [
        $valorCampo => 'Lo que tiene ahora: «' . $valorCampo . '» — ya no está en la lista',
    ] + $opcionesCampo;
}
?>
<?php
/* Si la plantilla dice qué campos usa cada opción, el mapa viaja en el propio
   <select> y panel.js esconde los demás. Sin JavaScript se ven todos, que es
   como estaba antes: esto es una ayuda, no un requisito. */
$mandaCampos = $defCampo['usa'] ?? null;
?>
<select id="<?= $eOp($idCampo) ?>" name="<?= $eOp($nombreCampo) ?>"
        <?php if (is_array($mandaCampos)): ?>data-manda-campos="<?= $eOp(json_encode($mandaCampos, JSON_UNESCAPED_UNICODE)) ?>"<?php endif; ?>>
  <?php foreach ($opcionesCampo as $clave => $comoSeLee): ?>
    <option value="<?= $eOp($clave) ?>"<?= (string) $clave === $valorCampo ? ' selected' : '' ?>>
      <?= $eOp($comoSeLee) ?>
    </option>
  <?php endforeach; ?>
</select>

<?php if (!$esConocido): ?>
  <p class="campo__ayuda campo__ayuda--aviso">
    Esta pieza tiene un valor que ya no se ofrece. Se respeta tal cual mientras
    no toques el desplegable; si eliges otro, el de ahora desaparece de la lista.
  </p>
<?php endif; ?>
