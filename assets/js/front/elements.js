/* WpPopPop Pro: visitor-side behaviour of the Pro elements (Image Select, Tile, Star Rating, Progress Bar). */

/* === WPPOPPOP_IMGSEL_RUNTIME_START === */
(function() {
    'use strict';
    if (window.__wppImgSel) return;
    window.__wppImgSel = true;
    function fire(el, name) {
        var ev;
        try { ev = new Event(name, { bubbles: true }); } catch (err) { ev = document.createEvent('Event'); ev.initEvent(name, true, true); }
        el.dispatchEvent(ev);
    }
    function sync(grid) {
        var acc = grid.getAttribute('data-active-color') || '#38bdf8';
        var cards = grid.querySelectorAll('.wppoppop-imgsel-card');
        var vals = [];
        for (var i = 0; i < cards.length; i++) {
            var on = cards[i].getAttribute('aria-pressed') === 'true';
            cards[i].style.borderColor = on ? acc : '#cbd5e1';
            if (on) { vals.push(cards[i].getAttribute('data-value')); }
        }
        var inp = grid.parentNode ? grid.parentNode.querySelector('input') : null;
        if (inp) { inp.value = vals.join(', '); fire(inp, 'input'); fire(inp, 'change'); }
    }
    function pick(card) {
        var grid = card.closest ? card.closest('.wppoppop-imgsel-grid') : null;
        if (!grid) return;
        var multi = grid.getAttribute('data-multi') === '1';
        var on = card.getAttribute('aria-pressed') === 'true';
        if (!multi) {
            var all = grid.querySelectorAll('.wppoppop-imgsel-card');
            for (var i = 0; i < all.length; i++) { all[i].setAttribute('aria-pressed', 'false'); }
            card.setAttribute('aria-pressed', 'true');
        } else {
            card.setAttribute('aria-pressed', on ? 'false' : 'true');
        }
        sync(grid);
    }
    document.addEventListener('click', function(e) {
        var card = (e.target && e.target.closest) ? e.target.closest('.wppoppop-imgsel-card') : null;
        if (card) { pick(card); }
    }, true);
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Enter' && e.key !== ' ' && e.keyCode !== 13 && e.keyCode !== 32) return;
        var card = (e.target && e.target.closest) ? e.target.closest('.wppoppop-imgsel-card') : null;
        if (card) { e.preventDefault(); pick(card); }
    }, true);
})();
/* === WPPOPPOP_IMGSEL_RUNTIME_END === */

/* === WPPOPPOP_TILE_RUNTIME_START === */
(function() {
    'use strict';
    if (window.__wppTile) return;
    window.__wppTile = true;
    function fire(el, name) {
        var ev;
        try { ev = new Event(name, { bubbles: true }); } catch (err) { ev = document.createEvent('Event'); ev.initEvent(name, true, true); }
        el.dispatchEvent(ev);
    }
    function sync(grid) {
        var acc = grid.getAttribute('data-active-color') || '#38bdf8';
        var cards = grid.querySelectorAll('.wppoppop-tl-card');
        var vals = [];
        for (var i = 0; i < cards.length; i++) {
            var c = cards[i], on = c.getAttribute('aria-pressed') === 'true';
            c.style.backgroundColor = on ? acc : (c.getAttribute('data-bg') || '#f1f5f9');
            c.style.color = on ? '#ffffff' : (c.getAttribute('data-fg') || '#334155');
            c.style.borderColor = on ? acc : (c.getAttribute('data-bc') || '#cbd5e1');
            if (on) { vals.push(c.getAttribute('data-value')); }
        }
        var inp = grid.parentNode ? grid.parentNode.querySelector('input') : null;
        if (inp) { inp.value = vals.join(', '); fire(inp, 'input'); fire(inp, 'change'); }
    }
    function pick(card) {
        var grid = card.closest ? card.closest('.wppoppop-tl-grid') : null;
        if (!grid) return;
        var multi = grid.getAttribute('data-multi') === '1';
        var on = card.getAttribute('aria-pressed') === 'true';
        if (!multi) {
            var all = grid.querySelectorAll('.wppoppop-tl-card');
            for (var i = 0; i < all.length; i++) { all[i].setAttribute('aria-pressed', 'false'); }
            card.setAttribute('aria-pressed', 'true');
        } else {
            card.setAttribute('aria-pressed', on ? 'false' : 'true');
        }
        sync(grid);
    }
    document.addEventListener('click', function(e) {
        var card = (e.target && e.target.closest) ? e.target.closest('.wppoppop-tl-card') : null;
        if (card) { pick(card); }
    }, true);
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Enter' && e.key !== ' ' && e.keyCode !== 13 && e.keyCode !== 32) return;
        var card = (e.target && e.target.closest) ? e.target.closest('.wppoppop-tl-card') : null;
        if (card) { e.preventDefault(); pick(card); }
    }, true);
})();
/* === WPPOPPOP_TILE_RUNTIME_END === */

