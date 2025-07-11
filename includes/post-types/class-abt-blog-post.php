<?php
/**
 * Academic blog post model class
 *
 * Handles extended functionality for academic blog posts including
 * citation management and bibliography generation.
 *
 * @link       https://wbcomdesigns.com
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes/post-types
 */

/**
 * Academic blog post model class.
 *
 * Extends WordPress post functionality with academic features like
 * citation tracking, footnote management, and bibliography generation.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes/post-types
 * @author     Academic Blogger's Toolkit Team
 */
class ABT_Blog_Post {

	/**
	 * Post ID of the academic blog post.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $post_id    The post ID.
	 */
	private $post_id;

	/**
	 * Academic metadata.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $metadata    Academic metadata array.
	 */
	private $metadata;

	/**
	 * Citations used in this post.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $citations    Array of citation IDs.
	 */
	private $citations;

	/**
	 * Initialize the academic blog post.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Post ID of existing academic blog post.
	 */
	public function __construct( $post_id = null ) {
		if ( $post_id && get_post_type( $post_id ) === 'abt_blog' ) {
			$this->post_id = $post_id;
			$this->load_metadata();
			$this->load_citations();
		}
	}

	/**
	 * Create a new academic blog post.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Post data array.
	 * @return   int|WP_Error     Post ID on success, WP_Error on failure.
	 */
	public function create( $data ) {
		// Validate required fields
		if ( empty( $data['title'] ) ) {
			return new WP_Error( 'missing_title', __( 'Post title is required.', 'academic-bloggers-toolkit' ) );
		}

		// Prepare post data
		$post_data = array(
			'post_type'    => 'abt_blog',
			'post_status'  => isset( $data['status'] ) ? $data['status'] : 'draft',
			'post_title'   => sanitize_text_field( $data['title'] ),
			'post_content' => wp_kses_post( $data['content'] ?? '' ),
			'post_excerpt' => sanitize_textarea_field( $data['excerpt'] ?? '' ),
			'post_author'  => get_current_user_id(),
		);

		// Insert the post
		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$this->post_id = $post_id;

		// Save academic metadata
		$this->save_academic_metadata( $data );

		// Set taxonomies if provided
		if ( ! empty( $data['categories'] ) ) {
			wp_set_post_terms( $post_id, $data['categories'], 'abt_blog_category' );
		}

		if ( ! empty( $data['tags'] ) ) {
			wp_set_post_terms( $post_id, $data['tags'], 'abt_blog_tag' );
		}

		if ( ! empty( $data['subjects'] ) ) {
			wp_set_post_terms( $post_id, $data['subjects'], 'abt_subject' );
		}

		return $post_id;
	}

