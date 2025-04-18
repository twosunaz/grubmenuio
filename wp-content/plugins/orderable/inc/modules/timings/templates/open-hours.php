<?php
/**
 * Template: Open hours.
 *
 * @package Orderable/Templates
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $args['upcoming_open_hours'] ) ) {
	return;
} ?>

<table class="orderable-open-hours">
	<tbody>
	<?php foreach ( $args['upcoming_open_hours'] as $open_hour ) { ?>
		<tr class="orderable-open-hours__day">
			<th class="orderable-open-hours__day-name">
				<?php if ( $args['date'] ) { ?>
					<span class="orderable-open-hours__date"><?php echo esc_attr( $open_hour['date'] ); ?></span>
				<?php } ?>

				<?php echo esc_attr( $open_hour['day'] ); ?>
			</th>
			<td class="orderable-open-hours__hours">
				<span class="orderable-open-hours__hours-text orderable-open-hours__hours-text--<?php echo $open_hour['is_closed'] ? 'closed' : 'open'; ?>"><?php echo $open_hour['hours']; ?></span>

				<?php if ( $args['services'] && ! empty( $open_hour['services'] ) && ! $open_hour['is_closed'] ) { ?>
					<?php foreach ( $open_hour['services'] as $service => $active ) { ?>
						<span class="orderable-open-hours__service orderable-open-hours__service--<?php echo esc_attr( $service ); ?> orderable-open-hours__service--<?php echo $active ? 'open' : 'closed'; ?>"><?php echo wp_kses_post( Orderable_Services::get_service_label( $service ) ); ?></span>
					<?php } ?>
				<?php } ?>
			</td>
		</tr>
	<?php } ?>
	</tbody>
</table>
