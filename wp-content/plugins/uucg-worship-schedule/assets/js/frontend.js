/**
 * UUCG Worship Schedule — view toggle, filters, calendar nav, drawer.
 */
(function () {
  'use strict';

  function qs(root, sel) {
    return root.querySelector(sel);
  }

  function qsa(root, sel) {
    return Array.prototype.slice.call(root.querySelectorAll(sel));
  }

  function parseData(root) {
    var el = qs(root, '.uucg-ws__data');
    if (!el) {
      return { services: {}, months: [] };
    }
    try {
      return JSON.parse(el.textContent);
    } catch (e) {
      return { services: {}, months: [] };
    }
  }

  function setView(root, view) {
    qsa(root, '.uucg-ws__view-btn').forEach(function (btn) {
      var active = btn.getAttribute('data-view') === view;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    qsa(root, '.uucg-ws__panel').forEach(function (panel) {
      var active = panel.getAttribute('data-panel') === view;
      panel.classList.toggle('is-active', active);
      if (active) {
        panel.removeAttribute('hidden');
      } else {
        panel.setAttribute('hidden', '');
      }
    });
  }

  function applyFilters(root) {
    var quarterSel = qs(root, 'select[data-filter="quarter"]');
    var celebrantSel = qs(root, 'select[data-filter="celebrant"]');
    var quarter = (quarterSel && quarterSel.value) || root._uucgWsQuarter || 'all';
    var celebrant = (celebrantSel && celebrantSel.value) || root._uucgWsCelebrant || 'all';

    root._uucgWsQuarter = quarter;
    root._uucgWsCelebrant = celebrant;

    qsa(root, '.uucg-ws-card, .uucg-ws__day--service').forEach(function (el) {
      var q = el.getAttribute('data-quarter') || '';
      var c = el.getAttribute('data-celebrant') || '';
      var show =
        (quarter === 'all' || q === quarter) &&
        (celebrant === 'all' || c === celebrant);
      el.classList.toggle('is-filtered-out', !show);
    });

    updateSeasonHeadings(root);
    updateFilterEmpty(root);
  }

  /** Hide season headings when no visible cards remain under them. */
  function updateSeasonHeadings(root) {
    var list = qs(root, '.uucg-ws__list');
    if (!list) {
      return;
    }
    var kids = Array.prototype.slice.call(list.children);
    for (var i = 0; i < kids.length; i++) {
      if (!kids[i].classList.contains('uucg-ws__season-heading')) {
        continue;
      }
      var hasVisible = false;
      for (var j = i + 1; j < kids.length; j++) {
        if (kids[j].classList.contains('uucg-ws__season-heading')) {
          break;
        }
        if (
          kids[j].classList.contains('uucg-ws-card') &&
          !kids[j].classList.contains('is-filtered-out')
        ) {
          hasVisible = true;
          break;
        }
      }
      kids[i].classList.toggle('is-filtered-out', !hasVisible);
    }
  }

  function updateFilterEmpty(root) {
    var empty = qs(root, '[data-filter-empty]');
    if (!empty) {
      return;
    }
    var cards = qsa(root, '.uucg-ws-card');
    if (!cards.length) {
      empty.setAttribute('hidden', '');
      return;
    }
    var anyVisible = cards.some(function (card) {
      return !card.classList.contains('is-filtered-out');
    });
    if (anyVisible) {
      empty.setAttribute('hidden', '');
    } else {
      empty.removeAttribute('hidden');
    }
  }

  function showMonth(root, monthKey) {
    var months = qsa(root, '.uucg-ws__month');
    var keys = months.map(function (m) {
      return m.getAttribute('data-month');
    });
    var idx = keys.indexOf(monthKey);
    if (idx < 0 && keys.length) {
      idx = 0;
      monthKey = keys[0];
    }

    months.forEach(function (m, i) {
      if (i === idx) {
        m.removeAttribute('hidden');
      } else {
        m.setAttribute('hidden', '');
      }
    });

    var title = qs(root, '[data-cal-title]');
    var active = months[idx];
    if (title && active) {
      title.textContent = active.getAttribute('data-month-label') || monthKey;
    }

    var prev = qs(root, '[data-cal-nav="prev"]');
    var next = qs(root, '[data-cal-nav="next"]');
    if (prev) {
      prev.disabled = idx <= 0;
    }
    if (next) {
      next.disabled = idx >= keys.length - 1;
    }

    root._uucgWsMonthIdx = idx;
    root._uucgWsMonthKeys = keys;
  }

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function drawerHtml(svc) {
    if (!svc) {
      return '';
    }
    var topic = svc.topic || 'Worship service';
    var celebrant = svc.celebrant || 'To be announced';
    var chips = '';
    if (svc.quarter_label) {
      chips +=
        '<span class="uucg-ws-card__chip uucg-ws-card__chip--' +
        escapeHtml(svc.quarter) +
        '">' +
        escapeHtml(svc.quarter_label) +
        '</span>';
    }
    if (svc.holidays) {
      chips +=
        '<span class="uucg-ws-card__chip uucg-ws-card__chip--holiday">' +
        escapeHtml(svc.holidays) +
        '</span>';
    }

    var associate = '';
    if (svc.associate) {
      associate =
        '<div class="uucg-ws-card__person"><span class="uucg-ws-card__role">Worship Associate</span><span class="uucg-ws-card__name">' +
        escapeHtml(svc.associate) +
        '</span></div>';
    }

    var summary = svc.summary
      ? '<p class="uucg-ws-card__summary">' + escapeHtml(svc.summary) + '</p>'
      : '';

    var day = (svc.date || '').slice(8, 10).replace(/^0/, '');
    var month = svc.date_short ? String(svc.date_short).split(' ')[0] : '';
    var weekday = svc.weekday || '';

    return (
      '<article class="uucg-ws-card uucg-ws-card--' +
      escapeHtml(svc.quarter || 'general') +
      '">' +
      '<div class="uucg-ws-card__date">' +
      '<span class="uucg-ws-card__weekday">' +
      escapeHtml(weekday) +
      '</span>' +
      '<span class="uucg-ws-card__day">' +
      escapeHtml(day) +
      '</span>' +
      '<span class="uucg-ws-card__month">' +
      escapeHtml(month) +
      '</span>' +
      '</div>' +
      '<div class="uucg-ws-card__body">' +
      '<div class="uucg-ws-card__meta">' +
      chips +
      '</div>' +
      '<h3 class="uucg-ws-card__topic" id="drawer-title">' +
      escapeHtml(topic) +
      '</h3>' +
      '<p class="uucg-ws-card__summary" style="margin-top:0;margin-bottom:0.5rem">' +
      escapeHtml(svc.date_display || '') +
      '</p>' +
      '<div class="uucg-ws-card__people">' +
      '<div class="uucg-ws-card__person"><span class="uucg-ws-card__role">Celebrant</span><span class="uucg-ws-card__name">' +
      escapeHtml(celebrant) +
      '</span></div>' +
      associate +
      '</div>' +
      summary +
      '</div></article>'
    );
  }

  function openDrawer(root, date) {
    var data = root._uucgWsData || parseData(root);
    var svc = data.services && data.services[date];
    var drawer = qs(root, '[data-drawer]');
    var content = qs(root, '[data-drawer-content]');
    if (!drawer || !content || !svc) {
      return;
    }
    content.innerHTML = drawerHtml(svc);
    drawer.removeAttribute('hidden');
    document.documentElement.style.overflow = 'hidden';
    var closeBtn = qs(drawer, '[data-drawer-close]');
    if (closeBtn) {
      closeBtn.focus();
    }
  }

  function closeDrawer(root) {
    var drawer = qs(root, '[data-drawer]');
    if (!drawer) {
      return;
    }
    drawer.setAttribute('hidden', '');
    document.documentElement.style.overflow = '';
  }

  function pickInitialMonth(months) {
    if (!months || !months.length) {
      return null;
    }
    var now = new Date();
    var key =
      now.getFullYear() +
      '-' +
      String(now.getMonth() + 1).padStart(2, '0');
    if (months.indexOf(key) !== -1) {
      return key;
    }
    // Next upcoming month with services.
    for (var i = 0; i < months.length; i++) {
      if (months[i] >= key) {
        return months[i];
      }
    }
    return months[months.length - 1];
  }

  function initRoot(root) {
    var data = parseData(root);
    root._uucgWsData = data;

    var defaultView = root.getAttribute('data-default-view') || 'list';
    setView(root, defaultView);

    root._uucgWsQuarter = 'all';
    root._uucgWsCelebrant = 'all';

    // View toggle
    qsa(root, '.uucg-ws__view-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        setView(root, btn.getAttribute('data-view'));
      });
    });

    // Season / celebrant dropdowns
    qsa(root, 'select[data-filter]').forEach(function (sel) {
      sel.addEventListener('change', function () {
        applyFilters(root);
      });
    });

    // Calendar months
    var months = data.months && data.months.length
      ? data.months
      : qsa(root, '.uucg-ws__month').map(function (m) {
          return m.getAttribute('data-month');
        });
    var initial = pickInitialMonth(months);
    if (initial) {
      showMonth(root, initial);
    }

    qsa(root, '[data-cal-nav]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var keys = root._uucgWsMonthKeys || months;
        var idx = typeof root._uucgWsMonthIdx === 'number' ? root._uucgWsMonthIdx : 0;
        if (btn.getAttribute('data-cal-nav') === 'prev') {
          idx = Math.max(0, idx - 1);
        } else {
          idx = Math.min(keys.length - 1, idx + 1);
        }
        if (keys[idx]) {
          showMonth(root, keys[idx]);
        }
      });
    });

    // Day open → drawer
    root.addEventListener('click', function (e) {
      var openBtn = e.target.closest('[data-open-date]');
      if (openBtn && root.contains(openBtn)) {
        e.preventDefault();
        openDrawer(root, openBtn.getAttribute('data-open-date'));
        return;
      }
      if (e.target.closest('[data-drawer-close]')) {
        closeDrawer(root);
      }
    });

    root.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closeDrawer(root);
      }
      var day = e.target.closest('.uucg-ws__day--service');
      if (day && root.contains(day) && (e.key === 'Enter' || e.key === ' ')) {
        e.preventDefault();
        openDrawer(root, day.getAttribute('data-date'));
      }
    });
  }

  function init() {
    qsa(document, '.uucg-ws').forEach(initRoot);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
