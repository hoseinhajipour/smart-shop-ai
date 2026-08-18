<?php
namespace SmartShopAI\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contract for AI providers.
 */
interface AIProviderInterface {

	/**
	 * Send a chat completion request.
	 *
	 * @param array $messages Array of {role, content} messages.
	 * @param array $options  Provider-specific options.
	 * @return array{success: bool, content: string, error: string, raw: array}
	 */
	public function chat( array $messages, array $options = array() ): array;

	/**
	 * Test connection to the AI provider.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function test_connection(): array;
}
