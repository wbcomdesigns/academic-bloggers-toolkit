<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://academic-bloggers-toolkit.com
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Only uninstall if explicitly requested by user
 */
if ( ! get_option( 'abt_uninstall_remove_data', false ) ) {
	return;
}

/**
 * Delete all plugin data
 */
function abt_uninstall_cleanup() {
	global $wpdb;
	
	// Delete all posts of our custom post types
	$post_types = array( 'abt_blog', 'abt_reference', 'abt_citation', 'abt_footnote', 'abt_bibliography' );
	
	foreach ( $post_types as $post_type ) {
		$posts = get_posts( array(
			'post_type'   => $post_type,
			'numberposts' => -1,
			'post_status' => 'any'
		) );
		
		foreach ( $posts as $post ) {
			// Delete all post meta
			delete_post_meta( $post->ID, '_abt_abstract' );
			delete_post_meta( $post->ID, '_abt_keywords' );
			delete_post_meta( $post->ID, '_abt_doi' );
			delete_post_meta( $post->ID, '_abt_pmid' );
			delete_post_meta( $post->ID, '_abt_isbn' );
			delete_post_meta( $post->ID, '_abt_url' );
			delete_post_meta( $post->ID, '_abt_citations' );
			delete_post_meta( $post->ID, '_abt_footnotes' );
			delete_post_meta( $post->ID, '_abt_bibliography' );
			delete_post_meta( $post->ID, '_abt_citation_style' );
			delete_post_meta( $post->ID, '_abt_citation_count' );
			delete_post_meta( $post->ID, '_abt_author' );
			delete_post_meta( $post->ID, '_abt_year' );
			delete_post_meta( $post->ID, '_abt_journal' );
			delete_post_meta( $post->ID, '_abt_volume' );
			delete_post_meta( $post->ID, '_abt_issue' );
			delete_post_meta( $post->ID, '_abt_pages' );
			delete_post_meta( $post->ID, '_abt_publisher' );
			delete_post_meta( $post->ID, '_abt_publication_place' );
			delete_post_meta( $post->ID, '_abt_type' );
			delete_post_meta( $post->ID, '_abt_status' );
			
			// Force delete the post (bypass trash)
			wp_delete_post( $post->ID, true );
		}
	}
	
	// Delete all taxonomies and terms
	$taxonomies = array( 'abt_blog_category', 'abt_blog_tag', 'abt_subject', 'abt_ref_category' );
	
	foreach ( $taxonomies as $taxonomy ) {
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false
		) );
		
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				wp_delete_term( $term->term_id, $taxonomy );
			}
		}
	}
	
	// Delete all plugin options
	$options = array(
		'abt_version',
		'abt_db_version',
		'abt_settings',
		'abt_citation_styles',
		'abt_default_citation_style',
		'abt_auto_cite_settings',
		'abt_import_settings',
		'abt_export_settings',
		'abt_display_settings',
		'abt_advanced_settings',
		'abt_uninstall_remove_data',
		'abt_first_activation',
		'abt_activation_timestamp',
		'abt_crossref_api_key',
		'abt_pubmed_api_key',
		'abt_google_books_api_key',
		'abt_cache_settings',
		'abt_performance_settings',
		'abt_debug_mode',
		'abt_log_level'
	);
	
	foreach ( $options as $option ) {
		delete_option( $option );
		delete_site_option( $option ); // For multisite
	}
	
	// Delete all user meta related to plugin
	$user_meta_keys = array(
		'abt_citation_style_preference',
		'abt_display_preferences',
		'abt_saved_searches',
		'abt_bookmarked_articles',
		'abt_export_format_preference',
		'affiliation',
		'orcid',
		'research_interests',
		'academic_title',
		'institution'
	);
	
	$users = get_users( array( 'fields' => 'ID' ) );
	foreach ( $users as $user_id ) {
		foreach ( $user_meta_keys as $meta_key ) {
			delete_user_meta( $user_id, $meta_key );
		}
	}
	
	// Clean up term relationships
	$wpdb->query( "DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN (
		SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} 
		WHERE taxonomy IN ('abt_blog_category', 'abt_blog_tag', 'abt_subject', 'abt_ref_category')
	)" );
	
	// Clean up term taxonomy
	$wpdb->query( "DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ('abt_blog_category', 'abt_blog_tag', 'abt_subject', 'abt_ref_category')" );
	
	// Clean up orphaned terms
	$wpdb->query( "DELETE t FROM {$wpdb->terms} t LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.term_id IS NULL" );
	
	// Delete any custom tables if they exist
	$custom_tables = array(
		$wpdb->prefix . 'abt_citations',
		$wpdb->prefix . 'abt_references',
		$wpdb->prefix . 'abt_citation_cache',
		$wpdb->prefix . 'abt_import_log',
		$wpdb->prefix . 'abt_search_log'
	);
	
	foreach ( $custom_tables as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
	
	// Clear any cached data
	wp_cache_flush();
	
	// Delete uploaded files in plugin directory (if any)
	$upload_dir = wp_upload_dir();
	$plugin_upload_dir = $upload_dir['basedir'] . '/academic-bloggers-toolkit/';
	
	if ( is_dir( $plugin_upload_dir ) ) {
		abt_recursive_delete( $plugin_upload_dir );
	}
	
	// Delete transients
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_abt_%' OR option_name LIKE '_transient_timeout_abt_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_abt_%' OR option_name LIKE '_site_transient_timeout_abt_%'" );
	
	// Clear any scheduled cron jobs
	wp_clear_scheduled_hook( 'abt_cleanup_old_citations' );
	wp_clear_scheduled_hook( 'abt_update_citation_counts' );
	wp_clear_scheduled_hook( 'abt_cache_cleanup' );
	wp_clear_scheduled_hook( 'abt_import_cleanup' );
	
	// Remove custom capabilities
	$roles = array( 'administrator', 'editor', 'author' );
	$capabilities = array(
		'edit_abt_blog',
		'edit_abt_blogs',
		'edit_others_abt_blogs',
		'publish_abt_blogs',
		'read_private_abt_blogs',
		'delete_abt_blog',
		'delete_abt_blogs',
		'delete_others_abt_blogs',
		'delete_published_abt_blogs',
		'delete_private_abt_blogs',
		'edit_private_abt_blogs',
		'edit_published_abt_blogs',
		'manage_abt_blog_categories',
		'edit_abt_blog_categories',
		'delete_abt_blog_categories',
		'assign_abt_blog_categories',
		'manage_abt_blog_tags',
		'edit_abt_blog_tags',
		'delete_abt_blog_tags',
		'assign_abt_blog_tags',
		'manage_abt_subjects',
		'edit_abt_subjects',
		'delete_abt_subjects',
		'assign_abt_subjects',
		'edit_abt_reference',
		'edit_abt_references',
		'edit_others_abt_references',
		'publish_abt_references',
		'read_private_abt_references',
		'delete_abt_reference',
		'delete_abt_references',
		'delete_others_abt_references',
		'delete_published_abt_references',
		'delete_private_abt_references',
		'edit_private_abt_references',
		'edit_published_abt_references',
		'manage_abt_ref_categories',
		'edit_abt_ref_categories',
		'delete_abt_ref_categories',
		'assign_abt_ref_categories'
	);
	
	foreach ( $roles as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			foreach ( $capabilities as $capability ) {
				$role->remove_cap( $capability );
			}
		}
	}
	
	// Remove rewrite rules
	delete_option( 'rewrite_rules' );
	
	// Log uninstall activity
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Academic Blogger\'s Toolkit: Plugin uninstalled and all data removed at ' . current_time( 'mysql' ) );
	}
}

/**
 * Recursively delete directory and all contents
 *
 * @param string $dir Directory path to delete
 */
function abt_recursive_delete( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}
	
	$files = array_diff( scandir( $dir ), array( '.', '..' ) );
	
	foreach ( $files as $file ) {
		$path = $dir . DIRECTORY_SEPARATOR . $file;
		if ( is_dir( $path ) ) {
			abt_recursive_delete( $path );
		} else {
			unlink( $path );
		}
	}
	
	rmdir( $dir );
}

/**
 * Multisite cleanup
 */
function abt_multisite_uninstall() {
	if ( ! is_multisite() ) {
		return;
	}
	
	global $wpdb;
	
	// Get all blog IDs
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
	
	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
		abt_uninstall_cleanup();
		restore_current_blog();
	}
	
	// Clean network-wide options
	$network_options = array(
		'abt_network_settings',
		'abt_network_version',
		'abt_network_activated'
	);
	
	foreach ( $network_options as $option ) {
		delete_site_option( $option );
	}
}

// Execute cleanup based on installation type
if ( is_multisite() ) {
	abt_multisite_uninstall();
} else {
	abt_uninstall_cleanup();
}

// Final cleanup
flush_rewrite_rules();