<?php
/**
 * Fired during plugin activation
 *
 * @link       https://wbcomdesigns.com
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes
 * @author     Academic Blogger's Toolkit Team
 */
class ABT_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Require the post types class to register them during activation
		require_once ABT_PLUGIN_DIR . 'includes/post-types/class-abt-post-types.php';
		
		// Create an instance and register post types
		$post_types = new ABT_Post_Types();
		$post_types->register_post_types();
		$post_types->register_taxonomies();
		
		// Flush rewrite rules to ensure pretty permalinks work
		flush_rewrite_rules();
		
		// Set default plugin options
		self::set_default_options();
		
		// Create plugin database tables if needed (future use)
		self::create_tables();
		
		// Set plugin activation flag
		add_option( 'abt_plugin_activated', true );
		
		// Record activation time
		add_option( 'abt_activation_time', current_time( 'timestamp' ) );
		
		// Set plugin version
		add_option( 'abt_plugin_version', ABT_VERSION );
	}
	
	/**
	 * Set default plugin options.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private static function set_default_options() {
		$default_options = array(
			'citation_style'          => 'apa',
			'bibliography_title'      => __( 'References', 'academic-bloggers-toolkit' ),
			'footnote_style'          => 'numeric',
			'auto_bibliography'       => true,
			'enable_tooltips'         => true,
			'enable_footnotes'        => true,
			'doi_fetcher_enabled'     => true,
			'pubmed_fetcher_enabled'  => true,
			'isbn_fetcher_enabled'    => true,
			'url_scraper_enabled'     => true,
			'import_formats'          => array( 'ris', 'bibtex', 'csv' ),
			'export_formats'          => array( 'ris', 'bibtex', 'csv' ),
			'enable_analytics'        => true,
			'admin_notices'           => true,
		);
		
		add_option( 'abt_settings', $default_options );
	}
	
	/**
	 * Create plugin database tables if needed.
	 * 
	 * Currently using WordPress post types, but this method is reserved
	 * for future enhancements that might require custom tables.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private static function create_tables() {
		global $wpdb;
		
		// For now, we're using WordPress post types exclusively
		// This method is reserved for future custom table needs
		
		/**
		 * Example future table creation:
		 * 
		 * $table_name = $wpdb->prefix . 'abt_analytics';
		 * 
		 * $charset_collate = $wpdb->get_charset_collate();
		 * 
		 * $sql = "CREATE TABLE $table_name (
		 *     id mediumint(9) NOT NULL AUTO_INCREMENT,
		 *     post_id bigint(20) NOT NULL,
		 *     reference_id bigint(20) NOT NULL,
		 *     citation_count int(11) DEFAULT 0,
		 *     last_cited datetime DEFAULT CURRENT_TIMESTAMP,
		 *     PRIMARY KEY  (id),
		 *     KEY post_id (post_id),
		 *     KEY reference_id (reference_id)
		 * ) $charset_collate;";
		 * 
		 * require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		 * dbDelta( $sql );
		 */
		
		// For Phase 1, we don't need custom tables
		// WordPress post meta handles our data storage needs efficiently
	}
}