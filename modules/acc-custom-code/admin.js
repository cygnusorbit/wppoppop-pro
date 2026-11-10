/* Custom Scoped CSS & JS: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the two code fields are saved to and restored from settings.custom_code ({ css, js }).
 * A popup without custom code resets the fields to empty. Sanitising on save lives in the core class (WpPopPop_Custom_Code).
 * Only loaded while the module is installed. ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_CUSTOM_CODE_UI: Custom Code module (Inspector DOM <-> settings.custom_code) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._customCodeWrapped) return;
    S._customCodeWrapped = true;

    function text(v) { return typeof v === 'string' ? v : ''; }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.custom_code = {
            css: $('#set-custom-css').val() || '',
            js: $('#set-custom-js').val() || ''
        };
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var c = (settings && settings.custom_code && typeof settings.custom_code === 'object') ? settings.custom_code : {};
        $('#set-custom-css').val(text(c.css));
        $('#set-custom-js').val(text(c.js));
        return r;
    };
})(jQuery);
