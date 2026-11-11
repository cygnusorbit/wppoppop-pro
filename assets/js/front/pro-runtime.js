/**
 * WpPopPop Pro: Google Analytics events and AdBlock signal for the core popup runtime.
 * Core announces popup events on the document; this file only listens.
 */
(function ($) {
    'use strict';
    var vars = window.wppoppop_front_vars || {};

    function sendGA(eventName, params) {
        try {
            if (typeof window.gtag === 'function') {
                window.gtag('event', eventName, params);
            }
            if (Array.isArray(window.dataLayer)) {
                window.dataLayer.push($.extend({ event: eventName }, params));
            }
        } catch (err) {}
    }

    if (vars.ga_tracking) {
        $(document).on('wppoppop:popup_shown', function (e, uid) {
            sendGA('wppoppop_impression', { popup_uid: uid });
        });
        $(document).on('wppoppop:popup_closed', function (e, uid) {
            sendGA('wppoppop_close', { popup_uid: uid });
        });
    }

    if (vars.adblock_detector) {
        $(function () {
            if (window.wppoppop_adblock_detected) {
                $(document).trigger('wppoppop:adblock_active');
            }
        });
    }
})(jQuery);
