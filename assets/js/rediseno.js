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
    var dots = $$("[data-slide-dot]", slider);
    if (slides.length < 2) return;
    var i = 0, timer = null;
    var DELAY = 7000;

    function go(n) {
      i = (n + slides.length) % slides.length;
      slides.forEach(function (s, k) {
        s.classList.toggle("is-active", k === i);
        s.setAttribute("aria-hidden", k === i ? "false" : "true");
      });
      dots.forEach(function (d, k) {
        d.classList.toggle("is-active", k === i);
        d.setAttribute("aria-selected", k === i ? "true" : "false");
      });
    }
    function play()  { stop(); timer = setInterval(function () { go(i + 1); }, DELAY); }
    function stop()  { if (timer) { clearInterval(timer); timer = null; } }

    dots.forEach(function (d, k) {
      d.addEventListener("click", function () { go(k); play(); });
    });
    slider.addEventListener("mouseenter", stop);
    slider.addEventListener("mouseleave", play);
    go(0);
    if (!window.matchMedia("(prefers-reduced-motion:reduce)").matches) play();
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
        e.preventDefault();
        var note = $("[data-form-note]", form);
        if (note) { note.hidden = false; note.textContent = "Gracias. Hemos recibido tu mensaje."; }
        form.reset();
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
