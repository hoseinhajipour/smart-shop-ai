<?php
namespace SmartShopAI\Search;

use SmartShopAI\MCP\MCPService;
use SmartShopAI\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Routes search requests to appropriate strategy.
 */
class SearchRouter {

	private SmartSearch $smart_search;
	private NormalSearch $normal_search;
	private MCPService $mcp;

	public function __construct() {
		$this->smart_search  = new SmartSearch();
		$this->normal_search = new NormalSearch();
		$this->mcp           = new MCPService();
	}

	/**
	 * Execute search based on parsed intent.
	 */
	public function search( array $intent ): array {
		$search_type = $this->determine_search_type( $intent );

		switch ( $search_type ) {
			case 'product_search':
				$result = $this->normal_search->search( $intent );
				break;
			case 'attribute_search':
				$result = $this->smart_search->search( $intent );
				break;
			default:
				$result = $this->smart_search->search( $intent );
		}

		$result['search_type'] = $search_type;
		$result['search_query'] = $this->build_search_query_string( $intent, $search_type );

		return $result;
	}

	private function determine_search_type( array $intent ): string {
		$attrs = $intent['attributes'] ?? array();

		// Vehicle fitment specs → attribute-based search (PCD, ET, size filters).
		if ( ! empty( $attrs['pcd'] ) || ! empty( $intent['vehicle'] ) ) {
			return 'attribute_search';
		}

		if ( ! empty( $intent['search_text'] ) ) {
			return 'product_search';
		}

		if ( ! empty( $attrs ) ) {
			return 'attribute_search';
		}

		return 'smart_search';
	}

	private function build_search_query_string( array $intent, string $type ): string {
		if ( $type === 'product_search' ) {
			return $intent['search_text'] ?? '';
		}

		$parts = array();
		if ( $intent['product_type'] ) {
			$parts[] = $intent['product_type'];
		}
		if ( $intent['vehicle'] ) {
			$parts[] = $intent['vehicle'];
		}
		if ( ! empty( $intent['attributes'] ) ) {
			foreach ( $intent['attributes'] as $key => $val ) {
				if ( $val ) {
					$parts[] = "{$key}:{$val}";
				}
			}
		}

		return implode( ' | ', $parts );
	}
}
