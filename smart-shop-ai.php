<?php
/**
 * Plugin Name:       Smart Shop AI Assistant
 * Plugin URI:        https://example.com/smart-shop-ai
 * Description:       AI-powered shopping assistant for WooCommerce. Helps customers find products through natural language chat.
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Smart Shop AI
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       smart-shop-ai
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   9.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSAI_VERSION', '1.0.0' );
define( 'SSAI_PLUGIN_FILE', __FILE__ );
define( 'SSAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSAI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Declare compatibility with WooCommerce features.
 */
function ssai_declare_woocommerce_compatibility(): void {
	if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		return;
	}

	$features = array(
		'custom_order_tables',
		'cart_checkout_blocks',
		'product_block_editor',
	);

	foreach ( $features as $feature ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( $feature, __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'ssai_declare_woocommerce_compatibility' );

/**
 * Check WooCommerce dependency.
 */
function ssai_check_woocommerce() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Smart Shop AI Assistant requires WooCommerce to be installed and active.', 'smart-shop-ai' );
			echo '</p></div>';
		} );
		return false;
	}
	return true;
}

/**
 * Initialize the plugin.
 */
function ssai_init() {
	if ( ! ssai_check_woocommerce() ) {
		return;
	}

	require_once SSAI_PLUGIN_DIR . 'includes/Core/Plugin.php';
	\SmartShopAI\Core\Plugin::instance();
}
add_action( 'plugins_loaded', 'ssai_init' );

/**
 * Activation hook.
 */
function ssai_activate() {
	require_once SSAI_PLUGIN_DIR . 'includes/Core/Activator.php';
	\SmartShopAI\Core\Activator::activate();
}
register_activation_hook( __FILE__, 'ssai_activate' );

/**
 * Deactivation hook.
 */
function ssai_deactivate() {
	require_once SSAI_PLUGIN_DIR . 'includes/Core/Deactivator.php';
	\SmartShopAI\Core\Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'ssai_deactivate' );
