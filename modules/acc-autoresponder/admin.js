/* Autoresponder: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the welcome email is saved to and restored from settings.autoresponder
 * ({ enable, subject, body }). Sanitising on save and sending on submit stay in the core (WpPopPop_Autoresponder).
 * setSettings also reads the legacy keys (enable_user_email, message) of popups saved before this module existed.
 * Only loaded while the module is installed. ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_AUTORESPONDER_UI: Autoresponder module (Inspector DOM <-> settings.autoresponder) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._autoWrapped) return;
    S._autoWrapped = true;

    function isTrue(v) { return v === true || v === 1 || v === '1' || v === 'true' || v === 'on'; }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.autoresponder = {
            enable: $('#set-auto-enable').is(':checked'),
            subject: $('#set-auto-subject').val() || '',
            body: $('#set-auto-body').val() || ''
        };
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var a = (settings && settings.autoresponder && typeof settings.autoresponder === 'object') ? settings.autoresponder : {};
        var body = (a.body !== undefined && a.body !== null && a.body !== '') ? a.body : (a.message || '');
        $('#set-auto-enable').prop('checked', isTrue(a.enable) || isTrue(a.enable_user_email));
        $('#set-auto-subject').val(a.subject || '');
        $('#set-auto-body').val(body);
        return r;
    };
})(jQuery);

/* WPPOPPOP_AUTO_TAGMENU: slide toggle + shortcode (merge-tag) button for the Autoresponder */
(function($) {
    'use strict';
    if (window._wppAutoTagMenu) return;
    window._wppAutoTagMenu = true;

    var $menu = null, $btn = null;

    function tr(t) { return (window.wp && window.wp.i18n && window.wp.i18n.__) ? window.wp.i18n.__(t, 'wppoppop') : t; }
    function esc(v) { return $('<div></div>').text(v === undefined || v === null ? '' : String(v)).html(); }

    function layerName(el, tok) {
        var n = el && (el.name || el.label_text || el.label || el.placeholder);
        return (typeof n === 'string' && n !== '') ? n : String(tok);
    }

    function tokens() {
        var out = [], seen = {};
        var core = window.WpPopPopBuilderCore;
        if (core && core.state && core.state.canvases) {
            Object.keys(core.state.canvases).forEach(function(c) {
                (core.state.canvases[c] || []).forEach(function(el) {
                    var t = el && (el.field_name || el.fieldName || el.fieldId);
                    if (!t || seen[t]) return;
                    seen[t] = true;
                    out.push([layerName(el, t) + ' | ' + (el.type || 'field'), '{{' + t + '}}']);
                });
            });
        }
        return out;
    }

    function ensureSwitch() {
        var $c = $('#set-auto-enable');
        if (!$c.length) return;
        var $p = $c.parent();
        if ($p.is('label.wppoppop-auto-check')) {
            var txt = $.trim($p.text()) || 'Send Welcome Email';
            $c.detach();
            var $sw = $('<label class="wppoppop-toggle-switch"></label>').append($c, '<span class="wppoppop-toggle-slider"></span>');
            $p.replaceWith($('<div class="wppoppop-auto-check"></div>').append($sw, $('<label></label>').attr('for', 'set-auto-enable').text(txt)));
        } else if (!$p.hasClass('wppoppop-toggle-switch')) {
            $c.wrap('<label class="wppoppop-toggle-switch"></label>');
            $c.after('<span class="wppoppop-toggle-slider"></span>');
        } else if (!$p.find('.wppoppop-toggle-slider').length) {
            $p.append('<span class="wppoppop-toggle-slider"></span>');
        }
    }

    function injectButtons() {
        $('.wppoppop-auto-lblrow.wppoppop-auto-hastag').each(function() {
            var $r = $(this);
            var f = $r.find('label').attr('for');
            if (!f || $r.find('.wppoppop-auto-tagbtn').length) return;
            $r.append('<button type="button" class="button button-small wppoppop-auto-tagbtn" data-target="#' + f + '" title="' + tr('Insert subscriber value') + '" aria-label="' + tr('Insert subscriber value') + '" aria-haspopup="true" aria-expanded="false" style="display:inline-flex;align-items:center;justify-content:center;min-height:22px;height:22px;line-height:1;padding:0 5px;margin:0 0 0 auto;"><span class="dashicons" aria-hidden="true" style="display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;font-size:16px;line-height:1;margin:0;padding:0;">&#xf150;</span></button>');
        });
    }

    function setup() { ensureSwitch(); injectButtons(); }

    function close() {
        if ($btn) { $btn.attr('aria-expanded', 'false'); }
        if ($menu) { $menu.remove(); }
        $menu = null; $btn = null;
    }

    function group(title, items) {
        var h = '<div style="padding:6px 10px 2px;font-size:11px;font-weight:700;color:#475569;">' + esc(title) + '</div>';
        $.each(items, function(i, it) {
            h += '<button type="button" class="wppoppop-auto-tag-item" data-tag="' + esc(it[1]) + '" style="display:block;width:100%;text-align:left;border:0;background:transparent;padding:5px 10px;font-size:12px;cursor:pointer;color:#1e293b;">' + esc(it[0]) + '</button>';
        });
        return h;
    }

    function open($b) {
        close();
        $btn = $b;
        $b.attr('aria-expanded', 'true');
        var forms = tokens().concat([['email', '{{email}}']]);
        var gen = [['Site name', '{{site-name}}']];
        $menu = $('<div class="wppoppop-auto-tag-menu" role="menu"></div>').css({
            position: 'absolute', top: '100%', right: 0, left: 'auto', zIndex: 50, minWidth: '190px', maxHeight: '220px',
            overflowY: 'auto', background: '#fff', border: '1px solid #cbd5e1', borderRadius: '0px',
            boxShadow: '0 4px 14px rgba(0,0,0,.15)', marginTop: '2px'
        }).html(group('Subscriber values', forms) + group('General', gen));
        $b.closest('.wppoppop-auto-lblrow').append($menu);
    }

    var S = window.WpPopPopBuilderSettings;
    if (S && !S._autoUiWrapped) {
        S._autoUiWrapped = true;
        var origSet = S.setSettings;
        S.setSettings = function() {
            var r = origSet.apply(this, arguments);
            setup();
            return r;
        };
    }

    $(document)
        .off('.wppAutoMenu')
        .on('click.wppAutoMenu', '.wppoppop-acc-item[data-html-module="acc-autoresponder"] > .wppoppop-acc-header', setup)
        .on('click.wppAutoMenu', '.wppoppop-auto-tagbtn', function(e) {
            e.preventDefault(); e.stopPropagation();
            if ($btn && $btn[0] === this && $menu) { close(); } else { open($(this)); }
        })
        .on('mouseenter.wppAutoMenu', '.wppoppop-auto-tag-item', function() { $(this).css('background', '#f1f5f9'); })
        .on('mouseleave.wppAutoMenu', '.wppoppop-auto-tag-item', function() { $(this).css('background', 'transparent'); })
        .on('click.wppAutoMenu', '.wppoppop-auto-tag-item', function(e) {
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
        .on('click.wppAutoMenu', function(e) {
            if ($menu && !$(e.target).closest('.wppoppop-auto-tag-menu, .wppoppop-auto-tagbtn').length) { close(); }
        })
        .on('keydown.wppAutoMenu', function(e) {
            if ($menu && (e.key === 'Escape' || e.keyCode === 27)) { e.stopPropagation(); close(); }
        });

    $(function() { setup(); setTimeout(setup, 300); });
})(jQuery);
