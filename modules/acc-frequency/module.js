/* Frequency Capping: front end.
 * A popup that carries data-freq-mode (printed by WpPopPop_Frequency, only while the module is installed and switched on) decides
 * how long a closed popup stays hidden. The core remembers a close as wppoppop_dismissed_<uid> (a timestamp in localStorage) and
 * never shows a dismissed popup on page load. This script runs right after the popup markup, before the core starts, and
 * removes that record when the rule says the popup may show again:
 *   always  - every page view (the popup still stays closed on the page where it was closed)
 *   session - only while the same browser session lasts (a sessionStorage flag is set on close)
 *   days    - until data-freq-days days have passed since the close
 * ES5 only (Chrome, Safari, Firefox, Edge). Blocked storage (private mode) is ignored. */
(function(window, document) {
    'use strict';
    if (window.__wppFreq) return;
    window.__wppFreq = true;

    var DISMISSED = 'wppoppop_dismissed_';
    var SESSION = 'wppoppop_freq_s_';
    var DAY = 86400000;

    function lget(k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } }
    function lrm(k) { try { window.localStorage.removeItem(k); } catch (e) { /* storage blocked */ } }
    function sget(k) { try { return window.sessionStorage.getItem(k); } catch (e) { return null; } }
    function sset(k, v) { try { window.sessionStorage.setItem(k, v); } catch (e) { /* storage blocked */ } }
    function srm(k) { try { window.sessionStorage.removeItem(k); } catch (e) { /* storage blocked */ } }

    function rule(el) {
        var mode = el.getAttribute('data-freq-mode');
        var days = parseInt(el.getAttribute('data-freq-days'), 10);
        if (mode !== 'session' && mode !== 'days') mode = 'always';
        if (isNaN(days) || days < 1) days = 7;
        return { mode: mode, days: days };
    }

    function prune(uid, r) {
        var raw = lget(DISMISSED + uid);
        if (raw === null) return;
        if (r.mode === 'always') {
            lrm(DISMISSED + uid);
        } else if (r.mode === 'session') {
            if (sget(SESSION + uid) !== '1') lrm(DISMISSED + uid);
        } else {
            var ts = parseInt(raw, 10);
            if (isNaN(ts) || (new Date().getTime() - ts) >= r.days * DAY) lrm(DISMISSED + uid);
        }
        if (r.mode !== 'session') srm(SESSION + uid);
    }

    function wraps() {
        return document.querySelectorAll('.wppoppop-popup-wrap[data-freq-mode][data-uid]');
    }

    var list = wraps(), i, el;
    for (i = 0; i < list.length; i++) {
        el = list[i];
        prune(el.getAttribute('data-uid'), rule(el));
    }

    var D = window.WpPopPopFrontDisplay;
    if (D && typeof D.close === 'function' && !D._freqWrapped) {
        D._freqWrapped = true;
        var origClose = D.close;
        D.close = function(uid) {
            var out = origClose.apply(this, arguments);
            try {
                var n = wraps(), j;
                for (j = 0; j < n.length; j++) {
                    if (n[j].getAttribute('data-uid') === String(uid) && rule(n[j]).mode === 'session') {
                        sset(SESSION + uid, '1');
                    }
                }
            } catch (e) { /* rule is best effort */ }
            return out;
        };
    }
})(window, document);
