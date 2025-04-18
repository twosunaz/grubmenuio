/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { map, sortBy, filter, head, isEmpty } from 'lodash';
import { ValidationInputError } from '@woocommerce/blocks-components'; // eslint-disable-line import/no-unresolved

export const Block = ( { checkoutExtensionData, extensions, validation } ) => {
	const { setExtensionData } = checkoutExtensionData;
	const { getValidationError, setValidationErrors, clearValidationError } =
		validation;

	const serviceDates =
		extensions?.[ 'orderable/order-service-date' ]?.serviceDates;
	const serviceDatesLabel =
		extensions?.[ 'orderable/order-service-date' ]?.serviceDatesLabel;

	const serviceDate = useSelect( ( select ) => {
		return select( 'wc/store/checkout' ).getExtensionData()?.[
			'orderable/order-service-date'
		]?.timestamp;
	} );

	const isShippingRateBeingSelected = useSelect( ( select ) =>
		select( 'wc/store/cart' ).isShippingRateBeingSelected()
	);

	const isDisabled = useSelect(
		( select ) => {
			return (
				isShippingRateBeingSelected ||
				select( 'wc/store/cart' ).isCustomerDataUpdating()
			);
		},
		[ isShippingRateBeingSelected ]
	);

	const hasShippingRates = useSelect( ( select ) => {
		return ! isEmpty(
			select( 'wc/store/cart' ).getShippingRates()?.[ 0 ]?.shipping_rates
		);
	} );

	const shouldSelectFirstAvailableDateFromExtensions =
		extensions?.[ 'orderable/order-service-date' ]
			?.shouldSelectFirstAvailableDate;
	const [
		shouldSelectFirstAvailableDate,
		setShouldSelectFirstAvailableDate,
	] = useState( false );

	const hasNoServiceDatesAvailable = getValidationError(
		'orderable/order-service-date'
	);
	const hasNoServiceDateSelected = getValidationError(
		'orderable/order-no-service-date-selected'
	);

	useEffect( () => {
		if ( shouldSelectFirstAvailableDateFromExtensions ) {
			setShouldSelectFirstAvailableDate( true );
		}
	}, [ shouldSelectFirstAvailableDateFromExtensions ] );

	useEffect( () => {
		if ( shouldSelectFirstAvailableDate && ! isDisabled ) {
			const timestamp = head(
				sortBy(
					filter(
						serviceDates,
						( serviceDateItem ) =>
							serviceDateItem?.value &&
							serviceDateItem?.value !== 'asap'
					),
					[ 'value' ]
				)
			)?.value;

			if ( ! timestamp ) {
				return;
			}

			setExtensionData(
				'orderable/order-service-date',
				'timestamp',
				`${ timestamp }`
			);

			setShouldSelectFirstAvailableDate( false );
		}
	}, [
		shouldSelectFirstAvailableDate,
		serviceDates,
		isDisabled,
		setExtensionData,
	] );

	useEffect( () => {
		if ( ! serviceDates && hasShippingRates ) {
			setValidationErrors( {
				'orderable/order-service-date': {
					message: __( 'No service dates available', 'orderable' ),
					hidden: false,
				},
			} );

			return;
		}

		clearValidationError( 'orderable/order-service-date' );
	}, [
		serviceDates,
		setValidationErrors,
		clearValidationError,
		hasShippingRates,
	] );

	useEffect( () => {
		if ( ! serviceDate ) {
			setExtensionData(
				'orderable/order-service-date',
				'timestamp',
				serviceDates?.[ 0 ]?.value
			);
		}
	}, [ serviceDates, setExtensionData, serviceDate ] );

	useEffect( () => {
		if ( ! serviceDate ) {
			setValidationErrors( {
				'orderable/order-no-service-date-selected': {
					message: __( 'Please select a service date', 'orderable' ),
					hidden: true,
				},
			} );

			return;
		}

		clearValidationError( 'orderable/order-no-service-date-selected' );
	}, [ serviceDate, setValidationErrors, clearValidationError ] );

	return (
		<div
			className={ `wp-block-orderable-checkout__service-date ${
				hasNoServiceDateSelected ? 'has-error' : ''
			}` }
		>
			{ hasNoServiceDatesAvailable?.message && (
				<ValidationInputError
					errorMessage={ hasNoServiceDatesAvailable.message }
				/>
			) }

			{ serviceDates && (
				<>
					<SelectControl
						label={ serviceDatesLabel }
						disabled={ isDisabled }
						value={ serviceDate }
						options={ map( serviceDates ) }
						onChange={ ( value ) => {
							setExtensionData(
								'orderable/order-service-date',
								'timestamp',
								value
							);
						} }
					/>
					{ hasNoServiceDateSelected &&
						! hasNoServiceDateSelected.hidden && (
							<ValidationInputError
								errorMessage={
									hasNoServiceDateSelected.message
								}
							/>
						) }
				</>
			) }
		</div>
	);
};
