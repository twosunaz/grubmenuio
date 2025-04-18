
(function ($, document) {
  'use strict';

  var orderable_accordion = {
    /**
     * On doc ready.
     */
    on_ready() {
      orderable_accordion.cache();
      orderable_accordion.watch();
    },
    /**
     * Cache.
     */
    cache() {
      orderable_accordion.vars = {
        classes: {
          parent: 'orderable-accordion',
          link: 'orderable-accordion__item-link',
          content: 'orderable-accordion__item-content',
          link_active: 'orderable-accordion__item-link--active',
          content_active: 'orderable-accordion__item-content--active'
        }
      };
    },
    /**
     * Watch.
     */
    watch() {
      /**
       * When click accordion link.
       */
      $(document.body).on('click', '.' + orderable_accordion.vars.classes.link, function (e) {
        e.preventDefault();
        const $link = $(this),
          $parent = $link.closest('.' + orderable_accordion.vars.classes.parent),
          content_id = $link.attr('href'),
          $content = $(content_id),
          is_active = $link.hasClass(orderable_accordion.vars.classes.link_active);
        $parent.find('.' + orderable_accordion.vars.classes.link).removeClass(orderable_accordion.vars.classes.link_active);
        $parent.find('.' + orderable_accordion.vars.classes.content).removeClass(orderable_accordion.vars.classes.content_active);
        if (!is_active) {
          $link.addClass(orderable_accordion.vars.classes.link_active);
          $content.addClass(orderable_accordion.vars.classes.content_active);
        }
        $(document.body).trigger('orderable-accordion.toggled', {
          link: $link,
          content: $content
        });
      });

      /**
       * When drawer is opened.
       */
      $(document.body).on('orderable-scrollbar.created', function (e, args) {
        const $active_accordion = $('.orderable-drawer .' + orderable_accordion.vars.classes.link_active);
        if ($active_accordion.length <= 0) {
          return;
        }
        const $scroll_content = args.content,
          scroll_position = $scroll_content.scrollTop() - $scroll_content.offset().top + $active_accordion.offset().top;
        $scroll_content.scrollTop(scroll_position);
      });
    }
  };
  $(document).ready(orderable_accordion.on_ready);
})(jQuery, document);
(function ($, document) {
  'use strict';

  var orderable_drawer = {
    /**
     * Delays invoking function
     *
     * @param {Function} func    The function to debounce.
     * @param {number}   timeout The number of milliseconds to delay.
     * @return {Function} Returns the new debounced function.
     */
    debounce(func, timeout = 700) {
      let timer;
      return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => {
          func.apply(this, args);
        }, timeout);
      };
    },
    /**
     * Allow only number for events like keypress
     *
     * @param {Event} event
     */
    allow_only_numbers(event) {
      const value = String.fromCharCode(event.which);
      if (!/^\d+$/.test(value)) {
        event.preventDefault();
      }
    },
    /**
     * Send a request to change the quantity.
     *
     * @param {Event} event
     */
    on_change_quantity(event) {
      const quantityElement = $(event.currentTarget);
      const product_id = quantityElement.data('orderable-product-id');
      const cart_item_key = quantityElement.data('orderable-cart-item-key');
      const quantity = parseInt(quantityElement.text());
      const data = {
        action: 'orderable_cart_quantity',
        cart_item_key,
        product_id,
        quantity
      };
      jQuery.post(orderable_vars.ajax_url, data, function (response) {
        if (!response) {
          return;
        }
        $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, quantityElement]);
        $(document.body).trigger('orderable-drawer.quantity-updated');
      });
    },
    /**
     * On doc ready.
     */
    on_ready() {
      orderable_drawer.cache();
      orderable_drawer.watch();

      /**
       * Handle manually changing the quantity of a product.
       */
      $(document.body).on('keypress', '.orderable-quantity-roller__quantity', orderable_drawer.allow_only_numbers);
      $('.orderable-drawer__cart, .orderable-product--add-to-cart, .orderable-products-list').on('input', '.orderable-quantity-roller__quantity', orderable_drawer.debounce(orderable_drawer.on_change_quantity));
      $(document.body).on('click', '.orderable-quantity-roller__quantity', function (event) {
        event.stopPropagation();
      });

      /**
       * We turn off the click event for .add_to_cart_button elements
       * to keep the AJAX behaviour only on Mini cart when the option
       * "Enable AJAX add to cart buttons on archives" is disabled.
       */
      if (orderable_vars && !orderable_vars.woocommerce_enable_ajax_add_to_cart) {
        $(document.body).off('click', '.add_to_cart_button');
      }
    },
    /**
     * Cache.
     */
    cache() {
      orderable_drawer.vars = {
        classes: {
          overlay: 'orderable-drawer-overlay',
          drawer: 'orderable-drawer',
          drawer_cart: 'orderable-drawer__cart',
          drawer_html: 'orderable-drawer__html',
          overlay_open: 'orderable-drawer-overlay--open',
          drawer_open: 'orderable-drawer--open',
          drawer_open_body: 'orderable-drawer-open'
        }
      };
      orderable_drawer.elements = {
        body: $('body'),
        overlay: $('.' + orderable_drawer.vars.classes.overlay),
        drawer: $('.' + orderable_drawer.vars.classes.drawer),
        drawer_cart: $('.' + orderable_drawer.vars.classes.drawer_cart),
        drawer_html: $('.' + orderable_drawer.vars.classes.drawer_html),
        floating_cart_button_class: '.orderable-floating-cart__button'
      };
    },
    /**
     * Watch for trigger events.
     */
    watch() {
      if (typeof orderable_drawer.elements.drawer === 'undefined') {
        return;
      }
      $(document.body).on('orderable-drawer.open', orderable_drawer.open);
      $(document.body).on('orderable-drawer.close', orderable_drawer.close);
      $(document.body).on('click', orderable_drawer.elements.floating_cart_button_class, function () {
        $(document.body).trigger('orderable-drawer.open', {
          show_cart: true
        });
      });
      $(document.body).on('orderable-increase-quantity', orderable_drawer.cart.handle_quantity_change_by_button);
      $(document.body).on('orderable-decrease-quantity', orderable_drawer.cart.handle_quantity_change_by_button);
      const updateQuantityRequest = orderable_drawer.debounce(orderable_drawer.cart.click_increase_decrease_quantity);
      $(document.body).on('orderable-increase-quantity', updateQuantityRequest);
      $(document.body).on('orderable-decrease-quantity', updateQuantityRequest);
      const drawer = document.querySelector('body:not( .rtl ) .orderable-drawer');
      const drawer_rtl = document.querySelector('body.rtl .orderable-drawer');
      if (drawer) {
        drawer.addEventListener('swiped-right', function (e) {
          orderable_drawer.close();
        });
      }
      if (drawer_rtl) {
        drawer_rtl.addEventListener('swiped-left', function (e) {
          orderable_drawer.close();
        });
      }
    },
    /**
     * Open the drawer.
     * @param event
     * @param args
     */
    open(event, args) {
      args.html = args.html || false;
      args.show_cart = args.show_cart || false;
      orderable_drawer.elements.drawer_html.hide();
      orderable_drawer.elements.drawer_cart.hide();
      if (args.html) {
        orderable_drawer.elements.drawer_html.html(args.html);
        orderable_drawer.elements.drawer_html.show();
      }
      if (args.show_cart) {
        // Empty drawer HTML before showing cart. Prevents options
        // interfering with subsequent cart additions.
        orderable_drawer.elements.drawer_html.html('');
        orderable_drawer.elements.drawer_cart.show();
      }
      orderable_drawer.elements.overlay.addClass(orderable_drawer.vars.classes.overlay_open);
      orderable_drawer.elements.drawer.addClass(orderable_drawer.vars.classes.drawer_open);
      orderable_drawer.elements.body.addClass(orderable_drawer.vars.classes.drawer_open_body);
      $(document.body).trigger('orderable-drawer.opened', args);
    },
    /**
     * Close the drawer.
     */
    close() {
      orderable_drawer.elements.overlay.removeClass(orderable_drawer.vars.classes.overlay_open);
      orderable_drawer.elements.drawer.removeClass(orderable_drawer.vars.classes.drawer_open);
      orderable_drawer.elements.body.removeClass(orderable_drawer.vars.classes.drawer_open_body);
      orderable_drawer.elements.drawer_html.html('');
      $(document.body).trigger('orderable-drawer.closed');
    },
    /**
     * Mini cart related functions.
     */
    cart: {
      /**
       * When increase qty is clicked.
       *
       * @param e
       * @param $button
       */
      click_increase_decrease_quantity(e, $button) {
        const direction = $button.data('orderable-trigger');
        const product_id = $button.attr('data-orderable-product-id'),
          cart_item_key = $button.attr('data-orderable-cart-item-key'),
          quantity = $button.attr('data-orderable-quantity');
        const siblingButtonName = 'increase-quantity' === direction ? 'decrease' : 'increase';
        const $siblingButton = $button.siblings(`.orderable-quantity-roller__button--${siblingButtonName}`);
        const $quantityElement = $button.siblings('.orderable-quantity-roller__quantity');
        const data = {
          action: 'orderable_cart_quantity',
          cart_item_key,
          product_id,
          quantity
        };
        if (this.currentRequest) {
          this.currentRequest.abort();
          this.currentRequest = undefined;
        }
        $button.addClass('orderable-button--loading');
        $button.attr('disabled', true);
        $siblingButton.attr('disabled', true);
        $quantityElement.attr('contenteditable', false);
        this.currentRequest = jQuery.post(orderable_vars.ajax_url, data, function (response) {
          if (!response) {
            return;
          }
          const $quantityElement = $button.siblings('.orderable-quantity-roller__quantity');
          if (response && response.fragments && response.fragments['.orderable-mini-cart__notices']) {
            $(document.body).trigger('orderable-drawer.open', {
              show_cart: true
            });
          }
          switch (data.quantity) {
            case '0':
              $(document.body).trigger('removed_from_cart', [response.fragments, response.cart_hash, $button]);
              break;
            case $quantityElement.attr('data-orderable-updating-quantity'):
              $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
              $(document.body).trigger('orderable-drawer.quantity-updated');
              break;
            default:
              break;
          }
        }.bind(this)).always(function () {
          this.currentRequest = undefined;
          $button.removeClass('orderable-button--loading');
          $button.attr('disabled', false);
          $siblingButton.attr('disabled', false);
          $quantityElement.attr('contenteditable', true);
        }.bind(this));
      },
      handle_quantity_change_by_button(e, $button) {
        const direction = $button.data('orderable-trigger');
        const quantity = parseInt($button.attr('data-orderable-quantity'));
        const siblingButtonName = 'increase-quantity' === direction ? 'decrease' : 'increase';
        const $siblingButton = $button.siblings(`.orderable-quantity-roller__button--${siblingButtonName}`);
        const $quantityElement = $button.siblings('.orderable-quantity-roller__quantity');
        const newQuantity = 'increase-quantity' === direction ? quantity + 1 : Math.max(0, quantity - 1);
        const $parent = $button.parents('.orderable-product__actions-button');
        if (0 === newQuantity && $parent.length) {
          const $addToCartButton = $parent.find('button.orderable-button[data-orderable-trigger]');
          const $quantityRoller = $parent.find('.orderable-quantity-roller');
          if ($quantityRoller.length) {
            $addToCartButton.removeClass('orderable-button--product-in-the-cart');
            $quantityRoller.removeClass('orderable-quantity-roller--is-active');
          }
        }
        $button.attr('data-orderable-quantity', newQuantity);
        $siblingButton.attr('data-orderable-quantity', newQuantity);
        $quantityElement.attr('data-orderable-updating-quantity', newQuantity);
        $quantityElement.text(newQuantity);
        $quantityElement.attr('contenteditable', false);
      }
    }
  };
  $(document).ready(orderable_drawer.on_ready);
})(jQuery, document);
(function ($, document) {
  'use strict';

  var orderable_products = {
    /**
     * On doc ready.
     */
    on_ready() {
      orderable_products.cache();
      orderable_products.watch();
    },
    /**
     * Cache.
     */
    cache() {
      orderable_products.vars = {
        classes: {
          clickable_product: 'orderable-product--add-to-cart ',
          add_to_order_button: 'orderable-product__add-to-order',
          product_messages: 'orderable-product__messages',
          product_price: 'orderable-product__actions-price',
          invalid_field: 'orderable-field--invalid',
          option_select_td: 'orderable-product__option-select',
          button_loading: 'orderable-button--loading',
          out_of_stock: 'orderable-button--out-of-stock'
        },
        parent_price: null,
        variable_product_types: ['variable', 'variable-subscription']
      };
      orderable_products.elements = {};
    },
    /**
     * Watch for trigger events.
     */
    watch() {
      $(document.body).on('orderable-drawer.opened', orderable_products.init_product_options);
      $(document.body).on('orderable-add-to-cart', orderable_products.click_add_to_order);
      $(document.body).on('orderable-product-options', orderable_products.click_add_to_order);
      $(document.body).on('orderable-view-product', orderable_products.view_product);
      $(document.body).on('mouseenter mouseleave', '.' + orderable_products.vars.classes.clickable_product, orderable_products.simulate_add_to_order_hover);
      $(document.body).on('orderable-edit-cart-item', orderable_products.edit_cart_item);
      $(document.body).on('orderable-update-cart-item', orderable_products.update_cart_item);
      $(document.body).on('orderable-show-cart', orderable_products.show_cart);
      $(document.body).on('orderable-add-to-cart-without-side-drawer', orderable_products.click_add_to_order);
      $(document.body).on('added_to_cart', orderable_products.remove_fragments);
      $(document.body).on('added_to_cart', orderable_products.remove_animation);
      $(document.body).on('added_to_cart', orderable_products.shake_floating_cart);
      $(document.body).on('removed_from_cart', orderable_products.hide_quantity_roller);
      $(document.body).on('wc_cart_button_updated', orderable_products.remove_view_cart_link);
    },
    /**
     * Simulate hover on add to order button.
     *
     * @param event
     */
    simulate_add_to_order_hover(event) {
      const $element = $(this),
        $button = $element.find('.' + orderable_products.vars.classes.add_to_order_button);
      $button.toggleClass('orderable-button--hover', 'mouseenter' === event.type);
    },
    /**
     * Add to order click event.
     *
     * This event accounts for button clicks or card clicks.
     * @param event
     * @param $element
     */
    click_add_to_order(event, $element) {
      // If undefined, it means it was triggered by a click
      // event and not the `orderable-add-to-cart` trigger.
      $element = typeof $element !== 'undefined' ? $element : $(this);

      // The button is either the clicked element, or the
      // add to order button within the clicked element.
      const $button = $element.is('button') ? $element : $element.find('.' + orderable_products.vars.classes.add_to_order_button),
        action = $button.data('orderable-trigger'),
        product_id = $button.data('orderable-product-id'),
        variation_id = $button.data('orderable-variation-id'),
        attributes = $button.data('orderable-variation-attributes'),
        args = {
          action
        };
      if ($button.hasClass(orderable_products.vars.classes.button_loading) || $button.hasClass(orderable_products.vars.classes.out_of_stock)) {
        return;
      }
      $button.addClass(orderable_products.vars.classes.button_loading);
      switch (action) {
        case 'add-to-cart':
          orderable_products.add_to_cart({
            product_id,
            variation_id,
            attributes,
            thisbutton: $element
          }, function (response) {
            args.show_cart = true;
            args.response = response;
            $(document.body).trigger('orderable-drawer.open', args);
            $button.removeClass(orderable_products.vars.classes.button_loading);
            const $addToCartButtonOutsideDrawer = $('.orderable-product .orderable-product__actions-button button.orderable-product__add-to-order[data-orderable-product-id=' + product_id + ']');
            if ($addToCartButtonOutsideDrawer.siblings('.orderable-quantity-roller').length) {
              $addToCartButtonOutsideDrawer.addClass('orderable-button--product-in-the-cart');
            }
          });
          break;
        case 'add-to-cart-without-side-drawer':
          orderable_products.add_to_cart({
            product_id,
            variation_id,
            attributes
          }, function (response) {
            args.response = response;
            $button.addClass('orderable-button--product-in-the-cart');
            $button.removeClass(orderable_products.vars.classes.button_loading);
          });
          break;
        case 'product-options':
          orderable_products.get_product_options({
            product_id,
            focus: $button.data('orderable-focus')
          }, function (response) {
            args.html = response.html;
            $(document.body).trigger('orderable-drawer.open', args);
            $button.removeClass(orderable_products.vars.classes.button_loading);
          });
          break;
        default:
          break;
      }
    },
    /**
     * Show the cart.
     */
    show_cart() {
      $(document.body).trigger('orderable-drawer.open', {
        show_cart: true
      });
    },
    /**
     * View product.
     *
     * @param event
     * @param $element
     */
    view_product(event, $element) {
      const product_id = $element.data('orderable-product-id'),
        args = {
          action: 'product-options'
        };
      orderable_products.get_product_options({
        product_id,
        focus: $element.data('orderable-focus')
      }, function (response) {
        args.html = response.html;
        $(document.body).trigger('orderable-drawer.open', args);
      });
    },
    /**
     * Ajax add to cart.
     * @param args
     * @param callback
     */
    add_to_cart(args, callback) {
      if (typeof args.product_id === 'undefined') {
        return;
      }
      let data = {
        action: 'orderable_add_to_cart',
        product_id: args.product_id,
        variation_id: args.variation_id || false,
        attributes: args.attributes || false
      };

      // Prepare addons data.
      if ($('.orderable-product-fields-group').length) {
        let inputs = jQuery('.orderable-product-fields-group :input').serializeArray();
        inputs = orderable_products.add_unchecked_checkbox_fields(inputs);
        const addons_data = orderable_products.convert_to_flat_object(inputs);
        if (!jQuery.isEmptyObject(addons_data)) {
          data = Object.assign(data, addons_data); // Merge objects.
        }
      }
      jQuery.post(orderable_vars.ajax_url, data, function (response) {
        if (!response) {
          return;
        }

        // Trigger event so themes can refresh other areas.
        $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, args.thisbutton]);
        if (typeof callback === 'function') {
          callback(response);
        }
      });
    },
    /**
     * Edit cart item.
     *
     * @param event
     * @param $element
     */
    edit_cart_item(event, $element) {
      const cart_item_key = $element.data('orderable-cart-item-key');
      $element.addClass(orderable_products.vars.classes.button_loading);
      orderable_products.get_cart_item_options({
        cart_item_key
      }, function (response) {
        const args = {
          html: response.html,
          action: 'update-cart-item'
        };
        $(document.body).trigger('orderable-drawer.open', args);
        $element.removeClass(orderable_products.vars.classes.button_loading);
      });
    },
    /**
     * Update cart item.
     *
     * @param event
     * @param $element
     */
    update_cart_item(event, $element) {
      const cart_item_key = $element.data('orderable-cart-item-key');
      const product_id = $element.data('orderable-product-id');
      const variation_id = $element.data('orderable-variation-id');
      const attributes = $element.data('orderable-variation-attributes');
      $element.addClass(orderable_products.vars.classes.button_loading);
      orderable_products.update_cart_item_options({
        cart_item_key,
        product_id,
        variation_id,
        attributes
      }, function (response) {
        const args = {
          show_cart: true,
          response
        };
        $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $element]);
        $(document.body).trigger('orderable-drawer.open', args);
        $element.removeClass(orderable_products.vars.classes.button_loading);
      });
    },
    /**
     * Convert [{name:x, value:y }] to {x:y} format.
     * @param inputs
     */
    convert_to_flat_object(inputs) {
      const data = {};
      inputs.forEach(function (input) {
        const is_array = '[]' === input.name.substr(-2) || Array.isArray(input.name);
        // If last 2 chars are '[]', remove them.
        const key = is_array ? input.name.substr(0, input.name.length - 2) : input.name;
        if (is_array) {
          data[key] = typeof data[key] === 'undefined' ? [] : data[key];
          data[key].push(input.value);
        } else {
          data[key] = input.value;
        }
      });
      return data;
    },
    /**
     * Get variable product options.
     *
     * @param args
     * @param callback
     */
    get_product_options(args, callback) {
      if (typeof args.product_id === 'undefined') {
        return;
      }
      args.action = 'orderable_get_product_options';
      jQuery.post(orderable_vars.ajax_url, args, function (response) {
        if (!response.success) {
          return;
        }
        if (typeof callback === 'function') {
          callback(response.data);
        }
      });
    },
    /**
     * Get variable product options.
     *
     * @param args
     * @param callback
     */
    get_cart_item_options(args, callback) {
      if (typeof args.cart_item_key === 'undefined') {
        return;
      }
      args.action = 'orderable_get_cart_item_options';
      jQuery.post(orderable_vars.ajax_url, args, function (response) {
        if (!response.success) {
          return;
        }
        if (typeof callback === 'function') {
          callback(response.data);
        }
      });
    },
    /**
     * Update variable product options.
     *
     * @param args
     * @param callback
     */
    update_cart_item_options(args, callback) {
      if (typeof args.cart_item_key === 'undefined') {
        return;
      }
      args.action = 'orderable_update_cart_item_options';

      // Prepare addons data.
      if ($('.orderable-product-fields-group').length) {
        let inputs = jQuery('.orderable-product-fields-group :input').serializeArray();
        inputs = orderable_products.add_unchecked_checkbox_fields(inputs);
        const addons_data = orderable_products.convert_to_flat_object(inputs);
        if (!jQuery.isEmptyObject(addons_data)) {
          args = Object.assign(args, addons_data); // Merge objects.
        }
      }
      jQuery.post(orderable_vars.ajax_url, args, function (response) {
        if (!response) {
          return;
        }
        if (typeof callback === 'function') {
          callback(response);
        }
      });
    },
    /**
     * Init drawer product options.
     *
     * @param event
     * @param args
     */
    init_product_options(event, args) {
      if (typeof args.action === 'undefined' || 'product-options' !== args.action && 'update-cart-item' !== args.action) {
        return;
      }
      const selectors = '.orderable-drawer .orderable-product__options input, .orderable-drawer .orderable-product__options select, .orderable-product__options textarea';
      const $options = $(selectors);
      orderable_products.vars.parent_price = $('.orderable-drawer .orderable-product__actions-price').html();
      orderable_products.product_options_change($options);
      orderable_products.update_button_state();
      const debounced_update_button_state = orderable_products.debounce(orderable_products.update_button_state, 500);
      const debounced_product_options_change = orderable_products.debounce(orderable_products.product_options_change, 500);
      $(document).on('change keyup', selectors, function () {
        debounced_product_options_change($options);
        debounced_update_button_state();
      });
    },
    /**
     * On product options change.
     *
     * @param $options
     */
    product_options_change($options) {
      const $add_to_order_button = $('.orderable-drawer .orderable-product__add-to-order, .orderable-drawer .orderable-product__update-cart-item'),
        options_set = orderable_products.check_options($options),
        product_type = $add_to_order_button.data('orderable-product-type');
      if ('product-options' === $add_to_order_button.attr('data-orderable-trigger')) {
        $add_to_order_button.attr('data-orderable-trigger', 'add-to-cart');
      }
      $('.' + orderable_products.vars.classes.product_messages).html('');
      if (!orderable_products.vars.variable_product_types.includes(product_type)) {
        return;
      }
      if (!options_set) {
        orderable_products.clear_variation($add_to_order_button);
        return;
      }
      const variation = orderable_products.check_variation($options);
      orderable_products.set_variation($add_to_order_button, variation);
    },
    /**
     * Check if all product options are set.
     *
     * @param  $options
     * @return {boolean}
     */
    check_options($options) {
      if ($options.length <= 0) {
        return false;
      }
      let all_set = true;
      $options.each(function (index, option) {
        // Only check attribute fields.
        if (!$(option).hasClass('orderable-input--validate')) {
          return;
        }
        if ('' === $(option).val()) {
          $(option).addClass(orderable_products.vars.classes.invalid_field);
          all_set = false;
        } else {
          $(option).removeClass(orderable_products.vars.classes.invalid_field);
        }
      });
      return all_set;
    },
    /**
     * Check if variation has been selected.
     * @param $options
     */
    check_variation($options) {
      const $product = $options.closest('.orderable-drawer');
      let variations = $product.find('.orderable-product__variations').text();
      variations = variations ? JSON.parse(variations) : '';
      const selected_options = orderable_products.serialize_object($options),
        matching_variations = orderable_products.find_matching_variations(variations, selected_options);
      if (orderable_products.is_empty(matching_variations)) {
        return false;
      }
      const variation = matching_variations.shift();
      variation.attributes = selected_options;
      variation.attributes_json = JSON.stringify(selected_options);
      return typeof variation !== 'undefined' ? variation : false;
    },
    /**
     * Set variation for add to cart button.
     * @param $button
     * @param variation
     */
    set_variation($button, variation) {
      let variation_id = variation.variation_id || '',
        attributes = variation.attributes_json || '',
        price = variation.price_html || orderable_products.vars.parent_price,
        message = '';
      if (variation && '' !== variation.availability_html) {
        message = variation.availability_html;
      }
      if (variation && !variation.is_in_stock) {
        message = '<p>' + orderable_vars.i18n.out_of_stock + '</p>';
      }
      if (variation && !variation.is_purchasable) {
        message = '<p>' + orderable_vars.i18n.unavailable + '</p>';
      }
      if (false === variation) {
        message = '<p>' + orderable_vars.i18n.no_exist + '</p>';
      }
      if (variation && (!variation.is_purchasable || !variation.is_in_stock)) {
        variation_id = '';
        attributes = '';
      }
      if ('' !== message) {
        $('.' + orderable_products.vars.classes.product_messages).html(message);
      }
      $button.data('orderable-variation-id', variation_id);
      $button.data('orderable-variation-attributes', attributes);
      $('.orderable-drawer .orderable-product__actions-price').html(price);
      $button.trigger('orderable_variation_set', {
        variation,
        variation_id,
        attributes,
        price
      });
    },
    /**
     * Clear variation and disable add to order.
     *
     * @param $button
     */
    clear_variation($button) {
      orderable_products.set_variation($button, '');
      if (orderable_products.vars.parent_price) {
        $('.orderable-drawer .orderable-product__actions-price').html(orderable_products.vars.parent_price);
      }
    },
    /**
     * Find matching variations for attributes.
     * @param variations
     * @param attributes
     */
    find_matching_variations(variations, attributes) {
      const matching = [];
      for (let i = 0; i < variations.length; i++) {
        const variation = variations[i];
        if (orderable_products.is_matching_variation(variation.attributes, attributes)) {
          matching.push(variation);
        }
      }
      return matching;
    },
    /**
     * See if attributes match.
     * @param  variation_attributes
     * @param  attributes
     * @return {boolean}
     */
    is_matching_variation(variation_attributes, attributes) {
      let match = true;
      for (const attr_name in variation_attributes) {
        if (variation_attributes.hasOwnProperty(attr_name)) {
          const val1 = variation_attributes[attr_name];
          const val2 = attributes[attr_name];
          if (val1 !== undefined && val2 !== undefined && val1.length !== 0 && val2.length !== 0 && val1 !== val2) {
            match = false;
          }
        }
      }
      return match;
    },
    /**
     * Is value empty?
     *
     * @param  value
     * @return {boolean}
     */
    is_empty(value) {
      return typeof value === 'undefined' || false === value || value.length <= 0 || !value;
    },
    /**
     * Serialize into a key/value object.
     *
     * @param  $elements
     * @return {{}}
     */
    serialize_object: function objectifyForm($elements) {
      const serialized = $elements.serializeArray(),
        return_object = {};
      for (let i = 0; i < serialized.length; i++) {
        return_object[serialized[i].name] = serialized[i].value;
      }
      return return_object;
    },
    /**
     * Disable/Enable the 'Add to cart' button based on the presence of orderable-field--invalid class.
     */
    update_button_state() {
      // Add delay to ensure invalid class has been assigned to inputs.
      setTimeout(function () {
        let $button = $('.orderable-drawer .orderable-product__add-to-order, .orderable-drawer .orderable-product__update-cart-item'),
          invalid_fields_count = $('.orderable-drawer__html .' + orderable_products.vars.classes.invalid_field).length,
          product_type = $button.data('orderable-product-type'),
          has_variation_id = true;
        if ('variable' === product_type) {
          has_variation_id = '' !== $button.data('orderable-variation-id');
        }
        $button.prop('disabled', invalid_fields_count || !has_variation_id);
      }, 50);
    },
    /**
     * Debounce function.
     *
     * @param func      Function to debounce.
     * @param wait      Time to wait in milliseconds.
     * @param immediate Trigger the function on the leading edge, instead of the trailing.
     *
     * @return
     */
    debounce(func, wait, immediate) {
      let timeout;
      return function () {
        const context = this,
          args = arguments;
        const later = function () {
          timeout = null;
          if (!immediate) {
            func.apply(context, args);
          }
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) {
          func.apply(context, args);
        }
      };
    },
    /**
     * Remove the quantity roller fragments
     *
     * @param {Event}  e
     * @param {Object} fragments
     * @return void
     */
    remove_fragments(e, fragments) {
      if (!fragments || 'undefined' === typeof wc_cart_fragments_params || !wc_cart_fragments_params.fragment_name) {
        return;
      }
      const regex_quantity_roller = /\.orderable-product\[data-orderable-product-id='[1-9][0-9]*'\] \.orderable-product__actions-button \.orderable-quantity-roller/;
      const regex_product_in_the_cart_counter = /\.orderable-product\[data-orderable-product-id='[1-9][0-9]*'\] \.orderable-product__actions-button \.orderable-product__actions-counter/;
      for (const key in fragments) {
        if (!regex_quantity_roller.test(key) && !regex_product_in_the_cart_counter.test(key)) {
          continue;
        }
        fragments[key] = undefined;
      }
      sessionStorage.setItem(wc_cart_fragments_params.fragment_name, JSON.stringify(fragments));
    },
    /**
     * Remove animation.
     */
    remove_animation() {
      setTimeout(function () {
        $('.orderable-product__actions-counter').css('animation', '');
      }, 1000);
    },
    /**
     * Hide quantity roller element and show the Add to Cart button.
     *
     * @param {Event}   e
     * @param {Object}  fragments
     * @param {string}  cart_hash
     * @param {Element} $button
     * @return
     */
    hide_quantity_roller(e, fragments, cart_hash, $button) {
      const product_id = $button.attr('data-product_id') || $button.attr('data-orderable-product-id');
      if (!product_id) {
        return;
      }
      const $actions_button = $('.orderable-product[data-orderable-product-id=' + product_id + '] .orderable-product__actions-button');
      if (!$actions_button.length) {
        return;
      }
      const $quantity_roller = $actions_button.find('.orderable-quantity-roller');
      if ($quantity_roller.length) {
        $actions_button.find('button.orderable-product__add-to-order[data-orderable-trigger]').removeClass('orderable-button--product-in-the-cart');
        $quantity_roller.addClass('orderable-button--hide');
      }
    },
    /**
     * Add unchecked checkboxs to the list of inputs
     * sent to the request to add/update an item
     *
     * @param {Object} inputs
     * @return {Object}
     */
    add_unchecked_checkbox_fields(inputs) {
      jQuery('.orderable-product-fields-group :input[type="checkbox"]:not(:checked)').each(function (index, element) {
        inputs.push({
          name: element.getAttribute('name'),
          value: ''
        });
      });
      return inputs;
    },
    /**
     * Shake the floating cart button.
     *
     * @return void
     */
    shake_floating_cart() {
      $('.orderable-floating-cart__button').css('animation', 'wobble-hor-bottom .8s both');
    },
    /**
     * Remove the view cart link.
     *
     * @param event
     * @param $button
     */
    remove_view_cart_link(event, $button) {
      if (!$button?.hasClass('orderable-product__add-to-order')) {
        return;
      }
      $button?.siblings('.added_to_cart.wc-forward').remove();
    }
  };
  $(document).ready(orderable_products.on_ready);
})(jQuery, document);
(function ($, document) {
  'use strict';

  var orderable_scrollbar = {
    /**
     * On doc ready.
     */
    on_ready() {
      orderable_scrollbar.cache();
      orderable_scrollbar.watch();
    },
    /**
     * Cache.
     */
    cache() {
      orderable_scrollbar.vars = {
        top: {}
      };
      orderable_scrollbar.elements = {};
    },
    /**
     * Watch.
     */
    watch() {
      $(document.body).on('orderable-drawer.opened', orderable_scrollbar.trigger);
      $(document.body).on('orderable-tabs.changed', orderable_scrollbar.trigger);
      $(document.body).on('orderable-accordion.toggled', orderable_scrollbar.trigger);
      $(document.body).on('wc_fragments_loaded', orderable_scrollbar.trigger);
    },
    /**
     * Init or retrigger scrollbars.
     */
    trigger() {
      $('.orderable-sb-container').each(function (index, element) {
        const $element = $(element),
          scroll_id = $element.data('orderable-scroll-id');
        if (!orderable_scrollbar.has_scrollbar($element)) {
          $element.scrollBox({
            containerClass: 'orderable-sb-container',
            containerNoScrollClass: 'orderable-sb-container-noscroll',
            contentClass: 'orderable-sb-content',
            scrollbarContainerClass: 'orderable-sb-scrollbar-container',
            scrollBarClass: 'orderable-sb-scrollbar'
          });
          const $content = $element.find('.orderable-sb-content');
          if ($content.length > 0) {
            $content.on('scroll.scrollBox', orderable_scrollbar.log_top_position);

            // Set scroll position.
            if (typeof orderable_scrollbar.vars.top[scroll_id] !== 'undefined') {
              $content.scrollTop(orderable_scrollbar.vars.top[scroll_id]);
            }
          }
          $(document.body).trigger('orderable-scrollbar.created', {
            element: $element,
            content: $content
          });
        }
      });
      $(window).trigger('resize.scrollBox');
    },
    /**
     * Has scrollbar already?
     *
     * @param  $element
     * @return {boolean}
     */
    has_scrollbar($element) {
      return $element.find('.orderable-sb-content').length > 0;
    },
    /**
     * Set scrolltop position.
     *
     * @param e
     */
    log_top_position(e) {
      const $element = $(e.currentTarget),
        $container = $element.closest('.orderable-sb-container'),
        scroll_id = $container.data('orderable-scroll-id');
      orderable_scrollbar.vars.top[scroll_id] = $(e.currentTarget).scrollTop();
    }
  };
  $(document).ready(orderable_scrollbar.on_ready);
})(jQuery, document);
(function ($, document) {
  'use strict';

  var orderable_tabs = {
    /**
     * On doc ready.
     */
    on_ready() {
      orderable_tabs.cache();
      orderable_tabs.watch();
      orderable_tabs.toggle_scroll();
    },
    /**
     * On resize.
     */
    on_resize() {
      if (typeof orderable_tabs.vars === 'undefined') {
        orderable_tabs.cache();
      }
      orderable_tabs.toggle_scroll();
    },
    /**
     * Cache.
     */
    cache() {
      orderable_tabs.vars = {
        classes: {
          tabs: 'orderable-tabs',
          tabs_list: 'orderable-tabs__list',
          tab_items: 'orderable-tabs__item',
          tab_item_active: 'orderable-tabs__item--active',
          tab_links: 'orderable-tabs__link',
          tab_arrow_right: 'orderable-tabs__arrow-right',
          tab_arrow_left: 'orderable-tabs__arrow-left'
        },
        dragging: false
      };
      orderable_tabs.elements = {};
    },
    /**
     * Watch.
     */
    watch() {
      $('body').on('touchstart', function () {
        orderable_tabs.vars.dragging = false;
      }).on('touchmove', function () {
        orderable_tabs.vars.dragging = true;
      });
      $(document.body).on('click mouseup touchend', '.' + orderable_tabs.vars.classes.tab_links, function (e) {
        if (orderable_tabs.vars.dragging) {
          return;
        }
        e.preventDefault();
        const $link = $(this),
          section_id = $link.attr('href'),
          $tab = $link.closest('.' + orderable_tabs.vars.classes.tab_items),
          $tabs = $link.closest('.' + orderable_tabs.vars.classes.tabs),
          $tabs_list = $tabs.find('.' + orderable_tabs.vars.classes.tabs_list),
          $tab_items = $tabs.find('.' + orderable_tabs.vars.classes.tab_items),
          tabs_args = $tabs.data('orderable-tabs'),
          $wrapper = $link.closest(tabs_args.wrapper),
          $sections = $wrapper.find(tabs_args.sections),
          $section = $wrapper.find(section_id);
        $sections.hide();
        $section.show();
        $tab_items.removeClass(orderable_tabs.vars.classes.tab_item_active);
        $tab.addClass(orderable_tabs.vars.classes.tab_item_active);
        $tabs_list.animate({
          scrollLeft: $tabs_list.scrollLeft() + $tab.position().left
        });
        $(document.body).trigger('orderable-tabs.changed', {
          tab: $tab
        });
      });

      /**
       * Watch scroll position of tabs.
       */
      $('.' + orderable_tabs.vars.classes.tabs_list).on('scroll', function (e) {
        const $list = $(this),
          $wrapper = $list.parent('.' + orderable_tabs.vars.classes.tabs),
          $arrow_right = $list.siblings('.' + orderable_tabs.vars.classes.tab_arrow_right),
          $arrow_left = $list.siblings('.' + orderable_tabs.vars.classes.tab_arrow_left);
        if ($list[0].scrollWidth <= $wrapper.width() + $list.scrollLeft()) {
          $arrow_right.fadeOut();
        } else {
          $arrow_right.fadeIn();
        }
        if (0 >= $list.scrollLeft() - $arrow_left.width()) {
          $arrow_left.fadeOut();
        } else {
          $arrow_left.fadeIn();
        }
      });

      /**
       * Stop animated scroll if user manually scrolls.
       */
      $('.' + orderable_tabs.vars.classes.tabs_list).on('wheel DOMMouseScroll mousewheel touchmove', function () {
        $(this).stop();
      });

      /**
       * Click tab arrow right.
       */
      $(document).on('click', '.' + orderable_tabs.vars.classes.tab_arrow_right, function (e) {
        e.preventDefault();
        const $arrow = $(this),
          $wrapper = $arrow.parent(),
          $list = $wrapper.find('.' + orderable_tabs.vars.classes.tabs_list);
        $list.animate({
          scrollLeft: $list.scrollLeft() + $wrapper.width() * 0.5
        });
      });

      /**
       * Click tab arrow left.
       */
      $(document).on('click', '.' + orderable_tabs.vars.classes.tab_arrow_left, function (e) {
        e.preventDefault();
        const $arrow = $(this),
          $wrapper = $arrow.parent(),
          $list = $wrapper.find('.' + orderable_tabs.vars.classes.tabs_list);
        $list.animate({
          scrollLeft: $list.scrollLeft() - $wrapper.width() * 0.5
        });
      });
    },
    /**
     * Toggle scroll arrow.
     */
    toggle_scroll() {
      $('.' + orderable_tabs.vars.classes.tabs).each(function (index, wrapper) {
        const $tabs = $(this),
          tabs_args = $tabs.data('orderable-tabs'),
          $wrapper = $tabs.closest(tabs_args.wrapper),
          $list = $wrapper.find('.' + orderable_tabs.vars.classes.tabs_list),
          $arrow_right = $wrapper.find('.' + orderable_tabs.vars.classes.tab_arrow_right),
          wrapper_width = $wrapper.outerWidth(),
          list_width = $list[0].scrollWidth;
        if (list_width > wrapper_width) {
          $arrow_right.show();
        } else {
          $arrow_right.hide();
        }
      });
    }
  };
  $(document).ready(orderable_tabs.on_ready);
  $(window).on('resize', orderable_tabs.on_resize);
})(jQuery, document);
let orderable_timings = {}; // Make this global so pro modules can access it.

