/**
 * WpPopPop Popup Positions module: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the position grid and the screen-edge padding are saved to and restored
 * from settings.popup_position. The model (not the DOM) is the source of truth, so a switched-off module never
 * wipes saved values. Sanitising and rendering stay in the core (WpPopPop_Popup_Position).
 * The accordion ships only the inputs; the 3x3 position grid is built here. Only loaded while the module is installed.
 * Cross-browser: ES5 only (Chrome, Safari, Firefox, Edge).
 */
(function ($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._popupPositionWrapped) { return; }
    S._popupPositionWrapped = true;

    var POSITIONS = ['top-left', 'top-center', 'top-right', 'middle-left', 'center', 'middle-right', 'bottom-left', 'bottom-center', 'bottom-right'];
    var SIDES = ['top', 'right', 'bottom', 'left'];
    var MAX_PAD = 1000;
    var DEFAULT_PAD = 50; // WPPOPPOP_PP_DEFAULT_PAD
    var model = blank();

    // [slug, label, box x, box y] for the 3x3 grid of mini screens
    var CELLS = [
        ['top-left', 'Top left', 6, 6], ['top-center', 'Top center', 32, 6], ['top-right', 'Top right', 58, 6],
        ['middle-left', 'Middle left', 6, 23], ['center', 'Center', 32, 23], ['middle-right', 'Middle right', 58, 23],
        ['bottom-left', 'Bottom left', 6, 40], ['bottom-center', 'Bottom center', 32, 40], ['bottom-right', 'Bottom right', 58, 40]
    ];

    function tr(s) { return (window.wp && window.wp.i18n && window.wp.i18n.__) ? window.wp.i18n.__(s, 'wppoppop') : s; }

    function buildGrid() {
        var $g = $('#wppoppop-pp-grid');
        if (!$g.length || $g.children().length) { return; }
        $g.attr({ role: 'radiogroup', 'aria-label': tr('Popup position') });
        $.each(CELLS, function (i, c) {
            var label = tr(c[1]);
            var $b = $('<button type="button" class="wppoppop-pp-cell" role="radio" aria-checked="false"></button>')
                .attr({ 'data-pos': c[0], 'data-label': label, title: label });
            $b.html('<svg viewBox="0 0 100 70" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">' +
                '<rect class="pp-screen" x="1" y="1" width="98" height="68" rx="6" ry="6"/>' +
                '<rect class="pp-box" x="' + c[2] + '" y="' + c[3] + '" width="36" height="24" rx="4" ry="4"/></svg>');
            $g.append($b);
        });
        $('#wppoppop-pp-name').attr('aria-live', 'polite');
        $('#set-pp-edge').attr('aria-label', $('#set-pp-edge').attr('title') || '');
    }

    function blank() { return { position: 'center', top: DEFAULT_PAD, right: DEFAULT_PAD, bottom: DEFAULT_PAD, left: DEFAULT_PAD }; }
    function normPos(v) { return $.inArray(v, POSITIONS) !== -1 ? v : 'center'; }
    function normPad(v) {
        v = parseInt(v, 10);
        if (isNaN(v)) { v = DEFAULT_PAD; }
        if (v < 0) { v = 0; }
        return Math.min(MAX_PAD, v);
    }

    function selectedLabel() {
        return $('#wppoppop-pp-grid .wppoppop-pp-cell[data-pos="' + model.position + '"]').attr('data-label') || '';
    }

    function paint() {
        buildGrid();
        $('#wppoppop-pp-grid .wppoppop-pp-cell').each(function () {
            var on = $(this).attr('data-pos') === model.position;
            $(this).toggleClass('is-selected', on).attr('aria-checked', on ? 'true' : 'false');
        });
        $('#wppoppop-pp-name').text(selectedLabel());
        $.each(SIDES, function (i, s) { $('#set-pp-' + s).val(model[s]); });
    }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function () {
        var out = origGet.apply(this, arguments) || {};
        out.popup_position = $.extend({}, model);
        return out;
    };

    S.setSettings = function (settings) {
        var r = origSet.apply(this, arguments);
        var src = (settings && settings.popup_position && typeof settings.popup_position === 'object') ? settings.popup_position : {};
        model = blank();
        model.position = normPos(src.position);
        $.each(SIDES, function (i, s) { model[s] = normPad(src[s]); });
        paint();
        return r;
    };

    $(document)
        .off('.wppPos')
        .on('click.wppPos', '#wppoppop-pp-grid .wppoppop-pp-cell', function (e) {
            e.preventDefault();
            model.position = normPos($(this).attr('data-pos'));
            paint();
            $('#wppoppop-settings-drawer').trigger('change');
        })
        .on('mouseenter.wppPos focus.wppPos', '#wppoppop-pp-grid .wppoppop-pp-cell', function () {
            $('#wppoppop-pp-name').text($(this).attr('data-label') || '');
        })
        .on('mouseleave.wppPos blur.wppPos', '#wppoppop-pp-grid .wppoppop-pp-cell', function () {
            $('#wppoppop-pp-name').text(selectedLabel());
        })
        .on('input.wppPos change.wppPos', '#set-pp-top, #set-pp-right, #set-pp-bottom, #set-pp-left', function (e) {
            var side = this.id.replace('set-pp-', '');
            model[side] = normPad($(this).val());
            if (e.type === 'change') { $(this).val(model[side]); }
        })
        .on('click.wppPos', '#set-pp-edge', function (e) {
            e.preventDefault();
            $.each(SIDES, function (i, s) { model[s] = 0; });
            paint();
            $('#wppoppop-settings-drawer').trigger('change');
        });

    $(function () { buildGrid(); paint(); });
})(jQuery);
