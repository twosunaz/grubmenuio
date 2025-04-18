( function ( $, document ) {
	let parentPointsEarned = '';

	$( document.body ).on( 'orderable-drawer.opened', function () {
		parentPointsEarned = $(
			'.orderable-drawer .orderable-points-to-be-earned'
		).html();
	} );

	$( document.body ).on( 'orderable-drawer.closed', function () {
		parentPointsEarned = '';
	} );

	$( document.body ).on( 'orderable_variation_set', function ( event, data ) {
		if (
			! data?.variation?.points_earned_when_purchasing_message ||
			! data?.variation?.points_earned
		) {
			$( '.orderable-drawer .orderable-points-to-be-earned' ).html(
				parentPointsEarned
			);

			return;
		}

		const $field_group_wrap = $(
			'.orderable-drawer .orderable-product-fields-group-wrap'
		);

		let productPointsEarned = parseInt(
			data?.variation?.points_earned,
			10
		);

		let addonPointsEarned = 0;
		$field_group_wrap.find( '[data-product-option]' ).each( function () {
			const $option = $( this );

			const isSelected =
				$option.is( ':selected' ) ||
				$option.hasClass( 'orderable-product-option--checked' );

			if ( ! isSelected ) {
				return;
			}

			const pointsEarned = Number( $option.attr( 'data-points-earned' ) );

			if ( Number.isNaN( pointsEarned ) ) {
				return;
			}

			addonPointsEarned += pointsEarned;
		} );

		productPointsEarned = productPointsEarned + addonPointsEarned;

		if ( 'number' !== typeof productPointsEarned ) {
			return;
		}

		let message = data.variation.points_earned_when_purchasing_message;

		message = message?.replace( '{points}', productPointsEarned );

		if ( ! message ) {
			return;
		}

		$( '.orderable-drawer .orderable-points-to-be-earned' ).html( message );
	} );
} )( jQuery, document );
