<?php
namespace SmartShopAI\AI\Providers;

use SmartShopAI\AI\AIProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replicate API provider (official models such as openai/gpt-5.1).
 *
 * @see https://replicate.com/docs/reference/http
 * @see https://replicate.com/openai/gpt-5.1/api/schema
 */
class ReplicateProvider implements AIProviderInterface {

	private const API_BASE = 'https://api.replicate.com/v1';

	protected string $endpoint;
	protected string $api_key;
	protected string $model;
	protected int $max_tokens;
	protected int $timeout;
	protected string $verbosity;
	protected string $reasoning_effort;

	public function __construct( array $settings ) {
		$this->endpoint         = $settings['endpoint'] ?? self::API_BASE . '/predictions';
		$this->api_key          = $settings['api_key'] ?? '';
		$this->model            = $settings['model'] ?? 'openai/gpt-5.1';
		$this->max_tokens       = (int) ( $settings['max_tokens'] ?? 2048 );
		$this->timeout          = (int) ( $settings['timeout'] ?? 60 );
		$this->verbosity        = $settings['verbosity'] ?? 'medium';
		$this->reasoning_effort = $settings['reasoning_effort'] ?? 'none';
	}

	public function chat( array $messages, array $options = array() ): array {
		if ( empty( $this->api_key ) ) {
			return array(
				'success' => false,
				'content' => '',
				'error'   => 'Replicate API token is not configured.',
				'raw'     => array(),
			);
		}

		if ( empty( $this->model ) ) {
			return array(
				'success' => false,
				'content' => '',
				'error'   => 'Replicate model is not configured. Use owner/model format, e.g. openai/gpt-5.1.',
				'raw'     => array(),
			);
		}

		$input = $this->build_input( $messages, $options );
		$body  = array(
			'version' => $this->model,
			'input'   => $input,
		);

		$wait_seconds = min( 60, max( 1, $this->timeout ) );
		$response     = wp_remote_post(
			$this->get_predictions_endpoint(),
			array(
				'timeout' => $this->timeout + 15,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
					'Prefer'        => 'wait=' . $wait_seconds,
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

		$code        = wp_remote_retrieve_response_code( $response );
		$prediction  = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$error = $prediction['detail'] ?? $prediction['error'] ?? $prediction['message'] ?? "HTTP {$code}";
			if ( is_array( $error ) ) {
				$error = wp_json_encode( $error );
			}
			return array(
				'success' => false,
				'content' => '',
				'error'   => (string) $error,
				'raw'     => is_array( $prediction ) ? $prediction : array(),
			);
		}

		if ( is_array( $prediction ) && ! empty( $prediction['id'] ) && ! $this->is_terminal_status( $prediction['status'] ?? '' ) ) {
			$prediction = $this->poll_prediction( (string) $prediction['id'] );
			if ( is_wp_error( $prediction ) ) {
				return array(
					'success' => false,
					'content' => '',
					'error'   => $prediction->get_error_message(),
					'raw'     => array(),
				);
			}
		}

		if ( ( $prediction['status'] ?? '' ) === 'failed' ) {
			return array(
				'success' => false,
				'content' => '',
				'error'   => (string) ( $prediction['error'] ?? 'Replicate prediction failed.' ),
				'raw'     => $prediction,
			);
		}

		$content = $this->extract_output( $prediction['output'] ?? null );
		if ( '' === $content ) {
			return array(
				'success' => false,
				'content' => '',
				'error'   => 'Replicate returned an empty response.',
				'raw'     => $prediction,
			);
		}

		return array(
			'success' => true,
			'content' => $content,
			'error'   => '',
			'raw'     => $prediction,
		);
	}

	public function test_connection(): array {
		$result = $this->chat(
			array(
				array( 'role' => 'user', 'content' => 'Reply with the single word: ok' ),
			),
			array( 'max_tokens' => 16 )
		);

		if ( $result['success'] ) {
			return array( 'success' => true, 'message' => 'Replicate connection successful.' );
		}

		return array( 'success' => false, 'message' => $result['error'] );
	}

	/**
	 * @param array<int, array{role:string,content:string}> $messages
	 * @param array<string, mixed>                         $options
	 * @return array<string, mixed>
	 */
	private function build_input( array $messages, array $options ): array {
		$system_prompt = '';
		$chat_messages = array();

		foreach ( $messages as $message ) {
			$role    = (string) ( $message['role'] ?? '' );
			$content = (string) ( $message['content'] ?? '' );

			if ( '' === $content ) {
				continue;
			}

			if ( 'system' === $role ) {
				$system_prompt .= ( '' === $system_prompt ? '' : "\n\n" ) . $content;
				continue;
			}

			$chat_messages[] = array(
				'role'    => 'assistant' === $role ? 'assistant' : 'user',
				'content' => $content,
			);
		}

		if ( ! empty( $options['response_format']['type'] ) && 'json_object' === $options['response_format']['type'] ) {
			$system_prompt .= ( '' === $system_prompt ? '' : "\n\n" ) . 'You must respond with valid JSON only. Do not wrap the JSON in markdown fences.';
		}

		$input = array(
			'messages'              => $chat_messages,
			'system_prompt'         => $system_prompt,
			'verbosity'             => $this->verbosity,
			'reasoning_effort'      => $this->reasoning_effort,
			'max_completion_tokens' => (int) ( $options['max_tokens'] ?? $this->max_tokens ),
		);

		if ( empty( $chat_messages ) && ! empty( $system_prompt ) ) {
			$input['prompt'] = $system_prompt;
			unset( $input['messages'], $input['system_prompt'] );
		}

		return $input;
	}

	private function get_predictions_endpoint(): string {
		if ( ! empty( $this->endpoint ) ) {
			return $this->endpoint;
		}

		if ( false !== strpos( $this->model, '/' ) ) {
			return self::API_BASE . '/models/' . $this->model . '/predictions';
		}

		return self::API_BASE . '/predictions';
	}

	/**
	 * @return array<string, mixed>|\WP_Error
	 */
	private function poll_prediction( string $prediction_id ) {
		$url          = self::API_BASE . '/predictions/' . rawurlencode( $prediction_id );
		$max_attempts = max( 5, $this->timeout );

		for ( $attempt = 0; $attempt < $max_attempts; $attempt++ ) {
			$response = wp_remote_get(
				$url,
				array(
					'timeout' => 15,
					'headers' => array(
						'Authorization' => 'Bearer ' . $this->api_key,
						'Content-Type'  => 'application/json',
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code       = wp_remote_retrieve_response_code( $response );
			$prediction = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $code < 200 || $code >= 300 || ! is_array( $prediction ) ) {
				return new \WP_Error( 'ssai_replicate_poll', 'Failed to poll Replicate prediction.' );
			}

			if ( $this->is_terminal_status( $prediction['status'] ?? '' ) ) {
				return $prediction;
			}

			sleep( 1 );
		}

		return new \WP_Error( 'ssai_replicate_timeout', 'Replicate prediction timed out.' );
	}

	private function is_terminal_status( string $status ): bool {
		return in_array( $status, array( 'succeeded', 'failed', 'canceled' ), true );
	}

	/**
	 * @param mixed $output
	 */
	private function extract_output( $output ): string {
		if ( is_string( $output ) ) {
			return trim( $output );
		}

		if ( is_array( $output ) ) {
			if ( isset( $output['content'] ) && is_string( $output['content'] ) ) {
				return trim( $output['content'] );
			}

			if ( isset( $output['text'] ) && is_string( $output['text'] ) ) {
				return trim( $output['text'] );
			}

			$parts = array();
			foreach ( $output as $item ) {
				if ( is_string( $item ) ) {
					$parts[] = $item;
					continue;
				}

				if ( is_array( $item ) ) {
					if ( isset( $item['content'] ) && is_string( $item['content'] ) ) {
						$parts[] = $item['content'];
					} elseif ( isset( $item['text'] ) && is_string( $item['text'] ) ) {
						$parts[] = $item['text'];
					}
				}
			}

			return trim( implode( '', $parts ) );
		}

		return '';
	}
}
