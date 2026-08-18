<?php
namespace SmartShopAI\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contract for MCP / product data providers.
 */
interface MCPProviderInterface {

	/**
	 * Search products.
	 *
	 * @param array $params Search parameters.
	 * @return array{success: bool, products: array, error: string}
	 */
	public function search_products( array $params ): array;

	/**
	 * Get a single product by ID.
	 */
	public function get_product( int $product_id ): array;

	/**
	 * Search by attribute filters.
	 */
	public function search_by_attributes( array $filters, int $limit = 20 ): array;

	/**
	 * Test connection.
	 */
	public function test_connection(): array;
}
