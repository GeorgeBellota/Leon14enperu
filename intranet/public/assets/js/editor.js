/* ============================================================================
   EDITOR DE NOTICIAS · dar forma al cuerpo sin saber HTML
   ----------------------------------------------------------------------------
   Lo pidió el cliente después de publicar la nota de la Jornada de
   Capacitación: «solo permite agregar una fotografía principal, un titular y
   un bloque de texto sin opciones para insertar imágenes adicionales,
   separar el contenido en párrafos o ajustar el espaciado […] no es posible
   insertar enlaces externos dentro del texto».

   ── Por qué sin biblioteca ────────────────────────────────────────────────

   Este proyecto no tiene Node ni Composer, y traer un editor de los grandes
   significa medio mega de JavaScript para poner negritas. Con
   «contenteditable» y las órdenes del navegador se cubre lo que se pidió:
   párrafos, títulos, listas, negrita, enlaces e imágenes.

   «document.execCommand» está marcado como obsoleto y lleva años así. Lo
   siguen admitiendo todos los navegadores y no hay reemplazo equivalente:
   lo que vendría después es escribir a mano la manipulación de rangos, que
   es mucho más código y más sitios donde fallar. Si algún día deja de
   funcionar, lo que se pierde son los botones, no el contenido: el área
   sigue siendo editable y el texto se escribe igual.

   ── Lo que NO hace, a propósito ───────────────────────────────────────────

   No deja pegar formato. Pegar desde Word mete tipografías, tamaños y
   colores que no son los del sitio, y el titular blanco ilegible del que se
   quejó el cliente salió justo de ahí. Se pega SIEMPRE como texto plano y se
   da formato con los botones. Es un paso más y evita el problema de raíz.

   Y lo que salga de aquí no es lo que se guarda: HtmlSeguro lo filtra en el
   servidor. Esto es comodidad, no seguridad.
   ========================================================================== */