(function ($, document) {
  'use strict';

  orderable_timings = {
    /**
     * On doc ready.
     */
    on_ready() {
      orderable_timings.watch();
    },
    /**
     * Restore current timings.
     */
    restore() {
      const timings = orderable_timings.get_timings();
      if (!timings || !timings.date) {
        return;
      }
      const dateSelect = $('.orderable-order-timings__date');
      if (dateSelect.find('option[value="' + timings.date + '"]').length > 0) {
        dateSelect.val(timings.date);
        dateSelect.change();
      }
      if (!timings.time) {
        return;
      }
      const timeSelect = $('.orderable-order-timings__time');
      if (timeSelect.find('option[value="' + timings.time + '"]').length > 0) {
        timeSelect.val(timings.time);
        timeSelect.change();
      }
    },
    /**
     * Watch for trigger events.
     */
    watch() {
      $(document.body).on('wc_fragments_refreshed', function () {
        orderable_timings.restore();
      });
      $(document.body).on('updated_checkout', function () {
        orderable_timings.restore();
      });
      $(document.body).on('change', '.orderable-order-timings__date', function (event) {
        const $date_field = $(this),
          $selected = $date_field.find('option:selected'),
          slots = $selected.data('orderable-slots'),
          $time_field_wrap = $('.orderable-order-timings--time'),
          $time_field = $('.orderable-order-timings__time'),
          $first_option = $time_field.find('option').first(),
          $asap_option = $time_field.find('option[value="asap"]').first();
        const timings = orderable_timings.get_timings();
        timings.date = $('.orderable-order-timings__date').val();
        window.localStorage.setItem('orderable_timings', JSON.stringify(timings));
        $time_field.html($first_option);
        if ($asap_option) {
          $time_field.append($asap_option);
        }
        if (!slots) {
          $time_field.prop('disabled', true);
          $time_field_wrap.hide();
          return;
        }
        if ('all-day' === slots[0].value) {
          $time_field_wrap.hide();
          $time_field.prop('disabled', true);
        } else {
          $time_field.prop('disabled', false);
          $time_field_wrap.show();
          $.each(slots, function (index, slot) {
            $time_field.append($('<option />').attr('value', slot.value).attr('data-orderable-time-slot-id', slot?.setting_row?.time_slot_id).text(slot.formatted));
          });
        }
      });
      $(document.body).on('change', '.orderable-order-timings__time', function (event) {
        const timings = orderable_timings.get_timings();
        timings.time = $('.orderable-order-timings__time').val();
        window.localStorage.setItem('orderable_timings', JSON.stringify(timings));
        $(this).siblings('input[name="orderable_order_time_slot_id"]').val($(this).find(':selected').attr('data-orderable-time-slot-id'));
      });
    },
    get_timings() {
      return JSON.parse(window.localStorage.getItem('orderable_timings')) || {};
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
      orderable_triggers.watch();
    },
    /**
     * Watch for trigger events.
     */
    watch() {
      if ('undefined' !== typeof window.orderable_triggers_admin) {
        return;
      }
      $(document.body).on('click', '[data-orderable-trigger]', orderable_triggers.trigger);
    },
    /**
     * Fire trigger.
     * @param e
     */
    trigger(e) {
      // Prevent even bubbling up.
      e.stopImmediatePropagation();
      const $trigger_element = $(this),
        trigger = $trigger_element.data('orderable-trigger');
      if ($trigger_element.is('button') || $trigger_element.is('a')) {
        e.preventDefault();
      }
      $(document.body).trigger('orderable-' + trigger, [$trigger_element]);
    }
  };
  $(document).ready(orderable_triggers.on_ready);
})(jQuery, document);
/**
 * jQiery scrollBar Plugin
 * @author Falk Müller (www-falk-m.de)
 * Thankts to https://codepen.io/IliaSky/pen/obowmv
 */
