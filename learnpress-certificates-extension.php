<?php
/**
 * Plugin Name: LearnPress Certificates & Subscriptions API
 * Plugin URI: https://nextflytech.com
 * Description: REST API endpoints for LearnPress certificates and Paid Member Subscriptions. Provides API access to certificate data and subscription status.
 * Version: 4.1.0
 * Author: Nextfly Technologies Pvt Ltd
 * Author URI: https://nextflytech.com
 * Text Domain: learnpress-certificates-extension
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Require_LP_Version: 4.0.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'LPCE_VERSION', '4.1.0' );
define( 'LPCE_PLUGIN_FILE', __FILE__ );
define( 'LPCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LPCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LPCE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'LPCE_REQUIRE_LP_VERSION', '4.0.0' );

/**
 * Main plugin class
 */
class LearnPress_Certificates_Extension {
	
	/**
	 * Single instance of the class
	 *
	 * @var LearnPress_Certificates_Extension
	 */
	private static $instance = null;
	
	/**
	 * Get single instance
	 *
	 * @return LearnPress_Certificates_Extension
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}
	
	/**
	 * Include required files
	 */
	private function includes() {
		require_once LPCE_PLUGIN_DIR . 'includes/class-lpce-database.php';
		require_once LPCE_PLUGIN_DIR . 'includes/class-lpce-rest-api.php';
	}
	
	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init' ) );
		register_activation_hook( LPCE_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( LPCE_PLUGIN_FILE, array( $this, 'deactivate' ) );
	}
	
	/**
	 * Load plugin textdomain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'learnpress-certificates-extension', false, dirname( LPCE_PLUGIN_BASENAME ) . '/languages' );
	}
	
	/**
	 * Initialize plugin
	 */
	public function init() {
		// Check if LearnPress is active
		if ( ! class_exists( 'LearnPress' ) ) {
			add_action( 'admin_notices', array( $this, 'learnpress_missing_notice' ) );
			return;
		}
		
		// Check LearnPress version
		if ( ! $this->check_learnpress_version() ) {
			add_action( 'admin_notices', array( $this, 'learnpress_version_notice' ) );
			return;
		}
		
		// Check if Paid Member Subscriptions is active (optional, only for subscription endpoints)
		if ( ! function_exists( 'pms_get_member_subscriptions' ) ) {
			add_action( 'admin_notices', array( $this, 'pms_missing_notice' ) );
		}
		
		// Initialize components
		LPCE_Database::instance();
		LPCE_REST_API::instance();
	}
	
	/**
	 * Check if LearnPress version meets requirement
	 *
	 * @return bool
	 */
	private function check_learnpress_version() {
		if ( ! defined( 'LEARNPRESS_VERSION' ) ) {
			// Try to get version from LearnPress class if constant is not defined
			if ( class_exists( 'LearnPress' ) && defined( 'LP_PLUGIN_FILE' ) ) {
				$plugin_data = get_file_data( LP_PLUGIN_FILE, array( 'Version' => 'Version' ) );
				$lp_version = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : '0.0.0';
			} else {
				return false;
			}
		} else {
			$lp_version = LEARNPRESS_VERSION;
		}
		
		return version_compare( $lp_version, LPCE_REQUIRE_LP_VERSION, '>=' );
	}
	
	/**
	 * Show notice if LearnPress is not active
	 */
	public function learnpress_missing_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php 
				echo esc_html__( 'LearnPress Certificates & Subscriptions API requires LearnPress to be installed and active.', 'learnpress-certificates-extension' );
				?>
			</p>
		</div>
		<?php
	}
	
	/**
	 * Show notice if LearnPress version is too low
	 */
	public function learnpress_version_notice() {
		$current_version = defined( 'LEARNPRESS_VERSION' ) ? LEARNPRESS_VERSION : __( 'Unknown', 'learnpress-certificates-extension' );
		?>
		<div class="notice notice-error">
			<p>
				<?php 
				printf(
					esc_html__( 'LearnPress Certificates & Subscriptions API add-on version %1$s requires LearnPress version %2$s or higher. You are currently running LearnPress version %3$s. Please update LearnPress to the latest version.', 'learnpress-certificates-extension' ),
					esc_html( LPCE_VERSION ),
					esc_html( LPCE_REQUIRE_LP_VERSION ),
					esc_html( $current_version )
				);
				?>
			</p>
		</div>
		<?php
	}
	
	/**
	 * Show notice if Paid Member Subscriptions is not active (warning, not error)
	 */
	public function pms_missing_notice() {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php 
				echo esc_html__( 'LearnPress Certificates & Subscriptions API: Paid Member Subscriptions plugin is not active. Subscription status endpoints will not be available.', 'learnpress-certificates-extension' );
				?>
			</p>
		</div>
		<?php
	}
	
	/**
	 * Plugin activation
	 */
	public function activate() {
		// No need to create tables - using existing LearnPress certificates database
		// Flush rewrite rules
		flush_rewrite_rules();
	}
	
	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}
}

/**
 * Main instance
 *
 * @return LearnPress_Certificates_Extension
 */
function LPCE() {
	return LearnPress_Certificates_Extension::instance();
}

// Initialize plugin
LPCE();

