<?php
/**
 * Vista de la página «privacidad».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Política de privacidad · leon14enperu.com',
    'descripcion' => 'Qué datos personales recoge este sitio, para qué, durante cuánto tiempo y cómo ejercer tus derechos.',
    'ruta'        => 'privacidad/',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'article',
    'body_attr'   => 'data-phase="pre"',
];
?>
<?php
/* El contenido de esta página sale de la base y se edita desde el panel.
   Cada lectura lleva su texto de reserva: si la base no responde, o si
   alguien vacía un campo, la página se pinta con lo que decía antes.
   Una web sobre un viaje papal no puede quedarse muda porque falle MySQL. */
$paginaCms = $sitio->contenido('privacidad');
$secciones = $paginaCms['secciones'] ?? [];

$campo = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$hay = static fn (string $s): bool
    => \Intranet\Publico\Sitio::activa($secciones, $s);
?>

<main id="contenido">

<?php /* ── La portada de esta página ──────────────────────────────────────
         Sin foto, la franja es una banda roja lisa y compacta. En cuanto
         alguien elija una en el panel —Páginas → esta página → Cabecera—,
         la clase «--lisa» desaparece y la franja crece para mostrarla.

         Se quita la clase en lugar de dejarla siempre porque «--lisa» lleva
         min-height:0: con una fotografía dentro, la franja se colapsaría y
         la imagen no se vería. */ ?>
<?php $portada = $secciones['cabecera'] ?? []; ?>
<header class="cabecera-pagina<?= empty($portada['imagen_ruta']) ? ' cabecera-pagina--lisa' : '' ?>">
  <?php if (!empty($portada['imagen_ruta'])): ?>
    <div class="cabecera-pagina__media">
      <?= $sitio->imagen($portada, '', ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>
  <?php endif; ?>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
      <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Legal')) ?></span>
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Política de privacidad')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'Qué datos pedimos, para qué, cuánto tiempo los guardamos y cómo ejercer tus derechos.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><span aria-current="page">Privacidad</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<section class="seccion" aria-label="Texto legal">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-8">
    <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>Este sitio recoge datos personales en tres puntos y en ninguno más: el aviso por correo, el formulario de contacto y la inscripción de voluntariado. No hay analítica, ni píxeles de seguimiento, ni scripts de terceros que recojan datos.</p>
      <h2 class="legal__titulo">Responsable del tratamiento</h2>
      <p><strong>[RAZÓN SOCIAL POR CONFIRMAR]</strong>, con domicilio en <strong>[DOMICILIO POR CONFIRMAR]</strong>. Correo para ejercicio de derechos: <strong>[CORREO POR CONFIRMAR]</strong>.</p>
      <h2 class="legal__titulo">Qué datos y para qué</h2>
      <p><strong>Aviso por correo.</strong> Solo tu dirección de correo electrónico, para escribirte cuando se publique aquello de lo que pediste aviso. No se usa para ningún otro envío.</p>
      <p><strong>Formulario de contacto.</strong> Nombre, correo, motivo y el contenido de tu mensaje, para responderte.</p>
      <p><strong>Inscripción de voluntariado (Fase 01).</strong> Nombres y apellidos, DNI, fecha de nacimiento, dirección, correo, teléfono, talla de polo, contacto de emergencia, jurisdicción y servicio preferido. La finalidad es gestionar tu candidatura, asignarte un servicio y poder avisar a tu contacto de emergencia durante los días de la visita.</p>
      <h2 class="legal__titulo">Lo que este sitio no recoge</h2>
      <p>Los documentos de la Fase 02 del voluntariado —carta de recomendación, antecedentes judiciales y penales, evaluación psicológica— <strong>no se suben a esta web</strong>. Son datos sensibles con exigencias reforzadas y se entregan por un canal controlado por la organización.</p>
      <h2 class="legal__titulo">Plazo de conservación</h2>
      <p>Los datos se conservan hasta <strong>[PLAZO POR CONFIRMAR]</strong> y después se eliminan, salvo obligación legal de conservarlos más tiempo.</p>
      <h2 class="legal__titulo">Base legal</h2>
      <p>Tu consentimiento, prestado de forma expresa marcando la casilla correspondiente. La casilla nunca viene premarcada. Puedes retirar el consentimiento en cualquier momento, sin que ello afecte a la licitud del tratamiento anterior.</p>
      <h2 class="legal__titulo">Tus derechos</h2>
      <p>Puedes ejercer los derechos de acceso, rectificación, cancelación y oposición escribiendo a <strong>[CORREO POR CONFIRMAR]</strong>. Se te responderá en los plazos previstos por la normativa peruana de protección de datos personales.</p>
      <h2 class="legal__titulo">Destinatarios</h2>
      <p>Los datos no se ceden a terceros ni se usan con fines comerciales. En el caso del voluntariado podrán compartirse con la jurisdicción eclesiástica en la que vayas a servir, con la única finalidad de organizar el servicio.</p>
      <h2 class="legal__titulo">Seguridad</h2>
      <p>El sitio se sirve por HTTPS. La página del formulario de voluntariado no carga analítica ni recursos de terceros más allá de las tipografías y la librería de animación.</p>
      <h2 class="legal__titulo">Almacenamiento en tu navegador</h2>
      <p>El formulario de voluntariado guarda un borrador en el almacenamiento local de tu navegador para que no pierdas lo escrito si te interrumpen. Ese borrador <strong>no se envía a ningún servidor</strong> y puedes borrarlo con el botón que aparece sobre el formulario.</p>
    </div>
  </div></div></div>
</section>
</main>
