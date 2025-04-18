import { useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, TextControl } from '@wordpress/components';
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
						value={ attributes.label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div className="wp-block-orderable-receipt-layouts__label">
				{ attributes.showLabel && attributes.label }
			</div>

			<div> { __( '2042 Rhapsody Street', 'orderable' ) } </div>
		</div>
	);
}
