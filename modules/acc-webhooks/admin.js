/* Webhooks: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the webhook is saved to and restored from settings.webhooks ({ url, secret }).
 * Sanitising on save and the dispatch on submit stay in the core (WpPopPop_Webhooks).
 * Only loaded while the module is installed. ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_WEBHOOKS_UI: Webhooks module (Inspector DOM <-> settings.webhooks) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._webhooksWrapped) return;
    S._webhooksWrapped = true;

    function str(v) { return (v === undefined || v === null) ? '' : String(v); }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.webhooks = {
            url: $('#set-webhook-url').val() || '',
            secret: $('#set-webhook-secret').val() || ''
        };
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var w = (settings && settings.webhooks && typeof settings.webhooks === 'object') ? settings.webhooks : {};
        $('#set-webhook-url').val(str(w.url));
        $('#set-webhook-secret').val(str(w.secret));
        return r;
    };
})(jQuery);
