/* Side Tabs: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the tab is saved to and restored from
 * settings.sidetab ({ enable, label, pos, icon, bg, radius, anim }).
 * Sanitising on save and the front-end attributes stay in the core (WpPopPop_Side_Tabs); the tab is built by module.js.
 * The icon is chosen with the builder's Font Awesome picker (.wppoppop-fa-pick-btn).
 * Only loaded while the module is installed. ES5 only (Chrome, Safari, Firefox, Edge). */
/* WPPOPPOP_SIDE_TABS_UI: Side Tabs module (Inspector DOM <-> settings.sidetab) */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._sideTabsWrapped) return;
    S._sideTabsWrapped = true;

    var DEF_BG = '#c2185b', DEF_RADIUS = 6, DEF_ANIM = 'slide';
    var ANIMS = ['slide', 'bounce', 'fade', 'none'];
    var ICON_RE = /^(fas|far|fab|fa-solid|fa-regular|fa-brands)( fa-[a-z0-9-]+){1,3}$/;

    function isTrue(v) { return v === true || v === 1 || v === '1' || v === 'true' || v === 'on'; }
    function pos(v) { return v === 'right' ? 'right' : 'left'; }
    function icon(v) { v = String(v == null ? '' : v).replace(/^ +| +$/g, '').replace(/ +/g, ' '); return ICON_RE.test(v) ? v : ''; }
    function bg(v) { v = String(v == null ? '' : v).toLowerCase(); return /^#[0-9a-f]{6}$/.test(v) ? v : DEF_BG; }
    function radius(v) { v = parseInt(v, 10); if (isNaN(v)) v = DEF_RADIUS; return Math.max(0, Math.min(50, v)); }
    function anim(v) { return ANIMS.indexOf(v) !== -1 ? v : DEF_ANIM; }

    /* The picker buttons carry data-target, which the module HTML sanitiser strips, so they are added here. */
    function ensureButtons() {
        var $slot = $('.wppoppop-st-iconslot');
        if (!$slot.length || $slot.children().length) return;
        $('<button type="button" class="wppoppop-fa-pick-btn wppoppop-st-iconbtn" data-target="#set-sidetab-icon" title="Select icon" aria-label="Select icon"><i class="fas fa-icons" aria-hidden="true"></i></button>').appendTo($slot);
        $('<button type="button" class="wppoppop-fa-clear-btn wppoppop-st-iconclr" data-target="#set-sidetab-icon" title="Remove icon" aria-label="Remove icon">&times;</button>').appendTo($slot);
    }

    function refreshIcon() {
        var v = icon($('#set-sidetab-icon').val());
        $('.wppoppop-st-iconbtn i').attr('class', v || 'fas fa-icons');
        $('.wppoppop-st-iconslot').toggleClass('has-icon', v !== '');
    }

    function refreshReadouts() {
        $('#set-sidetab-bg-val').text(bg($('#set-sidetab-bg').val()));
        $('#set-sidetab-radius-val').text(radius($('#set-sidetab-radius').val()) + 'px');
    }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.sidetab = {
            enable: $('#set-sidetab-enable').is(':checked'),
            label: $('#set-sidetab-label').val() || '',
            pos: pos($('#set-sidetab-pos').val()),
            icon: icon($('#set-sidetab-icon').val()),
            bg: bg($('#set-sidetab-bg').val()),
            radius: radius($('#set-sidetab-radius').val()),
            anim: anim($('#set-sidetab-anim').val())
        };
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var t = (settings && settings.sidetab && typeof settings.sidetab === 'object') ? settings.sidetab : {};
        ensureButtons();
        $('#set-sidetab-enable').prop('checked', isTrue(t.enable));
        $('#set-sidetab-label').val(t.label || '');
        $('#set-sidetab-pos').val(pos(t.pos));
        $('#set-sidetab-icon').val(icon(t.icon));
        $('#set-sidetab-bg').val(bg(t.bg));
        $('#set-sidetab-radius').val(t.radius === undefined ? DEF_RADIUS : radius(t.radius));
        $('#set-sidetab-anim').val(anim(t.anim));
        refreshIcon();
        refreshReadouts();
        return r;
    };

    $(document).on('input change', '#set-sidetab-bg, #set-sidetab-radius', refreshReadouts);
    $(document).on('input change', '#set-sidetab-icon', refreshIcon);
    $(function() { ensureButtons(); refreshIcon(); refreshReadouts(); });
})(jQuery);
