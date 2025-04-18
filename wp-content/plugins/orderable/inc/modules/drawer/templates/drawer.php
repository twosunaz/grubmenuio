<?php
/**
 * Drawer: Main.
 *
 * This template can be overridden by copying it to yourtheme/orderable/drawer/drawer.php
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

<div class="orderable-drawer">
	<button class="orderable-drawer__close" data-orderable-trigger="drawer.close"><?php _e( 'Close', 'orderable' ); ?></button>

	<div class="orderable-drawer__inner orderable-drawer__html"></div>
	<div class="orderable-drawer__inner orderable-drawer__cart">
		<h3><?php _e( 'Your Order', 'orderable' ); ?></h3>

		<div class="orderable-mini-cart-wrapper">
			<?php Orderable_Drawer::mini_cart(); ?>
		</div>
	</div>
</div>
