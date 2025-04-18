import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, TextControl } from '@wordpress/components';
import './editor.scss';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

function LineItem( {
	productName,
	price,
	showPrices,
	showCheckboxes,
	showMetaData,
} ) {
	return (
		<div className="orderable-order-line-item">
			<span>
				{ showPrices &&
					createInterpolateElement(
						__( '1× <product/> <price/>', 'orderable' ),
						{
							product: (
								<span
									style={ {
										marginLeft: '5pt',
										display: 'inline-flex',
										flexDirection: 'column',
									} }
								>
									{ productName }

									{ showMetaData && (
										<span>
											{ __(
												'Size: medium',
												'orderable'
											) }
										</span>
									) }
								</span>
							),
							price: (
								<span style={ { marginLeft: '15pt' } }>
									{ price }
								</span>
							),
						}
					) }

				{ ! showPrices &&
					createInterpolateElement(
						__( '1× <product/>', 'orderable' ),
						{
							product: (
								<span
									style={ {
										marginLeft: '5pt',
										display: 'inline-flex',
										flexDirection: 'column',
									} }
								>
									{ productName }

									{ showMetaData && (
										<span>
											{ __(
												'Size: medium',
												'orderable'
											) }
										</span>
									) }
								</span>
							),
						}
					) }
			</span>

			{ showCheckboxes && (
				<span
					style={ {
						width: '13.5pt',
						height: '13.5pt',
						border: '1px solid #111111',
					} }
				></span>
			) }
		</div>
	);
}

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
						label={ __( 'Show prices', 'orderable' ) }
						checked={ attributes.showPrices }
						onChange={ ( value ) =>
							setAttributes( { showPrices: value } )
						}
					/>

					<ToggleControl
						label={ __( 'Show checkboxes', 'orderable' ) }
						checked={ attributes.showCheckboxes }
						onChange={ ( value ) =>
							setAttributes( { showCheckboxes: value } )
						}
					/>

					<ToggleControl
						label={ __( 'Show item meta data', 'orderable' ) }
						checked={ attributes.showMetaData }
						onChange={ ( value ) =>
							setAttributes( { showMetaData: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			{ attributes.showLabel && (
				<div className="wp-block-orderable-receipt-layouts__label">
					{ attributes.label }
				</div>
			) }

			<LineItem
				productName={ __( 'Spicy Marinated Olives', 'orderable' ) }
				price={ __( '$25.00', 'orderable' ) }
				showPrices={ attributes.showPrices }
				showCheckboxes={ attributes.showCheckboxes }
				showMetaData={ attributes.showMetaData }
			/>

			<LineItem
				productName={ __( 'Hamburger', 'orderable' ) }
				price={ __( '$18.00', 'orderable' ) }
				showPrices={ attributes.showPrices }
				showCheckboxes={ attributes.showCheckboxes }
				showMetaData={ attributes.showMetaData }
			/>
		</div>
	);
}
