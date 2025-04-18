import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	TextControl,
	FormTokenField,
} from '@wordpress/components';
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
						value={ attributes.label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
					/>

					<FormTokenField
						label={ __( 'Metakeys to show', 'orderable' ) }
						placeholder={ __(
							'Leave blank to show all',
							'orderable'
						) }
						value={ attributes.fieldsToInclude }
						onChange={ ( tokens ) =>
							setAttributes( { fieldsToInclude: tokens } )
						}
					/>

					<FormTokenField
						label={ __( 'Metakeys to exclude', 'orderable' ) }
						value={ attributes.fieldsToExclude }
						onChange={ ( tokens ) =>
							setAttributes( { fieldsToExclude: tokens } )
						}
						suggestions={ [
							'orderable_order_date',
							'orderable_order_time',
							'orderable_notification_optin',
						] }
					/>
				</PanelBody>
			</InspectorControls>

			{ attributes.showLabel && (
				<div className="wp-block-orderable-receipt-layouts__label">
					{ attributes.label }
				</div>
			) }

			<div className="wp-block-orderable-order-meta-fields__item">
				<span className="wp-block-orderable-receipt-layouts__label">
					is_vat_exempt:
				</span>{ ' ' }
				no
			</div>

			<div className="wp-block-orderable-order-meta-fields__item">
				<span className="wp-block-orderable-receipt-layouts__label">
					custom_metadata:
				</span>{ ' ' }
				Custom metadata
			</div>
		</div>
	);
}
