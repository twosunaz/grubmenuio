import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
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

					<ToggleControl
						label={ __( 'Show phone', 'orderable' ) }
						checked={ attributes.showPhone }
						onChange={ ( value ) =>
							setAttributes( { showPhone: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show email', 'orderable' ) }
						checked={ attributes.showEmail }
						onChange={ ( value ) =>
							setAttributes( { showEmail: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div className="wp-block-orderable-receipt-layouts__label">
				{ attributes.showLabel && attributes.label }
			</div>

			<div>{ __( '2659 Vernon Street', 'orderable' ) }</div>

			{ attributes.showPhone && (
				<div>{ __( '760-613-2784', 'orderable' ) }</div>
			) }

			{ attributes.showEmail && (
				<div>{ __( 'customer-orderable@test.com', 'orderable' ) }</div>
			) }
		</div>
	);
}
