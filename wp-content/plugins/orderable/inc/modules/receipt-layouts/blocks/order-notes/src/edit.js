import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

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
						placeholder={ __( 'Customer Note: ', 'orderable' ) }
						value={ attributes.label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			{ attributes.showLabel && (
				<div className="wp-block-orderable-receipt-layouts__label">
					{ attributes.label }
				</div>
			) }

			<div>{ __( 'Please remove the lettuce', 'orderable' ) }</div>
		</div>
	);
}
