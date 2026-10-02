<?php
/**
 * El portón: la contraseña del tablero de métricas.
 *
 * Entrar al panel no basta. Esta pantalla pide otra contraseña, que no está
 * en la base de datos: sólo su hash, en la configuración del servidor.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Csrf       $csrf
 * @var int $bloqueo  minutos que faltan para poder volver a intentarlo
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));
?>

<header class="encabezado">
  <h1>Métricas</h1>
  <p class="encabezado__pie">
    Esta parte va aparte: además de tu acceso al panel, pide una contraseña
    propia.
  </p>
</header>

<section class="tarjeta porton">
  <?php /* El icono de servidores, el mismo que lleva al portón desde la barra
           lateral: así se ve que se ha llegado a donde se quería. */ ?>
  <svg class="porton__icono" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    <rect x="3" y="4"  width="18" height="6" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.6"/>
    <rect x="3" y="14" width="18" height="6" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.6"/>
    <circle cx="7" cy="7"  r="1" fill="currentColor"/>
    <circle cx="7" cy="17" r="1" fill="currentColor"/>
    <path d="M11 7h6M11 17h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
  </svg>

  <?php if ($bloqueo > 0): ?>
    <p class="vacio">
      Demasiados intentos fallidos. Vuelve a probar en <?= (int) $bloqueo ?> minutos.
    </p>
  <?php else: ?>
    <form method="post" action="<?= $url('/metricas') ?>" class="porton__form">
      <?= $csrf->campo() ?>

      <div class="campo">
        <label class="campo__etiqueta" for="clave">Contraseña del tablero</label>
        <?php /* `autocomplete="off"` y `name` neutro: no es la contraseña de
                 nadie, y no interesa que los navegadores la ofrezcan guardar
                 junto a las de verdad. */ ?>
        <input type="password" id="clave" name="clave" required autofocus
               autocomplete="off" spellcheck="false">
      </div>

      <button class="btn btn--primario" type="submit">Abrir</button>
    </form>
  <?php endif; ?>

  <p class="campo__ayuda">
    Se cierra sola al rato. No la escribas en ningún archivo del proyecto:
    aquí sólo vive su huella, en la configuración del servidor.
  </p>
</section>
