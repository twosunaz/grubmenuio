import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Content', 'orderable' ) }>
					<TextControl
						label={ __( 'Label', 'orderable' ) }
						placeholder={ __( 'Location: ', 'orderable' ) }
						value={ attributes.label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<span className="wp-block-orderable-receipt-layouts__label">
				{ attributes.label }
			</span>
			{ __( 'Main Location', 'orderable' ) }
		</div>
	);
}
