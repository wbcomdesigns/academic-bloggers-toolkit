<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wbcomdesigns.com
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes
 * @author     Academic Blogger's Toolkit Team
 */
class ABT_Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      ABT_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'ABT_VERSION' ) ) {
			$this->version = ABT_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'academic-bloggers-toolkit';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - ABT_Loader. Orchestrates the hooks of the plugin.
	 * - ABT_i18n. Defines internationalization functionality.
	 * - ABT_Admin. Defines all hooks for the admin area.
	 * - ABT_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once ABT_PLUGIN_DIR . 'includes/class-abt-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once ABT_PLUGIN_DIR . 'includes/class-abt-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once ABT_PLUGIN_DIR . 'admin/class-abt-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once ABT_PLUGIN_DIR . 'public/class-abt-public.php';

		/**
		 * The class responsible for registering custom post types.
		 */
		require_once ABT_PLUGIN_DIR . 'includes/post-types/class-abt-post-types.php';

		/**
		 * Load admin page classes
		 */
		if ( is_admin() ) {
			require_once ABT_PLUGIN_DIR . 'admin/pages/class-abt-references-page.php';
			require_once ABT_PLUGIN_DIR . 'admin/pages/class-abt-statistics-page.php';
			require_once ABT_PLUGIN_DIR . 'admin/pages/class-abt-settings-page.php';
			require_once ABT_PLUGIN_DIR . 'admin/pages/class-abt-import-page.php';
		}

		$this->loader = new ABT_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the ABT_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new ABT_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new ABT_Admin( $this->get_plugin_name(), $this->get_version() );

		// Enqueue scripts and styles
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		
		// Add admin menu - THIS WAS MISSING
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		
		// Add meta boxes
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_meta_boxes' );
		
		// Save meta box data
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_meta_boxes' );
		
		// Add custom columns
		$this->loader->add_filter( 'manage_abt_blog_posts_columns', $plugin_admin, 'add_blog_columns' );
		$this->loader->add_action( 'manage_abt_blog_posts_custom_column', $plugin_admin, 'display_blog_columns', 10, 2 );
		
		$this->loader->add_filter( 'manage_abt_reference_posts_columns', $plugin_admin, 'add_reference_columns' );
		$this->loader->add_action( 'manage_abt_reference_posts_custom_column', $plugin_admin, 'display_reference_columns', 10, 2 );
		
		// Make columns sortable
		$this->loader->add_filter( 'manage_edit-abt_blog_sortable_columns', $plugin_admin, 'make_columns_sortable' );
		$this->loader->add_filter( 'manage_edit-abt_reference_sortable_columns', $plugin_admin, 'make_columns_sortable' );
		
		// Handle column sorting
		$this->loader->add_action( 'pre_get_posts', $plugin_admin, 'handle_column_sorting' );
		
		// Add admin notices
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'add_admin_notices' );
		
		// Add taxonomy filters
		$this->loader->add_action( 'restrict_manage_posts', $plugin_admin, 'add_taxonomy_filters' );
		$this->loader->add_action( 'pre_get_posts', $plugin_admin, 'handle_taxonomy_filtering' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new ABT_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Register custom post types and taxonomies.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_post_type_hooks() {
		$post_types = new ABT_Post_Types();

		$this->loader->add_action( 'init', $post_types, 'register_post_types' );
		$this->loader->add_action( 'init', $post_types, 'register_taxonomies' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		// Register post types first
		$this->define_post_type_hooks();
		
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    ABT_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}