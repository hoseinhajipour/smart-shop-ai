<?php
namespace SmartShopAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin singleton.
 */
class Plugin {

	/** @var Plugin|null */
	private static $instance = null;

	/** @var Loader */
	private $loader;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->loader = new Loader();
		$this->define_hooks();
	}

	private function load_dependencies(): void {
		$base = SSAI_PLUGIN_DIR . 'includes/';

		require_once $base . 'Core/Loader.php';
		require_once $base . 'Core/Settings.php';
		require_once $base . 'Core/ConversationLogger.php';

		require_once $base . 'AI/AIProviderInterface.php';
		require_once $base . 'AI/Providers/AbstractAIProvider.php';
		require_once $base . 'AI/Providers/OpenAIProvider.php';
		require_once $base . 'AI/Providers/AnthropicProvider.php';
		require_once $base . 'AI/Providers/GeminiProvider.php';
		require_once $base . 'AI/Providers/OpenAICompatibleProvider.php';
		require_once $base . 'AI/Providers/ReplicateProvider.php';
		require_once $base . 'AI/AIService.php';
		require_once $base . 'AI/AIModelFetcher.php';
		require_once $base . 'AI/IntentParser.php';

		require_once $base . 'MCP/MCPProviderInterface.php';
		require_once $base . 'MCP/WooCommerceMCPProvider.php';
		require_once $base . 'MCP/WooCommerceDirectProvider.php';
		require_once $base . 'MCP/MCPService.php';

		require_once $base . 'WooCommerce/AttributeDiscovery.php';
		require_once $base . 'WooCommerce/ProductSearcher.php';
		require_once $base . 'WooCommerce/WooCommerceHelper.php';

		require_once $base . 'Search/SmartSearch.php';
		require_once $base . 'Search/NormalSearch.php';
		require_once $base . 'Search/SearchRouter.php';

		require_once $base . 'Recommendation/ProductRanker.php';
		require_once $base . 'Fitment/FitmentHelper.php';
		require_once $base . 'Support/SupportHelper.php';
		require_once $base . 'Rules/RulesManager.php';

		require_once $base . 'REST/ChatController.php';
		require_once $base . 'REST/SettingsController.php';
		require_once $base . 'REST/DiagnosticsController.php';

		if ( is_admin() ) {
			require_once $base . 'Admin/AdminMenu.php';
		}

		require_once $base . 'Frontend/ChatbotLoader.php';
	}

	private function define_hooks(): void {
		$this->loader->add_action( 'init', $this, 'load_textdomain' );
		$this->loader->add_action( 'rest_api_init', $this, 'register_rest_routes' );

		if ( is_admin() ) {
			$admin = new \SmartShopAI\Admin\AdminMenu();
			$this->loader->add_action( 'admin_menu', $admin, 'register_menu' );
			$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );
		}

		$frontend = new \SmartShopAI\Frontend\ChatbotLoader();
		$this->loader->add_action( 'wp_enqueue_scripts', $frontend, 'enqueue_assets' );
		$this->loader->add_action( 'wp_footer', $frontend, 'render_chatbot' );
		$this->loader->add_action( 'wp_ajax_ssai_add_to_cart', $this, 'ajax_add_to_cart' );
		$this->loader->add_action( 'wp_ajax_nopriv_ssai_add_to_cart', $this, 'ajax_add_to_cart' );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'smart-shop-ai', false, dirname( SSAI_PLUGIN_BASENAME ) . '/languages' );
	}

	public function ajax_add_to_cart(): void {
		if ( ! \SmartShopAI\Core\Settings::is_capability_enabled( 'add_to_cart' ) ) {
			wp_send_json_error( array( 'message' => 'Add to cart is disabled.' ) );
		}

		$product_id = absint( $_POST['product_id'] ?? 0 );
		$quantity   = absint( $_POST['quantity'] ?? 1 );

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => 'Invalid product.' ) );
		}

		$added = WC()->cart->add_to_cart( $product_id, $quantity );

		if ( $added ) {
			wp_send_json_success( array( 'message' => 'Product added to cart.' ) );
		}

		wp_send_json_error( array( 'message' => 'Could not add product to cart.' ) );
	}

	public function register_rest_routes(): void {
		$chat         = new \SmartShopAI\REST\ChatController();
		$settings     = new \SmartShopAI\REST\SettingsController();
		$diagnostics  = new \SmartShopAI\REST\DiagnosticsController();

		$chat->register_routes();
		$settings->register_routes();
		$diagnostics->register_routes();
	}

	public function run(): void {
		$this->loader->run();
	}
}

// Boot immediately.
Plugin::instance()->run();