;
(function ($, window, document) {
  "use strict";

  var pluginName = "scrollBox",
    defaults = {
      containerClass: "sb-container",
      containerNoScrollClass: "sb-container-noscroll",
      contentClass: "sb-content",
      scrollbarContainerClass: "sb-scrollbar-container",
      scrollBarClass: "sb-scrollbar"
    };

  // plugin constructor
  function Plugin(element, options) {
    this.element = element;
    this.settings = $.extend({}, defaults, options);
    this._defaults = defaults;
    this._name = pluginName;
    this.init();
  }

  // Avoid Plugin.prototype conflicts
  $.extend(Plugin.prototype, {
    init: function () {
      this.addScrollbar();
      this.addEvents();
      this.onResize();
    },
    addScrollbar: function () {
      $(this.element).addClass(this.settings.containerClass);
      this.wrapper = $("<div class='" + this.settings.contentClass + "' />");
      this.wrapper.append($(this.element).contents());
      $(this.element).append(this.wrapper);
      this.scollbarContainer = $("<div class='" + this.settings.scrollbarContainerClass + "' />");
      this.scrollBar = $("<div class='" + this.settings.scrollBarClass + "' />");
      this.scollbarContainer.append(this.scrollBar);
      $(this.element).prepend(this.scollbarContainer);
    },
    addEvents: function () {
      this.wrapper.on("scroll." + pluginName, $.proxy(this.onScroll, this));
      $(window).on("resize." + pluginName, $.proxy(this.onResize, this));
      this.scrollBar.on('mousedown.' + pluginName, $.proxy(this.onMousedown, this));
      this.scrollBar.on('touchstart.' + pluginName, $.proxy(this.onTouchstart, this));
    },
    onTouchstart: function (ev) {
      var me = this;
      ev.preventDefault();
      var y = me.scrollBar[0].offsetTop;
      var onMove = function (end) {
        var delta = end.touches[0].pageY - ev.touches[0].pageY;
        me.scrollBar[0].style.top = Math.min(me.scollbarContainer[0].clientHeight - me.scrollBar[0].clientHeight, Math.max(0, y + delta)) + 'px';
        me.wrapper[0].scrollTop = me.wrapper[0].scrollHeight * me.scrollBar[0].offsetTop / me.scollbarContainer[0].clientHeight;
      };
      $(document).on("touchmove." + pluginName, onMove);
      $(document).on("touchend." + pluginName, function () {
        $(document).off("touchmove." + pluginName);
        $(document).off("touchend." + pluginName);
      });
    },
    onMousedown: function (ev) {
      var me = this;
      ev.preventDefault();
      var y = me.scrollBar[0].offsetTop;
      var onMove = function (end) {
        var delta = end.pageY - ev.pageY;
        me.scrollBar[0].style.top = Math.min(me.scollbarContainer[0].clientHeight - me.scrollBar[0].clientHeight, Math.max(0, y + delta)) + 'px';
        me.wrapper[0].scrollTop = me.wrapper[0].scrollHeight * me.scrollBar[0].offsetTop / me.scollbarContainer[0].clientHeight;
      };
      $(document).on("mousemove." + pluginName, onMove);
      $(document).on("mouseup." + pluginName, function () {
        $(document).off("mousemove." + pluginName);
        $(document).off("mouseup." + pluginName);
      });
    },
    onResize: function () {
      this.wrapper.css("max-height", $(this.element).height());
      var wrapper_client_height = this.wrapper[0].clientHeight;
      this.scrollBar.css("height", this.scollbarContainer[0].clientHeight * wrapper_client_height / this.wrapper[0].scrollHeight + "px");
      if (this.scollbarContainer[0].clientHeight <= this.scrollBar[0].clientHeight) {
        $(this.element).addClass(this.settings.containerNoScrollClass);
      } else {
        $(this.element).removeClass(this.settings.containerNoScrollClass);
      }
      this.onScroll();
    },
    onScroll: function () {
      this.scrollBar.css("top", Math.min(this.scollbarContainer[0].clientHeight - this.scrollBar[0].clientHeight, this.scollbarContainer[0].clientHeight * this.wrapper[0].scrollTop / this.wrapper[0].scrollHeight) + "px");
    }
  });

  // A really lightweight plugin wrapper around the constructor,
  // preventing against multiple instantiations
  $.fn[pluginName] = function (options) {
    return this.each(function () {
      if (!$.data(this, "plugin_" + pluginName)) {
        $.data(this, "plugin_" + pluginName, new Plugin(this, options));
      }
    });
  };
})(jQuery, window, document);
/*!
 * swiped-events.js - v1.1.6
 * Pure JavaScript swipe events
 * https://github.com/john-doherty/swiped-events
 * @inspiration https://stackoverflow.com/questions/16348031/disable-scrolling-when-touch-moving-certain-element
 * @author John Doherty <www.johndoherty.info>
 * @license MIT
 */
