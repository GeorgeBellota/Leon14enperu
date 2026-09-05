/* ============================================================================
   check-contrast.js — validación de contraste WCAG en las tres paletas (§5.6)

   Lee las variables COMPUTADAS de cada tema (no una copia de los valores) y
   comprueba todas las combinaciones que las reglas de uso de §5.4 permiten.
   Debe reportar CERO fallos en bandera, escudo y sereno.

   Uso: abrir assets/js/dev/contraste.html, o cargar este archivo en cualquier
   página del sitio y llamar a L14dev.comprobarContraste().

   NO se carga en producción. Es utillaje de revisión.
   ========================================================================== */
(function () {
  'use strict';

  var TEMAS = ['bandera', 'escudo', 'sereno'];

  /* ── Color: parseo y luminancia relativa (WCAG 2.2) ─────────────────── */
  function aRgb(valor) {
    valor = String(valor).trim();
    var m = valor.match(/^#([0-9a-f]{3,8})$/i);
    if (m) {
      var h = m[1];
      if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
      return [parseInt(h.slice(0, 2), 16), parseInt(h.slice(2, 4), 16), parseInt(h.slice(4, 6), 16)];
    }
    m = valor.match(/rgba?\(([^)]+)\)/i);
    if (m) {
      var p = m[1].split(/[\s,/]+/).filter(Boolean).map(parseFloat);
      return [p[0], p[1], p[2]];
    }
    throw new Error('Color no reconocido: ' + valor);
  }

  function canal(c) {
    c = c / 255;
    return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
  }

  function luminancia(rgb) {
    return 0.2126 * canal(rgb[0]) + 0.7152 * canal(rgb[1]) + 0.0722 * canal(rgb[2]);
  }

  function ratio(a, b) {
    var la = luminancia(aRgb(a)), lb = luminancia(aRgb(b));
    var hi = Math.max(la, lb), lo = Math.min(la, lb);
    return (hi + 0.05) / (lo + 0.05);
  }

  /* Mezcla opaca: `pct`% de `a` sobre el fondo `b`. Reproduce lo que hace
     color-mix(in srgb, X pct%, transparent) al pintarse encima de `b`. */
  function mezcla(a, pct, b) {
    var ra = aRgb(a), rb = aRgb(b), t = pct / 100;
    return 'rgb(' + [0, 1, 2].map(function (i) {
      return Math.round(ra[i] * t + rb[i] * (1 - t));
    }).join(',') + ')';
  }

  /* ── Combinaciones exigidas por §5.4 ────────────────────────────────── */
  var PRUEBAS = [
    /* Texto de lectura sobre los dos fondos claros */
    ['--ink', '--surface', 4.5, 'Texto corriente'],
    ['--ink', '--surface-alt', 4.5, 'Texto corriente sobre fondo alterno'],
    ['--ink-soft', '--surface', 4.5, 'Texto secundario'],
    ['--ink-soft', '--surface-alt', 4.5, 'Texto secundario sobre fondo alterno'],
    ['--ink-mute', '--surface', 3.0, 'Apagado: SOLO ≥16px, exigencia de texto grande'],
    ['--ink-mute', '--surface-alt', 3.0, 'Apagado sobre fondo alterno, solo ≥16px'],

    /* Texto en color: siempre la variante -strong */
    ['--primary-strong', '--surface', 4.5, 'Enlaces y texto en color primario'],
    ['--primary-strong', '--surface-alt', 4.5, 'Texto primario sobre fondo alterno'],
    ['--primary-strong', '--primary-tint', 4.5, 'Texto primario sobre su propio tinte'],
    ['--accent-strong', '--surface', 4.5, 'Texto de acento y errores'],
    ['--accent-strong', '--surface-alt', 4.5, 'Texto de acento sobre fondo alterno'],
    ['--accent-strong', '--accent-tint', 4.5, 'Texto de acento sobre su tinte (avisos)'],
    ['--ink', '--accent-tint', 4.5, 'Título de aviso'],
    ['--ink-soft', '--accent-tint', 4.5, 'Cuerpo de aviso'],
    ['--ink', '--primary-tint', 4.5, 'Texto sobre selección'],

    /* Fondos pastel de sección: --primary-tint y --accent-tint como superficie */
    ['--ink-soft', '--primary-tint', 4.5, 'Texto secundario sobre pastel primario'],
    ['--primary-strong', '--primary-tint', 4.5, 'Cifra del contador sobre su propio pastel'],
    ['--primary-strong', '--accent-tint', 4.5, 'Numerales sobre pastel de acento'],
    ['--accent-strong', '--primary-tint', 4.5, 'Texto de acento sobre pastel primario'],
    ['--ink-mute', '--primary-tint', 3.0, 'Apagado sobre pastel primario, solo ≥16px'],
    ['--ink-mute', '--accent-tint', 3.0, 'Apagado sobre pastel de acento, solo ≥16px'],
    ['--line-strong', '--primary-tint', 3.0, 'Filete de lista sobre pastel primario'],
    ['--line-strong', '--accent-tint', 3.0, 'Filete de lista sobre pastel de acento'],
    ['--gold', '--primary-tint', 3.0, 'Ornamento de oro sobre pastel primario'],
    ['--primary', '--primary-tint', 3.0, 'Trazo del diagrama sobre su pastel'],
    ['--focus', '--primary-tint', 3.0, 'Anillo de foco sobre pastel primario'],
    ['--focus', '--accent-tint', 3.0, 'Anillo de foco sobre pastel de acento'],

    /* Botones */
    ['--on-primary', '--primary', 4.5, 'Botón primario'],
    ['--on-primary', '--primary-strong', 4.5, 'Botón primario en hover'],
    ['--on-accent', '--accent', 4.5, 'Botón secundario'],
    ['--on-accent', '--accent-strong', 4.5, 'Botón secundario en hover'],

    /* Banda oscura: ni --primary ni --accent pisan --surface-invert */
    ['--on-primary', '--surface-invert', 4.5, 'Texto blanco sobre banda oscura'],
    ['--gold-invert', '--surface-invert', 4.5, 'Rótulo dorado sobre banda oscura'],
    ['--ink', '--gold-invert', 4.5, 'Botón claro sobre banda oscura (texto oscuro sobre oro)'],

    /* Componentes de interfaz: 3:1 (WCAG 2.2 SC 1.4.11) */
    ['--line-strong', '--surface', 3.0, 'Borde de campo'],
    ['--line-strong', '--surface-alt', 3.0, 'Borde de campo sobre fondo alterno'],
    ['--primary', '--surface', 3.0, 'Fondo de botón / hilo peregrino sobre fondo claro'],
    ['--primary', '--surface-alt', 3.0, 'Hilo peregrino sobre fondo alterno'],
    ['--accent', '--surface', 3.0, 'Hilo peregrino en temas con acento como hilo'],
    ['--gold', '--surface', 3.0, 'Filetes y viñetas de oro — NUNCA texto'],
    ['--gold', '--surface-alt', 3.0, 'Filetes de oro sobre fondo alterno'],
    ['--gold', '--accent-tint', 3.0, 'Filete de oro del bloque de aviso'],
    ['--focus', '--surface', 3.0, 'Anillo de foco'],
    ['--focus', '--surface-alt', 3.0, 'Anillo de foco sobre fondo alterno'],
    ['--focus-invert', '--surface-invert', 3.0, 'Anillo de foco sobre banda oscura'],
    ['--thread', '--surface', 3.0, 'Hilo peregrino'],
    ['--thread-invert', '--surface-invert', 3.0, 'Hilo peregrino sobre banda oscura'],

    /* Mezclas reales que se pintan en la banda oscura */
    [['--on-primary', 88, '--surface-invert'], '--surface-invert', 4.5, 'Párrafo al 88% sobre banda oscura'],
    [['--on-primary', 86, '--surface-invert'], '--surface-invert', 4.5, 'Texto de casilla al 86% sobre banda oscura'],
    [['--on-primary', 82, '--surface-invert'], '--surface-invert', 4.5, 'Bajada de cabecera al 82% sobre banda oscura'],
    [['--on-primary', 62, '--surface-invert'], '--surface-invert', 3.0, 'Enlace reservado al 62% sobre banda oscura']
  ];

  function resolver(estilo, ficha, valor) {
    if (Array.isArray(valor)) {
      return mezcla(estilo.getPropertyValue(valor[0]).trim(), valor[1], estilo.getPropertyValue(valor[2]).trim());
    }
    return estilo.getPropertyValue(valor).trim();
  }

  function nombre(v) { return Array.isArray(v) ? v[0] + ' @' + v[1] + '% / ' + v[2] : v; }

  function comprobarContraste(silencioso) {
    var raiz = document.documentElement;
    var original = raiz.getAttribute('data-theme');
    var informe = [];
    var fallos = 0;

    TEMAS.forEach(function (tema) {
      raiz.setAttribute('data-theme', tema);
      var estilo = getComputedStyle(raiz);

      PRUEBAS.forEach(function (p) {
        var fg = resolver(estilo, tema, p[0]);
        var bg = resolver(estilo, tema, p[1]);
        var r, error = null;
        try { r = ratio(fg, bg); } catch (e) { error = e.message; r = 0; }
        var pasa = !error && r >= p[2];
        if (!pasa) fallos++;
        informe.push({
          tema: tema,
          frente: nombre(p[0]),
          fondo: nombre(p[1]),
          exigido: p[2],
          obtenido: Math.round(r * 100) / 100,
          pasa: pasa,
          nota: p[3],
          error: error
        });
      });
    });

    if (original) raiz.setAttribute('data-theme', original); else raiz.removeAttribute('data-theme');

    if (!silencioso && window.console) {
      if (console.table) console.table(informe);
      console.log(fallos === 0
        ? '✔ Contraste: 0 fallos en las tres paletas (' + informe.length + ' comprobaciones).'
        : '✖ Contraste: ' + fallos + ' fallo(s) de ' + informe.length + ' comprobaciones.');
    }

    return { fallos: fallos, total: informe.length, informe: informe };
  }

  window.L14dev = window.L14dev || {};
  window.L14dev.comprobarContraste = comprobarContraste;
  window.L14dev.ratio = ratio;
  window.L14dev.PRUEBAS = PRUEBAS;
})();
