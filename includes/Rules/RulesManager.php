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
				'title'     => 'Wheel size',
				'rule_text' => 'If the user asks about wheels without specifying a size, ask about the size before recommending products.',
				'priority'  => 1,
			),
			array(
				'title'     => 'Stock availability',
				'rule_text' => 'Do not recommend products that are out of stock.',
				'priority'  => 2,
			),
			array(
				'title'     => 'Real pricing',
				'rule_text' => 'If the user asks about price, show the real WooCommerce price. Never guess prices.',
				'priority'  => 3,
			),
			array(
				'title'     => 'No irrelevant suggestions',
				'rule_text' => 'If no matching product is found, do not suggest unrelated products.',
				'priority'  => 4,
			),
			array(
				'title'     => 'Response tone',
				'rule_text' => 'Use a friendly and professional tone in responses.',
				'priority'  => 5,
			),
		);

		foreach ( $defaults as $rule ) {
			$this->create_rule( $rule );
		}
	}
}
