import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RadioControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Line', 'orderable' ) }>
					<RadioControl
						label={ __( 'Style', 'orderable' ) }
						selected={ attributes.lineStyle }
						options={ [
							{
								label: __( 'Dashed', 'orderable' ),
								value: 'dashed',
							},
							{
								label: __( 'Dotted', 'orderable' ),
								value: 'dotted',
							},
							{
								label: __( 'Solid', 'orderable' ),
								value: 'solid',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { lineStyle: value } )
						}
					/>

					<TextControl
						label={ __( 'Height', 'orderable' ) }
						type="number"
						step={ 1 }
						min={ 0 }
						value={ attributes.height }
						onChange={ ( value ) =>
							setAttributes( { height: parseInt( value, 10 ) } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<span className="wp-block-orderable-receipt-layouts__label">
				<hr
					className={ `orderable-divider--is-${ attributes.lineStyle }` }
					style={ {
						backgroundColor: 'transparent',
						marginBottom: 0,
						borderTopWidth: `${ attributes.height }px`,
						borderTopColor:
							attributes?.style?.color?.text || '#111111',
					} }
				/>
			</span>
		</div>
	);
}
