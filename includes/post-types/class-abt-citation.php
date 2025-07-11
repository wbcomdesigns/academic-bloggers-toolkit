<?php
/**
 * Citation model class
 *
 * Handles individual citation instances that connect references to blog posts.
 *
 * @link       https://wbcomdesigns.com
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes/post-types
 */

/**
 * Citation model class.
 *
 * Manages individual citation instances, including formatting options,
 * page numbers, and citation-specific metadata.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes/post-types
 * @author     Academic Blogger's Toolkit Team
 */
class ABT_Citation {

	/**
	 * Post ID of the citation.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $post_id    The citation post ID.
	 */
	private $post_id;

	/**
	 * Citation metadata.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $metadata    Citation metadata array.
	 */
	private $metadata;

	/**
	 * Initialize the citation.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Optional. Post ID of existing citation.
	 */
	public function __construct( $post_id = null ) {
		if ( $post_id && get_post_type( $post_id ) === 'abt_citation' ) {
			$this->post_id = $post_id;
			$this->load_metadata();
		}
	}

	/**
	 * Create a new citation.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Citation data array.
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
			'post_type'   => 'abt_citation',
			'post_status' => 'publish',
			'post_title'  => sprintf( 
				'Citation for post %d, reference %d', 
				$data['post_id'], 
				$data['reference_id'] 
			),
			'post_parent' => $data['post_id'],
			'post_author' => get_current_user_id(),
		);

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
	 * Update an existing citation.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Citation data array.
	 * @return   bool|WP_Error    True on success, WP_Error on failure.
	 */
	public function update( $data ) {
		if ( ! $this->post_id ) {
			return new WP_Error( 'no_citation', __( 'No citation loaded for update.', 'academic-bloggers-toolkit' ) );
		}

		// Validate data
		$validation = $this->validate_data( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Update metadata
		$this->save_metadata( $data );

		return true;
	}

	/**
	 * Delete the citation.
	 *
	 * @since    1.0.0
	 * @param    bool    $force_delete    Whether to force delete or move to trash.
	 * @return   bool|WP_Error           True on success, WP_Error on failure.
	 */
	public function delete( $force_delete = true ) {
		if ( ! $this->post_id ) {
			return new WP_Error( 'no_citation', __( 'No citation loaded for deletion.', 'academic-bloggers-toolkit' ) );
		}

		$result = wp_delete_post( $this->post_id, $force_delete );

		if ( $result ) {
			$this->post_id = null;
			$this->metadata = array();
			return true;
		}

		return new WP_Error( 'delete_failed', __( 'Failed to delete citation.', 'academic-bloggers-toolkit' ) );
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
	 * @param    array    $data    Citation data array.
	 */
	private function save_metadata( $data ) {
		if ( ! $this->post_id ) {
			return;
		}

		// Define metadata fields
		$meta_fields = array(
			'reference_id',
			'post_id',
			'page_number',
			'prefix',
			'suffix',
			'locator',
			'locator_type',
			'suppress_author',
			'citation_style',
			'citation_format',
			'position_in_text',
			'citation_group_id',
			'inline_citation',
			'formatted_citation',
			'created_date',
			'last_updated',
		);

		// Save each metadata field
		foreach ( $meta_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$value = $data[ $field ];

				// Sanitize based on field type
				switch ( $field ) {
					case 'reference_id':
					case 'post_id':
					case 'position_in_text':
					case 'citation_group_id':
						$value = intval( $value );
						break;
					case 'suppress_author':
					case 'inline_citation':
						$value = (bool) $value;
						break;
					default:
						$value = sanitize_text_field( $value );
						break;
				}

				update_post_meta( $this->post_id, '_abt_' . $field, $value );
			}
		}

		// Set creation/update timestamps
		if ( ! get_post_meta( $this->post_id, '_abt_created_date', true ) ) {
			update_post_meta( $this->post_id, '_abt_created_date', current_time( 'mysql' ) );
		}
		update_post_meta( $this->post_id, '_abt_last_updated', current_time( 'mysql' ) );

		// Update our internal metadata
		$this->load_metadata();
	}

	/**
	 * Validate citation data.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $data    Citation data to validate.
	 * @return   bool|WP_Error    True if valid, WP_Error if not.
	 */
	private function validate_data( $data ) {
		// Check required fields
		if ( empty( $data['reference_id'] ) ) {
			return new WP_Error( 'missing_reference', __( 'Reference ID is required.', 'academic-bloggers-toolkit' ) );
		}

		if ( empty( $data['post_id'] ) ) {
			return new WP_Error( 'missing_post', __( 'Post ID is required.', 'academic-bloggers-toolkit' ) );
		}

		// Validate reference exists
		if ( get_post_type( $data['reference_id'] ) !== 'abt_reference' ) {
			return new WP_Error( 'invalid_reference', __( 'Invalid reference ID.', 'academic-bloggers-toolkit' ) );
		}

		// Validate post exists
		if ( get_post_type( $data['post_id'] ) !== 'abt_blog' ) {
			return new WP_Error( 'invalid_post', __( 'Invalid post ID.', 'academic-bloggers-toolkit' ) );
		}

		// Validate locator type if provided
		if ( ! empty( $data['locator_type'] ) ) {
			$valid_locator_types = array(
				'page', 'pages', 'paragraph', 'section', 'chapter', 
				'figure', 'table', 'line', 'verse', 'column', 'note'
			);
			
			if ( ! in_array( $data['locator_type'], $valid_locator_types, true ) ) {
				return new WP_Error( 'invalid_locator_type', __( 'Invalid locator type.', 'academic-bloggers-toolkit' ) );
			}
		}

		return true;
	}

	/**
	 * Format the citation for display.
	 *
	 * @since    1.0.0
	 * @param    string    $style    Citation style (apa, mla, chicago, etc.).
	 * @return   string            Formatted citation HTML.
	 */
	public function format_citation( $style = null ) {
		if ( ! $this->post_id ) {
			return '';
		}

		$reference_id = $this->get_meta( 'reference_id' );
		if ( ! $reference_id ) {
			return '';
		}

		// Get reference data
		$reference = new ABT_Reference( $reference_id );
		$reference_data = $reference->get_data();

		if ( ! $reference_data ) {
			return '';
		}

		// Use provided style or get from citation metadata
		if ( ! $style ) {
			$style = $this->get_meta( 'citation_style', 'apa' );
		}

		// Basic citation formatting (will be enhanced in citation processing engine)
		$citation = $this->format_basic_citation( $reference_data, $style );

		// Add locator information
		$locator = $this->get_meta( 'locator' );
		$locator_type = $this->get_meta( 'locator_type', 'page' );

		if ( $locator ) {
			$citation .= ', ' . $locator_type . ' ' . $locator;
		}

		// Add prefix and suffix
		$prefix = $this->get_meta( 'prefix' );
		$suffix = $this->get_meta( 'suffix' );

		if ( $prefix ) {
			$citation = $prefix . ' ' . $citation;
		}

		if ( $suffix ) {
			$citation .= ', ' . $suffix;
		}

		return $citation;
	}

	/**
	 * Format basic citation.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array     $reference_data    Reference data.
	 * @param    string    $style            Citation style.
	 * @return   string                      Formatted citation.
	 */
	private function format_basic_citation( $reference_data, $style = 'apa' ) {
		$suppress_author = $this->get_meta( 'suppress_author', false );
		
		switch ( $style ) {
			case 'apa':
				return $this->format_apa_citation( $reference_data, $suppress_author );
			case 'mla':
				return $this->format_mla_citation( $reference_data, $suppress_author );
			case 'chicago':
				return $this->format_chicago_citation( $reference_data, $suppress_author );
			default:
				return $this->format_apa_citation( $reference_data, $suppress_author );
		}
	}

	/**
	 * Format APA style citation.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $reference_data    Reference data.
	 * @param    bool     $suppress_author   Whether to suppress author.
	 * @return   string                     Formatted APA citation.
	 */
	private function format_apa_citation( $reference_data, $suppress_author = false ) {
		$citation = '';

		if ( ! $suppress_author && ! empty( $reference_data['authors'] ) ) {
			$authors = is_array( $reference_data['authors'] ) ? $reference_data['authors'] : array( $reference_data['authors'] );
			
			if ( count( $authors ) === 1 ) {
				$citation .= $authors[0];
			} elseif ( count( $authors ) === 2 ) {
				$citation .= $authors[0] . ' & ' . $authors[1];
			} else {
				$citation .= $authors[0] . ' et al.';
			}
		}

		if ( ! empty( $reference_data['publication_year'] ) ) {
			if ( $citation ) {
				$citation .= ', ' . $reference_data['publication_year'];
			} else {
				$citation .= $reference_data['publication_year'];
			}
		}

		return $citation;
	}

	/**
	 * Format MLA style citation.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $reference_data    Reference data.
	 * @param    bool     $suppress_author   Whether to suppress author.
	 * @return   string                     Formatted MLA citation.
	 */
	private function format_mla_citation( $reference_data, $suppress_author = false ) {
		$citation = '';

		if ( ! $suppress_author && ! empty( $reference_data['authors'] ) ) {
			$authors = is_array( $reference_data['authors'] ) ? $reference_data['authors'] : array( $reference_data['authors'] );
			$citation .= $authors[0];
		}

		// MLA uses different format - will be enhanced in citation processor
		return $citation;
	}

	/**
	 * Format Chicago style citation.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $reference_data    Reference data.
	 * @param    bool     $suppress_author   Whether to suppress author.
	 * @return   string                     Formatted Chicago citation.
	 */
	private function format_chicago_citation( $reference_data, $suppress_author = false ) {
		$citation = '';

		if ( ! $suppress_author && ! empty( $reference_data['authors'] ) ) {
			$authors = is_array( $reference_data['authors'] ) ? $reference_data['authors'] : array( $reference_data['authors'] );
			$citation .= $authors[0];
		}

		if ( ! empty( $reference_data['publication_year'] ) ) {
			if ( $citation ) {
				$citation .= ' ' . $reference_data['publication_year'];
			} else {
				$citation .= $reference_data['publication_year'];
			}
		}

		return $citation;
	}

	/**
	 * Get citation data.
	 *
	 * @since    1.0.0
	 * @return   array|null    Citation data array or null if no citation loaded.
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
			'post_id'     => $post->post_parent,
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
	 * @return   int|null    Post ID or null if no citation loaded.
	 */
	public function get_id() {
		return $this->post_id;
	}

	/**
	 * Get specific metadata value.
	 *
	 * @since    1.0.0
	 * @param    string    $key        Metadata key.
	 * @param    mixed     $default    Default value if not found.
	 * @return   mixed               Metadata value or default.
	 */
	public function get_meta( $key, $default = null ) {
		$meta_key = '_abt_' . $key;
		return isset( $this->metadata[ $meta_key ] ) ? $this->metadata[ $meta_key ] : $default;
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
	 * Search citations.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Search arguments.
	 * @return   array           Array of citation objects.
	 */
	public static function search( $args = array() ) {
		$defaults = array(
			'post_type'      => 'abt_citation',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );
		$posts = get_posts( $args );

		$citations = array();
		foreach ( $posts as $post ) {
			$citation = new self( $post->ID );
			$citations[] = $citation;
		}

		return $citations;
	}

	/**
	 * Get citations for a specific post.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Post ID.
	 * @return   array             Array of citation objects.
	 */
	public static function get_by_post( $post_id ) {
		return self::search( array(
			'post_parent' => $post_id,
			'orderby'     => 'meta_value_num',
			'meta_key'    => '_abt_position_in_text',
			'order'       => 'ASC',
		) );
	}

	/**
	 * Get citations for a specific reference.
	 *
	 * @since    1.0.0
	 * @param    int    $reference_id    Reference ID.
	 * @return   array                  Array of citation objects.
	 */
	public static function get_by_reference( $reference_id ) {
		return self::search( array(
			'meta_query' => array(
				array(
					'key'   => '_abt_reference_id',
					'value' => $reference_id,
					'type'  => 'NUMERIC',
				),
			),
		) );
	}
}