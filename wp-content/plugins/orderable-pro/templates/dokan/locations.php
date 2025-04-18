<?php
/**
 * Display the locations screen.
 *
 * @package Orderable
 */

global $post;
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
							<?php
							if ( $dokan_integration->is_vendor_allowed_to_create_locations( dokan_get_current_user_id() ) ) {
								?>
								<div class="ord-dokan__add-location-btn dokan-clearfix">
									<a href="<?php echo esc_url( dokan_get_navigation_url( 'orderable/' ) . '?orderable_mv_location_id=new' ); ?>" class="dokan-btn dokan-btn-theme"><?php esc_html_e( 'Add New Location', 'dokan-lite' ); ?></a>
								</div>
								<?php
							}
							?>

							<form id="product-filter" method="POST" class="dokan-form-inline">
								<table class="dokan-table dokan-table-striped product-listing-table dokan-inline-editable-table" id="dokan-product-list-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Location', 'orderable-pro' ); ?></th>
											<th><?php esc_html_e( 'Address', 'orderable-pro' ); ?></th>
											<th><?php esc_html_e( 'Status', 'orderable-pro' ); ?></th>
											<th><?php esc_html_e( 'Services', 'orderable-pro' ); ?></th>
										</tr>
									</thead>
									<tbody>
									<?php
									$locations = $dokan_integration->get_vendor_locations();

									foreach ( $locations as $location_post ) {
										if ( empty( $location_post ) ) {
											continue;
										}

										$location     = new Orderable_Location_Single_Pro( $location_post->ID );
										$edit_allowed = $dokan_integration->is_vendor_allowed_to_override_location( dokan_get_current_user_id(), $location->location_data['location_id'] );
										$edit_url     = dokan_get_navigation_url( 'orderable' ) . '?orderable_mv_location_id=' . $location->location_data['location_id']
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
												<?php echo esc_html( $dokan_integration->get_formatted_address( $location ) ); ?>
											</td>
											<td>
												<strong><?php echo esc_html( $location->get_status() ); ?></strong>
											</td>
											<td>
												<?php echo esc_html( $dokan_integration->get_formatted_services( $location ) ); ?>
											</td>
										</tr>
										<?php
									}
									?>
									</tbody>

								</table>
							</form>
						</div>
				</article>

				<?php

				/**
				 *  Adding dokan_dashboard_content_before hook
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
