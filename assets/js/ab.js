(function(window, $) {
    'use strict';

    $(document).ready(function() {
        if (!window.WpPopPopAB) return;

        if (window.WpPopPopAB.Modal) {
            window.WpPopPopAB.Modal.init();
        }

        if (window.WpPopPopAB.Actions) {
            window.WpPopPopAB.Actions.init();
        }
    });
})(window, jQuery);
