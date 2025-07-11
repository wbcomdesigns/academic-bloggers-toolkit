<?php
/**
 * Reference model class
 *
 * Handles CRUD operations and data management for academic references.
 *
 * @link       https://academic-bloggers-toolkit.com
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes/post-types
 */

/**
 * Reference model class.
 *
 * Provides methods for creating, reading, updating, and deleting academic references.
 * Handles metadata management and validation for different reference types.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes/post-types
 * @author     Academic Blogger's Toolkit Team
 */
class ABT_Reference {

	/**
	 * Post ID of the reference.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $post_id    The post ID.
	 */
	private $post_id;

	/**
	 * Reference metadata.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $metadata    Reference metadata array.
	 */
	private $metadata;

	/**
	 * Valid reference types.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $valid_types    Array of valid reference types.
	 */
	private static $valid_types = array(
		'journal_article',
		'book',
		'book_chapter',
		'conference_paper',
		'thesis',
		'website',
		'report',
		'newspaper_article',
		'magazine_article',
		'patent',
		'government_document',
		'legal_case',
		'software',
		'dataset',
		'other'
	);

	/**
	 * Initialize the reference.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Optional. Post ID of existing reference.
	 */
	public function __construct( $post_id = null ) {
		if ( $post_id && get_post_type( $post_id ) === 'abt_reference' ) {
			$this->post_id = $post_id;
			$this->load_metadata();
		}
	}

