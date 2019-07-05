(function ($, Drupal) {
  Drupal.behaviors.paragraphs_sets_auto_apply = {
    attach: function (context, settings) {
      var $wrapper = $('#' + settings.paragraphs_sets.field_wrapper_id + ' .set-selection-wrapper');
      var $selector = $wrapper
        .find('select')
        .first();
      // Find the first option that's not None.
      var $first_opt = $selector
        .find('option')
        .filter(function (i) {
          return $(this).val() !== '_none';
        })
        .first();
      // Set the selector (once).
      if ($selector.val() != $first_opt.attr('value')) {
        $selector.val($first_opt.attr('value'));
        // Click the button.
        $wrapper.find('.paragraphs-set-button-set input[type="submit"]').mousedown();
      }
    },
  };
})(jQuery, Drupal);
