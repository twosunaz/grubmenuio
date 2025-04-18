(function($){
	var Orderable_dokan = {
		init: function () {
			$( 'body' ).on( 'change', '#orderable-dokan-override-location', Orderable_dokan.handle_override_toggle );

			window.setTimeout( () => {
				jQuery(document).on( 'click', '.orderable-mv-save-location-btn', Orderable_dokan.save_location );
			}, 1000 );
		},

		/**
		 * Handle override toggle.
		 */
		handle_override_toggle: function ( e ) {
			if ( !$( '#orderable-dokan-override-location' ).is( ':checked' ) ) {
				$( '.ord-dokan' ).addClass( 'ord--disabled' );
			} else {
				$( '.ord-dokan' ).removeClass( 'ord--disabled' );
			}

			if ( e ) {
				var toggle_checked = $( '#orderable-dokan-override-location' ).is( ':checked' );
				var data = {
					action: 'orderable_dokan_override_location',
					location_id: $( '#orderable-mv-location-id' ).val(),
					_wpnonce: $( '#orderable_location_nonce' ).val(),
					override: toggle_checked,
					plugin: $( '#orderable-mv-plugin-id' ).val(),
				};
				
				$.post( dokan.ajaxurl, data );
			}
		},

		save_location: function ( e ) {
			var data = {
				action: 'orderable_dokan_save_location_data',
				location_id: $( '#orderable-mv-location-id' ).val(),
				_wpnonce: $( '#orderable_location_nonce' ).val(),
				plugin: $( '#orderable-mv-plugin-id' ).val(),
				data: $( "#orderable-dokan-location" ).serializeJSON(),
			};

			$( '#orderable-dokan-location' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			} );

			$.post( dokan.ajaxurl, data, function ( data ) {
				$( '#orderable-dokan-location' ).unblock();

				if ( data && data.success && data.data.new_location_id ) { 
					$( '#orderable-mv-location-id' ).val( data.data.new_location_id );
				}
			} );

			return false;
		}
	
	};

	$( document ).ready( Orderable_dokan.init );
} )( jQuery );
