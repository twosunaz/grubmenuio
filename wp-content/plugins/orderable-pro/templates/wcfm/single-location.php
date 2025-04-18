<?php
/**
 * Display the edit location screen for WCFM.
 *
 * @package Orderable
 */

global $post;

$location_id      = filter_input( INPUT_GET, 'orderable_mv_location_id' );
$multi_vendor     = Orderable_Pro_Integration_Wcfm::get_instance();
$data             = $multi_vendor->get_location_data_for_vendor( $location_id );
$is_vendor_owner  = $multi_vendor->is_vendor_owner_of_location( get_current_user_id(), $location_id );
$override_allowed = $multi_vendor->is_vendor_allowed_to_override_location( get_current_user_id(), $location_id );
$location         = new Orderable_Location_Single( $location_id );
$location_title   = empty( $location->location_data['title'] ) ? '' : $location->location_data['title'];

if ( ! $override_allowed ) {
	?>
	<div class="collapse wcfm-collapse">
		<div class="wcfm-collapse-content">
		<?php esc_html_e( 'You are not allowed to override this location.', 'orderable-pro' ); ?>
		</div>
	</div>
	<?php
	return;
}
?>


<div class="collapse wcfm-collapse">
	<div class="wcfm-page-headig">
		<span class="wcfmfa fa-calendar-alt"></span>
		<span class="wcfm-page-heading-text"><?php esc_html_e( 'Orderable', 'wc-frontend-manager-ultimate' ); ?></span>
		<?php do_action( 'wcfm_page_heading' ); ?>

	</div>
	<div class="wcfm-collapse-content">
		<div id="wcfm_page_load"></div>

		<div class="wcfm-container wcfm-top-element-container">
			<h2><?php echo esc_html( ! empty( $location->location_data['title'] ) ? $location->location_data['title'] : '' ); ?></h2>
			<div class="wcfm-clearfix"></div>
		</div>
		<div class="wcfm-clearfix"></div><br />


		<div class="wcfm-container">
			<div id="wwcfm_bookings_listing_expander" class="wcfm-content">
				<form id="orderable-wcfm-location" method="POST" class="wcfm-form-inline">
					<div class="ord-wcfm <?php echo 'yes' === $data['override'] ? '' : 'ord--disabled'; ?>">
						<?php if ( 'new' === $location_id || $is_vendor_owner ) { ?>
							<div class="ord-wcfm__title">
								<input type="text" name="orderable_location_name" class="ord-wcfm__title-input" placeholder="<?php esc_html_e( 'Location name', 'orderable-pro' ); ?>" value="<?php echo esc_attr( $location_title ); ?>">
							</div>
						<?php } ?>

						<?php if ( 'new' !== $location_id && ! $is_vendor_owner ) { ?>
							<div class="ord-wcfm-override">
								<div class="ord-wcfm-override__msg">
									<strong><?php esc_html_e( 'Override settings for this location?' ); ?></strong>
								</div>
								<div class="ord-wcfm-override__toggle">
									<label class="ord-wcfm-override-toggle__switch">
										<input type="checkbox" class="wcfm-checkbox" id='orderable-wcfm-override-location' <?php checked( 'yes', $data['override'] ); ?> />
										<span class="ord-wcfm-override-toggle__slider"></span>
									</label>
								</div>
							</div>
						<?php } ?>

						<div class="ord-wcfm__location-form">
							<?php
							$multi_vendor->show_edit_location_screen( $location_id );
							?>
							<div class="ord-wcfm-location-form__overlay"></div>
						</div>
					</div>
				</form>
				<div class="wcfm-clearfix"></div>
			</div>
		</div>
	</div>
</div>