/**
 * UUCG Newsletter — AJAX signup
 */
(function () {
  'use strict';

  function qs(el, sel) {
    return el.querySelector(sel);
  }

  function bindForm(form) {
    if (form.getAttribute('data-uucg-nl-bound')) {
      return;
    }
    form.setAttribute('data-uucg-nl-bound', '1');

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (typeof uucgNl === 'undefined') {
        return;
      }

      var emailInput = qs(form, 'input[name="email"]');
      var honeypot = qs(form, 'input[name="website"]');
      var status = qs(form, '[data-uucg-nl-status]');
      var btn = qs(form, '.uucg-nl__btn');
      var btnLabel = qs(form, '.uucg-nl__btn-label');
      var original = btnLabel ? btnLabel.textContent : '';

      var email = emailInput ? emailInput.value.trim() : '';
      if (!email) {
        showStatus(status, uucgNl.i18n.error || 'Please enter your email.', 'error');
        return;
      }

      setLoading(btn, btnLabel, true, uucgNl.i18n.sending);
      hideStatus(status);

      var body = new FormData();
      body.append('action', 'uucg_nl_subscribe');
      body.append('nonce', uucgNl.nonce);
      body.append('email', email);
      body.append('website', honeypot ? honeypot.value : '');

      fetch(uucgNl.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: body,
      })
        .then(function (res) {
          return res.json().then(function (json) {
            return { ok: res.ok, json: json };
          });
        })
        .then(function (result) {
          setLoading(btn, btnLabel, false, original);
          if (result.json && result.json.success) {
            showStatus(
              status,
              (result.json.data && result.json.data.message) || 'Thank you!',
              'success'
            );
            form.reset();
          } else {
            var msg =
              (result.json &&
                result.json.data &&
                result.json.data.message) ||
              uucgNl.i18n.error;
            showStatus(status, msg, 'error');
          }
        })
        .catch(function () {
          setLoading(btn, btnLabel, false, original);
          showStatus(status, uucgNl.i18n.error, 'error');
        });
    });
  }

  function setLoading(btn, labelEl, loading, text) {
    if (!btn) {
      return;
    }
    btn.disabled = !!loading;
    if (labelEl && text) {
      labelEl.textContent = text;
    }
  }

  function showStatus(el, message, type) {
    if (!el) {
      return;
    }
    el.hidden = false;
    el.textContent = message;
    el.classList.remove('is-success', 'is-error');
    el.classList.add(type === 'success' ? 'is-success' : 'is-error');
  }

  function hideStatus(el) {
    if (!el) {
      return;
    }
    el.hidden = true;
    el.textContent = '';
    el.classList.remove('is-success', 'is-error');
  }

  function init() {
    document.querySelectorAll('[data-uucg-nl-form]').forEach(bindForm);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
