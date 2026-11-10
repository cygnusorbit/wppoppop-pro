/**
 * WpPopPop A/B: Edit campaign + status toggle
 * Cross-browser: Chrome, Safari, Firefox, Edge (ES5 only)
 */
(function (window, $) {
    'use strict';

    var Edit = {
        vars: function () {
            return window.wppoppop_vars || { ajax_url: window.ajaxurl || '', nonce: '' };
        },

        init: function () {
            var self = this;
            $(document).off('.wppAbEdit');
            $(document).on('click.wppAbEdit', '.wppoppop-edit-ab-btn', function (e) {
                e.preventDefault();
                self.open($(this).closest('tr'));
            });
            // "New A/B Campaign" always returns the modal to create mode.
            $(document).on('click.wppAbEdit', '#wppoppop-btn-open-create-ab', function () {
                self.setMode('');
            });
            $(document).on('change.wppAbEdit', '.wpp-ab-status-toggle', function () {
                self.toggle($(this));
            });
        },

        setMode: function (uid) {
            var $h = $('#wppoppop-create-ab-modal .wpp-modal-head h3').first();
            var $b = $('#wppoppop-create-ab-submit');
            if (!$h.data('createText')) { $h.data('createText', $h.text()); }
            if (!$b.data('createText')) { $b.data('createText', $b.text()); }
            $('#wppoppop-ab-edit-uid').val(uid || '');
            $h.text(uid ? 'Edit A/B Campaign' : $h.data('createText'));
            $b.prop('disabled', false).text(uid ? 'Save Changes' : $b.data('createText'));
        },

        open: function ($row) {
            if (!$row || !$row.length) { return; }
            var uid = $row.attr('data-uid') || '';
            var title = $row.attr('data-title') || '';
            var uids = [];
            try { uids = JSON.parse($row.attr('data-popups') || '[]'); } catch (err) { uids = []; }
            if (!uid) { return; }

            this.setMode(uid);
            $('#wppoppop-ab-title-input').val(title);
            $('.wppoppop-ab-popup-checkbox').each(function () {
                $(this).prop('checked', $.inArray($(this).val(), uids) !== -1);
            });
            $('#wppoppop-create-ab-modal').css('display', 'flex');
            $('#wppoppop-ab-title-input').trigger('focus');
        },

        toggle: function ($input) {
            var uid = $input.attr('data-uid');
            var want = $input.is(':checked');
            var $row = $input.closest('tr');
            var $sw = $input.closest('.wpp-ab-switch');
            var v = this.vars();

            $sw.addClass('is-busy');
            $input.prop('disabled', true);

            $.ajax({
                url: v.ajax_url, type: 'POST', dataType: 'json',
                data: { action: 'wppoppop_toggle_campaign', nonce: v.nonce, uid: uid, active: want ? 1 : 0 }
            }).done(function (res) {
                if (res && res.success && res.data) {
                    var on = res.data.status === 'active';
                    $input.prop('checked', on);
                    $sw.attr('title', on ? 'Active' : 'Inactive');
                    $row.find('.wpp-ab-state-badge').removeClass('wppoppop-active-badge wppoppop-inactive-badge').addClass(on ? 'wppoppop-active-badge' : 'wppoppop-inactive-badge').text(on ? wp.i18n.__('ACTIVE', 'wppoppop') : wp.i18n.__('INACTIVE', 'wppoppop'));
                } else {
                    $input.prop('checked', !want);
                    window.alert((res && res.data && res.data.message) || wp.i18n.__('Could not update status.', 'wppoppop'));
                }
            }).fail(function () {
                $input.prop('checked', !want);
                window.alert(wp.i18n.__('Server error while updating status.', 'wppoppop'));
            }).always(function () {
                $sw.removeClass('is-busy');
                $input.prop('disabled', false);
            });
        }
    };

    window.WpPopPopAB = window.WpPopPopAB || {};
    window.WpPopPopAB.Edit = Edit;
    $(function () { Edit.init(); });
})(window, jQuery);
