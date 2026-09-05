<?php
/**
 * Vista de la página «sedes».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Sedes del viaje de León XIV: Lima, Chiclayo, Cusco y Pucallpa',
    'descripcion' => 'Las cuatro sedes del viaje apostólico de León XIV al Perú: por qué cada ciudad, su jurisdicción eclesiástica y su relación con el Santo Padre.',
    'og_titulo'      => 'Cuatro ciudades, un solo pueblo',
    'og_descripcion' => 'Lima, Chiclayo, Cusco y Pucallpa: las cuatro sedes del viaje apostólico de noviembre de 2026.',
    'ruta'        => 'sedes/',
    'og_imagen'   => 'assets/img/og/og-sedes.jpg',
    'og_tipo'     => 'article',
    'body_attr'   => 'data-phase="pre"',
    'head_extra'  => '<script type="application/ld+json" nonce="' . $esc($sitio->nonce()) . '">
{
  "@context": "https://schema.org",
  "@graph": [
    { "@type": "Place", "name": "Lima", "address": { "@type": "PostalAddress", "addressLocality": "Lima", "addressCountry": "PE" }, "description": "Arquidiócesis de Lima, primada del Perú. Sede del viaje apostólico de León XIV, noviembre de 2026." },
    { "@type": "Place", "name": "Chiclayo", "address": { "@type": "PostalAddress", "addressLocality": "Chiclayo", "addressRegion": "Lambayeque", "addressCountry": "PE" }, "description": "Diócesis de Chiclayo, de la que Robert Prevost fue obispo entre 2015 y 2023. Sede del viaje apostólico de León XIV, noviembre de 2026." },
    { "@type": "Place", "name": "Cusco", "address": { "@type": "PostalAddress", "addressLocality": "Cusco", "addressCountry": "PE" }, "description": "Arquidiócesis del Cusco. Sede del viaje apostólico de León XIV, noviembre de 2026." },
    { "@type": "Place", "name": "Pucallpa", "address": { "@type": "PostalAddress", "addressLocality": "Pucallpa", "addressRegion": "Ucayali", "addressCountry": "PE" }, "description": "Vicariato Apostólico de Pucallpa, en la Amazonía peruana. Sede del viaje apostólico de León XIV, noviembre de 2026." }
  ]
}
</script>',
];
?>
<?php
/* El contenido de esta página sale de la base y se edita desde el panel.
   Cada lectura lleva su texto de reserva: si la base no responde, o si
   alguien vacía un campo, la página se pinta con lo que decía antes.
   Una web sobre un viaje papal no puede quedarse muda porque falle MySQL. */
$paginaCms = $sitio->contenido('sedes');
$secciones = $paginaCms['secciones'] ?? [];

$campo = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$hay = static fn (string $s): bool
    => \Intranet\Publico\Sitio::activa($secciones, $s);
?>

<main id="contenido">

<header class="cabecera-pagina">
  <div class="cabecera-pagina__media">
    <?php /* ── La portada de esta página ──────────────────────────────
         Sale del panel: Páginas → esta página → Cabecera. Se puede
         elegir una foto para escritorio y otra para móvil.

         Lo que va aquí abajo es el RESPALDO: la fotografía que la
         página traía escrita a mano. Mientras nadie elija otra en el
         panel se sigue viendo ésta, así que pasar la portada al
         gestor no cambió el aspecto de nada el día del despliegue. */ ?>
      <?php ob_start(); ?>
      <picture>
      <source type="image/webp" sizes="100vw" srcset="../assets/img/sedes-banner.webp 640w, ../assets/img/sedes-banner.webp 1024w, ../assets/img/sedes-banner.webp">
      <img src="../assets/img/sedes-banner.webp" sizes="100vw" srcset="../assets/img/sedes-banner.webp 640w, ../assets/img/sedes-banner.webp 1024w, ../assets/img/sedes-banner.webp 1200w" width="1200" height="514" alt="El Papa León XIV saluda desde la logia junto a la bandera del Perú" loading="lazy" decoding="async">
    </picture>
      <?php $respaldoPortada = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoPortada, ['sizes' => '100vw', 'prioridad' => true]) ?>
  </div>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
  <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Sedes')) ?></span>
  <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Cuatro ciudades, un solo pueblo')) ?></h1>
  <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'Lima, Chiclayo, Cusco y Pucallpa. La costa, el norte, los Andes y la Amazonía. Cuatro maneras de ser Iglesia en el mismo país.')) ?></p>
  <nav class="migas" aria-label="Migas de pan">
    <ol>
      <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
      <li><span aria-current="page">Sedes</span></li>
    </ol>
  </nav>
    </div>
