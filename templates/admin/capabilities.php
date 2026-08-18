<?php
/**
 * Capabilities page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$capabilities = array(
	'search_products'       => __( 'Search Products', 'smart-shop-ai' ),
	'search_by_attributes'  => __( 'Search by Attributes', 'smart-shop-ai' ),
	'get_product_details'   => __( 'Get Product Details', 'smart-shop-ai' ),
	'check_stock'           => __( 'Check Stock', 'smart-shop-ai' ),
	'check_price'           => __( 'Check Price', 'smart-shop-ai' ),
	'add_to_cart'           => __( 'Add to Cart', 'smart-shop-ai' ),
	'recommend_products'    => __( 'Recommend Products', 'smart-shop-ai' ),
	'ask_followup_questions'=> __( 'Ask Follow-up Questions', 'smart-shop-ai' ),
	'compare_products'      => __( 'Compare Products', 'smart-shop-ai' ),
);
?>
<div class="wrap ssai-admin-wrap">
	<h1><?php esc_html_e( 'AI Capabilities', 'smart-shop-ai' ); ?></h1>
	<p><?php esc_html_e( 'Control what the AI assistant is allowed to do.', 'smart-shop-ai' ); ?></p>

	<form id="ssai-capabilities-form" class="ssai-form">
		<table class="form-table">
			<?php foreach ( $capabilities as $key => $label ) : ?>
			<tr>
				<th><?php echo esc_html( $label ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1" class="ssai-capability" data-key="<?php echo esc_attr( $key ); ?>" />
						<?php esc_html_e( 'Enabled', 'smart-shop-ai' ); ?>
					</label>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Capabilities', 'smart-shop-ai' ); ?></button>
		</p>
	</form>
</div>
