/* Animation: Inspector DOM <-> JS model.
 * Canvas animation values live in canvasMeta (anim_appearance, anim_duration, anim_delay,
 * anim_disappearance, anim_exit_enabled) and are rendered by the core; this module only owns the
 * accordion: it hydrates the controls, writes edits back, mirrors them to settings.animation and
 * runs the Play button. Only loaded while the module is installed. */
(function ($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._animModWrapped) { return; }
    S._animModWrapped = true;

    var SEL_APP  = '#set-canvas-anim-appearance, #set-anim-appearance';
    var SEL_DUR  = '#set-canvas-anim-duration, #set-anim-duration';
    var SEL_DEL  = '#set-canvas-anim-delay, #set-anim-delay';
    var SEL_DIS  = '#set-canvas-anim-disappearance, #set-anim-disappearance';
    var SEL_EXIT = '#set-canvas-anim-exit-enable';

    function core() { return window.WpPopPopBuilderCore; }

    function curMeta(create) {
        var c = core();
        if (!c || !c.state) { return null; }
        var cur = c.state.currentCanvas || 1;
        if (!c.state.canvasMeta) {
            if (!create) { return null; }
            c.state.canvasMeta = {};
        }
        if (!c.state.canvasMeta[cur] && create) { c.state.canvasMeta[cur] = {}; }
        return c.state.canvasMeta[cur] || null;
    }

    function push() {
        var c = core();
        if (c && typeof c.pushHistory === 'function') { c.pushHistory(); }
    }

    function isOn(meta) {
        var v = meta ? meta.anim_exit_enabled : undefined;
        return v === true || v === 1 || v === '1' || v === 'true';
    }

    function applyExitUi(on) {
        var $sel = $(SEL_DIS);
        $(SEL_EXIT).prop('checked', !!on);
        $sel.prop('disabled', !on).attr('aria-disabled', on ? 'false' : 'true');
        $sel.parent().css({ opacity: on ? '' : '0.45', transition: 'opacity .15s ease' });
    }

    /* canvasMeta -> controls (runs on every canvas switch / history restore) */
    function hydrate() {
        var meta = curMeta(false) || {};
        var app = (!meta.anim_appearance || meta.anim_appearance === 'fade') ? 'fadeIn' : meta.anim_appearance;
        var dur = (meta.anim_duration !== undefined && meta.anim_duration !== '') ? parseInt(meta.anim_duration, 10) : 1000;
        var del = (meta.anim_delay !== undefined && meta.anim_delay !== '') ? parseInt(meta.anim_delay, 10) : 0;
        var dis = !meta.anim_disappearance ? 'none' : (meta.anim_disappearance === 'fade' ? 'fadeOut' : meta.anim_disappearance);
        $(SEL_APP).val(app);
        $(SEL_DUR).val(dur);
        $(SEL_DEL).val(del);
        $(SEL_DIS).val(dis);
        applyExitUi(isOn(meta));
    }

    var origSync = S.syncCurrentCanvasSettings, origGet = S.getSettings, origSet = S.setSettings;

    S.syncCurrentCanvasSettings = function () {
        var r = typeof origSync === 'function' ? origSync.apply(this, arguments) : undefined;
        hydrate();
        return r;
    };

    S.getSettings = function () {
        var out = (typeof origGet === 'function' ? origGet.apply(this, arguments) : null) || {};
        out.animation = {
            appearance: $(SEL_APP).val() || 'fadeIn',
            duration: parseInt($(SEL_DUR).val(), 10) || 1000,
            delay: parseInt($(SEL_DEL).val(), 10) || 0,
            disappearance: $(SEL_DIS).val() || 'none'
        };
        return out;
    };

    S.setSettings = function (settings) {
        var r = typeof origSet === 'function' ? origSet.apply(this, arguments) : undefined;
        var a = settings && settings.animation;
        if (a) {
            if (a.appearance) { $(SEL_APP).val(a.appearance); }
            if (a.duration !== undefined) { $(SEL_DUR).val(a.duration); }
            if (a.delay !== undefined) { $(SEL_DEL).val(a.delay); }
            if (a.disappearance) { $(SEL_DIS).val(a.disappearance); }
        }
        return r;
    };

    function num(el, def) {
        var v = parseInt($(el).val(), 10);
        return isNaN(v) ? def : v;
    }

    $(document).off('.wppAnimMod')
        .on('change.wppAnimMod', SEL_APP, function () {
            var m = curMeta(true);
            if (!m) { return; }
            m.anim_appearance = $(this).val();
            push();
        })
        .on('input.wppAnimMod change.wppAnimMod', SEL_DUR, function () {
            var m = curMeta(true);
            if (!m) { return; }
            m.anim_duration = num(this, 1000);
            push();
        })
        .on('input.wppAnimMod change.wppAnimMod', SEL_DEL, function () {
            var m = curMeta(true);
            if (!m) { return; }
            m.anim_delay = num(this, 0);
            push();
        })
        .on('change.wppAnimMod', SEL_DIS, function () {
            var m = curMeta(true);
            if (!m) { return; }
            m.anim_disappearance = $(this).val();
            push();
        })
        .on('change.wppAnimMod', SEL_EXIT, function () {
            var m = curMeta(true);
            if (!m) { return; }
            var on = $(this).is(':checked');
            m.anim_exit_enabled = on;
            applyExitUi(on);
            push();
        })
        .on('click.wppAnimMod', '#set-canvas-anim-play', function (e) {
            e.preventDefault();
            var cv = window.WpPopPopBuilderCanvas;
            if (cv && typeof cv.playCanvasEntranceAnimation === 'function') { cv.playCanvasEntranceAnimation(); }
        });

    $(function () {
        setTimeout(function () {
            if (typeof S.syncCurrentCanvasSettings === 'function') { S.syncCurrentCanvasSettings(); }
        }, 300);
    });
})(jQuery);
