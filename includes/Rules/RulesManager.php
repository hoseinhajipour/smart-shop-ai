<?php
namespace SmartShopAI\Rules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages AI rules stored in database.
 */
class RulesManager {

	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'ssai_ai_rules';
	}

	public function get_all_rules(): array {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM {$this->table} ORDER BY priority ASC, id ASC",
			ARRAY_A
		) ?: array();
	}

	public function get_active_rules(): array {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY priority ASC, id ASC",
			ARRAY_A
		) ?: array();
	}

	public function get_rule( int $id ): ?array {
		global $wpdb;
		$rule = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ),
			ARRAY_A
		);
		return $rule ?: null;
	}

	public function create_rule( array $data ): int {
		global $wpdb;

		$wpdb->insert(
			$this->table,
			array(
				'title'      => sanitize_text_field( $data['title'] ?? '' ),
				'rule_text'  => sanitize_textarea_field( $data['rule_text'] ?? '' ),
				'priority'   => (int) ( $data['priority'] ?? 10 ),
				'is_active'  => (int) ( $data['is_active'] ?? 1 ),
			),
			array( '%s', '%s', '%d', '%d' )
		);

		return (int) $wpdb->insert_id;
	}

	public function update_rule( int $id, array $data ): bool {
		global $wpdb;

		$update = array();
		$format = array();

		if ( isset( $data['title'] ) ) {
			$update['title'] = sanitize_text_field( $data['title'] );
			$format[] = '%s';
		}
		if ( isset( $data['rule_text'] ) ) {
			$update['rule_text'] = sanitize_textarea_field( $data['rule_text'] );
			$format[] = '%s';
		}
		if ( isset( $data['priority'] ) ) {
			$update['priority'] = (int) $data['priority'];
			$format[] = '%d';
		}
		if ( isset( $data['is_active'] ) ) {
			$update['is_active'] = (int) $data['is_active'];
			$format[] = '%d';
		}

		if ( empty( $update ) ) {
			return false;
		}

		return (bool) $wpdb->update( $this->table, $update, array( 'id' => $id ), $format, array( '%d' ) );
	}

	public function delete_rule( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( $this->table, array( 'id' => $id ), array( '%d' ) );
	}

	public function seed_default_rules(): void {
		$existing = $this->get_all_rules();
		if ( ! empty( $existing ) ) {
			return;
		}

		$defaults = array(
			array(
				'title'     => 'سایز رینگ',
				'rule_text' => 'اگر کاربر درباره رینگ سؤال کرد و سایز را مشخص نکرد، قبل از پیشنهاد محصول درباره سایز سؤال کن.',
				'priority'  => 1,
			),
			array(
				'title'     => 'موجودی',
				'rule_text' => 'هیچ محصولی که موجود نیست پیشنهاد نده.',
				'priority'  => 2,
			),
			array(
				'title'     => 'قیمت واقعی',
				'rule_text' => 'اگر کاربر درباره قیمت سؤال کرد، قیمت واقعی WooCommerce را نمایش بده. هرگز قیمت را حدس نزن.',
				'priority'  => 3,
			),
			array(
				'title'     => 'عدم پیشنهاد نامرتبط',
				'rule_text' => 'اگر محصولی پیدا نشد، محصول نامرتبط پیشنهاد نده.',
				'priority'  => 4,
			),
			array(
				'title'     => 'لحن پاسخ',
				'rule_text' => 'در پاسخ‌ها از لحن دوستانه و حرفه‌ای استفاده کن.',
				'priority'  => 5,
			),
		);

		foreach ( $defaults as $rule ) {
			$this->create_rule( $rule );
		}
	}
}
