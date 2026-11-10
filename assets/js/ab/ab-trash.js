/**
 * WpPopPop A/B: Trash / Restore actions (soft delete)
 * Cross-browser: Chrome, Safari, Firefox, Edge (ES5 only)
 * WPPOPPOP_AB_FILTERS
 */
(function (window, $) {
    'use strict';

    var Trash = {
        vars: function () {
            return window.wppoppop_vars || { ajax_url: window.ajaxurl || '', nonce: '' };
        },

        init: function () {
            var self = this;
            $(document).off('.wppAbTrash');
            $(document).on('click.wppAbTrash', '.wppoppop-trash-ab-btn', function (e) {
                e.preventDefault();
                self.run($(this), 'wppoppop_trash_campaign');
            });
            $(document).on('click.wppAbTrash', '.wppoppop-restore-ab-btn', function (e) {
                e.preventDefault();
                self.run($(this), 'wppoppop_restore_campaign');
            });
        },

        run: function ($btn, action) {
            var v = this.vars();
            $btn.prop('disabled', true);
            $.ajax({
                url: v.ajax_url, type: 'POST', dataType: 'json',
                data: { action: action, nonce: v.nonce, uid: $btn.attr('data-uid') }
            }).done(function (res) {
                if (res && res.success) {
                    window.location.reload();
                } else {
                    $btn.prop('disabled', false);
                    window.alert((res && res.data && res.data.message) || wp.i18n.__('Could not update campaign.', 'wppoppop'));
                }
            }).fail(function () {
                $btn.prop('disabled', false);
                window.alert(wp.i18n.__('Server error while updating campaign.', 'wppoppop'));
            });
        }
    };

    window.WpPopPopAB = window.WpPopPopAB || {};
    window.WpPopPopAB.Trash = Trash;
    $(function () { Trash.init(); });
})(window, jQuery);