!function (t, e) {
  "use strict";

  "function" != typeof t.CustomEvent && (t.CustomEvent = function (t, n) {
    n = n || {
      bubbles: !1,
      cancelable: !1,
      detail: void 0
    };
    var a = e.createEvent("CustomEvent");
    return a.initCustomEvent(t, n.bubbles, n.cancelable, n.detail), a;
  }, t.CustomEvent.prototype = t.Event.prototype), e.addEventListener("touchstart", function (t) {
    if ("true" === t.target.getAttribute("data-swipe-ignore")) return;
    s = t.target, r = Date.now(), n = t.touches[0].clientX, a = t.touches[0].clientY, u = 0, i = 0;
  }, !1), e.addEventListener("touchmove", function (t) {
    if (!n || !a) return;
    var e = t.touches[0].clientX,
      r = t.touches[0].clientY;
    u = n - e, i = a - r;
  }, !1), e.addEventListener("touchend", function (t) {
    if (s !== t.target) return;
    var e = parseInt(l(s, "data-swipe-threshold", "20"), 10),
      o = parseInt(l(s, "data-swipe-timeout", "500"), 10),
      c = Date.now() - r,
      d = "",
      p = t.changedTouches || t.touches || [];
    Math.abs(u) > Math.abs(i) ? Math.abs(u) > e && c < o && (d = u > 0 ? "swiped-left" : "swiped-right") : Math.abs(i) > e && c < o && (d = i > 0 ? "swiped-up" : "swiped-down");
    if ("" !== d) {
      var b = {
        dir: d.replace(/swiped-/, ""),
        touchType: (p[0] || {}).touchType || "direct",
        xStart: parseInt(n, 10),
        xEnd: parseInt((p[0] || {}).clientX || -1, 10),
        yStart: parseInt(a, 10),
        yEnd: parseInt((p[0] || {}).clientY || -1, 10)
      };
      s.dispatchEvent(new CustomEvent("swiped", {
        bubbles: !0,
        cancelable: !0,
        detail: b
      })), s.dispatchEvent(new CustomEvent(d, {
        bubbles: !0,
        cancelable: !0,
        detail: b
      }));
    }
    n = null, a = null, r = null;
  }, !1);
  var n = null,
    a = null,
    u = null,
    i = null,
    r = null,
    s = null;
  function l(t, n, a) {
    for (; t && t !== e.documentElement;) {
      var u = t.getAttribute(n);
      if (u) return u;
      t = t.parentNode;
    }
    return a;
  }
}(window, document);