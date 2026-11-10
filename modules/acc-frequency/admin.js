/* Frequency Capping: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the mode and the number of days are saved to and restored from settings.frequency
 * ({ mode: always|session|days, days }). A popup without frequency data resets the fields to their defaults.
 * Sanitising on save lives in the core class (WpPopPop_Frequency).
 * Only loaded while the module is installed. ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_FREQUENCY_UI: Frequency module (Inspector DOM <-> settings.frequency) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._frequencyWrapped) return;
    S._frequencyWrapped = true;

    var MODES = ['always', 'session', 'days'];

    function mode(v) {
        return (typeof v === 'string' && MODES.indexOf(v) !== -1) ? v : 'always';
    }
    function days(v) {
        var n = parseInt(v, 10);
        if (isNaN(n) || n < 1) return 7;
        return n > 365 ? 365 : n;
    }
    function sync() {
        $('#wppoppop-fq-days-row').css('display', $('#set-freq-mode').val() === 'days' ? '' : 'none');
    }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.frequency = {
            mode: mode($('#set-freq-mode').val()),
            days: days($('#set-freq-days').val())
        };
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var f = (settings && settings.frequency && typeof settings.frequency === 'object') ? settings.frequency : {};
        $('#set-freq-mode').val(mode(f.mode));
        $('#set-freq-days').val(days(f.days));
        sync();
        return r;
    };

    $(document).on('change', '#set-freq-mode', sync);
    $(sync);
})(jQuery);
