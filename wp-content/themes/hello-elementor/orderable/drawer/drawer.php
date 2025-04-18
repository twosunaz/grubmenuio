<?php
/**
 * Drawer: Main.
 * @package Orderable/Templates
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="orderable-drawer">
	<button class="orderable-drawer__close" data-orderable-trigger="drawer.close"><?php _e( 'Close', 'orderable' ); ?></button>

	<div class="orderable-drawer__inner orderable-drawer__html"></div>
	<div class="orderable-drawer__inner orderable-drawer__cart">
		<h3><?php _e( 'Your Order', 'orderable' ); ?></h3>
		<div class="orderable-mini-cart-wrapper">
			<?php Orderable_Drawer::mini_cart(); ?>
		</div>
	</div>
</div>
// <script>
// document.addEventListener("DOMContentLoaded", function () {
// 	console.log("🧪 DOMContentLoaded fired");

// 	const observer = new MutationObserver(() => {
// 		if (!document.body.classList.contains("orderable-drawer-open")) return;

// 		console.log("🟢 Drawer opened");

// 		setTimeout(() => {
// 			const drawer = document.querySelector(".orderable-drawer__inner.orderable-drawer__html");
// 			if (!drawer) {
// 				console.log("❌ Drawer content not found");
// 				return;
// 			}

// 			const optionsTable = drawer.querySelector(".orderable-product__options tbody");
// 			if (!optionsTable) {
// 				console.log("❌ Options table not found");
// 				return;
// 			}

// 			const sizeSelect = optionsTable.querySelector('select[name="attribute_size"]');
// 			if (!sizeSelect) {
// 				console.log("❌ Size select not found");
// 				return;
// 			}

// 			const sizeRow = sizeSelect.closest("tr");
// 			if (!sizeRow) {
// 				console.log("❌ Size row not found");
// 				return;
// 			}

// 			optionsTable.insertBefore(sizeRow, optionsTable.firstChild);
// 			console.log("✅ Size option row moved to top");
// 		}, 500); // More time in case DOM is async-rendered
// 	});

// 	observer.observe(document.body, {
// 		attributes: true,
// 		attributeFilter: ["class"]
// 	});
// });
// </script>