(function () {
  'use strict';

  var area = document.querySelector('[data-editor]');
  if (!area) return;

  var campo = document.querySelector('[data-editor-campo]');
  var barra = document.querySelector('[data-editor-barra]');
  if (!campo || !barra) return;

  /* El contenido inicial sale del <textarea>, que es lo que viaja en el
     formulario y lo que se guarda si el JavaScript no llega a cargar. */
  area.innerHTML = campo.value || '<p></p>';

  function sincronizar() {
    campo.value = area.innerHTML.trim() === '<br>' ? '' : area.innerHTML;
  }

  area.addEventListener('input', sincronizar);
  area.addEventListener('blur', sincronizar);

  // Y antes de enviar, por si el último cambio fue con un botón.
  var formulario = campo.closest('form');
  if (formulario) formulario.addEventListener('submit', sincronizar);

  /* ── Pegar siempre en limpio ──────────────────────────────────────────── */
  area.addEventListener('paste', function (ev) {
    ev.preventDefault();

    var texto = (ev.clipboardData || window.clipboardData).getData('text/plain');

    // Los saltos dobles se vuelven párrafos: es como viene un texto de Word
    // y es lo que la persona espera ver.
    var trozos = texto.split(/\n{2,}/);

    if (trozos.length > 1) {
      document.execCommand('insertHTML', false, trozos.map(function (t) {
        return '<p>' + escapar(t.replace(/\n/g, '<br>')) + '</p>';
      }).join(''));
    } else {
      document.execCommand('insertText', false, texto);
    }

    sincronizar();
  });

  function escapar(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/&lt;br&gt;/g, '<br>');
  }

  /* ── Los botones ──────────────────────────────────────────────────────── */
  barra.addEventListener('click', function (ev) {
    var boton = ev.target.closest('[data-orden]');
    if (!boton) return;

    ev.preventDefault();
    area.focus();

    var orden = boton.getAttribute('data-orden');

    if (orden === 'enlace')  { ponerEnlace(); }
    else if (orden === 'imagen') { elegirImagen(); }
    else if (orden === 'video')  { ponerVideo(); }
    else if (orden === 'formatBlock') {
      document.execCommand('formatBlock', false, boton.getAttribute('data-valor'));
    }
    else { document.execCommand(orden, false, null); }

    sincronizar();
  });

  /* ── Enlaces ──────────────────────────────────────────────────────────── */
  /* ── Un vídeo de YouTube ────────────────────────────────────────────────
     Inserta el atajo [youtube src="…"] en su propio párrafo. Lo resuelve el
     SERVIDOR al guardar: busca el título, baja la portada a la biblioteca y
     deja la marca lista para pintar.

     Por eso aquí no se valida la dirección más allá de que parezca de
     YouTube: quien decide es el servidor, y si no la reconoce lo dice al
     guardar con el atajo todavía en su sitio para corregirlo. Validar aquí
     con otro criterio sólo serviría para rechazar enlaces que el servidor sí
     entiende. */
  function ponerVideo() {
    var url = window.prompt('Pega la dirección del vídeo de YouTube', 'https://');
    if (url === null) return;

    url = url.trim();

    if (url === '' || url === 'https://') return;

    if (!/youtu\.?be/i.test(url)) {
      avisar('Eso no parece un enlace de YouTube. Pega la dirección del vídeo.');
      return;
    }

    /* Las comillas se quitan: van dentro de un atributo del atajo y una
       comilla suelta lo partiría en dos. */
    document.execCommand('insertHTML', false,
      '<p>[youtube src="' + url.replace(/["'<>]/g, '') + '"]</p>');

    avisar('Vídeo añadido. Se verá al guardar: el servidor le busca la portada.');
  }

  function ponerEnlace() {
    var seleccion = window.getSelection();

    if (!seleccion || seleccion.isCollapsed) {
      avisar('Selecciona antes el texto que quieres enlazar.');
      return;
    }

    var url = window.prompt('¿A dónde lleva el enlace?', 'https://');
    if (url === null) return;

    url = url.trim();

    if (url === '' || url === 'https://') return;

    /* El servidor sólo admite http, https, mailto, tel y rutas del sitio.
       Se avisa aquí para no perder el viaje al guardar. */
    if (!/^(https?:\/\/|mailto:|tel:|\/)/i.test(url)) {
      avisar('El enlace debe empezar por https://, mailto:, tel: o /.');
      return;
    }

    document.execCommand('createLink', false, url);
    sincronizar();
  }

  /* ── Imágenes dentro del texto ────────────────────────────────────────── */
  var selector = document.querySelector('[data-editor-imagen]');

  function elegirImagen() {
    if (selector) selector.click();
  }

  if (selector) {
    selector.addEventListener('change', function () {
      var archivo = selector.files && selector.files[0];
      if (!archivo) return;

      var alt = window.prompt('¿Qué se ve en la imagen? (para quien no la ve)', '') || '';

      var datos = new FormData();
      datos.append('imagen', archivo);
      datos.append('alt', alt);
      datos.append('_csrf', barra.getAttribute('data-csrf') || '');

      avisar('Subiendo la imagen…', true);

      fetch(barra.getAttribute('data-sube'), {
        method: 'POST',
        body: datos,
        credentials: 'same-origin'
      })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
        .then(function (res) {
          if (!res.ok) { avisar(res.j.error || 'No se pudo subir la imagen.'); return; }

          area.focus();
          document.execCommand('insertHTML', false,
            '<p><img src="' + res.j.src + '" alt="' + alt.replace(/"/g, '&quot;') + '"></p>');

          sincronizar();
          avisar('');
        })
        .catch(function () { avisar('No se pudo subir la imagen. Revisa la conexión.'); });

      selector.value = '';
    });
  }

  /* ── Avisos ───────────────────────────────────────────────────────────── */
  var aviso = document.querySelector('[data-editor-aviso]');

  function avisar(texto, trabajando) {
    if (!aviso) { if (texto) window.alert(texto); return; }

    aviso.textContent = texto || '';
    aviso.hidden = !texto;
    aviso.classList.toggle('is-trabajando', !!trabajando);
  }

  /* El área ya es editable desde el HTML sólo si este archivo cargó: así,
     sin JavaScript, se ve el <textarea> de siempre y se puede escribir. */
  area.setAttribute('contenteditable', 'true');
  area.hidden = false;
  campo.hidden = true;
  barra.hidden = false;
})();
