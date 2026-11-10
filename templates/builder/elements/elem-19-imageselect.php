<?php
if (!defined('ABSPATH')) exit;
?>
<!-- DECOMPOSED MODULE 19: IMAGE SELECT (imageselect) -->
<div class="element-panel wppoppop-panel-specific" id="panel-elem-imageselect" data-elem-family="form" style="display:none;background:#0f172a;border:1px solid #1e293b;border-radius:8px;padding:12px;margin-bottom:14px;">
    <div style="font-size:10px;font-weight:800;color:#38bdf8;text-transform:uppercase;margin-bottom:10px;letter-spacing:0.5px;"><?php esc_html_e('19. Visual Image Select Grid', 'wppoppop-pro'); ?></div>
    <label style="display:block;font-size:10px;font-weight:700;color:#94a3b8;margin-bottom:4px;text-transform:uppercase;"><?php esc_html_e('IMAGE CHOICES (LABEL + IMAGE URL)', 'wppoppop-pro'); ?></label>
    <textarea id="prop-imageselect-options" rows="3" class="widefat wppoppop-input wppoppop-gp-bind" data-prop="optionsList" tabindex="-1" aria-hidden="true" style="display:none;"></textarea>
    <!-- WPPOPPOP_IMGSEL_REPEATER -->
    <div style="display:flex;gap:4px;margin-bottom:4px;padding-right:62px;">
        <span style="flex:1 1 36%;font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;"><?php esc_html_e('Label', 'wppoppop-pro'); ?></span>
        <span style="flex:1 1 64%;font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;"><?php esc_html_e('Image URL', 'wppoppop-pro'); ?></span>
    </div>
    <div id="wppoppop-imgsel-rows" style="margin-bottom:10px;"></div>
    <script>
    (function () {
        'use strict';
        if (window.__wppImgSelRepeater) { return; }
        window.__wppImgSelRepeater = true;
        var last = null;
        function ta() { return document.getElementById('prop-imageselect-options'); }
        function box() { return document.getElementById('wppoppop-imgsel-rows'); }
        function trim(v) { return String(v == null ? '' : v).replace(/^\s+|\s+$/g, ''); }
        function cleanLabel(v) { return trim(String(v == null ? '' : v).replace(/[\r\n|]+/g, ' ')); }
        function cleanUrl(v) { return trim(String(v == null ? '' : v).replace(/[\r\n]+/g, '')); }
        function fire(el) {
            var ev;
            try { ev = new Event('input', { bubbles: true }); } catch (err) { ev = document.createEvent('Event'); ev.initEvent('input', true, true); }
            el.dispatchEvent(ev);
        }
        function parse(line) {
            var lbl = line, img = '', p, m;
            if (line.indexOf('::') !== -1) { p = line.split('::'); lbl = p.shift(); img = p.join('::'); }
            else if (line.indexOf('|') !== -1) { p = line.split('|'); lbl = p.shift(); img = p.join('|'); }
            else if ((m = line.match(/^(.*?),\s*((?:https?:)?\/\/\S+|\/\S+)$/i))) { lbl = m[1]; img = m[2]; }
            return { l: trim(lbl), u: trim(img) };
        }
        function mkInput(ph, val, role, flex) {
            var i = document.createElement('input');
            i.type = 'text';
            i.className = 'widefat';
            i.placeholder = ph;
            i.value = val || '';
            i.setAttribute('data-role', role);
            i.style.cssText = 'flex:' + flex + ';min-width:0;font-size:12px;';
            return i;
        }
        function mkBtn(txt, title, act) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'button button-small';
            b.title = title;
            b.setAttribute('aria-label', title);
            b.setAttribute('data-act', act);
            b.style.cssText = 'min-width:26px;height:28px;padding:0;font-weight:700;line-height:1;';
            b.appendChild(document.createTextNode(txt));
            return b;
        }
        function mkRow(l, u) {
            var row = document.createElement('div');
            row.className = 'wppoppop-imgsel-row';
            row.style.cssText = 'display:flex;gap:4px;align-items:center;margin-bottom:6px;';
            row.appendChild(mkInput('Label', l, 'label', '1 1 36%'));
            /* WPPOPPOP_IMGSEL_MEDIA */
            var uw = document.createElement('div');
            uw.style.cssText = 'position:relative;flex:1 1 64%;min-width:0;display:flex;align-items:center;';
            var ui = mkInput('https://... image URL', u, 'url', '1 1 auto');
            ui.style.cssText = 'width:100%;min-width:0;font-size:12px;padding-right:30px;';
            uw.appendChild(ui);
            var mb = document.createElement('button');
            mb.type = 'button';
            mb.setAttribute('data-act', 'media');
            mb.title = 'Choose image from Media Library';
            mb.setAttribute('aria-label', 'Choose image from Media Library');
            mb.style.cssText = 'position:absolute;right:3px;top:50%;transform:translateY(-50%);width:22px;height:22px;padding:0;margin:0;border:0;background:transparent;color:#38bdf8;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;';
            var mi = document.createElement('span');
            mi.className = 'dashicons dashicons-admin-media';
            mi.style.cssText = 'font-size:16px;width:16px;height:16px;line-height:16px;pointer-events:none;';
            mb.appendChild(mi);
            uw.appendChild(mb);
            row.appendChild(uw);
            row.appendChild(mkBtn('+', 'Add a choice below', 'add'));
            row.appendChild(mkBtn('-', 'Remove this choice', 'del'));
            return row;
        }
        function serialize() {
            var t = ta(), c = box();
            if (!t || !c) { return; }
            var rows = c.querySelectorAll('.wppoppop-imgsel-row'), out = [], i;
            for (i = 0; i < rows.length; i++) {
                var l = cleanLabel(rows[i].querySelector('[data-role="label"]').value);
                var u = cleanUrl(rows[i].querySelector('[data-role="url"]').value);
                if (!l && !u) { continue; }
                out.push(u ? ((l || 'Choice') + ' | ' + u) : l);
            }
            var v = out.join('\n');
            last = v;
            if (t.value !== v) { t.value = v; fire(t); }
        }
        function rebuild(force) {
            var t = ta(), c = box();
            if (!t || !c) { return; }
            var raw = String(t.value || '');
            if (!force && last !== null && raw === last) { return; }
            var lines = raw.split(/\r?\n/).map(trim).filter(Boolean);
            if (lines.length === 1 && lines[0].indexOf(',') !== -1 && !/https?:|\/\/|\||::/i.test(lines[0])) {
                lines = lines[0].split(',').map(trim).filter(Boolean);
            }
            c.innerHTML = '';
            lines.forEach(function (line) { var p = parse(line); c.appendChild(mkRow(p.l, p.u)); });
            if (!lines.length) { c.appendChild(mkRow('', '')); }
            last = raw;
        }
        window.__wppImgSelRebuild = rebuild;

        var mediaFrame = null, mediaRow = null;
        function setRow(row, url, title) {
            if (!row) { return; }
            var ui = row.querySelector('[data-role="url"]'), li = row.querySelector('[data-role="label"]');
            if (ui) { ui.value = url; }
            if (li && !trim(li.value) && title) { li.value = cleanLabel(title); }
            serialize();
        }
        function openMedia(row) {
            mediaRow = row;
            if (!window.wp || !window.wp.media) {
                var ui = row.querySelector('[data-role="url"]');
                var manual = window.prompt('WordPress Media is unavailable. Enter image URL:', ui ? ui.value : '');
                if (manual) { setRow(row, cleanUrl(manual), ''); }
                return;
            }
            if (!mediaFrame) {
                mediaFrame = window.wp.media({
                    title: 'Select or Upload Image',
                    button: { text: 'Use Image' },
                    multiple: false,
                    library: { type: 'image' }
                });
                mediaFrame.on('select', function () {
                    var att = mediaFrame.state().get('selection').first();
                    att = att ? att.toJSON() : null;
                    if (att && att.url) { setRow(mediaRow, att.url, att.title || ''); }
                });
            }
            mediaFrame.open();
        }

        document.addEventListener('input', function (e) {
            var t = e.target;
            if (t && t.closest && t.closest('#wppoppop-imgsel-rows')) { serialize(); }
        }, true);

        document.addEventListener('click', function (e) {
            var tg = e.target;
            var b = (tg && tg.closest) ? tg.closest('#wppoppop-imgsel-rows [data-act]') : null;
            if (b) {
                e.preventDefault();
                var row = b.closest('.wppoppop-imgsel-row'), c = box();
                if (!row || !c) { return; }
                if (b.getAttribute('data-act') === 'media') {
                    openMedia(row);
                    return;
                }
                if (b.getAttribute('data-act') === 'add') {
                    var nr = mkRow('', '');
                    c.insertBefore(nr, row.nextSibling);
                    var f = nr.querySelector('input');
                    if (f) { f.focus(); }
                } else {
                    if (c.querySelectorAll('.wppoppop-imgsel-row').length > 1) {
                        c.removeChild(row);
                    } else {
                        var ins = row.querySelectorAll('input'), k;
                        for (k = 0; k < ins.length; k++) { ins[k].value = ''; }
                    }
                    serialize();
                }
                return;
            }
            if (tg && tg.closest && tg.closest('.wppoppop-canvas-item, .wppoppop-layer-item')) {
                [60, 250, 600].forEach(function (ms) { setTimeout(function () { rebuild(false); }, ms); });
            }
        }, true);

        function watch() {
            var p = document.getElementById('panel-elem-imageselect');
            if (!p || p.__wppImgSelWatch) { return; }
            p.__wppImgSelWatch = true;
            if (window.MutationObserver) {
                new MutationObserver(function () { rebuild(false); }).observe(p, { attributes: true, attributeFilter: ['style', 'class'] });
            }
            p.addEventListener('mousedown', function () { rebuild(false); }, true);
        }
        watch();
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () { watch(); rebuild(false); });
        } else {
            watch();
            rebuild(false);
        }
    })();
    </script>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
        <div>
            <label style="display:block;font-size:10px;font-weight:700;color:#94a3b8;margin-bottom:4px;text-transform:uppercase;"><?php esc_html_e('GRID COLUMNS', 'wppoppop-pro'); ?></label>
            <select id="prop-imageselect-cols" class="widefat wppoppop-input wppoppop-gp-bind" data-prop="gridCols" style="font-size:12px;">
                <option value="2"><?php esc_html_e('2 Columns', 'wppoppop-pro'); ?></option>
                <option value="3"><?php esc_html_e('3 Columns', 'wppoppop-pro'); ?></option>
                <option value="4"><?php esc_html_e('4 Columns', 'wppoppop-pro'); ?></option>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:10px;font-weight:700;color:#94a3b8;margin-bottom:4px;text-transform:uppercase;"><?php esc_html_e('ACTIVE BORDER', 'wppoppop-pro'); ?></label>
            <input type="color" id="prop-imageselect-accent" class="widefat wppoppop-gp-bind" data-prop="activeColor" value="#38bdf8" style="height:34px;padding:2px;background:#090d16;border:1px solid #1e293b;border-radius:6px;cursor:pointer;">
        </div>
    </div>
    <label style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:#cbd5e1;cursor:pointer;">
        <input type="checkbox" id="prop-imageselect-multi" class="wppoppop-gp-bind" data-prop="multiSelect" value="1"> <?php esc_html_e('Allow Multiple Image Selections', 'wppoppop-pro'); ?>
    </label>
</div>