</div>
</header>

<!-- ══════════════ INTRODUCCIÓN ══════════════ -->
<section class="seccion" aria-labelledby="t-intro">
<div class="contenedor">
  <div class="reticula">
    <div class="col-m-4 col-t-6 col-d-8">
      <header class="seccion__encabezado seccion__encabezado--mayor">
        <hr class="seccion__filete" data-reveal="line-draw">
        <span class="rotulo"><?= $esc($campo('estas-cuatro', 'rotulo', 'Costa, sierra y selva')) ?></span>
        <h2 class="titular--mayor" id="t-intro" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('estas-cuatro', 'titulo', 'Por qué estas cuatro')) ?></span></span></h2>
      </header>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>La Santa Sede anunció el 5 de agosto de 2026 que el Papa León XIV visitará <strong>Lima, Chiclayo, Cusco y Pucallpa</strong> entre el 11 y el 16 de noviembre. Es la única información de itinerario que hay: no se ha publicado ni el orden del viaje ni los días que corresponden a cada ciudad.</p>
        <p>Aun así, la elección dice bastante. Están la capital y su arquidiócesis primada; la diócesis que el Santo Padre pastoreó durante ocho años; la Iglesia andina más antigua del país; y un vicariato apostólico amazónico. Costa, sierra y selva. Una arquidiócesis, una diócesis y un territorio de misión.</p>
        <p>En esta página encontrarás, para cada sede, su jurisdicción eclesiástica, por qué está en el viaje y qué relación guarda con León XIV. Todo lo que dependa del programa oficial aparece marcado como pendiente.</p>
      </div>
    </div>
  </div>
</div>
</section>

