<?php
/**
 * Plugin Name: Remove WooCommerce Billing Fields
 * Description: Removes billing address fields on checkout for WooCommerce.
 * Version: 1.0
 * Author: Your Name
 */

function remove_billing_fields_for_checkout( $fields ) {
    unset( $fields['billing']['billing_address_1'] );
    unset( $fields['billing']['billing_address_2'] );
    unset( $fields['billing']['billing_city'] );
    unset( $fields['billing']['billing_state'] );
    unset( $fields['billing']['billing_postcode'] );
    unset( $fields['billing']['billing_country'] );
    return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'remove_billing_fields_for_checkout' );