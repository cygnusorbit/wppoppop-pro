/* MailChimp: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the MailChimp cards (API key, audience, merge fields, groups, tags, double opt-in,
 * conditional logic) are saved to and restored from settings.mailchimp. Sanitising, sending and the audience / field /
 * group lookups stay in the core (WpPopPop_Mailchimp and WpPopPop_Ajax_Mailchimp). The accordion ships only the list
 * container and the add button; the cards are built here. Only loaded while the module is installed.
 * ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_MAILCHIMP_UI: MailChimp module (Inspector DOM <-> settings.mailchimp) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._mcWrapped) return;
    S._mcWrapped = true;

    function tr(t) { return (window.wp && window.wp.i18n && window.wp.i18n.__) ? window.wp.i18n.__(t, 'wppoppop') : t; }

    var MAX = 20;
    var list = [];
    var cache = {};
    var OPS = [['is', 'is'], ['is_not', 'is not'], ['contains', 'contains'], ['not_contains', 'does not contain'], ['empty', 'is empty'], ['not_empty', 'is not empty']];

    function uid() { return 'mc_' + Math.random().toString(36).slice(2, 8); }
    function esc(v) {
        return String(v === undefined || v === null ? '' : v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function isTrue(v) { return v === true || v === 1 || v === '1' || v === 'true'; }
    function trim(v) { return String(v === undefined || v === null ? '' : v).replace(/^\s+|\s+$/g, ''); }
    function keyOk(k) { return /^[A-Za-z0-9]{8,64}-[a-z]{2}[0-9]{1,2}$/.test(trim(k)); }
    function isPlain(o) { return o && typeof o === 'object' && !$.isArray(o); }

    function blank() {
        return {
            id: uid(), name: 'MailChimp', enable: true, send: 'after_submission',
            api_key: '', list_id: '', list_name: '',
            fields: { EMAIL: '{{email}}' }, field_labels: { EMAIL: 'Email address' },
            groups: {}, group_defs: [], tags: '', double: false,
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
    function cur($el) { return byId($el.closest('.wppoppop-mc-card').attr('data-id')); }
    function $card(id) { return $('#wppoppop-mc-list .wppoppop-mc-card[data-id="' + id + '"]'); }

    function listOptions(n) {
        var c = cache[n.id] && cache[n.id].lists, h = '', found = false;
        h += '<option value="">' + esc(tr('Select audience')) + '</option>';
        $.each(c || [], function(i, l) {
            var sel = (l.id === n.list_id);
            if (sel) found = true;
            h += '<option value="' + esc(l.id) + '"' + (sel ? ' selected' : '') + '>' + esc(l.name) + '</option>';
        });
        if (n.list_id && !found) { h += '<option value="' + esc(n.list_id) + '" selected>' + esc(n.list_name || n.list_id) + '</option>'; }
        return h;
    }

    function fieldRows(n) {
        var h = '';
        $.each(n.fields, function(tag, val) {
            var lbl = n.field_labels && n.field_labels[tag] ? n.field_labels[tag] : '';
            h += '<div class="wppoppop-mc-frow" data-tag="' + esc(tag) + '">' +
                '<span class="wppoppop-mc-flbl" title="' + esc(lbl) + '">' + esc(tag) + '</span>' +
                '<input type="text" class="widefat" data-f="' + esc(tag) + '" value="' + esc(val) + '" placeholder="' + esc(lbl) + '">' +
                '<button type="button" class="wppoppop-mc-btn is-code wppoppop-mc-tag" title="' + esc(tr('Insert merge tag')) + '" aria-label="' + esc(tr('Insert merge tag')) + '" aria-haspopup="true" aria-expanded="false"><span class="dashicons" aria-hidden="true">&#xf150;</span></button>' +
                '</div>';
        });
        return h;
    }

    /* WPPOPPOP_MC_SWITCH: toggle switch markup (attr is data-k for a setting, data-g for a group) */
    function switchHtml(attr, key, on, label) {
        return '<label class="wppoppop-mc-switch"><input type="checkbox" role="switch" ' + attr + '="' + esc(key) + '"' + (on ? ' checked' : '') + '><span class="wppoppop-mc-trk" aria-hidden="true"></span><span class="wppoppop-mc-swl">' + esc(label) + '</span></label>';
    }

    function groupRows(n) {
        var h = '';
        $.each(n.group_defs || [], function(i, g) {
            h += switchHtml('data-g', g.id, !!n.groups[g.id], g.label || g.id);
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
        return '<div class="wppoppop-mc-rule">' + f + o + '<input type="text" data-r="value" value="' + esc(r.value) + '" placeholder="' + esc(tr('Value')) + '">' +
            '<button type="button" class="wppoppop-mc-x wppoppop-mc-rule-del" aria-label="' + esc(tr('Remove rule')) + '">&times;</button></div>';
    }

    function cardHtml(n, open) {
        var rules = '';
        $.each(n.rules || [], function(i, r) { rules += ruleHtml(r); });
        var hasKey = keyOk(n.api_key);
        return '<div class="wppoppop-mc-card" data-id="' + esc(n.id) + '">' +
            '<div class="wppoppop-mc-head">' +
                '<strong class="wppoppop-mc-title">' + esc(n.name || 'MailChimp') + '</strong>' +
                '<span class="wppoppop-mc-icons">' +
                    '<button type="button" class="wppoppop-mc-icon wppoppop-mc-del" title="' + esc(tr('Delete')) + '" aria-label="' + esc(tr('Delete MailChimp')) + '"><span class="dashicons dashicons-trash"></span></button>' +
                    '<button type="button" class="wppoppop-mc-icon wppoppop-mc-gear" title="' + esc(tr('Settings')) + '" aria-label="' + esc(tr('Toggle MailChimp settings')) + '" aria-expanded="' + (open ? 'true' : 'false') + '"><span class="dashicons dashicons-admin-generic"></span></button>' +
                '</span>' +
            '</div>' +
            '<div class="wppoppop-mc-body" style="' + (open ? '' : 'display:none;') + '">' +
                '<div class="wppoppop-mc-row"><span class="wppoppop-mc-lbl">' + esc(tr('Name')) + '</span><div class="wppoppop-mc-namerow"><input type="text" class="widefat" data-k="name" value="' + esc(n.name) + '">' + switchHtml('data-k', 'enable', isTrue(n.enable), tr('Enabled')) + '</div></div>' +
                '<div class="wppoppop-mc-row"><span class="wppoppop-mc-lbl">' + esc(tr('Execute')) + '</span><select class="widefat" data-k="send">' +
                    '<option value="after_submission"' + (n.send !== 'after_confirmation' ? ' selected' : '') + '>' + esc(tr('After successful form submission')) + '</option>' +
                    '<option value="after_confirmation"' + (n.send === 'after_confirmation' ? ' selected' : '') + '>' + esc(tr('After double opt-in confirmation')) + '</option></select></div>' +
                '<div class="wppoppop-mc-row"><span class="wppoppop-mc-lbl">' + esc(tr('API Key')) + '</span><input type="text" class="widefat" data-k="api_key" autocomplete="off" spellcheck="false" value="' + esc(n.api_key) + '">' +
                    '<p class="wppoppop-mc-help"><a href="https://mailchimp.com/help/about-api-keys/" target="_blank" rel="noopener noreferrer">' + esc(tr('Find your MailChimp API Key here')) + '</a></p></div>' +
                '<div class="wppoppop-mc-row"><span class="wppoppop-mc-lbl">' + esc(tr('List ID')) + '</span><select class="widefat wppoppop-mc-list-sel"' + (hasKey ? '' : ' disabled') + '>' + listOptions(n) + '</select>' +
                    '<p class="wppoppop-mc-status" role="status" aria-live="polite"></p></div>' +
                '<div class="wppoppop-mc-row"><span class="wppoppop-mc-lbl">' + esc(tr('Fields')) + '</span><div class="wppoppop-mc-fields">' + fieldRows(n) + '</div>' +
                    '<button type="button" class="wppoppop-mc-btn is-sm wppoppop-mc-load wppoppop-mc-load-fields"><span class="dashicons dashicons-download" aria-hidden="true"></span><span>' + esc(tr('Load Fields')) + '</span></button>' +
                    '<p class="wppoppop-mc-help">' + esc(tr('Map each MailChimp field to a form value, for example {{email}}.')) + '</p></div>' +
                '<div class="wppoppop-mc-row"><span class="wppoppop-mc-lbl">' + esc(tr('Groups')) + '</span><div class="wppoppop-mc-groups">' + groupRows(n) + '</div>' +
                    '<button type="button" class="wppoppop-mc-btn is-sm wppoppop-mc-load wppoppop-mc-load-groups"><span class="dashicons dashicons-download" aria-hidden="true"></span><span>' + esc(tr('Load Groups')) + '</span></button></div>' +
                '<div class="wppoppop-mc-row"><span class="wppoppop-mc-lbl">' + esc(tr('Tags')) + '</span><input type="text" class="widefat" data-k="tags" value="' + esc(n.tags) + '">' +
                    '<p class="wppoppop-mc-help">' + esc(tr('Comma-separated list of tags added to the subscriber.')) + '</p></div>' +
                '<div class="wppoppop-mc-checks">' + switchHtml('data-k', 'double', isTrue(n.double), tr('Double opt-in')) +
                switchHtml('data-k', 'logic_enable', isTrue(n.logic_enable), tr('Enable conditional logic')) + '</div>' +
                '<div class="wppoppop-mc-logic" style="' + (isTrue(n.logic_enable) ? '' : 'display:none;') + '">' +
                    '<div class="wppoppop-mc-match">' + esc(tr('Subscribe if')) + ' <select data-k="logic_match"><option value="all"' + (n.logic_match !== 'any' ? ' selected' : '') + '>' + esc(tr('all')) + '</option><option value="any"' + (n.logic_match === 'any' ? ' selected' : '') + '>' + esc(tr('any')) + '</option></select> ' + esc(tr('of the rules match:')) + '</div>' +
                    rules + '<button type="button" class="wppoppop-mc-btn is-sm wppoppop-mc-rule-add">' + esc(tr('+ Add rule')) + '</button>' +
                '</div>' +
            '</div></div>';
    }

    function render() {
        var $l = $('#wppoppop-mc-list');
        if (!$l.length) return;
        var openIds = {};
        $l.find('.wppoppop-mc-card').each(function() { if ($(this).find('.wppoppop-mc-body').css('display') !== 'none') openIds[$(this).attr('data-id')] = true; });
        var h = '';
        $.each(list, function(i, n) { h += cardHtml(n, !!openIds[n.id]); });
        if (!list.length) { h = '<p class="description wppoppop-mc-empty">' + esc(tr('No MailChimp connections yet. Click "+ MailChimp".')) + '</p>'; }
        $l.html(h);
        $('#wppoppop-mc-add').prop('disabled', list.length >= MAX);
    }

    function setStatus(id, msg, isErr) {
        var $s = $card(id).find('.wppoppop-mc-status');
        $s.text(msg || '').toggleClass('is-error', !!isErr);
    }

    function ajax(action, n, done) {
        var v = window.wppoppop_vars || {};
        $.post(v.ajax_url || window.ajaxurl, { action: action, nonce: v.nonce, api_key: trim(n.api_key), list_id: n.list_id || '' }, null, 'json')
            .done(function(res) {
                if (res && res.success) { done(res.data || {}); }
                else { setStatus(n.id, (res && res.data && res.data.message) || tr('MailChimp request failed.'), true); }
            })
            .fail(function() { setStatus(n.id, tr('MailChimp request failed.'), true); });
    }

    function loadLists(n) {
        if (!keyOk(n.api_key)) return;
        var key = trim(n.api_key);
        setStatus(n.id, tr('Loading audiences...'), false);
        ajax('wppoppop_mailchimp_lists', n, function(data) {
            if (!byId(n.id) || trim(n.api_key) !== key) return;
            cache[n.id] = { lists: $.isArray(data.lists) ? data.lists : [] };
            var $sel = $card(n.id).find('.wppoppop-mc-list-sel');
            $sel.html(listOptions(n)).prop('disabled', false);
            setStatus(n.id, cache[n.id].lists.length ? '' : tr('No audiences found for this API key.'), !cache[n.id].lists.length);
        });
    }

    function autoMatch(tag) {
        var t = String(tag).toLowerCase(), hit = '';
        if (t === 'email') return '{{email}}';
        $.each(fieldTokens(), function(i, f) { if (!hit && f.tok.toLowerCase() === t) hit = '{{' + f.tok + '}}'; });
        return hit;
    }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.mailchimp = $.map(list, function(n) { return $.extend(true, {}, n); });
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var src = settings && settings.mailchimp;
        if (isPlain(src)) { src = $.map(src, function(v) { return v; }); }
        list = [];
        cache = {};
        $.each($.isArray(src) ? src : [], function(i, n) {
            if (!n || typeof n !== 'object') return;
            var m = $.extend(true, blank(), n);
            m.id = n.id ? String(n.id) : uid();
            m.enable = isTrue(n.enable) || n.enable === undefined;
            m.double = isTrue(n.double);
            m.logic_enable = isTrue(n.logic_enable);
            m.fields = isPlain(n.fields) ? $.extend({}, n.fields) : { EMAIL: '{{email}}' };
            if (!m.fields.EMAIL) m.fields.EMAIL = '{{email}}';
            m.field_labels = isPlain(n.field_labels) ? $.extend({}, n.field_labels) : {};
            m.groups = isPlain(n.groups) ? $.extend({}, n.groups) : {};
            m.group_defs = $.isArray(n.group_defs) ? n.group_defs : [];
            m.rules = $.isArray(n.rules) ? n.rules : [];
            list.push(m);
        });
        render();
        return r;
    };

    function openBody($c) {
        $c.find('.wppoppop-mc-body').show();
        $c.find('.wppoppop-mc-gear').attr('aria-expanded', 'true');
    }

    $(document)
        .off('.wppMc')
        .on('click.wppMc', '#wppoppop-mc-add', function(e) {
            e.preventDefault();
            if (list.length >= MAX) return;
            var n = blank();
            list.push(n);
            render();
            openBody($card(n.id));
        })
        .on('click.wppMc', '.wppoppop-mc-head', function(e) {
            if ($(e.target).closest('.wppoppop-mc-del').length) return;
            var $c = $(this).closest('.wppoppop-mc-card');
            var $b = $c.find('.wppoppop-mc-body');
            var show = ($b.css('display') === 'none');
            $b.toggle(show);
            $c.find('.wppoppop-mc-gear').attr('aria-expanded', show ? 'true' : 'false');
            var n = cur($c);
            if (show && n && keyOk(n.api_key) && !cache[n.id]) { loadLists(n); }
        })
        .on('click.wppMc', '.wppoppop-mc-del', function(e) {
            e.preventDefault(); e.stopPropagation();
            var n = cur($(this));
            if (!n || !window.confirm(tr('Delete this MailChimp connection?'))) return;
            list = $.grep(list, function(x) { return x.id !== n.id; });
            delete cache[n.id];
            render();
        })
        .on('input.wppMc change.wppMc', '#wppoppop-mc-list [data-k]', function(e) {
            var n = cur($(this)); if (!n) return;
            var k = $(this).attr('data-k');
            n[k] = ($(this).attr('type') === 'checkbox') ? $(this).is(':checked') : $(this).val();
            var $c = $(this).closest('.wppoppop-mc-card');
            if (k === 'name') { $c.find('.wppoppop-mc-title').text(n.name || 'MailChimp'); }
            if (k === 'logic_enable') { $c.find('.wppoppop-mc-logic').toggle(!!n.logic_enable); }
            if (k === 'api_key') {
                var ok = keyOk(n.api_key);
                $c.find('.wppoppop-mc-list-sel').prop('disabled', !ok);
                if (e.type === 'change') {
                    n.api_key = trim(n.api_key);
                    delete cache[n.id];
                    if (ok) { loadLists(n); } else { setStatus(n.id, n.api_key ? tr('This does not look like a MailChimp API key.') : '', !!n.api_key); }
                }
            }
        })
        .on('change.wppMc', '.wppoppop-mc-list-sel', function() {
            var n = cur($(this)); if (!n) return;
            var $o = $(this).find('option:selected');
            n.list_id = $(this).val() || '';
            n.list_name = n.list_id ? $o.text() : '';
            n.fields = { EMAIL: n.fields.EMAIL || '{{email}}' };
            n.field_labels = { EMAIL: 'Email address' };
            n.groups = {};
            n.group_defs = [];
            var $c = $(this).closest('.wppoppop-mc-card');
            $c.find('.wppoppop-mc-fields').html(fieldRows(n));
            $c.find('.wppoppop-mc-groups').empty();
        })
        .on('input.wppMc change.wppMc', '#wppoppop-mc-list [data-f]', function() {
            var n = cur($(this)); if (!n) return;
            n.fields[$(this).attr('data-f')] = $(this).val();
        })
        .on('change.wppMc', '#wppoppop-mc-list [data-g]', function() {
            var n = cur($(this)); if (!n) return;
            var id = $(this).attr('data-g');
            if ($(this).is(':checked')) { n.groups[id] = true; } else { delete n.groups[id]; }
        })
        .on('click.wppMc', '.wppoppop-mc-load-fields', function(e) {
            e.preventDefault();
            var n = cur($(this)); if (!n) return;
            if (!n.list_id) { setStatus(n.id, tr('Select an audience first.'), true); return; }
            setStatus(n.id, tr('Loading fields...'), false);
            ajax('wppoppop_mailchimp_fields', n, function(data) {
                if (!byId(n.id)) return;
                var old = n.fields, f = {}, labels = {};
                $.each($.isArray(data.fields) ? data.fields : [], function(i, x) {
                    if (!x || !x.tag) return;
                    f[x.tag] = old[x.tag] ? old[x.tag] : autoMatch(x.tag);
                    labels[x.tag] = x.label || '';
                });
                if (!f.EMAIL) f.EMAIL = old.EMAIL || '{{email}}';
                n.fields = f;
                n.field_labels = labels;
                $card(n.id).find('.wppoppop-mc-fields').html(fieldRows(n));
                setStatus(n.id, '', false);
            });
        })
        .on('click.wppMc', '.wppoppop-mc-load-groups', function(e) {
            e.preventDefault();
            var n = cur($(this)); if (!n) return;
            if (!n.list_id) { setStatus(n.id, tr('Select an audience first.'), true); return; }
            setStatus(n.id, tr('Loading groups...'), false);
            ajax('wppoppop_mailchimp_groups', n, function(data) {
                if (!byId(n.id)) return;
                var defs = $.isArray(data.groups) ? data.groups : [], keep = {};
                $.each(defs, function(i, g) { if (n.groups[g.id]) keep[g.id] = true; });
                n.group_defs = defs;
                n.groups = keep;
                $card(n.id).find('.wppoppop-mc-groups').html(groupRows(n));
                setStatus(n.id, defs.length ? '' : tr('No groups found in this audience.'), !defs.length);
            });
        })
        .on('click.wppMc', '.wppoppop-mc-rule-add', function(e) {
            e.preventDefault();
            var n = cur($(this)); if (!n || n.rules.length >= 20) return;
            n.rules.push({ field: '', operator: 'is', value: '' });
            render();
            openBody($card(n.id));
        })
        .on('click.wppMc', '.wppoppop-mc-rule-del', function(e) {
            e.preventDefault();
            var n = cur($(this)); if (!n) return;
            var i = $(this).closest('.wppoppop-mc-card').find('.wppoppop-mc-rule').index($(this).closest('.wppoppop-mc-rule'));
            if (i >= 0) n.rules.splice(i, 1);
            render();
            openBody($card(n.id));
        })
        .on('input.wppMc change.wppMc', '#wppoppop-mc-list [data-r]', function() {
            var n = cur($(this)); if (!n) return;
            var i = $(this).closest('.wppoppop-mc-card').find('.wppoppop-mc-rule').index($(this).closest('.wppoppop-mc-rule'));
            if (i < 0 || !n.rules[i]) return;
            n.rules[i][$(this).attr('data-r')] = $(this).val();
        })
        .on('click.wppMc', '.wppoppop-acc-item[data-html-module="acc-mailchimp"] > .wppoppop-acc-header', function() { setTimeout(render, 0); });

    $(function() { setTimeout(render, 300); });

    /* Merge-tag menu (the </> button next to a mapped field) */
    var $menu = null, $btn = null;

    function closeMenu() {
        if ($btn) { $btn.attr('aria-expanded', 'false'); }
        if ($menu) { $menu.remove(); }
        $menu = null; $btn = null;
    }

    function group(title, items) {
        var h = '<div class="wppoppop-mc-menu-h">' + esc(title) + '</div>';
        $.each(items, function(i, it) { h += '<button type="button" class="wppoppop-mc-menu-item" data-tag="' + esc(it[1]) + '">' + esc(it[0]) + '</button>'; });
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
        $menu = $('<div class="wppoppop-mc-menu" role="menu"></div>').html(group(tr('Form values'), forms) + group(tr('General'), gen));
        $b.closest('.wppoppop-mc-frow').append($menu);
    }

    $(document)
        .off('.wppMcMenu')
        .on('click.wppMcMenu', '.wppoppop-mc-tag', function(e) {
            e.preventDefault(); e.stopPropagation();
            if ($btn && $btn[0] === this && $menu) { closeMenu(); } else { openMenu($(this)); }
        })
        .on('click.wppMcMenu', '.wppoppop-mc-menu-item', function(e) {
            e.preventDefault(); e.stopPropagation();
            var tag = $(this).attr('data-tag');
            var $t = $btn ? $btn.closest('.wppoppop-mc-frow').find('[data-f]') : $();
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
        .on('click.wppMcMenu', function(e) {
            if ($menu && !$(e.target).closest('.wppoppop-mc-menu, .wppoppop-mc-tag').length) { closeMenu(); }
        })
        .on('keydown.wppMcMenu', function(e) {
            if ($menu && (e.key === 'Escape' || e.keyCode === 27)) { e.stopPropagation(); closeMenu(); }
        });
})(jQuery);
