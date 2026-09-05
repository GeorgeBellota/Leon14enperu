/* ============================================================================
   form.js — formularios del sitio (§12.4)

   Dos piezas:
     1. Aviso por correo  → bloque de la home y de agenda.html
     2. Inscripción de voluntariado (Fase 01) → voluntariado.html, en tres pasos

   ─────────────────────────────────────────────────────────────────────────
   EL ÚNICO PUNTO QUE HAY QUE TOCAR PARA CONECTAR EL BACKEND son las dos
   funciones enviarInscripcion() y enviarAviso() del final de este archivo.
   Hoy resuelven en local y muestran la pantalla de confirmación. Mañana se
   sustituye su cuerpo por un fetch() al endpoint del cliente. Nada más.
   ─────────────────────────────────────────────────────────────────────────

   Protección de datos: el formulario web NO pide nada de la Fase 02. Los
   antecedentes penales, la carta de recomendación y la evaluación psicológica
   son datos sensibles y se entregan por un canal controlado por la
   organización, jamás subidos a un formulario público.
   ========================================================================== */
window.L14 = window.L14 || {};

/* Huella de esta versión del archivo.
   La página comprueba que el JavaScript que le ha llegado es el que ella
   espera. Si el navegador o una caché intermedia sirven una copia vieja, el
   número no coincide y la página se recarga sola pidiendo la buena.
   La escribe el servidor al desplegar; aquí sólo hay un valor de partida. */
window.L14.version = "b7375ead";

