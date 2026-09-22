<?php
/**
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Csrf       $csrf
 * @var array<string,string> $paginas
 * @var string $inicio
 * @var array  $visibles
 * @var array  $pieEnlaces
 * @var string $pie
 * @var string $inicioViaje
 * @var string $finViaje
 * @var string $fase
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

/* $visibles llega ya resuelto por el controlador: las claves traducidas y, si
   no hay nada guardado, las nueve del diseño que es lo que enseña la web. Aquí
   sólo se marca lo que hay. */
$estaVisible = static fn (string $clave): bool => in_array($clave, $visibles, true);
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

    <header class="tarjeta__cabecera sep-m"><h3>Enlaces de la banda</h3></header>

    <p class="vacio">
      Van debajo del copyright, en una sola línea. Son para las páginas que no están en
      el menú y a las que conviene dejar un camino. Si no marcas ninguna, la banda se
      queda sólo con el copyright.
    </p>

    <p class="campo__ayuda">
      Sólo aparecen con el pie en «Sólo el copyright» o en las internas. Con el pie
      completo no se pintan: esas páginas ya están dentro de las columnas.
    </p>

    <div class="lista-casillas sep-m">
      <?php foreach ($paginas as $clave => $rotulo): ?>
        <label class="interruptor">
          <input type="checkbox" name="pie_enlaces[]" value="<?= $e($clave) ?>"<?= in_array($clave, $pieEnlaces, true) ? ' checked' : '' ?>>
          <span><?= $e($rotulo) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </section>

  <?php /* ── Transmisión en directo ───────────────────────────────────────
           Se pide el identificador o la dirección, no el bloque <iframe> que
           da YouTube: ese bloque es HTML, y un campo libre cuyo contenido
           acaba en la página deja meter lo que sea. El reproductor lo compone
           el servidor. */ ?>
  <section class="tarjeta sep-l">
    <header class="tarjeta__cabecera"><h2>Transmisión en directo</h2></header>

    <p class="campo__ayuda">
      Aparece en <strong>En directo</strong>. Vacío = la página se queda como
      está, sin reproductor.
    </p>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="c-directo">Vídeo o canal de YouTube</label>
      <input type="text" id="c-directo" name="directo_youtube" value="<?= $e($directo) ?>"
             placeholder="https://www.youtube.com/watch?v=..." spellcheck="false">
      <p class="campo__ayuda">
        Pega la dirección de la transmisión tal cual. También vale el
        identificador suelto: 11 caracteres para un vídeo concreto, o el del
        canal —empieza por <code>UC</code>— para que emita lo que el canal esté
        dando en cada momento, sin tener que volver aquí cada día.
      </p>
    </div>

    <div class="campo">
      <label class="campo__etiqueta" for="c-directo-titulo">Título sobre el reproductor</label>
      <input type="text" id="c-directo-titulo" name="directo_titulo" value="<?= $e($directoTitulo) ?>"
             placeholder="Misa en el Parque Bicentenario" maxlength="120">
    </div>

    <?php if ($directo !== ''): ?>
      <p class="campo__ayuda campo__ayuda--aviso sep-m">
        <strong>El reproductor no carga solo: hace falta pulsar.</strong>
        Hasta que alguien lo hace, la página no contacta con YouTube ni deja
        ninguna cookie. Al pulsar, la visualización se cuenta entera en tus
        estadísticas de YouTube.
      </p>
    <?php endif; ?>
  </section>

  <?php /* ── Medición ─────────────────────────────────────────────────────
           Se pide el IDENTIFICADOR, no el fragmento de código que Google y
           Meta dan para pegar. Ese fragmento es JavaScript, y un campo libre
           cuyo contenido acaba dentro de un <script> deja que quien entre al
           panel ejecute lo que quiera en todas las páginas del sitio. */ ?>
  <section class="tarjeta sep-l">
    <header class="tarjeta__cabecera"><h2>Medición de visitas</h2></header>

    <p class="campo__ayuda">
      Los dos campos son opcionales y vienen vacíos. Mientras lo estén, el sitio
      no carga nada de Google ni de Meta, no guarda ninguna cookie de
      seguimiento y no muestra ningún aviso.
    </p>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="c-ga4">Google Analytics · identificador de medición</label>
      <input type="text" id="c-ga4" name="analitica_ga4" value="<?= $e($ga4) ?>"
             placeholder="G-XXXXXXXXXX" maxlength="20" spellcheck="false">
      <p class="campo__ayuda">
        Está en Google Analytics, en <strong>Administrar → Flujos de datos</strong>.
        Empieza por <code>G-</code>. No pegues el bloque de código: sólo el identificador.
      </p>
    </div>

    <div class="campo">
      <label class="campo__etiqueta" for="c-pixel">Meta · identificador del píxel</label>
      <input type="text" id="c-pixel" name="analitica_pixel" value="<?= $e($pixel) ?>"
             placeholder="123456789012345" maxlength="20" inputmode="numeric" spellcheck="false">
      <p class="campo__ayuda">
        Está en el <strong>Administrador de eventos</strong> de Meta. Son sólo números.
      </p>
    </div>

    <?php if ($ga4 !== '' || $pixel !== ''): ?>
      <p class="campo__ayuda campo__ayuda--aviso sep-m">
        <strong>Con esto activado, el sitio pide consentimiento antes de medir.</strong>
        Ninguna de las dos herramientas se carga hasta que la persona acepta, y
        quien rechace no es seguido. La página de Cookies y la de Privacidad
        tienen que decir qué se usa y para qué.
      </p>
    <?php endif; ?>
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

  <!-- ── Correo del formulario de contacto ── -->
  <section class="tarjeta sep-l">
    <header class="tarjeta__cabecera"><h2>Correo de contacto</h2></header>

    <p class="vacio">
      Quien envía los mensajes del formulario de <strong>Contacto</strong> no es este
      servidor, sino un pequeño archivo alojado en el cPanel. Desde aquí el correo o no
      sale o acaba en la carpeta de spam: la dirección del servidor no tiene reputación
      y el dominio no la respalda desde esta máquina.
    </p>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="contacto_api_url">Dirección de la API</label>
      <input type="url" id="contacto_api_url" name="contacto_api_url"
             value="<?= $e($contactoUrl) ?>"
             placeholder="https://tu-cpanel.com/externo/enviar.php">
      <p class="campo__ayuda">
        Tiene que empezar por <strong>https://</strong>. Por ahí viaja el token, y en
        una dirección sin cifrar lo leería cualquiera en el camino.
        <strong>Si la dejas vacía, el formulario no envía nada</strong> y le dice a
        quien escriba que use los correos directos.
      </p>
    </div>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="contacto_api_token">Token</label>
      <input type="text" id="contacto_api_token" name="contacto_api_token"
             value="<?= $e($contactoToken) ?>" autocomplete="off" spellcheck="false">
      <p class="campo__ayuda">
        El mismo que pusiste en <code>externo/config.php</code> del cPanel. Si no
        coinciden, la API rechaza el envío y el mensaje no sale.
      </p>
    </div>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="contacto_asunto">Asunto del aviso</label>
      <input type="text" id="contacto_asunto" name="contacto_asunto"
             value="<?= $e($contactoAsunto) ?>">
      <p class="campo__ayuda">El asunto con el que llega el correo al buzón.</p>
    </div>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="contacto_plantilla">Cuerpo del aviso</label>
      <textarea id="contacto_plantilla" name="contacto_plantilla" rows="10"><?= $e($contactoPlantilla) ?></textarea>
      <p class="campo__ayuda">
        Los marcadores se sustituyen por lo que escriba la persona:
        <code>{nombre}</code>, <code>{correo}</code>, <code>{motivo}</code>,
        <code>{mensaje}</code> y <code>{fecha}</code>. Valen también en el asunto.
        Se envía como texto plano; las etiquetas HTML no se interpretan.
      </p>
    </div>

    <p class="campo__ayuda sep-m">
      La dirección que recibe los mensajes <strong>no se pone aquí</strong>, sino en el
      <code>config.php</code> del cPanel. Es a propósito: si el destinatario viajara
      con cada envío, quien consiguiera el token podría mandar correo a donde quisiera
      firmado con tu dominio.
    </p>
  </section>

  <div class="barra-guardar">
    <button class="btn btn--primario" type="submit">Guardar</button>
  </div>
</form>
