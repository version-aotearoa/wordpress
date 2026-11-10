<?php
defined( 'ABSPATH' ) || exit;
?>
<dialog class="wcw-qv" data-wcw-dialog aria-label="<?php esc_attr_e( 'Quick view', 'woocommerce-works' ); ?>">
	<div class="wcw-qv-shell">
		<div class="wcw-qv-bar">
			<div class="wcw-qv-nav-group">
				<button type="button" class="wcw-qv-nav" data-wcw-prev aria-label="<?php esc_attr_e( 'Previous work', 'woocommerce-works' ); ?>">&lsaquo;</button>
				<button type="button" class="wcw-qv-nav" data-wcw-next aria-label="<?php esc_attr_e( 'Next work', 'woocommerce-works' ); ?>">&rsaquo;</button>
			</div>
			<button type="button" class="wcw-qv-close" data-wcw-close aria-label="<?php esc_attr_e( 'Close', 'woocommerce-works' ); ?>">&times;</button>
		</div>
		<div class="wcw-qv-body" data-wcw-body aria-live="polite"></div>
	</div>
</dialog>
