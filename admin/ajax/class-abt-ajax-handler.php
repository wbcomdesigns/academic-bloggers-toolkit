<?php
/**
 * AJAX handler for admin operations.
 *
 * @link       https://github.com/academic-bloggers-toolkit
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/ajax
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handler for admin operations.
 *
 * Handles all AJAX requests from the admin interface including
 * citation management, reference operations, and search functionality.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/ajax
 * @author     Academic Bloggers Toolkit Team
 */
class ABT_Ajax_Handler {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register AJAX hooks.
	 *
	 * @since    1.0.0
	 */
	public function register_ajax_hooks() {
		// Citation management
		add_action( 'wp_ajax_abt_save_citation', array( $this, 'save_citation' ) );
		add_action( 'wp_ajax_abt_delete_citation', array( $this, 'delete_citation' ) );
		add_action( 'wp_ajax_abt_get_citation', array( $this, 'get_citation' ) );
		add_action( 'wp_ajax_abt_update_citation_order', array( $this, 'update_citation_order' ) );

		// Footnote management
		add_action( 'wp_ajax_abt_save_footnote', array( $this, 'save_footnote' ) );
		add_action( 'wp_ajax_abt_delete_footnote', array( $this, 'delete_footnote' ) );
		add_action( 'wp_ajax_abt_get_footnote', array( $this, 'get_footnote' ) );

		// Reference operations
		add_action( 'wp_ajax_abt_search_references', array( $this, 'search_references' ) );
		add_action( 'wp_ajax_abt_get_reference_preview', array( $this, 'get_reference_preview' ) );
		add_action( 'wp_ajax_abt_duplicate_reference', array( $this, 'duplicate_reference' ) );

		// Bibliography operations
		add_action( 'wp_ajax_abt_generate_bibliography', array( $this, 'generate_bibliography' ) );
		add_action( 'wp_ajax_abt_export_bibliography', array( $this, 'export_bibliography' ) );

		// Auto-cite operations
		add_action( 'wp_ajax_abt_fetch_from_doi', array( $this, 'fetch_from_doi' ) );
		add_action( 'wp_ajax_abt_fetch_from_url', array( $this, 'fetch_from_url' ) );
		add_action( 'wp_ajax_abt_fetch_from_pmid', array( $this, 'fetch_from_pmid' ) );

		// Import operations
		add_action( 'wp_ajax_abt_import_references', array( $this, 'import_references' ) );
	}

