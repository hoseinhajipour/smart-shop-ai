<?php
namespace SmartShopAI\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce MCP remote provider.
 */
class WooCommerceMCPProvider implements MCPProviderInterface {

	private string $endpoint;
	private string $api_key;
	private int $timeout;

	public function __construct( array $settings ) {
		$this->endpoint = rtrim( $settings['endpoint'] ?? '', '/' );
		$this->api_key  = $settings['api_key'] ?? '';
		$this->timeout  = 30;
	}

	public function search_products( array $params ): array {
		return $this->call_tool( 'search_products', $params );
	}

	public function get_product( int $product_id ): array {
		$result = $this->call_tool( 'get_product', array( 'id' => $product_id ) );
		if ( $result['success'] && ! empty( $result['products'] ) ) {
			return array(
				'success' => true,
				'product' => $result['products'][0] ?? null,
				'error'   => '',
			);
		}
		return array( 'success' => false, 'product' => null, 'error' => $result['error'] );
	}

	public function search_by_attributes( array $filters, int $limit = 20 ): array {
		return $this->call_tool( 'search_by_attributes', array(
			'filters' => $filters,
			'limit'   => $limit,
		) );
	}

	public function test_connection(): array {
		$result = $this->call_tool( 'ping', array() );
		if ( $result['success'] ) {
			return array( 'success' => true, 'message' => 'MCP connection successful.' );
		}
		return array( 'success' => false, 'message' => $result['error'] ?: 'MCP connection failed.' );
	}

	/**
	 * Call an MCP tool via HTTP.
	 */
	private function call_tool( string $tool, array $arguments ): array {
		if ( empty( $this->endpoint ) ) {
			return array(
				'success'  => false,
				'products' => array(),
				'error'    => 'MCP endpoint not configured.',
			);
		}

		$body = array(
			'jsonrpc' => '2.0',
			'id'      => 1,
			'method'  => 'tools/call',
			'params'  => array(
				'name'      => $tool,
				'arguments' => $arguments,
			),
		);

		$headers = array( 'Content-Type' => 'application/json' );
		if ( $this->api_key ) {
			$headers['Authorization'] = 'Bearer ' . $this->api_key;
		}

		$response = wp_remote_post(
			$this->endpoint,
			array(
				'timeout' => $this->timeout,
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success'  => false,
				'products' => array(),
				'error'    => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			return array(
				'success'  => false,
				'products' => array(),
				'error'    => $data['error']['message'] ?? "HTTP {$code}",
			);
		}

		// Parse MCP response - support multiple formats.
		$products = array();
		if ( isset( $data['result']['content'] ) ) {
			foreach ( $data['result']['content'] as $item ) {
				if ( $item['type'] === 'text' ) {
					$parsed = json_decode( $item['text'], true );
					if ( is_array( $parsed ) ) {
						$products = isset( $parsed['products'] ) ? $parsed['products'] : $parsed;
					}
				}
			}
		} elseif ( isset( $data['result']['products'] ) ) {
			$products = $data['result']['products'];
		} elseif ( isset( $data['products'] ) ) {
			$products = $data['products'];
		}

		return array(
			'success'  => true,
			'products' => is_array( $products ) ? $products : array(),
			'error'    => '',
		);
	}
}