/* === WPPOPPOP_TILE_BOX_RUNTIME_START === */
(function() {
    'use strict';
    if (window.__wppTileBox) return;
    window.__wppTileBox = true;
    function clear() {
        var n = document.querySelectorAll('.wppoppop-tl-wrap'), i, p;
        for (i = 0; i < n.length; i++) {
            p = n[i].parentNode;
            if (p && p.style) { p.style.setProperty('background-color', 'transparent', 'important'); }
        }
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', clear); } else { clear(); }
    if (window.MutationObserver && document.body) {
        new MutationObserver(function() { clear(); }).observe(document.body, { childList: true, subtree: true });
    } else {
        setInterval(clear, 800);
    }
})();
/* === WPPOPPOP_TILE_BOX_RUNTIME_END === */

/* === WPPOPPOP_RATING_SYNC_START === */
(function($) {
    'use strict';
    function paint($w) {
        var v = parseInt($w.find('input[type="hidden"]').val(), 10) || 0;
        var act = $w.attr('data-active-color') || '#f59e0b';
        var ina = $w.attr('data-inactive-color') || '#cbd5e1';
        $w.find('.wppoppop-rating-glyph').each(function() {
            var sc = parseInt($(this).attr('data-score'), 10) || 0;
            $(this).css('color', sc <= v ? act : ina);
        });
    }
    $(document).off('change.wppRtSync').on('change.wppRtSync', '.wppoppop-interactive-rating input[type="hidden"]', function() {
        var $w = $(this).closest('.wppoppop-interactive-rating');
        var max = $w.find('.wppoppop-rating-glyph').length;
        var v = parseInt(this.value, 10) || 0;
        if (v > max) { this.value = String(max); }
        if (v < 0) { this.value = ''; }
        paint($w);
    });
})(jQuery);
/* === WPPOPPOP_RATING_SYNC_END === */

/* === WPPOPPOP_RATING_RUNTIME_START === */
(function() {
    'use strict';
    if (window.__wppRating) return;
    window.__wppRating = true;

    if (!document.getElementById('wppoppop-rating-runtime-css')) {
        var st = document.createElement('style');
        st.id = 'wppoppop-rating-runtime-css';
        st.appendChild(document.createTextNode('.wppoppop-interactive-rating,.wppoppop-interactive-rating .wppoppop-rating-glyph{pointer-events:auto!important;touch-action:manipulation;}.wppoppop-rating-glyph:focus{outline:2px solid rgba(2,132,199,.5);outline-offset:2px;border-radius:0px;}'));
        (document.head || document.documentElement).appendChild(st);
    }

    function fire(el, name) {
        var ev;
        try { ev = new Event(name, { bubbles: true }); } catch (err) { ev = document.createEvent('Event'); ev.initEvent(name, true, true); }
        el.dispatchEvent(ev);
    }
    function glyphs(wrap) { return wrap.querySelectorAll('.wppoppop-rating-glyph'); }
    function paint(wrap, score) {
        var act = wrap.getAttribute('data-active-color') || '#f59e0b';
        var ina = wrap.getAttribute('data-inactive-color') || '#cbd5e1';
        var g = glyphs(wrap);
        for (var i = 0; i < g.length; i++) {
            var sc = parseInt(g[i].getAttribute('data-score'), 10) || 0;
            g[i].style.color = (sc <= score) ? act : ina;
            g[i].setAttribute('aria-checked', sc === score ? 'true' : 'false');
        }
    }
    function current(wrap) {
        var inp = wrap.querySelector('input[type="hidden"]');
        return inp ? (parseInt(inp.value, 10) || 0) : 0;
    }
    function setValue(wrap, score) {
        var inp = wrap.querySelector('input[type="hidden"]');
        if (!inp) return;
        var max = glyphs(wrap).length;
        score = Math.max(0, Math.min(max, score));
        inp.value = score > 0 ? String(score) : '';
        paint(wrap, score);
        fire(inp, 'input');
        fire(inp, 'change');
        var bub = document.querySelector('.wppoppop-error-bubble');
        if (bub && bub.getAttribute('data-rating') === '1' && bub.parentNode) { bub.parentNode.removeChild(bub); }
    }
    function glyphOf(t) { return (t && t.closest) ? t.closest('.wppoppop-rating-glyph') : null; }

    document.addEventListener('click', function(e) {
        var g = glyphOf(e.target);
        if (!g) return;
        var wrap = g.closest('.wppoppop-interactive-rating');
        if (!wrap) return;
        e.preventDefault();
        e.stopPropagation();
        setValue(wrap, parseInt(g.getAttribute('data-score'), 10) || 0);
    }, true);

    document.addEventListener('mouseover', function(e) {
        var g = glyphOf(e.target);
        if (!g) return;
        var wrap = g.closest('.wppoppop-interactive-rating');
        if (wrap) { paint(wrap, parseInt(g.getAttribute('data-score'), 10) || 0); }
    }, true);
    document.addEventListener('mouseout', function(e) {
        var g = glyphOf(e.target);
        if (!g) return;
        var wrap = g.closest('.wppoppop-interactive-rating');
        if (wrap) { paint(wrap, current(wrap)); }
    }, true);

    document.addEventListener('keydown', function(e) {
        var g = glyphOf(e.target);
        if (!g) return;
        var wrap = g.closest('.wppoppop-interactive-rating');
        if (!wrap) return;
        var k = e.key, sc = parseInt(g.getAttribute('data-score'), 10) || 0, cur = current(wrap);
        if (k === 'Enter' || k === ' ' || e.keyCode === 13 || e.keyCode === 32) { e.preventDefault(); setValue(wrap, sc); }
        else if (k === 'ArrowRight' || k === 'ArrowUp' || e.keyCode === 39 || e.keyCode === 38) { e.preventDefault(); setValue(wrap, Math.max(cur, 0) + 1); }
        else if (k === 'ArrowLeft' || k === 'ArrowDown' || e.keyCode === 37 || e.keyCode === 40) { e.preventDefault(); setValue(wrap, cur - 1); }
    }, true);

    /* repaint when the value is set from elsewhere (dynamic URL default, reset, etc.) */
    document.addEventListener('change', function(e) {
        var t = e.target;
        if (!t || !t.closest || t.type !== 'hidden') return;
        var wrap = t.closest('.wppoppop-interactive-rating');
        if (!wrap) return;
        var max = glyphs(wrap).length, v = parseInt(t.value, 10) || 0;
        if (v > max) { t.value = String(max); v = max; }
        if (v < 0) { t.value = ''; v = 0; }
        paint(wrap, v);
    }, true);

    function init(root) {
        var list = (root || document).querySelectorAll('.wppoppop-interactive-rating');
        for (var i = 0; i < list.length; i++) {
            var w = list[i];
            var g = glyphs(w);
            for (var j = 0; j < g.length; j++) { if (!g[j].hasAttribute('tabindex')) { g[j].setAttribute('tabindex', '0'); } }
            paint(w, current(w));
        }
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function() { init(); }); } else { init(); }
    if (window.MutationObserver && document.body) {
        new MutationObserver(function() { init(); }).observe(document.body, { childList: true, subtree: true });
    }
    window.addEventListener('load', function() { init(); });

    /* required rating: hidden inputs are skipped by the generic validator */
    document.addEventListener('click', function(e) {
        var btn = (e.target && e.target.closest) ? e.target.closest('.wppoppop-submit-trigger') : null;
        if (!btn) return;
        var popup = btn.closest('.wppoppop-popup-wrap') || document.body;
        var list = popup.querySelectorAll('.wppoppop-interactive-rating');
        for (var i = 0; i < list.length; i++) {
            var w = list[i], inp = w.querySelector('input[type="hidden"]');
            if (!inp || !inp.hasAttribute('required') || inp.value) continue;
            if (!(w.offsetWidth || w.offsetHeight || w.getClientRects().length)) continue;
            e.preventDefault();
            e.stopImmediatePropagation();
            var old = document.querySelector('.wppoppop-error-bubble');
            if (old && old.parentNode) { old.parentNode.removeChild(old); }
            var b = document.createElement('div');
            b.className = 'wppoppop-error-bubble';
            b.setAttribute('data-rating', '1');
            b.textContent = inp.getAttribute('data-error-msg') || 'Please select a rating.';
            var r = w.getBoundingClientRect();
            b.style.cssText = 'position:absolute;top:' + (r.top + window.pageYOffset - 34) + 'px;left:' + (r.left + window.pageXOffset) + 'px;background:#ef4444;color:#fff;padding:6px 12px;border-radius:0px;font-size:12px;font-weight:600;z-index:9999999;box-shadow:0 4px 12px rgba(239,68,68,.3);';
            document.body.appendChild(b);
            setTimeout(function() { if (b.parentNode) { b.parentNode.removeChild(b); } }, 3500);
            return;
        }
    }, true);
})();
/* === WPPOPPOP_RATING_RUNTIME_END === */

