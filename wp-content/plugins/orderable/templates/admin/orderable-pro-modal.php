<?php
/**
 * Template: Pro modal.
 *
 * This template can be overridden by copying it to yourtheme/orderable/orderable-pro-modal.php
 *
 * HOWEVER, on occasion Orderable will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Orderable/Templates
 */

defined( 'ABSPATH' ) || exit;
?>

<div id="orderable-pro-modal" style="display: none;">
	<div class="orderable-pro-modal">
		<div class="orderable-pro-modal__content">
			<h2><?php _e( 'Unlock Orderable Pro', 'orderable' ); ?></h2>
			<p><?php _e( 'Get this feature and more with the Pro version of Orderable!', 'orderable' ); ?></p>
			<?php echo Orderable_Helpers::get_pro_button( 'pro-modal', __( 'Learn More', 'orderable' ), false ); ?>
		</div>
	</div>
</div>
