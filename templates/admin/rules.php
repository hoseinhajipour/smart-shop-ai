<?php
/**
 * AI Rules page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rules_manager = new \SmartShopAI\Rules\RulesManager();
$rules_manager->seed_default_rules();
$rules = $rules_manager->get_all_rules();
?>
<div class="wrap ssai-admin-wrap">
	<h1><?php esc_html_e( 'AI Rules', 'smart-shop-ai' ); ?></h1>
	<p><?php esc_html_e( 'Define rules that guide the AI assistant behavior.', 'smart-shop-ai' ); ?></p>

	<div class="ssai-rules-list" id="ssai-rules-list">
		<?php foreach ( $rules as $rule ) : ?>
		<div class="ssai-rule-item" data-id="<?php echo esc_attr( $rule['id'] ); ?>">
			<div class="ssai-rule-header">
				<strong><?php echo esc_html( $rule['title'] ); ?></strong>
				<span class="ssai-rule-priority">Priority: <?php echo esc_html( $rule['priority'] ); ?></span>
				<label class="ssai-toggle">
					<input type="checkbox" class="ssai-rule-active" <?php checked( $rule['is_active'], 1 ); ?> />
					<?php esc_html_e( 'Active', 'smart-shop-ai' ); ?>
				</label>
			</div>
			<p class="ssai-rule-text"><?php echo esc_html( $rule['rule_text'] ); ?></p>
			<button class="button ssai-delete-rule" data-id="<?php echo esc_attr( $rule['id'] ); ?>"><?php esc_html_e( 'Delete', 'smart-shop-ai' ); ?></button>
		</div>
		<?php endforeach; ?>
	</div>

	<h2><?php esc_html_e( 'Add New Rule', 'smart-shop-ai' ); ?></h2>
	<form id="ssai-add-rule-form" class="ssai-form">
		<table class="form-table">
			<tr>
				<th><label for="rule_title"><?php esc_html_e( 'Title', 'smart-shop-ai' ); ?></label></th>
				<td><input type="text" id="rule_title" name="title" class="regular-text" required /></td>
			</tr>
			<tr>
				<th><label for="rule_text"><?php esc_html_e( 'Rule', 'smart-shop-ai' ); ?></label></th>
				<td><textarea id="rule_text" name="rule_text" rows="3" class="large-text" required></textarea></td>
			</tr>
			<tr>
				<th><label for="rule_priority"><?php esc_html_e( 'Priority', 'smart-shop-ai' ); ?></label></th>
				<td><input type="number" id="rule_priority" name="priority" value="10" min="1" max="100" /></td>
			</tr>
		</table>
		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Rule', 'smart-shop-ai' ); ?></button>
		</p>
	</form>
</div>
