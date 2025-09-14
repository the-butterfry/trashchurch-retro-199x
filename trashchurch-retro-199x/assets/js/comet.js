(function () {
  'use strict';

  // Config from PHP
  var cfg = window.TR199X_COMET || {};
  var COUNT  = Math.max(1, Math.min(100, parseInt(cfg.count || 10, 10)));
  var SMOOTH = Math.max(0.01, Math.min(0.98, parseFloat(cfg.smooth || 0.25)));
  var IMG    = String(cfg.image || '');
  var SIZE   = Math.max(4, Math.min(256, parseInt(cfg.size || 40, 10))); // 40x40 default
  var FADE   = !!cfg.fade;
  var ALLOW_COARSE = !!cfg.allowCoarse;

  try {
    // Skip on coarse pointers unless allowed
    if (!ALLOW_COARSE) {
      var mm = window.matchMedia && window.matchMedia('(pointer: coarse)');
      if (mm && mm.matches) return;
    }

    // Skip if no image configured
    if (!IMG) return;

    var dots = [];
    var pos = { x: window.innerWidth / 2, y: window.innerHeight / 2 };
    var target = { x: pos.x, y: pos.y };

    // Container to keep z-index sane
    var container = document.createElement('div');
    container.style.position = 'fixed';
    container.style.left = '0';
    container.style.top = '0';
    container.style.width = '100%';
    container.style.height = '100%';
    container.style.pointerEvents = 'none';
    container.style.zIndex = '2147483647'; // on top
    document.addEventListener('DOMContentLoaded', function () {
      document.body.appendChild(container);
    });

    // Create trail elements
    for (var i = 0; i < COUNT; i++) {
      var el = document.createElement('div');
      el.className = 'tr-comet-dot';
      // Visuals
      el.style.position = 'fixed';
      el.style.width = SIZE + 'px';
      el.style.height = SIZE + 'px';
      el.style.transform = 'translate(-50%, -50%)';
      el.style.backgroundImage = 'url("' + IMG + '")';
      el.style.backgroundRepeat = 'no-repeat';
      el.style.backgroundSize = 'cover';
      el.style.willChange = 'transform, opacity';
      el.style.pointerEvents = 'none';
      el.style.userSelect = 'none';

      // Fading tail
      if (FADE) {
        var alpha = 1 - (i / COUNT);
        el.style.opacity = String(Math.max(0.15, alpha));
      }

      container.appendChild(el);
      dots.push({
        el: el,
        x: pos.x,
        y: pos.y
      });
    }

    // Track pointer
    function onMove(ev) {
      if (ev.touches && ev.touches[0]) {
        target.x = ev.touches[0].clientX;
        target.y = ev.touches[0].clientY;
      } else {
        target.x = ev.clientX;
        target.y = ev.clientY;
      }
    }
    window.addEventListener('mousemove', onMove, { passive: true });
    window.addEventListener('touchmove', onMove, { passive: true });

    // Animate
    function tick() {
      // Ease head toward target
      pos.x += (target.x - pos.x) * SMOOTH;
      pos.y += (target.y - pos.y) * SMOOTH;

      // Place the first dot at head
      if (dots[0]) {
        dots[0].x += (pos.x - dots[0].x) * SMOOTH;
        dots[0].y += (pos.y - dots[0].y) * SMOOTH;
        dots[0].el.style.left = dots[0].x + 'px';
        dots[0].el.style.top  = dots[0].y + 'px';
      }

      // Each subsequent dot follows the previous
      for (var i = 1; i < dots.length; i++) {
        var prev = dots[i - 1];
        var d = dots[i];
        d.x += (prev.x - d.x) * SMOOTH;
        d.y += (prev.y - d.y) * SMOOTH;
        d.el.style.left = d.x + 'px';
        d.el.style.top  = d.y + 'px';
      }

      requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);

    // Handle resize to keep head sane
    window.addEventListener('resize', function () {
      target.x = Math.max(0, Math.min(window.innerWidth, target.x));
      target.y = Math.max(0, Math.min(window.innerHeight, target.y));
    }, { passive: true });

  } catch (e) {
    if (window.console && console.warn) console.warn('Comet init failed:', e);
  }
})();
