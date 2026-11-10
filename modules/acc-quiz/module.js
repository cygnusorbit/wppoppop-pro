/* Quiz & Lead Scoring: front end.
 * A popup that carries data-quiz="1" (printed by WpPopPop_Quiz, only while the module is installed and switched on) adds up the
 * points of its chosen radio answers. A radio choice scores when its value ends in ":points" (Label|value:points, for example
 * Yes|yes:10). The total is kept as the {quiz_score} token (it is also submitted with the form) and the Next button of the last
 * question canvas is routed to the pass canvas (total >= data-quiz-pass) or to the fail canvas. Confetti fires on pass when enabled.
 * ES5 only (Chrome, Safari, Firefox, Edge). */
(function(window, document, $) {
    'use strict';
    if (!$ || window.__wppQuiz) return;
    window.__wppQuiz = true;

    var WRAP = '.wppoppop-popup-wrap[data-quiz="1"]';
    var NEXT = '.wppoppop-next-canvas-btn, .wppoppop-next-screen-btn, .wppoppop-next-step';
    var CANVAS = '.wppoppop-canvas-container, .wppoppop-canvas-stage';

    function points(v) {
        var m = /:\s*(-?\d+)\s*$/.exec(String(v === undefined || v === null ? '' : v));
        return m ? parseInt(m[1], 10) : null;
    }

    function num(el, name, def) {
        var n = parseInt(el.attr(name), 10);
        return isNaN(n) ? def : n;
    }

    function setToken($w, score) {
        var L = window.WpPopPopFront && window.WpPopPopFront.Logic;
        if (!L) return;
        if (typeof L.setToken === 'function') { L.setToken($w, 'quiz_score', score); }
        else if (L.tokens) { L.tokens.quiz_score = score; }
    }

    function evaluate($w) {
        var score = 0, last = 0, scored = 0;
        $w.find('input[type="radio"]').each(function() {
            var p = points(this.value);
            if (p === null) return;
            scored++;
            var c = parseInt($(this).closest(CANVAS).attr('data-canvas'), 10);
            if (!isNaN(c) && c > last) last = c;
            if (this.checked) score += p;
        });
        if (!scored) return;

        var pass = score >= num($w, 'data-quiz-pass', 70);
        var target = pass ? num($w, 'data-quiz-pass-canvas', 2) : num($w, 'data-quiz-fail-canvas', 3);
        setToken($w, score);
        $w.data('quizPassed', pass);

        $w.find(NEXT).each(function() {
            var $b = $(this);
            if (last) {
                var c = parseInt($b.closest(CANVAS).attr('data-canvas'), 10);
                if (c !== last) return;
            }
            /* jQuery caches data-* after the first read, so refresh both the attribute and the cache */
            $b.attr({ 'data-goto-canvas': target, 'data-goto-screen': target, 'data-goto': target, 'data-quiz-routed': '1' });
            $b.data('goto-canvas', target).data('goto-screen', target).data('goto', target);
        });
    }

    $(document).on('change input', WRAP + ' input[type="radio"]', function() {
        evaluate($(this).closest(WRAP));
    });

    $(document).on('click', WRAP + ' [data-quiz-routed="1"]', function() {
        var $w = $(this).closest(WRAP);
        if ($w.attr('data-quiz-confetti') === '1' && $w.data('quizPassed') && typeof window.confetti === 'function') {
            try { window.confetti({ particleCount: 120, spread: 70, origin: { y: 0.6 } }); } catch (e) { /* decorative only */ }
        }
    });

    $(function() { $(WRAP).each(function() { evaluate($(this)); }); });
})(window, document, window.jQuery);
