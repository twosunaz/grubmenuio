<div class="collapse wcfm-collapse" id="wcfm_bookings_listing">
	<div class="wcfm-page-headig">
		<span class="wcfmfa fa-calendar-alt"></span>
		<span class="wcfm-page-heading-text"><?php _e( 'Orderable', 'wc-frontend-manager-ultimate' ); ?></span>
		<?php do_action( 'wcfm_page_heading' ); ?>

	</div>
	<div class="wcfm-collapse-content">
		<div id="wcfm_page_load"></div>

		<div class="wcfm-container wcfm-top-element-container">
			<h2><?php printf( __( 'Locations', 'wc-frontend-manager-ultimate' ) ); ?></h2>
			<div class="wcfm-clearfix"></div>
		</div>
		<div class="wcfm-clearfix"></div><br />


		<div class="wcfm-container">
			<?php
			if ( $wcfm_integration->is_vendor_allowed_to_create_locations( get_current_user_id() ) ) {
				?>
				<div class="">
					<a href="<?php echo esc_url( $wcfm_integration->get_wcfm_orderable_url() . '?orderable_mv_location_id=new' ); ?>" class="ord-wcfm__btn ord-wcfm__btn--newlocation"><?php esc_html_e( 'Add New Location', 'dokan-lite' ); ?></a>
				</div>
				<?php
			}
			?>

			<div id="wwcfm_bookings_listing_expander" class="wcfm-content">
			<table class="dokan-table dokan-table-striped product-listing-table dokan-inline-editable-table" id="dokan-product-list-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Location', 'orderable-pro' ); ?></th>
						<th><?php esc_html_e( 'Address', 'orderable-pro' ); ?></th>
						<th><?php esc_html_e( 'Status', 'orderable-pro' ); ?></th>
						<th><?php esc_html_e( 'Overriden', 'orderable-pro' ); ?></th>
						<th><?php esc_html_e( 'Services', 'orderable-pro' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'orderable-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				$locations = $wcfm_integration->get_vendor_locations( get_current_user_id() );
				foreach ( $locations as $location_post ) {
					if ( empty( $location_post ) ) {
						continue;
					}

					$location     = new Orderable_Location_Single_Pro( $location_post->ID );
					$edit_allowed = $wcfm_integration->is_vendor_allowed_to_override_location( get_current_user_id(), $location->location_data['location_id'] );
					$edit_url     = $wcfm_integration->get_wcfm_orderable_url( 'orderable' ) . '?orderable_mv_location_id=' . $location->location_data['location_id']
					?>
					<tr>
						<td>
							<strong>
								<?php
								if ( $edit_allowed ) {
									echo sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html( $location_post->post_title ) );
								} else {
									echo esc_html( $location_post->post_title );
								}
								?>
							</strong>
							<?php
							if ( $edit_allowed ) {
								?>
								<div class="row-actions">
									<span class="edit">
										<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'orderable-pro' ); ?></a>
									</span>
								</div>
								<?php
							}
							?>
						</td>
						<td>
							<?php echo esc_html( $wcfm_integration->get_formatted_address( $location ) ); ?>
						</td>
						<td>
							<strong><?php echo esc_html( $location->get_status() ); ?></strong>
						</td>
						<td>
							<?php
							if ( $wcfm_integration->is_location_overriden( $location->location_data['location_id'] ) ) {
								echo '<span class="wcfmfa fa-check"></span>';
							} else {
								echo '<span class="wcfmfa fa-times"></span>';
							}
							?>
						</td>
						<td>
							<?php echo esc_html( $wcfm_integration->get_formatted_services( $location ) ); ?>
						</td>
						<td>
							<?php if ( $edit_allowed ) { ?>
								<a class="wcfm-action-icon wcfm-action-icon--edit-location" href="<?php echo esc_url( $edit_url ); ?>"><span class="wcfmfa fa-edit"></span></a>
							<?php } ?>
						</td>
					</tr>
					<?php
				}
				?>
				</tbody>

			</table>
				<div class="wcfm-clearfix"></div>
			</div>
		</div>
		<?php
		// do_action('after_wcfm_bookings_calendar');
		?>
	</div>
</div>
