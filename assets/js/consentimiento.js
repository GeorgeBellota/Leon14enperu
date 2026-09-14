/* ============================================================================
   CONSENTIMIENTO DE MEDICIÓN
   ----------------------------------------------------------------------------
   Decide si Google Analytics y el píxel de Meta llegan a existir en la página.

   El orden importa y es el único correcto: NADA se carga hasta que la persona
   acepta. Si el script se cargara y después saliera el cartel, esa visita ya
   estaría medida y el aviso no sería un consentimiento, sino el parte de algo
   ya hecho.

   Los identificadores llegan en atributos de datos del propio aviso, inertes.
   Aquí se leen, se vuelven a comprobar y se usan para componer el script. Si
   el formato no cuadra, no se carga nada: entre la base y un <script> no puede
   haber un solo carácter sin comprobar.
   ========================================================================== */

(function () {
  'use strict';

  var aviso = document.querySelector('[data-consentimiento]');
  if (!aviso) return;

  var LLAVE = 'leon14.cookies';

  var ga4   = (aviso.getAttribute('data-ga4') || '').toUpperCase();
  var pixel = aviso.getAttribute('data-pixel') || '';

  /* Última comprobación antes de construir un <script>. */
  if (!/^G-[A-Z0-9]{6,14}$/.test(ga4))  ga4 = '';
  if (!/^\d{10,20}$/.test(pixel))       pixel = '';
  if (!ga4 && !pixel) return;

  /* El almacenamiento del navegador puede estar bloqueado —modo privado,
     ajustes del sistema—. Si no se puede leer ni escribir, se pregunta cada
     vez: molesto, pero nunca se mide a quien no dijo que sí. */
  function recordado() {
    try { return window.localStorage.getItem(LLAVE); } catch (e) { return null; }
  }

  function recordar(valor) {
    try { window.localStorage.setItem(LLAVE, valor); } catch (e) { /* da igual */ }
  }

  function cargarScript(src) {
    var s = document.createElement('script');
    s.src = src;
    s.async = true;
    document.head.appendChild(s);
    return s;
  }

  function encenderAnalytics() {
    if (!ga4) return;

    cargarScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(ga4));

    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    window.gtag = gtag;
    gtag('js', new Date());
    /* Sin cookies de publicidad: se mide el uso del sitio, no se construye un
       perfil para anuncios. Es lo que dice el aviso, así que es lo que hay
       que hacer. */
    gtag('config', ga4, { anonymize_ip: true, allow_google_signals: false });
  }

  function encenderPixel() {
    if (!pixel) return;

    /* El arranque que documenta Meta, escrito a mano en vez de pegado: así se
       ve qué hace y no entra ni una línea que no hayamos leído. */
    var n = window.fbq = function () {
      n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
    };
    if (!window._fbq) window._fbq = n;
    n.push = n;
    n.loaded = true;
    n.version = '2.0';
    n.queue = [];

    cargarScript('https://connect.facebook.net/en_US/fbevents.js');

    window.fbq('init', pixel);
    window.fbq('track', 'PageView');
  }

  function aceptar() {
    recordar('si');
    aviso.hidden = true;
    encenderAnalytics();
    encenderPixel();
  }

  function rechazar() {
    recordar('no');
    aviso.hidden = true;
    /* Nada que apagar: no se encendió nada. */
  }

  var decision = recordado();

  if (decision === 'si') {
    encenderAnalytics();
    encenderPixel();
    return;
  }

  if (decision === 'no') {
    return;
  }

  /* Sin decisión todavía: se pregunta. */
  aviso.hidden = false;

  var si = aviso.querySelector('[data-cookies-aceptar]');
  var no = aviso.querySelector('[data-cookies-rechazar]');

  if (si) si.addEventListener('click', aceptar);
  if (no) no.addEventListener('click', rechazar);

  /* Escape equivale a rechazar. Cerrar un cartel nunca puede significar que
     sí: quien lo quita de en medio sin leerlo no ha autorizado nada. */
  document.addEventListener('keydown', function (evento) {
    if (evento.key === 'Escape' && !aviso.hidden) rechazar();
  });
})();
