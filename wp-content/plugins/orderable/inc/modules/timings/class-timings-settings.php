<?php
/**
 * Timings settings.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Timings settings class.
 */
class Orderable_Timings_Settings {
	/**
	 * Open hours settings key.
	 *
	 * @var string
	 */
	public static $open_hours_key = 'store_general_open_hours';

	/**
	 * Service hours settings key.
	 *
	 * @var string
	 * @deprecated 1.8.0 No longer used.
	 */
	public static $service_hours_key = 'store_general_service_hours';

	/**
	 * Holidays settings key.
	 *
	 * @var string
	 */
	public static $holidays_key = 'holidays';

	/**
	 * Init.
	 */
	public static function run() {
	}

	/**
	 * Get time field.
	 *
	 * @param string $name
	 * @param array  $values
	 *
	 * @return false|string
	 */
	public static function get_time_field( $name, $values = array() ) {
		$defaults = array(
			'hour'   => '',
			'minute' => '',
			'period' => '',
		);

		$values = wp_parse_args( $values, $defaults );

		$hours   = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12 );
		$minutes = array( '00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55' );

		ob_start();
		?>
		<span class="orderable-time">
			<select class="orderable-time__select orderable-time__select--hour" name="<?php echo esc_attr( $name ); ?>[hour]">
				<?php foreach ( $hours as $hour ) { ?>
					<option value="<?php echo esc_attr( $hour ); ?>" <?php selected( $values['hour'], $hour ); ?>><?php echo esc_html( $hour ); ?></option>
				<?php } ?>
			</select>
			<select class="orderable-time__select orderable-time__select--minute" name="<?php echo esc_attr( $name ); ?>[minute]">
				<?php foreach ( $minutes as $minute ) { ?>
					<option value="<?php echo esc_attr( $minute ); ?>" <?php selected( $values['minute'], $minute ); ?>><?php echo esc_html( $minute ); ?></option>
				<?php } ?>
			</select>
			<select class="orderable-time__select orderable-time__select--period" name="<?php echo esc_attr( $name ); ?>[period]">
				<option value="AM" <?php selected( $values['period'], 'AM' ); ?>>AM</option>
				<option value="PM" <?php selected( $values['period'], 'PM' ); ?>>PM</option>
			</select>
		</span>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get max orders field.
	 *
	 * @param string $name
	 * @param array  $settings
	 *
	 * @return mixed|void
	 */
	public static function get_max_orders_field( $name, $settings = array() ) {
		ob_start();
		?>
		<strong class="orderable-table__rwd-labels"><?php esc_html_e( 'Max Orders (Day)', 'orderable' ); ?></strong>
		<?php echo Orderable_Helpers::get_pro_button( 'max-orders' ); ?>
		<?php

		return apply_filters( 'orderable_get_max_orders_field', ob_get_clean(), $name, $settings );
	}

	/**
	 * Get time slot fields.
	 *
	 * @param string $name
	 * @param array  $settings
	 *
	 * @return mixed|void
	 */
	public static function get_time_slot_fields( $name, $settings = array() ) {
		ob_start();
		?>
		<tr data-orderable-period="time-slots" class="orderable-table__no-td-border" 
		<?php
		if ( 'all-day' === $settings['period'] ) {
			echo 'style="display: none;"';
		}
		?>
		>
			<th class="orderable-table__column orderable-table__column--medium"><?php esc_html_e( 'Hours', 'orderable' ); ?></th>
			<td class="orderable-table__column orderable-table__column--time" rowspan="4" style="text-align: center;">
				<?php echo Orderable_Helpers::get_pro_button( 'time-slots' ); ?>
			</td>
		</tr>
		<tr data-orderable-period="time-slots" 
		<?php
		if ( 'all-day' === $settings['period'] ) {
			echo 'style="display: none;"';
		}
		?>
		>
			<th class="orderable-table__column orderable-table__column--medium"><?php esc_html_e( 'Frequency (Mins)', 'orderable' ); ?></th>
		</tr>
		<tr data-orderable-period="time-slots" 
		<?php
		if ( 'all-day' === $settings['period'] ) {
			echo 'style="display: none;"';
		}
		?>
		>
			<th class="orderable-table__column orderable-table__column--medium"><?php esc_html_e( 'Lead Time (Mins)', 'orderable' ); ?></th>
		</tr>
		<tr data-orderable-period="time-slots" 
		<?php
		if ( 'all-day' === $settings['period'] ) {
			echo 'style="display: none;"';
		}
		?>
		>
			<th class="orderable-table__column orderable-table__column--medium"><?php esc_html_e( 'Max Orders (Slot)', 'orderable' ); ?></th>
		</tr>
		<?php

		return apply_filters( 'orderable_get_time_slot_fields', ob_get_clean(), $name, $settings );
	}
}
