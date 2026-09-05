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
     EDITOR DE BLOQUES DEL CMS
     Añadir, quitar y reordenar los elementos repetibles de una sección.

     Los índices de los campos (bloques[0], bloques[1]…) se renumeran después
     de cada operación. Sin eso, quitar el bloque del medio dejaría huecos
     (0, 2, 3) y el orden dependería del hueco en lugar de la posición.
     ══════════════════════════════════════════════════════════════════════ */
  (function editorDeBloques() {
    var lista = document.querySelector('[data-bloques]');
    if (!lista) return;

    var molde = document.querySelector('[data-molde-bloque]');
    var anadir = document.querySelector('[data-anadir-bloque]');
    var maximo = parseInt(lista.getAttribute('data-maximo'), 10) || 20;

    function renumerar() {
      var bloques = lista.querySelectorAll('[data-bloque]');

      Array.prototype.forEach.call(bloques, function (bloque, indice) {
        var numero = bloque.querySelector('[data-bloque-num]');
        if (numero) numero.textContent = String(indice + 1);

        Array.prototype.forEach.call(bloque.querySelectorAll('[name]'), function (campo) {
          campo.name = campo.name.replace(/^bloques\[[^\]]*\]/, 'bloques[' + indice + ']');
        });

        /* Las flechas de los extremos no llevan a ninguna parte. */
        var subir = bloque.querySelector('[data-subir]');
        var bajar = bloque.querySelector('[data-bajar]');
        if (subir) subir.disabled = indice === 0;
        if (bajar) bajar.disabled = indice === bloques.length - 1;
      });

      if (anadir) anadir.disabled = bloques.length >= maximo;
    }

    if (anadir && molde) {
      anadir.addEventListener('click', function () {
        if (lista.querySelectorAll('[data-bloque]').length >= maximo) return;

        var nuevo = molde.content.cloneNode(true);
        lista.appendChild(nuevo);
        renumerar();

        var ultimo = lista.querySelector('[data-bloque]:last-child input[type="text"]');
        if (ultimo) ultimo.focus();
      });
    }

    lista.addEventListener('click', function (evento) {
      var boton = evento.target.closest('button');
      if (!boton) return;

      var bloque = boton.closest('[data-bloque]');
      if (!bloque) return;

      if (boton.hasAttribute('data-quitar')) {
        var nombre = lista.getAttribute('data-nombre') || 'bloque';
        if (!window.confirm('¿Quitar este ' + nombre.toLowerCase() + '? El cambio se aplica al guardar.')) return;
        bloque.remove();
      } else if (boton.hasAttribute('data-subir') && bloque.previousElementSibling) {
        bloque.parentNode.insertBefore(bloque, bloque.previousElementSibling);
      } else if (boton.hasAttribute('data-bajar') && bloque.nextElementSibling) {
        bloque.parentNode.insertBefore(bloque.nextElementSibling, bloque);
      } else {
        return;
      }

      renumerar();
    });

    renumerar();
  })();

  /* ── Vista previa del selector de imagen ──────────────────────────────
     El <select> ya funciona solo: esto sólo enseña la foto elegida para no
     tener que guardar y volver para comprobar que es la que se quería.
     Se escucha en el documento porque los bloques se añaden después. */
  document.addEventListener('change', function (evento) {
    var select = evento.target;
    if (!(select instanceof HTMLSelectElement) || !select.hasAttribute('data-elegir-imagen')) return;

    var caja = select.closest('[data-selector-imagen]');
    if (!caja) return;

    var img   = caja.querySelector('[data-vista-imagen]');
    var vacia = caja.querySelector('[data-vista-vacia]');
    var opcion = select.options[select.selectedIndex];
    var src    = opcion ? opcion.getAttribute('data-src') : null;

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
  });
})();
