/**
 * CT Core Admin Scripts
 *
 * @package Ct_Core
 */

(function ($) {
  "use strict";

  var mnorCoreAdmin = {
    init: function () {
      this.bindEvents();
    },

    bindEvents: function () {
      $("#ct-core_options_enable_cf7_phone_mask").on(
        "change",
        this.togglePhoneMaskFields.bind(this),
      );
      this.togglePhoneMaskFields();
    },

    togglePhoneMaskFields: function () {
      var checked = $("#ct-core_options_enable_cf7_phone_mask").is(":checked");
      $(".ct-core-phone-mask-field").each(function () {
        $(this).closest("tr").toggle(checked);
      });
      $(".ct-core-phone-mask-notice").toggle(checked);
    },
  };

  // Initialize on document ready.
  $(document).ready(function () {
    mnorCoreAdmin.init();
  });
})(jQuery);
