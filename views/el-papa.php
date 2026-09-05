<?php
/**
 * Vista de la página «el-papa».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'León XIV, el Papa que volvió a ser peruano · Biografía',
    'descripcion' => 'Robert Francis Prevost, agustino, misionero en Chulucanas, formador en Trujillo y obispo de Chiclayo. Biografía del 267.º Papa y su vínculo con el Perú.',
    'ruta'        => 'el-papa/',
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
$paginaCms = $sitio->contenido('el-papa');
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
      <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', '267.º sucesor de Pedro')) ?></span>
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Cuarenta años de Perú')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'Robert Francis Prevost llegó a Chulucanas en 1985 con treinta años. Salió del Perú en 2023 siendo ciudadano peruano y obispo de Chiclayo.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><span aria-current="page">El Papa</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<!-- ══════════════ RETRATO Y FICHA ══════════════ -->
<section class="seccion" aria-labelledby="t-ficha">
  <div class="contenedor">
    <div class="reticula">
      <figure class="col-m-4 col-t-3 col-d-4 retrato" data-reveal="wipe-up">
        <div class="figura ar-4-5">
          <picture>
            <source type="image/webp" sizes="(min-width:1024px) 32vw, 100vw" srcset="../assets/img/pontifice/retrato-oficial-640.webp 640w, ../assets/img/pontifice/retrato-oficial-1024.webp 1024w, ../assets/img/pontifice/retrato-oficial-1131.webp 1131w">
            <img src="../assets/img/pontifice/retrato-oficial-1024.jpg" sizes="(min-width:1024px) 32vw, 100vw" srcset="../assets/img/pontifice/retrato-oficial-640.jpg 640w, ../assets/img/pontifice/retrato-oficial-1024.jpg 1024w, ../assets/img/pontifice/retrato-oficial-1131.jpg 1131w" width="1131" height="1414" alt="Su Santidad el Papa León XIV" loading="lazy" decoding="async">
          </picture>
        </div>
        <figcaption class="pie-foto">Su Santidad el Papa León XIV. <span>Fotografía: Santa Sede</span></figcaption>
      </figure>

      <div class="col-m-4 col-t-3 col-d-7">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('quien-leon-xiv', 'rotulo', 'En breve')) ?></span>
      <h2 class="titular--mayor" id="t-ficha" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('quien-leon-xiv', 'titulo', 'Quién es León XIV')) ?></span></span></h2>
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>Primer Papa agustino y segundo del continente americano. Fue elegido el 8 de mayo de 2025, en la tarde del segundo día de cónclave.</p>
    </header>
        <dl class="ficha-datos">
          <div><dt>Nombre</dt><dd>Robert Francis Prevost, O.S.A.</dd></div>
          <div><dt>Nacimiento</dt><dd>14 de septiembre de 1955, Chicago (Illinois)</dd></div>
          <div><dt>Orden</dt><dd>San Agustín. Primera profesión en 1978, votos solemnes en 1981</dd></div>
          <div><dt>Ordenación sacerdotal</dt><dd>19 de junio de 1982, Roma</dd></div>
          <div><dt>En el Perú</dt><dd>Chulucanas, Trujillo, Chiclayo y Callao, entre 1985 y 2023</dd></div>
          <div><dt>Obispo de Chiclayo</dt><dd>2015 – 2023</dd></div>
          <div><dt>Elección al pontificado</dt><dd>8 de mayo de 2025, segundo día de cónclave</dd></div>
          <div><dt>Fumata blanca</dt><dd>18:07, hora de Roma</dd></div>
        </dl>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ RAÍCES ══════════════ -->
<section class="seccion mancha mancha--sup-der" aria-labelledby="t-raices">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-6 col-d-8">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('raices-vocacion-agustiniana', 'rotulo', '1955 – 1984')) ?></span>
      <h2 class="titular--mayor" id="t-raices" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('raices-vocacion-agustiniana', 'titulo', 'Raíces y vocación agustiniana')) ?></span></span></h2>
    </header>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>Nació en Chicago el 14 de septiembre de 1955, hijo de Louis Marius Prevost, de ascendencia francesa e italiana, y de Mildred Martínez, de ascendencia española. Tiene dos hermanos, Louis Martín y John Joseph.</p>
        <p>Estudió primero en el Seminario Menor de los Padres Agustinos y después en la Universidad de Villanova, en Pensilvania, donde se licenció en Matemáticas y cursó Filosofía en 1977. El 1 de septiembre de ese mismo año ingresó en el noviciado de la Orden de San Agustín en St. Louis, en la provincia de Nuestra Señora del Buen Consejo de Chicago. Hizo su primera profesión el 2 de septiembre de 1978 y emitió los votos solemnes el 29 de agosto de 1981.</p>
        <p>Se formó en la Catholic Theological Union de Chicago, donde se licenció en Teología. A los veintisiete años sus superiores lo enviaron a Roma a estudiar Derecho Canónico en la Pontificia Universidad Santo Tomás de Aquino. Allí fue ordenado sacerdote el 19 de junio de 1982, en el Colegio Agustiniano de Santa Mónica.</p>
      </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ EL PERÚ ══════════════ -->
<section class="seccion seccion--tinte seccion--pastel-acento" aria-labelledby="t-peru">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-6 col-d-8">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('misionero-formador-peru', 'rotulo', '1985 – 1999')) ?></span>
      <h2 class="titular--mayor" id="t-peru" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('misionero-formador-peru', 'titulo', 'Misionero y formador en el Perú')) ?></span></span></h2>
    </header>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>Se licenció en 1984 y al año siguiente, mientras preparaba su tesis doctoral, fue enviado a la misión agustiniana de <strong>Chulucanas, en Piura</strong>. Allí empezaron sus cuarenta años de Perú.</p>
        <p>En 1987 defendió su tesis sobre el papel del prior local en la Orden de San Agustín y fue nombrado director de Vocaciones y director de Misiones de su provincia agustiniana en Illinois. Al año siguiente volvió, esta vez a <strong>Trujillo</strong>, como director del proyecto de formación común para los aspirantes agustinos de los vicariatos de Chulucanas, Iquitos y Apurímac: el desierto, la selva y los Andes en una misma casa de formación.</p>
        <p>En once años fue prior de la comunidad, director de formación y profesor de profesos. En la archidiócesis de Trujillo fue vicario judicial y profesor de Derecho Canónico, Patrística y Moral en el Seminario Mayor San Carlos y San Marcelo.</p>
        <p>Al mismo tiempo se le confió la atención pastoral de Nuestra Señora Madre de la Iglesia, más tarde parroquia de Santa Rita, en la periferia pobre de la ciudad, y la administración parroquial de Nuestra Señora de Monserrat. Esos once años de parroquia de barrio son la parte de su biografía que menos aparece en los titulares y más explica lo demás.</p>
      </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ CHICLAYO ══════════════ -->
<section class="seccion" aria-labelledby="t-chiclayo">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-6 col-d-8">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('prior-general-obispo', 'rotulo', '1999 – 2023')) ?></span>
      <h2 class="titular--mayor" id="t-chiclayo" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('prior-general-obispo', 'titulo', 'De prior general a obispo de Chiclayo')) ?></span></span></h2>
    </header>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>En 1999 fue elegido prior provincial de la provincia agustiniana de Chicago y, dos años y medio después, sus hermanos lo eligieron <strong>prior general de la Orden</strong>. Fue confirmado en 2007 para un segundo mandato.</p>
        <p>El 3 de noviembre de 2014 el Papa Francisco lo nombró administrador apostólico de la diócesis de <strong>Chiclayo</strong> y lo elevó a la dignidad episcopal como obispo titular de Sufar. Entró en la diócesis el 7 de noviembre y fue ordenado obispo el 12 de diciembre, festividad de Nuestra Señora de Guadalupe, en la catedral de Santa María. El 26 de septiembre de 2015 fue nombrado obispo de Chiclayo.</p>
        <p>En marzo de 2018 fue elegido segundo vicepresidente de la Conferencia Episcopal Peruana, de la que también fue miembro del Consejo Económico y presidente de la Comisión Episcopal de Cultura y Educación. En abril de 2020 asumió además la administración apostólica de la diócesis del Callao.</p>
        <p>En 2023, al dejar el Perú, la Conferencia Episcopal Peruana le concedió la Medalla de Oro de Santo Toribio de Mogrovejo, y la Universidad Católica Santo Toribio de Mogrovejo, de Chiclayo, el doctorado <em>honoris causa</em> en Derecho.</p>
      </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ ROMA Y EL PONTIFICADO ══════════════ -->
<section class="seccion mancha mancha--inf-izq mancha--oro" aria-labelledby="t-roma">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-6 col-d-8">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('chiclayo-pontificado', 'rotulo', '2023 – hoy')) ?></span>
      <h2 class="titular--mayor" id="t-roma" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('chiclayo-pontificado', 'titulo', 'De Chiclayo al pontificado')) ?></span></span></h2>
    </header>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>El 30 de enero de 2023 el Papa Francisco lo llamó a Roma como <strong>prefecto del Dicasterio para los Obispos</strong> y presidente de la Pontificia Comisión para América Latina, elevándolo al rango de arzobispo. En el consistorio del 30 de septiembre de ese año fue creado cardenal, con la diaconía de Santa Mónica, de la que tomó posesión en enero de 2024.</p>
        <p>El 6 de febrero de 2025 fue promovido al orden de los cardenales obispos, con el título suburbicario de Albano. Durante la última hospitalización del Papa Francisco presidió, el 3 de marzo, el rosario por la salud del Pontífice en la plaza de San Pedro.</p>
        <p>El cónclave se abrió el 7 de mayo de 2025. Fue elegido en la tarde del día siguiente, <strong>el 8 de mayo</strong>, y eligió el nombre de León XIV. La fumata blanca se elevó sobre la Capilla Sixtina a las 18:07, hora de Roma. Es el 267.º Pontífice, el primero procedente de los Estados Unidos y el primero perteneciente a la Orden de San Agustín. El 18 de mayo presidió la celebración de inicio de su ministerio petrino.</p>
      </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ ESCUDO Y LEMA ══════════════ -->
<section class="seccion seccion--tinte seccion--pastel" aria-labelledby="t-escudo">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-3 col-d-4" data-reveal="fade-rise">
        <img class="escudo-pagina" src="../assets/img/escudo-leon-xiv.svg" width="440" height="545" alt="Escudo de Su Santidad el Papa León XIV: partido en diagonal, con lirio de plata sobre campo azur y corazón llameante traspasado por una flecha sobre un libro cerrado" loading="lazy">
      </div>
      <div class="col-m-4 col-t-3 col-d-7">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('escudo-lema', 'rotulo', 'Heráldica')) ?></span>
      <h2 class="titular--mayor" id="t-escudo" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('escudo-lema', 'titulo', 'El escudo y el lema')) ?></span></span></h2>
    </header>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>El escudo está dividido diagonalmente en dos sectores. La parte superior tiene fondo azul y presenta un lirio blanco. La inferior, sobre fondo claro, lleva la imagen que recuerda a la Orden de San Agustín: un libro cerrado sobre el que descansa un corazón traspasado por una flecha.</p>
        <p>Esa imagen evoca la conversión de san Agustín, que él mismo explicó con las palabras <em>«Vulnerasti cor meum verbo tuo»</em>: has traspasado mi corazón con tu Palabra.</p>
        <p>El lema, <strong>«In Illo uno unum»</strong>, procede de un sermón de san Agustín, la Exposición del Salmo 127, y significa que aunque los cristianos seamos muchos, en el único Cristo somos uno. León XIV confirmó en lo esencial el escudo y el lema que había elegido para su consagración episcopal en Chiclayo.</p>
      </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ MAGISTERIO ══════════════ -->
<section class="seccion" aria-labelledby="t-magisterio">
  <div class="contenedor">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('magisterio-hasta-hoy', 'rotulo', 'Documentos')) ?></span>
      <h2 class="titular--mayor" id="t-magisterio" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('magisterio-hasta-hoy', 'titulo', 'Su magisterio hasta hoy')) ?></span></span></h2>
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>Dos textos mayores en lo que va de pontificado. El magisterio completo se publica en la web de la Santa Sede.</p>
    </header>
    <ul class="dias">
      <li class="dia" data-reveal="fade-rise">
        <h3 class="dia__sede">Dilexi te</h3>
        <p class="dia__ventana"><time datetime="2025-10-04">4 de octubre de 2025</time></p>
        <p class="estado estado--cumplido">Exhortación apostólica</p>
      </li>
      <li class="dia" data-reveal="fade-rise">
        <h3 class="dia__sede">Magnifica humanitas</h3>
        <p class="dia__ventana"><time datetime="2026-05-25">25 de mayo de 2026</time></p>
        <p class="estado estado--cumplido">Carta encíclica</p>
      </li>
    </ul>
    <div class="reticula sep-l">
      <div class="col-m-4 col-t-3 col-d-5">
        <p class="tarjeta__texto"><strong>Dilexi te</strong> («Te he amado») es su primera exhortación apostólica y el primer gran documento del pontificado. Se centra en el amor de Dios y de la Iglesia hacia los pobres.</p>
      </div>
      <div class="col-m-4 col-t-3 col-d-5">
        <p class="tarjeta__texto"><strong>Magnifica humanitas</strong> es su primera carta encíclica. Aborda la custodia de la dignidad de la persona humana frente a los avances y los desafíos éticos de la inteligencia artificial.</p>
      </div>
    </div>
    <p class="seccion__pie"><a class="enlace-flecha" href="https://www.vatican.va/content/leo-xiv/es.html" rel="noopener">Magisterio completo en vatican.va <svg aria-hidden="true"><use href="#i-enlace"/></svg></a></p>
  </div>
</section>
</main>
