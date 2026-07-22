/**
 * UUCG In the Loop — tabs + lazy platform SDK loaders.
 */
(function () {
  'use strict';

  var SDK = {
    tiktok: false,
    instagram: false,
    facebook: false,
    x: false,
  };

  function loadScript(src, id, onload) {
    if (id && document.getElementById(id)) {
      if (typeof onload === 'function') {
        onload();
      }
      return;
    }
    var s = document.createElement('script');
    s.src = src;
    s.async = true;
    if (id) {
      s.id = id;
    }
    if (typeof onload === 'function') {
      s.onload = onload;
    }
    document.body.appendChild(s);
  }

  function lockTikTokScroll(root) {
    var scope = root || document;
    scope.querySelectorAll('.uucg-itl-card--tiktok iframe, .uucg-itl-tiktok-iframe').forEach(function (frame) {
      frame.setAttribute('scrolling', 'no');
      frame.style.overflow = 'hidden';
    });
  }

  function loadTikTok() {
    if (SDK.tiktok) {
      lockTikTokScroll();
      return;
    }
    SDK.tiktok = true;
    loadScript('https://www.tiktok.com/embed.js', 'uucg-itl-tiktok-sdk', function () {
      // Embed.js may inject iframes shortly after load.
      lockTikTokScroll();
      setTimeout(function () {
        lockTikTokScroll();
      }, 800);
      setTimeout(function () {
        lockTikTokScroll();
      }, 2000);
    });
    // Direct iframe embeds (no embed.js) still get the attribute now.
    lockTikTokScroll();
  }

  function loadFacebook() {
    if (SDK.facebook) {
      if (window.FB && typeof window.FB.XFBML !== 'undefined') {
        window.FB.XFBML.parse();
      }
      return;
    }
    SDK.facebook = true;

    window.fbAsyncInit = function () {
      if (window.FB) {
        window.FB.init({
          xfbml: true,
          version: 'v21.0',
        });
      }
    };

    loadScript(
      'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v21.0',
      'facebook-jssdk',
      function () {
        if (window.FB && window.FB.XFBML) {
          window.FB.XFBML.parse();
        }
      }
    );
  }

  function loadInstagram() {
    if (SDK.instagram) {
      if (window.instgrm && window.instgrm.Embeds && typeof window.instgrm.Embeds.process === 'function') {
        window.instgrm.Embeds.process();
      }
      return;
    }
    SDK.instagram = true;
    loadScript('https://www.instagram.com/embed.js', 'uucg-itl-instagram-sdk', function () {
      if (window.instgrm && window.instgrm.Embeds && typeof window.instgrm.Embeds.process === 'function') {
        window.instgrm.Embeds.process();
      }
    });
  }

  function loadX() {
    if (SDK.x) {
      if (window.twttr && window.twttr.widgets) {
        window.twttr.widgets.load();
      }
      return;
    }
    SDK.x = true;
    loadScript('https://platform.twitter.com/widgets.js', 'uucg-itl-x-sdk', function () {
      if (window.twttr && window.twttr.widgets) {
        window.twttr.widgets.load();
      }
    });
  }

  function loadPlatform(platform) {
    if (platform === 'tiktok') {
      loadTikTok();
    } else if (platform === 'instagram') {
      loadInstagram();
    } else if (platform === 'facebook') {
      loadFacebook();
    } else if (platform === 'x') {
      loadX();
    }
  }

  function activateTab(root, tabKey, focusTab) {
    var tabs = root.querySelectorAll('.uucg-itl__tab');
    var panels = root.querySelectorAll('.uucg-itl__panel');
    var activeTab = null;

    tabs.forEach(function (tab) {
      var isActive = tab.getAttribute('data-tab') === tabKey;
      tab.classList.toggle('is-active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      tab.setAttribute('tabindex', isActive ? '0' : '-1');
      if (isActive) {
        activeTab = tab;
      }
    });

    panels.forEach(function (panel) {
      var isActive = panel.getAttribute('data-platform') === tabKey;
      panel.classList.toggle('is-active', isActive);
      if (isActive) {
        panel.removeAttribute('hidden');
      } else {
        panel.setAttribute('hidden', '');
      }
    });

    loadPlatform(tabKey);
    if (tabKey === 'tiktok') {
      lockTikTokScroll(root);
    }

    if (focusTab && activeTab) {
      activeTab.focus();
    }
  }

  function bindTabs(root) {
    var tablist = root.querySelector('.uucg-itl__tabs');
    if (!tablist) {
      return;
    }

    var tabs = Array.prototype.slice.call(root.querySelectorAll('.uucg-itl__tab'));

    tablist.addEventListener('click', function (e) {
      var tab = e.target.closest('.uucg-itl__tab');
      if (!tab || !root.contains(tab)) {
        return;
      }
      activateTab(root, tab.getAttribute('data-tab'), false);
    });

    tablist.addEventListener('keydown', function (e) {
      var current = document.activeElement;
      if (!current || !current.classList.contains('uucg-itl__tab')) {
        return;
      }

      var idx = tabs.indexOf(current);
      if (idx < 0) {
        return;
      }

      var next = idx;
      if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
        next = (idx + 1) % tabs.length;
        e.preventDefault();
      } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
        next = (idx - 1 + tabs.length) % tabs.length;
        e.preventDefault();
      } else if (e.key === 'Home') {
        next = 0;
        e.preventDefault();
      } else if (e.key === 'End') {
        next = tabs.length - 1;
        e.preventDefault();
      } else {
        return;
      }

      activateTab(root, tabs[next].getAttribute('data-tab'), true);
    });
  }

  function initRoot(root) {
    var defaultTab = root.getAttribute('data-default-tab') || 'tiktok';
    bindTabs(root);
    activateTab(root, defaultTab, false);
  }

  function init() {
    document.querySelectorAll('.uucg-itl').forEach(initRoot);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
