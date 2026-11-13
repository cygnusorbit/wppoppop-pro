/* Double Opt-In: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the confirmation email, thank-you message and redirect are saved to and
 * restored from settings.double_optin. Sending the confirmation and sanitising stay in the core
 * (WpPopPop_Double_Optin). The accordion ships the inputs; the merge-tag buttons and the textarea defaults are
 * added here. Only loaded while the module is installed. ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_DOUBLE_OPTIN_UI: Double Opt-In module (Inspector DOM <-> settings.double_optin) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._doptWrapped) return;
    S._doptWrapped = true;

    var DEF = {
        enable: false,
        recipient: '',
        subject: 'Please confirm your email address',
        message: 'Dear visitor!\n\nPlease confirm your email address by clicking the following link:\n<a href="{{confirmation-url}}">{{confirmation-url}}</a>\n\nThanks.',
        from_email: '{{global-from-email}}',
        from_name: '{{global-from-name}}',
        thank_message: '<h4 style="text-align: center;">Thank you!</h4><p style="text-align: center;">Your email address successfully confirmed.</p>',
        thank_url: ''
    };

    var MAP = {
        recipient: '#set-dopt-recipient',
        subject: '#set-dopt-subject',
        message: '#set-dopt-message',
        from_email: '#set-dopt-from-email',
        from_name: '#set-dopt-from-name',
        thank_message: '#set-dopt-thank-message',
        thank_url: '#set-dopt-thank-url'
    };

    function isTrue(v) { return v === true || v === 1 || v === '1' || v === 'true'; }

    function tr(t) { return (window.wp && window.wp.i18n && window.wp.i18n.__) ? window.wp.i18n.__(t, 'wppoppop') : t; }

    var touched = false;

    /* The accordion HTML is sanitised (no data-*, aria-*, style), so the merge-tag buttons are added here. */
    /* WPPOPPOP_DOPT_SWITCH: the enable check is a slide toggle (also upgrades markup that still has the plain checkbox) */
    function ensureSwitch() {
        var $c = $('#set-dopt-enable');
        if (!$c.length) return;
        var $p = $c.parent();
        if ($p.is('label.wppoppop-dopt-check')) {
            var txt = $.trim($p.text()) || 'Enable Double Opt-In';
            $c.detach();
            var $sw = $('<label class="wppoppop-toggle-switch"></label>').append($c, '<span class="wppoppop-toggle-slider"></span>');
            $p.replaceWith($('<div class="wppoppop-dopt-check"></div>').append($sw, $('<label></label>').attr('for', 'set-dopt-enable').text(txt)));
        } else if (!$p.hasClass('wppoppop-toggle-switch')) {
            $c.wrap('<label class="wppoppop-toggle-switch"></label>');
            $c.after('<span class="wppoppop-toggle-slider"></span>');
        } else if (!$p.find('.wppoppop-toggle-slider').length) {
            $p.append('<span class="wppoppop-toggle-slider"></span>');
        }
    }

    function injectTags() {
        ensureSwitch();
        $('.wppoppop-dopt-lblrow.wppoppop-dopt-hastag').each(function() {
            var $r = $(this);
            var f = $r.find('label').attr('for');
            if (!f || $r.find('.wppoppop-dopt-tagbtn').length) return;
            $r.append('<button type="button" class="button button-small wppoppop-dopt-tagbtn" data-target="#' + f + '" title="' + tr('Insert merge tag') + '" aria-label="' + tr('Insert merge tag') + '" aria-haspopup="true" aria-expanded="false" style="display:inline-flex;align-items:center;justify-content:center;min-height:22px;height:22px;line-height:1;padding:0 5px;margin:0 0 0 auto;"><span class="dashicons" aria-hidden="true" style="display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;font-size:16px;line-height:1;margin:0;padding:0;">&#xf150;</span></button>');
        });
    }

    /* Textarea defaults can't ship in the sanitised HTML, so a popup that was never loaded gets them here. */
    function seedDefaults() {
        $('#set-dopt-enable').prop('checked', false);
        $.each(MAP, function(k, sel) { $(sel).val(DEF[k]); });
    }

    function refreshTags() {
        var tags = [
            ['Form values', null],
            ['{{email}}', '{{email}}'],
            ['General', null],
            ['Confirmation URL', '{{confirmation-url}}'],
            ['Global from email', '{{global-from-email}}'],
            ['Global from name', '{{global-from-name}}'],
            ['Site name', '{{site-name}}']
        ];
        var seen = {};
        var core = window.WpPopPopBuilderCore;
        var fields = [];
        if (core && core.state && core.state.canvases) {
            Object.keys(core.state.canvases).forEach(function(c) {
                (core.state.canvases[c] || []).forEach(function(el) {
                    var tok = el && (el.field_name || el.fieldName || el.fieldId);
                    if (!tok || seen[tok]) return;
                    seen[tok] = true;
                    fields.push([String(tok) + ' | ' + (el.type || 'field'), '{{' + tok + '}}']);
                });
            });
        }
        $('.wppoppop-dopt-tag').each(function() {
            var $s = $(this).empty().append('<option value="">&lt;/&gt; Tags</option>');
            var $g1 = $('<optgroup label="Form values"></optgroup>');
            $.each(fields.concat([tags[1]]), function(i, t) { $g1.append($('<option></option>').val(t[1]).text(t[0])); });
            var $g2 = $('<optgroup label="General"></optgroup>');
            $.each(tags.slice(3), function(i, t) { $g2.append($('<option></option>').val(t[1]).text(t[0])); });
            $s.append($g1, $g2);
        });
    }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        var d = { enable: $('#set-dopt-enable').is(':checked') };
        $.each(MAP, function(k, sel) {
            var $e = $(sel);
            d[k] = $e.length ? ($e.val() || '') : DEF[k];
        });
        out.double_optin = d;
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        touched = true;
        injectTags();
        var d = (settings && settings.double_optin && typeof settings.double_optin === 'object') ? settings.double_optin : {};
        $('#set-dopt-enable').prop('checked', isTrue(d.enable));
        $.each(MAP, function(k, sel) {
            $(sel).val(d[k] !== undefined && d[k] !== null ? d[k] : DEF[k]);
        });
        refreshTags();
        return r;
    };

    $(document)
        .off('change.wppDoptTag', '.wppoppop-dopt-tag')
        .on('change.wppDoptTag', '.wppoppop-dopt-tag', function() {
            var tag = $(this).val();
            if (!tag) return;
            var $t = $($(this).attr('data-target'));
            var el = $t.get(0);
            if (!el) return;
            var v = $t.val() || '';
            var s = (typeof el.selectionStart === 'number') ? el.selectionStart : v.length;
            var e = (typeof el.selectionEnd === 'number') ? el.selectionEnd : s;
            $t.val(v.slice(0, s) + tag + v.slice(e));
            var pos = s + tag.length;
            try { el.focus(); el.setSelectionRange(pos, pos); } catch (err) {}
            $(this).val('');
            $t.trigger('input');
        })
        .off('click.wppDoptHdr', '.wppoppop-acc-item[data-html-module="acc-double-optin"] > .wppoppop-acc-header')
        .on('click.wppDoptHdr', '.wppoppop-acc-item[data-html-module="acc-double-optin"] > .wppoppop-acc-header', refreshTags);

    $(function() { injectTags(); if (!touched) { seedDefaults(); } setTimeout(refreshTags, 300); });
})(jQuery);


