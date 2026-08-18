<?php
namespace SmartShopAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin deactivation tasks.
 */
class Deactivator {

	public static function deactivate(): void {
		// Clear scheduled events if any.
		wp_clear_scheduled_hook( 'ssai_cleanup_logs' );
	}
}
