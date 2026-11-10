/**
 * WpPopPop Builder: Targeting Triggers module (Popup Settings > Targeting Triggers)
 * Data lifecycle: inspector DOM <-> JS model <-> getSettings()/setSettings() <-> save payload.
 * The model (not the DOM) is the source of truth: ids missing from a (limited) list are never dropped.
 * Only loaded while the module is installed; the UI is built here from the data the core provides.
 * Cross-browser: ES5 only (Chrome, Safari, Firefox, Edge).
 */
(function ($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._ttWrapped) { return; }
    S._ttWrapped = true;

    var DRAWER = '#wppoppop-settings-drawer';
    var ROOT = '#wppoppop-tt-item';
    var EVENTS = ['onload', 'onscroll', 'onexit', 'oninactivity'];
    var MODES = ['every-time', 'once-only', 'once-per-period'];
    var AREAS = ['sitewide', 'homepage', 'posts', 'pages', 'url'];
    var LISTS = ['cats', 'tags', 'posts', 'pages'];
    var NUM = { period_hours: [1, 8760, 24], start_delay: [0, 3600, 0], close_delay: [0, 3600, 0], scroll_offset: [0, 100000, 600], idle_delay: [1, 3600, 15] };
    var FLAGS = ['cats_all', 'tags_all', 'posts_all', 'pages_all', 'cats_archive', 'tags_archive'];
    var FLAG_DEF = { cats_all: true, tags_all: true, posts_all: true, pages_all: true, cats_archive: false, tags_archive: false };
    var model = blank();

    /* Everything below the header is built here: the sanitised accordion.html can't carry data-*, role or aria-*.
       Lists and translated labels come from the core (WpPopPop_Targeting_Triggers::localize_builder). */
    var DATA = window.WpPopPopTargetingData || {};
    function L(s) { return (DATA.i18n && DATA.i18n[s]) ? DATA.i18n[s] : s; }
    function items(k) { return Array.isArray(DATA[k]) ? DATA[k] : []; }

    function rolesList() {
        var r = items('roles');
        return r.length ? r : [['all', L('All roles')], ['visitor', L('Visitors (logged out)')]];
    }

    function el(tag, attrs, text) {
        var $e = $(document.createElement(tag));
        if (attrs) { $.each(attrs, function (k, v) { $e.attr(k, v); }); }
        if (text !== undefined) { $e.text(text); }
        return $e;
    }
    function head(text) { return el('span', { 'class': 'tt-h' }, text); }
    function hint(text, cond) {
        var $p = el('p', { 'class': 'tt-hint' }, text);
        if (cond) { $p.attr('data-tt-when', cond); }
        return $p;
    }
    function when(cond, cls) {
        var a = { 'data-tt-when': cond };
        if (cls) { a['class'] = cls; }
        return el('div', a).hide();
    }
    function seg(group, label, opts) {
        var $s = el('div', { 'class': 'tt-segs', role: 'radiogroup', 'aria-label': label });
        $.each(opts, function (i, o) {
            $s.append(el('button', { type: 'button', 'class': 'tt-seg', role: 'radio', 'aria-checked': 'false', 'data-tt-group': group, 'data-tt-val': o[0] }, o[1]));
        });
        return $s;
    }
    function allSpec(key, a, b) { return seg(key + '_all', '', [['true', a], ['false', b]]); }
    function row($r, id, label, attrs, unit) {
        $r.append(el('label', { 'for': id }, label));
        $r.append(el('input', $.extend({ id: id, 'class': 'widefat' }, attrs)));
        if (unit) { $r.append(el('span', { 'class': 'tt-unit' }, unit)); }
        return $r;
    }
    function numRow($r, id, label, field, min, max, val, unit) {
        return row($r, id, label, { type: 'number', 'data-tt-field': field, min: min, max: max, step: 1, value: val }, unit);
    }
    function chk(key, val, label) {
        return el('label', { 'class': 'tt-chk' })
            .append(el('input', { type: 'checkbox', 'data-tt-item': key, value: val }))
            .append(el('span', null, label));
    }
    function list($to, key, filter) {
        var $l = el('div', { 'class': 'tt-list', 'data-tt-list': key }), data = items(key);
        if (filter) {
            $to.append(el('input', { type: 'search', 'class': 'widefat tt-filter', 'data-tt-filter': key, placeholder: L('Search...'), autocomplete: 'off' }));
        }
        if (!data.length) { $l.append(el('p', { 'class': 'tt-none' }, L('Nothing found.'))); }
        $.each(data, function (i, d) { $l.append(chk(key, d[0], d[1])); });
        $to.append($l);
    }
    function sub(title, key, a, b, flag, flagText) {
        var $s = el('div', { 'class': 'tt-sub' }), $w = when(key + '_all:false');
        $s.append(head(title)).append(allSpec(key, a, b));
        list($w, key, true);
        $s.append($w);
        if (flag) {
            $s.append(el('label', { 'class': 'tt-chk tt-mt6' })
                .append(el('input', { type: 'checkbox', 'data-tt-flag': flag }))
                .append(el('span', null, flagText)));
        }
        return $s;
    }

    function build() {
        var $root = $(ROOT), $fs, $props, $sec, $w, $r, $a;
        if (!$root.length || $root.children().length) { return; }

        $root.append(el('div', { 'class': 'tt-top' })
            .append(head(L('Popup Targeting')))
            .append(el('label', { 'class': 'tt-master', title: L('Turn the entire Targeting Triggers setting on or off') })
                .append(el('input', { type: 'checkbox', id: 'set-tt-enabled', checked: 'checked', 'aria-label': L('Enable Targeting Triggers') }))
                .append(el('span', { 'class': 'tt-track', 'aria-hidden': 'true' }))));

        $fs = el('fieldset', { id: 'wppoppop-tt-fs' });
        $root.append($fs);

        $fs.append(el('div', { 'class': 'tt-sec' })
            .append(seg('event', L('Popup Targeting'), [['onload', L('OnLoad')], ['onscroll', L('OnScroll')], ['onexit', L('OnExit')], ['oninactivity', L('OnInactivity')]]))
            .append(hint(L('Choose when the popup appears. Until one is selected the popup keeps its default behavior.'), 'event:')));

        $props = el('div', { 'class': 'tt-props', 'data-tt-has': 'event' }).hide();
        $fs.append($props);
        $props.append(head(L('Target Properties')));

        $sec = el('div', { 'class': 'tt-sec' }).append(head(L('Periodicity')))
            .append(seg('mode', L('Periodicity'), [['every-time', L('Every time')], ['once-only', L('Once only')], ['once-per-period', L('Once per period')]]));
        $sec.append(numRow(when('mode:once-per-period', 'tt-row'), 'set-tt-period', L('Show again after'), 'period_hours', 1, 8760, 24, L('hrs')));
        $props.append($sec);

        $sec = when('event:onload', 'tt-sec');
        $sec.append(numRow(el('div', { 'class': 'tt-row' }), 'set-tt-start', L('Start delay'), 'start_delay', 0, 3600, 0, L('sec')));
        $sec.append(numRow(el('div', { 'class': 'tt-row' }), 'set-tt-close', L('Close delay'), 'close_delay', 0, 3600, 0, L('sec')));
        $sec.append(hint(L('Close delay 0 keeps the popup open until it is closed.')));
        $props.append($sec);

        $sec = when('event:onscroll', 'tt-sec');
        $sec.append(numRow(el('div', { 'class': 'tt-row' }), 'set-tt-offset', L('Scrolling offset'), 'scroll_offset', 0, 100000, 600));
        $sec.append(el('div', { 'class': 'tt-mt8' }).append(seg('scroll_unit', L('Scrolling offset unit'), [['px', 'px'], ['%', '%']])));
        $props.append($sec);

        $sec = when('event:oninactivity', 'tt-sec');
        $sec.append(numRow(el('div', { 'class': 'tt-row' }), 'set-tt-idle', L('Inactivity delay'), 'idle_delay', 1, 3600, 15, L('sec')));
        $props.append($sec);

        $sec = el('div', { 'class': 'tt-sec' }).append(head(L('Activity')))
            .append(seg('activity', L('Activity'), [['always', L('Always')], ['period', L('Certain period')]]));
        $w = when('activity:period');
        $w.append(row(el('div', { 'class': 'tt-row' }), 'set-tt-from', L('From'), { type: 'date', 'data-tt-field': 'from', placeholder: 'YYYY-MM-DD' }));
        $w.append(row(el('div', { 'class': 'tt-row' }), 'set-tt-to', L('End'), { type: 'date', 'data-tt-field': 'to', placeholder: 'YYYY-MM-DD' }));
        $sec.append($w);
        $props.append($sec);

        $sec = el('div', { 'class': 'tt-sec' }).append(head(L('User Roles')));
        $r = el('div', { 'class': 'tt-list tt-flush', 'data-tt-list': 'roles' });
        $.each(rolesList(), function (i, d) { $r.append(chk('roles', d[0], d[1])); });
        $sec.append($r);
        $props.append($sec);

        $sec = el('div', { 'class': 'tt-sec' }).append(head(L('Site Area')))
            .append(seg('area', L('Site Area'), [['sitewide', L('Sitewide')], ['homepage', L('Homepage')], ['posts', L('Posts')], ['pages', L('Pages')], ['url', L('URL')]]));

        $a = when('area:posts');
        $a.append(sub(L('Categories'), 'cats', L('All'), L('Specific'), 'cats_archive', L('Also show on category archive pages')));
        $a.append(sub(L('Tags'), 'tags', L('All'), L('Specific'), 'tags_archive', L('Also show on tag archive pages')));
        $a.append(sub(L('Posts'), 'posts', L('All'), L('Specific')));
        $a.append(hint(L('A post matches when it is in any selected category, tag or post list.')));
        $sec.append($a);

        $a = when('area:pages');
        $a.append(sub(L('Pages'), 'pages', L('All pages'), L('Specific pages')));
        $sec.append($a);

        $a = when('area:url');
        $a.append(el('div', { 'class': 'tt-sub' })
            .append(el('label', { 'for': 'set-tt-url', 'class': 'tt-h' }, L('URL contains')))
            .append(el('textarea', { id: 'set-tt-url', 'class': 'widefat', 'data-tt-field': 'url_keywords', rows: 3, placeholder: '/pricing' + String.fromCharCode(10) + '/blog/summer-sale' }))
            .append(hint(L('One per line. The popup shows when the page address contains any of them. Empty = every URL.'))));
        $sec.append($a);
        $props.append($sec);
    }

    function blank() {
        return {
            enabled: true, event: '', mode: 'every-time', period_hours: 24, start_delay: 0, close_delay: 0,
            scroll_offset: 600, scroll_unit: 'px', idle_delay: 15, activity: 'always', from: '', to: '',
            roles: ['all'], area: 'sitewide',
            cats_all: true, cats: [], cats_archive: false, tags_all: true, tags: [], tags_archive: false,
            posts_all: true, posts: [], pages_all: true, pages: [], url_keywords: ''
        };
    }
    function pick(v, list, def) { return $.inArray(v, list) !== -1 ? v : def; }
    function bool(v) { return v === true || v === 1 || v === '1' || v === 'true'; }
    function num(v, min, max, def) {
        var n = parseFloat(v);
        if (isNaN(n)) { return def; }
        return Math.max(min, Math.min(max, Math.round(n)));
    }
    function ids(v) {
        var out = [], seen = {}, i, n;
        if (Array.isArray(v)) {
            for (i = 0; i < v.length; i++) {
                n = parseInt(v[i], 10);
                if (n > 0 && !seen[n]) { seen[n] = 1; out.push(n); }
            }
        }
        return out;
    }
    function roles(v) {
        var out = [], i, r;
        if (Array.isArray(v)) {
            for (i = 0; i < v.length; i++) {
                r = String(v[i]);
                if (/^[A-Za-z0-9_\-]+$/.test(r) && $.inArray(r, out) === -1) { out.push(r); }
            }
        }
        return (!out.length || $.inArray('all', out) !== -1) ? ['all'] : out;
    }
    function date(v) { return (typeof v === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(v)) ? v : ''; }
    function has(o, k) { return Object.prototype.hasOwnProperty.call(o, k); }

    function normalize(src) {
        var d = blank(), m = blank(), k, i;
        if (!src || typeof src !== 'object') { return m; }
        m.enabled = has(src, 'enabled') ? bool(src.enabled) : d.enabled;
        m.event = pick(src.event, EVENTS, '');
        m.mode = pick(src.mode, MODES, 'every-time');
        m.scroll_unit = pick(src.scroll_unit, ['px', '%'], 'px');
        m.activity = pick(src.activity, ['always', 'period'], 'always');
        m.area = pick(src.area, AREAS, 'sitewide');
        for (k in NUM) { if (has(NUM, k)) { m[k] = num(src[k], NUM[k][0], NUM[k][1], NUM[k][2]); } }
        if (m.scroll_unit === '%') { m.scroll_offset = Math.min(100, m.scroll_offset); }
        for (i = 0; i < FLAGS.length; i++) { m[FLAGS[i]] = has(src, FLAGS[i]) ? bool(src[FLAGS[i]]) : FLAG_DEF[FLAGS[i]]; }
        for (i = 0; i < LISTS.length; i++) { m[LISTS[i]] = ids(src[LISTS[i]]); }
        m.roles = roles(src.roles);
        m.from = date(src.from);
        m.to = date(src.to);
        if (m.from && m.to && m.from > m.to) { k = m.from; m.from = m.to; m.to = k; }
        m.url_keywords = typeof src.url_keywords === 'string' ? src.url_keywords.substring(0, 20000) : '';
        return m;
    }

    function clone(m) { return $.extend(true, {}, m); }

    function paint() {
        var $root = $(ROOT);
        if (!$root.length) { return; }

        $root.find('[data-tt-group]').each(function () {
            var on = String(model[$(this).attr('data-tt-group')]) === $(this).attr('data-tt-val');
            $(this).toggleClass('is-on', on).attr('aria-checked', on ? 'true' : 'false');
        });

        $root.find('[data-tt-field]').each(function () {
            if (this === document.activeElement) { return; }
            $(this).val(model[$(this).attr('data-tt-field')]);
        });

        $root.find('[data-tt-item]').each(function () {
            var key = $(this).attr('data-tt-item'), val = this.value, on;
            on = key === 'roles' ? $.inArray(val, model.roles) !== -1 : $.inArray(parseInt(val, 10), model[key]) !== -1;
            this.checked = on;
        });

        $root.find('[data-tt-flag]').each(function () { this.checked = !!model[$(this).attr('data-tt-flag')]; });

        $('#set-tt-enabled').prop('checked', model.enabled);
        $('#wppoppop-tt-fs').prop('disabled', !model.enabled);
        $root.toggleClass('is-off', !model.enabled);

        $root.find('[data-tt-when]').each(function () {
            var p = $(this).attr('data-tt-when').split(':');
            $(this).toggle(String(model[p[0]]) === p[1]);
        });
        $root.find('[data-tt-has]').each(function () {
            $(this).toggle(String(model[$(this).attr('data-tt-has')]) !== '');
        });
    }

    function changed() { $(DRAWER).trigger('change'); }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function () {
        var out = origGet.apply(this, arguments) || {};
        out.targeting_triggers = clone(model);
        return out;
    };

    S.setSettings = function (settings) {
        var r = origSet.apply(this, arguments);
        model = normalize(settings && settings.targeting_triggers);
        paint();
        return r;
    };

    function bind() {
        $(DRAWER)
            .off('.wppTT')
            .on('click.wppTT', ROOT + ' [data-tt-group]', function (e) {
                var g = $(this).attr('data-tt-group'), v = $(this).attr('data-tt-val');
                e.preventDefault();
                if ($.inArray(g, FLAGS) !== -1) { model[g] = bool(v); } else { model[g] = v; }
                if (g === 'scroll_unit' && v === '%') { model.scroll_offset = Math.min(100, model.scroll_offset); }
                model = normalize(model);
                paint();
                changed();
            })
            .on('input.wppTT change.wppTT', ROOT + ' [data-tt-field]', function (e) {
                var k = $(this).attr('data-tt-field'), v = $(this).val();
                if (NUM[k]) {
                    model[k] = num(v, NUM[k][0], (k === 'scroll_offset' && model.scroll_unit === '%') ? 100 : NUM[k][1], NUM[k][2]);
                } else if (k === 'from' || k === 'to') {
                    model[k] = date(v);
                } else if (k === 'url_keywords') {
                    model[k] = String(v).substring(0, 20000);
                }
                if (e.type === 'change') {
                    if (model.from && model.to && model.from > model.to) { v = model.from; model.from = model.to; model.to = v; }
                    $(this).val(model[k]);
                    paint();
                }
            })
            .on('change.wppTT', ROOT + ' [data-tt-item]', function () {
                var key = $(this).attr('data-tt-item'), raw = this.value, on = this.checked, cur, n;
                if (key === 'roles') {
                    if (raw === 'all') {
                        model.roles = ['all'];
                    } else {
                        cur = $.grep(model.roles, function (x) { return x !== 'all' && x !== raw; });
                        if (on) { cur.push(raw); }
                        model.roles = cur.length ? cur : ['all'];
                    }
                } else {
                    n = parseInt(raw, 10);
                    cur = $.grep(model[key], function (x) { return x !== n; });
                    if (on) { cur.push(n); }
                    model[key] = cur;
                }
                paint();
            })
            .on('change.wppTT', ROOT + ' [data-tt-flag]', function () {
                model[$(this).attr('data-tt-flag')] = this.checked;
            })
            .on('change.wppTT', '#set-tt-enabled', function () {
                model.enabled = this.checked;
                paint();
            })
            .on('input.wppTT', ROOT + ' [data-tt-filter]', function () {
                var q = String($(this).val()).replace(/^\s+|\s+$/g, '').toLowerCase(), key = $(this).attr('data-tt-filter');
                $(ROOT + ' [data-tt-list="' + key + '"] .tt-chk').each(function () {
                    $(this).toggle(!q || $(this).text().toLowerCase().indexOf(q) !== -1);
                });
            });
    }

    if ($(DRAWER).length) { bind(); }
    $(function () { build(); bind(); paint(); });
})(jQuery);
