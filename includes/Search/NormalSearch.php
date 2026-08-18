<?php
namespace SmartShopAI\Search;

use SmartShopAI\MCP\MCPService;
use SmartShopAI\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Standard WooCommerce text search.
 */
class NormalSearch {

	private MCPService $mcp;

	public function __construct() {
		$this->mcp = new MCPService();
	}

	public function search( array $intent ): array {
		if ( ! Settings::is_capability_enabled( 'search_products' ) ) {
			return array(
				'success'  => false,
				'products' => array(),
				'error'    => 'Product search is disabled.',
			);
		}

		$search_text = $intent['search_text'] ?? '';
		if ( empty( $search_text ) ) {
			return array(
				'success'  => false,
				'products' => array(),
				'error'    => 'No search text provided.',
			);
		}

		$mcp_result = $this->mcp->search_products( array(
			'intent'       => 'smart_search',
			'search_text'  => $search_text,
			'product_type' => $intent['product_type'] ?? null,
			'vehicle'      => $intent['vehicle'] ?? null,
			'attributes'   => $intent['attributes'] ?? array(),
			'limit'        => 20,
		) );

		return array(
			'success'  => $mcp_result['success'],
			'products' => $mcp_result['products'] ?? array(),
			'error'    => $mcp_result['error'] ?? '',
			'mcp_log'  => $this->mcp->get_request_log(),
		);
	}
}
