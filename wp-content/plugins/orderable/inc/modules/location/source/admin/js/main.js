( function ( $ ) {
	var orderable_multi_location = {
		on_ready() {
			$( '.orderable-toggle-field' ).on(
				'click',
				orderable_multi_location.handle_toggle_field_on_click
			);

			$( '.orderable-override-open-hours-toggle-field' ).on(
				'click',
				orderable_multi_location.handle_override_open_hours_on_click
			);

			$( '.orderable-delivery-toggle-field' ).on(
				'click',
				orderable_multi_location.handle_enable_service_delivery_on_click
			);
			$( '.orderable-pickup-toggle-field' ).on(
				'click',
				orderable_multi_location.handle_enable_service_pickup_on_click
			);

			$( '.orderable-admin-button--pickup' ).on( 'click', function () {
				if (
					$(
						'#orderable_location_service_hours_pickup_same_as_delivery'
					).prop( 'checked' )
				) {
					$( '.orderable-element--pickup' ).addClass(
						'orderable-element--disabled'
					);
				} else {
					$( '.orderable-element--pickup' ).removeClass(
						'orderable-element--disabled'
					);
				}
			} );

			const datepicker_args = $( '.datepicker' ).data( 'datepicker' );
			$( '.datepicker' ).datepicker( datepicker_args );

			$( document.body ).on(
				'orderable-new-row',
				orderable_multi_location.on_new_holiday_row
			);
		},

		handle_toggle_field_on_click() {
			$( this ).toggleClass( [
				'woocommerce-input-toggle--disabled',
				'woocommerce-input-toggle--enabled',
			] );

			const value = $( this ).hasClass(
				'woocommerce-input-toggle--enabled'
			);

			$( this )
				.siblings( '.orderable-toggle-field__input' )
				.val( value ? 'yes' : 'no' );
		},

		handle_override_open_hours_on_click() {
			$( this )
				.siblings( '.orderable-open-hours-settings' )
				.toggleClass( 'orderable-store-open-hours--hide' );
			$( '.orderable-store-open-hours__open-hours' ).toggleClass(
				'orderable-store-open-hours--hide'
			);
		},

		handle_enable_service_delivery_on_click() {
			const delivery_is_enabled = $( this ).hasClass(
				'woocommerce-input-toggle--enabled'
			);
			pickup_is_enabled =
				$( '[name=orderable_location_store_services_pickup]' ).val() ===
				'yes';

			if ( delivery_is_enabled ) {
				$( '.orderable-admin-button--delivery' ).removeClass(
					'orderable-ui-hide'
				);
				$( '.orderable-notice--select-service' ).addClass(
					'orderable-ui-hide'
				);
			} else {
				$( '.orderable-admin-button--delivery' )
					.addClass( 'orderable-ui-hide' )
					.removeClass( 'orderable-trigger-element--active' );
			}

			if ( pickup_is_enabled && delivery_is_enabled ) {
				$(
					'#orderable_location_service_hours_pickup_same_as_delivery_label'
				).removeClass( 'orderable-ui-hide' );

				const has_pickup_days_selected = $(
					'.orderable-toggle-wrapper--pickup'
				)
					.find( '.orderable-select--days' )
					.first()
					.val().length;

				if ( ! has_pickup_days_selected ) {
					$(
						'#orderable_location_service_hours_pickup_same_as_delivery'
					)
						.prop( 'checked', true )
						.change();
				}

				return;
			}

			if ( delivery_is_enabled && ! pickup_is_enabled ) {
				$(
					'#orderable_location_service_hours_pickup_same_as_delivery_label'
				).removeClass( 'orderable-ui-hide' );
				$( '.orderable-admin-button--delivery' ).addClass(
					'orderable-trigger-element--active'
				);
				$( '.orderable-toggle-wrapper--delivery' ).addClass(
					'orderable-toggle-wrapper--active'
				);

				return;
			}

			if ( ! delivery_is_enabled && ! pickup_is_enabled ) {
				$( '.orderable-notice--select-service' ).removeClass(
					'orderable-ui-hide'
				);
				$( '.orderable-toggle-wrapper--delivery' ).removeClass(
					'orderable-toggle-wrapper--active'
				);

				return;
			}

			if ( ! delivery_is_enabled && pickup_is_enabled ) {
				$( '#orderable_location_service_hours_pickup_same_as_delivery' )
					.prop( 'checked', false )
					.change();

				$(
					'#orderable_location_service_hours_pickup_same_as_delivery_label'
				).addClass( 'orderable-ui-hide' );
				$( '.orderable-table--service-hours-pickup' ).removeClass(
					'orderable-element--disabled'
				);

				$( '.orderable-admin-button--pickup' ).addClass(
					'orderable-trigger-element--active'
				);
				$( '.orderable-toggle-wrapper--pickup' )
					.addClass( 'orderable-toggle-wrapper--active' )
					.removeClass( 'orderable-element--disabled' );

				$( '.orderable-admin-button--delivery' ).removeClass(
					'orderable-trigger-element--active'
				);
				$( '.orderable-toggle-wrapper--delivery' ).removeClass(
					'orderable-toggle-wrapper--active'
				);
			}
		},

		handle_enable_service_pickup_on_click() {
			const pickup_is_enabled = $( this ).hasClass(
					'woocommerce-input-toggle--enabled'
				),
				delivery_is_enabled =
					$(
						'[name=orderable_location_store_services_delivery]'
					).val() === 'yes';

			if ( pickup_is_enabled ) {
				$( '.orderable-admin-button--pickup' ).removeClass(
					'orderable-ui-hide'
				);
				$( '.orderable-table--service-hours-pickup' ).removeClass(
					'orderable-element--disabled'
				);
				$( '.orderable-notice--select-service' ).addClass(
					'orderable-ui-hide'
				);
			} else {
				$( '.orderable-admin-button--pickup' )
					.addClass( 'orderable-ui-hide' )
					.removeClass( 'orderable-trigger-element--active' );
			}

			if ( pickup_is_enabled && delivery_is_enabled ) {
				$(
					'#orderable_location_service_hours_pickup_same_as_delivery_label'
				).removeClass( 'orderable-ui-hide' );

				$( '#orderable_location_service_hours_pickup_same_as_delivery' )
					.prop( 'checked', true )
					.change();

				return;
			}

			if ( pickup_is_enabled && ! delivery_is_enabled ) {
				$( '#orderable_location_service_hours_pickup_same_as_delivery' )
					.prop( 'checked', false )
					.change();

				$(
					'#orderable_location_service_hours_pickup_same_as_delivery_label'
				).addClass( 'orderable-ui-hide' );
				$( '.orderable-admin-button--pickup' ).addClass(
					'orderable-trigger-element--active'
				);

				$( '.orderable-toggle-wrapper--pickup' )
					.addClass( 'orderable-toggle-wrapper--active' )
					.removeClass( 'orderable-element--disabled' );

				$( '.orderable-element--pickup' ).removeClass(
					'orderable-element--disabled'
				);

				return;
			}

			if ( ! pickup_is_enabled && delivery_is_enabled ) {
				$( '#orderable_location_service_hours_pickup_same_as_delivery' )
					.prop( 'checked', true )
					.change();

				$(
					'#orderable_location_service_hours_pickup_same_as_delivery_label'
				).addClass( 'orderable-ui-hide' );
				$( '.orderable-table--service-hours-delivery' ).removeClass(
					'orderable-element--disabled'
				);

				$( '.orderable-admin-button--delivery' ).addClass(
					'orderable-trigger-element--active'
				);
				$( '.orderable-toggle-wrapper--delivery' )
					.addClass( 'orderable-toggle-wrapper--active' )
					.removeClass( 'orderable-element--disabled' );

				$( '.orderable-admin-button--pickup' ).removeClass(
					'orderable-trigger-element--active'
				);
				$( '.orderable-toggle-wrapper--pickup' ).removeClass(
					'orderable-toggle-wrapper--active'
				);

				return;
			}

			if ( ! delivery_is_enabled && ! pickup_is_enabled ) {
				$( '.orderable-notice--select-service' ).removeClass(
					'orderable-ui-hide'
				);
				$( '.orderable-toggle-wrapper--pickup' ).removeClass(
					'orderable-toggle-wrapper--active'
				);
			}
		},

		on_new_holiday_row() {
			const $row = $( '.orderable-table--holidays' ).find(
				'.orderable-table__row--repeatable:last-child'
			);

			$row.find( '.datepicker' ).each( function () {
				const args = $( this ).data( 'datepicker' );

				$( this ).datepicker( args );
			} );
		},
	};

	$( document ).ready( orderable_multi_location.on_ready );
} )( jQuery );
