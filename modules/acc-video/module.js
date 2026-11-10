/* Video Playback Listeners: front end.
 * A popup that carries data-video-listen="1" (printed by WpPopPop_Video_Listeners, only while the module is installed and
 * switched on) is opened while a video of the page plays: at once when data-video-time is 0, otherwise when the playback
 * reaches that many seconds. Videos inside popups are ignored. Media events do not bubble, so they are caught in the
 * capture phase on the document (this also covers videos added later). A popup opens once per page view.
 * ES5 only (Chrome, Safari, Firefox, Edge). */
(function(window, document, $) {
    'use strict';
    if (!$ || window.__wppVideoListen) return;
    window.__wppVideoListen = true;

    var SEL = '.wppoppop-popup-wrap[data-video-listen="1"]:not([data-inline="1"]):not([data-manual="1"])';
    var done = {};

    function isPreview() {
        return (document.body && document.body.className.indexOf('wppoppop-preview-frame') !== -1) || /[?&]wppoppop_preview=/.test(window.location.search);
    }

    function dismissed(uid) {
        try { return !!window.localStorage.getItem('wppoppop_dismissed_' + uid); } catch (e) { return false; }
    }

    function fire($w) {
        var uid = $w.attr('data-uid'), D = window.WpPopPopFrontDisplay;
        if (!uid || done[uid] || !D || typeof D.show !== 'function') return;
        done[uid] = true;
        if ($w.is(':visible') || dismissed(uid)) return;
        D.show(uid);
    }

    function onMedia(e) {
        var v = e.target, t;
        if (!v || String(v.tagName).toUpperCase() !== 'VIDEO' || v.paused || isPreview()) return;
        if ($(v).closest('.wppoppop-popup-wrap, .wppoppop-overlay').length) return;
        t = Number(v.currentTime) || 0;
        $(SEL).each(function() {
            var need = parseFloat(this.getAttribute('data-video-time')) || 0;
            if (t >= need) fire($(this));
        });
    }

    if (document.addEventListener) {
        document.addEventListener('play', onMedia, true);
        document.addEventListener('playing', onMedia, true);
        document.addEventListener('timeupdate', onMedia, true);
    }
})(window, document, window.jQuery);
