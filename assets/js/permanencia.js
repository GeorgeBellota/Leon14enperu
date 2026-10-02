/* ============================================================================
   CUÁNTO SE ESTUVO MIRANDO ESTA PÁGINA
   ----------------------------------------------------------------------------
   Al salir, manda dos datos: la ruta y los segundos. Nada más.

   ── Lo que NO hace, y por qué importa ─────────────────────────────────────

   No pone cookies. No guarda nada en el navegador. No genera identificador,
   ni de sesión ni de visitante. Dos visitas de la misma persona llegan
   exactamente igual que dos visitas de personas distintas: no hay forma de
   unirlas, ni aquí ni en el servidor.

   Por eso esto no es seguimiento ni perfilado: lo que se obtiene es «la
   página X se leyó 47 segundos», sumado con el resto. Quién la leyó no se
   sabe, y no se puede averiguar después.

   ── Se cuenta el tiempo VISIBLE ───────────────────────────────────────────

   Una pestaña olvidada en segundo plano tres horas no es alguien leyendo tres
   horas. El reloj se para cuando la pestaña deja de verse y se reanuda cuando
   vuelve.

   ── Por qué se avisa al ocultarse y no sólo al cerrar ─────────────────────

   Porque en el móvil muchas pestañas no llegan a cerrarse: el sistema las
   manda a segundo plano y las mata sin avisar. Pasar a oculto es la última
   señal fiable que hay.

   Pero eso trae un problema: quien cambia de pestaña a los cinco segundos y
   vuelve a leer cinco minutos habría quedado apuntado como cinco segundos. Lo
   que sigue después del primer aviso se manda como CONTINUACIÓN —una marca,
   `c`—: el servidor suma los segundos pero no cuenta otra visita. Así el
   total es verdad y el recuento también, sin que haga falta identificar a
   nadie ni unir un envío con el anterior.

   ── Por qué «pagehide» y no «unload» ──────────────────────────────────────

   Porque «unload» impide que el navegador guarde la página en su caché de ir
   y volver: con él puesto, pulsar «atrás» recarga la página entera en lugar
   de recuperarla al instante. Se paga en velocidad para todos a cambio de
   nada.

   ── Por qué sendBeacon ────────────────────────────────────────────────────

   Porque es el único envío que el navegador se compromete a terminar aunque
   la página ya se esté cerrando. Un fetch() normal se cancela a medias, y la
   medida se perdería justo en la visita más corta, que es la que más dice.
   ========================================================================== */

(function () {
  'use strict';

  if (!navigator.sendBeacon) {
    return;
  }

  var pendiente = 0;       // milisegundos visibles aún sin mandar
  var desde = Date.now();  // cuándo empezó el tramo visible en curso
  var yaMande = false;     // ¿el primer aviso ya salió?

  function parar() {
    if (desde) {
      pendiente += Date.now() - desde;
      desde = 0;
    }
  }

  function seguir() {
    if (!desde) {
      desde = Date.now();
    }
  }

  function avisar() {
    parar();

    var segundos = Math.round(pendiente / 1000);

    /* Menos de dos segundos no es una lectura, es un rebote o una
       redirección. Más de media hora es una pestaña olvidada que el reloj
       visible no supo descontar. */
    if (segundos < 2 || segundos > 1800) {
      return;
    }

    // Lo mandado deja de estar pendiente, aunque el envío se pierda: contarlo
    // dos veces falsearía el total más que perderlo una.
    pendiente = 0;

    var dato = { u: location.pathname, s: segundos };

    if (yaMande) {
      dato.c = 1; // continuación: suma el tiempo, no cuentes otra visita
    }

    yaMande = true;

    try {
      /* La dirección va RELATIVA: el sitio vive en la raíz del dominio en
         producción y dentro de una carpeta en desarrollo, y así vale en los
         dos sin tener que pasarle la base desde PHP. */
      navigator.sendBeacon('_m', JSON.stringify(dato));
    } catch (e) {
      // Si no se puede medir, no se mide. No es motivo para romper nada.
    }
  }

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      avisar();
    } else {
      seguir();
    }
  });

  window.addEventListener('pagehide', avisar);
})();
