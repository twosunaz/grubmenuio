(function ($) {
  'use strict';

  /* Localized JS vars: window.orderable_dz_js_vars */
  const orderable_dz = {
    /**
     * On document ready.
     */
    on_ready() {
      orderable_dz.cache();
      orderable_dz.add_listeners();
    },
    /**
     * Cache variables.
     */
    cache() {
      orderable_dz.post_id = $('#post_ID').val();
      if ($('#orderable_multi_location_store_services_meta_box').length) {
        orderable_dz.$metabox = $('#orderable_multi_location_store_services_meta_box');
      } else {
        orderable_dz.$metabox = $('#orderable_location_store_services_meta_box');
      }
      orderable_dz.$modal_wrapper = $('#orderable-delivery-zones-modal-wrapper');
      orderable_dz.$modal_add_update = $('#orderable-delivery-zones-modal-add-update');
      orderable_dz.$modal_add_existing = $('#orderable-delivery-zones-modal-add-existing');
      orderable_dz.msg_timeout = null;
      orderable_dz.modal_transition_time_slot_id = null;
      orderable_dz.add_existing_modal_opened = false;
    },
    /**
     * Add event listeners.
     */
    add_listeners() {
      orderable_dz.$modal_wrapper.on('click', '.js-delivery-zones-tab-nav-link', orderable_dz.handler_toggle_tabs);
      orderable_dz.$modal_wrapper.on('click', '#js-cancel-delivery-zone-modal', orderable_dz.handler_close_modal);
      orderable_dz.$modal_wrapper.on('click', '#js-add-new-delivery-zone', orderable_dz.handler_add_update_zone);
      orderable_dz.$modal_wrapper.on('click', '#js-add-existing-delivery-zone', orderable_dz.handler_add_existing_zone);
      orderable_dz.$modal_wrapper.on('click', '#js-transition-existing-to-new-modal', orderable_dz.handler_transition_modal);
      orderable_dz.$modal_wrapper.on('change', '.js-delivery-zones-list-item input', orderable_dz.handler_mark_zone_selected);
      orderable_dz.$modal_wrapper.on('keyup', '#js-delivery-zone-search', orderable_dz.handler_zone_search);
      orderable_dz.$modal_wrapper.on('change keyup', orderable_dz.handler_modal_form_change);
      orderable_dz.$metabox.on('click', '.js-open-add-delivery-zone-modal', orderable_dz.handler_open_add_update_modal);
      orderable_dz.$metabox.on('click', '.js-add-existing-delivery-zone', orderable_dz.handler_open_add_existing_modal);
      orderable_dz.$metabox.on('click', '.js-remove-delivery-zone', orderable_dz.handler_remove_zone);
      $(document.body).on('orderable-remove-row', orderable_dz.handler_remove_all_time_slot_zones);
      $(document).on('keyup', '#js-delivery-zone-modal-fee', orderable_dz.handler_sanitize_fee);
      $(document).on('keyup', orderable_dz.handler_escape_key_close_modal);
      $(document).on('orderable-delivery-zone-ajax-success', orderable_dz.handler_trigger_dom_update);
      $(document).on('orderable-delivery-zone-after-dom-update', orderable_dz.handler_after_dom_update);
    },
    /**
     * Handle AJAX success.
     *
     * @param {Event}  event                      jQuery Event object.
     * @param          request_data.request_data
     * @param {Object} request_data               The data sent and the response of the request.
     * @param          request_data.response_data
     * @return void
     */
    handler_trigger_dom_update(event, {
      request_data: sent_data,
      response_data: response
    }) {
      if (!sent_data.request_type) {
        return;
      }
      switch (sent_data.request_type) {
        case 'edit':
          orderable_dz.update_delivery_zone_in_time_slot(sent_data, response);
          break;
        case 'add_new':
        case 'add_existing':
          orderable_dz.insert_delivery_zone_in_time_slot(sent_data, response);
          break;
        case 'remove':
          orderable_dz.remove_delivery_zone_in_time_slot(sent_data, response);
          break;

        // @TODO - We need to handle deletion via WC shippings?
        case 'delete':
          orderable_dz.remove_delivery_zone_in_list_table(sent_data, response);
          break;
        default:
          break;
      }
    },
    /**
     * Handle AJAX success.
     *
     * @param {Event}  event        jQuery Event object.
     * @param {Object} request_data The data sent and the response of the request.
     * @param          data
     */
    handler_after_dom_update(event, data) {
      // Timeout gives the DOM enough time to update.
      setTimeout(function () {
        if ('add_new' === data.request.request_type) {
          const zone_list_template = wp.template('existing-zones-list-item'),
            zone_list_template_data = orderable_dz.generate_zone_row_data(data.request, data.response, data.time_slot_row);
          $('#js-delivery-zone-modal-zones-list').append(zone_list_template(zone_list_template_data));
        }
        orderable_dz.close_modal();
      }, 250);
    },
    /**
     * Handler: toggle modal tabs.
     *
     * @param {Event} event jQuery Event object.
     */
    handler_toggle_tabs(event) {
      // NOTE: Not required into we implement the "drawing" tab.
      // $( '.orderable-delivery-zones-modal__tab, .orderable-delivery-zones-modal__tabs-nav-link' ).toggleClass( 'active' );
    },
    /**
     * Handler: close modal on Escape key press.
     *
     * @param {Event} event jQuery Event object.
     */
    handler_escape_key_close_modal(event) {
      if (event.key == 'Escape') {
        orderable_dz.handler_close_modal();
      }
    },
    /**
     * Handler: open the add/update modal.
     *
     * @param {Event}  event             jQuery Event object.
     * @param {string} transition_action The type of transition action to take e.g. `add-new`.
     */
    handler_open_add_update_modal(event, transition_action) {
      let action;
      if (transition_action) {
        action = transition_action;
      } else {
        const $btn = $(event.target).hasClass('dashicons') ? $(event.target).parent() : $(event.target);
        action = $btn.data('action');
      }
      const text = 'add-new' === action ? window.orderable_dz_js_vars.text.modal_add : window.orderable_dz_js_vars.text.modal_update,
        time_slot_id = transition_action ? orderable_dz.modal_transition_time_slot_id : orderable_dz.get_time_slot_id(event),
        time_slot_index = orderable_dz.get_time_slot_index(event);
      orderable_dz.$modal_add_update.find('.orderable-delivery-zones-modal__title').text(text);
      orderable_dz.$modal_add_update.find('.orderable-delivery-zones-modal__button--add-update .text').text(text);
      orderable_dz.$modal_add_update.find('.js-delivery-zone-modal-time-slot').val(time_slot_id);
      orderable_dz.$modal_add_update.find('.js-delivery-zone-modal-time-slot-index').val(time_slot_index);
      if ('edit' === action) {
        orderable_dz.form_add_zone_data(event);
      }
      setTimeout(function () {
        $('body').css({
          'overflow-y': 'hidden'
        });
        orderable_dz.$modal_wrapper.show();
        orderable_dz.$modal_add_update.fadeIn();
      }, 250);
    },
    /**
     * Handler: open a modal.
     *
     * @param {Event} event jQuery Event object.
     */
    handler_open_add_existing_modal(event) {
      const time_slot_id = orderable_dz.get_time_slot_id(event),
        time_slot_index = orderable_dz.get_time_slot_index(event);
      orderable_dz.$modal_add_existing.find('.js-delivery-zone-modal-time-slot').val(time_slot_id);
      orderable_dz.$modal_add_existing.find('.js-delivery-zone-modal-time-slot-index').val(time_slot_index);
      orderable_dz.hide_existing_time_slot_zones(event);
      setTimeout(function () {
        $('body').css({
          'overflow-y': 'hidden'
        });
        orderable_dz.$modal_wrapper.show();
        orderable_dz.$modal_add_existing.fadeIn();
        orderable_dz.maybe_show_no_zones_msg();
      }, 100);
    },
    /**
     * Handler: close a modal.
     *
     * @param {Event} event jQuery Event object.
     */
    handler_close_modal(event) {
      orderable_dz.close_modal(event);
    },
    /**
     * Handler: add new zone links.
     *
     * @param {Event} event jQuery Event object.
     */
    handler_add_update_zone(event) {
      orderable_dz.maybe_show_modal_spinner();
      const zone_id = $('#js-delivery-zone-modal-zone-id').val(),
        request_data = {
          request_type: zone_id ? 'edit' : 'add_new',
          post_id: orderable_dz.post_id,
          time_slot_index: parseInt(orderable_dz.$modal_add_update.find('.js-delivery-zone-modal-time-slot-index').val()),
          time_slot_id: parseInt(orderable_dz.$modal_add_update.find('.js-delivery-zone-modal-time-slot').val()),
          zone_name: $('#js-delivery-zone-modal-area-name').val(),
          zone_postcodes: $('#js-delivery-zone-modal-postcodes').val(),
          zone_fee: $('#js-delivery-zone-modal-fee').val()
        };

      // If we have a zone ID, this is an update request,
      // so modify the request_data accordingly.
      if (zone_id) {
        request_data.zone_id = zone_id;
      }
      if (!request_data.zone_name || !request_data.zone_postcodes) {
        if (!request_data.zone_name) {
          $('#js-delivery-zone-modal-valid-name').fadeIn();
        }
        if (!request_data.zone_postcodes) {
          $('#js-delivery-zone-modal-valid-postcodes').fadeIn();
        }
        orderable_dz.maybe_show_modal_spinner();
      } else {
        orderable_dz.handler_trigger_dom_update(event, {
          request_data,
          response_data: {
            data: {
              status: true,
              zone_id: zone_id ? zone_id : Date.now() // In the absence of a zone ID, we use a timestamp.
            }
          }
        });
      }
    },
    /**
     * Handler: add existing zone links.
     *
     * @param {Event} event jQuery Event object.
     */
    handler_add_existing_zone(event) {
      orderable_dz.maybe_show_modal_spinner();
      const $selected_zones = $('.orderable-delivery-zones-modal__field-checkbox:checked');
      $selected_zones.each(function (index, element) {
        // In this context we only need the location, zone and time slot IDs,
        // however by sending this data, we can immediately send it back
        // for the front-end update.
        const request_data = {
          request_type: 'add_existing',
          post_id: orderable_dz.post_id,
          zone_id: $(element).val(),
          time_slot_index: orderable_dz.$modal_add_existing.find('.js-delivery-zone-modal-time-slot-index').val(),
          time_slot_id: orderable_dz.$modal_add_existing.find('.js-delivery-zone-modal-time-slot').val(),
          zone_name: $(element).data('zone-name'),
          zone_postcodes: $(element).data('zone-postcodes'),
          zone_fee: $(element).data('zone-fee')
        };
        orderable_dz.handler_trigger_dom_update(event, {
          request_data,
          response_data: {
            data: {
              status: true,
              zone_id: request_data.zone_id
            }
          }
        });
      });
      orderable_dz.close_modal();
    },
    /**
     * Handler: add existing zone links.
     *
     * @param {Event} event jQuery Event object.
     */
    handler_transition_modal(event) {
      orderable_dz.modal_transition_time_slot_id = $(event.target).closest('.orderable-delivery-zones-modal__form').find('.js-delivery-zone-modal-time-slot').val();
      orderable_dz.close_modal(event, true);
    },
    /**
     * Handler: remove zone links.
     *
     * @param {Event} event jQuery Event object.
     */
    handler_remove_zone(event) {
      if (!window.confirm(window.orderable_dz_js_vars.text.zone_confirm_remove)) {
        return;
      }
      const time_slot_id = orderable_dz.get_time_slot_id(event),
        zone_id = orderable_dz.get_zone_id(event);
      orderable_dz.handler_trigger_dom_update(event, {
        request_data: {
          request_type: 'remove',
          zone_id,
          time_slot_id
        },
        response_data: {
          data: {
            status: true,
            zone_ids: [zone_id]
          }
        }
      });
    },
    /**
     * Handler: remove all zones from a time slot when
     * the slot is removed from the location.
     *
     * This prevents orphaned zones still associated
     * with a location, but without a valid time slot.
     *
     * @param {Event}  event The jQuery Event object.
     * @param {Object} data  jQuery Event data.
     */
    handler_remove_all_time_slot_zones(event, data) {
      const $row = $(data.trigger_element).closest('.orderable-table__row'),
        time_slot_id = $row.data('orderable-time-slot'),
        $zones = $row.find('.orderable-table-delivery-zones-row__item'),
        zone_ids = [];
      $zones.each(function (index, element) {
        zone_ids.push($(element).data('zone-id'));
      });
      orderable_dz.handler_trigger_dom_update(event, {
        request_data: {
          request_type: 'remove',
          zone_ids,
          time_slot_id
        },
        response_data: {
          data: {
            status: true,
            zone_ids,
            msg: 'All delivery zones successfully removed!'
          }
        }
      });
    },
    /**
     * Handler: sanitize the fee field.
     *
     * @param {Event} event The jQuery Event object.
     */
    handler_sanitize_fee(event) {
      $(event.target).val($(event.target).val().replace(/[^0-9.]/gm, ''));
    },
    /**
     * Handler: listen for zone search change and update the list.
     *
     * @param {Event} event The jQuery Event object.
     */
    handler_zone_search(event) {
      const $zone_items = $('.orderable-delivery-zones-modal-add-existing .orderable-delivery-zones-modal__zones-list-item:not(.hide-existing)'),
        search_query = $(event.target).val().toLowerCase();
      let debounce_search;
      if (!$zone_items.length) {
        orderable_dz.maybe_show_no_zones_msg();
        return;
      }
      clearTimeout(debounce_search);
      debounce_search = setTimeout(function () {
        if (!search_query || search_query.length < 2) {
          // Show all rows if the query is empty.
          $zone_items.show();
          orderable_dz.maybe_show_no_zones_msg();
        } else {
          // Filters based on the search query.
          $zone_items.each(function (index, element) {
            const $zone = $(element),
              zone_name = $zone.find('input[type=checkbox]').data('zone-name').toLowerCase();
            if ($zone.hasClass('hide-existing') || !zone_name.includes(search_query)) {
              $(element).hide();
            } else {
              $(element).show();
            }
          });
          orderable_dz.maybe_show_no_zones_msg();
        }
      }, 100);
    },
    /**
     * Handler: take action when a modal form changes.
     *
     * @param {Event} event The jQuery Event object.
     */
    handler_modal_form_change(event) {
      const $add_new_button = $('#js-add-new-delivery-zone'),
        $add_existing_button = $('#js-add-existing-delivery-zone');

      // Change state of the add new zone button
      // based on whether the postcode and area
      // name fields are populated.
      if ($('#js-delivery-zone-modal-postcodes').val() && $('#js-delivery-zone-modal-area-name').val()) {
        $add_new_button.prop('disabled', false);
      } else {
        $add_new_button.prop('disabled', true);
      }

      // Change state of the add existing zone button
      // based on the selections made in the list.
      if ($('.orderable-delivery-zones-modal__zones-list-item.selected').length) {
        $add_existing_button.prop('disabled', false);
      } else {
        $add_existing_button.prop('disabled', true);
      }
    },
    /**
     * Handler: mark a zone selected.
     *
     * @param {Event} event The jQuery Event object.
     */
    handler_mark_zone_selected(event) {
      const $zone_item = $(event.target).closest('.orderable-delivery-zones-modal__zones-list-item');
      if ($zone_item.hasClass('selected')) {
        $zone_item.removeClass('selected');
      } else {
        $zone_item.addClass('selected');
      }
    },
    /**
     * Maybe show the "no zones" msg.
     */
    maybe_show_no_zones_msg() {
      const $zone_items = $('.orderable-delivery-zones-modal-add-existing .orderable-delivery-zones-modal__zones-list-item:not(.hide-existing):visible'),
        $no_zones_msg = $('#js-no-delivery-zones-msg'),
        $add_zones_button = $('.orderable-delivery-zones-modal__button--add-existing');
      if (!$zone_items.length) {
        $add_zones_button.prop('disabled', true);
        $no_zones_msg.show();
      } else {
        if (orderable_dz.add_existing_modal_opened) {
          $add_zones_button.prop('disabled', false);
          orderable_dz.add_existing_modal_opened = true;
        }
        $no_zones_msg.hide();
      }
    },
    /**
     * Generate the data for a specific zone row.
     *
     * @param {Object} request        Request data.
     * @param {Object} response       Request data.
     * @param {Object} $time_slot_row jQuery Object.
     */
    generate_zone_row_data(request, response, $time_slot_row = false) {
      let delivery_zone_id = null;
      const zone_id = request.zone_id ? request.zone_id : response.data.zone_id;

      // Don't increment the row count if this is an edit action.
      if ($time_slot_row.length) {
        if (request.request_type === 'edit') {
          delivery_zone_id = $time_slot_row.find(`.orderable-table-delivery-zones-row__item[data-zone-id=${zone_id}]`).data('zone-count');
        } else {
          delivery_zone_id = $time_slot_row.find('.orderable-table-delivery-zones-row__item').length + 1;
        }
      }
      const zone_data = {
        time_slot_id: request.time_slot_id,
        zone_id,
        zone_name: request.zone_name,
        zone_postcodes: request.zone_postcodes,
        zone_fee: request.zone_fee
      };
      return {
        ...zone_data,
        input_value: JSON.stringify(zone_data),
        delivery_zone_id,
        time_slot_index: request.time_slot_index,
        text_zone_title: window.orderable_dz_js_vars.text.zone_title,
        text_edit_zone: window.orderable_dz_js_vars.text.zone_edit,
        text_remove_zone: window.orderable_dz_js_vars.text.zone_remove
      };
    },
    /**
     * Insert a new delivery zone in the time slot.
     *
     * @param {Object} request  Request data.
     * @param {Object} response Request data.
     */
    insert_delivery_zone_in_time_slot(request, response) {
      const $time_slot_row = $(`.orderable-toggle-wrapper--delivery [data-orderable-index=${request.time_slot_index}]`),
        row_template = wp.template('delivery-zones-row'),
        row_template_data = orderable_dz.generate_zone_row_data(request, response, $time_slot_row);
      $time_slot_row.find('.orderable-table-delivery-zones-row__no-items').hide();
      $time_slot_row.find('.orderable-table-delivery-zones-row__actions').before(row_template(row_template_data));
      const status = response.data.status ? 'success' : 'error';
      $(document).trigger('orderable-delivery-zone-after-dom-update', {
        status,
        request,
        response,
        time_slot_row: $time_slot_row
      });
    },
    /**
     * Insert a new delivery zone in the time slot.
     *
     * @param {Object} request  Request data.
     * @param {Object} response Response data.
     */
    update_delivery_zone_in_time_slot(request, response) {
      if (!request.time_slot_id) {
        return;
      }
      const $time_slot_row = $(`.orderable-toggle-wrapper--delivery [data-orderable-time-slot=${request.time_slot_id}]`),
        template = wp.template('delivery-zones-row'),
        template_data = orderable_dz.generate_zone_row_data(request, response, $time_slot_row),
        zone_id = request.zone_id ? request.zone_id : response.data.zone_id;
      $time_slot_row.find(`.orderable-table-delivery-zones-row__item[data-zone-id=${zone_id}]`).replaceWith(template(template_data));
      $(document).trigger('orderable-delivery-zone-after-dom-update', {
        status: 'success',
        request,
        response
      });
    },
    /**
     * Remove a delivery zone in the time slot.
     *
     * @param {Object} request  Request data.
     * @param {Object} response Response data.
     */
    remove_delivery_zone_in_time_slot(request, response) {
      for (const zone_id of response.data.zone_ids) {
        $(`.orderable-toggle-wrapper--delivery [data-orderable-time-slot=${request.time_slot_id}] .orderable-table-delivery-zones-row__item[data-zone-id=${zone_id}]`).remove();
      }
      const $time_slot_row = $(`.orderable-toggle-wrapper--delivery [data-orderable-time-slot=${request.time_slot_id}]`);
      if (!$time_slot_row.find('.orderable-table-delivery-zones-row__item').length) {
        $time_slot_row.find('.orderable-table-delivery-zones-row__no-items').fadeIn();
      }
      $(document).trigger('orderable-delivery-zone-after-dom-update', {
        status: response.success,
        request,
        response
      });
      orderable_dz.reset_modal_forms();
    },
    /**
     * Populate the modal form with data when editing an existing zone.
     *
     * @param {Event} event The jQuery Event object.
     */
    form_add_zone_data(event) {
      const $delivery_zone_row = $(event.target).closest('[data-zone-id]');
      $('#js-delivery-zone-modal-zone-id').val($delivery_zone_row.attr('data-zone-id'));
      $('#js-delivery-zone-modal-postcodes').val($delivery_zone_row.attr('data-zone-postcodes'));
      $('#js-delivery-zone-modal-area-name').val($delivery_zone_row.attr('data-zone-name'));
      $('#js-delivery-zone-modal-fee').val($delivery_zone_row.attr('data-zone-fee'));
      if ($('#js-delivery-zone-modal-postcodes').val() && $('#js-delivery-zone-modal-area-name').val()) {
        $('#js-add-new-delivery-zone').prop('disabled', false);
      }
    },
    /**
     * Close the modal and reset the forms.
     *
     * @param {Event}   event                 The jQuery Event object.
     * @param {boolean} open_add_update_modal True to open the add/update modal.
     */
    close_modal(event, open_add_update_modal = false) {
      // Add a small delay to mask any UI change in the background.
      setTimeout(function () {
        $('body').css({
          'overflow-y': 'visible'
        });
        if (!open_add_update_modal) {
          orderable_dz.$modal_wrapper.fadeOut();
        }
        orderable_dz.$modal_wrapper.find('.orderable-delivery-zones-modal').hide();
        $('.orderable-delivery-zones-modal__msg').fadeOut();
        orderable_dz.maybe_show_modal_spinner(true);
        orderable_dz.unhide_existing_time_slot_zones();
        orderable_dz.reset_modal_forms();
        orderable_dz.add_existing_modal_opened = false;
        $('.orderable-delivery-zones-modal__footer .orderable-delivery-zones-modal__button--add-update').prop('disabled', true);
        $('.orderable-delivery-zones-modal__footer .orderable-delivery-zones-modal__button--add-existing').prop('disabled', true);
        if (open_add_update_modal) {
          orderable_dz.handler_open_add_update_modal(event, 'add-new');
        }
      }, 250);
    },
    /**
     * Reset the modal forms.
     */
    reset_modal_forms() {
      orderable_dz.$modal_wrapper.find('input:not([type="checkbox"]), textarea').val('');
      orderable_dz.$modal_wrapper.find('input[type="checkbox"]').removeAttr('checked');
      orderable_dz.$modal_wrapper.find('.orderable-delivery-zones-modal__zones-list-item').removeClass('selected').fadeIn();
    },
    /**
     * Maybe show the modal spinner.
     *
     * @param {boolean} remove True to force remove the class.
     */
    maybe_show_modal_spinner(remove = false) {
      const $icon = $('.orderable-delivery-zones-modal__button .icon');
      if (remove || $icon.hasClass('active')) {
        $icon.removeClass('active');
      } else {
        $icon.addClass('active');
      }
    },
    /**
     * Hide zones already added to a time zone in the existing zones list.
     *
     * @param {Event} event The jQuery Event object.
     */
    hide_existing_time_slot_zones(event) {
      const time_slot_id = orderable_dz.get_time_slot_id(event),
        $time_slot_row = $(`.orderable-toggle-wrapper--delivery [data-orderable-time-slot=${time_slot_id}]`),
        $modal_existing_zones = $('.orderable-delivery-zones-modal__zones-list-item');
      if (!$time_slot_row.length) {
        return;
      }
      $modal_existing_zones.each(function (index, element) {
        const zone_id = $(element).find('input').val();
        if (zone_id && $time_slot_row.find(`.orderable-table-delivery-zones-row__item[data-zone-id="${zone_id}"]`).length) {
          $(element).addClass('hide-existing');
        }
      });
    },
    /**
     * Unhide zones already added to a time zone in the existing zones list.
     */
    unhide_existing_time_slot_zones() {
      $('.orderable-delivery-zones-modal__zones-list-item').removeClass('hide-existing');
    },
    /**
     * Get the time slot ID from the parent row
     * when clicking on an action link.
     *
     * @param {Event} event The jQuery Event object.
     */
    get_time_slot_id(event) {
      // Service hours UI.
      return parseInt($(event.target).closest('.orderable-table__row').data('orderable-time-slot'));
    },
    /**
     * Get the time slot index from the parent row
     * when clicking on an action link.
     *
     * @param {Event} event The jQuery Event object.
     */
    get_time_slot_index(event) {
      // Service hours UI.
      return parseInt($(event.target).closest('.orderable-table__row').data('orderable-index'));
    },
    /**
     * Get the time slot index from the parent row
     * when clicking on an action link.
     *
     * @param {Event} event The jQuery Event object.
     */
    get_zone_id(event) {
      return $(event.target).closest('.orderable-table-delivery-zones-row__item').data('zone-id');
    }
  };
  $(document).ready(orderable_dz.on_ready);
})(jQuery);
(function ($) {
  var orderable_multi_location = {
    on_ready() {
      $('.orderable-toggle-field').on('click', orderable_multi_location.handle_toggle_field_on_click);
      $('.orderable-override-open-hours-toggle-field').on('click', orderable_multi_location.handle_override_open_hours_on_click);
      $('.orderable-delivery-toggle-field').on('click', orderable_multi_location.handle_enable_service_delivery_on_click);
      $('.orderable-pickup-toggle-field').on('click', orderable_multi_location.handle_enable_service_pickup_on_click);
      $('.orderable-admin-button--pickup').on('click', function () {
        if ($('#orderable_location_service_hours_pickup_same_as_delivery').prop('checked')) {
          $('.orderable-element--pickup').addClass('orderable-element--disabled');
        } else {
          $('.orderable-element--pickup').removeClass('orderable-element--disabled');
        }
      });
      const datepicker_args = $('.datepicker').data('datepicker');
      $('.datepicker').datepicker(datepicker_args);
      $(document.body).on('orderable-new-row', orderable_multi_location.on_new_holiday_row);
    },
    handle_toggle_field_on_click() {
      $(this).toggleClass(['woocommerce-input-toggle--disabled', 'woocommerce-input-toggle--enabled']);
      const value = $(this).hasClass('woocommerce-input-toggle--enabled');
      $(this).siblings('.orderable-toggle-field__input').val(value ? 'yes' : 'no');
    },
    handle_override_open_hours_on_click() {
      $(this).siblings('.orderable-open-hours-settings').toggleClass('orderable-store-open-hours--hide');
      $('.orderable-store-open-hours__open-hours').toggleClass('orderable-store-open-hours--hide');
    },
    handle_enable_service_delivery_on_click() {
      const delivery_is_enabled = $(this).hasClass('woocommerce-input-toggle--enabled');
      pickup_is_enabled = $('[name=orderable_location_store_services_pickup]').val() === 'yes';
      if (delivery_is_enabled) {
        $('.orderable-admin-button--delivery').removeClass('orderable-ui-hide');
        $('.orderable-notice--select-service').addClass('orderable-ui-hide');
      } else {
        $('.orderable-admin-button--delivery').addClass('orderable-ui-hide').removeClass('orderable-trigger-element--active');
      }
      if (pickup_is_enabled && delivery_is_enabled) {
        $('#orderable_location_service_hours_pickup_same_as_delivery_label').removeClass('orderable-ui-hide');
        const has_pickup_days_selected = $('.orderable-toggle-wrapper--pickup').find('.orderable-select--days').first().val().length;
        if (!has_pickup_days_selected) {
          $('#orderable_location_service_hours_pickup_same_as_delivery').prop('checked', true).change();
        }
        return;
      }
      if (delivery_is_enabled && !pickup_is_enabled) {
        $('#orderable_location_service_hours_pickup_same_as_delivery_label').removeClass('orderable-ui-hide');
        $('.orderable-admin-button--delivery').addClass('orderable-trigger-element--active');
        $('.orderable-toggle-wrapper--delivery').addClass('orderable-toggle-wrapper--active');
        return;
      }
      if (!delivery_is_enabled && !pickup_is_enabled) {
        $('.orderable-notice--select-service').removeClass('orderable-ui-hide');
        $('.orderable-toggle-wrapper--delivery').removeClass('orderable-toggle-wrapper--active');
        return;
      }
      if (!delivery_is_enabled && pickup_is_enabled) {
        $('#orderable_location_service_hours_pickup_same_as_delivery').prop('checked', false).change();
        $('#orderable_location_service_hours_pickup_same_as_delivery_label').addClass('orderable-ui-hide');
        $('.orderable-table--service-hours-pickup').removeClass('orderable-element--disabled');
        $('.orderable-admin-button--pickup').addClass('orderable-trigger-element--active');
        $('.orderable-toggle-wrapper--pickup').addClass('orderable-toggle-wrapper--active').removeClass('orderable-element--disabled');
        $('.orderable-admin-button--delivery').removeClass('orderable-trigger-element--active');
        $('.orderable-toggle-wrapper--delivery').removeClass('orderable-toggle-wrapper--active');
      }
    },
    handle_enable_service_pickup_on_click() {
      const pickup_is_enabled = $(this).hasClass('woocommerce-input-toggle--enabled'),
        delivery_is_enabled = $('[name=orderable_location_store_services_delivery]').val() === 'yes';
      if (pickup_is_enabled) {
        $('.orderable-admin-button--pickup').removeClass('orderable-ui-hide');
        $('.orderable-table--service-hours-pickup').removeClass('orderable-element--disabled');
        $('.orderable-notice--select-service').addClass('orderable-ui-hide');
      } else {
        $('.orderable-admin-button--pickup').addClass('orderable-ui-hide').removeClass('orderable-trigger-element--active');
      }
      if (pickup_is_enabled && delivery_is_enabled) {
        $('#orderable_location_service_hours_pickup_same_as_delivery_label').removeClass('orderable-ui-hide');
        $('#orderable_location_service_hours_pickup_same_as_delivery').prop('checked', true).change();
        return;
      }
      if (pickup_is_enabled && !delivery_is_enabled) {
        $('#orderable_location_service_hours_pickup_same_as_delivery').prop('checked', false).change();
        $('#orderable_location_service_hours_pickup_same_as_delivery_label').addClass('orderable-ui-hide');
        $('.orderable-admin-button--pickup').addClass('orderable-trigger-element--active');
        $('.orderable-toggle-wrapper--pickup').addClass('orderable-toggle-wrapper--active').removeClass('orderable-element--disabled');
        $('.orderable-element--pickup').removeClass('orderable-element--disabled');
        return;
      }
      if (!pickup_is_enabled && delivery_is_enabled) {
        $('#orderable_location_service_hours_pickup_same_as_delivery').prop('checked', true).change();
        $('#orderable_location_service_hours_pickup_same_as_delivery_label').addClass('orderable-ui-hide');
        $('.orderable-table--service-hours-delivery').removeClass('orderable-element--disabled');
        $('.orderable-admin-button--delivery').addClass('orderable-trigger-element--active');
        $('.orderable-toggle-wrapper--delivery').addClass('orderable-toggle-wrapper--active').removeClass('orderable-element--disabled');
        $('.orderable-admin-button--pickup').removeClass('orderable-trigger-element--active');
        $('.orderable-toggle-wrapper--pickup').removeClass('orderable-toggle-wrapper--active');
        return;
      }
      if (!delivery_is_enabled && !pickup_is_enabled) {
        $('.orderable-notice--select-service').removeClass('orderable-ui-hide');
        $('.orderable-toggle-wrapper--pickup').removeClass('orderable-toggle-wrapper--active');
      }
    },
    on_new_holiday_row() {
      const $row = $('.orderable-table--holidays').find('.orderable-table__row--repeatable:last-child');
      $row.find('.datepicker').each(function () {
        const args = $(this).data('datepicker');
        $(this).datepicker(args);
      });
    }
  };
  $(document).ready(orderable_multi_location.on_ready);
})(jQuery);