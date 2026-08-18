<?php
namespace SmartShopAI\AI;

use SmartShopAI\Core\Settings;
use SmartShopAI\AI\Providers\OpenAIProvider;
use SmartShopAI\AI\Providers\AnthropicProvider;
use SmartShopAI\AI\Providers\GeminiProvider;
use SmartShopAI\AI\Providers\OpenAICompatibleProvider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Factory and orchestrator for AI providers.
 */
class AIService {

	private AIProviderInterface $provider;

	public function __construct() {
		$this->provider = $this->create_provider();
	}

	public function get_provider(): AIProviderInterface {
		return $this->provider;
	}

	private function create_provider(): AIProviderInterface {
		$settings = Settings::get_ai_settings();
		$provider = $settings['provider'];

		switch ( $provider ) {
			case 'anthropic':
				return new AnthropicProvider( $settings );
			case 'gemini':
				return new GeminiProvider( $settings );
			case 'openrouter':
			case 'groq':
			case 'together':
			case 'openai_compatible':
			case 'custom':
				return new OpenAICompatibleProvider( $settings );
			case 'openai':
			default:
				return new OpenAIProvider( $settings );
		}
	}

	/**
	 * Generate a chat response with system prompt and rules.
	 */
	public function chat( array $messages, array $context = array() ): array {
		$system_prompt = $this->build_system_prompt( $context );
		$full_messages = array_merge(
			array( array( 'role' => 'system', 'content' => $system_prompt ) ),
			$messages
		);

		return $this->provider->chat( $full_messages );
	}

	/**
	 * Extract structured intent from user message.
	 */
	public function extract_intent( string $user_message, array $conversation_history = array() ): array {
		$attributes_info = $context_attributes = '';
		$mapping = Settings::get_attribute_mapping();

		if ( ! empty( $mapping ) ) {
			$attributes_info = 'Known attribute mappings: ' . wp_json_encode( $mapping );
		}

		$extract_prompt = <<<PROMPT
Analyze the user's message and extract structured search parameters.
Return ONLY valid JSON with these fields (use null for missing values):
{
  "intent": "smart_search|product_search|followup|general",
  "product_type": "wheel|tire|battery|parts|other|null",
  "vehicle": "normalized vehicle name or null",
  "vehicle_brand": "brand or null",
  "vehicle_model": "model or null",
  "attributes": {
    "size": "wheel/tire size or null",
    "color": "color or null",
    "brand": "product brand or null",
    "style": "style or null",
    "price_max": "max price number or null",
    "price_min": "min price number or null",
    "sort_by": "price_asc|price_desc|relevance or null"
  },
  "search_text": "text for normal product search or null",
  "needs_followup": true/false,
  "followup_question": "question to ask if info is missing or null",
  "confidence": 0.0-1.0
}

Vehicle normalization examples:
- "پژو 206", "206", "Peugeot 206" → "Peugeot 206"
- "سمند", "Samand LX" → "Samand"
- "پراید", "Pride" → "Pride"

Product type detection:
- رینگ, ring, wheel → wheel
- لاستیک, tire → tire
- باتری, battery → battery
- قطعات, parts → parts

{$attributes_info}
PROMPT;

		$messages = array();
		foreach ( $conversation_history as $msg ) {
			$messages[] = array(
				'role'    => $msg['role'],
				'content' => $msg['content'],
			);
		}
		$messages[] = array( 'role' => 'user', 'content' => $user_message );

		$result = $this->provider->chat(
			array_merge(
				array( array( 'role' => 'system', 'content' => $extract_prompt ) ),
				$messages
			),
			array(
				'temperature'    => 0.1,
				'response_format'  => array( 'type' => 'json_object' ),
			)
		);

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'error'   => $result['error'],
				'data'    => array(),
			);
		}

		$parsed = json_decode( $result['content'], true );
		if ( ! is_array( $parsed ) ) {
			// Try to extract JSON from response.
			if ( preg_match( '/\{.*\}/s', $result['content'], $matches ) ) {
				$parsed = json_decode( $matches[0], true );
			}
		}

		return array(
			'success' => true,
			'error'   => '',
			'data'    => is_array( $parsed ) ? $parsed : array(),
		);
	}

	/**
	 * Generate final response with product data.
	 */
	public function generate_response(
		string $user_message,
		array $intent_data,
		array $products,
		array $conversation_history = array()
	): array {
		$products_json = wp_json_encode( $products, JSON_UNESCAPED_UNICODE );
		$intent_json   = wp_json_encode( $intent_data, JSON_UNESCAPED_UNICODE );

		$response_prompt = <<<PROMPT
Based on the user's message and the ACTUAL product data from WooCommerce, generate a helpful response in English.

CRITICAL RULES:
- NEVER invent product information. Only use data from the products list below.
- If no products found, say so honestly and suggest alternatives.
- Include match scores when available.
- Be friendly and professional in English.

User message: {$user_message}

Extracted intent: {$intent_json}

Products from WooCommerce (USE ONLY THIS DATA):
{$products_json}

Respond in English. If products were found, briefly explain why they match.
PROMPT;

		$messages = array();
		foreach ( $conversation_history as $msg ) {
			$messages[] = array( 'role' => $msg['role'], 'content' => $msg['content'] );
		}
		$messages[] = array( 'role' => 'user', 'content' => $response_prompt );

		return $this->chat( $messages );
	}

	private function build_system_prompt( array $context = array() ): string {
		$prompt = Settings::get_system_prompt();

		$rules_manager = new \SmartShopAI\Rules\RulesManager();
		$active_rules  = $rules_manager->get_active_rules();

		if ( ! empty( $active_rules ) ) {
			$prompt .= "\n\nActive AI Rules:\n";
			foreach ( $active_rules as $rule ) {
				$prompt .= "- {$rule['rule_text']}\n";
			}
		}

		if ( ! empty( $context['products'] ) ) {
			$prompt .= "\n\nAvailable products data is provided in the conversation. Never guess product details.";
		}

		return $prompt;
	}

	public function test_connection(): array {
		return $this->provider->test_connection();
	}
}
