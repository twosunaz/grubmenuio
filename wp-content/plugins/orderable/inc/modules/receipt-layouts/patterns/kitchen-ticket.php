<?php
/**
 * Title: Kitchen
 * Description: Kitchen ticket pattern
 * Slug: orderable-receipt-layouts/kitchen-ticket
 *
 * @package Orderable
 */

?>

<!-- wp:columns {"verticalAlignment":null,"isStackedOnMobile":false} -->
<div class="wp-block-columns is-not-stacked-on-mobile has-text-color" style="color:#111111"><!-- wp:column {"verticalAlignment":"top"} -->
<div class="wp-block-column is-vertically-aligned-top has-text-color" style="color:#111111"><!-- wp:orderable/order-number {"label":"#"} /--></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center has-text-color" style="color:#111111"><!-- wp:orderable/order-date-time {"label":"","style":{"color":{"text":"#111111"},"typography":{"textAlign":"center"}}} /--></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center has-text-color" style="color:#111111"><!-- wp:orderable/order-service-type {"label":"","style":{"color":{"text":"#111111"},"typography":{"textAlign":"right"}}} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:orderable/divider {"style":{"color":{"text":"#111111"},"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|50"}}}} /-->

<!-- wp:orderable/order-line-items {"showLabel":false,"showPrices":false} /-->

<!-- wp:orderable/divider {"style":{"color":{"text":"#111111"},"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} /-->

<!-- wp:orderable/order-notes /-->

<!-- wp:orderable/divider {"style":{"color":{"text":"#111111"},"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} /-->

<!-- wp:orderable/order-service-date-time /-->

<!-- wp:paragraph -->
<p class="has-text-color" style="color:#111111"></p>
<!-- /wp:paragraph -->
