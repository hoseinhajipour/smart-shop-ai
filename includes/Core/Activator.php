<?php
namespace SmartShopAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin activation tasks.
 */
class Activator {

	public static function activate(): void {
		self::create_tables();
		self::set_default_options();
	}

	private static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$logs_table      = $wpdb->prefix . 'ssai_conversation_logs';
		$rules_table     = $wpdb->prefix . 'ssai_ai_rules';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql_logs = "CREATE TABLE {$logs_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session_id varchar(64) NOT NULL DEFAULT '',
			user_message text NOT NULL,
			ai_response text NOT NULL,
			intent varchar(50) DEFAULT NULL,
			extracted_data longtext DEFAULT NULL,
			search_query text DEFAULT NULL,
			products_found longtext DEFAULT NULL,
			mcp_requests longtext DEFAULT NULL,
			response_time_ms int(11) DEFAULT 0,
			error_message text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY session_id (session_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_rules = "CREATE TABLE {$rules_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL DEFAULT '',
			rule_text text NOT NULL,
			priority int(11) NOT NULL DEFAULT 10,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY priority (priority),
			KEY is_active (is_active)
		) {$charset_collate};";

		dbDelta( $sql_logs );
		dbDelta( $sql_rules );
	}

	private static function set_default_options(): void {
		$defaults = array(
			'ai_provider'       => 'openai',
			'ai_endpoint'       => 'https://api.openai.com/v1/chat/completions',
			'ai_api_key'        => '',
			'ai_model'          => 'gpt-4o-mini',
			'ai_temperature'    => 0.7,
			'ai_max_tokens'     => 2048,
			'ai_timeout'        => 30,
			'system_prompt'     => self::default_system_prompt(),
			'mcp_provider'      => 'woocommerce_direct',
			'mcp_endpoint'      => '',
			'mcp_api_key'       => '',
			'attribute_mapping' => array(),
			'capabilities'      => self::default_capabilities(),
			'chatbot_enabled'   => true,
			'chatbot_welcome'   => 'سلام 👋 چه محصولی دنبالش می‌گردی؟',
			'quick_actions'     => self::default_quick_actions(),
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( 'ssai_' . $key ) ) {
				add_option( 'ssai_' . $key, $value );
			}
		}
	}

	private static function default_system_prompt(): string {
		return 'تو دستیار خرید فروشگاه هستی. وظیفه تو کمک به مشتری برای پیدا کردن محصول مناسب است.
هیچ‌وقت اطلاعات محصول را حدس نزن.
اطلاعات قیمت، موجودی و مشخصات را فقط از WooCommerce دریافت کن.
اگر اطلاعات کافی نداری، از مشتری سؤال بپرس.
پاسخ‌ها را به زبان فارسی و با لحن دوستانه و حرفه‌ای بده.';
	}

	private static function default_capabilities(): array {
		return array(
			'search_products'       => true,
			'search_by_attributes'    => true,
			'get_product_details'     => true,
			'check_stock'             => true,
			'check_price'             => true,
			'add_to_cart'             => false,
			'recommend_products'      => true,
			'ask_followup_questions'  => true,
			'compare_products'        => false,
		);
	}

	private static function default_quick_actions(): array {
		return array(
			array( 'icon' => '🚗', 'label' => 'پیدا کردن رینگ', 'query' => 'برای خودرویم رینگ می‌خوام' ),
			array( 'icon' => '🔋', 'label' => 'پیدا کردن باتری', 'query' => 'باتری مناسب خودرویم می‌خوام' ),
			array( 'icon' => '🛞', 'label' => 'پیدا کردن لاستیک', 'query' => 'لاستیک مناسب خودرویم می‌خوام' ),
			array( 'icon' => '🔎', 'label' => 'جستجوی محصول', 'query' => 'جستجوی محصول' ),
		);
	}
}
