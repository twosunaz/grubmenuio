
(function ($, document) {
  'use strict';

  const orderable_layouts = {
    /**
     * On doc ready.
     */
    on_ready() {
      /**
       * On change layout setting.
       */
      $(document.body).on('change', '.orderable-table--product-lists input, .orderable-table--product-lists select', function (e) {
        const $field = $(this),
          $parent = $field.closest('.orderable-table__row--repeatable'),
          $shortcode = $parent.find('.orderable-field--product-list-shortcode');
        const default_shortcode_parameters = {
          categories: '',
          layout: 'grid',
          images: 'true'
        };
        const shortcode_parameters = {
          categories: $parent.find('.orderable-select--categories').val().toString(),
          layout: $parent.find('.orderable-select--layout').val().toString(),
          images: $parent.find('.orderable-checkbox--images').is(':checked').toString()
        };
        let shortcode = '[orderable';
        $.each(shortcode_parameters, function (key, value) {
          if (value === default_shortcode_parameters[key]) {
            return;
          }
          shortcode += ' ' + key + '="' + value + '"';
        });
        shortcode += ']';
        $shortcode.val(shortcode);
      });
    }
  };
  $(document).ready(orderable_layouts.on_ready);
})(jQuery, document);
(function ($, document) {
  'use strict';

  var orderable_multiselects = {
    /**
     * On doc ready.
     */
    on_ready() {
      orderable_multiselects.init($('.orderable-select--multi-select'));

      /**
       * Init multiselects trigger.
       */
      $(document).on('orderable-init-multiselects', function (e, data) {
        if (typeof data.selects === 'undefined') {
          return;
        }
        orderable_multiselects.init(data.selects);
      });

      /**
       * Destroy mutliselects trigger.
       */
      $(document).on('orderable-destroy-multiselects', function (e, data) {
        if (typeof data.selects === 'undefined') {
          return;
        }
        orderable_multiselects.destroy(data.selects);
      });
    },
    /**
     * Init multiselects.
     *
     * @param $selects
     */
    init($selects) {
      $selects.multiSelect();
      $selects.each(function (index, select) {
        const $select = $(select),
          $multi_select = $select.siblings('.multi-select-container'),
          $none_option = $multi_select.find('.multi-select-menuitem--none'),
          none_label = $select.data('orderable-select-none-option');
        $none_option.remove();
        const $labels = $multi_select.find('.multi-select-menuitem'),
          $disabled_labels = $multi_select.find('.multi-select-menuitems input:disabled').parent();
        $labels.show();
        $disabled_labels.hide();
        if ($labels.length === $disabled_labels.length && none_label) {
          $multi_select.find('.multi-select-menuitems').append('<span class="multi-select-menuitem multi-select-menuitem--none">' + none_label + '</span>');
        }
      });
    },
    /**
     * Destroy multiselects.
     *
     * @param $selects
     */
    destroy($selects) {
      const $multi_selects = $selects.siblings('.multi-select-container');
      $multi_selects.remove();
      $selects.data('plugin_multiSelect', false);
    }
  };
  $(document).ready(orderable_multiselects.on_ready);
})(jQuery, document);
(function ($, document) {
  'use strict';

  var orderable_onboard = {
    /**
     * On doc ready.
     */
    on_ready() {
      $(document.body).on('iconic_onboard_wait_complete', orderable_onboard.init.woo_installed);
    },
    /**
     * Onboard methods.
     */
    init: {
      /**
       * After woo installed.
       * @param event
       * @param data
       */
      woo_installed(event, data) {
        if (typeof data === 'undefined') {
          return;
        }
        if ('install_plugin' !== data.wait_event) {
          return;
        }
        if ('woocommerce' !== data.json.plugin_data['repo-slug']) {
          return;
        }
        orderable_onboard.populate_woo_fields();
      }
    },
    /**
     * Populate woo fields.
     */
    populate_woo_fields() {
      const fields = {
        default_country: {
          type: 'select',
          selector: 'select#iconic_onboard_default_country'
        },
        business_name: {
          type: 'text',
          selector: 'input#iconic_onboard_business_name'
        },
        business_address: {
          type: 'text',
          selector: 'input#iconic_onboard_business_address'
        },
        business_address_2: {
          type: 'text',
          selector: 'input#iconic_onboard_business_address_2'
        },
        business_city: {
          type: 'text',
          selector: 'input#iconic_onboard_business_city'
        },
        business_postcode: {
          type: 'text',
          selector: 'input#iconic_onboard_business_postcode'
        }
      };
      const data = {
        action: 'orderable_get_onboard_woo_fields',
        fields
      };
      $.post(ajaxurl, data).done(function (response) {
        try {
          if (response.success) {
            $.each(fields, function (index, field) {
              const $field = $(field.selector);
              if ($field.length <= 0 || typeof response.data[index] === 'undefined') {
                return;
              }
              if ('select' === field.type) {
                $field.html(response.data[index]);
              } else {
                $field.val(response.data[index]);
              }
            });
          }
        } catch (ex) {
          console.log(response);
          console.log(ex);
          alert("Couldn't save.");
        }
      }).fail(function () {
        alert("Couldn't save. Are you connected to the internet? ");
      }).always(function () {});
    }
  };
  $(document).ready(orderable_onboard.on_ready);
})(jQuery, document);
jQuery(document).ready(function () {
  function show_orderable_pointer(id) {
    const pointer = orderable_pointers.pointers[id];
    if (pointer === undefined) {
      return;
    }
    const options = jQuery.extend(pointer.options, {
      pointerClass: 'wp-pointer wc-pointer orderable-pointer',
      close() {
        jQuery.post(orderable_pointers.ajax_url, {
          pointer: id,
          action: 'dismiss-wp-pointer'
        });
        if (pointer && pointer.next && orderable_pointers.pointers[pointer.next]) {
          setTimeout(function () {
            show_orderable_pointer(pointer.next);
          }, 250);
        }
      },
      skip() {
        const active_pointers = document.querySelectorAll('.wp-pointer.orderable-pointer');
        Array.from(active_pointers).forEach(function (active_pointer) {
          active_pointer.remove();
        });
        jQuery.post(orderable_pointers.ajax_url, {
          pointer: 'orderable-tour-dismissed',
          action: 'dismiss-wp-pointer'
        });
      },
      buttons(event, t) {
        const next = pointer && pointer.next && orderable_pointers.pointers[pointer.next] ? orderable_pointers.i18n.next : orderable_pointers.i18n.close,
          button = jQuery('<a class="button button-primary" href="#">' + next + '</a>'),
          wrapper = jQuery('<div class="wc-pointer-buttons" />');
        const skip = orderable_pointers.i18n.skip,
          skipButton = jQuery('<a class="button button-secondary" href="#">' + skip + '</a>');
        button.bind('click.pointer', function (e) {
          e.preventDefault();
          t.element.pointer('close');
        });
        skipButton.bind('click.pointer', function (e) {
          e.preventDefault();
          pointer.options.skip();
        });
        wrapper.append(button);
        wrapper.append(skipButton);
        return wrapper;
      }
    });
    const this_pointer = jQuery(pointer.target).pointer(options);
    this_pointer.pointer('open');
    if (pointer.next_trigger) {
      jQuery(pointer.next_trigger.target).on(pointer.next_trigger.event, function () {
        setTimeout(function () {
          this_pointer.pointer('close');
        }, 400);
      });
    }
  }
  function init_orderable_pointers() {
    if (typeof orderable_pointers === 'undefined') {
      return;
    }
    jQuery.each(orderable_pointers.pointers, function (i) {
      show_orderable_pointer(i);
      return false;
    });
  }
  setTimeout(init_orderable_pointers, 800);
});
(function ($, document) {
  'use strict';

  var orderable_pro = {
    /**
     * On ready.
     */
    on_ready() {
      $(document.body).on('orderable-pro-modal', orderable_pro.trigger_pro_modal);
    },
    /**
     * Trigger pro modal.
     */
    trigger_pro_modal() {
      console.log('Pro only.');
      tb_show('Pro Feature', '#TB_inline?inlineId=orderable-pro-modal');
    }
  };
  $(document).ready(orderable_pro.on_ready);
})(jQuery, document);
(function ($, document) {
  'use strict';

  const orderable_timings = {
    /**
     * On doc ready.
     */
    on_ready() {
      /**
       * On enable day.
       */
      $(document.body).on('change', '.orderable-enable-day', function (e) {
        const $checkbox = $(this),
          day = $checkbox.data('orderable-day'),
          checked = $checkbox.is(':checked'),
          $day_selects = $('.orderable-select--days');
        $day_selects.find('option[value="' + day + '"]').attr('disabled', !checked).attr('selected', false);
        $(document).trigger('orderable-destroy-multiselects', {
          selects: $day_selects
        });
        $(document).trigger('orderable-init-multiselects', {
          selects: $day_selects
        });
      });

      /**
       * Toggle delivery/pickup service hours.
       */
      $(document.body).on('change', '[name*="\\[store_general_services\\]"]', function (e) {
        const $checkbox = $(this),
          service = $checkbox.val(),
          checked = $checkbox.is(':checked'),
          $elements = $('.orderable-toggle-wrapper--' + service + ', button[data-orderable-wrapper="' + service + '"]'),
          visibility_class = 'orderable-ui-hide',
          $select_services_notice = $('.orderable-notice--select-service');

        // Toggle visibility of service hours.
        if (checked) {
          $elements.removeClass(visibility_class);
        } else {
          $elements.addClass(visibility_class);
        }
        const $selected_services = $('[name*="\\[store_general_services\\]"]:checked');

        // If no services selected, show message.
        if ($selected_services.length <= 0) {
          $select_services_notice.removeClass(visibility_class);
        } else {
          $select_services_notice.addClass(visibility_class);

          // Toggle first enabled service.
          const active_service = $selected_services.eq(0).val(),
            $active_service_button = $('button[data-orderable-wrapper="' + active_service + '"]');
          $active_service_button.click();
        }

        // If only pickup selected, toggle "same as delivery" checkbox.
        let $same_as_delivery_checkbox = $('#orderable_settings_store_general_service_hours_pickup_same'),
          default_state = $same_as_delivery_checkbox.data('default-state');
        if (typeof default_state === 'undefined') {
          default_state = $same_as_delivery_checkbox.is(':checked');
          $same_as_delivery_checkbox.data('default-state', default_state);
        }
        if (1 === $selected_services.length && 'pickup' === $selected_services.val()) {
          $same_as_delivery_checkbox.prop('checked', false).parent().addClass(visibility_class);
          $(document.body).trigger('orderable-toggle-element', {
            trigger_element: $same_as_delivery_checkbox[0],
            add_class: false
          });
        } else {
          $same_as_delivery_checkbox.prop('checked', default_state).parent().removeClass(visibility_class);
          $(document.body).trigger('orderable-toggle-element', {
            trigger_element: $same_as_delivery_checkbox[0],
            add_class: default_state
          });
        }
        $(document).trigger('orderable-add-last-row-class');
      });

      /**
       * Set default state of "Same as delivery service hours" checkbox.
       */
      $(document.body).on('change', '#orderable_settings_store_general_service_hours_pickup_same', function (e) {
        $(this).data('default-state', $(this).is(':checked'));
      });

      /**
       * Toggle open hours day.
       */
      $('.orderable-enable-day').on('change', function () {
        const $row = jQuery(this).closest('tr'),
          hidden_class = 'orderable-table__row--hidden';
        $row.toggleClass(hidden_class, !this.checked);
      });
    }
  };
  $(document).ready(orderable_timings.on_ready);
})(jQuery, document);
(function ($, document) {
  'use strict';

  var orderable_triggers = {
    /**
     * On doc ready.
     */
    on_ready() {
      /**
       * On trigger click or change.
       */
      $(document.body).on('click change', '[data-orderable-trigger]', function (e) {
        const $trigger_element = $(this),
          trigger = $trigger_element.data('orderable-trigger');
        if ('click' === e.type && ($trigger_element.is('select') || $trigger_element.is('input') || $trigger_element.is('label'))) {
          return;
        }
        if ($trigger_element.is('button') || $trigger_element.is('a')) {
          e.preventDefault();
        }
        $(document.body).trigger('orderable-' + trigger, {
          trigger_element: $trigger_element
        });
      });

      /**
       * On new row.
       */
      $(document.body).on('orderable-new-row', function (e, data) {
        const $button = $(data.trigger_element),
          $target = $($button.data('orderable-target')),
          $body = $target.find('.orderable-table__body'),
          $row = $body.find('.orderable-table__row--repeatable:last-child'),
          index = parseInt($row.data('orderable-index')),
          time_slot = parseInt($row.data('orderable-time-slot')),
          new_index = index + 1,
          row_html = $row[0]?.outerHTML.replace(/\[\d+\]/gm, '[' + new_index + ']').replace(/data-orderable-index="\d+"/gm, 'data-orderable-index="' + new_index + '"').replace(/data-orderable-time-slot="\d+"/gm, 'data-orderable-time-slot=""');
        $body.append(row_html);
        const $new_row = $body.find('.orderable-table__row--repeatable:last-child');

        // Remove delivery zone rows.
        $new_row.find('.orderable-table-delivery-zones-row__item').remove();
        $new_row.find('.orderable-table-delivery-zones-row__message').remove();
        $new_row.find('.orderable-table-delivery-zones-row__no-items').show();

        // Reset inputs.
        $new_row.find('input').not('input[type="checkbox"]').val('');
        $new_row.find('input[type="hidden"][name^="service_hours"]').val('');

        // Reset datepickers.
        const $datepickers = $new_row.find('.hasDatepicker');
        $datepickers.each(function (index, datepicker) {
          const $datepicker = $(datepicker);

          // Remove datepicker class and datepicker generated ID.
          $datepicker.removeClass('hasDatepicker').attr('id', '');
        });

        // Reset selects.
        const $selects = $new_row.find('select');
        $selects.each(function (index, select) {
          const $select = $(select),
            $blank_option = $select.find('option[value=""]'),
            is_multi_select = $select.hasClass('orderable-select--multi-select'),
            value = $blank_option.length || is_multi_select ? '' : $select.find('option:first-child').val();
          $select.val(value);
          if (is_multi_select) {
            $select.change();
          }
        });

        // Checkboxes.
        $new_row.find('input[type="checkbox"]').prop('checked', false);

        // Remove multi select container and reinit.
        $new_row.find('.multi-select-container').remove();
        $(document).trigger('orderable-init-multiselects', {
          selects: $body.find('.orderable-select--multi-select')
        });

        // Trigger select toggles.
        $body.find('[data-orderable-trigger="toggle-element-select"]').change();
        $(document).trigger('orderable-add-last-row-class');
      });

      /**
       * On remove row.
       */
      $(document.body).on('orderable-remove-row', function (e, data) {
        if (!window.confirm(window.orderable_vars.i18n.confirm_remove_service_hours)) {
          e.stopImmediatePropagation();
          return;
        }
        const $button = $(data.trigger_element),
          $row = $button.closest('tr'),
          $tbody = $button.closest('tbody'),
          row_count = $tbody.find('>tr').length;
        if (row_count === 1) {
          $row.find('input').val('');
          $row.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
          $row.find('select').each(function () {
            if ($(this).hasClass('orderable-select--multi-select')) {
              return;
            }
            this.selectedIndex = 0;
            $(this).trigger('change');
          });
          $row.find('.multi-select-container input[type="checkbox"]').trigger('change');
        } else {
          $row.remove();
        }
        $(document).trigger('orderable-add-last-row-class');
      });

      /**
       * On toggle element.
       */
      $(document.body).on('orderable-toggle-element', function (e, data) {
        data.add_class = typeof data.add_class !== 'undefined' ? data.add_class : null;
        const $trigger_element = $(data.trigger_element),
          $target = $($trigger_element.data('orderable-target')),
          toggle_class = $trigger_element.data('orderable-toggle-class');
        if (null === data.add_class) {
          $target.toggleClass(toggle_class);
        } else if (true === data.add_class) {
          $target.addClass(toggle_class);
        } else if (false === data.add_class) {
          $target.removeClass(toggle_class);
        }
        $(document).trigger('orderable-add-last-row-class');
      });

      /**
       * On toggle element select.
       */
      $(document.body).on('orderable-toggle-element-select', function (e, data) {
        const $trigger_element = $(data.trigger_element),
          $parent = $trigger_element.closest($trigger_element.data('orderable-parent')),
          targets = $trigger_element.data('orderable-target'),
          selected = $trigger_element.val();
        if ($parent.length <= 0 || typeof targets === 'undefined' || typeof targets[selected] === 'undefined') {
          return;
        }
        $.each(targets[selected], function (index, target) {
          const $target = $parent.find(target);
          if ('show' === index) {
            $target.show();
          } else if ('hide' === index) {
            $target.hide();
          }
        });
        $(document).trigger('orderable-add-last-row-class');
      });

      /**
       * On toggle wrapper.
       */
      $(document.body).on('orderable-toggle-wrapper', function (e, data) {
        const $trigger_element = $(data.trigger_element),
          wrapper = $trigger_element.data('orderable-wrapper'),
          group = $trigger_element.data('orderable-wrapper-group'),
          $target = $('.orderable-toggle-wrapper--' + wrapper + '[data-orderable-wrapper-group="' + group + '"]');
        $('.orderable-toggle-wrapper[data-orderable-wrapper-group="' + group + '"]').removeClass('orderable-toggle-wrapper--active');
        $target.addClass('orderable-toggle-wrapper--active');
        $('[data-orderable-wrapper-group="' + group + '"]').removeClass('orderable-trigger-element--active');
        $('[data-orderable-wrapper="' + wrapper + '"]').addClass('orderable-trigger-element--active');
        $(document).trigger('orderable-add-last-row-class');
      });

      /**
       * Add last row class trigger.
       */
      $(document).on('orderable-add-last-row-class', orderable_triggers.add_last_row_class);

      /**
       * On ready triggers.
       */
      $(document).trigger('orderable-add-last-row-class');
    },
    /**
     * Add last row class.
     */
    add_last_row_class() {
      const $tables = $('.orderable-table'),
        last_row_class = 'orderable-table__row--last',
        $last_rows = $tables.find('.' + last_row_class),
        $last_row = $tables.find('tbody > tr:visible:last');
      $last_rows.removeClass(last_row_class);
      $last_row.addClass(last_row_class);
    }
  };
  window.orderable_triggers_admin = orderable_triggers;
  $(document).ready(orderable_triggers.on_ready);
})(jQuery, document);