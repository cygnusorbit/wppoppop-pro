/* WooCommerce: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the coupon options are saved to and restored from settings.woocommerce
 * ({ coupon, amount, days }). Sanitising on save and the coupon on submit stay in the core (WpPopPop_Woocommerce).
 * Only loaded while the module is installed. ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_WOOCOMMERCE_UI: WooCommerce module (Inspector DOM <-> settings.woocommerce) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._wooWrapped) return;
    S._wooWrapped = true;

    function isTrue(v) { return v === true || v === 1 || v === '1' || v === 'true' || v === 'on'; }
    function str(v) { return (v === undefined || v === null) ? '' : String(v); }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.woocommerce = {
            coupon: $('#set-wc-coupon').is(':checked'),
            amount: $('#set-wc-amount').val() || '',
            days: $('#set-wc-days').val() || ''
        };
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var w = (settings && settings.woocommerce && typeof settings.woocommerce === 'object') ? settings.woocommerce : {};
        $('#set-wc-coupon').prop('checked', isTrue(w.coupon));
        $('#set-wc-amount').val(str(w.amount));
        $('#set-wc-days').val(str(w.days));
        return r;
    };
})(jQuery);