<!-- ══════════════ FICHAS DE SEDE ══════════════ -->
<section class="seccion">
<div class="contenedor">
  <div class="reticula">
    <div class="col-m-4 col-t-6 col-d-9">

      <!-- ── LIMA ── -->
      <article class="sede" id="lima">
        <figure class="sede__figura" data-reveal="wipe-up">
          <div class="figura ar-3-2">
            <picture>
              <source type="image/webp" sizes="(min-width:1024px) 66vw, 100vw" srcset="../assets/img/fotos/sede-lima-640.webp 640w, ../assets/img/fotos/sede-lima-1024.webp 1024w, ../assets/img/fotos/sede-lima-1600.webp 1600w">
              <img src="../assets/img/fotos/sede-lima-1024.jpg" sizes="(min-width:1024px) 66vw, 100vw" srcset="../assets/img/fotos/sede-lima-640.jpg 640w, ../assets/img/fotos/sede-lima-1024.jpg 1024w, ../assets/img/fotos/sede-lima-1600.jpg 1600w" width="1600" height="1067" alt="Celebración solemne presidida por el Papa León XIV" loading="lazy" decoding="async">
            </picture>
          </div>
          <figcaption class="pie-foto">Celebración solemne presidida por el Santo Padre. <span>Fotografía: Santa Sede</span></figcaption>
        </figure>

          <h2 class="sede__nombre">Lima</h2>
          <p class="sede__jurisdiccion">Arquidiócesis de Lima · Primada del Perú · Dentro del 11–16 de noviembre <span class="estado">Por confirmar</span></p>

          <div class="sede__bloque">
            <h3>Por qué esta ciudad</h3>
            <p>Lima es la capital y la mayor concentración de fieles del país: su arquidiócesis abarca a más de nueve millones de habitantes. Fue creada en 1546 por el papa Paulo III y desde entonces ha sido el centro desde el que se organizó la evangelización de esta parte del continente; en 1943 quedó confirmada como arquidiócesis primada del Perú. Toda visita pontificia pasa por aquí: es la sede protocolar y donde se concentra la logística del viaje.</p>
          </div>

          <div class="sede__bloque">
            <h3>Su relación con León XIV</h3>
            <p>Lima es la sede de la Conferencia Episcopal Peruana, de la que Robert Prevost fue segundo vicepresidente desde marzo de 2018, además de miembro de su Consejo Económico y presidente de la Comisión Episcopal de Cultura y Educación. En abril de 2020 fue nombrado administrador apostólico de la vecina diócesis del Callao. Su vínculo con Lima es sobre todo institucional: aquí se reunía con sus hermanos obispos.</p>
          </div>

          <div class="sede__bloque">
            <h3>Qué esperar</h3>
            <p>En Lima está la Basílica Catedral y la memoria de santo Toribio de Mogrovejo, patrono de la arquidiócesis, y de santa Rosa de Lima, patrona de la sede episcopal. Y está el Señor de los Milagros, el Cristo de Pachacamilla, cuya procesión de octubre es la manifestación de fe más multitudinaria del Perú. Nada de esto forma parte todavía del programa: son las señas de identidad de la ciudad que recibirá al Santo Padre.</p>
          </div>

          <p class="sede__bloque"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('guia-del-peregrino/#como-llegar')) ?>"  >Cómo llegar <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
        </article>

        <!-- ── CHICLAYO ── -->
        <article class="sede" id="chiclayo">
          <figure class="sede__figura" data-reveal="wipe-up">
            <div class="figura ar-3-2">
              <picture>
                <source type="image/webp" sizes="(min-width:1024px) 66vw, 100vw" srcset="../assets/img/fotos/sede-chiclayo-640.webp 640w, ../assets/img/fotos/sede-chiclayo-1024.webp 1024w, ../assets/img/fotos/sede-chiclayo-1486.webp 1486w">
                <img src="../assets/img/fotos/sede-chiclayo-1024.jpg" sizes="(min-width:1024px) 66vw, 100vw" srcset="../assets/img/fotos/sede-chiclayo-640.jpg 640w, ../assets/img/fotos/sede-chiclayo-1024.jpg 1024w, ../assets/img/fotos/sede-chiclayo-1486.jpg 1486w" width="1486" height="991" alt="El Papa León XIV bendice a una fiel durante una visita pastoral" loading="lazy" decoding="async">
              </picture>
            </div>
            <figcaption class="pie-foto">El Santo Padre bendice a una fiel durante una visita pastoral. <span>Fotografía: Santa Sede</span></figcaption>
          </figure>

          <h2 class="sede__nombre">Chiclayo</h2>
          <p class="sede__jurisdiccion">Diócesis de Chiclayo · Lambayeque y la provincia de Santa Cruz (Cajamarca) · Dentro del 11–16 de noviembre <span class="estado">Por confirmar</span></p>

          <div class="sede__bloque">
            <h3>Por qué esta ciudad</h3>
            <p>Si hay un lugar donde esta visita se lee como un regreso, es Chiclayo. La diócesis fue erigida el 17 de diciembre de 1956 con territorio desprendido de la arquidiócesis de Trujillo y de la diócesis de Cajamarca, y su jurisdicción cubre todo el departamento de Lambayeque y la provincia cajamarquina de Santa Cruz. Su sede es la iglesia de Santa María, cuya construcción comenzó en 1869.</p>
          </div>

          <div class="sede__bloque">
            <h3>Su relación con León XIV</h3>
            <p>Robert Prevost llegó a Chiclayo el 7 de noviembre de 2014 como administrador apostólico, y el 12 de diciembre de ese año —festividad de Nuestra Señora de Guadalupe— fue ordenado obispo en la catedral de Santa María. El 26 de septiembre de 2015 fue nombrado obispo de la diócesis, y lo fue hasta 2023, cuando el Papa Francisco lo llamó a Roma. Ocho años. Ese mismo 2023, la Universidad Católica Santo Toribio de Mogrovejo, de Chiclayo, le concedió el doctorado <em>honoris causa</em> en Derecho. Chiclayo no lo recibe como a un visitante ilustre: lo recibe como a su obispo.</p>
          </div>

          <div class="sede__bloque">
            <h3>Qué esperar</h3>
            <p>Lambayeque es tierra de devociones muy arraigadas y de una religiosidad popular que llena los caminos. Es también una región de trabajo agrícola y comunidades dispersas: exactamente el tipo de periferia que ha marcado el ministerio del Santo Padre desde que llegó al Perú en 1985, primero a la misión agustiniana de Chulucanas y después a Trujillo.</p>
          </div>

          <p class="sede__bloque"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('guia-del-peregrino/#como-llegar')) ?>"  >Cómo llegar <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
        </article>

        <!-- ── CUSCO ── -->
        <article class="sede" id="cusco">
          <figure class="sede__figura" data-reveal="wipe-up">
            <div class="figura ar-3-2">
              <picture>
                <source type="image/webp" sizes="(min-width:1024px) 66vw, 100vw" srcset="../assets/img/fotos/sede-cusco-480.webp 480w, ../assets/img/fotos/sede-cusco-596.webp 596w">
                <img src="../assets/img/fotos/sede-cusco-596.jpg" sizes="(min-width:1024px) 66vw, 100vw" srcset="../assets/img/fotos/sede-cusco-480.jpg 480w, ../assets/img/fotos/sede-cusco-596.jpg 596w" width="596" height="397" alt="El Santo Padre, en oración ante el altar" loading="lazy" decoding="async">
              </picture>
            </div>
            <figcaption class="pie-foto">El Santo Padre, en oración ante el altar. <span>Fotografía: Santa Sede</span></figcaption>
          </figure>

          <h2 class="sede__nombre">Cusco</h2>
          <p class="sede__jurisdiccion">Arquidiócesis del Cusco · Dentro del 11–16 de noviembre <span class="estado">Por confirmar</span></p>

          <div class="sede__bloque">
            <h3>Por qué esta ciudad</h3>
            <p>Cusco es la capital histórica del Perú y la sede de una de las Iglesias más antiguas del continente, elevada al rango de arquidiócesis en 1943. Representa a la Iglesia de la sierra: la que se reza en quechua y en castellano, la que sostiene comunidades a más de tres mil metros de altura y la que ha hecho de la fiesta religiosa el eje del año.</p>
          </div>

          <div class="sede__bloque">
            <h3>Su relación con León XIV</h3>
            <p>No consta un vínculo pastoral directo del Santo Padre con el Cusco: sus cuarenta años peruanos transcurrieron sobre todo en el norte. Sí conoce de cerca el mundo andino: desde Trujillo dirigió, entre 1988 y 1998, el proyecto de formación común de los aspirantes agustinos de los vicariatos de Chulucanas, Iquitos y Apurímac —desierto, selva y Andes— y fue profesor de Derecho Canónico, Patrística y Moral en el Seminario Mayor San Carlos y San Marcelo.</p>
          </div>

          <div class="sede__bloque">
            <h3>Qué esperar</h3>
            <p>El Señor de los Temblores, Patrón Jurado del Cusco, cuya procesión del Lunes Santo detiene la ciudad entera. El Corpus Christi cusqueño, con sus imágenes recorriendo las calles hasta la catedral. Y la Virgen de los Remedios, patrona de la arquidiócesis. Cusco no necesita que le enseñen a recibir: lleva siglos haciéndolo.</p>
          </div>

          <p class="sede__bloque"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('guia-del-peregrino/#como-llegar')) ?>"  >Cómo llegar <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
        </article>

        <!-- ── PUCALLPA ── -->
        <article class="sede" id="pucallpa">
          <figure class="sede__figura" data-reveal="wipe-up">
            <div class="figura ar-3-2">
              <picture>
                <source type="image/webp" sizes="(min-width:1024px) 66vw, 100vw" srcset="../assets/img/fotos/sede-pucallpa-480.webp 480w, ../assets/img/fotos/sede-pucallpa-678.webp 678w">
                <img src="../assets/img/fotos/sede-pucallpa-678.jpg" sizes="(min-width:1024px) 66vw, 100vw" srcset="../assets/img/fotos/sede-pucallpa-480.jpg 480w, ../assets/img/fotos/sede-pucallpa-678.jpg 678w" width="678" height="452" alt="El Papa León XIV saluda a los fieles" loading="lazy" decoding="async">
              </picture>
            </div>
            <figcaption class="pie-foto">El Santo Padre saluda a los fieles. <span>Fotografía: Santa Sede</span></figcaption>
          </figure>

          <h2 class="sede__nombre">Pucallpa</h2>
          <p class="sede__jurisdiccion">Vicariato Apostólico de Pucallpa · Ucayali · Dentro del 11–16 de noviembre <span class="estado">Por confirmar</span></p>

          <div class="sede__bloque">
            <h3>Por qué esta ciudad</h3>
            <p>Pucallpa no es sede de una diócesis, sino de un vicariato apostólico: una circunscripción de territorio de misión que depende directamente de la Santa Sede. Fue erigido el 2 de marzo de 1956, al dividirse el antiguo vicariato de Ucayali, y cubre más de 52.000 kilómetros cuadrados en las provincias de Coronel Portillo, Padre Abad y parte de Atalaya. Que una de las cuatro sedes sea ésta dice algo en sí mismo: la Amazonía y sus pueblos originarios no son un apéndice del viaje.</p>
          </div>

          <div class="sede__bloque">
            <h3>Su relación con León XIV</h3>
            <p>Su trato con la Amazonía peruana viene de lejos. El proyecto de formación agustiniana que dirigió desde Trujillo reunía a los aspirantes de tres vicariatos, y uno de ellos era Iquitos, en plena selva. Más tarde, como prefecto del Dicasterio para los Obispos y presidente de la Pontificia Comisión para América Latina, tuvo a su cargo circunscripciones como ésta en todo el continente.</p>
          </div>

          <div class="sede__bloque">
            <h3>Qué esperar</h3>
            <p>El vicariato contaba con unos 462.000 bautizados a finales de 2022, repartidos por un territorio inmenso al que muchas veces se llega por río. Es la Iglesia de las distancias largas y de las comunidades pequeñas, la que trabaja con los pueblos originarios de Ucayali. Aquí la palabra <em>periferia</em> no es una metáfora.</p>
          </div>

          <p class="sede__bloque"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('guia-del-peregrino/#como-llegar')) ?>"  >Cómo llegar <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
        </article>

      </div>
    </div>
  </div>
