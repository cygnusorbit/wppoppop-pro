/* Autoresponder: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the welcome email is saved to and restored from settings.autoresponder
 * ({ enable, subject, body }). Sanitising on save and sending on submit stay in the core (WpPopPop_Autoresponder).
 * setSettings also reads the legacy keys (enable_user_email, message) of popups saved before this module existed.
 * Only loaded while the module is installed. ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_AUTORESPONDER_UI: Autoresponder module (Inspector DOM <-> settings.autoresponder) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._autoWrapped) return;
    S._autoWrapped = true;

    function isTrue(v) { return v === true || v === 1 || v === '1' || v === 'true' || v === 'on'; }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.autoresponder = {
            enable: $('#set-auto-enable').is(':checked'),
            subject: $('#set-auto-subject').val() || '',
            body: $('#set-auto-body').val() || ''
        };
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var a = (settings && settings.autoresponder && typeof settings.autoresponder === 'object') ? settings.autoresponder : {};
        var body = (a.body !== undefined && a.body !== null && a.body !== '') ? a.body : (a.message || '');
        $('#set-auto-enable').prop('checked', isTrue(a.enable) || isTrue(a.enable_user_email));
        $('#set-auto-subject').val(a.subject || '');
        $('#set-auto-body').val(body);
        return r;
    };
})(jQuery);
