(function($){
	var Orderable_wcfm = {
		init: function () {
			$( 'body' ).on( 'change', '#orderable-wcfm-override-location', Orderable_wcfm.handle_override_toggle );

			window.setTimeout( () => {
				jQuery(document).on( 'click', '.orderable-mv-save-location-btn', Orderable_wcfm.save_location );
				Orderable_wcfm.checkbox_fix();
			}, 1000 );
		},

		/**
		 * Handle override toggle.
		 */
		handle_override_toggle: function ( e ) {
			if ( !$( '#orderable-wcfm-override-location' ).is( ':checked' ) ) {
				$( '.ord-wcfm' ).addClass( 'ord--disabled' );
			} else {
				$( '.ord-wcfm' ).removeClass( 'ord--disabled' );
			}

			if ( e ) {
				var toggle_checked = $( '#orderable-wcfm-override-location' ).is( ':checked' );
				var data = {
					action: 'orderable_wcfm_override_location',
					location_id: $( '#orderable-mv-location-id' ).val(),
					_wpnonce: $( '#orderable_location_nonce' ).val(),
					override: toggle_checked,
					plugin: $( '#orderable-mv-plugin-id' ).val(),
				};
				
				$.post( wcfm_orderable_var.ajaxurl, data );
			}
		},

		/**
		 * Save location.
		 */
		save_location: function ( e ) {
			console.log( $( "#orderable-wcfm-location" ).serializeJSON() );

			var data = {
				action: 'orderable_wcfm_save_location_data',
				location_id: $( '#orderable-mv-location-id' ).val(),
				_wpnonce: $( '#orderable_location_nonce' ).val(),
				plugin: $( '#orderable-mv-plugin-id' ).val(),
				data: $( "#orderable-wcfm-location" ).serializeJSON(),
			};

			$( '#orderable-wcfm-location' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			} );

			$.post( wcfm_orderable_var.ajaxurl, data, function ( data ) {
				$( '#orderable-wcfm-location' ).unblock();

				if ( data && data.success && data.data.new_location_id ) { 
					$( '#orderable-mv-location-id' ).val( data.data.new_location_id );
				}
			} );

			return false;
		},
	
		/**
		 * Fix checkboxes issue.
		 *
		 * Checkboxes in WCFM do not work unless wcfm-checkbox class is added to them.
		 */
		checkbox_fix: function () { 
			$( `.orderable-enable-day,
				.orderable-table--holidays input[type=checkbox],
				.orderable-delivery-zones-modal__field-checkbox,
				.multi-select-menu input[type=checkbox],
				#orderable_location_service_hours_pickup_same_as_delivery`
			).addClass( 'wcfm-checkbox' );

			jQuery('html').on('click.multiselect', function() {
				$( '.multi-select-menu input[type=checkbox]' ).addClass( 'wcfm-checkbox' );
			} );
			
		}
	};

	$( document ).ready( Orderable_wcfm.init );
} )( jQuery );
