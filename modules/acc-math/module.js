/* Conditional Logic & Math: front end.
 * Calculates the formula of every popup that carries data-math-formula / data-math-target attributes (printed by
 * WpPopPop_Logic_Math, only while the module is installed and switched on) and writes the result into the target
 * while the visitor fills the form. {name} is the value of the form field with that name.
 * No eval: the formula is parsed by a small calculator (+ - * / % ^, brackets, a few functions).
 * ES5 only (Chrome, Safari, Firefox, Edge). */
(function(window, document, $) {
    'use strict';
    if (!$ || window.__wppLogicMath) return;
    window.__wppLogicMath = true;

    var ROOT = '[data-math-formula]';
    var LIMIT = 1e15;
    var busy = false;

    function toNum(v) {
        var n = parseFloat(String(v === undefined || v === null ? '' : v).replace(/[^0-9.\-]/g, ''));
        return isFinite(n) ? n : NaN;
    }

    function fields(root, name) {
        var out = [], all = root.querySelectorAll('[data-field-name]'), i, n;
        for (i = 0; i < all.length; i++) {
            if (all[i].getAttribute('data-field-name') === name) out.push(all[i]);
        }
        if (out.length) return out;
        all = root.querySelectorAll('input, select, textarea');
        for (i = 0; i < all.length; i++) {
            n = all[i].getAttribute('name');
            if (n === name || n === 'fields[' + name + ']' || n === 'fields[' + name + '][]' || all[i].id === name) out.push(all[i]);
        }
        return out;
    }

    /* Number of a field: a number is used as typed, a ticked choice counts its numeric value (1 when it has none). */
    function fieldValue(root, name) {
        var els = fields(root, name), total = 0, i, j, el, t, n;
        for (i = 0; i < els.length; i++) {
            el = els[i];
            t = String(el.type || '').toLowerCase();
            if (t === 'checkbox' || t === 'radio') {
                if (el.checked) { n = toNum(el.value); total += isNaN(n) ? 1 : n; }
            } else if (el.tagName === 'SELECT' && el.multiple) {
                for (j = 0; j < el.options.length; j++) {
                    if (el.options[j].selected) { n = toNum(el.options[j].value); total += isNaN(n) ? 0 : n; }
                }
            } else {
                n = toNum(el.value);
                total += isNaN(n) ? 0 : n;
            }
        }
        return total;
    }

    var FUNCS = {
        round: function(a) { return Math.round(a[0]); },
        floor: function(a) { return Math.floor(a[0]); },
        ceil: function(a) { return Math.ceil(a[0]); },
        abs: function(a) { return Math.abs(a[0]); },
        sqrt: function(a) { return Math.sqrt(a[0]); },
        pow: function(a) { return Math.pow(a[0], a[1]); },
        min: function(a) { return Math.min.apply(null, a); },
        max: function(a) { return Math.max.apply(null, a); }
    };

    /* Recursive-descent calculator. Throws on anything it does not understand. */
    function calculate(src) {
        var pos = 0, depth = 0;

        function fail() { throw new Error('math'); }
        function skip() { while (pos < src.length && src.charAt(pos) === ' ') pos++; }
        function peek() { skip(); return src.charAt(pos); }
        function enter() { if (++depth > 40) fail(); }

        function expr() {
            enter();
            var v = term(), c;
            for (c = peek(); c === '+' || c === '-'; c = peek()) {
                pos++;
                v = (c === '+') ? v + term() : v - term();
            }
            depth--;
            return v;
        }
        function term() {
            var v = unary(), c, r;
            for (c = peek(); c === '*' || c === '/' || c === '%'; c = peek()) {
                pos++;
                r = unary();
                if (c === '*') v = v * r;
                else if (r === 0) fail();
                else v = (c === '/') ? v / r : v % r;
            }
            return v;
        }
        function unary() {
            var c = peek();
            if (c === '-') { pos++; enter(); var n = -unary(); depth--; return n; }
            if (c === '+') { pos++; return unary(); }
            return power();
        }
        function power() {
            var b = primary();
            if (peek() === '^') { pos++; return Math.pow(b, unary()); }
            return b;
        }
        function primary() {
            var c = peek(), m, rest, args, name;
            if (c === '(') {
                pos++;
                var v = expr();
                if (peek() !== ')') fail();
                pos++;
                return v;
            }
            rest = src.substring(pos);
            m = /^(\d+\.?\d*|\.\d+)/.exec(rest);
            if (m) { pos += m[0].length; return parseFloat(m[0]); }
            m = /^[a-z]+/i.exec(rest);
            if (!m) fail();
            name = m[0].toLowerCase();
            pos += m[0].length;
            if (peek() === '(') {
                pos++;
                args = [];
                if (peek() === ')') { pos++; } else {
                    for (;;) {
                        args.push(expr());
                        c = peek();
                        pos++;
                        if (c === ')') break;
                        if (c !== ',') fail();
                    }
                }
                if (!FUNCS.hasOwnProperty(name) || !args.length) fail();
                return FUNCS[name](args);
            }
            if (name === 'pi') return Math.PI;
            if (name === 'e') return Math.E;
            return fail();
        }

        var result = expr();
        if (peek() !== '') fail();
        return result;
    }

    function compute(root, formula) {
        if (!formula || formula.length > 200) return '';
        var text = formula.replace(/\{([^{}]{1,64})\}/g, function(all, name) {
            var v = fieldValue(root, name);
            if (!isFinite(v) || Math.abs(v) >= LIMIT) v = 0;
            return '(' + v.toFixed(10) + ')';
        });
        if (/[^0-9a-z.+\-*\/%^(), ]/i.test(text)) return '';
        try {
            var r = calculate(text);
            if (!isFinite(r) || Math.abs(r) >= LIMIT) return '';
            return String(Math.round(r * 100) / 100);
        } catch (e) {
            return '';
        }
    }

    function findTarget(root, t) {
        var el = document.getElementById(t), all, i, plain;
        if (el && root.contains(el)) return el;
        all = root.querySelectorAll('[data-field-name]');
        for (i = 0; i < all.length; i++) {
            if (all[i].getAttribute('data-field-name') === t) return all[i];
        }
        plain = t.replace(/^(wppoppop-layer-item-|wppoppop-layer-|layer-|wppoppop-el-|el-)/, '');
        all = root.querySelectorAll('[data-layer-id]');
        for (i = 0; i < all.length; i++) {
            var id = all[i].getAttribute('data-layer-id');
            if (id === t || id === plain) return all[i];
        }
        return null;
    }

    function show(el, val) {
        var tag = el.tagName, inner, span, type;
        if (tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT') {
            inner = el.querySelector('input:not([type=checkbox]):not([type=radio]):not([type=hidden]):not([type=file]), textarea');
            if (inner) {
                el = inner;
            } else {
                span = el.querySelector('.wppoppop-text-render') || el;
                if (span.textContent !== val) span.textContent = val;
                return;
            }
        }
        type = String(el.type || '').toLowerCase();
        if (tag === 'SELECT' || type === 'checkbox' || type === 'radio' || type === 'file') return;
        if (el.value !== val) {
            el.value = val;
            busy = true;
            try { $(el).trigger('change'); } finally { busy = false; }
        }
    }

    function run(root) {
        var formula = root.getAttribute('data-math-formula') || '';
        var target = root.getAttribute('data-math-target') || '';
        if (!formula || !target) return;
        var el = findTarget(root, target);
        if (el) show(el, compute(root, formula));
    }

    function watch(root) {
        if (root.__wppMath || !window.MutationObserver) return;
        root.__wppMath = true;
        var timer = null;
        new window.MutationObserver(function() {
            window.clearTimeout(timer);
            timer = window.setTimeout(function() { run(root); }, 50);
        }).observe(root, { attributes: true, attributeFilter: ['aria-hidden', 'style', 'class'] });
    }

    function init() {
        $(ROOT).each(function() { run(this); watch(this); });
    }

    $(document).on('input change keyup', ROOT + ' input, ' + ROOT + ' select, ' + ROOT + ' textarea', function() {
        if (busy) return;
        var root = $(this).closest(ROOT)[0];
        if (root) run(root);
    });

    $(init);
    $(window).on('load', init);
})(window, document, window.jQuery);
