(function ($) {
  "use strict";

  function togglePriceFields() {
    var priceType = $('input[name="_service_price_type"]:checked').val();

    $(".ct-core-price-fields").hide().find(":input").prop("disabled", true);
    if (priceType) {
      $(".ct-core-price-" + priceType)
        .show()
        .find(":input")
        .prop("disabled", false);
    }
  }

  $(document).ready(function () {
    togglePriceFields();

    $('input[name="_service_price_type"]').on("change", function () {
      togglePriceFields();
    });
  });
})(jQuery);