/* === WPPOPPOP_PROGRESS_RUNTIME_START === */
(function(window, document) {
    'use strict';
    if (window.__wppProgressBound) return;
    window.__wppProgressBound = true;

    function visible(el) {
        return !!(el.offsetWidth || el.offsetHeight || (el.getClientRects && el.getClientRects().length));
    }
    function formPercent(scope) {
        var nodes = scope.querySelectorAll('input, select, textarea');
        var groups = {}, order = [], uid = 0;
        for (var i = 0; i < nodes.length; i++) {
            var f = nodes[i], type = (f.type || '').toLowerCase();
            if (f.disabled) continue;
            if (['hidden', 'submit', 'button', 'reset', 'image', 'range'].indexOf(type) !== -1) continue;
            if (!visible(f)) continue;
            var key = ((type === 'radio' || type === 'checkbox') && f.name) ? ('g:' + f.name) : ('u:' + (uid++));
            if (!groups[key]) { groups[key] = false; order.push(key); }
            var done = false;
            if (type === 'radio' || type === 'checkbox') done = f.checked;
            else if (type === 'file') done = !!(f.files && f.files.length);
            else done = String(f.value || '').replace(/^\s+|\s+$/g, '') !== '';
            if (done) groups[key] = true;
        }
        if (!order.length) return 0;
        var ok = 0;
        for (var k = 0; k < order.length; k++) { if (groups[order[k]]) ok++; }
        return Math.round(ok / order.length * 100);
    }
    function stepPercent(popup) {
        var cs = popup.querySelectorAll('.wppoppop-canvas-container');
        if (!cs.length) return 0;
        var idx = 0;
        for (var i = 0; i < cs.length; i++) {
            if (window.getComputedStyle(cs[i]).display !== 'none') { idx = i; break; }
        }
        return Math.round((idx + 1) / cs.length * 100);
    }
    function update() {
        var bars = document.querySelectorAll('[data-progress-mode="form"], [data-progress-mode="steps"]');
        for (var i = 0; i < bars.length; i++) {
            var bar = bars[i];
            var mode = bar.getAttribute('data-progress-mode');
            var popup = bar.closest('.wppoppop-popup-wrap') || document;
            var scope = bar.closest('.wppoppop-canvas-container') || popup;
            var pct = (mode === 'steps') ? stepPercent(popup) : formPercent(scope);
            var fill = bar.querySelector('.wppoppop-progress-fill');
            if (fill) fill.style.width = pct + '%';
            var badge = bar.querySelector('.wppoppop-progress-badge');
            if (badge) {
                var lbl = bar.getAttribute('data-progress-label') || '';
                var show = bar.getAttribute('data-progress-showpct') !== '0';
                badge.textContent = lbl ? (lbl + (show ? ' (' + pct + '%)' : '')) : (show ? (pct + '%') : '');
            }
            bar.setAttribute('aria-valuenow', pct);
            bar.setAttribute('role', 'progressbar');
        }
    }
    function later() { setTimeout(update, 0); setTimeout(update, 80); setTimeout(update, 400); }
    ['input', 'change', 'keyup', 'click'].forEach(function(ev) { document.addEventListener(ev, later, true); });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', update); else update();
    setInterval(update, 1000);   /* covers popups opening later and canvas fade transitions */
})(window, document);
/* === WPPOPPOP_PROGRESS_RUNTIME_END === */
