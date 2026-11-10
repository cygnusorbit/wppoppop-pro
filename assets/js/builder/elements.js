/* WpPopPop Pro - builder behaviour for the Pro elements (progress bar, image select, tile, rating).
   Registers its handlers in the core extension store (window.WpPopPopElementExt). It loads whenever the Pro
   plugin is active, so saved elements keep rendering on the stage even while the licence is inactive. */
(function (window, $) {
    'use strict';
    var Ext = window.WpPopPopElementExt;
    if (!Ext) { return; }

    /* Image Select + Tile: Inspector fields <- model */
    var hydrateChoices = function (el, type) {
        if (type === 'imageselect') { /* WPPOPPOP_IMAGESELECT_HYDRATE */
            var isOpts = el.optionsList || el.options || '';
            if (Array.isArray(isOpts)) { isOpts = isOpts.join('\n'); }
            $('#prop-imageselect-options').val(isOpts);
            if (window.__wppImgSelRebuild) { window.__wppImgSelRebuild(true); } /* WPPOPPOP_IMGSEL_REBUILD */
            $('#prop-imageselect-cols').val(String(parseInt(el.gridCols || el.columns, 10) || 3));
            $('#prop-imageselect-accent').val(el.activeColor || '#38bdf8');
            $('#prop-imageselect-multi').prop('checked', el.multiSelect === true || el.multiSelect === 1 || el.multiSelect === '1' || el.multiSelect === 'true');
        }
        if (type === 'tile' && !el.bgColor) { /* WPPOPPOP_TILE_BG_DEFAULT */
            $('#prop-bg-color').val('#f1f5f9');
            $('#prop-bg-transparent').prop('checked', false);
        }
        if (type === 'tile') { /* WPPOPPOP_TILE_HYDRATE */
            var tlOpts = el.optionsList || el.options || '';
            if (Array.isArray(tlOpts)) { tlOpts = tlOpts.join('\n'); }
            $('#prop-tile-options').val(tlOpts);
            $('#prop-tile-cols').val(String(parseInt(el.gridCols || el.columns, 10) || 3));
            $('#prop-tile-accent').val(el.activeColor || '#38bdf8');
            $('#prop-tile-multi').prop('checked', el.multiSelect === true || el.multiSelect === 1 || el.multiSelect === '1' || el.multiSelect === 'true');
        }
    };

    Ext.register('imageselect', {
        stage: function (el, ctx) {
            var align = ctx.align, reqStar = ctx.reqStar, defVal = ctx.defVal, innerHtml = '';
            /* WPPOPPOP_IMAGESELECT_STAGE_V2 */
            var imgRaw = el.optionsList || el.options || '';
            if (Array.isArray(imgRaw)) { imgRaw = imgRaw.join('\n'); }
            var imgLines = $.trim(String(imgRaw)).split(/\r?\n/).map(function(x) { return $.trim(x); }).filter(Boolean);
            if (imgLines.length === 1 && imgLines[0].indexOf(',') !== -1 && !/https?:|\/\/|\||::/.test(imgLines[0])) {
                imgLines = imgLines[0].split(',').map(function(x) { return $.trim(x); }).filter(Boolean);
            }
            if (!imgLines.length) { imgLines = ['Style A', 'Style B', 'Style C']; }
            var iCols = Math.min(4, Math.max(1, parseInt(el.gridCols || el.columns, 10) || 3));
            var iAcc  = el.activeColor || '#38bdf8';
            var iTxt  = 'font-size:' + (parseInt(el.fontSize, 10) || 12) + 'px;font-weight:' + (el.fontWeight || '600') + ';color:' + (el.color || '#1e293b') + ';';
            var parseImg = function(line) {
                var m, p, lbl = line, img = '';
                if (line.indexOf('::') !== -1) { p = line.split('::'); lbl = $.trim(p.shift()); img = $.trim(p.join('::')); }
                else if (line.indexOf('|') !== -1) { p = line.split('|'); lbl = $.trim(p.shift()); img = $.trim(p.join('|')); }
                else if ((m = line.match(/^(.*?),\s*((?:https?:)?\/\/\S+|\/\S+)$/i))) { lbl = $.trim(m[1]); img = m[2]; }
                return { l: lbl, i: img };
            };
            var cards = imgLines.slice(0, 8).map(function(line, idx) {
                var it = parseImg(line);
                var active = (idx === 0);
                var thumb = (it.i && /^((https?:)?\/\/|\/)/i.test(it.i))
                    ? '<img src="' + String(it.i).replace(/"/g, '%22').replace(/</g, '%3C') + '" alt="" style="width:100%;flex:1 1 auto;min-height:0;object-fit:cover;border-radius:0px;display:block;" />'
                    : '<span class="dashicons dashicons-format-image" style="font-size:18px;width:18px;height:18px;color:' + (active ? iAcc : '#94a3b8') + ';"></span>';
                return '<div style="min-width:0;min-height:0;box-sizing:border-box;border:2px solid ' + (active ? iAcc : '#cbd5e1') + ';border-radius:0px;background:#ffffff;padding:4px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;text-align:center;">' + thumb +
                    '<span style="max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;' + iTxt + '">' + $('<div>').text(it.l || 'Choice').html() + (idx === 0 ? reqStar : '') + '</span></div>';
            }).join('');
            innerHtml = '<div style="width:100%;height:100%;display:grid;grid-template-columns:repeat(' + iCols + ',minmax(0,1fr));grid-auto-rows:minmax(0,1fr);gap:6px;box-sizing:border-box;pointer-events:none;">' + cards + '</div>';
            return innerHtml;
        },
        fallbackInner: function (el) {
            var isOpts = String(el.options || 'Starter Plan, Pro Plan, Enterprise').split(/[\n,]+/);
            var cols = parseInt(el.columns, 10) || 3;
            var cards = isOpts.slice(0, 4).map(function(o, idx) {
                var parts = String(o).split('|');
                var lbl = $.trim(parts[0] || 'Choice');
                var imgUrl = parts[1] ? $.trim(parts[1]) : '';
                var borderCol = idx === 0 ? '#0284c7' : '#cbd5e1';
                var thumb = imgUrl ? '<img src="' + imgUrl + '" style="width:100%;height:32px;object-fit:cover;border-radius:0px;margin-bottom:2px;" />' : '<span class="dashicons dashicons-format-image" style="display:block;margin:0 auto 2px;color:#64748b;"></span>';
                return '<div style="border:2px solid ' + borderCol + ';border-radius:0px;padding:4px;text-align:center;font-size:11px;font-weight:600;background:#f8fafc;">' + thumb + lbl + '</div>';
            }).join('');
            return '<div style="display:grid;grid-template-columns:repeat(' + cols + ',1fr);gap:6px;width:100%;height:100%;align-items:center;">' + cards + '</div>';
        },
        hydrate: hydrateChoices
    });

    Ext.register('tile', {
        stage: function (el, ctx) {
            var align = ctx.align, reqStar = ctx.reqStar, defVal = ctx.defVal, innerHtml = '';
            /* WPPOPPOP_TILE_STAGE_V2 */
            var tRaw = el.optionsList || el.options || '';
            if (Array.isArray(tRaw)) { tRaw = tRaw.join('\n'); }
            var tLines = $.trim(String(tRaw)).split(/\r?\n/).map(function(x) { return $.trim(x); }).filter(Boolean);
            if (tLines.length === 1 && tLines[0].indexOf(',') !== -1) { tLines = tLines[0].split(',').map(function(x) { return $.trim(x); }).filter(Boolean); }
            if (!tLines.length) { tLines = ['$10 / mo', '$25 / mo', '$50 / mo']; }
            var tCols = Math.min(4, Math.max(1, parseInt(el.gridCols || el.columns, 10) || 3));
            var tAcc  = (el.activeColor && /^#[0-9a-fA-F]{3,8}$/.test(el.activeColor)) ? el.activeColor : '#38bdf8';
            var tBw   = (parseInt(el.borderWidth, 10) > 0) ? parseInt(el.borderWidth, 10) : 1;
            var tBs   = (['solid', 'dashed', 'dotted', 'double', 'none'].indexOf(el.borderStyle) !== -1) ? el.borderStyle : 'solid';
            var tBc   = (el.borderColor && /^#[0-9a-fA-F]{3,8}$/.test(el.borderColor)) ? el.borderColor : '#cbd5e1';
            var tRad  = (el.borderRadius !== undefined && el.borderRadius !== '' && !isNaN(parseInt(el.borderRadius, 10))) ? parseInt(el.borderRadius, 10) : 6;
            var tJ    = align === 'right' ? 'flex-end' : (align === 'left' ? 'flex-start' : 'center');
            var tFg   = (el.color && /^#[0-9a-fA-F]{3,8}$/.test(el.color)) ? el.color : '#334155';
            var tBg   = (el.transparentFill || el.bgColor === 'transparent') ? 'transparent' : ((el.bgColor && /^#[0-9a-fA-F]{3,8}$/.test(el.bgColor)) ? el.bgColor : '#f1f5f9'); /* WPPOPPOP_TILE_BG_STAGE */
            var tTxt  = 'font-size:' + (parseInt(el.fontSize, 10) || 12) + 'px;font-weight:' + (el.fontWeight || '700') + ';line-height:' + (parseFloat(el.lineHeight) || 1.3) + ';letter-spacing:' + (parseFloat(el.letterSpacing) || 0) + 'px;';
            var tTiles = tLines.slice(0, 8).map(function(lbl, idx) {
                var on = (idx === 0);
                var bd = (tBs === 'none') ? 'none' : (tBw + 'px ' + tBs + ' ' + (on ? tAcc : tBc));
                return '<div style="min-width:0;box-sizing:border-box;display:flex;align-items:center;justify-content:' + tJ + ';padding:6px;border-radius:' + tRad + 'px;border:' + bd + ';background:' + (on ? tAcc : tBg) + ';color:' + (on ? '#ffffff' : tFg) + ';' + tTxt + '">' +
                    '<span style="max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + $('<div>').text(lbl).html() + (idx === 0 ? reqStar : '') + '</span></div>';
            }).join('');
            innerHtml = '<div style="width:100%;height:100%;display:grid;grid-template-columns:repeat(' + tCols + ',minmax(0,1fr));grid-auto-rows:minmax(0,1fr);gap:6px;box-sizing:border-box;pointer-events:none;">' + tTiles + '</div>';
            return innerHtml;
        },
        stageDone: function ($node) {
            if ($node && $node[0]) {
                $node[0].style.setProperty('background-color', 'transparent', 'important'); /* WPPOPPOP_TILE_BG_BOX */
                $node[0].style.setProperty('padding', '0', 'important');
            }
        },
        fallbackInner: function (el) {
            var tlOpts = String(el.options || '$10 / mo, $25 / mo, $50 / mo').split(/[\n,]+/);
            var tlCols = parseInt(el.columns, 10) || 3;
            var actBg = el.activeColor || '#0284c7';
            var tiles = tlOpts.slice(0, 4).map(function(o, i) {
                var styleStr = i === 0 ? ('background:' + actBg + ';color:#ffffff;border:1px solid ' + actBg + ';') : 'background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;';
                return '<div style="padding:6px;border-radius:0px;text-align:center;font-weight:700;font-size:12px;' + styleStr + '">' + $.trim(o) + '</div>';
            }).join('');
            return '<div style="display:grid;grid-template-columns:repeat(' + tlCols + ',1fr);gap:6px;width:100%;height:100%;align-items:center;">' + tiles + '</div>';
        },
        spawn: function (newEl) {
            newEl.textAlign = 'center'; newEl.hoverBgColor = '#0284c7'; newEl.hoverTextColor = '#ffffff'; newEl.hoverBorderColor = '#0284c7'; /* WPPOPPOP_TILE_DEFAULTS */
        },
        hydrate: hydrateChoices
    });

    Ext.register('rating', {
        stage: function (el, ctx) {
            var align = ctx.align, reqStar = ctx.reqStar, defVal = ctx.defVal, innerHtml = '';
            /* WPPOPPOP_RATING_STAGE_V2: ratingMax/ratingShape/ratingColor/ratingInactiveColor, default from Data tab */
            var rMax   = parseInt(el.ratingMax, 10) === 10 ? 10 : 5;
            var rShape = el.ratingShape || 'star';
            var rCol   = (/^#[0-9a-fA-F]{3,8}$/.test(el.ratingColor || '')) ? el.ratingColor : '#f59e0b';
            var rOff   = (/^#[0-9a-fA-F]{3,8}$/.test(el.ratingInactiveColor || '')) ? el.ratingInactiveColor : '#cbd5e1';
            var rScore = Math.min(rMax, Math.max(0, parseInt(defVal, 10) || 0));
            var glyph  = rShape === 'heart' ? '&#9829;' : (rShape === 'thumb' ? '&#128077;' : '&#9733;');
            var justify = align === 'center' ? 'center' : (align === 'right' ? 'flex-end' : 'flex-start');
            var starsHtml = '';
            for (var s = 1; s <= rMax; s++) {
                var c = (s <= rScore) ? rCol : rOff;
                starsHtml += '<span style="color:' + c + ';font-size:' + (rMax === 10 ? '15px' : '20px') + ';line-height:1;">' + glyph + '</span>';
            }
            innerHtml = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:' + justify + ';gap:4px;padding:0 6px;box-sizing:border-box;pointer-events:none;">' + starsHtml + '</div>';
            return innerHtml;
        },
        fallbackInner: function (el) {
            var rMax = parseInt(el.ratingMax, 10) || 5;
            var rCol = el.ratingColor || '#f59e0b';
            var rShape = el.ratingShape || 'star';
            var glyph = rShape === 'heart' ? '♥' : (rShape === 'thumb' ? '👍' : '★');
            var stars = '';
            for (var si = 0; si < rMax; si++) {
                var c = si < Math.ceil(rMax * 0.8) ? rCol : '#cbd5e1';
                stars += '<span style="color:' + c + ';font-size:20px;line-height:1;">' + glyph + '</span>';
            }
            return '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;gap:4px;pointer-events:none;">' + stars + '</div>';
        },
        spawn: function (newEl) { newEl.width = 140; },
        /* Without the Rating panel (licence inactive) there is nothing to read: keep the saved values untouched. */
        flushData: function (el) {
            if (!$('#panel-elem-rating').length) { return true; }
            if (!$('#prop-rt-data-default').length) { return false; }
            el.ratingMax      = parseInt($('#prop-rt-data-stars').val(), 10) === 10 ? 10 : 5;
            el.defaultValue   = $('#prop-rt-data-default').val() || '';
            el.dynamicDefault = $('#prop-rt-data-dynamic').is(':checked');
            el.paramName      = $.trim($('#prop-rt-data-param').val() || '');
            el.saveToDb       = $('#prop-rt-data-savedb').is(':checked');
            return true;
        }
    });

    Ext.register('progress', {
        mediaStage: function (el) {
            var innerHtml = '';
            var pct = Math.min(100, Math.max(0, parseInt(el.progressValue !== undefined ? el.progressValue : 65, 10)));
            var fCol = el.fillColor || '#c2185b';
            var tCol = el.trackColor || '#334155';
            var showPct = (el.showPercentLabel !== false);
            var lblText = $.trim(el.progressLabel || '');
            var stripeCss = el.striped ? 'background-image:linear-gradient(45deg,rgba(255,255,255,.18) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.18) 50%,rgba(255,255,255,.18) 75%,transparent 75%,transparent);background-size:20px 20px;' : '';
            var badgeText = lblText ? (lblText + (showPct ? ' (' + pct + '%)' : '')) : (showPct ? (pct + '%') : '');
            innerHtml = '<div style="width:100%;height:100%;background:' + tCol + ';border-radius:inherit;overflow:hidden;position:relative;display:flex;align-items:center;pointer-events:none;">' +
                '<div style="width:' + pct + '%;height:100%;background-color:' + fCol + ';' + stripeCss + 'transition:width 0.2s ease;"></div>' +
                (badgeText ? '<span style="position:absolute;left:0;right:0;text-align:center;font-size:11px;font-weight:700;color:#ffffff;text-shadow:0 1px 2px rgba(0,0,0,0.45);">' + $('<div>').text(badgeText).html() + '</span>' : '') +
                '</div>';
            return innerHtml;
        },
        spawn: function (newEl) { newEl.height = 20; /* WPPOPPOP_PROGRESS_SPAWN_20 */ },
        syncRows: function (el) {
            var pMode = $('#prop-progress-mode').val() || el.progressMode || 'manual';
            $('#prop-progress-val-wrap').toggle(pMode === 'manual');
        },
        bindInspector: function (resolveEl) {
            // Progress Bar Percentage Slider Synchronization
            $('#prop-progress-val').off('input.wppoppop_stage change.wppoppop_stage').on('input.wppoppop_stage change.wppoppop_stage', function() {
                var el = resolveEl();
                if (el) {
                    var p = parseInt($(this).val(), 10) || 0;
                    el.progressVal = p;
                    $('#prop-progress-val-num').text(p);
                    var $node = $('#el-' + el.id);
                    if ($node.length) {
                        $node.find('.wppoppop-progress-bar').css('width', p + '%');
                        $node.find('span').text(p + '%');
                    }
                }
            });

            // Rating Stars & Color Synchronization
            $('#prop-rating-color, #prop-rating-max').off('input.wppoppop_stage change.wppoppop_stage').on('input.wppoppop_stage change.wppoppop_stage', function() {
                var el = resolveEl();
                if (el) {
                    el.ratingColor = $('#prop-rating-color').val();
                    el.ratingMax = parseInt($('#prop-rating-max').val(), 10) || 5;
                    if (window.WpPopPopBuilderCanvas) window.WpPopPopBuilderCanvas.renderCanvas();
                }
            });
        }
    });
})(window, jQuery);

/* === WPPOPPOP_TILE_HOVER_BIND_START === */
(function(window, $) {
    'use strict';
    var Reg = window.WpPopPopElementsRegistry = window.WpPopPopElementsRegistry || {};
    var MAP = { 'prop-elem-hover-bg': 'hoverBgColor', 'prop-elem-hover-color': 'hoverTextColor', 'prop-elem-hover-border': 'hoverBorderColor' };
    $(document).off('input.tlHover change.tlHover', '#prop-elem-hover-bg, #prop-elem-hover-color, #prop-elem-hover-border')
        .on('input.tlHover change.tlHover', '#prop-elem-hover-bg, #prop-elem-hover-color, #prop-elem-hover-border', function(e) {
            var el = (typeof Reg.resolveElement === 'function') ? Reg.resolveElement() : null;
            if (!el || String(el.type || '').toLowerCase() !== 'tile') { return; }
            var k = MAP[this.id];
            if (k) { el[k] = $(this).val(); }
            if (e.type === 'change' && window.WpPopPopBuilderCore && typeof window.WpPopPopBuilderCore.pushHistory === 'function') {
                window.WpPopPopBuilderCore.pushHistory();
            }
        });
})(window, jQuery);
/* === WPPOPPOP_TILE_HOVER_BIND_END === */

/* === WPPOPPOP_TILE_BG_BIND_START === */
(function(window, $) {
    'use strict';
    var Reg = window.WpPopPopElementsRegistry = window.WpPopPopElementsRegistry || {};
    $(document).off('input.tlBg change.tlBg', '#prop-bg-color, #prop-bg-transparent')
        .on('input.tlBg change.tlBg', '#prop-bg-color, #prop-bg-transparent', function() {
            setTimeout(function() {
                var el = (typeof Reg.resolveElement === 'function') ? Reg.resolveElement() : null;
                if (el && String(el.type || '').toLowerCase() === 'tile' && typeof Reg.renderFormElementStage === 'function') {
                    Reg.renderFormElementStage(el);
                }
            }, 0);
        });
})(window, jQuery);
/* === WPPOPPOP_TILE_BG_BIND_END === */

/* === WPPOPPOP_RATING_HYDRATE_START === */
(function(window, $) {
    'use strict';
    var Reg = window.WpPopPopElementsRegistry = window.WpPopPopElementsRegistry || {};
    /* WPPOPPOP_RATING_STYLE: Rating hides every generic Style card */
    if (!document.getElementById('wppoppop-rating-style-css')) {
        var st = document.createElement('style');
        st.id = 'wppoppop-rating-style-css';
        st.appendChild(document.createTextNode(
            '#insp-tab-style.wppoppop-rt-style #insp-section-opacity,' +
            '#insp-tab-style.wppoppop-rt-style #insp-section-bg-color,' +
            '#insp-tab-style.wppoppop-rt-style #insp-section-padding,' +
            '#insp-tab-style.wppoppop-rt-style #insp-section-shadow,' +
            '#insp-tab-style.wppoppop-rt-style #insp-section-custom-css,' +
            '#insp-tab-style.wppoppop-rt-style #wppoppop-form-icons-card,' +
            '#insp-tab-style.wppoppop-rt-style #insp-section-typography,' +
            '#insp-tab-style.wppoppop-rt-style #insp-section-border { display: none !important; }'
        ));
        (document.head || document.documentElement).appendChild(st);
    }
    function rtEl() {
        var el = (typeof Reg.resolveElement === 'function') ? Reg.resolveElement() : null;
        if (!el) return null;
        return String(el.type || '').toLowerCase() === 'rating' ? el : false;
    }
    function hydrateRt() {
        var el = rtEl();
        $('#insp-tab-style').toggleClass('wppoppop-rt-style', !!el);
        if (!el) return;
        var ok = function(c) { return /^#[0-9a-fA-F]{3,8}$/.test(c || ''); };
        $('#prop-rating-scale').val(parseInt(el.ratingMax, 10) === 10 ? '10' : '5');
        $('#prop-rating-glyph').val(el.ratingShape || 'star');
        $('#prop-rating-active-color').val(ok(el.ratingColor) ? el.ratingColor : '#f59e0b');
        $('#prop-rating-inactive-color').val(ok(el.ratingInactiveColor) ? el.ratingInactiveColor : '#cbd5e1');
    }
    $(document).off('click.wppRtHydrate', '.wppoppop-canvas-item, .wppoppop-layer-item, #wppoppop-inspector-drawer [data-tab], .wppoppop-ribbon-btn, .wppoppop-ribbon-tool')
        .on('click.wppRtHydrate', '.wppoppop-canvas-item, .wppoppop-layer-item, #wppoppop-inspector-drawer [data-tab], .wppoppop-ribbon-btn, .wppoppop-ribbon-tool', function() {
            [30, 150, 400, 800].forEach(function(ms) { setTimeout(hydrateRt, ms); });
        });
    if (typeof Reg.hydrateCoreAndStyleFields === 'function' && !Reg._rtStyleHooked) {
        Reg._rtStyleHooked = true;
        var origHy = Reg.hydrateCoreAndStyleFields;
        Reg.hydrateCoreAndStyleFields = function(el) {
            var r = origHy.apply(this, arguments);
            var isRt = String((el && el.type) || '').toLowerCase() === 'rating';
            $('#insp-tab-style').toggleClass('wppoppop-rt-style', isRt);
            if (isRt) { setTimeout(hydrateRt, 0); }
            return r;
        };
    }
})(window, jQuery);
/* === WPPOPPOP_RATING_HYDRATE_END === */

/* === WPPOPPOP_RATING_DATA_TAB_START === */
(function(window, $) {
    'use strict';
    var Reg = window.WpPopPopElementsRegistry = window.WpPopPopElementsRegistry || {};
    var ACC = '#a3195b';
    if (!document.getElementById('wppoppop-rating-data-css')) {
        var st = document.createElement('style');
        st.id = 'wppoppop-rating-data-css';
        st.appendChild(document.createTextNode(
            '#insp-tab-data.wppoppop-rt-data > *:not(#insp-rating-data){display:none !important;}' +
            '#insp-rating-data{display:none;padding:14px;}' +
            '#insp-tab-data.wppoppop-rt-data > #insp-rating-data{display:block !important;}' +
            '#insp-rating-data .rt-lbl{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:800;color:#e2e8f0;text-transform:uppercase;margin:0 0 6px;}' +
            '#insp-rating-data .rt-help{display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;border-radius:0%;background:#e2e8f0;color:#0f172a;font-size:10px;font-weight:800;line-height:1;cursor:help;flex-shrink:0;}' +
            '#insp-rating-data .rt-field{width:100%;height:36px;background:#090d16;border:1px solid #1e293b;color:#f8fafc;border-radius:0px;padding:0 10px;font-size:12px;box-sizing:border-box;}' +
            '#insp-rating-data .rt-row{margin-bottom:16px;}' +
            '#insp-rating-data .rt-sw{position:relative;display:inline-block;width:46px;height:26px;flex-shrink:0;}' +
            '#insp-rating-data .rt-sw input{position:absolute;opacity:0;width:0;height:0;}' +
            '#insp-rating-data .rt-sl{position:absolute;inset:0;background:#475569;border-radius:0px;cursor:pointer;transition:background .15s ease;}' +
            '#insp-rating-data .rt-sl::before{content:"";position:absolute;left:3px;top:3px;width:20px;height:20px;background:#fff;border-radius:0%;transition:transform .15s ease;}' +
            '#insp-rating-data .rt-sw input:checked + .rt-sl{background:' + ACC + ';}' +
            '#insp-rating-data .rt-sw input:checked + .rt-sl::before{transform:translateX(20px);}' +
            '#insp-rating-data .rt-sw input:focus + .rt-sl{box-shadow:0 0 0 2px rgba(163,25,91,.45);}'
        ));
        (document.head || document.documentElement).appendChild(st);
    }
    function help(t) { return '<span class="rt-help" title="' + t + '" aria-label="' + t + '">?</span>'; }
    function card() {
        return '<div id="insp-rating-data">' +
            '<div class="rt-row"><label class="rt-lbl" for="prop-rt-data-stars">Number of Stars ' + help('How many stars or icons the rating scale shows.') + '</label>' +
                '<select id="prop-rt-data-stars" class="rt-field"><option value="5">5 Stars</option><option value="10">10 Stars</option></select></div>' +
            '<div class="rt-row"><label class="rt-lbl" for="prop-rt-data-default">Default Rating ' + help('Pre-selected rating when the form loads. Choose No rating to leave it empty.') + '</label>' +
                '<select id="prop-rt-data-default" class="rt-field"></select></div>' +
            '<div class="rt-row"><label class="rt-lbl" for="prop-rt-data-dynamic" style="justify-content:space-between;"><span style="display:inline-flex;align-items:center;gap:6px;">Dynamic Default Value ' + help('Fill the rating from a URL query parameter, e.g. ?rating=4.') + '</span>' +
                '<span class="rt-sw"><input type="checkbox" id="prop-rt-data-dynamic"><span class="rt-sl"></span></span></label></div>' +
            '<div class="rt-row" id="prop-rt-data-param-row" style="display:none;"><label class="rt-lbl" for="prop-rt-data-param">Parameter Name ' + help('The URL parameter to read, e.g. rating for ?rating=4.') + '</label>' +
                '<input type="text" id="prop-rt-data-param" class="rt-field" placeholder="e.g. rating"></div>' +
            '<div class="rt-row"><label class="rt-lbl" for="prop-rt-data-savedb" style="justify-content:space-between;"><span style="display:inline-flex;align-items:center;gap:6px;">Save to Database ' + help('Store the submitted rating with the lead record.') + '</span>' +
                '<span class="rt-sw"><input type="checkbox" id="prop-rt-data-savedb" checked><span class="rt-sl"></span></span></label></div>' +
            '</div>';
    }
    function ensureCard() {
        var $tab = $('#insp-tab-data');
        if (!$tab.length) return false;
        if (!$('#insp-rating-data').length) { $tab.append(card()); }
        return true;
    }
    function rtEl() {
        var el = (typeof Reg.resolveElement === 'function') ? Reg.resolveElement() : null;
        if (!el) return null;
        return String(el.type || '').toLowerCase() === 'rating' ? el : false;
    }
    function fillDefaults(max, cur) {
        var h = '<option value="">No rating</option>';
        for (var i = 1; i <= max; i++) { h += '<option value="' + i + '">' + i + (i === 1 ? ' Star' : ' Stars') + '</option>'; }
        var $d = $('#prop-rt-data-default').html(h);
        var n = parseInt(cur, 10);
        $d.val((n >= 1 && n <= max) ? String(n) : '');
    }
    function hydrate() {
        var el = rtEl();
        var ready = ensureCard();
        $('#insp-tab-data').toggleClass('wppoppop-rt-data', !!el);
        if (!el || !ready) return;
        var max = parseInt(el.ratingMax, 10) === 10 ? 10 : 5;
        $('#prop-rt-data-stars').val(String(max));
        fillDefaults(max, el.defaultValue);
        var dyn = (el.dynamicDefault === true || el.dynamicDefault === 1 || el.dynamicDefault === '1' || el.dynamicDefault === 'true');
        $('#prop-rt-data-dynamic').prop('checked', dyn);
        $('#prop-rt-data-param').val(el.paramName || '');
        $('#prop-rt-data-param-row').toggle(dyn);
        $('#prop-rt-data-savedb').prop('checked', !(el.saveToDb === false || el.saveToDb === 'false' || el.saveToDb === 0 || el.saveToDb === '0'));
    }
    function commit(e) {
        if (e && e.type === 'change' && window.WpPopPopBuilderCore && typeof window.WpPopPopBuilderCore.pushHistory === 'function') {
            window.WpPopPopBuilderCore.pushHistory();
        }
    }
    function render(el) { if (typeof Reg.renderFormElementStage === 'function') { Reg.renderFormElementStage(el); } }

    $(document).off('input.wppRtData change.wppRtData', '#prop-rt-data-stars').on('input.wppRtData change.wppRtData', '#prop-rt-data-stars', function(e) {
        var el = rtEl(); if (!el) return;
        var max = parseInt($(this).val(), 10) === 10 ? 10 : 5;
        el.ratingMax = max;
        var cur = parseInt(el.defaultValue, 10);
        if (cur > max) { el.defaultValue = String(max); }
        fillDefaults(max, el.defaultValue);
        $('#prop-rating-scale').val(String(max));
        render(el); commit(e);
    });
    $(document).off('input.wppRtData change.wppRtData', '#prop-rt-data-default').on('input.wppRtData change.wppRtData', '#prop-rt-data-default', function(e) {
        var el = rtEl(); if (!el) return;
        el.defaultValue = $(this).val() || '';
        render(el); commit(e);
    });
    $(document).off('change.wppRtData', '#prop-rt-data-dynamic').on('change.wppRtData', '#prop-rt-data-dynamic', function(e) {
        var el = rtEl(); if (!el) return;
        el.dynamicDefault = $(this).is(':checked');
        $('#prop-rt-data-param-row').toggle(el.dynamicDefault);
        commit(e);
    });
    $(document).off('input.wppRtData change.wppRtData', '#prop-rt-data-param').on('input.wppRtData change.wppRtData', '#prop-rt-data-param', function(e) {
        var el = rtEl(); if (!el) return;
        el.paramName = $.trim($(this).val() || '');
        commit(e);
    });
    $(document).off('change.wppRtData', '#prop-rt-data-savedb').on('change.wppRtData', '#prop-rt-data-savedb', function(e) {
        var el = rtEl(); if (!el) return;
        el.saveToDb = $(this).is(':checked');
        commit(e);
    });
    /* keep Content-tab "Scale Max" and this tab in sync */
    $(document).off('change.wppRtDataSync', '#prop-rating-scale').on('change.wppRtDataSync', '#prop-rating-scale', function() { setTimeout(hydrate, 0); });

    $(document).off('click.wppRtDataHydrate', '.wppoppop-canvas-item, .wppoppop-layer-item, #wppoppop-inspector-drawer [data-tab], .wppoppop-ribbon-btn, .wppoppop-ribbon-tool')
        .on('click.wppRtDataHydrate', '.wppoppop-canvas-item, .wppoppop-layer-item, #wppoppop-inspector-drawer [data-tab], .wppoppop-ribbon-btn, .wppoppop-ribbon-tool', function() {
            [30, 150, 400, 800].forEach(function(ms) { setTimeout(hydrate, ms); });
        });
    if (typeof Reg.hydrateCoreAndStyleFields === 'function' && !Reg._rtDataHooked) {
        Reg._rtDataHooked = true;
        var orig = Reg.hydrateCoreAndStyleFields;
        Reg.hydrateCoreAndStyleFields = function(el) {
            var r = orig.apply(this, arguments);
            setTimeout(hydrate, 0);
            return r;
        };
    }
    $(function() { ensureCard(); });
})(window, jQuery);
/* === WPPOPPOP_RATING_DATA_TAB_END === */

/* === WPPOPPOP_IMAGESELECT_SIZE_PRESET_START === */
/* Image Select layers (type: imageselect): default COORDINATES & BOUNDS preset WIDTH 200px x HEIGHT 50px.
   Applied on creation, plus an "Apply preset" button in COORDINATES & BOUNDS for existing layers. */
(function(window, $) {
    'use strict';

    var TYPE = 'imageselect';
    var PRESET_W = 200;
    var PRESET_H = 50;

    function cleanId(raw) {
        return String(raw || '').replace(/^wppoppop-el-/, '').replace(/^el-/, '').trim();
    }
    function getList() {
        var core = window.WpPopPopBuilderCore;
        if (!core || !core.state || !core.state.canvases) return null;
        return core.state.canvases[core.state.currentCanvas || 1] || null;
    }
    function getActiveEl() {
        var core = window.WpPopPopBuilderCore;
        var list = getList();
        if (!core || !list || !core.state.activeId) return null;
        var id = cleanId(core.state.activeId);
        for (var i = 0; i < list.length; i++) {
            if (list[i] && cleanId(list[i].id) === id) return list[i];
        }
        return null;
    }
    function isImageSelect(el) {
        return !!el && String(el.type || '').toLowerCase().trim() === TYPE;
    }
    function applySize(el) {
        el.width = PRESET_W;
        el.height = PRESET_H;
        $('#prop-width, #prop-size-width').val(PRESET_W);
        $('#prop-height, #prop-size-height').val(PRESET_H);
        var id = cleanId(el.id);
        var $node = $('#canvas-elem-' + id + ', #wppoppop-el-' + id + ', #el-' + id + ', .wppoppop-canvas-item[data-id="' + id + '"]').first();
        if ($node.length) $node.css({ width: PRESET_W + 'px', height: PRESET_H + 'px' });
    }
    function render(label) {
        if (window.WpPopPopBuilderCanvas && typeof window.WpPopPopBuilderCanvas.renderCanvas === 'function') {
            window.WpPopPopBuilderCanvas.renderCanvas();
        }
        if (window.WpPopPopBuilderCore && typeof window.WpPopPopBuilderCore.pushHistory === 'function') {
            window.WpPopPopBuilderCore.pushHistory(label);
        }
    }

    function wrapAddElement() {
        var canvas = window.WpPopPopBuilderCanvas;
        if (!canvas || typeof canvas.addElement !== 'function' || canvas.__wppoppopImageSelectWrapped) return;
        canvas.__wppoppopImageSelectWrapped = true;
        var orig = canvas.addElement;
        canvas.addElement = function(type) {
            var before = getList() ? getList().length : 0;
            var res = orig.apply(this, arguments);
            if (String(type || '').toLowerCase().trim() === TYPE) {
                var list = getList() || [];
                var created = (res && typeof res === 'object' && res.type) ? res : (list.length > before ? list[list.length - 1] : null);
                if (isImageSelect(created)) {
                    applySize(created);
                    render('New Image Select Preset');
                }
            }
            return res;
        };
    }

    function ensureButton() {
        var $coords = $('#insp-section-coords');
        if (!$coords.length || $('#wppoppop-imgsel-size-wrap').length) return;
        $coords.append(
            '<div id="wppoppop-imgsel-size-wrap" style="display:none; margin-top:8px;">' +
                '<button type="button" id="wppoppop-imgsel-size-btn" class="button button-small" ' +
                'style="width:100%; font-size:11px; font-weight:700;">Apply Image Select Preset (200 x 50)</button>' +
            '</div>'
        );
    }

    function refreshButton() {
        ensureButton();
        $('#wppoppop-imgsel-size-wrap').toggle(isImageSelect(getActiveEl()));
    }

    function bindButton() {
        $(document).off('click.wppoppopImgSelSize').on('click.wppoppopImgSelSize', '#wppoppop-imgsel-size-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var el = getActiveEl();
            if (!isImageSelect(el)) return;
            applySize(el);
            render('Apply Image Select Size Preset');
        });
    }

    function hookInspector() {
        var insp = window.WpPopPopBuilderInspector;
        if (!insp || insp.__wppoppopImageSelectHooked) return;
        insp.__wppoppopImageSelectHooked = true;
        if (typeof insp.open === 'function') {
            var orig = insp.open;
            insp.open = function() {
                var res = orig.apply(this, arguments);
                setTimeout(refreshButton, 20);
                return res;
            };
        }
    }

    $(function() {
        bindButton();
        wrapAddElement();
        hookInspector();
        refreshButton();
        setTimeout(wrapAddElement, 150);
        setTimeout(hookInspector, 150);
        setInterval(refreshButton, 500);
    });
})(window, jQuery);
/* === WPPOPPOP_IMAGESELECT_SIZE_PRESET_END === */

/* === WPPOPPOP_PROGRESS_START === */
(function(window,$){
    'use strict';
    function cid(r){return String(r||'').replace(/^wppoppop-el-/,'').replace(/^el-/,'').trim();}
    function list(){var c=window.WpPopPopBuilderCore;return (c&&c.state&&c.state.canvases&&c.state.canvases[c.state.currentCanvas||1])||[];}
    function activeEl(){
        var c=window.WpPopPopBuilderCore; if(!c||!c.state)return null;
        var id=cid(c.state.activeId); if(!id)return null;
        var l=list(); for(var i=0;i<l.length;i++){ if(l[i]&&cid(l[i].id)===id) return l[i]; }
        return null;
    }
    function isProg(el){return !!el&&String(el.type||'').toLowerCase()==='progress';}
    function T(v){return v===true||v==='true'||v===1||v==='1'||v==='on';}
    function hex(v,d){return /^#[0-9a-f]{3,8}$/i.test(v||'')?v:d;}
    function num(el){
        var ks=['progressValue','progressPercent','progressVal'];
        for(var i=0;i<ks.length;i++){var n=parseFloat(el[ks[i]]); if(!isNaN(n)) return Math.max(0,Math.min(100,Math.round(n)));}
        return 65;
    }
    function flag(el,keys,def){for(var i=0;i<keys.length;i++){if(el[keys[i]]!==undefined) return T(el[keys[i]]);} return def;}

    if(!document.getElementById('wpp-prog-css')){
        $('head').append('<style id="wpp-prog-css">@-webkit-keyframes wppProgStripe{from{background-position:20px 0}to{background-position:0 0}}@keyframes wppProgStripe{from{background-position:20px 0}to{background-position:0 0}}.wppoppop-prog-anim{-webkit-animation:wppProgStripe 1s linear infinite;animation:wppProgStripe 1s linear infinite}</style>');
    }

    function html(el){
        var pct=num(el);
        var fill=hex(el.fillColor||el.progressColor,'#10b981');
        var track=hex(el.trackColor,'#334155');
        var striped=flag(el,['progressStriped','striped'],false);
        var anim=striped&&flag(el,['progressAnimated'],false);
        var showPct=flag(el,['showPercentLabel'],true);
        var lbl=$.trim(el.progressLabel||'');
        var badge=lbl?(lbl+(showPct?' ('+pct+'%)':'')):(showPct?(pct+'%'):'');
        var stripe=striped?'background-image:linear-gradient(45deg,rgba(255,255,255,.2) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.2) 50%,rgba(255,255,255,.2) 75%,transparent 75%,transparent);background-size:20px 20px;':'';
        return '<div style="width:100%;height:100%;background:'+track+';border-radius:inherit;overflow:hidden;position:relative;display:flex;align-items:center;pointer-events:none;">'+
            '<div class="wppoppop-progress-fill'+(anim?' wppoppop-prog-anim':'')+'" style="width:'+pct+'%;height:100%;background-color:'+fill+';'+stripe+'transition:width .2s ease;"></div>'+
            (badge?'<span style="position:absolute;left:0;right:0;text-align:center;font-size:11px;font-weight:700;color:#fff;text-shadow:0 1px 2px rgba(0,0,0,.45);">'+$('<div>').text(badge).html()+'</span>':'')+
            '</div>';
    }
    function node(el){
        var id=cid(el.id);
        return $('#canvas-elem-'+id+', #wppoppop-el-'+id+', #el-'+id+', .wppoppop-canvas-item[data-id="'+el.id+'"]').first();
    }
    function paint(el){
        var $n=node(el); if(!$n.length) return;
        var $h=$n.children('.ui-resizable-handle, .wppoppop-resize-handle').detach();
        $n.html(html(el)).append($h);
    }
    function paintAll(){list().forEach(function(e){ if(isProg(e)) paint(e); });}

    function wrap(){
        var cv=window.WpPopPopBuilderCanvas;
        if(cv&&!cv.__progWrapped&&typeof cv.renderCanvas==='function'){
            cv.__progWrapped=true; var o=cv.renderCanvas;
            cv.renderCanvas=function(){var r=o.apply(this,arguments); try{paintAll();}catch(e){} return r;};
        }
        var R=window.WpPopPopElementsRegistry;
        if(R&&!R.__progWrapped&&typeof R.renderMediaElementStage==='function'){
            R.__progWrapped=true; var o2=R.renderMediaElementStage;
            R.renderMediaElementStage=function(el){var r=o2.apply(this,arguments); try{ if(isProg(el)) paint(el);}catch(e){} return r;};
        }
    }

    function hydrate(){
        var el=activeEl(); if(!isProg(el)) return;
        $('#prop-progress-percent').val(num(el));
        $('#prop-progress-color').val(hex(el.fillColor||el.progressColor,'#10b981'));
        $('#prop-progress-track').val(hex(el.trackColor,'#334155'));
        $('#prop-progress-label').val(el.progressLabel||'');
        $('#prop-progress-striped').prop('checked',flag(el,['progressStriped','striped'],false));
        $('#prop-progress-animated').prop('checked',flag(el,['progressAnimated'],false));
        $('#prop-progress-showpct').prop('checked',flag(el,['showPercentLabel'],true));
    }
    function sched(){[0,60,200,350,700,1200].forEach(function(ms){setTimeout(function(){wrap();hydrate();paintAll();},ms);});}

    $(document).off('input.wppProg change.wppProg')
      .on('input.wppProg change.wppProg','#prop-progress-percent,#prop-progress-color,#prop-progress-track,#prop-progress-label,#prop-progress-striped,#prop-progress-animated,#prop-progress-showpct',function(e){
        var el=activeEl(); if(!isProg(el)) return;
        var p=parseInt($('#prop-progress-percent').val(),10); if(isNaN(p)) p=0; p=Math.max(0,Math.min(100,p));
        el.progressValue=p; el.progressPercent=p; el.progressVal=p;
        var c=$('#prop-progress-color').val(); el.progressColor=c; el.fillColor=c;
        el.trackColor=$('#prop-progress-track').val();
        el.progressLabel=$('#prop-progress-label').val();
        var st=$('#prop-progress-striped').is(':checked'); el.progressStriped=st; el.striped=st;
        el.progressAnimated=$('#prop-progress-animated').is(':checked');
        el.showPercentLabel=$('#prop-progress-showpct').is(':checked');
        paint(el);
        if(e.type==='change'&&window.WpPopPopBuilderCore&&window.WpPopPopBuilderCore.pushHistory) window.WpPopPopBuilderCore.pushHistory('Change Progress Bar');
    });

    window.addEventListener('click',function(e){
        var t=e.target;
        if(t&&t.closest&&t.closest('.wppoppop-canvas-item, .wppoppop-layer-item, .wppoppop-insp-tab, #wppoppop-inspector-drawer')) sched();
    },true);
    $(function(){wrap();setTimeout(wrap,150);setTimeout(wrap,450);sched();});
})(window,jQuery);
/* === WPPOPPOP_PROGRESS_END === */

/* === WPPOPPOP_PROGRESS_MODE_START === */
(function(window,$){
    'use strict';
    function cid(r){return String(r||'').replace(/^wppoppop-el-/,'').replace(/^el-/,'').trim();}
    function activeEl(){
        var c=window.WpPopPopBuilderCore; if(!c||!c.state)return null;
        var id=cid(c.state.activeId); if(!id)return null;
        var l=(c.state.canvases&&c.state.canvases[c.state.currentCanvas||1])||[];
        for(var i=0;i<l.length;i++){ if(l[i]&&cid(l[i].id)===id) return l[i]; }
        return null;
    }
    function isProg(el){return !!el&&String(el.type||'').toLowerCase()==='progress';}
    function mode(el){var m=el&&el.progressMode; return (m==='form'||m==='steps')?m:'manual';}
    function apply(el){
        var m=mode(el);
        $('#prop-progress-mode').val(m);
        $('#prop-progress-val-wrap').css('display', m==='manual'?'':'none');
    }
    function run(){var el=activeEl(); if(isProg(el)) apply(el);}
    function sched(){[0,60,200,350,700,1200].forEach(function(ms){setTimeout(run,ms);});}
    $(document).off('change.wppProgMode','#prop-progress-mode').on('change.wppProgMode','#prop-progress-mode',function(){
        var el=activeEl(); if(!isProg(el)) return;
        var v=$(this).val(); el.progressMode=(v==='form'||v==='steps')?v:'manual';
        apply(el);
        if(window.WpPopPopBuilderCore&&window.WpPopPopBuilderCore.pushHistory) window.WpPopPopBuilderCore.pushHistory('Change Progress Mode');
    });
    window.addEventListener('click',function(e){
        var t=e.target;
        if(t&&t.closest&&t.closest('.wppoppop-canvas-item, .wppoppop-layer-item, .wppoppop-insp-tab, #wppoppop-inspector-drawer')) sched();
    },true);
    $(function(){sched();});
})(window,jQuery);
/* === WPPOPPOP_PROGRESS_MODE_END === */
