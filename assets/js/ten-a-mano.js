/* ============================================================================
   «TEN A MANO» · el cajón lateral del formulario de voluntariado
   ----------------------------------------------------------------------------
   En pantallas anchas el recuadro se pega al lateral derecho y se puede
   plegar. En estrechas no se toca: ahí va debajo del formulario, como lo
   dibuja el editable, y plegarlo no aportaría nada.

   El botón se pinta desde aquí —viene con «hidden» en el HTML— porque sin
   JavaScript no hay nada que plegar: sería un botón que no hace nada. Sin
   este archivo, el recuadro se queda donde estaba y siempre visible, que es
   un estado perfectamente válido.

   Lo que se recuerda es la decisión de quien lo cierra, no un ajuste del
   sitio: vive en el navegador de cada persona y no viaja a ninguna parte.
   ========================================================================== */

(function () {
  'use strict';

  var caja = document.querySelector('[data-mano]');
  if (!caja) return;

  var boton = caja.querySelector('[data-mano-plegar]');
  if (!boton) return;

  var LLAVE = 'l14-ten-a-mano';
  /* El mismo ancho al que la hoja de estilos convierte el recuadro en cajón.
     Si cambia allí, tiene que cambiar aquí: por debajo de eso el botón sobra,
     porque no hay nada plegado que desplegar. */
  var ANCHO = window.matchMedia('(min-width: 1024px)');

  /* localStorage falla en navegación privada de algunos navegadores y cuando
     el visitante bloquea el almacenamiento. Que no se recuerde la preferencia
     es un incordio menor; que reviente la página, no. */
  function recordado() {
    try { return localStorage.getItem(LLAVE); } catch (e) { return null; }
  }

  function recordar(valor) {
    try { localStorage.setItem(LLAVE, valor); } catch (e) { /* sin memoria */ }
  }

  function pintar(plegado) {
    caja.classList.toggle('vol-mano--plegado', plegado);
    boton.setAttribute('aria-expanded', plegado ? 'false' : 'true');

    /* Plegado, el botón es lo único que asoma: la hoja de estilos retira el
       titular porque quedaba cortado contra el borde. Así que el rótulo del
       botón pasa a ser el nombre del recuadro, que es lo que hace falta leer
       desde fuera para saber qué hay ahí dentro. */
    var txt = boton.querySelector('.vol-mano__plegar-txt');
    if (txt) { txt.textContent = plegado ? 'Ten a mano' : 'Ocultar'; }

    boton.setAttribute('aria-label', (plegado ? 'Mostrar' : 'Ocultar') + ' la lista «Ten a mano»');
  }

  function aplicar() {
    if (!ANCHO.matches) {
      /* En estrecho vuelve a ser lo que era: sin botón y sin plegar. */
      boton.hidden = true;
      caja.classList.remove('vol-mano--plegado');
      return;
    }

    boton.hidden = false;
    pintar(recordado() === 'plegado');
  }

  boton.addEventListener('click', function () {
    var plegado = !caja.classList.contains('vol-mano--plegado');

    pintar(plegado);
    recordar(plegado ? 'plegado' : 'abierto');
  });

  /* addEventListener en un MediaQueryList no existe en Safari antiguo, que
     todavía usa addListener. Se prueban los dos para no dejar fuera a nadie
     al girar el teléfono o redimensionar la ventana. */
  if (typeof ANCHO.addEventListener === 'function') {
    ANCHO.addEventListener('change', aplicar);
  } else if (typeof ANCHO.addListener === 'function') {
    ANCHO.addListener(aplicar);
  }

  aplicar();
})();