</section>

<!-- ══════════════ DIAGRAMA DE RUTA ══════════════ -->
<section class="seccion seccion--tinte" aria-labelledby="t-diagrama-sedes">
  <div class="contenedor">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('cuatro-sedes-mapa', 'rotulo', 'Diagrama esquemático')) ?></span>
      <h2 id="t-diagrama-sedes" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('cuatro-sedes-mapa', 'titulo', 'Las cuatro sedes en el mapa del país')) ?></span></span></h2>
      <p>Diagrama esquemático, no un mapa: las cuatro ciudades situadas por su posición relativa de norte a sur. <strong>El orden del viaje no se ha publicado</strong> y aquí no se insinúa.</p>
    </header>

    <div class="reticula">
      <div class="col-m-4 col-t-3 col-d-5 diagrama">
        <svg viewBox="0 0 300 520" role="img" aria-labelledby="t-svg-sedes d-svg-sedes" data-reveal="fade-rise">
          <title id="t-svg-sedes">Las cuatro sedes del viaje apostólico, de norte a sur</title>
          <desc id="d-svg-sedes">De norte a sur: Chiclayo, Pucallpa, Lima y Cusco, unidas por la línea del recorrido.</desc>
          <line class="diagrama__eje" x1="150" y1="14" x2="150" y2="506"/>
          <text class="diagrama__cardinal" x="150" y="10" text-anchor="middle">N</text>
          <text class="diagrama__cardinal" x="150" y="518" text-anchor="middle">S</text>
          <path class="diagrama__ruta" d="M65 73 C110 105 152 118 181 163 C202 216 140 300 126 369 C119 411 191 424 237 452"/>
          <a href="#chiclayo" class="nodo" aria-label="Ir a la ficha de Chiclayo">
            <circle class="nodo__aro" cx="65" cy="73" r="13"/><circle class="nodo__punto" cx="65" cy="73" r="6.5"/>
            <text class="nodo__texto" x="82" y="78">Chiclayo</text>
          </a>
          <a href="#pucallpa" class="nodo" aria-label="Ir a la ficha de Pucallpa">
            <circle class="nodo__aro" cx="181" cy="163" r="13"/><circle class="nodo__punto" cx="181" cy="163" r="6.5"/>
            <text class="nodo__texto" x="198" y="168">Pucallpa</text>
          </a>
          <a href="#lima" class="nodo" aria-label="Ir a la ficha de Lima">
            <circle class="nodo__aro" cx="126" cy="369" r="13"/><circle class="nodo__punto" cx="126" cy="369" r="6.5"/>
            <text class="nodo__texto" x="143" y="374">Lima</text>
          </a>
          <a href="#cusco" class="nodo" aria-label="Ir a la ficha de Cusco">
            <circle class="nodo__aro" cx="237" cy="452" r="13"/><circle class="nodo__punto" cx="237" cy="452" r="6.5"/>
            <text class="nodo__texto" x="197" y="478" text-anchor="middle">Cusco</text>
          </a>
        </svg>
      </div>

      <div class="col-m-4 col-t-3 col-d-6">
        <div class="texto-lectura">
          <p>Del norte costero a los Andes del sur, pasando por la Amazonía central: las cuatro sedes recorren de un extremo a otro las tres regiones del país. Es una geografía que obliga a volar y que explica por qué el viaje ocupa seis días completos.</p>
        </div>
        <div class="aviso sep-m">
          <p class="aviso__titulo">Cómo llegar a tu sede</p>
          <p>Las indicaciones de acceso, transporte y puntos de encuentro dependen de los recintos, que aún no se han anunciado. Se publicarán junto con el programa oficial.</p>
        </div>
        <p class="seccion__pie"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('agenda/')) ?>">Ver el estado de la agenda <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
      </div>
    </div>
  </div>
</section>

</main>
