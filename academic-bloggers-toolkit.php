<?php
/**
 * Academic Blogger's Toolkit
 *
 * @link              https://wbcomdesigns.com
 * @since             1.0.0
 * @package           Academic_Bloggers_Toolkit
 *
 * @wordpress-plugin
 * Plugin Name:       Academic Blogger's Toolkit
 * Plugin URI:        https://wbcomdesigns.com
 * Description:       Complete academic citation management system for WordPress. Create academic blog posts with proper citations, footnotes, and bibliographies. Features auto-cite from DOI, PMID, ISBN, and URL sources.
 * Version:           1.0.0
 * Author:            Academic Blogger's Toolkit Team
 * Author URI:        https://wbcomdesigns.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       academic-bloggers-toolkit
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Tested up to:      6.4
 * Requires PHP:      8.0
 * Network:           false
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ABT_VERSION', '1.0.0' );

/**
 * Plugin directory path.
 */
define( 'ABT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'ABT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'ABT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin file path.
 */
define( 'ABT_PLUGIN_FILE', __FILE__ );

/**
 * Minimum WordPress version required.
 */
define( 'ABT_MIN_WP_VERSION', '6.0' );

/**
 * Minimum PHP version required.
 */
define( 'ABT_MIN_PHP_VERSION', '8.0' );

/**
 * Check if WordPress and PHP versions meet minimum requirements.
 *
 * @since 1.0.0
 */
function abt_check_requirements() {
	global $wp_version;
	
	$php_version = phpversion();
	$wp_version_check = version_compare( $wp_version, ABT_MIN_WP_VERSION, '>=' );
	$php_version_check = version_compare( $php_version, ABT_MIN_PHP_VERSION, '>=' );
	
	if ( ! $wp_version_check || ! $php_version_check ) {
		add_action( 'admin_notices', 'abt_requirements_notice' );
		return false;
	}
	
	return true;
}

/**
 * Display admin notice for requirements not met.
 *
 * @since 1.0.0
 */
function abt_requirements_notice() {
	global $wp_version;
	
	$php_version = phpversion();
	$class = 'notice notice-error';
	$message = sprintf(
		/* translators: 1: Plugin name, 2: WordPress version, 3: required WordPress version, 4: PHP version, 5: required PHP version */
		__( '%1$s requires WordPress %3$s+ (you have %2$s) and PHP %5$s+ (you have %4$s).', 'academic-bloggers-toolkit' ),
		'<strong>Academic Blogger\'s Toolkit</strong>',
		$wp_version,
		ABT_MIN_WP_VERSION,
		$php_version,
		ABT_MIN_PHP_VERSION
	);
	
	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-abt-activator.php
 *
 * @since 1.0.0
 */
function abt_activate() {
	if ( ! abt_check_requirements() ) {
		wp_die(
			esc_html__( 'Plugin activation failed due to unmet requirements.', 'academic-bloggers-toolkit' ),
			esc_html__( 'Plugin Activation Error', 'academic-bloggers-toolkit' ),
			array( 'back_link' => true )
		);
	}
	
	require_once ABT_PLUGIN_DIR . 'includes/class-abt-activator.php';
	ABT_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-abt-deactivator.php
 *
 * @since 1.0.0
 */
function abt_deactivate() {
	require_once ABT_PLUGIN_DIR . 'includes/class-abt-deactivator.php';
	ABT_Deactivator::deactivate();
}

/**
 * Register activation and deactivation hooks.
 */
register_activation_hook( __FILE__, 'abt_activate' );
register_deactivation_hook( __FILE__, 'abt_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require ABT_PLUGIN_DIR . 'includes/class-abt-core.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function abt_run() {
	// Check requirements before running
	if ( ! abt_check_requirements() ) {
		return;
	}
	
	$plugin = new ABT_Core();
	$plugin->run();
}

/**
 * Initialize the plugin.
 */
add_action( 'plugins_loaded', 'abt_run' );

/**
 * Add plugin action links.
 *
 * @since 1.0.0
 * @param array $links An array of plugin action links.
 * @return array Modified array of plugin action links.
 */
function abt_plugin_action_links( $links ) {
	$settings_link = '<a href="' . admin_url( 'edit.php?post_type=abt_reference&page=abt-settings' ) . '">' . esc_html__( 'Settings', 'academic-bloggers-toolkit' ) . '</a>';
	array_unshift( $links, $settings_link );
	
	return $links;
}

add_filter( 'plugin_action_links_' . ABT_PLUGIN_BASENAME, 'abt_plugin_action_links' );

/**
 * Add plugin meta links.
 *
 * @since 1.0.0
 * @param array  $meta_links An array of the plugin's metadata.
 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
 * @return array Modified array of plugin metadata.
 */
function abt_plugin_meta_links( $meta_links, $plugin_file ) {
	if ( ABT_PLUGIN_BASENAME === $plugin_file ) {
		$meta_links[] = '<a href="https://wbcomdesigns.com/docs/" target="_blank">' . esc_html__( 'Documentation', 'academic-bloggers-toolkit' ) . '</a>';
		$meta_links[] = '<a href="https://wbcomdesigns.com/support/" target="_blank">' . esc_html__( 'Support', 'academic-bloggers-toolkit' ) . '</a>';
	}
	
	return $meta_links;
}

add_filter( 'plugin_row_meta', 'abt_plugin_meta_links', 10, 2 );