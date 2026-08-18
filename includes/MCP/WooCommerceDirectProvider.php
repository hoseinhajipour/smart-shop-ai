<?php
namespace SmartShopAI\MCP;

use SmartShopAI\Core\Settings;
use SmartShopAI\WooCommerce\ProductSearcher;
use SmartShopAI\WooCommerce\WooCommerceHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Direct WooCommerce access (fallback when MCP is not configured).
 */
class WooCommerceDirectProvider implements MCPProviderInterface {

	private ProductSearcher $searcher;

	public function __construct() {
		$this->searcher = new ProductSearcher();
	}

	public function search_products( array $params ): array {
		$products = $this->searcher->search( $params );
		return array(
			'success'  => true,
			'products' => $products,
			'error'    => '',
		);
	}

	public function get_product( int $product_id ): array {
		$product = WooCommerceHelper::format_product( wc_get_product( $product_id ) );
		if ( ! $product ) {
			return array( 'success' => false, 'product' => null, 'error' => 'Product not found.' );
		}
		return array( 'success' => true, 'product' => $product, 'error' => '' );
	}

	public function search_by_attributes( array $filters, int $limit = 20 ): array {
		$products = $this->searcher->search_by_attributes( $filters, $limit );
		return array(
			'success'  => true,
			'products' => $products,
			'error'    => '',
		);
	}

	public function test_connection(): array {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array( 'success' => false, 'message' => 'WooCommerce is not active.' );
		}

		$count = wp_count_posts( 'product' );
		$total = isset( $count->publish ) ? (int) $count->publish : 0;

		return array(
			'success' => true,
			'message' => sprintf( 'WooCommerce connected. %d published products.', $total ),
		);
	}
}
