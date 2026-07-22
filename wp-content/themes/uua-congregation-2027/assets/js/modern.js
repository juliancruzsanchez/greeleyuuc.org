/**
 * UUCG Warm Modern — subtle interactions
 * Scroll reveals, sticky glass state, soft card depth.
 * Respects prefers-reduced-motion.
 */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  /* ------------------------------------------------------------------ */
  /* Sticky header / scrolled glass state                               */
  /* ------------------------------------------------------------------ */
  function initScrollState() {
    var body = document.body;
    var threshold = 24;
    var ticking = false;

    function update() {
      if (window.scrollY > threshold) {
        body.classList.add('is-scrolled');
      } else {
        body.classList.remove('is-scrolled');
      }
      ticking = false;
    }

    window.addEventListener(
      'scroll',
      function () {
        if (!ticking) {
          window.requestAnimationFrame(update);
          ticking = true;
        }
      },
      { passive: true }
    );

    update();
  }

  /* ------------------------------------------------------------------ */
  /* Scroll reveal                                                      */
  /* ------------------------------------------------------------------ */
  function initReveals() {
    if (reduceMotion || !('IntersectionObserver' in window)) {
      return;
    }

    var selectors = [
      '.thumbnail',
      '.uuafbw_widget',
      '.home-widget-2',
      '.upcomingservice_widget',
      '.widget',
      'article.post',
      'article.type-post',
      '.hentry',
      '.page-header',
      '.metaslider',
      '.home-featured',
      '.mission1',
      '.home-btm-text',
      '.testimonial-entry',
      '.widget_woothemes_testimonials .quote',
      '.uu-service-list article',
      '.footer-widgets .widget',
      '.affiliation-logos',
      '.content .entry-content > *',
      '#primary > .site-main > *'
    ];

    var nodes = document.querySelectorAll(selectors.join(','));
    if (!nodes.length) {
      return;
    }

    // Avoid double-wrapping nested matches: only top-level-ish elements
    var seen = new WeakSet();
    var targets = [];

    nodes.forEach(function (el) {
      if (seen.has(el)) {
        return;
      }
      // Skip tiny text nodes wrappers deep in content that would flash too much
      if (el.closest('.navbar') || el.closest('.masthead-header') || el.closest('.site-notice')) {
        return;
      }
      // Full-page plugin shells (In the Loop, Worship Schedule) are page body, not cards
      if (el.matches('.uucg-no-title-page')) {
        return;
      }
      // Don't reveal every paragraph inside posts — only direct children blocks already selected
      if (el.matches('.content .entry-content > p, .content .entry-content > ul, .content .entry-content > ol')) {
        // allow a few early content blocks only
      }
      el.classList.add('uucg-reveal');
      seen.add(el);
      targets.push(el);
    });

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      },
      {
        root: null,
        rootMargin: '0px 0px -8% 0px',
        threshold: 0.08
      }
    );

    targets.forEach(function (el) {
      var rect = el.getBoundingClientRect();
      // Above-the-fold: show immediately so nothing flashes blank
      if (rect.top < window.innerHeight * 0.95 && rect.bottom > 0) {
        el.classList.add('is-visible');
      } else {
        observer.observe(el);
      }
    });
  }

  /* ------------------------------------------------------------------ */
  /* Soft 3D tilt on feature cards (pointer only, subtle)               */
  /* ------------------------------------------------------------------ */
  function initCardTilt() {
    if (reduceMotion || window.matchMedia('(hover: none)').matches) {
      return;
    }

    var cards = document.querySelectorAll('.thumbnail, .uuafbw_widget .thumbnail, .home-widget-2');
    if (!cards.length) {
      return;
    }

    cards.forEach(function (card) {
      var max = 4; // degrees — keep gentle

      card.addEventListener('pointermove', function (e) {
        var rect = card.getBoundingClientRect();
        var x = (e.clientX - rect.left) / rect.width;
        var y = (e.clientY - rect.top) / rect.height;
        var rotY = (x - 0.5) * max * 2;
        var rotX = (0.5 - y) * max * 2;

        card.style.transform =
          'translateY(-6px) rotateX(' + rotX.toFixed(2) + 'deg) rotateY(' + rotY.toFixed(2) + 'deg) scale(1.01)';
      });

      card.addEventListener('pointerleave', function () {
        card.style.transform = '';
      });
    });
  }

  /* ------------------------------------------------------------------ */
  /* Smooth internal anchor scroll (native fallback already on html)    */
  /* ------------------------------------------------------------------ */
  function initAnchors() {
    if (reduceMotion) {
      return;
    }

    document.addEventListener('click', function (e) {
      var link = e.target.closest('a[href^="#"]');
      if (!link) {
        return;
      }
      var id = link.getAttribute('href');
      if (!id || id === '#' || id.length < 2) {
        return;
      }
      var target = document.querySelector(id);
      if (!target) {
        return;
      }
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      if (history.pushState) {
        history.pushState(null, '', id);
      }
    });
  }

  /* ------------------------------------------------------------------ */
  /* Mobile right drawer                                                */
  /* ------------------------------------------------------------------ */
  function initDrawer() {
    var body = document.body;
    var openBtns = document.querySelectorAll('[data-uucg-drawer-open]');
    var closeEls = document.querySelectorAll('[data-uucg-drawer-close]');
    var drawer = document.getElementById('uucg-drawer');
    if (!drawer || !openBtns.length) {
      return;
    }

    var lastFocus = null;

    function isOpen() {
      return body.classList.contains('uucg-drawer-open');
    }

    function setExpanded(open) {
      openBtns.forEach(function (btn) {
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.classList.toggle('is-open', open);
      });
    }

    function openDrawer() {
      lastFocus = document.activeElement;
      body.classList.add('uucg-drawer-open');
      drawer.setAttribute('aria-hidden', 'false');
      setExpanded(true);
      var closeBtn = drawer.querySelector('.uucg-drawer-close');
      if (closeBtn) {
        window.setTimeout(function () {
          closeBtn.focus();
        }, 50);
      }
    }

    function closeDrawer() {
      body.classList.remove('uucg-drawer-open');
      drawer.setAttribute('aria-hidden', 'true');
      setExpanded(false);
      if (lastFocus && typeof lastFocus.focus === 'function') {
        lastFocus.focus();
      }
    }

    function toggleDrawer() {
      if (isOpen()) {
        closeDrawer();
      } else {
        openDrawer();
      }
    }

    openBtns.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        toggleDrawer();
      });
    });

    closeEls.forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        closeDrawer();
      });
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isOpen()) {
        closeDrawer();
      }
    });

    // Close after choosing a real destination (keep dropdown toggles open)
    drawer.addEventListener('click', function (e) {
      var link = e.target.closest('a[href]');
      if (!link) {
        return;
      }
      if (link.classList.contains('dropdown-toggle')) {
        return;
      }
      var href = link.getAttribute('href');
      if (href && href !== '#') {
        closeDrawer();
      }
    });

    // If resized to desktop, force close
    var mq = window.matchMedia('(min-width: 992px)');
    function onBp(e) {
      if (e.matches && isOpen()) {
        closeDrawer();
      }
    }
    if (mq.addEventListener) {
      mq.addEventListener('change', onBp);
    } else if (mq.addListener) {
      mq.addListener(onBp);
    }
  }

  onReady(function () {
    document.documentElement.classList.add('uucg-js');
    initScrollState();
    initReveals();
    initCardTilt();
    initAnchors();
    initDrawer();
  });
})();
