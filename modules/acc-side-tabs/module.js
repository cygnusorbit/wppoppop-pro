/* Side Tabs: front end.
 * Builds one sticky tab per popup that carries data-sidetab-* attributes (printed by WpPopPop_Side_Tabs,
 * only while the module is installed and switched on). The tab is pinned to the screen edge and shows an optional
 * Font Awesome icon plus the label, in the chosen colour, corner radius and slide-in animation.
 * Clicking a tab opens its popup, even if the visitor closed it before. Also shown in the builder's live preview.
 * Skips inline popups. ES5 only (Chrome, Safari, Firefox, Edge). */
(function(window, document, $) {
    'use strict';
    if (!$ || window.__wppSideTabs) return;
    window.__wppSideTabs = true;

    var FA_CDN = (window.wppoppop_front_vars && window.wppoppop_front_vars.fa_url) || (window.wppoppop_vars && window.wppoppop_vars.fa_url) || '';
    var ICON_RE = /^(fas|far|fab|fa-solid|fa-regular|fa-brands)( fa-[a-z0-9-]+){1,3}$/;
    var ANIMS = ['slide', 'bounce', 'fade', 'none'];

    var CSS = '.wppoppop-st-stack{position:fixed;top:50%;z-index:99990;display:flex;flex-direction:column;gap:8px;-webkit-transform:translateY(-50%);transform:translateY(-50%);}' +
        '.wppoppop-st-stack.wppoppop-st-left{left:0;align-items:flex-start;}' +
        '.wppoppop-st-stack.wppoppop-st-right{right:0;align-items:flex-end;}' +
        '.wppoppop-st-tab{display:block;margin:0;border:0;background:#c2185b;color:#fff;font-family:inherit;font-size:14px;font-weight:700;line-height:1.2;padding:14px 10px;cursor:pointer;' +
        'box-shadow:0 4px 12px rgba(0,0,0,.2);-webkit-user-select:none;-moz-user-select:none;user-select:none;-webkit-appearance:none;appearance:none;' +
        '-webkit-transition:-webkit-filter .15s ease;transition:filter .15s ease;}' +
        '.wppoppop-st-tab:hover,.wppoppop-st-tab:focus{-webkit-filter:brightness(.92);filter:brightness(.92);color:#fff;}' +
        '.wppoppop-st-tab:focus-visible{outline:2px solid #fff;outline-offset:-4px;}' +
        '.wppoppop-st-in{display:flex;align-items:center;gap:8px;white-space:nowrap;-webkit-writing-mode:vertical-rl;-ms-writing-mode:tb-rl;writing-mode:vertical-rl;}' +
        '.wppoppop-st-in{-webkit-transform:rotate(180deg);transform:rotate(180deg);}' + /* wppoppop-st-v2: label turned 180deg */
        '.wppoppop-st-ic{display:inline-block;line-height:1;font-size:15px;-webkit-writing-mode:horizontal-tb;-ms-writing-mode:lr-tb;writing-mode:horizontal-tb;}' +
        '.wppoppop-st-ic{-webkit-transform:rotate(90deg);transform:rotate(90deg);}' + /* wppoppop-st-v3: icon turned a further 180deg (net 270deg inside the 180deg label row) */
        '@-webkit-keyframes wppoppop-st-slide-left{from{-webkit-transform:translateX(-100%);}to{-webkit-transform:translateX(0);}}' +
        '@keyframes wppoppop-st-slide-left{from{transform:translateX(-100%);}to{transform:translateX(0);}}' +
        '@-webkit-keyframes wppoppop-st-slide-right{from{-webkit-transform:translateX(100%);}to{-webkit-transform:translateX(0);}}' +
        '@keyframes wppoppop-st-slide-right{from{transform:translateX(100%);}to{transform:translateX(0);}}' +
        '@-webkit-keyframes wppoppop-st-bounce-left{0%{-webkit-transform:translateX(-100%);}60%{-webkit-transform:translateX(12px);}80%{-webkit-transform:translateX(-5px);}100%{-webkit-transform:translateX(0);}}' +
        '@keyframes wppoppop-st-bounce-left{0%{transform:translateX(-100%);}60%{transform:translateX(12px);}80%{transform:translateX(-5px);}100%{transform:translateX(0);}}' +
        '@-webkit-keyframes wppoppop-st-bounce-right{0%{-webkit-transform:translateX(100%);}60%{-webkit-transform:translateX(-12px);}80%{-webkit-transform:translateX(5px);}100%{-webkit-transform:translateX(0);}}' +
        '@keyframes wppoppop-st-bounce-right{0%{transform:translateX(100%);}60%{transform:translateX(-12px);}80%{transform:translateX(5px);}100%{transform:translateX(0);}}' +
        '@-webkit-keyframes wppoppop-st-fade{from{opacity:0;}to{opacity:1;}}' +
        '@keyframes wppoppop-st-fade{from{opacity:0;}to{opacity:1;}}' +
        '.wppoppop-st-a-slide,.wppoppop-st-a-bounce,.wppoppop-st-a-fade{-webkit-animation-duration:.55s;animation-duration:.55s;-webkit-animation-delay:.15s;animation-delay:.15s;-webkit-animation-fill-mode:both;animation-fill-mode:both;-webkit-animation-timing-function:cubic-bezier(.22,.8,.3,1);animation-timing-function:cubic-bezier(.22,.8,.3,1);}' +
        '.wppoppop-st-left .wppoppop-st-a-slide{-webkit-animation-name:wppoppop-st-slide-left;animation-name:wppoppop-st-slide-left;}' +
        '.wppoppop-st-right .wppoppop-st-a-slide{-webkit-animation-name:wppoppop-st-slide-right;animation-name:wppoppop-st-slide-right;}' +
        '.wppoppop-st-left .wppoppop-st-a-bounce{-webkit-animation-name:wppoppop-st-bounce-left;animation-name:wppoppop-st-bounce-left;}' +
        '.wppoppop-st-right .wppoppop-st-a-bounce{-webkit-animation-name:wppoppop-st-bounce-right;animation-name:wppoppop-st-bounce-right;}' +
        '.wppoppop-st-a-fade{-webkit-animation-name:wppoppop-st-fade;animation-name:wppoppop-st-fade;}' +
        '@media (prefers-reduced-motion:reduce){.wppoppop-st-tab{-webkit-animation:none!important;animation:none!important;}}';

    function addCss() {
        if (document.getElementById('wppoppop-st-css')) return;
        var st = document.createElement('style');
        st.id = 'wppoppop-st-css';
        st.appendChild(document.createTextNode(CSS));
        (document.head || document.getElementsByTagName('head')[0]).appendChild(st);
    }

    /* Font Awesome is optional on a site: load it once, only when a tab actually uses an icon. */
    function ensureFa() {
        if (!FA_CDN || document.getElementById('wppoppop-st-fa')) return;
        var links = document.getElementsByTagName('link'), i;
        for (i = 0; i < links.length; i++) {
            if (/font-?awesome|fontawesome|fa-all|all\.min\.css/i.test(links[i].href || '')) return;
        }
        var l = document.createElement('link');
        l.id = 'wppoppop-st-fa';
        l.rel = 'stylesheet';
        l.href = FA_CDN;
        (document.head || document.getElementsByTagName('head')[0]).appendChild(l);
    }

    function stack(side) {
        var $s = $('.wppoppop-st-stack.wppoppop-st-' + side);
        if (!$s.length) {
            $s = $('<div class="wppoppop-st-stack wppoppop-st-' + side + '"></div>').appendTo(document.body);
        }
        return $s;
    }

    function openPopup(uid) {
        if (window.WpPopPop && typeof window.WpPopPop.show === 'function') {
            window.WpPopPop.show(uid);
        } else if (window.WpPopPopFrontDisplay && typeof window.WpPopPopFrontDisplay.show === 'function') {
            window.WpPopPopFrontDisplay.show(uid);
        }
    }

    function hexColor(v) {
        v = String(v || '').toLowerCase();
        return /^#[0-9a-f]{6}$/.test(v) ? v : '#c2185b';
    }

    function build() {
        $('.wppoppop-popup-wrap[data-sidetab]:not([data-inline="1"])').each(function() {
            var $w = $(this), uid = $w.attr('data-uid');
            var side = $w.attr('data-sidetab') === 'right' ? 'right' : 'left';
            var label = $w.attr('data-sidetab-label') || '';
            var icon = String($w.attr('data-sidetab-icon') || '').replace(/^ +| +$/g, '');
            var rad = parseInt($w.attr('data-sidetab-radius'), 10);
            var anim = $w.attr('data-sidetab-anim') || 'slide';
            var made = false, r, $tab, $in;
            if (!ICON_RE.test(icon)) icon = '';
            if (!uid || (!label && !icon)) return;
            $('.wppoppop-st-tab').each(function() { if ($(this).attr('data-uid') === uid) { made = true; } });
            if (made) return;
            if (isNaN(rad)) rad = 6;
            rad = Math.max(0, Math.min(50, rad));
            if (ANIMS.indexOf(anim) === -1) anim = 'slide';
            r = rad + 'px';
            addCss();
            if (icon) ensureFa();
            $tab = $('<button type="button" class="wppoppop-st-tab"></button>')
                .attr('data-uid', uid)
                .attr('aria-haspopup', 'dialog')
                .attr('title', label || 'Open')
                .css({
                    'background-color': hexColor($w.attr('data-sidetab-bg')),
                    'border-radius': side === 'left' ? '0 ' + r + ' ' + r + ' 0' : r + ' 0 0 ' + r
                });
            if (anim !== 'none') $tab.addClass('wppoppop-st-a-' + anim);
            $in = $('<span class="wppoppop-st-in"></span>').appendTo($tab);
            if (icon) $('<i class="wppoppop-st-ic" aria-hidden="true"></i>').addClass(icon).appendTo($in);
            if (label) $('<span class="wppoppop-st-txt"></span>').text(label).appendTo($in);
            $tab.appendTo(stack(side));
        });
    }

    $(document).on('click.wppSideTab', '.wppoppop-st-tab', function(e) {
        e.preventDefault();
        openPopup($(this).attr('data-uid'));
    });

    $(build);
    $(window).on('load', build);
})(window, document, window.jQuery);
