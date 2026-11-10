/* GetResponse: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the GetResponse cards (API key, campaign, field mapping, conditional logic) are
 * saved to and restored from settings.getresponse. Sanitising, sending and the campaign / custom field lookups stay in
 * the core (WpPopPop_Getresponse and WpPopPop_Ajax_Getresponse). The accordion ships only the list container and the
 * add button; the cards are built here. Only loaded while the module is installed.
 * ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_GETRESPONSE_UI: GetResponse module (Inspector DOM <-> settings.getresponse) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._grWrapped) return;
    S._grWrapped = true;

    function tr(t) { return (window.wp && window.wp.i18n && window.wp.i18n.__) ? window.wp.i18n.__(t, 'wppoppop') : t; }

    var MAX = 20;
    var list = [];
    var cache = {};
    var OPS = [['is', 'is'], ['is_not', 'is not'], ['contains', 'contains'], ['not_contains', 'does not contain'], ['empty', 'is empty'], ['not_empty', 'is not empty']];
    var BASE = { EMAIL: ['Email', 'Email Address'], NAME: ['Name', 'Full name'] };

    function uid() { return 'gr_' + Math.random().toString(36).slice(2, 8); }
    function esc(v) {
        return String(v === undefined || v === null ? '' : v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function isTrue(v) { return v === true || v === 1 || v === '1' || v === 'true'; }
    function trim(v) { return String(v === undefined || v === null ? '' : v).replace(/^\s+|\s+$/g, ''); }
    function keyOk(k) { return /^[A-Za-z0-9]{8,64}$/.test(trim(k)); }
    function isPlain(o) { return o && typeof o === 'object' && !$.isArray(o); }
    function norm(s) { return String(s === undefined || s === null ? '' : s).toLowerCase().replace(/[^a-z0-9]+/g, ''); }

    function blank() {
        return {
            id: uid(), name: 'GetResponse', enable: true, send: 'after_submission',
            api_key: '', campaign_id: '', campaign_name: '',
            fields: { EMAIL: '{{email}}', NAME: '{{name}}' }, field_labels: { EMAIL: 'Email Address', NAME: 'Full name' },
            logic_enable: false, logic_match: 'all', rules: []
        };
    }

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

    function byId(id) { for (var i = 0; i < list.length; i++) { if (list[i].id === id) return list[i]; } return null; }
    function cur($el) { return byId($el.closest('.wppoppop-gr-card').attr('data-id')); }
    function $card(id) { return $('#wppoppop-gr-list .wppoppop-gr-card[data-id="' + id + '"]'); }

    function campaignOptions(n) {
        var c = cache[n.id] && cache[n.id].campaigns, h = '', found = false;
        h += '<option value="">' + esc(tr('Select campaign')) + '</option>';
        $.each(c || [], function(i, l) {
            var sel = (l.id === n.campaign_id);
            if (sel) found = true;
            h += '<option value="' + esc(l.id) + '"' + (sel ? ' selected' : '') + '>' + esc(l.name) + '</option>';
        });
        if (n.campaign_id && !found) { h += '<option value="' + esc(n.campaign_id) + '" selected>' + esc(n.campaign_name || n.campaign_id) + '</option>'; }
        return h;
    }

    function fieldRows(n) {
        var h = '', tags = ['EMAIL', 'NAME'];
        $.each(n.fields, function(tag) { if (tag !== 'EMAIL' && tag !== 'NAME') tags.push(tag); });
        $.each(tags, function(i, tag) {
            var val = n.fields[tag] === undefined ? '' : n.fields[tag];
            var lbl = n.field_labels && n.field_labels[tag] ? n.field_labels[tag] : '';
            var title = BASE[tag] ? tr(BASE[tag][0]) : (lbl || tag);
            var hint = BASE[tag] ? tr(BASE[tag][1]) : (lbl || tag);
            h += '<div class="wppoppop-gr-frow" data-tag="' + esc(tag) + '">' +
                '<span class="wppoppop-gr-flbl" title="' + esc(hint) + '">' + esc(title) + '</span>' +
                '<input type="text" class="widefat" data-f="' + esc(tag) + '" value="' + esc(val) + '" placeholder="' + esc(hint) + '">' +
                '<button type="button" class="wppoppop-gr-btn is-code wppoppop-gr-tag" title="' + esc(tr('Insert merge tag')) + '" aria-label="' + esc(tr('Insert merge tag')) + '" aria-haspopup="true" aria-expanded="false"><span class="dashicons" aria-hidden="true">&#xf150;</span></button>' +
                '</div>';
        });
        return h;
    }

    function ruleHtml(r) {
        var f = '<select data-r="field"><option value="">' + esc(tr('Select field')) + '</option>', found = false;
        $.each(fieldTokens(), function(i, t) { var sel = (r.field === t.tok); if (sel) found = true; f += '<option value="' + esc(t.tok) + '"' + (sel ? ' selected' : '') + '>' + esc(t.tok) + '</option>'; });
        if (r.field && !found) { f += '<option value="' + esc(r.field) + '" selected>' + esc(r.field) + '</option>'; }
        f += '</select>';
        var o = '<select data-r="operator">';
        $.each(OPS, function(i, p) { o += '<option value="' + p[0] + '"' + (r.operator === p[0] ? ' selected' : '') + '>' + p[1] + '</option>'; });
        o += '</select>';
        return '<div class="wppoppop-gr-rule">' + f + o + '<input type="text" data-r="value" value="' + esc(r.value) + '" placeholder="' + esc(tr('Value')) + '">' +
            '<button type="button" class="wppoppop-gr-x wppoppop-gr-rule-del" aria-label="' + esc(tr('Remove rule')) + '">&times;</button></div>';
    }

    function switchHtml(key, on, label) {
        return '<label class="wppoppop-gr-switch"><input type="checkbox" role="switch" data-k="' + key + '"' + (on ? ' checked' : '') + '><span class="wppoppop-gr-trk" aria-hidden="true"></span><span class="wppoppop-gr-swl">' + esc(label) + '</span></label>';
    }

    function cardHtml(n, open) {
        var rules = '';
        $.each(n.rules || [], function(i, r) { rules += ruleHtml(r); });
        var hasKey = keyOk(n.api_key);
        return '<div class="wppoppop-gr-card" data-id="' + esc(n.id) + '">' +
            '<div class="wppoppop-gr-head">' +
                '<strong class="wppoppop-gr-title">' + esc(n.name || 'GetResponse') + '</strong>' +
                '<span class="wppoppop-gr-icons">' +
                    '<button type="button" class="wppoppop-gr-icon wppoppop-gr-del" title="' + esc(tr('Delete')) + '" aria-label="' + esc(tr('Delete GetResponse')) + '"><span class="dashicons dashicons-trash"></span></button>' +
                    '<button type="button" class="wppoppop-gr-icon wppoppop-gr-gear" title="' + esc(tr('Settings')) + '" aria-label="' + esc(tr('Toggle GetResponse settings')) + '" aria-expanded="' + (open ? 'true' : 'false') + '"><span class="dashicons dashicons-admin-generic"></span></button>' +
                '</span>' +
            '</div>' +
            '<div class="wppoppop-gr-body" style="' + (open ? '' : 'display:none;') + '">' +
                '<div class="wppoppop-gr-row"><span class="wppoppop-gr-lbl">' + esc(tr('Name')) + '</span><div class="wppoppop-gr-namerow"><input type="text" class="widefat" data-k="name" value="' + esc(n.name) + '">' + switchHtml('enable', isTrue(n.enable), tr('Enabled')) + '</div></div>' +
                '<div class="wppoppop-gr-row"><span class="wppoppop-gr-lbl">' + esc(tr('Execute')) + '</span><select class="widefat" data-k="send">' +
                    '<option value="after_submission"' + (n.send !== 'after_confirmation' ? ' selected' : '') + '>' + esc(tr('After successful form submission')) + '</option>' +
                    '<option value="after_confirmation"' + (n.send === 'after_confirmation' ? ' selected' : '') + '>' + esc(tr('After double opt-in confirmation')) + '</option></select></div>' +
                '<div class="wppoppop-gr-row"><span class="wppoppop-gr-lbl">' + esc(tr('API Key')) + '</span><input type="text" class="widefat" data-k="api_key" autocomplete="off" spellcheck="false" value="' + esc(n.api_key) + '">' +
                    '<p class="wppoppop-gr-help">' + esc(tr('Find your GetResponse API Key')) + ' <a href="https://app.getresponse.com/api" target="_blank" rel="noopener noreferrer">' + esc(tr('here')) + '</a>.</p></div>' +
                '<div class="wppoppop-gr-row"><span class="wppoppop-gr-lbl">' + esc(tr('Campaign ID')) + '</span><select class="widefat wppoppop-gr-camp-sel"' + (hasKey ? '' : ' disabled') + '>' + campaignOptions(n) + '</select>' +
                    '<p class="wppoppop-gr-status" role="status" aria-live="polite"></p></div>' +
                '<div class="wppoppop-gr-row"><span class="wppoppop-gr-lbl">' + esc(tr('Fields')) + '</span><div class="wppoppop-gr-fields">' + fieldRows(n) + '</div>' +
                    '<button type="button" class="wppoppop-gr-btn is-sm wppoppop-gr-load wppoppop-gr-load-fields"><span class="dashicons dashicons-download" aria-hidden="true"></span><span>' + esc(tr('Load Fields')) + '</span></button></div>' +
                '<div class="wppoppop-gr-checks">' + switchHtml('logic_enable', isTrue(n.logic_enable), tr('Enable conditional logic')) + '</div>' +
                '<div class="wppoppop-gr-logic" style="' + (isTrue(n.logic_enable) ? '' : 'display:none;') + '">' +
                    '<div class="wppoppop-gr-match">' + esc(tr('Subscribe if')) + ' <select data-k="logic_match"><option value="all"' + (n.logic_match !== 'any' ? ' selected' : '') + '>' + esc(tr('all')) + '</option><option value="any"' + (n.logic_match === 'any' ? ' selected' : '') + '>' + esc(tr('any')) + '</option></select> ' + esc(tr('of the rules match:')) + '</div>' +
                    rules + '<button type="button" class="wppoppop-gr-btn is-sm wppoppop-gr-rule-add">' + esc(tr('+ Add rule')) + '</button>' +
                '</div>' +
            '</div></div>';
    }

    function render() {
        var $l = $('#wppoppop-gr-list');
        if (!$l.length) return;
        var openIds = {};
        $l.find('.wppoppop-gr-card').each(function() { if ($(this).find('.wppoppop-gr-body').css('display') !== 'none') openIds[$(this).attr('data-id')] = true; });
        var h = '';
        $.each(list, function(i, n) { h += cardHtml(n, !!openIds[n.id]); });
        if (!list.length) { h = '<p class="description wppoppop-gr-empty">' + esc(tr('No GetResponse connections yet. Click "+ GetResponse".')) + '</p>'; }
        $l.html(h);
        $('#wppoppop-gr-add').prop('disabled', list.length >= MAX);
    }

    function setStatus(id, msg, isErr) {
        var $s = $card(id).find('.wppoppop-gr-status');
        $s.text(msg || '').toggleClass('is-error', !!isErr);
    }

    function ajax(action, n, done) {
        var v = window.wppoppop_vars || {};
        $.post(v.ajax_url || window.ajaxurl, { action: action, nonce: v.nonce, api_key: trim(n.api_key) }, null, 'json')
            .done(function(res) {
                if (res && res.success) { done(res.data || {}); }
                else { setStatus(n.id, (res && res.data && res.data.message) || tr('GetResponse request failed.'), true); }
            })
            .fail(function() { setStatus(n.id, tr('GetResponse request failed.'), true); });
    }

    function loadCampaigns(n) {
        if (!keyOk(n.api_key)) return;
        var key = trim(n.api_key);
        setStatus(n.id, tr('Loading campaigns...'), false);
        ajax('wppoppop_getresponse_campaigns', n, function(data) {
            if (!byId(n.id) || trim(n.api_key) !== key) return;
            cache[n.id] = { campaigns: $.isArray(data.campaigns) ? data.campaigns : [] };
            var $sel = $card(n.id).find('.wppoppop-gr-camp-sel');
            $sel.html(campaignOptions(n)).prop('disabled', false);
            setStatus(n.id, cache[n.id].campaigns.length ? '' : tr('No campaigns found for this API key.'), !cache[n.id].campaigns.length);
        });
    }

    function autoMatch(tag, label) {
        var t = norm(tag === 'NAME' ? 'name' : (tag === 'EMAIL' ? 'email' : label)), hit = '';
        if (tag === 'EMAIL') return '{{email}}';
        if (!t) return '';
        $.each(fieldTokens(), function(i, f) {
            var k = norm(f.tok);
            if (!hit && (k === t || (tag === 'NAME' && k === 'fullname'))) hit = '{{' + f.tok + '}}';
        });
        return hit;
    }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.getresponse = $.map(list, function(n) { return $.extend(true, {}, n); });
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var src = settings && settings.getresponse;
        if (isPlain(src)) { src = $.map(src, function(v) { return v; }); }
        list = [];
        cache = {};
        $.each($.isArray(src) ? src : [], function(i, n) {
            if (!n || typeof n !== 'object') return;
            var m = $.extend(true, blank(), n);
            m.id = n.id ? String(n.id) : uid();
            m.enable = isTrue(n.enable) || n.enable === undefined;
            m.logic_enable = isTrue(n.logic_enable);
            m.fields = isPlain(n.fields) ? $.extend({}, n.fields) : { EMAIL: '{{email}}', NAME: '' };
            if (!m.fields.EMAIL) m.fields.EMAIL = '{{email}}';
            if (m.fields.NAME === undefined) m.fields.NAME = '';
            m.field_labels = isPlain(n.field_labels) ? $.extend({}, n.field_labels) : {};
            m.rules = $.isArray(n.rules) ? n.rules : [];
            list.push(m);
        });
        render();
        return r;
    };

    function openBody($c) {
        $c.find('.wppoppop-gr-body').show();
        $c.find('.wppoppop-gr-gear').attr('aria-expanded', 'true');
    }

    $(document)
        .off('.wppGr')
        .on('click.wppGr', '#wppoppop-gr-add', function(e) {
            e.preventDefault();
            if (list.length >= MAX) return;
            var n = blank();
            list.push(n);
            render();
            openBody($card(n.id));
        })
        .on('click.wppGr', '.wppoppop-gr-head', function(e) {
            if ($(e.target).closest('.wppoppop-gr-del').length) return;
            var $c = $(this).closest('.wppoppop-gr-card');
            var $b = $c.find('.wppoppop-gr-body');
            var show = ($b.css('display') === 'none');
            $b.toggle(show);
            $c.find('.wppoppop-gr-gear').attr('aria-expanded', show ? 'true' : 'false');
            var n = cur($c);
            if (show && n && keyOk(n.api_key) && !cache[n.id]) { loadCampaigns(n); }
        })
        .on('click.wppGr', '.wppoppop-gr-del', function(e) {
            e.preventDefault(); e.stopPropagation();
            var n = cur($(this));
            if (!n || !window.confirm(tr('Delete this GetResponse connection?'))) return;
            list = $.grep(list, function(x) { return x.id !== n.id; });
            delete cache[n.id];
            render();
        })
        .on('input.wppGr change.wppGr', '#wppoppop-gr-list [data-k]', function(e) {
            var n = cur($(this)); if (!n) return;
            var k = $(this).attr('data-k');
            n[k] = ($(this).attr('type') === 'checkbox') ? $(this).is(':checked') : $(this).val();
            var $c = $(this).closest('.wppoppop-gr-card');
            if (k === 'name') { $c.find('.wppoppop-gr-title').text(n.name || 'GetResponse'); }
            if (k === 'logic_enable') { $c.find('.wppoppop-gr-logic').toggle(!!n.logic_enable); }
            if (k === 'api_key') {
                var ok = keyOk(n.api_key);
                $c.find('.wppoppop-gr-camp-sel').prop('disabled', !ok);
                if (e.type === 'change') {
                    n.api_key = trim(n.api_key);
                    delete cache[n.id];
                    if (ok) { loadCampaigns(n); } else { setStatus(n.id, n.api_key ? tr('This does not look like a GetResponse API key.') : '', !!n.api_key); }
                }
            }
        })
        .on('change.wppGr', '.wppoppop-gr-camp-sel', function() {
            var n = cur($(this)); if (!n) return;
            n.campaign_id = $(this).val() || '';
            n.campaign_name = n.campaign_id ? $(this).find('option:selected').text() : '';
        })
        .on('input.wppGr change.wppGr', '#wppoppop-gr-list [data-f]', function() {
            var n = cur($(this)); if (!n) return;
            n.fields[$(this).attr('data-f')] = $(this).val();
        })
        .on('click.wppGr', '.wppoppop-gr-load-fields', function(e) {
            e.preventDefault();
            var n = cur($(this)); if (!n) return;
            if (!keyOk(n.api_key)) { setStatus(n.id, tr('Enter a valid GetResponse API key first.'), true); return; }
            setStatus(n.id, tr('Loading fields...'), false);
            ajax('wppoppop_getresponse_fields', n, function(data) {
                if (!byId(n.id)) return;
                var old = n.fields, f = {}, labels = {};
                $.each($.isArray(data.fields) ? data.fields : [], function(i, x) {
                    if (!x || !x.tag) return;
                    f[x.tag] = (old[x.tag] !== undefined && old[x.tag] !== '') ? old[x.tag] : autoMatch(x.tag, x.label);
                    labels[x.tag] = x.label || '';
                });
                if (!f.EMAIL) f.EMAIL = old.EMAIL || '{{email}}';
                if (f.NAME === undefined) f.NAME = old.NAME || '';
                n.fields = f;
                n.field_labels = labels;
                $card(n.id).find('.wppoppop-gr-fields').html(fieldRows(n));
                setStatus(n.id, '', false);
            });
        })
        .on('click.wppGr', '.wppoppop-gr-rule-add', function(e) {
            e.preventDefault();
            var n = cur($(this)); if (!n || n.rules.length >= 20) return;
            n.rules.push({ field: '', operator: 'is', value: '' });
            render();
            openBody($card(n.id));
        })
        .on('click.wppGr', '.wppoppop-gr-rule-del', function(e) {
            e.preventDefault();
            var n = cur($(this)); if (!n) return;
            var i = $(this).closest('.wppoppop-gr-card').find('.wppoppop-gr-rule').index($(this).closest('.wppoppop-gr-rule'));
            if (i >= 0) n.rules.splice(i, 1);
            render();
            openBody($card(n.id));
        })
        .on('input.wppGr change.wppGr', '#wppoppop-gr-list [data-r]', function() {
            var n = cur($(this)); if (!n) return;
            var i = $(this).closest('.wppoppop-gr-card').find('.wppoppop-gr-rule').index($(this).closest('.wppoppop-gr-rule'));
            if (i < 0 || !n.rules[i]) return;
            n.rules[i][$(this).attr('data-r')] = $(this).val();
        })
        .on('click.wppGr', '.wppoppop-acc-item[data-html-module="acc-getresponse"] > .wppoppop-acc-header', function() { setTimeout(render, 0); });

    $(function() { setTimeout(render, 300); });

    /* Merge-tag menu (the </> button next to a mapped field) */
    var $menu = null, $btn = null;

    function closeMenu() {
        if ($btn) { $btn.attr('aria-expanded', 'false'); }
        if ($menu) { $menu.remove(); }
        $menu = null; $btn = null;
    }

    function group(title, items) {
        var h = '<div class="wppoppop-gr-menu-h">' + esc(title) + '</div>';
        $.each(items, function(i, it) { h += '<button type="button" class="wppoppop-gr-menu-item" data-tag="' + esc(it[1]) + '">' + esc(it[0]) + '</button>'; });
        return h;
    }

    function openMenu($b) {
        closeMenu();
        $btn = $b;
        $b.attr('aria-expanded', 'true');
        var forms = [];
        $.each(fieldTokens(), function(i, f) { forms.push([f.tok + ' | ' + f.type, '{{' + f.tok + '}}']); });
        forms.push(['email', '{{email}}']);
        var gen = [['Popup name', '{{form-name}}'], ['Submission ID', '{{submission-id}}'], ['Visitor country', '{{country}}'], ['Site name', '{{site-name}}']];
        $menu = $('<div class="wppoppop-gr-menu" role="menu"></div>').html(group(tr('Form values'), forms) + group(tr('General'), gen));
        $b.closest('.wppoppop-gr-frow').append($menu);
    }

    $(document)
        .off('.wppGrMenu')
        .on('click.wppGrMenu', '.wppoppop-gr-tag', function(e) {
            e.preventDefault(); e.stopPropagation();
            if ($btn && $btn[0] === this && $menu) { closeMenu(); } else { openMenu($(this)); }
        })
        .on('click.wppGrMenu', '.wppoppop-gr-menu-item', function(e) {
            e.preventDefault(); e.stopPropagation();
            var tag = $(this).attr('data-tag');
            var $t = $btn ? $btn.closest('.wppoppop-gr-frow').find('[data-f]') : $();
            var el = $t.get(0);
            if (el && tag) {
                var v = $t.val() || '';
                var a = (typeof el.selectionStart === 'number') ? el.selectionStart : v.length;
                var b = (typeof el.selectionEnd === 'number') ? el.selectionEnd : a;
                $t.val(v.slice(0, a) + tag + v.slice(b));
                try { el.focus(); el.setSelectionRange(a + tag.length, a + tag.length); } catch (err) {}
                $t.trigger('input');
            }
            closeMenu();
        })
        .on('click.wppGrMenu', function(e) {
            if ($menu && !$(e.target).closest('.wppoppop-gr-menu, .wppoppop-gr-tag').length) { closeMenu(); }
        })
        .on('keydown.wppGrMenu', function(e) {
            if ($menu && (e.key === 'Escape' || e.keyCode === 27)) { e.stopPropagation(); closeMenu(); }
        });
})(jQuery);
