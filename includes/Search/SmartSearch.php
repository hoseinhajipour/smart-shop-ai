<?php
namespace SmartShopAI\Search;

use SmartShopAI\MCP\MCPService;
use SmartShopAI\Recommendation\ProductRanker;
use SmartShopAI\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI-driven smart search using attributes and intent.
 */
class SmartSearch {

	private MCPService $mcp;
	private ProductRanker $ranker;

	public function __construct() {
		$this->mcp    = new MCPService();
		$this->ranker = new ProductRanker();
	}

	public function search( array $intent ): array {
		if ( ! Settings::is_capability_enabled( 'search_products' ) ) {
			return array(
				'success'  => false,
				'products' => array(),
				'error'    => 'Product search is disabled.',
			);
		}

		$params = array(
			'intent'       => 'smart_search',
			'product_type' => $intent['product_type'],
			'vehicle'      => $intent['vehicle'],
			'attributes'   => $intent['attributes'] ?? array(),
			'search_text'  => $intent['search_text'],
			'limit'        => 20,
		);

		$mcp_result = $this->mcp->search_products( $params );

		if ( ! $mcp_result['success'] ) {
			return array(
				'success'  => false,
				'products' => array(),
				'error'    => $mcp_result['error'],
				'mcp_log'  => $this->mcp->get_request_log(),
			);
		}

		$products = $mcp_result['products'];

		// Filter out of stock if capability requires.
		if ( Settings::is_capability_enabled( 'check_stock' ) ) {
			$products = array_filter( $products, function ( $p ) {
				return ! empty( $p['in_stock'] );
			} );
		}

		// Rank products.
		if ( Settings::is_capability_enabled( 'recommend_products' ) ) {
			$products = $this->ranker->rank( $products, $intent );
		}

		// Apply price filters.
		$attrs = $intent['attributes'] ?? array();
		if ( ! empty( $attrs['price_max'] ) ) {
			$max = (float) $attrs['price_max'];
			$products = array_filter( $products, function ( $p ) use ( $max ) {
				return (float) $p['price'] <= $max;
			} );
		}
		if ( ! empty( $attrs['price_min'] ) ) {
			$min = (float) $attrs['price_min'];
			$products = array_filter( $products, function ( $p ) use ( $min ) {
				return (float) $p['price'] >= $min;
			} );
		}

		// Sort by price if requested.
		if ( ! empty( $attrs['sort_by'] ) ) {
			$products = $this->sort_products( $products, $attrs['sort_by'] );
		}

		return array(
			'success'  => true,
			'products' => array_values( $products ),
			'error'    => '',
			'mcp_log'  => $this->mcp->get_request_log(),
		);
	}

	private function sort_products( array $products, string $sort_by ): array {
		if ( $sort_by === 'price_asc' ) {
			usort( $products, function ( $a, $b ) {
				return (float) $a['price'] <=> (float) $b['price'];
			} );
		} elseif ( $sort_by === 'price_desc' ) {
			usort( $products, function ( $a, $b ) {
				return (float) $b['price'] <=> (float) $a['price'];
			} );
		}
		return $products;
	}
}
