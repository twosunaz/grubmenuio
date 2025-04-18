<?php
/**
 * Receipt Layouts CSS
 *
 * @package orderable
 */

?>

<?php
/**
 * WordPress default preset.
 *
 * @see https://developer.wordpress.org/themes/global-settings-and-styles/settings/spacing/#spacing-scale-and-sizes
 */
?>
:root {
	--wp--preset--spacing--20: 5.28pt;
	--wp--preset--spacing--30: 8.04pt;
	--wp--preset--spacing--40: 12pt;
	--wp--preset--spacing--50: 18pt;
	--wp--preset--spacing--60: 27pt;
	--wp--preset--spacing--70: 40.59pt;
	--wp--preset--spacing--80: 60.75pt;
}

html {
	font-family: "Helvetica Neue", sans-serif;
	font-size: 12pt;
	color: #000;
}

@media print {
	@page {
		margin-top: 0;
		margin-bottom: 0;
	}

	body {
		margin-top: 40pt;
		margin-bottom: 40pt;
	}
}

body {
	max-width: 768px;
}

.wp-block-orderable-receipt-layouts,
.wp-block-orderable-order-meta__metadata-item {
	margin-bottom: 7.5pt;
	line-height: 18pt;
}

.wp-block-orderable-receipt-layouts__label {
	font-weight: 600;
}

.is-layout-flex {
	display: flex;
	gap: 24px;
}

.wp-block-columns.is-not-stacked-on-mobile {
  flex-wrap:nowrap !important;
}

.wp-block-columns.is-not-stacked-on-mobile > .wp-block-column {
  flex-basis:0;
  flex-grow:1;
}

.has-text-align-center {
  text-align:center;
}

.has-text-align-left {
  text-align:left;
}

.has-text-align-right {
  text-align:right;
}

.wp-block-image.is-style-rounded img {
	border-radius: 9999px;
}

.wp-block-columns:not(.is-not-stacked-on-mobile)>.wp-block-column {
	flex-basis: 0;
	flex-grow: 1;
}

figure.wp-block-table {
	margin: 0;
}

.wp-block-table table {
	display: table;
	position: relative;
	width: 100%;
	border-spacing: 0;
}

.wp-block-table thead th {
	border-bottom: 2px solid #000;
	padding: 9pt 14pt;
	text-align: left;
	font-weight: 700;
}

.wp-block-table.is-style-stripes tbody tr:nth-child(2n) {
	background-color: #f3f4f6;
}

.wp-block-table tbody td {
	border-bottom: 1px solid #000;
	padding: 9pt 14pt;
}

.wp-block-table tfoot td {
	font-weight: 700;
	border-top: 1px solid #000;
	padding: 9pt 14pt;
	text-align: left;
}

.wp-block-orderable-customer-billing-details__address,
.wp-block-orderable-customer-shipping-details__address{
	margin-bottom: 3pt;
}

.wp-block-orderable-customer-billing-details__phone {
	margin: 3pt 0;
}

.wp-block-orderable-customer-billing-details__email {
	margin: 3pt 0;
}

.wp-block-orderable-order-line-item {
	margin-bottom: 3pt;
}

.wp-block-orderable-order-line-item__data {
	display: flex;
	justify-content: space-between;
	max-width: 375pt;
	align-items: flex-start;
}

.wp-block-orderable-order-line-item__wrapper-name {
	display: inline-flex;
	flex-direction: column;
}

.wp-block-orderable-order-line-item__name,
.wp-block-orderable-order-line-item__metadata {
	margin-left: 5pt;
}

.wp-block-orderable-order-line-item__checkbox {
	width: 13.5pt;
	height: 13.5pt;
	border: 1px solid #111111;
}

.wp-block-orderable-order-line-items .wc-item-meta {
	list-style: none;
	margin: 0 0;
	padding: 0;
}

.wp-block-orderable-order-line-items .wc-item-meta .wc-item-meta-label {
	font-weight: normal;
}

.wp-block-orderable-order-line-items .wc-item-meta li p{
	display: inline;
}

.wp-block-orderable-order-line-item__subtotal {
	margin-left: 15pt;
}

.wp-block-orderable-order-totals {
	background-color: rgb(245, 245, 245);
	box-sizing: border-box;
	padding: 7.5pt;
}

.wp-block-orderable-order-totals__item {
	display: flex;
	justify-content: space-between;
}

.wp-block-orderable-order-meta-fields {
	margin-bottom: 0;
}
