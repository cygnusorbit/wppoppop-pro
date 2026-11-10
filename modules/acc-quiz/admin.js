/* Quiz & Lead Scoring: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the switch, the threshold, the pass / fail canvases and the confetti option are saved to
 * and restored from settings.quiz ({ enable, pass_threshold, pass_canvas, fail_canvas, confetti }). A popup without quiz data
 * resets the fields to their defaults. Sanitising on save lives in the core class (WpPopPop_Quiz).
 * Only loaded while the module is installed. ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_QUIZ_UI: Quiz module (Inspector DOM <-> settings.quiz) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._quizWrapped) return;
    S._quizWrapped = true;

    function on(v) { return v === true || v === 1 || v === '1' || v === 'true' || v === 'on'; }
    function int(sel, def, min) {
        var n = parseInt($(sel).val(), 10);
        return (isNaN(n) || n < min) ? def : n;
    }
    function pick(v, alt, def) {
        var n = parseInt(v !== undefined && v !== null && v !== '' ? v : alt, 10);
        return isNaN(n) ? def : n;
    }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.quiz = {
            enable: $('#set-quiz-enable').is(':checked'),
            pass_threshold: int('#set-quiz-pass', 70, 0),
            pass_canvas: int('#set-quiz-pass-canvas', 2, 1),
            fail_canvas: int('#set-quiz-fail-canvas', 3, 1),
            confetti: $('#set-quiz-confetti').is(':checked')
        };
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var q = (settings && settings.quiz && typeof settings.quiz === 'object') ? settings.quiz : {};
        $('#set-quiz-enable').prop('checked', on(q.enable));
        $('#set-quiz-pass').val(pick(q.pass_threshold, q.pass_score, 70));
        $('#set-quiz-pass-canvas').val(pick(q.pass_canvas, q.pass_screen, 2));
        $('#set-quiz-fail-canvas').val(pick(q.fail_canvas, q.fail_screen, 3));
        $('#set-quiz-confetti').prop('checked', on(q.confetti));
        return r;
    };
})(jQuery);
