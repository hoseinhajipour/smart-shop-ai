<?php
namespace SmartShopAI\MCP;

use SmartShopAI\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Factory for MCP providers.
 */
class MCPService {

	private MCPProviderInterface $provider;

	/** @var array */
	private $request_log = array();

	public function __construct() {
		$this->provider = $this->create_provider();
	}

	public function get_provider(): MCPProviderInterface {
		return $this->provider;
	}

	private function create_provider(): MCPProviderInterface {
		$settings = Settings::get_mcp_settings();

		if ( $settings['provider'] === 'woocommerce_mcp' && ! empty( $settings['endpoint'] ) ) {
			return new WooCommerceMCPProvider( $settings );
		}

		return new WooCommerceDirectProvider();
	}

	public function search_products( array $params ): array {
		$this->log_request( 'search_products', $params );
		$result = $this->provider->search_products( $params );
		$this->log_response( 'search_products', $result );
		return $result;
	}

	public function get_product( int $product_id ): array {
		$this->log_request( 'get_product', array( 'id' => $product_id ) );
		$result = $this->provider->get_product( $product_id );
		$this->log_response( 'get_product', $result );
		return $result;
	}

	public function search_by_attributes( array $filters, int $limit = 20 ): array {
		$this->log_request( 'search_by_attributes', array( 'filters' => $filters, 'limit' => $limit ) );
		$result = $this->provider->search_by_attributes( $filters, $limit );
		$this->log_response( 'search_by_attributes', $result );
		return $result;
	}

	public function test_connection(): array {
		return $this->provider->test_connection();
	}

	public function get_request_log(): array {
		return $this->request_log;
	}

	private function log_request( string $method, array $params ): void {
		$this->request_log[] = array(
			'type'   => 'request',
			'method' => $method,
			'params' => $params,
			'time'   => current_time( 'mysql' ),
		);
	}

	private function log_response( string $method, array $result ): void {
		$this->request_log[] = array(
			'type'    => 'response',
			'method'  => $method,
			'success' => $result['success'] ?? false,
			'count'   => isset( $result['products'] ) ? count( $result['products'] ) : 0,
			'time'    => current_time( 'mysql' ),
		);
	}
}
