/* Video Playback Listeners: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the switch and the timestamp are saved to and restored from settings.video ({ enable, time }).
 * Sanitising on save and the front attributes live in the core class (WpPopPop_Video_Listeners); the listener is module.js.
 * Only loaded while the module is installed. ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_VIDEO_UI: Video Playback Listeners module (Inspector DOM <-> settings.video) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._vidWrapped) return;
    S._vidWrapped = true;

    function on(v) { return v === true || v === 1 || v === '1' || v === 'true' || v === 'on'; }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.video = {
            enable: $('#set-vid-enable').is(':checked'),
            time: Math.max(0, parseInt($('#set-vid-time').val(), 10) || 0)
        };
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var v = (settings && settings.video && typeof settings.video === 'object') ? settings.video : {};
        var t = parseInt(v.time, 10);
        $('#set-vid-enable').prop('checked', on(v.enable));
        $('#set-vid-time').val(t > 0 ? String(t) : '');
        return r;
    };
})(jQuery);
