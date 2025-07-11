<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://academic-bloggers-toolkit.com
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes
 * @author     Academic Blogger's Toolkit Team
 */
class ABT_Deactivator {

	/**
	 * Plugin deactivation tasks.
	 *
	 * Performs necessary cleanup when the plugin is deactivated.
	 * Does NOT delete user data - only clears temporary/cache data.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Flush rewrite rules to clean up custom post type permalinks
		flush_rewrite_rules();
		
		// Clear any scheduled events
		self::clear_scheduled_events();
		
		// Clear temporary cache data
		self::clear_cache_data();
		
		// Remove activation flag (but keep user data)
		delete_option( 'abt_plugin_activated' );
		
		// Log deactivation time for analytics
		update_option( 'abt_deactivation_time', current_time( 'timestamp' ) );
	}
	
	/**
	 * Clear any scheduled WordPress cron events.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private static function clear_scheduled_events() {
		// Clear scheduled events if any exist
		// For future use when we implement scheduled tasks like:
		// - Bibliography updates
		// - Citation validation
		// - Analytics cleanup
		
		$scheduled_hooks = array(
			'abt_daily_cleanup',
			'abt_citation_validation',
			'abt_analytics_cleanup',
		);
		
		foreach ( $scheduled_hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}
	}
	
	/**
	 * Clear temporary cache and transient data.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private static function clear_cache_data() {
		global $wpdb;
		
		// Clear plugin-specific transients
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_abt_%' 
			OR option_name LIKE '_transient_timeout_abt_%'"
		);
		
		// Clear any object cache entries
		wp_cache_delete_group( 'abt_references' );
		wp_cache_delete_group( 'abt_citations' );
		wp_cache_delete_group( 'abt_bibliographies' );
		
		// Clear specific cache keys we might use
		$cache_keys = array(
			'abt_citation_styles',
			'abt_reference_count',
			'abt_popular_references',
			'abt_citation_statistics',
		);
		
		foreach ( $cache_keys as $key ) {
			wp_cache_delete( $key, 'academic-bloggers-toolkit' );
		}
	}
}