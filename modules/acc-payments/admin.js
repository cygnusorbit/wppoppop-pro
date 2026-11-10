/* Payments: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the gateway and amount are saved to and restored from settings.payments ({ gateway, amount }).
 * Sanitising on save and the defaults of the Pay layer live in the core class (WpPopPop_Logic_Payments).
 * Only loaded while the module is installed. ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_LOGIC_PAYMENTS_UI: Payments module (Inspector DOM <-> settings.payments) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._payWrapped) return;
    S._payWrapped = true;

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.payments = {
            gateway: $('#set-pay-gateway').val() || 'stripe',
            amount: parseFloat($('#set-pay-amount').val()) || 0
        };
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var p = (settings && settings.payments && typeof settings.payments === 'object') ? settings.payments : {};
        var gw = (p.gateway === 'paypal') ? 'paypal' : 'stripe';
        var amt = parseFloat(p.amount);
        $('#set-pay-gateway').val(gw);
        $('#set-pay-amount').val(amt > 0 ? String(amt) : '');
        return r;
    };
})(jQuery);