/* WPPOPPOP_DOPT_TAGMENU: icon button + merge-tag menu for Double Opt-In */
(function($) {
    'use strict';
    if (window._wppDoptTagMenu) return;
    window._wppDoptTagMenu = true;

    var $menu = null, $btn = null;

    function esc(v) { return $('<div></div>').text(v === undefined || v === null ? '' : String(v)).html(); }

    function tokens() {
        var out = [], seen = {};
        var core = window.WpPopPopBuilderCore;
        if (core && core.state && core.state.canvases) {
            Object.keys(core.state.canvases).forEach(function(c) {
                (core.state.canvases[c] || []).forEach(function(el) {
                    var t = el && (el.field_name || el.fieldName || el.fieldId);
                    if (!t || seen[t]) return;
                    seen[t] = true;
                    out.push([String(typeof el.name === 'string' && el.name ? el.name : t) + ' | ' + (el.type || 'field'), '{{' + t + '}}']);
                });
            });
        }
        return out;
    }

    function close() {
        if ($btn) { $btn.attr('aria-expanded', 'false'); }
        if ($menu) { $menu.remove(); }
        $menu = null; $btn = null;
    }

    function group(title, items) {
        var h = '<div style="padding:6px 10px 2px;font-size:11px;font-weight:700;color:#475569;">' + esc(title) + '</div>';
        $.each(items, function(i, it) {
            h += '<button type="button" class="wppoppop-dopt-tag-item" data-tag="' + esc(it[1]) + '" style="display:block;width:100%;text-align:left;border:0;background:transparent;padding:5px 10px;font-size:12px;cursor:pointer;color:#1e293b;">' + esc(it[0]) + '</button>';
        });
        return h;
    }

    function open($b) {
        close();
        $btn = $b;
        $b.attr('aria-expanded', 'true');
        var forms = tokens().concat([['email', '{{email}}'], ['All form data', '{{form-data}}']]);
        var gen = [['Confirmation URL', '{{confirmation-url}}'], ['Popup name', '{{form-name}}'], ['Submission ID', '{{submission-id}}'], ['Global from email', '{{global-from-email}}'], ['Global from name', '{{global-from-name}}'], ['Site name', '{{site-name}}']];
        $menu = $('<div class="wppoppop-dopt-tag-menu" role="menu"></div>').css({
            position: 'absolute', top: '100%', right: 0, left: 'auto', zIndex: 50, minWidth: '190px', maxHeight: '220px',
            overflowY: 'auto', background: '#fff', border: '1px solid #cbd5e1', borderRadius: '0px',
            boxShadow: '0 4px 14px rgba(0,0,0,.15)', marginTop: '2px'
        }).html(group('Form values', forms) + group('General', gen));
        $b.closest('.wppoppop-dopt-lblrow').append($menu);
    }

    $(document)
        .off('.wppDoptMenu')
        .on('click.wppDoptMenu', '.wppoppop-dopt-tagbtn', function(e) {
            e.preventDefault(); e.stopPropagation();
            if ($btn && $btn[0] === this && $menu) { close(); } else { open($(this)); }
        })
        .on('mouseenter.wppDoptMenu', '.wppoppop-dopt-tag-item', function() { $(this).css('background', '#f1f5f9'); })
        .on('mouseleave.wppDoptMenu', '.wppoppop-dopt-tag-item', function() { $(this).css('background', 'transparent'); })
        .on('click.wppDoptMenu', '.wppoppop-dopt-tag-item', function(e) {
            e.preventDefault(); e.stopPropagation();
            var tag = $(this).attr('data-tag');
            var $t = $btn ? $($btn.attr('data-target')) : $();
            var el = $t.get(0);
            if (el && tag) {
                var v = $t.val() || '';
                var a = (typeof el.selectionStart === 'number') ? el.selectionStart : v.length;
                var b = (typeof el.selectionEnd === 'number') ? el.selectionEnd : a;
                $t.val(v.slice(0, a) + tag + v.slice(b));
                try { el.focus(); el.setSelectionRange(a + tag.length, a + tag.length); } catch (err) {}
                $t.trigger('input');
            }
            close();
        })
        .on('click.wppDoptMenu', function(e) {
            if ($menu && !$(e.target).closest('.wppoppop-dopt-tag-menu, .wppoppop-dopt-tagbtn').length) { close(); }
        })
        .on('keydown.wppDoptMenu', function(e) {
            if ($menu && (e.key === 'Escape' || e.keyCode === 27)) { e.stopPropagation(); close(); }
        });
})(jQuery);
