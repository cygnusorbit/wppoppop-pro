/* Conditional Logic & Math: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the formula is saved to and restored from settings.logic_math ({ formula, target }).
 * Sanitising on save and the calculation in the browser live in the core class and module.js.
 * Only loaded while the module is installed. ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_LOGIC_MATH_UI: Conditional Logic & Math module (Inspector DOM <-> settings.logic_math) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._lmWrapped) return;
    S._lmWrapped = true;

    function str(v) { return (v === undefined || v === null || typeof v === 'object') ? '' : String(v); }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.logic_math = {
            formula: $('#set-math-formula').val() || '',
            target: $('#set-math-target').val() || ''
        };
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var m = (settings && settings.logic_math && typeof settings.logic_math === 'object') ? settings.logic_math : {};
        $('#set-math-formula').val(str(m.formula));
        $('#set-math-target').val(str(m.target));
        return r;
    };
})(jQuery);
