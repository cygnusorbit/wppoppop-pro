(function(window, $) {
    'use strict';
    window.WpPopPopAB = window.WpPopPopAB || {};

    var Modal = {
        init: function() {
            this.bindOpen();
            this.bindClose();
        },

        bindOpen: function() {
            $('#wppoppop-btn-open-create-ab').on('click', function() {
                $('#wppoppop-ab-title-input').val('');
                $('.wppoppop-ab-popup-checkbox').prop('checked', false);
                $('#wppoppop-create-ab-modal').css('display', 'flex');
            });
        },

        bindClose: function() {
            $('#wppoppop-create-ab-close, #wppoppop-create-ab-cancel').on('click', function() {
                $('#wppoppop-create-ab-modal').hide();
            });

            $('#wppoppop-create-ab-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
        }
    };

    window.WpPopPopAB.Modal = Modal;
})(window, jQuery);
