/* Downloads: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the download is saved to and restored from settings.downloads ({ enable, url }).
 * Sanitising on save, the one-time link on submit and the file handling stay in the core (WpPopPop_Downloads).
 * Only loaded while the module is installed. ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_DOWNLOADS_UI: Downloads module (Inspector DOM <-> settings.downloads) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._dlWrapped) return;
    S._dlWrapped = true;

    function isTrue(v) { return v === true || v === 1 || v === '1' || v === 'true' || v === 'on'; }
    function str(v) { return (v === undefined || v === null) ? '' : String(v); }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.downloads = {
            enable: $('#set-dl-enable').is(':checked'),
            url: $('#set-dl-url').val() || ''
        };
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var d = (settings && settings.downloads && typeof settings.downloads === 'object') ? settings.downloads : {};
        $('#set-dl-enable').prop('checked', isTrue(d.enable));
        $('#set-dl-url').val(str(d.url));
        return r;
    };
})(jQuery);
