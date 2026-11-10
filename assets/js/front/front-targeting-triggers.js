/**
 * WpPopPop Front: Targeting Triggers runtime (OnLoad / OnScroll / OnExit / OnInactivity).
 * Reads data-tt-* attributes rendered by WpPopPop_Targeting_Triggers::frontend().
 * Server side already applied activity period, user roles and site area.
 * Skips: builder preview, inline popups, manual (button / shortcode) popups, "always on load" popups.
 * Cross-browser: ES5 only (Chrome, Safari, Firefox, Edge).
 */
(function (window, document, $) {
    'use strict';
    if (window.__wppTTBound) { return; }
    window.__wppTTBound = true;

    var D = window.WpPopPopFrontDisplay;
    if (!D || !$) { return; }
    var vars = window.wppoppop_front_vars || {};
    var EPOCH = String(vars.cookie_epoch || 1);
    var SEL = '.wppoppop-popup-wrap[data-tt-event]:not([data-inline="1"]):not([data-manual="1"]):not([data-trigger-load="1"])';

    function isPreview() {
        return (document.body && document.body.className.indexOf('wppoppop-preview-frame') !== -1) || /[?&]wppoppop_preview=/.test(window.location.search);
    }
    function key(uid) { return 'wppoppop_tt_' + uid; }
    function getCookie(name) {
        var parts = document.cookie ? document.cookie.split('; ') : [], i, kv;
        for (i = 0; i < parts.length; i++) {
            kv = parts[i].split('=');
            if (kv.shift() === name) { try { return decodeURIComponent(kv.join('=')); } catch (e) { return ''; } }
        }
        return '';
    }
    /* Record = "<epoch>|<timestamp>", kept in localStorage and a cookie (private mode / blocked storage fallback). */
    function lastSeen(uid) {
        var v = '', p;
        try { v = window.localStorage.getItem(key(uid)) || ''; } catch (e) { v = ''; }
        if (!v) { v = getCookie(key(uid)); }
        p = v.split('|');
        if (p.length !== 2 || p[0] !== EPOCH) { return 0; }
        return parseInt(p[1], 10) || 0;
    }
    function markSeen(uid) {
        var v = EPOCH + '|' + new Date().getTime();
        try { window.localStorage.setItem(key(uid), v); } catch (e) {}
        try {
            document.cookie = key(uid) + '=' + encodeURIComponent(v) + '; path=/; max-age=315360000; SameSite=Lax' + (window.location.protocol === 'https:' ? '; Secure' : '');
        } catch (e2) {}
    }
    function allowed($w, uid) {
        var mode = $w.attr('data-tt-mode') || 'every-time', ts, hrs;
        if (mode === 'every-time') { return true; }
        ts = lastSeen(uid);
        if (!ts) { return true; }
        if (mode === 'once-only') { return false; }
        hrs = parseFloat($w.attr('data-tt-period')) || 24;
        return (new Date().getTime() - ts) >= hrs * 3600000;
    }
    function num($w, name, def) {
        var n = parseFloat($w.attr(name));
        return isNaN(n) ? def : n;
    }

    function bindPopup($w) {
        var uid = $w.attr('data-uid'), ev = $w.attr('data-tt-event'), fired = false;
        if (!uid) { return; }

        function fire() {
            var closeAfter;
            if (fired) { return; }
            fired = true;
            if ($w.is(':visible') || !allowed($w, uid)) { return; }
            D.show(uid);
            markSeen(uid);
            closeAfter = ev === 'onload' ? num($w, 'data-tt-close', 0) : 0;
            if (closeAfter > 0) {
                window.setTimeout(function () { if ($w.is(':visible')) { D.close(uid); } }, closeAfter * 1000);
            }
        }

        if (ev === 'onload') {
            window.setTimeout(fire, Math.max(0, num($w, 'data-tt-start', 0)) * 1000);
        } else if (ev === 'onscroll') {
            var offset = num($w, 'data-tt-offset', 600), pct = $w.attr('data-tt-unit') === '%';
            var checkScroll = function () {
                var doc = document.documentElement, body = document.body, y, max, cur;
                if (fired) { return; }
                y = window.pageYOffset || doc.scrollTop || (body && body.scrollTop) || 0;
                max = Math.max(1, Math.max(doc.scrollHeight || 0, body ? body.scrollHeight : 0) - (window.innerHeight || doc.clientHeight || 0));
                cur = pct ? (y / max) * 100 : y;
                if (cur >= offset) {
                    fire();
                    window.removeEventListener('scroll', checkScroll, false);
                }
            };
            window.addEventListener('scroll', checkScroll, false);
            checkScroll();
        } else if (ev === 'onexit') {
            var sens = num($w, 'data-tt-sens', 20);
            var onLeave = function (e) {
                if (fired || e.clientY > sens) { return; }
                fire();
            };
            document.documentElement.addEventListener('mouseleave', onLeave, false);
            document.addEventListener('mouseout', function (e) {
                if (!e.relatedTarget && !e.toElement) { onLeave(e); }
            }, false);
        } else if (ev === 'oninactivity') {
            var idle = Math.max(1, num($w, 'data-tt-idle', 15)) * 1000, last = new Date().getTime(), timer;
            var touch = function () { last = new Date().getTime(); };
            var names = ['mousemove', 'keydown', 'scroll', 'touchstart', 'click', 'wheel'], i;
            for (i = 0; i < names.length; i++) { window.addEventListener(names[i], touch, false); }
            timer = window.setInterval(function () {
                if (fired) { window.clearInterval(timer); return; }
                if (new Date().getTime() - last >= idle) {
                    window.clearInterval(timer);
                    fire();
                }
            }, 500);
        }
    }

    $(function () {
        if (isPreview()) { return; }
        $(SEL).each(function () { bindPopup($(this)); });
    });
})(window, document, window.jQuery);
