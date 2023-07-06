import "./settings-page.scss";

jQuery(document).ready(function () {
  (function ($) {
    const groupToggles = $(".wsuwp-content-visibility-settings__group-input");

    function bindEvents() {
      groupToggles.on("change", function (e) {
        const ad_groups = $(e.target)
          .closest(".wsuwp-content-visibility-settings__group-list-item")
          .find(".wsuwp-content-visibility-settings__sub-group-list");

        ad_groups[0].toggleAttribute("data-readonly");
      });
    }

    function init() {
      bindEvents();
    }

    init();
  })(jQuery);
});
