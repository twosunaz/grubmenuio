import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Content', 'orderable' ) }>
					<ToggleControl
						label={ __( 'Show label', 'orderable' ) }
						checked={ attributes.showLabel }
						onChange={ ( value ) =>
							setAttributes( { showLabel: value } )
						}
					/>
					<TextControl
						label={ __( 'Label', 'orderable' ) }
						placeholder={ __( 'Payment method: ', 'orderable' ) }
						value={ attributes.label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show date', 'orderable' ) }
						checked={ attributes.showDate }
						onChange={ ( value ) =>
							setAttributes( { showDate: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show time', 'orderable' ) }
						checked={ attributes.showTime }
						onChange={ ( value ) =>
							setAttributes( { showTime: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			{ attributes.showLabel && (
				<div className="orderable-service-date-time__label wp-block-orderable-receipt-layouts__label">
					{ attributes.label }
				</div>
			) }

			{ attributes.showDate && (
				<div className="orderable-service-date-time__date">
					<span className="wp-block-orderable-receipt-layouts__label">
						{ __( 'Delivery Date:', 'orderable' ) }
					</span>
					{ ' August 28, 2024' }
				</div>
			) }

			{ attributes.showTime && (
				<div className="orderable-service-date-time__time">
					<span className="wp-block-orderable-receipt-layouts__label">
						{ __( 'Delivery Time:', 'orderable' ) }
					</span>
					{ ' 3:00 PM' }
				</div>
			) }
		</div>
	);
}