	/**
	 * Save citation AJAX handler.
	 *
	 * @since    1.0.0
	 */
	public function save_citation() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'abt_metabox_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'academic-bloggers-toolkit' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'academic-bloggers-toolkit' ) ) );
		}

		// Get and validate data
		$post_id = intval( $_POST['post_id'] );
		$citation_id = intval( $_POST['citation_id'] );
		$reference_id = intval( $_POST['reference_id'] );
		$position = intval( $_POST['position'] );
		$prefix = sanitize_text_field( $_POST['prefix'] );
		$suffix = sanitize_text_field( $_POST['suffix'] );
		$suppress_author = isset( $_POST['suppress_author'] ) ? 1 : 0;

		if ( ! $post_id || ! $reference_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data provided.', 'academic-bloggers-toolkit' ) ) );
		}

		// Validate reference exists
		$reference = ABT_Reference::get( $reference_id );
		if ( ! $reference ) {
			wp_send_json_error( array( 'message' => __( 'Reference not found.', 'academic-bloggers-toolkit' ) ) );
		}

		try {
			if ( $citation_id ) {
				// Update existing citation
				$citation = ABT_Citation::get( $citation_id );
				if ( ! $citation ) {
					wp_send_json_error( array( 'message' => __( 'Citation not found.', 'academic-bloggers-toolkit' ) ) );
				}

				$citation->reference_id = $reference_id;
				$citation->position = $position;
				$citation->prefix = $prefix;
				$citation->suffix = $suffix;
				$citation->suppress_author = $suppress_author;
				
				$result = $citation->save();
			} else {
				// Create new citation
				$citation_data = array(
					'post_id' => $post_id,
					'reference_id' => $reference_id,
					'position' => $position,
					'prefix' => $prefix,
					'suffix' => $suffix,
					'suppress_author' => $suppress_author,
				);

				$citation = ABT_Citation::create( $citation_data );
				$result = $citation ? $citation->id : false;
			}

			if ( $result ) {
				// Get formatted citation for response
				$formatted_citation = $this->format_citation_response( $citation, $reference );
				
				wp_send_json_success( array( 
					'message' => __( 'Citation saved successfully.', 'academic-bloggers-toolkit' ),
					'citation' => $formatted_citation,
				) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Failed to save citation.', 'academic-bloggers-toolkit' ) ) );
			}

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Delete citation AJAX handler.
	 *
	 * @since    1.0.0
	 */
	public function delete_citation() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'abt_metabox_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'academic-bloggers-toolkit' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'academic-bloggers-toolkit' ) ) );
		}

		$citation_id = intval( $_POST['citation_id'] );

		if ( ! $citation_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid citation ID.', 'academic-bloggers-toolkit' ) ) );
		}

		$citation = ABT_Citation::get( $citation_id );
		if ( ! $citation ) {
			wp_send_json_error( array( 'message' => __( 'Citation not found.', 'academic-bloggers-toolkit' ) ) );
		}

		if ( $citation->delete() ) {
			wp_send_json_success( array( 'message' => __( 'Citation deleted successfully.', 'academic-bloggers-toolkit' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete citation.', 'academic-bloggers-toolkit' ) ) );
		}
	}

	/**
	 * Get citation AJAX handler.
	 *
	 * @since    1.0.0
	 */
	public function get_citation() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'abt_metabox_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'academic-bloggers-toolkit' ) ) );
		}

		$citation_id = intval( $_POST['citation_id'] );

		if ( ! $citation_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid citation ID.', 'academic-bloggers-toolkit' ) ) );
		}

		$citation = ABT_Citation::get( $citation_id );
		if ( ! $citation ) {
			wp_send_json_error( array( 'message' => __( 'Citation not found.', 'academic-bloggers-toolkit' ) ) );
		}

		$reference = ABT_Reference::get( $citation->reference_id );
		$formatted_citation = $this->format_citation_response( $citation, $reference );

		wp_send_json_success( array( 'citation' => $formatted_citation ) );
	}

	/**
	 * Save footnote AJAX handler.
	 *
	 * @since    1.0.0
	 */
	public function save_footnote() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'abt_metabox_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'academic-bloggers-toolkit' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'academic-bloggers-toolkit' ) ) );
		}

		// Get and validate data
		$post_id = intval( $_POST['post_id'] );
		$footnote_id = intval( $_POST['footnote_id'] );
		$content = wp_kses_post( $_POST['content'] );
		$position = intval( $_POST['position'] );

		if ( ! $post_id || empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data provided.', 'academic-bloggers-toolkit' ) ) );
		}

		try {
			if ( $footnote_id ) {
				// Update existing footnote
				$footnote = ABT_Footnote::get( $footnote_id );
				if ( ! $footnote ) {
					wp_send_json_error( array( 'message' => __( 'Footnote not found.', 'academic-bloggers-toolkit' ) ) );
				}

				$footnote->content = $content;
				$footnote->position = $position;
				
				$result = $footnote->save();
			} else {
				// Create new footnote
				$footnote_data = array(
					'post_id' => $post_id,
					'content' => $content,
					'position' => $position,
				);

				$footnote = ABT_Footnote::create( $footnote_data );
				$result = $footnote ? $footnote->id : false;
			}

			if ( $result ) {
				$formatted_footnote = array(
					'id' => $footnote->id,
					'content' => $footnote->content,
					'position' => $footnote->position,
					'content_preview' => wp_trim_words( $footnote->content, 15, '...' ),
				);
				
				wp_send_json_success( array( 
					'message' => __( 'Footnote saved successfully.', 'academic-bloggers-toolkit' ),
					'footnote' => $formatted_footnote,
				) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Failed to save footnote.', 'academic-bloggers-toolkit' ) ) );
			}

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Delete footnote AJAX handler.
	 *
	 * @since    1.0.0
	 */
	public function delete_footnote() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'abt_metabox_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'academic-bloggers-toolkit' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'academic-bloggers-toolkit' ) ) );
		}

		$footnote_id = intval( $_POST['footnote_id'] );

		if ( ! $footnote_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid footnote ID.', 'academic-bloggers-toolkit' ) ) );
		}

		$footnote = ABT_Footnote::get( $footnote_id );
		if ( ! $footnote ) {
			wp_send_json_error( array( 'message' => __( 'Footnote not found.', 'academic-bloggers-toolkit' ) ) );
		}

		if ( $footnote->delete() ) {
			wp_send_json_success( array( 'message' => __( 'Footnote deleted successfully.', 'academic-bloggers-toolkit' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete footnote.', 'academic-bloggers-toolkit' ) ) );
		}
	}

	/**
	 * Search references AJAX handler.
	 *
	 * @since    1.0.0
	 */
	public function search_references() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'abt_metabox_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'academic-bloggers-toolkit' ) ) );
		}

		$search_term = sanitize_text_field( $_POST['search_term'] );
		$limit = intval( $_POST['limit'] ) ?: 10;

		$args = array(
			'post_type' => 'abt_reference',
			'post_status' => 'publish',
			'posts_per_page' => $limit,
			's' => $search_term,
		);

		$query = new WP_Query( $args );
		$references = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post = get_post();
				
				$authors = get_post_meta( $post->ID, '_abt_authors', true );
				$year = get_post_meta( $post->ID, '_abt_year', true );
				$type = get_post_meta( $post->ID, '_abt_reference_type', true );

				$references[] = array(
					'id' => $post->ID,
					'title' => $post->post_title,
					'authors' => $authors,
					'year' => $year,
					'type' => $type,
					'formatted' => $this->format_reference_display( $post, $authors, $year ),
				);
			}
		}

		wp_reset_postdata();

		wp_send_json_success( array( 'references' => $references ) );
	}

	/**
	 * Get reference preview AJAX handler.
	 *
	 * @since    1.0.0
	 */
	public function get_reference_preview() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'abt_metabox_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'academic-bloggers-toolkit' ) ) );
		}

		$reference_id = intval( $_POST['reference_id'] );

		if ( ! $reference_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid reference ID.', 'academic-bloggers-toolkit' ) ) );
		}

		$reference = ABT_Reference::get( $reference_id );
		if ( ! $reference ) {
			wp_send_json_error( array( 'message' => __( 'Reference not found.', 'academic-bloggers-toolkit' ) ) );
		}

		// Get all metadata
		$metadata = array(
			'type' => $reference->get_meta( 'reference_type' ),
			'authors' => $reference->get_meta( 'authors' ),
			'year' => $reference->get_meta( 'year' ),
			'journal' => $reference->get_meta( 'journal' ),
			'volume' => $reference->get_meta( 'volume' ),
			'issue' => $reference->get_meta( 'issue' ),
			'pages' => $reference->get_meta( 'pages' ),
			'doi' => $reference->get_meta( 'doi' ),
			'url' => $reference->get_meta( 'url' ),
			'pmid' => $reference->get_meta( 'pmid' ),
			'isbn' => $reference->get_meta( 'isbn' ),
		);

		// Format preview
		$preview = array(
			'id' => $reference->id,
			'title' => $reference->post_title,
			'metadata' => array_filter( $metadata ), // Remove empty values
			'formatted_apa' => $this->format_reference_citation( $reference, 'apa' ),
			'formatted_mla' => $this->format_reference_citation( $reference, 'mla' ),
			'usage_count' => count( ABT_Citation::get_by_reference( $reference_id ) ),
		);

		wp_send_json_success( array( 'preview' => $preview ) );
	}

	/**
	 * Generate bibliography AJAX handler.
	 *
	 * @since    1.0.0
	 */
	public function generate_bibliography() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'abt_metabox_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'academic-bloggers-toolkit' ) ) );
		}

		$post_id = intval( $_POST['post_id'] );
		$style = sanitize_text_field( $_POST['style'] ) ?: 'apa';

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'academic-bloggers-toolkit' ) ) );
		}

		$citations = ABT_Citation::get_by_post( $post_id );
		$bibliography_entries = array();

		foreach ( $citations as $citation ) {
			$reference = ABT_Reference::get( $citation->reference_id );
			if ( $reference ) {
				$bibliography_entries[] = array(
					'reference_id' => $reference->id,
					'formatted' => $this->format_reference_citation( $reference, $style ),
				);
			}
		}

		wp_send_json_success( array( 
			'bibliography' => $bibliography_entries,
			'style' => $style,
			'count' => count( $bibliography_entries ),
		) );
	}

	/**
	 * Fetch from DOI AJAX handler.
	 *
	 * @since    1.0.0
	 */
	public function fetch_from_doi() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'abt_metabox_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'academic-bloggers-toolkit' ) ) );
		}

		$doi = sanitize_text_field( $_POST['doi'] );

		if ( empty( $doi ) ) {
			wp_send_json_error( array( 'message' => __( 'DOI is required.', 'academic-bloggers-toolkit' ) ) );
		}

		// Basic DOI validation
		if ( ! preg_match( '/^10\.\d{4,}\/\S+$/', $doi ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid DOI format.', 'academic-bloggers-toolkit' ) ) );
		}

		// This will be fully implemented in Phase 3 - Citation Processing Engine
		// For now, return a placeholder response
		wp_send_json_success( array( 
			'message' => __( 'DOI fetching will be available in the next update.', 'academic-bloggers-toolkit' ),
			'reference_data' => array(
				'title' => 'Sample Article from DOI: ' . $doi,
				'authors' => 'Sample Author',
				'year' => date( 'Y' ),
				'journal' => 'Sample Journal',
				'doi' => $doi,
				'type' => 'journal',
			),
		) );
	}

	/**
	 * Fetch from URL AJAX handler.
	 *
	 * @since    1.0.0
	 */
	public function fetch_from_url() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'abt_metabox_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'academic-bloggers-toolkit' ) ) );
		}

		$url = esc_url_raw( $_POST['url'] );

		if ( empty( $url ) ) {
			wp_send_json_error( array( 'message' => __( 'URL is required.', 'academic-bloggers-toolkit' ) ) );
		}

		// This will be fully implemented in Phase 3 - Citation Processing Engine
		// For now, return a placeholder response
		wp_send_json_success( array( 
			'message' => __( 'URL fetching will be available in the next update.', 'academic-bloggers-toolkit' ),
			'reference_data' => array(
				'title' => 'Website Title from: ' . $url,
				'url' => $url,
				'type' => 'website',
				'access_date' => date( 'Y-m-d' ),
			),
		) );
	}

	/**
	 * Format citation for response.
	 *
	 * @since    1.0.0
	 * @param    ABT_Citation    $citation     Citation object.
	 * @param    ABT_Reference   $reference    Reference object.
	 * @return   array                         Formatted citation data.
	 */
	private function format_citation_response( $citation, $reference ) {
		return array(
			'id' => $citation->id,
			'reference_id' => $citation->reference_id,
			'reference_title' => $reference->post_title,
			'reference_authors' => $reference->get_meta( 'authors' ),
			'position' => $citation->position,
			'prefix' => $citation->prefix,
			'suffix' => $citation->suffix,
			'suppress_author' => $citation->suppress_author,
		);
	}

	/**
	 * Format reference for display.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post      Post object.
	 * @param    string     $authors   Authors string.
	 * @param    string     $year      Year.
	 * @return   string                Formatted display string.
	 */
	private function format_reference_display( $post, $authors, $year ) {
		$display = $post->post_title;
		
		if ( $authors ) {
			$author_list = explode( ';', $authors );
			if ( count( $author_list ) > 1 ) {
				$display = trim( $author_list[0] ) . ' et al. - ' . $display;
			} else {
				$display = trim( $authors ) . ' - ' . $display;
			}
		}
		
		if ( $year ) {
			$display .= ' (' . $year . ')';
		}
		
		return $display;
	}

	/**
	 * Format reference citation.
	 *
	 * @since    1.0.0
	 * @param    ABT_Reference    $reference    Reference object.
	 * @param    string          $style        Citation style.
	 * @return   string                        Formatted citation.
	 */
	private function format_reference_citation( $reference, $style ) {
		// Basic implementation - will be enhanced in Phase 3
		$authors = $reference->get_meta( 'authors' );
		$year = $reference->get_meta( 'year' );
		$title = $reference->post_title;
		$journal = $reference->get_meta( 'journal' );

		switch ( $style ) {
			case 'apa':
				$formatted = '';
				if ( $authors ) $formatted .= $authors . ' ';
				if ( $year ) $formatted .= '(' . $year . '). ';
				$formatted .= '<em>' . $title . '</em>';
				if ( $journal ) $formatted .= '. ' . $journal;
				break;

			case 'mla':
				$formatted = '';
				if ( $authors ) $formatted .= $authors . '. ';
				$formatted .= '"' . $title . '"';
				if ( $journal ) $formatted .= '. <em>' . $journal . '</em>';
				if ( $year ) $formatted .= ', ' . $year;
				break;

			default:
				$formatted = $title;
				if ( $authors ) $formatted = $authors . '. ' . $formatted;
				if ( $year ) $formatted .= ' (' . $year . ')';
				break;
		}

		return $formatted;
	}
}