	/**
	 * Update an existing academic blog post.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Post data array.
	 * @return   bool|WP_Error    True on success, WP_Error on failure.
	 */
	public function update( $data ) {
		if ( ! $this->post_id ) {
			return new WP_Error( 'no_post', __( 'No post loaded for update.', 'academic-bloggers-toolkit' ) );
		}

		// Prepare post data
		$post_data = array(
			'ID' => $this->post_id,
		);

		if ( isset( $data['title'] ) ) {
			$post_data['post_title'] = sanitize_text_field( $data['title'] );
		}

		if ( isset( $data['content'] ) ) {
			$post_data['post_content'] = wp_kses_post( $data['content'] );
		}

		if ( isset( $data['excerpt'] ) ) {
			$post_data['post_excerpt'] = sanitize_textarea_field( $data['excerpt'] );
		}

		if ( isset( $data['status'] ) ) {
			$post_data['post_status'] = $data['status'];
		}

		// Update the post
		$result = wp_update_post( $post_data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Update academic metadata
		$this->save_academic_metadata( $data );

		// Update taxonomies
		if ( isset( $data['categories'] ) ) {
			wp_set_post_terms( $this->post_id, $data['categories'], 'abt_blog_category' );
		}

		if ( isset( $data['tags'] ) ) {
			wp_set_post_terms( $this->post_id, $data['tags'], 'abt_blog_tag' );
		}

		if ( isset( $data['subjects'] ) ) {
			wp_set_post_terms( $this->post_id, $data['subjects'], 'abt_subject' );
		}

		return true;
	}

	/**
	 * Load academic metadata from database.
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
	 * Load citations for this post.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_citations() {
		if ( ! $this->post_id ) {
			return;
		}

		// Get citation IDs stored in post meta
		$citation_ids = get_post_meta( $this->post_id, '_abt_citations', true );
		$this->citations = is_array( $citation_ids ) ? $citation_ids : array();
	}

	/**
	 * Save academic metadata.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $data    Post data array.
	 */
	private function save_academic_metadata( $data ) {
		if ( ! $this->post_id ) {
			return;
		}

		// Academic metadata fields
		$meta_fields = array(
			'citation_style'       => 'apa',
			'enable_bibliography'  => true,
			'bibliography_title'   => __( 'References', 'academic-bloggers-toolkit' ),
			'enable_footnotes'     => true,
			'footnote_style'       => 'numeric',
			'reading_time'         => 0,
			'word_count'           => 0,
			'academic_level'       => '',
			'peer_reviewed'        => false,
			'publication_date'     => '',
			'doi'                  => '',
			'research_area'        => '',
			'methodology'          => '',
			'keywords'             => array(),
			'abstract'             => '',
			'acknowledgments'      => '',
			'funding_sources'      => array(),
			'conflicts_of_interest' => '',
			'ethics_statement'     => '',
		);

		// Save each metadata field
		foreach ( $meta_fields as $field => $default ) {
			if ( isset( $data[ $field ] ) ) {
				$value = $data[ $field ];

				// Handle arrays
				if ( is_array( $value ) ) {
					$value = array_map( 'sanitize_text_field', $value );
				} else {
					$value = sanitize_text_field( $value );
				}

				update_post_meta( $this->post_id, '_abt_' . $field, $value );
			} elseif ( ! metadata_exists( 'post', $this->post_id, '_abt_' . $field ) ) {
				// Set default value if meta doesn't exist
				update_post_meta( $this->post_id, '_abt_' . $field, $default );
			}
		}

		// Calculate word count and reading time if content provided
		if ( isset( $data['content'] ) ) {
			$this->calculate_metrics( $data['content'] );
		}

		// Update our internal metadata
		$this->load_metadata();
	}

	/**
	 * Calculate reading metrics.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string    $content    Post content.
	 */
	private function calculate_metrics( $content ) {
		// Strip HTML and calculate word count
		$text = strip_tags( $content );
		$word_count = str_word_count( $text );

		// Calculate reading time (average 200 words per minute)
		$reading_time = ceil( $word_count / 200 );

		update_post_meta( $this->post_id, '_abt_word_count', $word_count );
		update_post_meta( $this->post_id, '_abt_reading_time', $reading_time );
	}

	/**
	 * Add a citation to this post.
	 *
	 * @since    1.0.0
	 * @param    int      $reference_id    Reference post ID.
	 * @param    array    $citation_data   Citation-specific data.
	 * @return   int|WP_Error            Citation ID on success, WP_Error on failure.
	 */
	public function add_citation( $reference_id, $citation_data = array() ) {
		if ( ! $this->post_id ) {
			return new WP_Error( 'no_post', __( 'No post loaded.', 'academic-bloggers-toolkit' ) );
		}

		// Verify reference exists
		if ( get_post_type( $reference_id ) !== 'abt_reference' ) {
			return new WP_Error( 'invalid_reference', __( 'Invalid reference ID.', 'academic-bloggers-toolkit' ) );
		}

		// Create citation post
		$citation_data = wp_parse_args( $citation_data, array(
			'page_number'    => '',
			'prefix'         => '',
			'suffix'         => '',
			'locator'        => '',
			'suppress_author' => false,
			'citation_style' => $this->get_meta( 'citation_style', 'apa' ),
		) );

		$citation_post = array(
			'post_type'   => 'abt_citation',
			'post_status' => 'publish',
			'post_title'  => sprintf( 'Citation for post %d, reference %d', $this->post_id, $reference_id ),
			'post_parent' => $this->post_id,
		);

		$citation_id = wp_insert_post( $citation_post );

		if ( is_wp_error( $citation_id ) ) {
			return $citation_id;
		}

		// Save citation metadata
		update_post_meta( $citation_id, '_abt_reference_id', $reference_id );
		update_post_meta( $citation_id, '_abt_post_id', $this->post_id );

		foreach ( $citation_data as $key => $value ) {
			update_post_meta( $citation_id, '_abt_' . $key, sanitize_text_field( $value ) );
		}

		// Add to post's citations array
		$this->citations[] = $citation_id;
		update_post_meta( $this->post_id, '_abt_citations', $this->citations );

		return $citation_id;
	}

	/**
	 * Remove a citation from this post.
	 *
	 * @since    1.0.0
	 * @param    int    $citation_id    Citation post ID.
	 * @return   bool                  True on success, false on failure.
	 */
	public function remove_citation( $citation_id ) {
		if ( ! $this->post_id ) {
			return false;
		}

		// Remove from citations array
		$key = array_search( $citation_id, $this->citations );
		if ( $key !== false ) {
			unset( $this->citations[ $key ] );
			update_post_meta( $this->post_id, '_abt_citations', array_values( $this->citations ) );
		}

		// Delete citation post
		return wp_delete_post( $citation_id, true ) !== false;
	}

	/**
	 * Get all citations for this post.
	 *
	 * @since    1.0.0
	 * @return   array    Array of citation data.
	 */
	public function get_citations() {
		if ( ! $this->post_id ) {
			return array();
		}

		$citations_data = array();

		foreach ( $this->citations as $citation_id ) {
			$citation_post = get_post( $citation_id );
			if ( ! $citation_post ) {
				continue;
			}

			$reference_id = get_post_meta( $citation_id, '_abt_reference_id', true );
			$citation_meta = get_post_meta( $citation_id );

			$citations_data[] = array(
				'citation_id'  => $citation_id,
				'reference_id' => $reference_id,
				'page_number'  => get_post_meta( $citation_id, '_abt_page_number', true ),
				'prefix'       => get_post_meta( $citation_id, '_abt_prefix', true ),
				'suffix'       => get_post_meta( $citation_id, '_abt_suffix', true ),
				'locator'      => get_post_meta( $citation_id, '_abt_locator', true ),
				'suppress_author' => get_post_meta( $citation_id, '_abt_suppress_author', true ),
			);
		}

		return $citations_data;
	}

	/**
	 * Generate bibliography for this post.
	 *
	 * @since    1.0.0
	 * @return   string|WP_Error    HTML bibliography or WP_Error on failure.
	 */
	public function generate_bibliography() {
		if ( ! $this->post_id ) {
			return new WP_Error( 'no_post', __( 'No post loaded.', 'academic-bloggers-toolkit' ) );
		}

		if ( empty( $this->citations ) ) {
			return '';
		}

		// Get unique references from citations
		$reference_ids = array();
		foreach ( $this->citations as $citation_id ) {
			$reference_id = get_post_meta( $citation_id, '_abt_reference_id', true );
			if ( $reference_id && ! in_array( $reference_id, $reference_ids ) ) {
				$reference_ids[] = $reference_id;
			}
		}

		if ( empty( $reference_ids ) ) {
			return '';
		}

		// Get citation style
		$citation_style = $this->get_meta( 'citation_style', 'apa' );
		$bibliography_title = $this->get_meta( 'bibliography_title', __( 'References', 'academic-bloggers-toolkit' ) );

		// Build bibliography HTML
		$html = '<div class="abt-bibliography">';
		$html .= '<h3 class="abt-bibliography-title">' . esc_html( $bibliography_title ) . '</h3>';
		$html .= '<ol class="abt-bibliography-list">';

		foreach ( $reference_ids as $reference_id ) {
			$reference = new ABT_Reference( $reference_id );
			$reference_data = $reference->get_data();

			if ( $reference_data ) {
				$formatted_reference = $this->format_reference( $reference_data, $citation_style );
				$html .= '<li class="abt-bibliography-item">' . $formatted_reference . '</li>';
			}
		}

		$html .= '</ol>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Format a reference according to citation style.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array     $reference_data    Reference data.
	 * @param    string    $style            Citation style.
	 * @return   string                      Formatted reference.
	 */
	private function format_reference( $reference_data, $style = 'apa' ) {
		// Basic formatting - will be enhanced in citation processing engine
		$formatted = '';

		// Authors
		if ( ! empty( $reference_data['authors'] ) ) {
			$authors = is_array( $reference_data['authors'] ) ? $reference_data['authors'] : array( $reference_data['authors'] );
			$formatted .= implode( ', ', $authors ) . '. ';
		}

		// Publication year
		if ( ! empty( $reference_data['publication_year'] ) ) {
			$formatted .= '(' . $reference_data['publication_year'] . '). ';
		}

		// Title
		$formatted .= '<em>' . esc_html( $reference_data['title'] ) . '</em>. ';

		// Journal/Publisher info
		if ( ! empty( $reference_data['journal'] ) ) {
			$formatted .= '<em>' . esc_html( $reference_data['journal'] ) . '</em>';

			if ( ! empty( $reference_data['volume'] ) ) {
				$formatted .= ', ' . esc_html( $reference_data['volume'] );
			}

			if ( ! empty( $reference_data['issue'] ) ) {
				$formatted .= '(' . esc_html( $reference_data['issue'] ) . ')';
			}

			if ( ! empty( $reference_data['pages'] ) ) {
				$formatted .= ', ' . esc_html( $reference_data['pages'] );
			}

			$formatted .= '. ';
		} elseif ( ! empty( $reference_data['publisher'] ) ) {
			$formatted .= esc_html( $reference_data['publisher'] ) . '. ';
		}

		// DOI or URL
		if ( ! empty( $reference_data['doi'] ) ) {
			$formatted .= 'https://doi.org/' . esc_html( $reference_data['doi'] );
		} elseif ( ! empty( $reference_data['url'] ) ) {
			$formatted .= esc_url( $reference_data['url'] );
		}

		return $formatted;
	}

	/**
	 * Get post data with academic metadata.
	 *
	 * @since    1.0.0
	 * @return   array|null    Post data array or null if no post loaded.
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
			'id'           => $this->post_id,
			'title'        => $post->post_title,
			'content'      => $post->post_content,
			'excerpt'      => $post->post_excerpt,
			'status'       => $post->post_status,
			'author'       => $post->post_author,
			'created'      => $post->post_date,
			'modified'     => $post->post_modified,
			'permalink'    => get_permalink( $this->post_id ),
		);

		// Add academic metadata
		if ( $this->metadata ) {
			foreach ( $this->metadata as $key => $value ) {
				// Remove _abt_ prefix from keys
				$clean_key = str_replace( '_abt_', '', $key );
				$data[ $clean_key ] = $value;
			}
		}

		// Add taxonomies
		$data['categories'] = wp_get_post_terms( $this->post_id, 'abt_blog_category', array( 'fields' => 'names' ) );
		$data['tags'] = wp_get_post_terms( $this->post_id, 'abt_blog_tag', array( 'fields' => 'names' ) );
		$data['subjects'] = wp_get_post_terms( $this->post_id, 'abt_subject', array( 'fields' => 'names' ) );

		// Add citation data
		$data['citations'] = $this->get_citations();
		$data['citation_count'] = count( $this->citations );

		return $data;
	}

	/**
	 * Get post ID.
	 *
	 * @since    1.0.0
	 * @return   int|null    Post ID or null if no post loaded.
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
	 * Search academic blog posts.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Search arguments.
	 * @return   array           Array of ABT_Blog_Post objects.
	 */
	public static function search( $args = array() ) {
		$defaults = array(
			'post_type'      => 'abt_blog',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );
		$posts = get_posts( $args );

		$blog_posts = array();
		foreach ( $posts as $post ) {
			$blog_post = new self( $post->ID );
			$blog_posts[] = $blog_post;
		}

		return $blog_posts;
	}

	/**
	 * Get academic blog post count.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   int             Number of posts.
	 */
	public static function get_count( $args = array() ) {
		$defaults = array(
			'post_type'   => 'abt_blog',
			'post_status' => 'publish',
		);

		$args = wp_parse_args( $args, $defaults );
		$query = new WP_Query( $args );

		return $query->found_posts;
	}
}