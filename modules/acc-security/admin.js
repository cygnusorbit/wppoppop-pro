/* Security & Protections: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the switches, the antibot options and the personal data key fields are saved to
 * and restored from settings.security. Sanitising on save and the checks at submission time live in the core class
 * (WpPopPop_Protect). Only loaded while the module is installed.
 * ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_PROTECT_UI: Security & Protections module (Inspector DOM <-> settings.security) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._spWrapped) return;
    S._spWrapped = true;

    function tr(t) { return (window.wp && window.wp.i18n && window.wp.i18n.__) ? window.wp.i18n.__(t, 'wppoppop') : t; }
    function esc(v) {
        return String(v === undefined || v === null ? '' : v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function trim(v) { return String(v === undefined || v === null ? '' : v).replace(/^\s+|\s+$/g, ''); }
    function on(v, d) {
        if (v === undefined || v === null) return d;
        if (v === true || v === 1 || v === '1' || v === 'true' || v === 'on') return true;
        if (v === false || v === 0 || v === '0' || v === 'false' || v === 'off' || v === '') return false;
        return d;
    }

    var DEFAULT_ERROR = 'Thank you. We have already got your request.';
    var MAX_KEYS = 20;
    var keys = [];
    var CHECKS = {
        save_ip: ['#set-sp-save-ip', true], save_ua: ['#set-sp-save-ua', true], email_tech: ['#set-sp-email-tech', true],
        record_tech: ['#set-sp-record-tech', true], disable_scroll: ['#set-sp-disable-scroll', false],
        antibot: ['#set-sp-antibot', true], antibot_form: ['#set-sp-ab-form', true], antibot_ip: ['#set-sp-ab-ip', true]
    };

    function fieldTokens() {
        var out = [], seen = {};
        var core = window.WpPopPopBuilderCore;
        if (core && core.state && core.state.canvases) {
            Object.keys(core.state.canvases).forEach(function(c) {
                (core.state.canvases[c] || []).forEach(function(el) {
                    var tok = el && (el.field_name || el.fieldName || el.fieldId);
                    if (!tok || seen[tok]) return;
                    seen[tok] = true;
                    out.push({ tok: String(tok), type: el.type || 'field' });
                });
            });
        }
        return out;
    }

    function typeOf(tok) {
        var t = fieldTokens();
        for (var i = 0; i < t.length; i++) { if (t[i].tok === tok) return t[i].type; }
        return '';
    }

    function renderKeys() {
        var h = '';
        $.each(keys, function(i, k) {
            var ty = typeOf(k);
            h += '<span class="wppoppop-sp-chip" data-key="' + esc(k) + '"><span class="wppoppop-sp-chip-t">' + esc(k + (ty ? ' | ' + ty : '')) + '</span>' +
                '<button type="button" class="wppoppop-sp-chip-x" title="' + esc(tr('Remove')) + '">&times;</button></span>';
        });
        $('#wppoppop-sp-keys').html(h || '<span class="wppoppop-sp-none">' + esc(tr('No field selected')) + '</span>');
        renderAdd();
    }

    function renderAdd() {
        var $s = $('#wppoppop-sp-key-add');
        if (!$s.length) return;
        var h = '<option value="">' + esc(tr('Add field')) + '</option>';
        $.each(fieldTokens(), function(i, t) {
            if ($.inArray(t.tok, keys) === -1) h += '<option value="' + esc(t.tok) + '">' + esc(t.tok + ' | ' + t.type) + '</option>';
        });
        $s.html(h).val('');
    }

    function toggleAb() { $('#wppoppop-sp-ab').css('display', $('#set-sp-antibot').is(':checked') ? '' : 'none'); }

    function delayVal() {
        var n = parseInt($('#set-sp-delay').val(), 10);
        if (isNaN(n)) n = 10;
        return Math.max(1, Math.min(3600, n));
    }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        if (!$('#set-sp-antibot').length) return out;
        var m = {};
        $.each(CHECKS, function(k, c) { m[k] = $(c[0]).is(':checked'); });
        m.personal_keys = keys.slice(0);
        m.antibot_delay = delayVal();
        m.antibot_error = trim($('#set-sp-ab-error').val()) || DEFAULT_ERROR;
        out.security = m;
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var m = (settings && settings.security && typeof settings.security === 'object') ? settings.security : {};
        $.each(CHECKS, function(k, c) { $(c[0]).prop('checked', on(m[k], c[1])); });
        var n = parseInt(m.antibot_delay, 10);
        $('#set-sp-delay').val(isNaN(n) ? 10 : Math.max(1, Math.min(3600, n)));
        $('#set-sp-ab-error').val(m.antibot_error ? String(m.antibot_error) : DEFAULT_ERROR);
        keys = [];
        if ($.isArray(m.personal_keys)) {
            $.each(m.personal_keys, function(i, k) {
                k = trim(k);
                if (k && $.inArray(k, keys) === -1 && keys.length < MAX_KEYS) keys.push(k);
            });
        }
        renderKeys();
        toggleAb();
        return r;
    };

    $(document)
        .off('.wppSp')
        .on('change.wppSp', '#set-sp-antibot', toggleAb)
        .on('change.wppSp', '#set-sp-delay', function() { $(this).val(delayVal()); })
        .on('change.wppSp', '#wppoppop-sp-key-add', function() {
            var v = $(this).val();
            if (v && $.inArray(v, keys) === -1 && keys.length < MAX_KEYS) { keys.push(v); }
            renderKeys();
        })
        .on('mousedown.wppSp focus.wppSp', '#wppoppop-sp-key-add', renderAddKeep)
        .on('click.wppSp', '.wppoppop-sp-chip-x', function(e) {
            e.preventDefault();
            var k = $(this).closest('.wppoppop-sp-chip').attr('data-key');
            keys = $.grep(keys, function(x) { return x !== k; });
            renderKeys();
        })
        .on('click.wppSp', '.wppoppop-acc-item[data-html-module="acc-security"] > .wppoppop-acc-header', function() { setTimeout(renderKeys, 0); });

    /* the field list can change while the builder is open: refresh the options before the menu opens */
    function renderAddKeep() {
        var $s = $('#wppoppop-sp-key-add');
        if ($s.find('option').length - 1 !== $.grep(fieldTokens(), function(t) { return $.inArray(t.tok, keys) === -1; }).length) renderAdd();
    }
})(jQuery);
