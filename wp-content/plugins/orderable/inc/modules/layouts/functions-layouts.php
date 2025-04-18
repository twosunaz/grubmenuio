<?php
/**
 * Functions: Layouts.
 *
 * @package Orderable/Functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orderable function to render layout.
 *
 * @param int|null $id
 */
function orderable( $id = null ) {
	echo Orderable_Layouts::orderable_shortcode(
		array(
			'id' => $id,
		)
	);
}
