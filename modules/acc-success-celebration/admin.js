/* Success Celebration: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the chosen effect is saved to and restored from settings.celebration.effect.
 * The effect list ships in accordion.html; the allow-list on save stays in the core (ajax-builder / ajax-templates)
 * and the effects themselves run on the front end (front-submit.js, front-gamification.js).
 * Only loaded while the module is installed. ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_CELEBRATION_UI: per-popup success celebration effect */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._celebrationWrapped) return;
    S._celebrationWrapped = true;
    var ALLOWED = ['default', 'none', 'burst', 'cannon', 'fireworks', 'rain', 'stars', 'both'];
    function norm(v) { return ALLOWED.indexOf(v) !== -1 ? v : 'default'; }
    var origGet = S.getSettings, origSet = S.setSettings;
    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.celebration = { effect: norm($('#set-celebration-effect').val()) };
        return out;
    };
    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var eff = (settings && settings.celebration && settings.celebration.effect) || 'default';
        $('#set-celebration-effect').val(norm(eff));
        return r;
    };
})(jQuery);
