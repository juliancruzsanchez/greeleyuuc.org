/**
 * UUCG Pledge Calculator
 * Progressive giving guide based on UUA-style income brackets.
 */
(function () {
  'use strict';

  /**
   * Reference rows from the congregation giving guide.
   * Percentages are suggested share of adjusted monthly income.
   * s = Supporter, u = Sustainer, v = Visionary, t = Transformer
   */
  var ROWS = [
    { m: 1000, s: 2, u: 3, v: 5, t: 10 },
    { m: 1500, s: 2, u: 3, v: 5, t: 10 },
    { m: 2000, s: 2, u: 3, v: 5, t: 10 },
    { m: 3000, s: 2, u: 3, v: 5, t: 10 },
    { m: 4000, s: 3, u: 4, v: 5, t: 10 },
    { m: 6500, s: 3, u: 4, v: 6, t: 10 },
    { m: 8500, s: 3, u: 5, v: 6, t: 10 },
    { m: 10000, s: 3, u: 5, v: 6, t: 10 },
    { m: 12500, s: 4, u: 5, v: 6, t: 10 },
    { m: 17000, s: 4, u: 6, v: 7, t: 10 },
    { m: 25000, s: 5, u: 6, v: 8, t: 10 },
    { m: 40000, s: 6, u: 7, v: 9, t: 10 }
  ];

  var TIERS = [
    {
      key: 's',
      name: 'Supporter',
      range: '2–6% of income',
      blurb: 'A meaningful starting point that sustains core ministry.',
      color: 'gold'
    },
    {
      key: 'u',
      name: 'Sustainer',
      range: '3–7% of income',
      blurb: 'Helps the congregation thrive year-round.',
      color: 'blue'
    },
    {
      key: 'v',
      name: 'Visionary',
      range: '5–9% of income',
      blurb: 'Fuels growth, justice work, and new possibilities.',
      color: 'green'
    },
    {
      key: 't',
      name: 'Transformer',
      range: '10% of income',
      blurb: 'A bold, transformative commitment to our shared mission.',
      color: 'rose'
    }
  ];

  /* Slider bounds in monthly income (matches the quick-adjust control). */
  var SLIDER_MONTHLY_MIN = 1000;
  var SLIDER_MONTHLY_MAX = 12000;
  var SLIDER_MONTHLY_STEP = 100;

  function clamp(n, min, max) {
    return Math.min(max, Math.max(min, n));
  }

  function parseMoney(value) {
    if (value == null || value === '') {
      return NaN;
    }
    var cleaned = String(value).replace(/[^0-9.]/g, '');
    if (!cleaned) {
      return NaN;
    }
    return parseFloat(cleaned);
  }

  function formatMoney(n) {
    if (!isFinite(n)) {
      return '—';
    }
    var rounded = Math.round(n);
    return '$' + rounded.toLocaleString('en-US');
  }

  function formatPct(n) {
    if (!isFinite(n)) {
      return '—';
    }
    // Show one decimal only when needed
    var v = Math.round(n * 10) / 10;
    return (v % 1 === 0 ? v.toFixed(0) : v.toFixed(1)) + '%';
  }

  /**
   * Interpolate suggested % between guide rows for a smooth calculator.
   */
  function ratesForMonthly(monthly) {
    if (!isFinite(monthly) || monthly <= 0) {
      return null;
    }

    if (monthly <= ROWS[0].m) {
      return { s: ROWS[0].s, u: ROWS[0].u, v: ROWS[0].v, t: ROWS[0].t };
    }

    var last = ROWS[ROWS.length - 1];
    if (monthly >= last.m) {
      return { s: last.s, u: last.u, v: last.v, t: last.t };
    }

    for (var i = 0; i < ROWS.length - 1; i++) {
      var a = ROWS[i];
      var b = ROWS[i + 1];
      if (monthly >= a.m && monthly <= b.m) {
        var t = (monthly - a.m) / (b.m - a.m);
        return {
          s: a.s + (b.s - a.s) * t,
          u: a.u + (b.u - a.u) * t,
          v: a.v + (b.v - a.v) * t,
          t: a.t + (b.t - a.t) * t
        };
      }
    }

    return { s: last.s, u: last.u, v: last.v, t: last.t };
  }

  function updateSliderFill(slider) {
    if (!slider) {
      return;
    }
    var min = Number(slider.min) || 0;
    var max = Number(slider.max) || 100;
    var val = Number(slider.value);
    if (!isFinite(val)) {
      val = min;
    }
    var pct = max > min ? ((val - min) / (max - min)) * 100 : 0;
    pct = clamp(pct, 0, 100);
    slider.style.setProperty('--pc-slider-pct', pct + '%');
  }

  function initCalculator(root) {
    var incomeInput = root.querySelector('[data-pledge-income]');
    var modeInputs = root.querySelectorAll('[data-pledge-mode]');
    var results = root.querySelector('[data-pledge-results]');
    var empty = root.querySelector('[data-pledge-empty]');
    var monthlyOut = root.querySelector('[data-pledge-monthly-out]');
    var annualOut = root.querySelector('[data-pledge-annual-out]');
    var slider = root.querySelector('[data-pledge-slider]');
    var sliderLabel = root.querySelector('[data-pledge-slider-label]');
    var sliderRange = root.querySelector('[data-pledge-slider-range]');

    if (!incomeInput || !results) {
      return;
    }

    function currentMode() {
      var checked = root.querySelector('[data-pledge-mode]:checked');
      return checked ? checked.value : 'monthly';
    }

    function getMonthly() {
      var raw = parseMoney(incomeInput.value);
      if (!isFinite(raw) || raw <= 0) {
        return NaN;
      }
      return currentMode() === 'annual' ? raw / 12 : raw;
    }

    /**
     * Slider always represents the active period (monthly or annual amounts).
     */
    function syncSliderBounds(mode) {
      if (!slider) {
        return;
      }
      if (mode === 'annual') {
        slider.min = String(SLIDER_MONTHLY_MIN * 12);
        slider.max = String(SLIDER_MONTHLY_MAX * 12);
        slider.step = String(SLIDER_MONTHLY_STEP * 12);
      } else {
        slider.min = String(SLIDER_MONTHLY_MIN);
        slider.max = String(SLIDER_MONTHLY_MAX);
        slider.step = String(SLIDER_MONTHLY_STEP);
      }
    }

    function syncSliderChrome(mode) {
      if (sliderLabel) {
        sliderLabel.textContent =
          mode === 'annual' ? 'Quick adjust (annual)' : 'Quick adjust (monthly)';
      }
      if (sliderRange) {
        if (mode === 'annual') {
          sliderRange.textContent =
            formatMoney(SLIDER_MONTHLY_MIN * 12) +
            ' – ' +
            formatMoney(SLIDER_MONTHLY_MAX * 12);
        } else {
          sliderRange.textContent =
            formatMoney(SLIDER_MONTHLY_MIN) +
            ' – ' +
            formatMoney(SLIDER_MONTHLY_MAX);
        }
      }
    }

    function render() {
      var mode = currentMode();
      var monthly = getMonthly();
      var rates = ratesForMonthly(monthly);

      syncSliderChrome(mode);
      syncSliderBounds(mode);

      if (!rates) {
        if (empty) {
          empty.hidden = false;
        }
        results.hidden = true;
        if (monthlyOut) {
          monthlyOut.textContent = '—';
        }
        if (annualOut) {
          annualOut.textContent = '—';
        }
        TIERS.forEach(function (tier) {
          var card = results.querySelector('[data-tier="' + tier.key + '"]');
          if (!card) {
            return;
          }
          card.querySelector('[data-tier-pct]').textContent = '—';
          card.querySelector('[data-tier-monthly]').textContent = '—';
          card.querySelector('[data-tier-annual]').textContent = '—';
        });
        updateSliderFill(slider);
        return;
      }

      if (empty) {
        empty.hidden = true;
      }
      results.hidden = false;

      var annual = monthly * 12;
      if (monthlyOut) {
        monthlyOut.textContent = formatMoney(monthly);
      }
      if (annualOut) {
        annualOut.textContent = formatMoney(annual);
      }

      if (slider && document.activeElement !== slider) {
        var periodVal = mode === 'annual' ? annual : monthly;
        var sliderVal = clamp(
          Math.round(periodVal),
          Number(slider.min),
          Number(slider.max)
        );
        // Snap to step
        var step = Number(slider.step) || 1;
        sliderVal = Math.round(sliderVal / step) * step;
        sliderVal = clamp(sliderVal, Number(slider.min), Number(slider.max));
        slider.value = String(sliderVal);
      }
      updateSliderFill(slider);

      TIERS.forEach(function (tier) {
        var pct = rates[tier.key];
        var monthlyPledge = monthly * (pct / 100);
        var annualPledge = monthlyPledge * 12;
        var card = results.querySelector('[data-tier="' + tier.key + '"]');
        if (!card) {
          return;
        }
        card.querySelector('[data-tier-pct]').textContent = formatPct(pct);
        card.querySelector('[data-tier-monthly]').textContent = formatMoney(monthlyPledge);
        card.querySelector('[data-tier-annual]').textContent = formatMoney(annualPledge) + ' / year';
      });
    }

    function setIncomeFromSlider(periodAmount) {
      incomeInput.value = String(Math.round(periodAmount));
      render();
    }

    incomeInput.addEventListener('input', render);
    incomeInput.addEventListener('change', render);

    // Mode switch: convert value so underlying income stays stable
    var lastMode = currentMode();
    modeInputs.forEach(function (radio) {
      radio.addEventListener('change', function () {
        var raw = parseMoney(incomeInput.value);
        var newMode = currentMode();
        if (isFinite(raw) && raw > 0 && newMode !== lastMode) {
          if (lastMode === 'monthly' && newMode === 'annual') {
            incomeInput.value = String(Math.round(raw * 12));
          } else if (lastMode === 'annual' && newMode === 'monthly') {
            incomeInput.value = String(Math.round(raw / 12));
          }
        }
        lastMode = newMode;
        render();
      });
    });

    if (slider) {
      slider.addEventListener('input', function () {
        updateSliderFill(slider);
        setIncomeFromSlider(Number(slider.value));
      });
    }

    // Example starter value for demo polish
    if (!incomeInput.value) {
      incomeInput.value = '4000';
    }

    syncSliderBounds(currentMode());
    syncSliderChrome(currentMode());
    if (slider && !slider.value) {
      slider.value = '4000';
    }

    render();
  }

  function boot() {
    document.querySelectorAll('[data-uucg-pledge-calculator]').forEach(initCalculator);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
