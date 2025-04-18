<?php
/**
 * Display the edit location screen.
 *
 * @package Orderable
 */

global $post;
$location_id      = filter_input( INPUT_GET, 'orderable_mv_location_id' );
$multi_vendor     = Orderable_Pro_Integration_Dokan::get_instance();
$data             = $multi_vendor->get_location_data_for_vendor( $location_id );
$is_vendor_owner  = $multi_vendor->is_vendor_owner_of_location( dokan_get_current_user_id(), $location_id );
$override_allowed = $dokan_integration->is_vendor_allowed_to_override_location( dokan_get_current_user_id(), $location_id );
$location         = new Orderable_Location_Single( $location_id );
$location_title   = empty( $location->location_data['title'] ) ? '' : $location->location_data['title'];

if ( ! $override_allowed ) {
	?>
	<div class="dokan-alert dokan-alert-danger">
		<?php esc_html_e( 'You are not allowed to override this location.', 'orderable-pro' ); ?>
	<?php
	return;
}
?>

<?php do_action( 'dokan_dashboard_wrap_start' ); ?>
<div class="dokan-dashboard-wrap ">

	<?php

	/**
	 *  Adding dokan_dashboard_content_before hook
	 *
	 *  @hooked get_dashboard_side_navigation
	 *
	 *  @since 2.4
	 */
	do_action( 'dokan_dashboard_content_before' );
	?>

		<div class="dokan-dashboard-content dokan-product-listing">
			<?php
			/**
			 *  Adding dokan_dashboard_content_before hook
			 *
			 *  @hooked get_dashboard_side_navigation
			 *
			 *  @since 2.4
			 */
			do_action( 'dokan_dashboard_content_inside_before' );
			?>

			<article class="dokan-product-listing-area">
				<?php dokan_product_dashboard_errors(); ?>

				<div class="dokan-dashboard-product-listing-wrapper">
					<form id="orderable-dokan-location" method="POST" class="dokan-form-inline">
						<div class="ord-dokan <?php echo 'yes' === $data['override'] ? '' : 'ord--disabled'; ?>">
							<?php if ( 'new' === $location_id || $is_vendor_owner ) { ?>
								<div class="ord-dokan__title">
									<input type="text" name="orderable_location_name" class="ord-dokan__title-input" placeholder="<?php esc_html_e( 'Location name', 'orderable-pro' ); ?>" value="<?php echo esc_attr( $location_title ); ?>">
								</div>
								<?php
							} else {
								?>
								<h2><?php echo esc_html( $location_title ); ?></h2>
								<?php
							}
							?>

							<?php if ( 'new' !== $location_id && ! $is_vendor_owner ) { ?>
								<div class="ord-dokan-override">
									<div class="ord-dokan-override__msg">
										<strong><?php esc_html_e( 'Override settings for this location?' ); ?></strong>
									</div>
									<div class="ord-dokan-override__toggle">
										<label class="ord-dokan-override-toggle__switch">
											<input type="checkbox" id='orderable-dokan-override-location' <?php checked( 'yes', $data['override'] ); ?> >
											<span class="ord-dokan-override-toggle__slider"></span>
										</label>
									</div>
								</div>
							<?php } ?>

							<div class="ord-dokan__location-form">
								<?php
								$multi_vendor->show_edit_location_screen( $location_id );
								?>
								<div class="ord-dokan-location-form__overlay"></div>
							</div>
						</div>
					</form>
				</div>
			</article>

			<?php

			/**
			 * Adding dokan_dashboard_content_before hook.
			 *
			 *  @hooked get_dashboard_side_navigation
			 *
			 *  @since 2.4
			 */
			do_action( 'dokan_dashboard_content_inside_after' );
			?>

		</div><!-- #primary .content-area -->

		<?php

		/**
		 *  Adding dokan_dashboard_content_after hook
		 *
		 *  @since 2.4
		 */
		do_action( 'dokan_dashboard_content_after' );
		?>

	</div><!-- .dokan-dashboard-wrap -->

<?php do_action( 'dokan_dashboard_wrap_end' ); ?>