	/**
	 * Create a new reference.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Reference data array.
	 * @return   int|WP_Error     Post ID on success, WP_Error on failure.
	 */
	public function create( $data ) {
		// Validate required fields
		$validation = $this->validate_data( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Prepare post data
		$post_data = array(
			'post_type'   => 'abt_reference',
			'post_status' => 'publish',
			'post_title'  => sanitize_text_field( $data['title'] ),
			'post_author' => get_current_user_id(),
		);

		// Add description if provided
		if ( ! empty( $data['description'] ) ) {
			$post_data['post_content'] = wp_kses_post( $data['description'] );
		}

		// Insert the post
		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$this->post_id = $post_id;

		// Save metadata
		$this->save_metadata( $data );

		return $post_id;
	}

	/**
	 * Update an existing reference.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Reference data array.
	 * @return   bool|WP_Error    True on success, WP_Error on failure.
	 */
	public function update( $data ) {
		if ( ! $this->post_id ) {
			return new WP_Error( 'no_reference', __( 'No reference loaded for update.', 'academic-bloggers-toolkit' ) );
		}

		// Validate data
		$validation = $this->validate_data( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Prepare post data
		$post_data = array(
			'ID'         => $this->post_id,
			'post_title' => sanitize_text_field( $data['title'] ),
		);

		// Add description if provided
		if ( ! empty( $data['description'] ) ) {
			$post_data['post_content'] = wp_kses_post( $data['description'] );
		}

		// Update the post
		$result = wp_update_post( $post_data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Update metadata
		$this->save_metadata( $data );

		return true;
	}

	/**
	 * Delete the reference.
	 *
	 * @since    1.0.0
	 * @param    bool    $force_delete    Whether to force delete or move to trash.
	 * @return   bool|WP_Error           True on success, WP_Error on failure.
	 */
	public function delete( $force_delete = false ) {
		if ( ! $this->post_id ) {
			return new WP_Error( 'no_reference', __( 'No reference loaded for deletion.', 'academic-bloggers-toolkit' ) );
		}

		$result = wp_delete_post( $this->post_id, $force_delete );

		if ( $result ) {
			$this->post_id = null;
			$this->metadata = array();
			return true;
		}

		return new WP_Error( 'delete_failed', __( 'Failed to delete reference.', 'academic-bloggers-toolkit' ) );
	}

	/**
	 * Load metadata from database.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_metadata() {
		if ( ! $this->post_id ) {
			return;
		}

		$this->metadata = get_post_meta( $this->post_id );

		// Clean up metadata (remove array wrappers from single values)
		foreach ( $this->metadata as $key => $value ) {
			if ( is_array( $value ) && count( $value ) === 1 ) {
				$this->metadata[ $key ] = $value[0];
			}
		}
	}

	/**
	 * Save metadata to database.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $data    Reference data array.
	 */
	private function save_metadata( $data ) {
		if ( ! $this->post_id ) {
			return;
		}

		// Define metadata fields
		$meta_fields = array(
			'reference_type',
			'authors',
			'publication_year',
			'journal',
			'volume',
			'issue',
			'pages',
			'publisher',
			'publication_place',
			'doi',
			'pmid',
			'isbn',
			'url',
			'access_date',
			'abstract',
			'keywords',
			'notes',
			'language',
			'edition',
			'chapter_title',
			'editors',
			'conference_name',
			'conference_location',
			'conference_date',
			'thesis_type',
			'institution',
			'department',
			'patent_number',
			'filing_date',
			'issue_date',
			'assignee',
			'legal_reporter',
			'court',
			'decision_date',
			'case_number',
			'software_version',
			'operating_system',
			'programming_language',
			'repository_url',
			'dataset_version',
			'data_format',
			'access_restrictions'
		);

		// Save each metadata field
		foreach ( $meta_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$value = $data[ $field ];

				// Handle arrays (like authors, keywords)
				if ( is_array( $value ) ) {
					$value = array_map( 'sanitize_text_field', $value );
				} else {
					$value = sanitize_text_field( $value );
				}

				update_post_meta( $this->post_id, '_abt_' . $field, $value );
			}
		}

		// Update our internal metadata
		$this->load_metadata();
	}

	/**
	 * Validate reference data.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $data    Reference data to validate.
	 * @return   bool|WP_Error    True if valid, WP_Error if not.
	 */
	private function validate_data( $data ) {
		// Check required fields
		if ( empty( $data['title'] ) ) {
			return new WP_Error( 'missing_title', __( 'Reference title is required.', 'academic-bloggers-toolkit' ) );
		}

		if ( empty( $data['reference_type'] ) ) {
			return new WP_Error( 'missing_type', __( 'Reference type is required.', 'academic-bloggers-toolkit' ) );
		}

		// Validate reference type
		if ( ! in_array( $data['reference_type'], self::$valid_types, true ) ) {
			return new WP_Error( 'invalid_type', __( 'Invalid reference type.', 'academic-bloggers-toolkit' ) );
		}

		// Validate year if provided
		if ( ! empty( $data['publication_year'] ) ) {
			$year = intval( $data['publication_year'] );
			if ( $year < 1000 || $year > ( date( 'Y' ) + 10 ) ) {
				return new WP_Error( 'invalid_year', __( 'Invalid publication year.', 'academic-bloggers-toolkit' ) );
			}
		}

		// Validate DOI format if provided
		if ( ! empty( $data['doi'] ) && ! $this->validate_doi( $data['doi'] ) ) {
			return new WP_Error( 'invalid_doi', __( 'Invalid DOI format.', 'academic-bloggers-toolkit' ) );
		}

		// Validate URL if provided
		if ( ! empty( $data['url'] ) && ! filter_var( $data['url'], FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_url', __( 'Invalid URL format.', 'academic-bloggers-toolkit' ) );
		}

		return true;
	}

	/**
	 * Validate DOI format.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string    $doi    DOI to validate.
	 * @return   bool             True if valid DOI format.
	 */
	private function validate_doi( $doi ) {
		// Basic DOI format validation
		$pattern = '/^10\.\d{4,}(?:\.\d+)*\/[-._;()\/:a-zA-Z0-9]+$/';
		return preg_match( $pattern, $doi );
	}

	/**
	 * Get reference data.
	 *
	 * @since    1.0.0
	 * @return   array|null    Reference data array or null if no reference loaded.
	 */
	public function get_data() {
		if ( ! $this->post_id ) {
			return null;
		}

		$post = get_post( $this->post_id );
		if ( ! $post ) {
			return null;
		}

		$data = array(
			'id'          => $this->post_id,
			'title'       => $post->post_title,
			'description' => $post->post_content,
			'created'     => $post->post_date,
			'modified'    => $post->post_modified,
		);

		// Add metadata
		if ( $this->metadata ) {
			foreach ( $this->metadata as $key => $value ) {
				// Remove _abt_ prefix from keys
				$clean_key = str_replace( '_abt_', '', $key );
				$data[ $clean_key ] = $value;
			}
		}

		return $data;
	}

	/**
	 * Get post ID.
	 *
	 * @since    1.0.0
	 * @return   int|null    Post ID or null if no reference loaded.
	 */
	public function get_id() {
		return $this->post_id;
	}

	/**
	 * Get specific metadata value.
	 *
	 * @since    1.0.0
	 * @param    string    $key    Metadata key.
	 * @return   mixed            Metadata value or null if not found.
	 */
	public function get_meta( $key ) {
		$meta_key = '_abt_' . $key;
		return isset( $this->metadata[ $meta_key ] ) ? $this->metadata[ $meta_key ] : null;
	}

	/**
	 * Set specific metadata value.
	 *
	 * @since    1.0.0
	 * @param    string    $key      Metadata key.
	 * @param    mixed     $value    Metadata value.
	 * @return   bool               True on success, false on failure.
	 */
	public function set_meta( $key, $value ) {
		if ( ! $this->post_id ) {
			return false;
		}

		$meta_key = '_abt_' . $key;
		$result = update_post_meta( $this->post_id, $meta_key, $value );
		
		if ( $result ) {
			$this->metadata[ $meta_key ] = $value;
		}

		return $result;
	}

	/**
	 * Get valid reference types.
	 *
	 * @since    1.0.0
	 * @return   array    Array of valid reference types.
	 */
	public static function get_valid_types() {
		return self::$valid_types;
	}

	/**
	 * Search references.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Search arguments.
	 * @return   array           Array of reference objects.
	 */
	public static function search( $args = array() ) {
		$defaults = array(
			'post_type'      => 'abt_reference',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );
		$posts = get_posts( $args );

		$references = array();
		foreach ( $posts as $post ) {
			$reference = new self( $post->ID );
			$references[] = $reference;
		}

		return $references;
	}

	/**
	 * Get reference count.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   int             Number of references.
	 */
	public static function get_count( $args = array() ) {
		$defaults = array(
			'post_type'   => 'abt_reference',
			'post_status' => 'publish',
		);

		$args = wp_parse_args( $args, $defaults );
		$query = new WP_Query( $args );

		return $query->found_posts;
	}
}