/**
 * @file
 * Behaviors Bootstrap Layout Builder general scripts.
 */

(function ($, Drupal, once) {
  // Configure Section.
  Drupal.behaviors.bootstrapLayoutBuilderConfigureSection = {
    attach(context) {
      // Custom solution for Bootstrap 3 Drupal theme.
      $('input.blb_container_type', context).each(function () {
        const checked = $(this).prop('checked');
        if (typeof checked !== typeof undefined && checked !== false) {
          $(this).parent('label').addClass('active');
        }
      });

      // Custom solution for Bootstrap 3 & Bario Drupal themes.
      $('.blb_container_type .fieldset-wrapper label', context).on(
        'click',
        function () {
          $(this)
            .parents('.fieldset-wrapper')
            .find('label')
            .removeClass('active');
          $(this)
            .parents('.fieldset-wrapper')
            .find('input')
            .prop('checked', false);
          // Temp comment the following line because of conflict with live preview.
          // $(this).parent().find('input').prop('checked', true);
          $(this).addClass('active');
        },
      );

      // Graphical Layout Columns
      $('.blb_breakpoint_cols', context).each(function () {
        const numOfCols = 12;
        // .custom-control, .custom-radio to solve Bario issues.
        once(
          'custom-control-custom-radio-bario',
          $(this).find('.form-item, .custom-control, .custom-radio'),
          context,
        ).forEach(function (elem, i) {
          const cols = elem
            .querySelector('input')
            .value.replace('blb_col_', '');
          const colsConfig = cols.split('_');
          const colsLabel = $(elem).find('label');
          let colClasses = 'blb_breakpoint_col';
          const checked = $(elem).find('input').prop('checked');
          if (typeof checked !== typeof undefined && checked !== false) {
            colClasses += ' bp-selected';
          }

          // Wrap our radio labels and display as a tooltip.
          colsLabel.wrapInner('<div class="bs_tooltip bs_tooltip-lg"></div>');

          // Provide a graphical representation of the columns via some nifty divs styling.
          $.each(colsConfig, function (index, value) {
            const width = (value / numOfCols) * 100;
            $('<div />', {
              text: `${width.toFixed(0)}%`,
              style: `width:${width}%;`,
              class: colClasses,
            })
              .appendTo(colsLabel)
              .on('click', function () {
                $(this)
                  .parents('.blb_breakpoint_cols')
                  .find('.blb_breakpoint_col')
                  .removeClass('bp-selected');
                $(this)
                  .parents('.blb_breakpoint_cols')
                  .find('input')
                  .prop('checked', false);
                $(this)
                  .parents('label')
                  .parent()
                  .find('input')
                  .prop('checked', true);
                $(this)
                  .parents('label')
                  .find('.blb_breakpoint_col')
                  .addClass('bp-selected');
              });
          });
        });
      });

      // Auto-sized textareas.
      $('textarea.blb-auto-size', context).each(function () {
        const height = this.scrollHeight;
        this.setAttribute(
          'style',
          `height:${height}px;overflow-y:hidden;min-height:60px!important;padding:.65rem 1rem;`,
        );
      });
    },
  };
})(jQuery, Drupal, once);
