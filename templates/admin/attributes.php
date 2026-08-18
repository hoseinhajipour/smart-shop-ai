<?php
/**
 * Attribute Mapping page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ssai-admin-wrap">
	<h1><?php esc_html_e( 'Product Attribute Mapping', 'smart-shop-ai' ); ?></h1>
	<p><?php esc_html_e( 'Map semantic attribute names to your WooCommerce global attributes or product categories.', 'smart-shop-ai' ); ?></p>

	<div id="ssai-attributes-loading"><?php esc_html_e( 'Loading attributes...', 'smart-shop-ai' ); ?></div>

	<form id="ssai-attributes-form" class="ssai-form" style="display:none;">
		<table class="form-table" id="ssai-mapping-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Semantic Name', 'smart-shop-ai' ); ?></th>
					<th><?php esc_html_e( 'WooCommerce Attribute / Category', 'smart-shop-ai' ); ?></th>
				</tr>
			</thead>
			<tbody id="ssai-mapping-body"></tbody>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Mapping', 'smart-shop-ai' ); ?></button>
		</p>
	</form>

	<h2><?php esc_html_e( 'Discovered Attributes', 'smart-shop-ai' ); ?></h2>
	<div id="ssai-discovered-attributes"></div>
</div>