(function (L14) {
  'use strict';

  var CLAVE_BORRADOR = 'l14-inscripcion-borrador';
  var TIEMPO_MINIMO = 3000;  /* antispam: menos de 3 s es un robot */

  /* ══════════════════════════════════════════════════════════════════════
     DECISIÓN PENDIENTE DE VALIDAR CON EL CLIENTE
     Los documentos no dicen si se admiten menores de edad. No se decide aquí.
       · false → no se comprueba la edad (comportamiento actual)
       · true  → se exige mayoría de edad contra la fecha de nacimiento
     Si la respuesta es que SÍ se admiten menores, además hace falta
     consentimiento de tutor y protocolo de salvaguarda: eso es trabajo de
     producto, no una línea de configuración.
     ══════════════════════════════════════════════════════════════════════ */
  var EXIGIR_MAYORIA_DE_EDAD = false;

  /* ── Utilidades de validación ────────────────────────────────────────── */
  /* ── Los avisos, cortos ──────────────────────────────────────────────────
     Eran frases completas —«El DNI tiene ocho dígitos, sin puntos ni
     guiones»— y en una columna de 168 píxeles ocupaban tres renglones que
     caían encima de la etiqueta del campo siguiente: se leían los dos textos
     mezclados y no se entendía ninguno.

     Dicho en una línea se entiende igual y cabe donde tiene que caber. La
     explicación larga sigue estando donde corresponde: en el texto de ayuda
     que hay bajo cada campo antes de escribir nada. */
  var reglas = {
    requerido: function (v) { return v.trim() !== '' || 'Este dato es obligatorio.'; },
    correo: function (v) {
      return /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(v.trim()) || 'Ese correo no es válido.';
    },
    dni: function (v) {
      return /^\d{8}$/.test(v.trim()) || 'Deben ser 8 dígitos.';
    },
    telefono: function (v) {
      return /^\d{6,15}$/.test(v.replace(/[\s()+-]/g, '')) || 'Sólo números, sin espacios.';
    },
    fecha: function (v) {
      if (!v) return 'Este dato es obligatorio.';
      var d = new Date(v + 'T00:00:00');
      if (isNaN(d.getTime())) return 'Esa fecha no es válida.';
      if (d.getFullYear() < 1900 || d > new Date()) return 'Revisa la fecha de nacimiento.';
      if (EXIGIR_MAYORIA_DE_EDAD) {
        var hoy = new Date();
        var edad = hoy.getFullYear() - d.getFullYear();
        var m = hoy.getMonth() - d.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < d.getDate())) edad--;
        if (edad < 18) return 'La inscripción está abierta a mayores de 18 años.';
      }
      return true;
    }
  };

  function validarCampo(input) {
    var lista = (input.getAttribute('data-valida') || '').split(' ').filter(Boolean);
    if (input.required && lista.indexOf('requerido') === -1) lista.unshift('requerido');

    for (var i = 0; i < lista.length; i++) {
      var regla = reglas[lista[i]];
      if (!regla) continue;
      if (!input.value.trim() && lista[i] !== 'requerido') continue; /* vacío lo juzga «requerido» */
      var r = regla(input.value);
      if (r !== true) return r;
    }
    if (input.type === 'checkbox' && input.required && !input.checked) {
      return 'Necesitamos tu consentimiento para continuar.';
    }
    return true;
  }

  /* El hueco del mensaje se reserva AL ARRANCAR, no al fallar.
     Si el <p> del error nace en el momento del error, la página se desplaza
     justo cuando el campo pierde el foco: el usuario escribe mal el correo,
     pulsa el botón de enviar, el aviso empuja el botón hacia abajo y el clic
     cae al vacío. Reservando el hueco desde el principio, el error aparece y
     desaparece sin mover ni un píxel. */
  function prepararMensajes(zona) {
    Array.prototype.forEach.call(zona.querySelectorAll('[data-valida], [required]'), function (input) {
      if (!input.id || input.type === 'hidden' || input.classList.contains('trampa')) return;
      var id = input.id + '-error';
      if (document.getElementById(id)) return;

      var campo = input.closest('.campo') || input.closest('.casilla') || input.parentElement;
      var caja = document.createElement('p');
      caja.id = id;
      caja.className = 'campo__error';
      caja.setAttribute('role', 'alert');
      campo.appendChild(caja);

      var descrito = (input.getAttribute('aria-describedby') || '').split(' ').filter(Boolean);
      if (descrito.indexOf(id) === -1) descrito.push(id);
      input.setAttribute('aria-describedby', descrito.join(' '));
    });
  }

  function pintarError(input, mensaje) {
    var campo = input.closest('.campo') || input.closest('.casilla') || input.parentElement;
    var caja = input.id ? document.getElementById(input.id + '-error') : null;

    if (mensaje === true) {
      if (campo) { campo.classList.remove('campo--error'); campo.classList.remove('campo--aviso-visible'); }
      input.removeAttribute('aria-invalid');
      if (caja) caja.textContent = '';
      return true;
    }

    if (campo) campo.classList.add('campo--error');
    input.setAttribute('aria-invalid', 'true');

    if (caja) {
      caja.textContent = mensaje;

      /* El campo avisa si su mensaje se va a ver, para que el CSS le reserve
         sitio debajo. Sin esa reserva el aviso cae encima de la etiqueta del
         campo siguiente y se leen los dos a la vez, mezclados.
         Se marca el campo y no la caja porque el hueco lo tiene que abrir
         quien ocupa espacio en la página, y la caja va colocada en absoluto:
         no empuja nada. */
      var generico = mensaje === reglas.requerido('');
      if (campo) campo.classList.toggle('campo--aviso-visible', !generico);

      /* Se distingue el aviso genérico —«este dato es obligatorio»— del que
         explica algo concreto —«el DNI tiene ocho dígitos»—.

         El genérico se repite en cada campo vacío, y con siete campos vacíos
         son siete veces la misma frase, apretadas unas contra otras. No añade
         nada: el campo ya está marcado en rojo y el aviso de arriba dice
         cuántos faltan y por cuál empezar. En el formulario de la derecha se
         oculta a la vista, pero sigue ahí para quien navega con lector de
         pantalla, que no ve ni el rojo ni el recuadro.

         Los mensajes que sí explican algo se muestran siempre: son uno o dos
         a la vez y son los que enseñan a corregir. */
      caja.classList.toggle('campo__error--generico', generico);
    }

    return false;
  }

  function validarZona(zona) {
    var campos = zona.querySelectorAll('[data-valida], [required]');
    var fallos = [];
    Array.prototype.forEach.call(campos, function (input) {
      /* ── Dos exclusiones y una excepción ──────────────────────────────
         · [data-ignorar] es el campo que flatpickr crea para enseñar la
           fecha con formato bonito. Es visible y hereda el `required`, pero
           no tiene ni id ni name: no es un campo del formulario, es un
           escaparate del de al lado. Se colaba en la validación y aparecía
           en la lista de errores como una viñeta sin nombre —«: Este dato es
           obligatorio»—, que no le dice nada a nadie.
         · Los ocultos no se validan… salvo el de la fecha, que flatpickr
           esconde al arrancar. Ese SÍ es el campo de verdad, el que guarda el
           valor y el que se envía; si no se comprueba, un formulario sin
           fecha pasa la revisión del navegador y sólo lo para el servidor. */
      if (input.hasAttribute('data-ignorar') || input.classList.contains('trampa')) return;
      if (input.type === 'hidden' && !input.hasAttribute('data-calendario')) return;
      var r = validarCampo(input);
      if (!pintarError(input, r)) {
        var etiqueta = zona.querySelector('label[for="' + input.id + '"]');
        fallos.push({ id: input.id, texto: etiqueta ? etiqueta.textContent.replace('*', '').trim() : input.name, mensaje: r });
      }
    });
    return fallos;
  }

  function resumirErrores(form, fallos) {
    var resumen = form.querySelector('[data-resumen-errores]');
    if (!resumen) return;
    if (!fallos.length) { resumen.hidden = true; resumen.innerHTML = ''; return; }

    /* Una línea, no una lista.
       La lista repetía debajo lo que cada campo ya dice junto a sí mismo, y
       esos ~190 píxeles de más empujaban el botón fuera de la parte visible
       del formulario. Aquí basta con cuántos faltan y con poder saltar al
       primero; el qué corregir está donde hay que corregirlo. */
    /* ── Todo lo que hay que corregir se dice AQUÍ ARRIBA ──────────────────
       Los avisos vivían debajo de cada campo y no encajaban: en columnas
       estrechas se partían en varios renglones, caían sobre la etiqueta de
       abajo y descuadraban el bloque.

       Ahora el sitio del aviso es este recuadro, que está pegado arriba y no
       se mueve. Cuando falla un solo campo se dice qué le pasa —«DNI: deben
       ser 8 dígitos»—; cuando fallan varios, cuántos son y por dónde empezar,
       con un enlace que lleva hasta él. Debajo del campo sólo queda el borde
       rojo, que señala sin ocupar sitio. */
    var primero = fallos[0];
    var texto;

    if (fallos.length === 1) {
      /* Con un único fallo cabe el motivo entero, que es lo que de verdad
         ayuda a arreglarlo. El genérico no aporta nada —«es obligatorio» ya
         lo dice el asterisco— así que en ese caso se nombra el campo. */
      texto = primero.mensaje === reglas.requerido('')
        ? 'Falta un dato: <a href="#' + primero.id + '">' + primero.texto + '</a>'
        : '<a href="#' + primero.id + '">' + primero.texto + '</a>: ' + primero.mensaje;
    } else {
      texto = 'Faltan <strong>' + fallos.length + ' datos</strong>. Empieza por '
            + '<a href="#' + primero.id + '">' + primero.texto + '</a>';
    }

    resumen.innerHTML = '<span>' + texto + '</span>';
    resumen.classList.remove('resumen-errores--aviso');
    resumen.hidden = false;
    resumen.setAttribute('tabindex', '-1');
    resumen.focus();
  }

  /* ══════════════════════════════════════════════════════════════════════
     1. AVISO POR CORREO
     ══════════════════════════════════════════════════════════════════════ */
  function avisos() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-form="aviso"]'), function (form) {
      var nacido = Date.now();
      var salida = form.querySelector('[data-mensaje]');
      prepararMensajes(form);

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var fallos = validarZona(form);
        if (fallos.length) {
          if (salida) {
            salida.className = 'mensaje-form mensaje-form--error';
            salida.textContent = fallos[0].mensaje;
          }
          return;
        }
        var trampa = form.querySelector('.trampa input');
        if ((trampa && trampa.value) || Date.now() - nacido < TIEMPO_MINIMO) {
          if (salida) {
            salida.className = 'mensaje-form mensaje-form--error';
            salida.textContent = 'No hemos podido registrar el envío. Inténtalo de nuevo en unos segundos.';
          }
          return;
        }

        var datos = {
          correo: (form.querySelector('input[type="email"]') || {}).value || '',
          origen: form.getAttribute('data-origen') || 'sitio',
          consentimiento: true,
          momento: new Date().toISOString()
        };

        L14.enviarAviso(datos).then(function () {
          form.reset();
          if (salida) {
            salida.className = 'mensaje-form mensaje-form--ok';
            salida.textContent = 'Listo. Te escribiremos cuando haya novedades.';
          }
        }).catch(function () {
          if (salida) {
            salida.className = 'mensaje-form mensaje-form--error';
            salida.textContent = 'No hemos podido registrar tu correo. Inténtalo de nuevo en unos minutos.';
          }
        });
      });

      /* Validación al salir del campo, nunca al teclear */
      Array.prototype.forEach.call(form.querySelectorAll('input'), function (input) {
        input.addEventListener('blur', function () { pintarError(input, validarCampo(input)); });
      });
    });
  }

  /* ══════════════════════════════════════════════════════════════════════
     2. INSCRIPCIÓN DE VOLUNTARIADO — tres pasos
     Sin JS el formulario se envía entero de una vez: los tres grupos de
     campos están en el DOM y solo este script los oculta.
     ══════════════════════════════════════════════════════════════════════ */
  function inscripcion() {
    var form = document.querySelector('[data-form="inscripcion"]');
    if (!form) return;

    var nacido = Date.now();
    var pasos = Array.prototype.slice.call(form.querySelectorAll('[data-paso]'));
    var barra = form.querySelector('[data-progreso-barra]');
    var texto = form.querySelector('[data-progreso-texto]');
    var atras = form.querySelector('[data-atras]');
    var adelante = form.querySelector('[data-adelante]');
    var enviar = form.querySelector('[data-enviar]');
    var confirmacion = document.querySelector('[data-confirmacion]');
    var actual = 0;

    if (!pasos.length) return;
    prepararMensajes(form);

    /* ══════════════════════════════════════════════════════════════════════
       DOS PASOS

       Fueron cuatro, y luego uno solo. Dos es el punto medio que funciona:

         · Cuatro obligaban a navegar tres veces. Para una convocatoria
           nacional, con gente de muy distinta soltura con una pantalla, cada
           salto era un sitio donde perderse.
         · Uno solo eliminaba la navegación pero dejaba un formulario de unos
           2400 píxeles, con barra de desplazamiento permanente.

       Con dos, cada mitad cabe en pantalla sin desplazarse y sólo hay un
       «Continuar» que entender en todo el proceso.

       Las cuatro secciones siguen ahí como encabezados —Inscripción,
       Ubicación, Contacto, Tu servicio— porque agrupar ayuda a leer catorce
       campos. Lo que se redujo son los saltos, no la estructura.
       ══════════════════════════════════════════════════════════════════════ */
    function mostrar(indice, mover) {
      actual = Math.max(0, Math.min(pasos.length - 1, indice));
      pasos.forEach(function (p, i) { p.hidden = i !== actual; });

      if (barra) barra.style.width = ((actual + 1) / pasos.length * 100) + '%';
      if (texto) texto.textContent = 'Paso ' + (actual + 1) + ' de ' + pasos.length;
      if (atras) atras.hidden = actual === 0;
      if (adelante) adelante.hidden = actual === pasos.length - 1;
      if (enviar) enviar.hidden = actual !== pasos.length - 1;

      if (mover) {
        /* Al cambiar de paso hay que volver arriba: si no, se llega al paso 2
           con la vista a media altura y da la sensación de que falta algo por
           encima. El foco al título para que un lector de pantalla anuncie
           dónde está. */
        var caja = form.closest('.form-fijo') || form;
        if (caja.scrollTop !== undefined) caja.scrollTop = 0;

        var titulo = pasos[actual].querySelector('.paso__titulo');
        if (titulo) { titulo.setAttribute('tabindex', '-1'); titulo.focus(); }

        var pos = form.getBoundingClientRect();
        if (pos.top < 0) window.scrollTo({ top: window.scrollY + pos.top - 100, behavior: 'smooth' });
      }
    }

    if (adelante) {
      adelante.addEventListener('click', function () {
        var fallos = validarZona(pasos[actual]);
        resumirErrores(form, fallos);
        if (fallos.length) {
          var primero = pasos[actual].querySelector('[aria-invalid="true"]');
          if (primero) {
            if (primero.scrollIntoView) primero.scrollIntoView({ behavior: 'smooth', block: 'center' });
            try { primero.focus({ preventScroll: true }); } catch (err) { primero.focus(); }
          }
          return;
        }
        guardarBorrador();
        mostrar(actual + 1, true);
      });
    }

    if (atras) atras.addEventListener('click', function () { mostrar(actual - 1, true); });

    mostrar(0, false);

    Array.prototype.forEach.call(form.querySelectorAll('input, select, textarea'), function (input) {
      if (input.classList.contains('trampa')) return;
      input.addEventListener('blur', function () { pintarError(input, validarCampo(input)); });
      input.addEventListener('change', guardarBorrador);
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var fallos = [];
      pasos.forEach(function (p) { fallos = fallos.concat(validarZona(p)); });
      resumirErrores(form, fallos);
      if (fallos.length) {
        /* El campo que falta puede estar en el otro paso: primero se va allí,
           y sólo entonces se le lleva la vista encima. Sin lo primero, el
           aviso diría «faltan 3 datos» señalando a algo que no se ve. */
        var primero = form.querySelector('[aria-invalid="true"]');
        if (primero) {
          var indice = pasos.indexOf(primero.closest('[data-paso]'));
          if (indice >= 0 && indice !== actual) mostrar(indice, false);

          if (primero.scrollIntoView) {
            primero.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
          try { primero.focus({ preventScroll: true }); } catch (err) { primero.focus(); }
        }
        return;
      }

      var trampa = form.querySelector('.trampa input');
      if ((trampa && trampa.value) || Date.now() - nacido < TIEMPO_MINIMO) {
        resumirErrores(form, [{ id: form.id, texto: 'Envío', mensaje: 'No hemos podido registrar el envío. Inténtalo de nuevo en unos segundos.' }]);
        return;
      }

      var datos = recoger();

      /* ── Mientras se guarda ─────────────────────────────────────────────
         El servidor puede tardar diez segundos. Sin nada que lo diga, la
         página parece congelada: la persona vuelve a pulsar, se envía dos
         veces, o se va pensando que está caída.

         El botón queda inutilizable y anuncia lo que está pasando. aria-busy
         hace que un lector de pantalla lo diga en voz alta. */
      function ocupado(si, texto) {
        if (!enviar) return;
        enviar.disabled = si;
        enviar.setAttribute('aria-busy', si ? 'true' : 'false');
        enviar.classList.toggle('btn--ocupado', si);
        enviar.textContent = texto;
      }

      ocupado(true, 'Guardando tu información…');

      L14.enviarInscripcion(datos).then(function (respuesta) {
        borrarBorrador();
        form.hidden = true;
        if (confirmacion) {
          /* El código de inscripción lo asigna el servidor (VOL-2026-000123).
             Es la referencia con la que la persona puede escribirnos. */
          var hueco = confirmacion.querySelector('[data-codigo]');
          if (hueco && respuesta && respuesta.codigo) hueco.textContent = respuesta.codigo;

          /* ── Guardada por la vía de contingencia ───────────────────────
             La base no aceptó la inscripción y quedó a buen recaudo en el
             servidor, pendiente de pasarla a mano. Se confirma igual, porque
             el envío llegó de verdad, pero sin decir «ya estás inscrito»:
             sería falso y se enteraría después y peor. */
          var aviso = confirmacion.querySelector('[data-contingencia]');
          if (aviso) aviso.hidden = !(respuesta && respuesta.contingencia);

          confirmacion.hidden = false;
          confirmacion.setAttribute('tabindex', '-1');
          confirmacion.focus();
          confirmacion.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }).catch(function (error) {
        /* ── No hubo respuesta del servidor ────────────────────────────────
           Ni éxito ni error con explicación: la petición no llegó o no volvió.
           Aquí NO se puede decir que se guardó —no sabemos si llegó—, y
           tampoco se puede dejar el formulario mudo.

           Se envía a la antigua, recargando la página con los datos. Lo hace
           el navegador sin JavaScript de por medio, así que funciona incluso
           si lo que falla es algo de aquí, y el servidor responde con su
           propia página: confirmación o error, pero siempre algo. */
        if (error && error.sinRespuesta && !form.dataset.reintentoNativo) {
          form.dataset.reintentoNativo = '1';
          ocupado(true, 'Guardando tu información…');
          try { form.submit(); return; } catch (e) { /* sigue abajo */ }
        }

        ocupado(false, 'Enviar inscripción');

        /* El servidor valida de nuevo TODO, así que puede rechazar cosas que
           el navegador no sabe: un DNI ya inscrito, una jurisdicción retirada
           del catálogo. Esos mensajes se pintan en su campo, no como un aviso
           genérico que no dice qué corregir. */
        var delServidor = (error && error.errores) || {};
        var campos = Object.keys(delServidor);

        if (campos.length) {
          var fallos = [];
          campos.forEach(function (nombre) {
            var input = form.elements[nombre] || form.querySelector('[name="' + nombre + '"]');
            if (input && input.id) {
              pintarError(input, delServidor[nombre]);
              var etiqueta = form.querySelector('label[for="' + input.id + '"]');
              fallos.push({
                id: input.id,
                texto: etiqueta ? etiqueta.textContent.replace('*', '').trim() : nombre,
                mensaje: delServidor[nombre]
              });
              var indice = pasos.indexOf(input.closest('[data-paso]'));
              if (indice >= 0) mostrar(indice, false);
            } else {
              fallos.push({ id: form.id, texto: 'Envío', mensaje: delServidor[nombre] });
            }
          });
          resumirErrores(form, fallos);
          return;
        }

        resumirErrores(form, [{ id: form.id, texto: 'Envío', mensaje: 'No hemos podido registrar tu inscripción. Inténtalo de nuevo en unos minutos.' }]);
      });
    });

    /* ── Borrador en localStorage, con aviso explícito ────────────────── */
    function recoger() {
      var datos = {};
      var fd = new FormData(form);
      fd.forEach(function (v, k) { if (k !== 'sitio-web') datos[k] = v; });
      return datos;
    }

    function guardarBorrador() {
      try { localStorage.setItem(CLAVE_BORRADOR, JSON.stringify(recoger())); } catch (e) { /* modo privado */ }
      pintarAvisoBorrador(true);
    }

    function borrarBorrador() {
      try { localStorage.removeItem(CLAVE_BORRADOR); } catch (e) { /* nada */ }
      pintarAvisoBorrador(false);
    }

    /* ── El borrador ya NO se restaura ─────────────────────────────────
       DESACTIVADO · 21/08/2026 · ver intranet/DEUDA-TECNICA.md

       Restauraba lo escrito en una visita anterior asignando `campo.value`
       a cada control. Con los desplegables en cascada eso es una trampa:
       asignar el valor NO dispara el evento `change`, así que se quedaba un
       departamento puesto y la provincia vacía y bloqueada para siempre.
       Quien volvía a la página se encontraba el formulario a medio rellenar
       y sin poder avanzar.

       Se dejaba de lado a mucha gente por una comodidad. Mientras la cascada
       no sepa reconstruirse desde un borrador, el formulario arranca limpio.

       Para volver a activarlo hay que restaurar EN ORDEN: poner el
       departamento, esperar a que carguen sus provincias, poner la provincia,
       esperar los distritos, y sólo entonces el distrito. */
    function restaurarBorrador() {
      // Se borra cualquier borrador que quedara de la versión anterior: si no,
      // sigue ahí ocupando sitio y reapareciendo si alguien reactiva esto.
      try { localStorage.removeItem(CLAVE_BORRADOR); } catch (e) { /* modo privado */ }
      pintarAvisoBorrador(false);
    }

    function pintarAvisoBorrador(hay) {
      var aviso = form.querySelector('[data-borrador]');
      if (aviso) aviso.hidden = !hay;
    }

    var botonBorrar = form.querySelector('[data-borrar-borrador]');
    if (botonBorrar) {
      botonBorrar.addEventListener('click', function () {
        borrarBorrador();
        form.reset();
        mostrar(0, true);
      });
    }

    /* ── Ubigeo en cascada ─────────────────────────────────────────────
       Al elegir departamento se cargan sus provincias; al elegir provincia,
       sus distritos. Sin JavaScript esto lo resuelve el servidor recargando
       la página, así que aquí sólo se mejora lo que ya funciona. */
    (function ubigeo() {
      var departamento = form.querySelector('[data-ubigeo="departamento"]');
      var provincia = form.querySelector('[data-ubigeo="provincia"]');
      var distrito = form.querySelector('[data-ubigeo="distrito"]');
      if (!departamento || !provincia || !distrito) return;

      /* Quedó de cuando provincia y distrito eran desplegables encadenados y
         sin JavaScript había que recargar la página para traer la lista
         siguiente. Ya no se pinta, pero puede seguir vivo en una página que
         algún navegador tenga guardada. */
      var recargar = form.querySelector('[name="_cargar_ubigeo"]');
      if (recargar) recargar.hidden = true;

      /* Las direcciones las escribe PHP en el formulario, no se construyen
         aquí como rutas relativas: esta misma página se sirve en
         /voluntariado/ y en la raíz del dominio, y una ruta relativa apunta a
         un sitio distinto en cada caso. */
      var endpoint = form.getAttribute('data-ubigeo-url') || 'ubigeo.php';
      var respaldo = form.getAttribute('data-ubigeo-json') || '';

      /* ── Respaldo local ─────────────────────────────────────────────────
         El ubigeo entero en un archivo estático: 25 departamentos, 196
         provincias y 1874 distritos. Lo sirve Apache sin tocar PHP ni MySQL.

         Existe porque la consulta al servidor se estaba quedando colgada y el
         desplegable de provincia no llegaba a cargar nunca: la mayoría de la
         gente no podía pasar del segundo paso. Con esto, si el servidor tarda
         o no responde, el formulario sigue funcionando igual.

         Se pide UNA vez, en cuanto carga la página, y se guarda en memoria:
         cuando hace falta ya está ahí y la respuesta es inmediata. */
      var cacheRespaldo = null;

      function pedirRespaldo() {
        if (cacheRespaldo) return cacheRespaldo;
        if (!respaldo) return Promise.reject();

        cacheRespaldo = fetch(respaldo)
          .then(function (r) { return r.json(); })
          .catch(function () { cacheRespaldo = null; throw new Error('sin respaldo'); });

        return cacheRespaldo;
      }

      /* Se precarga en segundo plano, sin estorbar: para cuando alguien llegue
         al paso 2, el respaldo ya está en memoria. */
      if (respaldo) { pedirRespaldo().catch(function () {}); }

      function delRespaldo(tipo, clave) {
        return pedirRespaldo().then(function (d) {
          var lista = tipo === 'p' ? (d.p || {})[clave] : (d.x || {})[clave];

          /* El archivo guarda pares [id, nombre] para pesar menos; aquí se
             devuelven con la misma forma que da el servidor. */
          return (lista || []).map(function (par) {
            return { id: par[0], nombre: par[1] };
          });
        });
      }

      /* Cuánto se espera al servidor antes de tirar del archivo. Tres segundos
         es más de lo que tarda una respuesta sana y menos de lo que nadie está
         dispuesto a mirar un campo que dice «Cargando…».

         Ya no es un plazo crítico: aunque se agote y el archivo tampoco esté,
         el campo se puede escribir a mano. Antes, agotarse significaba no
         poder inscribirse. */
      var ESPERA = 3000;

      function consultarServidor(consulta) {
        var corta = typeof AbortController === 'function' ? new AbortController() : null;
        var reloj = setTimeout(function () { if (corta) corta.abort(); }, ESPERA);

        var opciones = { headers: { 'Accept': 'application/json' } };
        if (corta) opciones.signal = corta.signal;

        return fetch(endpoint + '?' + consulta, opciones)
          .then(function (r) {
            clearTimeout(reloj);
            if (!r.ok) throw new Error('respuesta ' + r.status);
            return r.json();
          })
          .then(function (d) {
            if (!d.items || !d.items.length) throw new Error('sin datos');
            return d.items;
          })
          .catch(function (e) {
            clearTimeout(reloj);
            throw e;
          });
      }

      /* Pide una lista y NO se rinde nunca de forma que estorbe: si no hay
         datos, devuelve una lista vacía y el campo sigue escribiéndose. */
      function pedirLista(consulta, tipo, clave) {
        return consultarServidor(consulta)
          .catch(function () { return delRespaldo(tipo, clave); })
          .catch(function () { return []; });
      }

      /* ── Comparación tolerante ──────────────────────────────────────────
         Quien escribe «cañete» debe encontrar CAÑETE, y quien escribe
         «huarochiri» sin tilde debe encontrar HUAROCHIRÍ. Se comparan las
         palabras sin tildes, sin mayúsculas y sin signos.

         normalize('NFD') separa la letra de su tilde y el reemplazo borra la
         tilde suelta. La ñ se trata aparte a propósito: en español no es una n
         con virgulilla sino otra letra, pero quien teclea deprisa escribe «n»,
         así que aquí se igualan para poder encontrarla. */
      function plano(t) {
        t = String(t == null ? '' : t).toLowerCase();
        if (t.normalize) t = t.normalize('NFD').replace(/[̀-ͯ]/g, '');
        return t.replace(/ñ/g, 'n').replace(/[^a-z0-9 ]/g, ' ').replace(/\s+/g, ' ').trim();
      }

      /* ── Un campo de texto con sugerencias ──────────────────────────────
         No es un desplegable disfrazado: es un campo de texto de verdad al
         que se le añade una ayuda. Se puede escribir cualquier cosa, tomar
         una sugerencia con el ratón, con las flechas o con Enter, o no tomar
         ninguna. Todo eso vale.

         Se hace a mano y no con una librería del CDN por coherencia: la razón
         de existir de esta pantalla es funcionar cuando la red falla, y sería
         absurdo colgarla de otra descarga que también puede fallar. Son
         ochenta líneas. */
      function sugerencias(campo) {
        var caja = campo.closest('.sugiere');
        if (!caja) return null;

        var oculto = caja.querySelector('input[type="hidden"]');
        var lista = caja.querySelector('.sugiere__lista');
        var datos = [];        /* [{id, nombre}] disponibles ahora mismo */
        var marcado = -1;      /* sugerencia resaltada con las flechas */

        function cerrar() {
          lista.hidden = true;
          lista.innerHTML = '';
          marcado = -1;
          campo.setAttribute('aria-expanded', 'false');
        }

        /* El código oficial sólo vale mientras el texto siga siendo el de la
           sugerencia que se tomó. Si después se corrige una letra, el código
           deja de corresponder y se descarta: es preferible guardar un nombre
           sin código que un código que no es el de esa persona. */
        function sincronizar() {
          if (!oculto) return;
          var actual = plano(campo.value);
          var encaja = datos.some(function (d) {
            return d.id === oculto.value && plano(d.nombre) === actual;
          });
          if (!encaja) oculto.value = '';
        }

        function elegir(d) {
          campo.value = d.nombre;
          if (oculto) oculto.value = d.id;
          cerrar();
          campo.dispatchEvent(new CustomEvent('ubigeo:elegido', { bubbles: true }));
        }

        function pintar(filtradas) {
          lista.innerHTML = '';

          if (!filtradas.length) { cerrar(); return; }

          filtradas.slice(0, 60).forEach(function (d, i) {
            var li = document.createElement('li');
            li.className = 'sugiere__item';
            li.setAttribute('role', 'option');
            li.setAttribute('aria-selected', 'false');
            li.textContent = d.nombre;

            /* mousedown y no click: click llega DESPUÉS de que el campo pierda
               el foco, y para entonces la lista ya se cerró y el clic cae en
               el vacío. Es el fallo clásico de todo autocompletado. */
            li.addEventListener('mousedown', function (e) {
              e.preventDefault();
              elegir(d);
            });

            li.dataset.i = String(i);
            lista.appendChild(li);
          });

          lista.hidden = false;
          campo.setAttribute('aria-expanded', 'true');
          marcado = -1;
        }

        function filtrar() {
          var q = plano(campo.value);
          if (!datos.length) { cerrar(); return; }
          if (!q) { pintar(datos); return; }

          /* Primero las que empiezan por lo escrito, después las que lo
             contienen: quien teclea «lima» espera LIMA arriba del todo. */
          var empiezan = [], contienen = [];
          datos.forEach(function (d) {
            var n = plano(d.nombre);
            if (n.indexOf(q) === 0) empiezan.push(d);
            else if (n.indexOf(q) !== -1) contienen.push(d);
          });

          pintar(empiezan.concat(contienen));
        }

        function resaltar(n) {
          var items = lista.querySelectorAll('.sugiere__item');
          if (!items.length) return;

          if (marcado >= 0 && items[marcado]) {
            items[marcado].classList.remove('es-activo');
            items[marcado].setAttribute('aria-selected', 'false');
          }

          marcado = (n + items.length) % items.length;
          items[marcado].classList.add('es-activo');
          items[marcado].setAttribute('aria-selected', 'true');
          items[marcado].scrollIntoView({ block: 'nearest' });
        }

        campo.addEventListener('input', function () { sincronizar(); filtrar(); });
        campo.addEventListener('focus', filtrar);
        campo.addEventListener('blur', function () { setTimeout(cerrar, 120); });

        campo.addEventListener('keydown', function (e) {
          if (e.key === 'ArrowDown') { e.preventDefault(); if (lista.hidden) filtrar(); else resaltar(marcado + 1); }
          else if (e.key === 'ArrowUp') { e.preventDefault(); resaltar(marcado - 1); }
          else if (e.key === 'Escape') { cerrar(); }
          else if (e.key === 'Enter') {
            /* Enter sólo captura una sugerencia si hay una resaltada. Si no,
               se deja pasar: puede que la persona haya terminado de escribir
               algo que no está en la lista y quiera continuar. */
            var items = lista.querySelectorAll('.sugiere__item');
            if (!lista.hidden && marcado >= 0 && items[marcado]) {
              e.preventDefault();
              items[marcado].dispatchEvent(new Event('mousedown'));
            }
          }
        });

        return {
          /* Cambia el catálogo disponible. No toca lo que la persona haya
             escrito: si ya puso algo, se respeta. */
          servir: function (items) {
            datos = items || [];
            sincronizar();
            if (document.activeElement === campo) filtrar();
          },
          vaciar: function () {
            campo.value = '';
            if (oculto) oculto.value = '';
            datos = [];
            cerrar();
          },
          idActual: function () { return oculto ? oculto.value : ''; }
        };
      }

      var acProvincia = sugerencias(provincia);
      var acDistrito  = sugerencias(distrito);
      if (!acProvincia || !acDistrito) return;   /* HTML viejo: no se estorba */

      departamento.addEventListener('change', function () {
        acProvincia.vaciar();
        acDistrito.vaciar();
        if (!departamento.value) return;

        pedirLista('departamento=' + encodeURIComponent(departamento.value),
                   'p', departamento.value)
          .then(function (items) { acProvincia.servir(items); });
      });

      /* Los distritos se piden cuando la provincia queda resuelta con su
         código. Si se escribió a mano y no hay código, no hay lista que pedir
         y el distrito se teclea: es justo el caso que antes dejaba tirada a la
         gente y ahora simplemente sigue adelante. */
      provincia.addEventListener('ubigeo:elegido', function () {
        acDistrito.vaciar();
        var id = acProvincia.idActual();
        if (!id) return;

        pedirLista('provincia=' + encodeURIComponent(id), 'x', id)
          .then(function (items) { acDistrito.servir(items); });
      });

      /* ── El formulario arranca limpio ───────────────────────────────────
         Los navegadores restauran los valores de un formulario al recargar o
         al volver con el botón «atrás», y eso dejaba un departamento puesto
         con la provincia vacía: la persona veía algo elegido que no eligió.

         Se vacían los tres al arrancar, salvo que el servidor los haya
         pintado ya con algo —que sólo ocurre cuando el formulario vuelve con
         un error y hay que devolverle a la persona lo que había puesto—. */
      if (!departamento.getAttribute('data-conservar')) {
        departamento.value = '';
        acProvincia.vaciar();
        acDistrito.vaciar();
      } else {
        /* Vuelta de un error: se recargan los catálogos para que las
           sugerencias sigan funcionando sin tener que reelegir nada. */
        if (departamento.value) {
          pedirLista('departamento=' + encodeURIComponent(departamento.value),
                     'p', departamento.value)
            .then(function (items) { acProvincia.servir(items); });
        }
        var idProv = acProvincia.idActual();
        if (idProv) {
          pedirLista('provincia=' + encodeURIComponent(idProv), 'x', idProv)
            .then(function (items) { acDistrito.servir(items); });
        }
      }
    })();

    /* ── Calendario de la fecha de nacimiento ──────────────────────────
       flatpickr sustituye al calendario nativo del navegador, que empieza
       siempre por el mes actual y obliga a retroceder treinta y tantos años
       para una fecha de nacimiento.

       Lo que resuelve, y por lo que se usa una librería en lugar del campo
       nativo:
         · abre directamente por el año que diga data-abre-en, SIN rellenar
           el campo (una fecha puesta de oficio que nadie revisa acaba
           guardada como si fuera la de verdad);
         · el año es un campo escribible, no una flecha que hay que pulsar;
         · el mes es un desplegable;
         · se ve igual en todos los navegadores y también en móvil.

       Si el CDN no responde, esta función no hace nada y el campo se queda
       como <input type="date">, que sigue siendo perfectamente usable. */
    (function calendario() {
      var campo = form.querySelector('[data-calendario]');
      if (!campo) return;

      if (typeof window.flatpickr !== 'function') return;   /* CDN caído */

      /* flatpickr recomienda text: sobre un type="date" convivirían dos
         calendarios, el suyo y el del navegador. El type se cambia sólo
         cuando ya sabemos que flatpickr está disponible, para que el campo
         nativo siga siendo la alternativa si no lo está. */
      campo.type = 'text';
      campo.setAttribute('inputmode', 'none');   /* en móvil, sin teclado */

      var abreEn = campo.getAttribute('data-abre-en') || '1990-01-01';

      if (window.flatpickr.l10ns && window.flatpickr.l10ns.es) {
        window.flatpickr.localize(window.flatpickr.l10ns.es);
      }

      window.flatpickr(campo, {
        dateFormat: 'Y-m-d',          /* lo que viaja al servidor */
        altInput: true,               /* lo que ve la persona */
        altFormat: 'j \\d\\e F \\d\\e Y',
        minDate: campo.getAttribute('min') || null,
        maxDate: campo.getAttribute('max') || null,
        monthSelectorType: 'dropdown',
        disableMobile: true,          /* también en móvil manda flatpickr */
        allowInput: false,
        onOpen: function (elegidas, cadena, instancia) {
          /* Sólo salta si aún no hay fecha: si la persona ya eligió una, o
             el formulario volvió con error y la trae puesta, el calendario
             debe abrirse donde ella la dejó, no en 1990. */
          if (!elegidas.length) {
            instancia.jumpToDate(abreEn, true);
          }
        },
        onChange: function () {
          pintarError(campo, true);
        }
      });

      /* altInput crea un campo nuevo y esconde el original. El resumen de
         errores enlaza con #nacimiento, así que al pulsar ese enlace hay que
         llevar el foco al que se ve, no al que está oculto. */
      var visible = campo.nextElementSibling;
      if (visible && visible.classList.contains('form-control')) {
        visible.classList.add('flatpickr-visible');

        /* Y hay que sacarlo de la validación.
           flatpickr le copia el `required` del original, pero este campo no
           tiene id ni name: no se envía nada desde él, sólo enseña la fecha
           con formato legible. Al validarlo como si fuera uno más, aparecía
           en la lista de errores sin nombre —«: Este dato es obligatorio»— y,
           peor, impedía pasar de paso aunque la fecha estuviera puesta.
           El campo que se valida es el original, que es el que lleva el
           valor. */
        visible.removeAttribute('required');
        visible.setAttribute('data-ignorar', '');
      }
    })();

    /* ── Lupa del DNI ──────────────────────────────────────────────────
       Consulta los datos del documento y rellena el nombre. */
    (function lupaDni() {
      var boton = form.querySelector('[data-buscar-dni]');
      var dni = form.querySelector('#dni');
      var nombre = form.querySelector('#nombres');
      var estado = form.querySelector('[data-dni-estado]');
      if (!boton || !dni || !nombre) return;

      /* Lo que cuenta la lupa —«consultando…», «todavía no está disponible»—
         se dice en el mismo recuadro de arriba que los errores, y no debajo
         del campo. Dos avisos en dos sitios distintos para el mismo DNI era
         justo lo que descuadraba el bloque: el de la lupa aparecía debajo,
         empujaba, y encima competía con el de validación.

         Se conserva el <p> de estado con el mismo texto —oculto a la vista
         por CSS dentro del carril— porque lleva aria-live: es lo que hace que
         un lector de pantalla anuncie el resultado sin que nadie tenga que ir
         a buscarlo. */
      function decir(texto, tipo) {
        if (estado) {
          estado.textContent = texto;
          estado.className = 'campo__estado' + (tipo ? ' campo__estado--' + tipo : '');
        }

        var resumen = form.querySelector('[data-resumen-errores]');
        if (!resumen) return;

        if (!texto) { resumen.hidden = true; resumen.innerHTML = ''; return; }

        resumen.innerHTML = '<span>' + texto + '</span>';
        /* Marcado como aviso y no como error: que la consulta del documento no
           esté disponible no es una falta de quien se inscribe, y pintarlo en
           rojo de alarma sugiere que ha hecho algo mal. */
        resumen.classList.toggle('resumen-errores--aviso', tipo !== 'error');
        resumen.hidden = false;
      }

      boton.addEventListener('click', function () {
        /* La comprobación y el mensaje salen de `reglas.dni`, no de aquí.
           Antes esta función repetía por su cuenta la expresión regular y el
           texto del aviso, y al acortar los mensajes se quedó con el largo:
           el formulario decía una cosa y la lupa otra, para el mismo error y
           en el mismo campo. Un texto escrito dos veces acaba divergiendo
           siempre; lo raro es que tarde. */
        var revision = reglas.dni(dni.value);

        if (revision !== true) {
          pintarError(dni, revision);
          /* Y se dice arriba, que es donde se leen los avisos. Sin esta línea
             el campo se marcaba en rojo y no se explicaba en ninguna parte
             por qué: el texto de debajo ya no se muestra. */
          decir('<a href="#dni">DNI</a>: ' + revision, 'error');
          dni.focus();
          return;
        }

        boton.disabled = true;
        decir('Consultando…');

        L14.buscarDni(dni.value.trim()).then(function (datos) {
          boton.disabled = false;

          if (!datos || !datos.nombre) {
            decir('No encontramos ese DNI. Escribe tu nombre a mano.', 'aviso');
            nombre.readOnly = false;
            nombre.focus();
            return;
          }

          nombre.value = datos.nombre;
          /* readOnly y no disabled: un campo deshabilitado NO se envía con el
             formulario, y el nombre se perdería por el camino. */
          nombre.readOnly = true;
          pintarError(nombre, true);
          decir('Datos encontrados.', 'ok');
        }).catch(function (error) {
          boton.disabled = false;
          nombre.readOnly = false;
          decir((error && error.mensaje) || 'La consulta no está disponible. Escribe tu nombre a mano.', 'aviso');
          nombre.focus();
        });
      });

      /* Si cambian el DNI después de una búsqueda, el nombre vuelve a ser
         editable: si no, quedaría el nombre de otra persona bloqueado. */
      dni.addEventListener('input', function () {
        if (nombre.readOnly) {
          nombre.readOnly = false;
          nombre.value = '';
          decir('');
        }
      });
    })();

    restaurarBorrador();
    mostrar(0, false);
  }

  /* ══════════════════════════════════════════════════════════════════════
     3. PUNTOS DE CONEXIÓN CON EL BACKEND
     ══════════════════════════════════════════════════════════════════════ */

  /**
   * Registra una inscripción de voluntariado (Fase 01).
   *
   * Va contra la propia página (voluntariado/index.php), que es quien valida y
   * escribe en la tabla `voluntarios`. Mismo origen: sin CORS y sin exponer un
   * endpoint aparte.
   *
   * Se envía como FormData y no como JSON a propósito: así el cuerpo de la
   * petición es idéntico al que manda el navegador cuando NO hay JavaScript, y
   * el servidor tiene un único camino de entrada que mantener en vez de dos.
   * La cabecera Accept es lo que le pide que responda JSON en lugar de HTML.
   *
   * @param {Object} datos Campos del formulario, ya validados en cliente.
   * @returns {Promise<{ok: boolean, codigo: string}>}
   */
  function enviarInscripcion(datos) {
    var form = document.querySelector('[data-form="inscripcion"]');
    var cuerpo = new FormData();

    Object.keys(datos).forEach(function (k) { cuerpo.append(k, datos[k]); });

    /* El testigo firmado y la marca de tiempo no salen de recoger(), porque
       ese método descarta los campos ocultos. Se añaden aquí. */
    if (form) {
      ['_testigo', '_nacido'].forEach(function (nombre) {
        var campo = form.querySelector('[name="' + nombre + '"]');
        if (campo) cuerpo.append(nombre, campo.value);
      });
    }

    /* El action del formulario es «#inscripcion»: sirve para que el envío sin
       JavaScript vuelva a la misma página y caiga en el ancla del formulario.
       Como destino de fetch no vale, así que se usa la ruta actual. */
    var accion = form ? (form.getAttribute('action') || '') : '';
    if (accion === '' || accion.charAt(0) === '#') accion = window.location.pathname;

    /* ── Un intento ─────────────────────────────────────────────────────
       El plazo es largo a propósito: este servidor tarda entre uno y diez
       segundos hasta para un archivo estático, y cortar a los cinco convertiría
       una lentitud normal en una inscripción perdida. Treinta segundos es más
       de lo que nadie espera mirando la pantalla, pero el botón dice lo que
       está pasando y eso hace la espera soportable. */
    function intento() {
      var corta = typeof AbortController === 'function' ? new AbortController() : null;
      var reloj = setTimeout(function () { if (corta) corta.abort(); }, 30000);

      var opciones = {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: cuerpo,
        credentials: 'same-origin'
      };
      if (corta) opciones.signal = corta.signal;

      return fetch(accion, opciones).then(function (r) {
        clearTimeout(reloj);

        return r.json().catch(function () {
          /* Respuesta que no es JSON: una página de error del hosting, un
             aviso del proxy. No sabemos qué pasó, y sobre todo no sabemos si
             se guardó, así que se trata como falta de respuesta. */
          var e = new Error('respuesta-ilegible');
          e.sinRespuesta = true;
          throw e;
        }).then(function (datos) {
          if (!r.ok || !datos.ok) {
            var error = new Error('inscripcion-rechazada');
            error.errores = datos.errores || {};
            /* Un rechazo con motivos es una respuesta clara del servidor y no
               se reintenta: el envío no va a mejorar por repetirlo. */
            error.sinRespuesta = !datos.errores;
            throw error;
          }
          return datos;
        });
      }).catch(function (e) {
        clearTimeout(reloj);
        /* Fallo de red o plazo agotado: no llegó respuesta. */
        if (!e.errores) e.sinRespuesta = true;
        throw e;
      });
    }

    /* Un reintento y sólo uno. Cubre el tropiezo puntual —que en este hosting
       es lo más frecuente— sin machacar un servidor que quizá esté ahogado
       precisamente por exceso de peticiones.

       Si el primer intento SÍ guardó y se perdió la respuesta, el segundo
       chocará con el DNI ya inscrito; el servidor reconoce ese caso y devuelve
       el código en lugar de un error. */
    return intento().catch(function (e) {
      if (!e.sinRespuesta) throw e;

      return new Promise(function (r) { setTimeout(r, 1200); }).then(intento);
    });
  }

  /**
   * Busca los datos de un DNI. La lupa del formulario llama aquí.
   *
   * ─────────────────────────────────────────────────────────────────────
   * PUNTO DE CONEXIÓN CON RENIEC. Hoy no hay servicio contratado, así que
   * rechaza con un mensaje que le dice a la persona qué hacer: escribir el
   * nombre a mano. No falla en silencio ni deja el formulario bloqueado.
   *
   * El día que se contrate, esto es lo único que cambia:
   *
   *   return fetch('reniec.php?dni=' + encodeURIComponent(dni), {
   *     headers: { 'Accept': 'application/json' }
   *   }).then(function (r) { return r.json(); })
   *     .then(function (d) {
   *       if (!d.ok) throw { mensaje: d.mensaje };
   *       return { nombre: d.nombre };
   *     });
   *
   * El endpoint debe vivir en el servidor, NUNCA llamarse desde el navegador
   * con la credencial: publicaría el token del servicio a cualquiera que abra
   * las herramientas de desarrollo. Los ajustes reniec.endpoint y
   * reniec.token ya existen en la base para eso.
   * ─────────────────────────────────────────────────────────────────────
   *
   * @param {string} dni Ocho dígitos, ya validados.
   * @returns {Promise<{nombre: string}>}
   */
  function buscarDni(dni) {
    return Promise.reject({
      mensaje: 'La búsqueda por DNI todavía no está disponible. Escribe tu nombre a mano.'
    });
  }

  /**
   * Registra un correo para el aviso de publicación de la agenda.
   * Mismo criterio: sustituir el cuerpo por el fetch del cliente.
   */
  function enviarAviso(datos) {
    if (window.console && console.info) {
      console.info('[leon14enperu] Aviso por correo listo para enviar:', datos);
    }
    return new Promise(function (resolve) { setTimeout(resolve, 300); });
  }

  function init() { avisos(); inscripcion(); }

  L14.form = { init: init };
  L14.enviarInscripcion = enviarInscripcion;
  L14.buscarDni = buscarDni;
  L14.enviarAviso = enviarAviso;
})(window.L14);
