<?php
namespace SmartShopAI\AI\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Anthropic Claude API provider.
 */
class AnthropicProvider extends AbstractAIProvider {

	public function chat( array $messages, array $options = array() ): array {
		$system = '';
		$chat_messages = array();

		foreach ( $messages as $msg ) {
			if ( $msg['role'] === 'system' ) {
				$system = $msg['content'];
			} else {
				$chat_messages[] = $msg;
			}
		}

		$body = array(
			'model'      => $options['model'] ?? $this->model,
			'max_tokens' => $options['max_tokens'] ?? $this->max_tokens,
			'messages'   => $chat_messages,
		);

		if ( $system ) {
			$body['system'] = $system;
		}

		$headers = array(
			'x-api-key'         => $this->api_key,
			'anthropic-version' => '2023-06-01',
		);

		// Override auth header for Anthropic.
		$response = wp_remote_post(
			$this->endpoint ?: 'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => $this->timeout,
				'headers' => array_merge(
					array( 'Content-Type' => 'application/json' ),
					$headers
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'content' => '',
				'error'   => $response->get_error_message(),
				'raw'     => array(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$error = $body['error']['message'] ?? "HTTP {$code}";
			return array(
				'success' => false,
				'content' => '',
				'error'   => $error,
				'raw'     => $body ?: array(),
			);
		}

		return array(
			'success' => true,
			'content' => $this->extract_content( $body ),
			'error'   => '',
			'raw'     => $body,
		);
	}

	protected function extract_content( array $body ): string {
		if ( ! empty( $body['content'] ) && is_array( $body['content'] ) ) {
			foreach ( $body['content'] as $block ) {
				if ( $block['type'] === 'text' ) {
					return $block['text'];
				}
			}
		}
		return '';
	}
}
