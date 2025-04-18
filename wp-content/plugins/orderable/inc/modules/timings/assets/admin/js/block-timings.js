const orderableIconTimmingsBlock = wp.element.createElement('svg',
	{
		width: 20,
		height: 15,
		viewBox: '0 0 20 15'
	},
	wp.element.createElement( 'path',
		{
			'fillRule': 'evenodd',
			'clipRule': 'evenodd',
			fill: '#BD47F5',
			d: "M15.6129 13.6639C18.6362 11.6975 20.7599 7.52818 19.7423 4.61553C18.7247 1.71768 14.5511 0.0765439 10.1415 0.00261896C5.7467 -0.071306 1.1159 1.42198 0.186796 4.20155C-0.75706 6.98113 2.01552 11.0618 5.36326 13.2204C8.69625 15.379 12.6044 15.6303 15.6129 13.6639ZM7.10944 2.68222C6.69369 2.68222 6.35667 3.01988 6.35667 3.43641C6.35667 3.85293 6.69369 4.1906 7.10944 4.1906H10.5044L10.5036 4.19159C10.8969 4.21733 11.2078 4.54442 11.2078 4.94415C11.2078 5.36068 10.8702 5.69834 10.4537 5.69834H9.7826L9.78247 5.69897H4.34928C3.93354 5.69897 3.59651 6.03664 3.59651 6.45316C3.59651 6.86969 3.93354 7.20735 4.34928 7.20735H10.4848C10.8869 7.2237 11.2078 7.55483 11.2078 7.96091C11.2078 8.36064 10.8969 8.68774 10.5037 8.71347L10.5055 8.71573H6.10574C5.69 8.71573 5.35297 9.0534 5.35297 9.46992C5.35297 9.88645 5.69 10.2241 6.10574 10.2241H13.8007C13.8251 10.2241 13.8492 10.223 13.8729 10.2207C16.018 10.1438 17.7318 8.48614 17.7318 6.45253C17.7318 4.36989 15.9344 2.68158 13.7171 2.68158H13.7162L7.10944 2.68222ZM15.0192 6.45253C15.0192 6.87904 14.599 7.51057 13.7171 7.51057C12.8351 7.51057 12.415 6.87904 12.415 6.45253C12.415 6.02602 12.8351 5.39449 13.7171 5.39449C14.599 5.39449 15.0192 6.02602 15.0192 6.45253ZM12.3659 11.2742C12.4644 11.5671 12.2588 11.9864 11.9662 12.1841C11.675 12.3818 11.2968 12.3566 10.9742 12.1395C10.6502 11.9224 10.3819 11.5121 10.4732 11.2326C10.5631 10.9531 11.0113 10.8029 11.4367 10.8103C11.8634 10.8178 12.2674 10.9828 12.3659 11.2742ZM13.6518 12.1841C13.3592 11.9864 13.1536 11.5671 13.2521 11.2742C13.3506 10.9828 13.7546 10.8178 14.1813 10.8103C14.6067 10.8029 15.0549 10.9531 15.1448 11.2326C15.2361 11.5121 14.9678 11.9224 14.6438 12.1395C14.3212 12.3566 13.943 12.3818 13.6518 12.1841Z"
		}
	)
);


wp.blocks.registerBlockType( 'orderable/open-hours', {
	title: wp.i18n.__( 'Orderable: Open Hours', 'orderable' ),
	description: wp.i18n.__( 'Display the location open hours.', 'orderable' ),
	category: 'common', //category
	icon: orderableIconTimmingsBlock,
	//display the edit interface + preview
	edit( props ) {
		const attributes = props.attributes;
		const setAttributes = props.setAttributes;
		const locations = orderable_timings_block_vars?.locations;

		/**
		 * Get block options.
		 *
		 * @return {Array}
		 */
		function getOptions() {
			let options = [];

			if ( ! locations.length ) {
				return options;
			}

			options.push(
				wp.element.createElement( wp.components.SelectControl, {
					key: 'orderable-open-hours-control',
					value: attributes.location_id,
					label: wp.i18n.__( 'Locations', 'orderable' ),
					onChange: ( location_id ) => { setAttributes( { location_id } ) },
					help: wp.i18n.__( 'Leave empty to show the location selected by the user on the frontend.', 'orderable' ),
					options: [ {title: '', value: ''}, ...locations ]
				} )
			);

			return options;
		}

		//Display block preview and UI
		return wp.element.createElement( 'div', {
				key: 'orderable-render',
			},
			[
				// Preview a block with a PHP render callback.
				wp.element.createElement( wp.serverSideRender, {
					key: 'orderable-serverside-render',
					block: 'orderable/open-hours',
					attributes: attributes
				} ),
				// Block inspector.
				! locations.length ? undefined : wp.element.createElement( wp.blockEditor.InspectorControls, {
						key: 'orderable-inspector-controls',
					},
					[
						wp.element.createElement(
							'div',
							{
								key: 'orderable-components-panel-body',
								className: 'components-panel__body is-opened',
							},
							getOptions()
						),
					]
				)
			]
		);
	},
	save() {
		return null;//save has to exist. This all we need
	}
} );