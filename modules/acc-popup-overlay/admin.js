/* Popup Overlay: Inspector DOM <-> JS model.
 * Wraps getSettings / setSettings so the overlay controls (enable, opacity, colour, custom image / animation
 * backgrounds) are saved to and restored from settings.overlay. Sanitising and rendering stay in the core
 * (WpPopPop_Overlay). Only loaded while the module is installed. */
/* WPPOPPOP_OVERLAY_UI: popup overlay opacity / color / custom backgrounds */
(function($) {
    'use strict';
    var S = window.WpPopPopBuilderSettings;
    if (!S || S._overlayWrapped) return;
    S._overlayWrapped = true;

    var model = { backgrounds: [], selected: '' };
    var editingId = null;
    var IMG_RE = /\.(jpe?g|png)(\?.*)?$/i;

    function newId() { return 'bg_' + Math.random().toString(36).slice(2, 8) + (new Date().getTime()).toString(36).slice(-3); }
    function find(id) {
        for (var i = 0; i < model.backgrounds.length; i++) { if (model.backgrounds[i].id === id) return model.backgrounds[i]; }
        return null;
    }
    function clampOpacity(v) {
        v = parseInt(v, 10);
        if (isNaN(v)) v = 65;
        return Math.max(0, Math.min(100, v));
    }

    function syncOpacityLabel() { $('#set-ov-opacity-val').text(clampOpacity($('#set-ov-opacity').val()) + '%'); }
    function syncEnableUi() { /* WPPOPPOP_OVERLAY_ENABLE_UI */
        var on = $('#set-ov-enable').is(':checked');
        $('#set-ov-enable').closest('.wppoppop-acc-body').children().not('.wppoppop-ov-enable-row').css({ opacity: on ? 1 : 0.45, 'pointer-events': on ? '' : 'none' });
    }

    /* WPPOPPOP_OVERLAY_PAGER: paginated custom background list (default 5 per page) */
    var ovPage = 0, ovPerPage = 5, ovLastCount = 0;

    function pageOfSelected() {
        for (var i = 0; i < model.backgrounds.length; i++) {
            if (model.backgrounds[i].id === model.selected) { return Math.floor(i / ovPerPage); }
        }
        return 0;
    }

    function renderPager(total, pages) {
        var $p = $('#set-ov-bg-pager').empty();
        if (total <= 5) { $p.hide(); return; }
        var $info = $('<span></span>').text(total + ' items');
        var $nav = $('<span></span>').css({ display: 'inline-flex', alignItems: 'center', gap: '6px' });
        $nav.append($('<button type="button" class="button button-small set-ov-pg-prev"></button>').prop('disabled', ovPage <= 0).html('&lsaquo; Prev'));
        $nav.append($('<span></span>').css({ minWidth: '70px', textAlign: 'center', fontWeight: '600' }).text('Page ' + (ovPage + 1) + ' / ' + pages));
        $nav.append($('<button type="button" class="button button-small set-ov-pg-next"></button>').prop('disabled', ovPage >= pages - 1).html('Next &rsaquo;'));
        var $sel = $('<select id="set-ov-pg-size" class="button-small"></select>').css({ fontSize: '11px', height: '26px' });
        $.each([5, 10, 20], function(i, n) {
            $sel.append($('<option></option>').val(n).text(n + ' / page').prop('selected', n === ovPerPage));
        });
        $p.append($info, $nav, $sel).css('display', 'flex');
    }

    function renderList() {
        var $l = $('#set-ov-bg-list').empty();
        var total = model.backgrounds.length;
        var pages = Math.max(1, Math.ceil(total / ovPerPage));
        if (ovPage < 0 || total > ovLastCount) { ovPage = pageOfSelected(); }   /* load or add: show the selected item */
        ovLastCount = total;
        ovPage = Math.max(0, Math.min(pages - 1, ovPage));

        function row(id, label, badge, editable) {
            var $r = $('<div></div>').css({ display: 'flex', alignItems: 'center', gap: '6px', padding: '6px 8px', border: '1px solid #e2e8f0', borderRadius: '0px', marginBottom: '4px', background: '#fff' });
            var $rad = $('<input type="radio" name="set-ov-bg-selected">').val(id).prop('checked', model.selected === id);
            var $lab = $('<span></span>').css({ flex: 1, fontSize: '12px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }).text(label);
            $r.append($rad, $lab);
            if (badge) { $r.append($('<span></span>').css({ fontSize: '10px', color: '#64748b', textTransform: 'uppercase' }).text(badge)); }
            if (editable) {
                $r.append($('<button type="button" class="button button-small set-ov-bg-edit"></button>').attr('data-id', id).text('Edit'));
                $r.append($('<button type="button" class="button button-small set-ov-bg-del"></button>').attr('data-id', id).css('color', '#b91c1c').text('Delete'));
            }
            $l.append($r);
        }
        row('', 'None (color overlay only)', '', false);
        var start = ovPage * ovPerPage;
        $.each(model.backgrounds.slice(start, start + ovPerPage), function(i, bg) {
            row(bg.id, bg.name || 'Background', bg.type === 'animation' ? 'Animation' : 'Picture', true);
        });
        renderPager(total, pages);
    }

    $(document)
        .off('click.wppOvPrev', '.set-ov-pg-prev').on('click.wppOvPrev', '.set-ov-pg-prev', function(e) { e.preventDefault(); ovPage--; renderList(); })
        .off('click.wppOvNext', '.set-ov-pg-next').on('click.wppOvNext', '.set-ov-pg-next', function(e) { e.preventDefault(); ovPage++; renderList(); })
        .off('change.wppOvSize', '#set-ov-pg-size').on('change.wppOvSize', '#set-ov-pg-size', function() {
            var first = ovPage * ovPerPage;               /* keep the first visible item in view */
            var n = parseInt($(this).val(), 10);
            ovPerPage = (n === 10 || n === 20) ? n : 5;
            ovPage = Math.floor(first / ovPerPage);
            renderList();
        });

    function toggleType() {
        var anim = $('#set-ov-bg-type').val() === 'animation';
        $('#set-ov-bg-img-wrap').toggle(!anim);
        $('#set-ov-bg-anim-wrap').toggle(anim);
    }
    function setPreview(url) {
        $('#set-ov-bg-preview').css('background-image', url ? 'url("' + String(url).replace(/"/g, '%22') + '")' : 'none');
    }
    function openEditor(bg) {
        editingId = bg ? bg.id : null;
        $('#set-ov-bg-name').val(bg ? bg.name : '');
        var wppOp = (bg && bg.opacity !== undefined && bg.opacity !== '' && !isNaN(parseInt(bg.opacity, 10))) ? Math.max(0, Math.min(100, parseInt(bg.opacity, 10))) : 100; /* WPPOPPOP_OVERLAY_BGOP */
        $('#set-ov-bg-opacity').val(wppOp);
        $('#set-ov-bg-opacity-val').text(wppOp + '%');
        $('#set-ov-bg-type').val(bg ? bg.type : 'image');
        $('#set-ov-bg-url').val(bg && bg.type === 'image' ? bg.url : '');
        $('#set-ov-bg-html').val(bg && bg.type === 'animation' ? bg.html : '');
        setPreview(bg && bg.type === 'image' ? bg.url : '');
        toggleType();
        $('#set-ov-bg-editor').slideDown(120);
    }
    function closeEditor() { editingId = null; $('#set-ov-bg-editor').slideUp(120); }

    var origGet = S.getSettings, origSet = S.setSettings;

    S.getSettings = function() {
        var out = origGet.apply(this, arguments) || {};
        out.overlay = {
            opacity: clampOpacity($('#set-ov-opacity').val()),
            color: $('#set-ov-color').val() || '#0f172a',
            enabled: $('#set-ov-enable').is(':checked'),
            selected: model.selected || '',
            backgrounds: $.map(model.backgrounds, function(b) { return $.extend({}, b); })
        };
        return out;
    };

    S.setSettings = function(settings) {
        var r = origSet.apply(this, arguments);
        var ov = (settings && settings.overlay) || {};
        $('#set-ov-opacity').val(ov.opacity !== undefined && ov.opacity !== '' ? clampOpacity(ov.opacity) : 65);
        $('#set-ov-color').val(/^#[0-9a-f]{6}$/i.test(ov.color || '') ? ov.color : '#0f172a');
        var legacyOff = !!ov.transparent && ov.transparent !== 'false' && ov.transparent !== '0'; /* old 'Transparent' = overlay off */
        var ovOn = (ov.enabled === undefined || ov.enabled === null || ov.enabled === '') ? !legacyOff : !(ov.enabled === false || ov.enabled === 0 || ov.enabled === '0' || ov.enabled === 'false');
        $('#set-ov-enable').prop('checked', ovOn);
        var list = ov.backgrounds;
        if (list && !$.isArray(list) && typeof list === 'object') { list = $.map(list, function(v) { return v; }); }
        model.backgrounds = $.isArray(list) ? $.map(list, function(b) { return $.extend({ id: newId(), name: '', type: 'image', url: '', html: '' }, b); }) : [];
        model.selected = (ov.selected && find(ov.selected)) ? ov.selected : '';
        ovPage = -1;
        closeEditor();
        syncOpacityLabel();
        syncEnableUi();
        renderList();
        return r;
    };

    $(document)
        .off('input.wppOv change.wppOv', '#set-ov-opacity').on('input.wppOv change.wppOv', '#set-ov-opacity', syncOpacityLabel)
        .off('change.wppOvT', '#set-ov-enable').on('change.wppOvT', '#set-ov-enable', function() {
            syncEnableUi();
            var core = window.WpPopPopBuilderCore;
            if (core && typeof core.pushHistory === 'function') core.pushHistory();
        })
        .off('change.wppOvType', '#set-ov-bg-type').on('change.wppOvType', '#set-ov-bg-type', toggleType)
        .off('input.wppOvUrl change.wppOvUrl', '#set-ov-bg-url').on('input.wppOvUrl change.wppOvUrl', '#set-ov-bg-url', function() { setPreview($.trim($(this).val())); })
        .off('click.wppOvAdd', '#set-ov-bg-add').on('click.wppOvAdd', '#set-ov-bg-add', function(e) { e.preventDefault(); if (model.backgrounds.length >= 20) { window.alert(wp.i18n.__('Maximum of 20 custom backgrounds.', 'wppoppop')); return; } openEditor(null); })
        .off('click.wppOvCancel', '#set-ov-bg-cancel').on('click.wppOvCancel', '#set-ov-bg-cancel', function(e) { e.preventDefault(); closeEditor(); })
        .off('change.wppOvSel', 'input[name="set-ov-bg-selected"]').on('change.wppOvSel', 'input[name="set-ov-bg-selected"]', function() { model.selected = $(this).val() || ''; })
        .off('click.wppOvEdit', '.set-ov-bg-edit').on('click.wppOvEdit', '.set-ov-bg-edit', function(e) { e.preventDefault(); var bg = find($(this).attr('data-id')); if (bg) openEditor(bg); })
        .off('click.wppOvDel', '.set-ov-bg-del').on('click.wppOvDel', '.set-ov-bg-del', function(e) {
            e.preventDefault();
            var id = $(this).attr('data-id');
            if (!window.confirm(wp.i18n.__('Delete this background?', 'wppoppop'))) return;
            model.backgrounds = $.grep(model.backgrounds, function(b) { return b.id !== id; });
            if (model.selected === id) model.selected = '';
            if (editingId === id) closeEditor();
            renderList();
        })
        .off('click.wppOvMedia', '#set-ov-bg-media').on('click.wppOvMedia', '#set-ov-bg-media', function(e) {
            e.preventDefault();
            if (!window.wp || !window.wp.media) {
                var manual = window.prompt(wp.i18n.__('WordPress Media is unavailable. Enter image URL (JPG/PNG):', 'wppoppop'), $('#set-ov-bg-url').val() || '');
                if (manual) { $('#set-ov-bg-url').val($.trim(manual)).trigger('change'); }
                return;
            }
            var frame = window.wp.media({ title: 'Select Overlay Background (JPG / PNG)', button: { text: 'Use Image' }, multiple: false, library: { type: ['image/jpeg', 'image/png'] } });
            frame.on('select', function() {
                var att = frame.state().get('selection').first();
                att = att ? att.toJSON() : null;
                if (att && att.url) { $('#set-ov-bg-url').val(att.url).trigger('change'); }
            });
            frame.open();
        })
        .off('click.wppOvApply', '#set-ov-bg-apply').on('click.wppOvApply', '#set-ov-bg-apply', function(e) {
            e.preventDefault();
            var type = $('#set-ov-bg-type').val() === 'animation' ? 'animation' : 'image';
            var name = $.trim($('#set-ov-bg-name').val()) || (type === 'animation' ? 'Animation' : 'Picture');
            var item = { id: editingId || newId(), name: name.slice(0, 60), type: type, url: '', html: '',
                opacity: (function(v) { v = parseInt(v, 10); return isNaN(v) ? 100 : Math.max(0, Math.min(100, v)); })($('#set-ov-bg-opacity').val()) };
            if (type === 'image') {
                item.url = $.trim($('#set-ov-bg-url').val());
                if (!IMG_RE.test(item.url)) { window.alert(wp.i18n.__('Please choose a JPEG or PNG image (.jpg, .jpeg, .png).', 'wppoppop')); return; }
            } else {
                item.html = $('#set-ov-bg-html').val() || '';
                if (!$.trim(item.html)) { window.alert(wp.i18n.__('Please enter the animation HTML / JS.', 'wppoppop')); return; }
            }
            if (editingId) {
                for (var i = 0; i < model.backgrounds.length; i++) { if (model.backgrounds[i].id === editingId) { model.backgrounds[i] = item; break; } }
            } else {
                model.backgrounds.push(item);
                model.selected = item.id;
            }
            closeEditor();
            renderList();
        });

    $(function() { syncOpacityLabel(); syncEnableUi(); renderList(); });
})(jQuery);

/* WPPOPPOP_OVERLAY_BGOP: live % label for the background opacity slider */
jQuery(function($) {
    $(document).off('input.wppOvBgOp change.wppOvBgOp', '#set-ov-bg-opacity')
        .on('input.wppOvBgOp change.wppOvBgOp', '#set-ov-bg-opacity', function() {
            $('#set-ov-bg-opacity-val').text((parseInt($(this).val(), 10) || 0) + '%');
        });
});
