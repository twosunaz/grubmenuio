<div class="orderable-fields-row orderable-fields-row--meta">
	<div class="orderable-fields-row__body">

		<div class="orderable-fields-row__body-row">
			<div class="orderable-fields-row__body-row-left">
				<h3>
					<label for="categories"><?php _e( 'Categories', 'orderable' ); ?></label>
				</h3>
				<p><?php _e( 'Select which product categories to display in this layout.', 'orderable' ); ?></p>
			</div>
			<div class="orderable-fields-row__body-row-right">
				<?php
				// Categories.
				woocommerce_wp_select(
					array(
						'id'                => 'orderable_categories',
						'name'              => 'orderable_categories[]',
						'class'             => 'orderable-select orderable-select--multi-select orderable-select--categories',
						'label'             => '',
						'options'           => Orderable_Layouts::get_categories(),
						'value'             => $layout_settings['categories'],
						'custom_attributes' => array(
							'multiple' => 'multiple',
						),
					)
				);
				?>
			</div>
		</div>

		<div class="orderable-fields-row__body-row">
			<div class="orderable-fields-row__body-row-left">
				<h3>
					<label for="layout"><?php _e( 'Display', 'orderable' ); ?></label>
				</h3>
				<p><?php _e( 'How should the products be displayed?', 'orderable' ); ?></p>
			</div>
			<div class="orderable-fields-row__body-row-right">
				<?php
				// Layout.
				woocommerce_wp_select(
					array(
						'id'      => 'orderable_layout',
						'label'   => '',
						'options' => array(
							'grid' => __( 'Grid', 'orderable' ),
							'list' => __( 'List', 'orderable' ),
						),
						'value'   => esc_attr( $layout_settings['layout'] ),
					)
				);
				?>
			</div>
		</div>

		<div class="orderable-fields-row__body-row">
			<div class="orderable-fields-row__body-row-left">
				<h3>
					<label for="orderable_sort"><?php esc_html_e( 'Sort', 'orderable' ); ?></label>
				</h3>
				<p><?php esc_html_e( 'How should the products be sorted?', 'orderable' ); ?></p>
			</div>
			<div class="orderable-fields-row__body-row-right">
				<?php Orderable_Layouts::get_layout_field( 'sort', $layout_settings ); ?>
			</div>
		</div>

		<div class="orderable-fields-row__body-row">
			<div class="orderable-fields-row__body-row-left">
				<h3>
					<label for="orderable_sort_on_frontend"><?php esc_html_e( 'Allow sorting on the frontend', 'orderable' ); ?></label>
				</h3>
				<p><?php esc_html_e( 'Choose if customers can change how the products are sorted.', 'orderable' ); ?></p>
			</div>
			<div class="orderable-fields-row__body-row-right">
				<?php Orderable_Layouts::get_layout_field( 'sort_on_frontend', $layout_settings ); ?>
			</div>
		</div>

		<div class="orderable-fields-row__body-row">
			<div class="orderable-fields-row__body-row-left">
				<h3>
					<label for="layout"><?php _e( 'Sections', 'orderable' ); ?></label>
				</h3>
				<p><?php esc_html_e( 'Separate each category by titles or tabs.', 'orderable' ); ?></p>
			</div>
			<div class="orderable-fields-row__body-row-right">
				<?php Orderable_Layouts::get_layout_field( 'sections', $layout_settings ); ?>
			</div>
		</div>

		<div class="orderable-fields-row__body-row">
			<div class="orderable-fields-row__body-row-left">
				<h3>
					<label for="images"><?php _e( 'Images', 'orderable' ); ?></label>
				</h3>
				<p><?php _e( 'Should product images be displayed?', 'orderable' ); ?></p>
			</div>
			<div class="orderable-fields-row__body-row-right">
				<?php
				// Images.
				woocommerce_wp_checkbox(
					array(
						'id'    => 'orderable_images',
						'label' => '',
						'value' => wc_bool_to_string( $layout_settings['images'] ),
					)
				);
				?>
			</div>
		</div>

		<div class="orderable-fields-row__body-row">
			<div class="orderable-fields-row__body-row-left">
				<h3>
					<label for="clickable_card"><?php _e( 'Clickable Card', 'orderable' ); ?></label>
				</h3>
				<p><?php _e( 'Choose what happens when you click the product card.', 'orderable' ); ?></p>
			</div>
			<div class="orderable-fields-row__body-row-right">
				<?php
				// Layout.
				woocommerce_wp_select(
					array(
						'id'      => 'orderable_card_click',
						'label'   => '',
						'options' => array(
							''             => __( 'Nothing', 'orderable' ),
							'add-to-cart'  => __( 'Add to Cart', 'orderable' ),
							'view-product' => __( 'Quick View Product', 'orderable' ),
						),
						'value'   => esc_attr( $layout_settings['card_click'] ),
					)
				);
				?>
			</div>
		</div>

		<div class="orderable-fields-row__body-row">
			<div class="orderable-fields-row__body-row-left">
				<h3>
					<label for="clickable_card"><?php esc_html_e( 'Quantity roller', 'orderable' ); ?></label>
				</h3>
				<p><?php esc_html_e( 'Allow customers to add products without triggering the side drawer.', 'orderable' ); ?></p>
			</div>
			<div class="orderable-fields-row__body-row-right">
				<?php
				woocommerce_wp_checkbox(
					array(
						'id'    => 'orderable_quantity_roller',
						'label' => '',
						'value' => wc_bool_to_string( $layout_settings['quantity_roller'] ),
					)
				);
				?>
			</div>
		</div>

		<?php
			/**
			 * Fires after the layout settings fields.
			 *
			 * @since 1.7.0
			 * @hook orderable_after_layout_settings_fields
			 * @param  array $layout_settings The layout settings.
			 */
			do_action( 'orderable_after_layout_settings_fields', $layout_settings );
		?>
	</div>
</div>

<?php Orderable_Helpers::orderable_pro_modal(); ?>
