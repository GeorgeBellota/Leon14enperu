/* =============================================================================
   León XIV en el Perú — comportamiento del sitio (JavaScript sin dependencias)
   ========================================================================== */
(function () {
  "use strict";

  var doc = document;
  var $  = function (s, c) { return (c || doc).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || doc).querySelectorAll(s)); };

  /* ------------------------------------------------------------ Menú móvil */
  function initNav() {
    var toggle = $(".nav-toggle");
    var nav = $("#site-nav");
    if (!toggle || !nav) return;

    var backdrop = $(".nav-backdrop");
    if (!backdrop) {
      backdrop = doc.createElement("div");
      backdrop.className = "nav-backdrop";
      doc.body.appendChild(backdrop);
    }

    function setOpen(open) {
      doc.body.classList.toggle("nav-open", open);
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      doc.body.style.overflow = open ? "hidden" : "";
      if (open) {
        var first = nav.querySelector("a");
        if (first) first.focus({ preventScroll: true });
      }
    }

    toggle.addEventListener("click", function () {
      setOpen(toggle.getAttribute("aria-expanded") !== "true");
    });
    backdrop.addEventListener("click", function () { setOpen(false); });
    doc.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && doc.body.classList.contains("nav-open")) { setOpen(false); toggle.focus(); }
    });
    nav.addEventListener("click", function (e) {
      if (e.target.closest("a")) setOpen(false);
    });
    var mq = window.matchMedia("(min-width:1024px)");
    (mq.addEventListener ? mq.addEventListener.bind(mq, "change") : mq.addListener.bind(mq))(function () {
      if (mq.matches) setOpen(false);
    });
  }

  /* ---------------------------------------------------- Sombra de cabecera */
  function initStickyHeader() {
    var header = $(".site-header");
    if (!header) return;
    var tick = false;
    function update() {
      header.classList.toggle("is-stuck", window.scrollY > 8);
      tick = false;
    }
    window.addEventListener("scroll", function () {
      if (!tick) { tick = true; window.requestAnimationFrame(update); }
    }, { passive: true });
    update();
  }

  /* --------------------------------------------------- Cuenta regresiva
     Puede haber más de una en la misma página: la tira fina de la cabecera y
     la banda dorada de la portada. La fecha sale del panel en data-objetivo;
     si no llega ninguna se usa la anunciada por la Santa Sede, que es mejor
     que dejar los ceros puestos. */
  var FECHA_VIAJE = "2026-11-11T00:00:00-05:00";

  function initCountdown() {
    var raices = $$("[data-countdown]");
    if (!raices.length) return;

    var relojes = [];

    raices.forEach(function (root) {
      var iso = root.getAttribute("data-objetivo") ||
                root.getAttribute("data-countdown") || FECHA_VIAJE;
      var target = new Date(iso).getTime();
      if (isNaN(target)) target = new Date(FECHA_VIAJE).getTime();

      relojes.push({
        target: target,
        out: {
          dias:     $("[data-cd='dias']", root),
          horas:    $("[data-cd='horas']", root),
          minutos:  $("[data-cd='minutos']", root),
          segundos: $("[data-cd='segundos']", root)
        }
      });
    });

    var pad = function (n) { return (n < 10 ? "0" : "") + n; };

    function tick() {
      relojes.forEach(function (r) {
        var diff = Math.max(0, r.target - Date.now());
        var s = Math.floor(diff / 1000);
        var d = Math.floor(s / 86400);
        var h = Math.floor((s % 86400) / 3600);
        var m = Math.floor((s % 3600) / 60);
        var sec = s % 60;
        if (r.out.dias)     r.out.dias.textContent     = d > 99 ? String(d) : pad(d);
        if (r.out.horas)    r.out.horas.textContent    = pad(h);
        if (r.out.minutos)  r.out.minutos.textContent  = pad(m);
        if (r.out.segundos) r.out.segundos.textContent = pad(sec);
      });
    }
    tick();
    setInterval(tick, 1000);
  }

  /* ------------------------------------------------------------ Acordeón */
  function initAccordions() {
    $$(".accordion").forEach(function (acc) {
      var single = acc.hasAttribute("data-single");
      $$(".accordion__btn", acc).forEach(function (btn) {
        btn.addEventListener("click", function () {
          var open = btn.getAttribute("aria-expanded") === "true";
          if (single && !open) {
            $$(".accordion__btn", acc).forEach(function (b) { b.setAttribute("aria-expanded", "false"); });
          }
          btn.setAttribute("aria-expanded", open ? "false" : "true");
        });
      });
    });
  }

  /* -------------------------------------------------- Filtros por ciudad */
  function initFilters() {
    $$("[data-filter-group]").forEach(function (group) {
      var name = group.getAttribute("data-filter-group");
      var targets = $$("[data-filter-item='" + name + "']");
      $$(".chip", group).forEach(function (chip) {
        chip.addEventListener("click", function () {
          var value = chip.getAttribute("data-filter");
          $$(".chip", group).forEach(function (c) { c.classList.toggle("is-active", c === chip); });
          targets.forEach(function (t) {
            var show = value === "all" || t.getAttribute("data-key") === value;
            t.hidden = !show;
          });
        });
      });
    });
  }

  /* ------------------------------------------------- Carrusel del inicio */
  function initHeroSlider() {
    var slider = $("[data-slider]");
    if (!slider) return;
    var slides = $$("[data-slide]", slider);
    var fotos = $$("[data-slide-foto]", slider);
    var dots = $$("[data-slide-dot]", slider);
    var btnPrev = $("[data-slide-prev]", slider);
    var btnNext = $("[data-slide-next]", slider);
    if (slides.length < 2) return;
    var i = 0, timer = null;
    var DELAY = 7000;
    var quieto = window.matchMedia("(prefers-reduced-motion:reduce)").matches;

    /* La barra de la marca activa la dibuja el CSS; el tiempo lo pone aquí para
       que salga del mismo número que el temporizador. */
    slider.style.setProperty("--hero-paso", (DELAY / 1000) + "s");

    /* Reiniciar una animación CSS exige quitarla, forzar el reflujo leyendo una
       medida y devolverla: si no, el navegador no la ve como nueva y la barra
       se queda llena desde el segundo salto. */
    function reiniciarCuenta(dot) {
      var barra = dot && dot.querySelector(".hero-dot__fill");
      if (!barra) return;
      barra.style.animation = "none";
      void barra.offsetWidth;
      barra.style.animation = "";
    }

    /* «sentido» vale 1 hacia delante y -1 hacia atrás; de él salen el lado por
       el que entra la lámina nueva y el lado por el que se va la anterior. */
    function go(n, sentido) {
      var previo = i;
      i = (n + slides.length) % slides.length;
      if (sentido) slider.setAttribute("data-dir", sentido > 0 ? "next" : "prev");

      slides.forEach(function (s, k) {
        s.classList.toggle("is-active", k === i);
        s.classList.toggle("is-leaving", k === previo && previo !== i);
        s.setAttribute("aria-hidden", k === i ? "false" : "true");
      });
      /* Cada lámina trae su fotografía. Si hay menos fotos que láminas —porque
         el marcado sea el antiguo, con una sola— no se toca nada y el fondo se
         queda fijo, que es como se comportaba antes. */
      if (fotos.length === slides.length) {
        fotos.forEach(function (f, k) { f.classList.toggle("is-active", k === i); });
      }
      dots.forEach(function (d, k) {
        d.classList.toggle("is-active", k === i);
        d.setAttribute("aria-selected", k === i ? "true" : "false");
        if (k === i) reiniciarCuenta(d);
      });
    }
    function play()  { stop(); if (!quieto) timer = setInterval(function () { go(i + 1, 1); }, DELAY); }
    function stop()  { if (timer) { clearInterval(timer); timer = null; } }

    /* Al mover a mano se vuelve a contar desde cero: nadie pierde la lámina que
       acaba de pedir porque al temporizador le quedaba medio segundo. */
    function irA(n, sentido) {
      go(n, sentido);
      if (!enPausa) play();
    }

    /* El cursor encima y el foco dentro paran la cuenta. Se guardan por separado
       porque se solapan: sacar el ratón mientras el foco sigue en una flecha no
       tiene que reanudar nada. */
    var raton = false, foco = false, enPausa = false;
    function revisarPausa() {
      var debe = raton || foco;
      if (debe === enPausa) return;
      enPausa = debe;
      slider.classList.toggle("esta-en-pausa", debe);
      if (debe) { stop(); } else { reiniciarCuenta(dots[i]); play(); }
    }

    dots.forEach(function (d, k) {
      d.addEventListener("click", function () { if (k !== i) irA(k, k > i ? 1 : -1); });
    });
    if (btnPrev) btnPrev.addEventListener("click", function () { irA(i - 1, -1); });
    if (btnNext) btnNext.addEventListener("click", function () { irA(i + 1, 1); });

    slider.addEventListener("mouseenter", function () { raton = true;  revisarPausa(); });
    slider.addEventListener("mouseleave", function () { raton = false; revisarPausa(); });
    slider.addEventListener("focusin",    function () { foco  = true;  revisarPausa(); });
    /* Al tabular de una flecha a una marca, «focusout» salta antes que el
       «focusin» siguiente: sin mirar a dónde va el foco, la cuenta se reanudaría
       y volvería a pararse en el mismo golpe de tecla, y la barra parpadearía. */
    slider.addEventListener("focusout", function (e) {
      if (e.relatedTarget && slider.contains(e.relatedTarget)) return;
      foco = false; revisarPausa();
    });

    slider.addEventListener("keydown", function (e) {
      if (e.key === "ArrowLeft")  { e.preventDefault(); irA(i - 1, -1); }
      if (e.key === "ArrowRight") { e.preventDefault(); irA(i + 1, 1); }
    });

    /* Arrastre lateral en táctil. Se descarta si el recorrido vertical manda:
       eso es alguien haciendo scroll, no cambiando de lámina. */
    var x0 = null, y0 = null;
    slider.addEventListener("pointerdown", function (e) {
      if (e.pointerType === "mouse") return;
      x0 = e.clientX; y0 = e.clientY;
    });
    slider.addEventListener("pointerup", function (e) {
      if (x0 === null) return;
      var dx = e.clientX - x0, dy = e.clientY - y0;
      x0 = null;
      if (Math.abs(dx) < 45 || Math.abs(dx) <= Math.abs(dy)) return;
      irA(dx < 0 ? i + 1 : i - 1, dx < 0 ? 1 : -1);
    });
    slider.addEventListener("pointercancel", function () { x0 = null; });

    go(0, 0);
    play();
  }

  /* --------------------------------------------- Vídeos (carga diferida) */
  function initVideos() {
    $$("[data-video]").forEach(function (card) {
      card.addEventListener("click", function () {
        var id = card.getAttribute("data-video");
        if (!id) return;
        var wrap = doc.createElement("div");
        wrap.style.cssText = "position:absolute;inset:0";
        wrap.innerHTML =
          '<iframe style="width:100%;height:100%;border:0" src="https://www.youtube-nocookie.com/embed/' +
          encodeURIComponent(id) + '?autoplay=1&rel=0" title="Vídeo" allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen></iframe>';
        card.innerHTML = "";
        card.appendChild(wrap);
      });
    });
  }

  /* ------------------------------------------------------------ Lightbox */
  function initLightbox() {
    var items = $$("[data-lightbox]");
    if (!items.length) return;

    var box = doc.createElement("div");
    box.className = "lightbox";
    box.setAttribute("role", "dialog");
    box.setAttribute("aria-modal", "true");
    box.hidden = true;
    box.innerHTML =
      '<button class="lightbox__close" type="button" aria-label="Cerrar">&times;</button>' +
      '<img class="lightbox__img" alt="">';
    doc.body.appendChild(box);

    var img = $(".lightbox__img", box);
    var last = null;

    function open(src, alt) {
      img.src = src; img.alt = alt || "";
      box.hidden = false;
      doc.body.style.overflow = "hidden";
      $(".lightbox__close", box).focus();
    }
    function close() {
      box.hidden = true; img.src = "";
      doc.body.style.overflow = "";
      if (last) last.focus();
    }

    items.forEach(function (el) {
      el.addEventListener("click", function () {
        last = el;
        var pic = el.querySelector("img");
        open(el.getAttribute("data-lightbox") || (pic && pic.currentSrc) || (pic && pic.src), pic && pic.alt);
      });
    });
    box.addEventListener("click", function (e) {
      if (e.target === box || e.target.closest(".lightbox__close")) close();
    });
    doc.addEventListener("keydown", function (e) { if (e.key === "Escape" && !box.hidden) close(); });
  }

  /* -------------------------------------------------- Formulario contacto */
  function initForms() {
    $$("form[data-validate]").forEach(function (form) {
      form.setAttribute("novalidate", "");
      form.addEventListener("submit", function (e) {
        var ok = true;
        $$("[required]", form).forEach(function (field) {
          var valid = field.type === "checkbox" ? field.checked : String(field.value).trim() !== "";
          if (valid && field.type === "email") valid = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(field.value);
          field.setAttribute("aria-invalid", valid ? "false" : "true");
          var msg = field.closest(".field, .check");
          if (msg) msg.classList.toggle("has-error", !valid);
          if (!valid && ok) { field.focus(); ok = false; }
        });
        if (!ok) { e.preventDefault(); return; }
        /* Y ya está: el formulario se envía de verdad.
           Aquí había un e.preventDefault() seguido de «Gracias. Hemos recibido
           tu mensaje» y un form.reset(). Es decir: se paraba el envío, se le
           decía a la persona que su mensaje había llegado y se le borraba lo
           escrito. No llegaba a ninguna parte. Ahora el POST va al servidor,
           que lo manda por la API de correo y responde con lo que de verdad
           haya pasado. */
      });
    });
  }

  /* --------------------------------------- Revelado suave al hacer scroll */
  function initReveal() {
    if (!("IntersectionObserver" in window)) return;
    if (window.matchMedia("(prefers-reduced-motion:reduce)").matches) return;
    var els = $$("[data-reveal]");
    if (!els.length) return;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add("is-visible"); io.unobserve(e.target); }
      });
    }, { rootMargin: "0px 0px -8% 0px", threshold: .08 });
    els.forEach(function (el) { io.observe(el); });
  }

  /* ---------------------------------------------------------------- Init */
  function boot() {
    initNav();
    initStickyHeader();
    initCountdown();
    initAccordions();
    initFilters();
    initHeroSlider();
    initVideos();
    initLightbox();
    initForms();
    initReveal();
  }

  if (doc.readyState === "loading") doc.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
