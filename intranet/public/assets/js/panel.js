/* ============================================================================
   INTRANET · JavaScript del panel
   ----------------------------------------------------------------------------
   Progresivo y sin dependencias: el panel funciona entero sin este archivo.
   Lo que hay aquí son comodidades, nunca requisitos.

   La Content-Security-Policy no permite 'unsafe-inline' en scripts, así que
   todo el JavaScript vive en archivos como éste. Nada de onclick="" en el HTML.
   ========================================================================== */

(function () {
  'use strict';

  /* ── Confirmación en acciones destructivas ────────────────────────────
     Cualquier botón con data-confirmar pide confirmación antes de enviar.
     Dar de baja a un voluntario no puede ser un clic accidental. */
  document.addEventListener('submit', function (evento) {
    var formulario = evento.target;
    if (!(formulario instanceof HTMLFormElement)) return;

    var mensaje = formulario.getAttribute('data-confirmar');
    if (mensaje && !window.confirm(mensaje)) {
      evento.preventDefault();
      return;
    }

    /* Doble envío: en una conexión lenta es fácil pulsar dos veces y crear
       dos registros idénticos. Se desactiva el botón tras el primer envío. */
    var boton = formulario.querySelector('[type="submit"]');
    if (boton && !formulario.hasAttribute('data-permitir-reenvio')) {
      window.setTimeout(function () {
        boton.disabled = true;
        boton.setAttribute('aria-busy', 'true');
      }, 0);
    }
  });

  /* ── Campos que se seleccionan solos al pulsarlos ─────────────────────
     Una ruta larga en un campo de solo lectura está para copiarla. Esto
     estaba escrito como onclick en el HTML, donde no llegaba a ejecutarse
     nunca: la CSP del panel es script-src 'self' y el navegador bloquea los
     manejadores en línea sin decir nada. */
  document.addEventListener('focus', function (evento) {
    var campo = evento.target;
    if (campo instanceof HTMLInputElement && campo.hasAttribute('data-marcar-todo')) {
      campo.select();
    }
  }, true);

  /* ── Filtros que se aplican al cambiar ────────────────────────────────
     En un <select> de filtro, obligar a pulsar «Buscar» sobra. */
  document.querySelectorAll('[data-filtro-auto]').forEach(function (control) {
    control.addEventListener('change', function () {
      var formulario = control.closest('form');
      if (formulario) formulario.requestSubmit();
    });
  });

  /* ── Aviso antes de perder cambios ────────────────────────────────────
     En los formularios largos del CMS, salir sin guardar cuesta trabajo real. */
  document.querySelectorAll('form[data-avisar-cambios]').forEach(function (formulario) {
    var sucio = false;

    formulario.addEventListener('input', function () { sucio = true; });
    formulario.addEventListener('submit', function () { sucio = false; });

    window.addEventListener('beforeunload', function (evento) {
      if (!sucio) return;
      evento.preventDefault();
      evento.returnValue = '';
    });
  });

  /* ══════════════════════════════════════════════════════════════════════
     DATOS PARA BUSCADORES

     Contador de caracteres y vista previa en vivo. Sin esto hay que guardar,
     abrir Google y esperar días para descubrir que el título se cortaba.

     El contador avisa al pasarse, no lo impide: los límites de Google son
     aproximados y dependen del ancho real de las letras, así que un tope duro
     mentiría más de lo que ayuda.
     ══════════════════════════════════════════════════════════════════════ */
  (function datosDeBuscador() {
    var campos = document.querySelectorAll('[data-contar]');
    if (!campos.length) return;

    var ecoTitulo = document.querySelector('[data-eco-titulo]');
    var ecoDesc   = document.querySelector('[data-eco-desc]');

    Array.prototype.forEach.call(campos, function (campo) {
      var tope = parseInt(campo.getAttribute('data-contar'), 10) || 60;
      var marca = document.createElement('p');
      marca.className = 'contador';

      /* Se cuelga de la ayuda que ya hay debajo del campo, para no meter una
         línea más en un formulario que ya es largo. */
      var ayuda = campo.parentNode.querySelector('.campo__ayuda');
      if (ayuda) {
        ayuda.appendChild(document.createTextNode(' '));
        marca = document.createElement('span');
        marca.className = 'contador';
        ayuda.appendChild(marca);
      } else {
        campo.parentNode.appendChild(marca);
      }

      function pintar() {
        var largo = campo.value.length;
        marca.textContent = largo + '/' + tope;
        marca.className = 'contador' + (largo > tope ? ' contador--pasado' : '');

        if (ecoTitulo && campo.hasAttribute('data-vista-titulo')) {
          ecoTitulo.textContent = campo.value.trim() || campo.placeholder;
        }
        if (ecoDesc && campo.hasAttribute('data-vista-desc')) {
          ecoDesc.textContent = campo.value.trim()
            || 'Sin descripción propia: se usará la que trae escrita la página.';
        }
      }

      campo.addEventListener('input', pintar);
      pintar();
    });
  })();

  /* ══════════════════════════════════════════════════════════════════════
     ORDEN DE LAS SECCIONES

     Arrastrar para cambiar en qué orden salen en la página.

     Va ENCIMA de las flechas, no en su lugar. Las flechas son botones de
     envío normales: funcionan sin JavaScript y son la única forma de mover
     una sección con el teclado. Aquí sólo se les quita el envío para mover
     la fila en el sitio, y el orden se guarda al pulsar «Guardar el orden».

     El formulario manda `orden[]` con todas las claves en el orden en que
     quedaron las filas. El servidor lo cruza con las secciones que la página
     tiene de verdad, así que una clave inventada no llega a ninguna parte.
     ══════════════════════════════════════════════════════════════════════ */
  (function ordenDeSecciones() {
    var lista = document.querySelector('[data-lista-secciones]');
    var forma = document.querySelector('[data-ordenar-secciones]');
    if (!lista || !forma) return;

    var guardar = forma.querySelector('[data-guardar-orden]');
    var filas   = lista.querySelectorAll('[data-seccion]');
    if (!filas.length) return;

    Array.prototype.forEach.call(document.querySelectorAll('[data-con-js]'), function (el) { el.hidden = false; });

    var arrastrada = null;
    var tocado = false;

    function marcarTocado() {
      if (tocado) return;
      tocado = true;
      if (guardar) guardar.hidden = false;
    }

    /* Renumerar y volver a decidir qué flechas llevan a alguna parte. Sin
       esto, después de mover una fila los números dirían otra cosa que el
       orden real y la primera seguiría ofreciendo «subir». */
    function renumerar() {
      var actuales = lista.querySelectorAll('[data-seccion]');

      Array.prototype.forEach.call(actuales, function (fila, indice) {
        var numero = fila.querySelector('[data-numero]');
        if (numero) numero.textContent = String(indice + 1);

        var arriba = fila.querySelector('[data-subir-seccion]');
        var abajo  = fila.querySelector('[data-bajar-seccion]');
        if (arriba) arriba.disabled = indice === 0;
        if (abajo)  abajo.disabled  = indice === actuales.length - 1;
      });
    }

    Array.prototype.forEach.call(filas, function (fila) {
      fila.draggable = true;
      fila.classList.add('seccion-grupo--arrastrable');

      fila.addEventListener('dragstart', function (evento) {
        arrastrada = fila;
        fila.classList.add('seccion-grupo--arrastrando');
        /* Firefox no arranca el arrastre si no se escribe algo en el
           portapapeles del evento. */
        if (evento.dataTransfer) {
          evento.dataTransfer.effectAllowed = 'move';
          evento.dataTransfer.setData('text/plain', fila.getAttribute('data-seccion') || '');
        }
      });

      fila.addEventListener('dragend', function () {
        fila.classList.remove('seccion-grupo--arrastrando');
        Array.prototype.forEach.call(lista.querySelectorAll('[data-seccion]'), function (f) {
          f.classList.remove('seccion-grupo--destino');
        });
        arrastrada = null;
        renumerar();
      });

      fila.addEventListener('dragover', function (evento) {
        if (!arrastrada || arrastrada === fila) return;
        evento.preventDefault();
        if (evento.dataTransfer) evento.dataTransfer.dropEffect = 'move';
        fila.classList.add('seccion-grupo--destino');
      });

      fila.addEventListener('dragleave', function () {
        fila.classList.remove('seccion-grupo--destino');
      });

      fila.addEventListener('drop', function (evento) {
        if (!arrastrada || arrastrada === fila) return;
        evento.preventDefault();
        fila.classList.remove('seccion-grupo--destino');

        /* Soltar encima de la mitad de arriba la coloca antes; encima de la
           mitad de abajo, después. Es lo que espera la mano. */
        var caja = fila.getBoundingClientRect();
        var antes = (evento.clientY - caja.top) < caja.height / 2;

        fila.parentNode.insertBefore(arrastrada, antes ? fila : fila.nextSibling);
        marcarTocado();
        renumerar();
      });
    });

    /* Con JavaScript las flechas dejan de enviar el formulario: mueven la
       fila aquí mismo y el orden se guarda al final, de una vez. */
    lista.addEventListener('click', function (evento) {
      var boton = evento.target.closest('[data-subir-seccion], [data-bajar-seccion]');
      if (!boton || boton.disabled) return;

      evento.preventDefault();

      var fila = boton.closest('[data-seccion]');
      if (!fila) return;

      if (boton.hasAttribute('data-subir-seccion') && fila.previousElementSibling) {
        fila.parentNode.insertBefore(fila, fila.previousElementSibling);
      } else if (boton.hasAttribute('data-bajar-seccion') && fila.nextElementSibling) {
        fila.parentNode.insertBefore(fila.nextElementSibling, fila);
      } else {
        return;
      }

      marcarTocado();
      renumerar();
      boton.focus();
    });

    renumerar();
  })();

  /* ══════════════════════════════════════════════════════════════════════
     SELECTOR DE IMAGEN

     Tres cosas: enseñar la elegida, elegir pulsando una miniatura, y subir
     una nueva sin salir de la pantalla.

     La tercera es la que motivó todo esto. Antes había que ir a Imágenes,
     subir, volver y recargar —perdiendo por el camino lo que se llevara
     escrito—, porque la biblioteca se lee al pintar la página.

     La imagen viaja sola a /medios con fetch. El formulario de la sección NO
     se envía: si se enviara y la subida pasara de post_max_size, PHP dejaría
     un $_POST vacío y ese formulario reconstruye la sección con lo que
     recibe. Se perdería entera y en silencio.
     ══════════════════════════════════════════════════════════════════════ */
  (function selectorDeImagen() {
    var cajas = document.querySelectorAll('[data-selector-imagen]');
    if (!cajas.length) return;

    /* Con JavaScript, el enlace a la biblioteca sobra: en su sitio va el botón
       de subir, que no obliga a salir. */
    Array.prototype.forEach.call(document.querySelectorAll('[data-sin-js]'), function (el) { el.hidden = true; });
    Array.prototype.forEach.call(document.querySelectorAll('[data-con-js]'), function (el) { el.hidden = false; });

    function testigo() {
      var campo = document.querySelector('input[name="_csrf"]');
      return campo ? campo.value : '';
    }

    /* Pone la vista previa y las miniaturas de acuerdo con lo que diga el
       <select>, que es quien manda: es el que se envía al guardar. */
    function sincronizar(caja) {
      var select = caja.querySelector('[data-elegir-imagen]');
      if (!select) return;

      var opcion = select.options[select.selectedIndex];
      var src    = opcion ? opcion.getAttribute('data-src') : null;
      var img    = caja.querySelector('[data-vista-imagen]');
      var vacia  = caja.querySelector('[data-vista-vacia]');

      if (img) {
        if (src) {
          img.src = src;
          img.alt = opcion.getAttribute('data-alt') || '';
          img.hidden = false;
        } else {
          img.removeAttribute('src');
          img.hidden = true;
        }
      }

      if (vacia) vacia.hidden = !!src;

      /* El nombre, porque a 90 px dos fotos parecidas son la misma. */
      var rotulo = caja.querySelector('[data-vista-nombre]');
      if (rotulo) {
        rotulo.textContent = (opcion && opcion.getAttribute('data-nombre')) || 'Sin imagen';
      }

      // «Quitar» no tiene sentido si no hay nada puesto.
      var quitar = caja.querySelector('[data-quitar-imagen]');
      if (quitar) quitar.hidden = !src;

      /* El <select> se esconde con JavaScript: se maneja desde la ventana.
         Sigue siendo el campo que viaja en el formulario. */
      select.hidden = true;
    }

    /* Una imagen nueva entra en TODOS los selectores de la pantalla, no sólo
       en el que la pidió: una sección puede tener varios campos de imagen y
       sería absurdo tener que subirla otra vez para el de al lado. */
    function repartir(medio) {

      Array.prototype.forEach.call(document.querySelectorAll('[data-selector-imagen]'), function (caja) {
        var select = caja.querySelector('[data-elegir-imagen]');
        if (select && !select.querySelector('option[value="' + medio.id + '"]')) {
          var opcion = document.createElement('option');
          opcion.value = String(medio.id);
          opcion.setAttribute('data-src', medio.url);
          opcion.setAttribute('data-alt', medio.alt || '');
          opcion.setAttribute('data-nombre', medio.nombre_archivo);
          opcion.textContent = medio.nombre_archivo + (medio.ancho ? ' (' + medio.ancho + '×' + medio.alto + ')' : '');
          select.appendChild(opcion);
        }
      });

      /* Y en la ventana, que es UNA para toda la pantalla: por eso esto va
         fuera del bucle. Al principio, que es donde se busca lo recién
         subido. */
      var rejilla = document.querySelector('[data-elegir-rejilla]');

      if (rejilla && !rejilla.querySelector('[data-elegir="' + medio.id + '"]')) {
        var li = document.createElement('li');
        li.setAttribute('data-nombre', (medio.nombre_archivo + ' ' + (medio.alt || '')).toLowerCase());

        var boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'elegir-img__pieza';
        boton.title = medio.nombre_archivo;
        boton.setAttribute('data-elegir', String(medio.id));
        boton.setAttribute('data-src', medio.url);
        boton.setAttribute('data-alt', medio.alt || '');
        boton.setAttribute('data-nombre', medio.nombre_archivo);

        var img = document.createElement('img');
        img.src = medio.url; img.alt = ''; img.loading = 'lazy';
        img.width = 120; img.height = 120;

        var pie = document.createElement('span');
        pie.className = 'elegir-img__pie';
        pie.textContent = medio.nombre_archivo;

        boton.appendChild(img); boton.appendChild(pie); li.appendChild(boton);
        rejilla.insertBefore(li, rejilla.firstChild);
      }
    }

    /* El panel de subida. Se construye aquí y no en el HTML porque son campos
       sueltos dentro del formulario de la sección: cuantos menos haya
       rondando sin usarse, menos posibilidades de que alguno se envíe por
       error al guardar. Ninguno lleva `name`, justamente por eso. */
    function abrirSubida(caja) {
      var zona = caja.querySelector('[data-subida]');
      if (!zona) return;

      if (!zona.hidden) { zona.hidden = true; return; }

      zona.innerHTML =
        '<div class="subida__campos">'
      +   '<label class="subida__campo"><span>Archivo</span>'
      +     '<input type="file" accept="image/*" data-subida-archivo></label>'
      +   '<label class="subida__campo"><span>Qué se ve en la imagen</span>'
      +     '<input type="text" maxlength="255" placeholder="Voluntarios recibiendo a los peregrinos" data-subida-alt></label>'
      +   '<label class="subida__decorativa">'
      +     '<input type="checkbox" data-subida-decorativa> Es decorativa, no aporta información'
      +   '</label>'
      + '</div>'
      + '<div class="subida__pie">'
      +   '<button type="button" class="btn btn--primario" data-subida-enviar>Subir y elegir</button>'
      +   '<span class="subida__estado" role="status" data-subida-estado></span>'
      + '</div>';

      zona.hidden = false;
      var archivo = zona.querySelector('[data-subida-archivo]');
      if (archivo) archivo.focus();
    }

    function subir(caja) {
      var zona    = caja.querySelector('[data-subida]');
      var archivo = zona.querySelector('[data-subida-archivo]');
      var alt     = zona.querySelector('[data-subida-alt]');
      var deco    = zona.querySelector('[data-subida-decorativa]');
      var estado  = zona.querySelector('[data-subida-estado]');
      var enviar  = zona.querySelector('[data-subida-enviar]');

      if (!archivo || !archivo.files || !archivo.files.length) {
        estado.textContent = 'Elige el archivo primero.';
        estado.className = 'subida__estado subida__estado--mal';
        return;
      }

      if (!alt.value.trim() && !deco.checked) {
        estado.textContent = 'Escribe qué se ve, o márcala como decorativa.';
        estado.className = 'subida__estado subida__estado--mal';
        alt.focus();
        return;
      }

      var datos = new FormData();
      datos.append('_csrf', testigo());
      datos.append('imagen', archivo.files[0]);
      datos.append('alt', alt.value.trim());
      if (deco.checked) datos.append('decorativa', '1');

      enviar.disabled = true;
      estado.className = 'subida__estado';
      estado.textContent = 'Subiendo…';

      fetch(caja.getAttribute('data-medios-url'), {
        method: 'POST',
        body: datos,
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      }).then(function (respuesta) {
        return respuesta.json().then(function (cuerpo) {
          return { ok: respuesta.ok, cuerpo: cuerpo };
        });
      }).then(function (r) {
        if (!r.ok || !r.cuerpo.ok) {
          throw new Error(r.cuerpo.mensaje || 'No se pudo subir la imagen.');
        }

        repartir(r.cuerpo.medio);

        var select = caja.querySelector('[data-elegir-imagen]');
        if (select) select.value = String(r.cuerpo.medio.id);

        Array.prototype.forEach.call(document.querySelectorAll('[data-selector-imagen]'), sincronizar);

        zona.hidden = true;
        zona.innerHTML = '';
      }).catch(function (error) {
        enviar.disabled = false;
        estado.className = 'subida__estado subida__estado--mal';
        /* Un fallo de red no trae mensaje del servidor: se distingue del
           rechazo con motivo, que sí lo trae y es el que hay que leer. */
        estado.textContent = error.message || 'No se pudo subir la imagen.';
      });
    }


    /* ══════════════════════════════════════════════════════════════════════
       LA VENTANA PARA ELEGIR IMAGEN
       ----------------------------------------------------------------------
       Una sola para toda la pantalla, compartida por todos los campos.

       Antes la rejilla iba dentro de cada campo. En Páginas → Inicio →
       Itinerario eran 14 copias de 96 miniaturas: 1 344 imágenes y 998 KB de
       HTML para editar una jornada, y creciendo con cada foto subida.

       Quién pidió abrirla se guarda aquí, en `pidiendo`: al elegir, hay que
       saber en qué <select> escribir.
       ══════════════════════════════════════════════════════════════════════ */
    var ventana  = document.querySelector('[data-ventana-biblioteca]');
    var pidiendo = null;

    function abrirBiblioteca(caja) {
      if (!ventana || typeof ventana.showModal !== 'function') {
        /* Sin <dialog> —navegador viejo— el <select> sigue ahí y sirve. Se
           destapa para que no se quede sin forma de elegir. */
        var sel = caja.querySelector('[data-elegir-imagen]');
        if (sel) { sel.hidden = false; sel.focus(); }
        return;
      }

      pidiendo = caja;

      // Se marca la que ya tuviera, para que se vea de dónde se parte.
      var actual = caja.querySelector('[data-elegir-imagen]');
      var valor  = actual ? actual.value : '';

      Array.prototype.forEach.call(ventana.querySelectorAll('[data-elegir]'), function (b) {
        var suya = b.getAttribute('data-elegir') === valor;
        b.classList.toggle('es-elegida', suya);
        b.setAttribute('aria-pressed', suya ? 'true' : 'false');
      });

      ventana.showModal();
    }

    function elegirEnLaVentana(id, src, alt, nombre) {
      if (!pidiendo) return;

      var select = pidiendo.querySelector('[data-elegir-imagen]');

      if (select) {
        /* Si la imagen es nueva y todavía no está en el <select> —recién
           subida desde otro campo— se añade: el <select> es lo que se envía,
           y un valor que no exista como <option> no viaja. */
        var op = id ? select.querySelector('option[value="' + id + '"]') : null;

        if (id && !op) {
          op = document.createElement('option');
          op.value = String(id);
          op.textContent = nombre || id;
          select.appendChild(op);
        }

        /* Y se le cuelga la ruta, el alt y el nombre. Siempre, no sólo si la
           opción es nueva: el HTML las trae desnudas a propósito —poner los
           data-* en las 96 eran 341 KB en esta pantalla— y es la ventana la
           que se los da a la que se elige, que es la única que sincronizar()
           necesita leer para pintar la vista previa. */
        if (op) {
          op.setAttribute('data-src', src || '');
          op.setAttribute('data-alt', alt || '');
          op.setAttribute('data-nombre', nombre || '');
        }

        select.value = id ? String(id) : '';
        sincronizar(pidiendo);
      }

      if (ventana && ventana.open) ventana.close();
    }

    if (ventana) {
      ventana.addEventListener('click', function (ev) {
        if (ev.target === ventana || ev.target.closest('[data-cerrar]')) {
          ventana.close();
          return;
        }

        var pieza = ev.target.closest('[data-elegir]');

        if (pieza) {
          elegirEnLaVentana(
            pieza.getAttribute('data-elegir'),
            pieza.getAttribute('data-src'),
            pieza.getAttribute('data-alt'),
            pieza.getAttribute('data-nombre')
          );

          return;
        }

        if (ev.target.closest('[data-elegir-ninguna]')) {
          elegirEnLaVentana('', '', '', '');
        }
      });

      ventana.addEventListener('close', function () {
        if (pidiendo) {
          var b = pidiendo.querySelector('[data-abrir-biblioteca]');
          if (b) b.focus();
        }

        pidiendo = null;
      });

      /* El buscador filtra lo que ya está pintado, sin volver al servidor:
         con menos de unos cientos de fotos es instantáneo y evita una
         petición por cada letra. */
      var buscar = ventana.querySelector('[data-elegir-buscar]');
      var vacio  = ventana.querySelector('[data-elegir-sinresultados]');

      if (buscar) {
        buscar.addEventListener('input', function () {
          var q = buscar.value.trim().toLowerCase();
          var vistas = 0;

          Array.prototype.forEach.call(ventana.querySelectorAll('[data-nombre]'), function (li) {
            if (li.tagName !== 'LI') return;

            var cabe = q === '' || (li.getAttribute('data-nombre') || '').indexOf(q) !== -1;
            li.hidden = !cabe;
            if (cabe) vistas++;
          });

          if (vacio) vacio.hidden = vistas > 0;
        });
      }
    }

    /* Todo por delegación: los bloques del CMS se añaden después de cargar la
       página y un escuchador por elemento no los vería. */
    document.addEventListener('click', function (evento) {
      var boton = evento.target.closest('button');
      if (!boton) return;

      var caja = boton.closest('[data-selector-imagen]');
      if (!caja) return;

      if (boton.hasAttribute('data-abrir-biblioteca')) {
        abrirBiblioteca(caja);
      } else if (boton.hasAttribute('data-quitar-imagen')) {
        var sel = caja.querySelector('[data-elegir-imagen]');
        if (sel) { sel.value = ''; sincronizar(caja); }
      } else if (boton.hasAttribute('data-abrir-subida')) {
        abrirSubida(caja);
      } else if (boton.hasAttribute('data-subida-enviar')) {
        subir(caja);
      }
    });

    document.addEventListener('change', function (evento) {
      var select = evento.target;
      if (!(select instanceof HTMLSelectElement) || !select.hasAttribute('data-elegir-imagen')) return;

      var caja = select.closest('[data-selector-imagen]');
      if (caja) sincronizar(caja);
    });

    Array.prototype.forEach.call(cajas, sincronizar);
  })();
})();
