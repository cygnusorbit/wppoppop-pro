(function(window, $) {
    'use strict';
    window.WpPopPopAB = window.WpPopPopAB || {};

    var Actions = {
        init: function() {
            this.bindSubmit();
            this.bindCopyShortcode();
            this.bindDelete();
        },

        getVars: function() {
            return window.wppoppop_vars || {
                ajax_url: ajaxurl || '',
                nonce: ''
            };
        },

        bindSubmit: function() {
            var self = this;
            $('#wppoppop-create-ab-submit').on('click', function() {
                var title = $('#wppoppop-ab-title-input').val().trim();
                var selectedUids = [];
                $('.wppoppop-ab-popup-checkbox:checked').each(function() {
                    selectedUids.push($(this).val());
                });

                if (!title) {
                    alert(wp.i18n.__('Please enter a campaign title.', 'wppoppop'));
                    return;
                }

                if (selectedUids.length < 2) {
                    alert(wp.i18n.__('Please select at least 2 popups for split-testing.', 'wppoppop'));
                    return;
                }

                var $btn = $(this);
                var origLabel = $btn.text();
                $btn.prop('disabled', true).text($('#wppoppop-ab-edit-uid').val() ? 'Saving...' : 'Launching...');

                var vars = self.getVars();

                $.post(vars.ajax_url, {
                    action: ($('#wppoppop-ab-edit-uid').val() ? 'wppoppop_update_campaign' : 'wppoppop_save_campaign'),
                    uid: ($('#wppoppop-ab-edit-uid').val() || ''),
                    nonce: vars.nonce,
                    title: title,
                    popup_uids: selectedUids
                }).done(function(res) {
                    if (res.success) {
                        location.reload();
                    } else {
                        alert(wp.i18n.__('Error:', 'wppoppop') + ' ' + (res.data ? res.data.message : wp.i18n.__('Unable to create campaign.', 'wppoppop')));
                        $btn.prop('disabled', false).text(origLabel);
                    }
                }).fail(function() {
                    alert(wp.i18n.__('Network error while saving campaign.', 'wppoppop'));
                    $btn.prop('disabled', false).text(origLabel);
                });
            });
        },

        bindCopyShortcode: function() {
            $('.wppoppop-copy-ab-sc-btn').on('click', function() {
                var sc = $(this).data('shortcode');
                var $btn = $(this);
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(sc);
                }
                var orig = $btn.text();
                $btn.text('Copied!');
                setTimeout(function() { $btn.text(orig); }, 1500);
            });
        },

        bindDelete: function() {
            var self = this;
            $('.wppoppop-del-ab-btn').on('click', function() {
                if (!confirm(wp.i18n.__('Permanently delete this A/B testing campaign? This cannot be undone.', 'wppoppop'))) {
                    return;
                }

                var uid = $(this).data('uid');
                var $row = $(this).closest('tr');
                var vars = self.getVars();

                $.post(vars.ajax_url, {
                    action: 'wppoppop_delete_campaign',
                    nonce: vars.nonce,
                    uid: uid
                }).done(function(res) {
                    if (res.success) {
                        window.location.reload();
                    } else {
                        alert(wp.i18n.__('Deletion Error:', 'wppoppop') + ' ' + (res.data ? res.data.message : wp.i18n.__('Unable to delete campaign.', 'wppoppop')));
                    }
                }).fail(function() {
                    alert(wp.i18n.__('Network error while deleting campaign.', 'wppoppop'));
                });
            });
        }
    };

    window.WpPopPopAB.Actions = Actions;
})(window, jQuery);
