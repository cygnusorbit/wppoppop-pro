/* Twilio SMS Alerts: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the switch, the numbers and the Twilio credentials are saved to and restored from
 * settings.sms ({ enable, phone, sid, token, from }). Sanitising on save and the sending live in the core class (WpPopPop_Sms).
 * Only loaded while the module is installed. ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_SMS_UI: Twilio SMS Alerts module (Inspector DOM <-> settings.sms) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._smsWrapped) return;
    S._smsWrapped = true;

    function on(v) { return v === true || v === 1 || v === '1' || v === 'true' || v === 'on'; }
    function str(v) { return (v === undefined || v === null || typeof v === 'object') ? '' : String(v); }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.sms = {
            enable: $('#set-sms-enable').is(':checked'),
            phone: $.trim($('#set-sms-phone').val() || ''),
            sid: $.trim($('#set-sms-sid').val() || ''),
            token: $.trim($('#set-sms-token').val() || ''),
            from: $.trim($('#set-sms-from').val() || '')
        };
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var m = (settings && settings.sms && typeof settings.sms === 'object') ? settings.sms : {};
        $('#set-sms-enable').prop('checked', on(m.enable));
        $('#set-sms-phone').val(str(m.phone));
        $('#set-sms-sid').val(str(m.sid));
        $('#set-sms-token').val(str(m.token));
        $('#set-sms-from').val(str(m.from));
        return r;
    };
})(jQuery);
