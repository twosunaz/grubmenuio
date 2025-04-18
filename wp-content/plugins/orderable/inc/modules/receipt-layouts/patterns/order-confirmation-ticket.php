<?php
/**
 * Title: Order Confirmation
 * Description: Order Confirmation ticket pattern
 * Slug: orderable-receipt-layouts/order-confirmation-ticket
 *
 * @package Orderable
 */

?>

<!-- wp:columns {"verticalAlignment":"top","style":{"color":{"text":"#111111"},"spacing":{"margin":{"bottom":"-11px"}}}} -->
<div class="wp-block-columns are-vertically-aligned-top has-text-color" style="color:#111111;margin-bottom:-11px"><!-- wp:column {"verticalAlignment":"top"} -->
<div class="wp-block-column is-vertically-aligned-top has-text-color" style="color:#111111"><!-- wp:orderable/order-number {"label":"Order Number #"} /--></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"top"} -->
<div class="wp-block-column is-vertically-aligned-top has-text-color" style="color:#111111"><!-- wp:orderable/order-date-time {"label":"Order Date/Time: "} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:orderable/customer-name /-->

<!-- wp:orderable/divider {"style":{"color":{"text":"#111111"},"spacing":{"margin":{"bottom":"var:preset|spacing|50","top":"var:preset|spacing|50"}}}} /-->

<!-- wp:orderable/order-line-items {"showLabel":false,"showPrices":false,"showMetaData":true} /-->

<!-- wp:orderable/order-total-items /-->

<!-- wp:orderable/order-totals {"style":{"color":{"text":"#111111"},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}}}} /-->

<!-- wp:orderable/divider {"style":{"color":{"text":"#111111"},"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} /-->

<!-- wp:orderable/order-payment-method /-->

<!-- wp:orderable/customer-billing-details {"showPhone":true,"showEmail":true} /-->

<!-- wp:orderable/customer-shipping-details /-->

<!-- wp:orderable/divider {"style":{"color":{"text":"#111111"},"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} /-->

<!-- wp:orderable/order-service-type /-->

<!-- wp:orderable/order-location /-->

<!-- wp:orderable/order-service-date-time /-->

<!-- wp:orderable/divider {"style":{"color":{"text":"#111111"},"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} /-->

<!-- wp:orderable/order-notes /-->

<!-- wp:orderable/divider {"style":{"color":{"text":"#111111"},"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} /-->

<!-- wp:orderable/order-meta-fields /-->

<!-- wp:orderable/divider {"style":{"color":{"text":"#111111"},"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} /-->
