/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { SelectControl, Disabled } from '@wordpress/components';

export const Edit = () => {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<Disabled>
				<SelectControl
					label={ __( 'Delivery Date', 'orderable' ) }
					options={ [
						{
							value: 'today',
							label: __( 'Today', 'orderable' ),
						},
						{
							value: 'tomorrow',
							label: __( 'Tomorrow', 'orderable' ),
						},
					] }
				/>
			</Disabled>
		</div>
	);
};
