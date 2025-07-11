<?php
/**
 * Bibliography model class
 *
 * Handles generated bibliographies for academic blog posts.
 *
 * @link       https://academic-bloggers-toolkit.com
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes/post-types
 */

/**
 * Bibliography model class.
 *
 * Manages generated bibliographies including caching, formatting,
 * and automatic updates when citations change.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes/post-types
 * @author     Academic Blogger's Toolkit Team
 */
class ABT_Bibliography {

	/**
	 * Post ID of the bibliography.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $post_id    The bibliography post ID.
	 */
	private $post_id;

	/**
	 * Bibliography metadata.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $metadata    Bibliography metadata array.
	 */
	private $metadata;

	/**
	 * Initialize the bibliography.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Optional. Post ID of existing bibliography.
	 */
	public function __construct( $post_id = null ) {
		if ( $post_id && get_post_type( $post_id ) === 'abt_bibliography' ) {
			$this->post_id = $post_id;
			$this->load_metadata();
		}
	}

	/**
	 * Create a new bibliography.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Bibliography data array.
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
			'post_type'    => 'abt_bibliography',
			'post_status'  => 'publish',
			'post_title'   => sprintf( 
				'Bibliography for post %d', 
				$data['source_post_id'] 
			),
			'post_content' => isset( $data['content'] ) ? wp_kses_post( $data['content'] ) : '',
			'post_parent'  => $data['source_post_id'],
			'post_author'  => get_current_user_id(),
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
	 * Update an existing bibliography.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Bibliography data array.
	 * @return   bool|WP_Error    True on success, WP_Error on failure.
	 */
	public function update( $data ) {
		if ( ! $this->post_id ) {
			return new WP_Error( 'no_bibliography', __( 'No bibliography loaded for update.', 'academic-bloggers-toolkit' ) );
		}

		// Validate data
		$validation = $this->validate_data( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Prepare post data
		$post_data = array(
			'ID' => $this->post_id,
		);

		if ( isset( $data['content'] ) ) {
			$post_data['post_content'] = wp_kses_post( $data['content'] );
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
	 * Delete the bibliography.
	 *
	 * @since    1.0.0
	 * @param    bool    $force_delete    Whether to force delete or move to trash.
	 * @return   bool|WP_Error           True on success, WP_Error on failure.
	 */
	public function delete( $force_delete = true ) {
		if ( ! $this->post_id ) {
			return new WP_Error( 'no_bibliography', __( 'No bibliography loaded for deletion.', 'academic-bloggers-toolkit' ) );
		}

		$result = wp_delete_post( $this->post_id, $force_delete );

		if ( $result ) {
			$this->post_id = null;
			$this->metadata = array();
			return true;
		}

		return new WP_Error( 'delete_failed', __( 'Failed to delete bibliography.', 'academic-bloggers-toolkit' ) );
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
	 * @param    array    $data    Bibliography data array.
	 */
	private function save_metadata( $data ) {
		if ( ! $this->post_id ) {
			return;
		}

		// Define metadata fields
		$meta_fields = array(
			'source_post_id',
			'citation_style',
			'bibliography_title',
			'reference_ids',
			'citation_ids',
			'sort_order',
			'format_style',
			'include_urls',
			'include_doi',
			'hanging_indent',
			'line_spacing',
			'font_family',
			'font_size',
			'generated_date',
			'last_updated',
			'auto_update',
			'cache_key',
		);

		// Save each metadata field
		foreach ( $meta_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$value = $data[ $field ];

				// Sanitize based on field type
				switch ( $field ) {
					case 'source_post_id':
						$value = intval( $value );
						break;
					case 'reference_ids':
					case 'citation_ids':
						$value = is_array( $value ) ? array_map( 'intval', $value ) : array();
						break;
					case 'auto_update':
					case 'include_urls':
					case 'include_doi':
					case 'hanging_indent':
						$value = (bool) $value;
						break;
					case 'line_spacing':
					case 'font_size':
						$value = floatval( $value );
						break;
					default:
						$value = sanitize_text_field( $value );
						break;
				}

				update_post_meta( $this->post_id, '_abt_' . $field, $value );
			}
		}

		// Set timestamps
		if ( ! get_post_meta( $this->post_id, '_abt_generated_date', true ) ) {
			update_post_meta( $this->post_id, '_abt_generated_date', current_time( 'mysql' ) );
		}
		update_post_meta( $this->post_id, '_abt_last_updated', current_time( 'mysql' ) );

		// Generate cache key
		$cache_key = $this->generate_cache_key();
		update_post_meta( $this->post_id, '_abt_cache_key', $cache_key );

		// Update our internal metadata
		$this->load_metadata();
	}

	/**
	 * Validate bibliography data.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $data    Bibliography data to validate.
	 * @return   bool|WP_Error    True if valid, WP_Error if not.
	 */
	private function validate_data( $data ) {
		// Check required fields
		if ( empty( $data['source_post_id'] ) ) {
			return new WP_Error( 'missing_source_post', __( 'Source post ID is required.', 'academic-bloggers-toolkit' ) );
		}

		// Validate post exists
		if ( get_post_type( $data['source_post_id'] ) !== 'abt_blog' ) {
			return new WP_Error( 'invalid_source_post', __( 'Invalid source post ID.', 'academic-bloggers-toolkit' ) );
		}

		return true;
	}

	/**
	 * Generate bibliography content.
	 *
	 * @since    1.0.0
	 * @param    array    $options    Generation options.
	 * @return   string|WP_Error     Generated HTML or WP_Error on failure.
	 */
	public function generate( $options = array() ) {
		if ( ! $this->post_id ) {
			return new WP_Error( 'no_bibliography', __( 'No bibliography loaded.', 'academic-bloggers-toolkit' ) );
		}

		$source_post_id = $this->get_meta( 'source_post_id' );
		if ( ! $source_post_id ) {
			return new WP_Error( 'no_source_post', __( 'No source post specified.', 'academic-bloggers-toolkit' ) );
		}

		// Get options
		$options = wp_parse_args( $options, array(
			'style'            => $this->get_meta( 'citation_style', 'apa' ),
			'title'            => $this->get_meta( 'bibliography_title', __( 'References', 'academic-bloggers-toolkit' ) ),
			'sort_order'       => $this->get_meta( 'sort_order', 'alphabetical' ),
			'include_urls'     => $this->get_meta( 'include_urls', true ),
			'include_doi'      => $this->get_meta( 'include_doi', true ),
			'hanging_indent'   => $this->get_meta( 'hanging_indent', true ),
		) );

		// Get citations from source post
		$blog_post = new ABT_Blog_Post( $source_post_id );
		$citations = $blog_post->get_citations();

		if ( empty( $citations ) ) {
			return '';
		}

		// Get unique references
		$reference_ids = array();
		foreach ( $citations as $citation ) {
			if ( ! in_array( $citation['reference_id'], $reference_ids ) ) {
				$reference_ids[] = $citation['reference_id'];
			}
		}

		// Get reference data
		$references = array();
		foreach ( $reference_ids as $ref_id ) {
			$reference = new ABT_Reference( $ref_id );
			$ref_data = $reference->get_data();
			if ( $ref_data ) {
				$references[] = $ref_data;
			}
		}

		// Sort references
		$references = $this->sort_references( $references, $options['sort_order'] );

		// Generate HTML
		$html = $this->format_bibliography_html( $references, $options );

		// Update bibliography content
		wp_update_post( array(
			'ID'           => $this->post_id,
			'post_content' => $html,
		) );

		// Update metadata
		update_post_meta( $this->post_id, '_abt_reference_ids', $reference_ids );
		update_post_meta( $this->post_id, '_abt_last_updated', current_time( 'mysql' ) );

		return $html;
	}

	/**
	 * Sort references according to specified order.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array     $references    Array of reference data.
	 * @param    string    $sort_order    Sort order type.
	 * @return   array                   Sorted references.
	 */
	private function sort_references( $references, $sort_order = 'alphabetical' ) {
		switch ( $sort_order ) {
			case 'chronological':
				usort( $references, function( $a, $b ) {
					$year_a = isset( $a['publication_year'] ) ? intval( $a['publication_year'] ) : 0;
					$year_b = isset( $b['publication_year'] ) ? intval( $b['publication_year'] ) : 0;
					return $year_a - $year_b;
				} );
				break;
			case 'reverse_chronological':
				usort( $references, function( $a, $b ) {
					$year_a = isset( $a['publication_year'] ) ? intval( $a['publication_year'] ) : 0;
					$year_b = isset( $b['publication_year'] ) ? intval( $b['publication_year'] ) : 0;
					return $year_b - $year_a;
				} );
				break;
			case 'citation_order':
				// Keep original order (already sorted by citation appearance)
				break;
			case 'alphabetical':
			default:
				usort( $references, function( $a, $b ) {
					// Sort by first author's last name, then by year, then by title
					$author_a = $this->get_primary_author_lastname( $a );
					$author_b = $this->get_primary_author_lastname( $b );
					
					$compare = strcmp( $author_a, $author_b );
					if ( $compare === 0 ) {
						$year_a = isset( $a['publication_year'] ) ? intval( $a['publication_year'] ) : 0;
						$year_b = isset( $b['publication_year'] ) ? intval( $b['publication_year'] ) : 0;
						$compare = $year_a - $year_b;
						
						if ( $compare === 0 ) {
							$compare = strcmp( $a['title'], $b['title'] );
						}
					}
					
					return $compare;
				} );
				break;
		}

		return $references;
	}

	/**
	 * Get primary author's last name for sorting.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $reference    Reference data.
	 * @return   string               Last name of primary author.
	 */
	private function get_primary_author_lastname( $reference ) {
		if ( empty( $reference['authors'] ) ) {
			return $reference['title'] ?? '';
		}

		$authors = is_array( $reference['authors'] ) ? $reference['authors'] : array( $reference['authors'] );
		$primary_author = $authors[0];

		// Extract last name (assuming "Last, First" or "First Last" format)
		if ( strpos( $primary_author, ',' ) !== false ) {
			$parts = explode( ',', $primary_author );
			return trim( $parts[0] );
		} else {
			$parts = explode( ' ', $primary_author );
			return trim( end( $parts ) );
		}
	}

	/**
	 * Format bibliography HTML.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $references    Array of reference data.
	 * @param    array    $options       Formatting options.
	 * @return   string                 Formatted HTML.
	 */
	private function format_bibliography_html( $references, $options ) {
		$class_names = array( 'abt-bibliography' );
		
		if ( $options['hanging_indent'] ) {
			$class_names[] = 'abt-hanging-indent';
		}

		$html = sprintf(
			'<div class="%s">',
			esc_attr( implode( ' ', $class_names ) )
		);

		if ( $options['title'] ) {
			$html .= sprintf(
				'<h3 class="abt-bibliography-title">%s</h3>',
				esc_html( $options['title'] )
			);
		}

		$html .= '<ol class="abt-bibliography-list">';

		foreach ( $references as $reference ) {
			$formatted_ref = $this->format_reference( $reference, $options );
			$html .= sprintf(
				'<li class="abt-bibliography-item" data-reference-id="%d">%s</li>',
				$reference['id'],
				$formatted_ref
			);
		}

		$html .= '</ol>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Format individual reference.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $reference    Reference data.
	 * @param    array    $options      Formatting options.
	 * @return   string                Formatted reference HTML.
	 */
	private function format_reference( $reference, $options ) {
		// This is basic formatting - will be enhanced by citation processing engine
		$formatted = '';

		// Authors
		if ( ! empty( $reference['authors'] ) ) {
			$authors = is_array( $reference['authors'] ) ? $reference['authors'] : array( $reference['authors'] );
			
			switch ( $options['style'] ) {
				case 'apa':
					$formatted .= $this->format_apa_authors( $authors );
					break;
				case 'mla':
					$formatted .= $this->format_mla_authors( $authors );
					break;
				case 'chicago':
					$formatted .= $this->format_chicago_authors( $authors );
					break;
				default:
					$formatted .= $this->format_apa_authors( $authors );
					break;
			}
			$formatted .= ' ';
		}

		// Publication year
		if ( ! empty( $reference['publication_year'] ) ) {
			$year_format = ( $options['style'] === 'apa' ) ? '(%s). ' : '%s. ';
			$formatted .= sprintf( $year_format, esc_html( $reference['publication_year'] ) );
		}

		// Title
		if ( $reference['reference_type'] === 'journal_article' ) {
			$formatted .= esc_html( $reference['title'] ) . '. ';
		} else {
			$formatted .= '<em>' . esc_html( $reference['title'] ) . '</em>. ';
		}

		// Journal/Publisher info
		if ( ! empty( $reference['journal'] ) ) {
			$formatted .= '<em>' . esc_html( $reference['journal'] ) . '</em>';

			if ( ! empty( $reference['volume'] ) ) {
				$formatted .= ', <em>' . esc_html( $reference['volume'] ) . '</em>';
			}

			if ( ! empty( $reference['issue'] ) ) {
				$formatted .= '(' . esc_html( $reference['issue'] ) . ')';
			}

			if ( ! empty( $reference['pages'] ) ) {
				$formatted .= ', ' . esc_html( $reference['pages'] );
			}

			$formatted .= '. ';
		} elseif ( ! empty( $reference['publisher'] ) ) {
			$formatted .= esc_html( $reference['publisher'] );
			
			if ( ! empty( $reference['publication_place'] ) ) {
				$formatted .= ', ' . esc_html( $reference['publication_place'] );
			}
			
			$formatted .= '. ';
		}

		// DOI or URL
		if ( $options['include_doi'] && ! empty( $reference['doi'] ) ) {
			$formatted .= sprintf(
				'<a href="https://doi.org/%s" target="_blank" rel="noopener">https://doi.org/%s</a>',
				esc_attr( $reference['doi'] ),
				esc_html( $reference['doi'] )
			);
		} elseif ( $options['include_urls'] && ! empty( $reference['url'] ) ) {
			$formatted .= sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( $reference['url'] ),
				esc_html( $reference['url'] )
			);
		}

		return $formatted;
	}

	/**
	 * Format authors in APA style.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $authors    Array of author names.
	 * @return   string              Formatted author list.
	 */
	private function format_apa_authors( $authors ) {
		if ( count( $authors ) === 1 ) {
			return $authors[0] . '.';
		} elseif ( count( $authors ) === 2 ) {
			return $authors[0] . ', & ' . $authors[1] . '.';
		} else {
			$last_author = array_pop( $authors );
			return implode( ', ', $authors ) . ', & ' . $last_author . '.';
		}
	}

	/**
	 * Format authors in MLA style.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $authors    Array of author names.
	 * @return   string              Formatted author list.
	 */
	private function format_mla_authors( $authors ) {
		if ( count( $authors ) === 1 ) {
			return $authors[0] . '.';
		} else {
			return $authors[0] . ', et al.';
		}
	}

	/**
	 * Format authors in Chicago style.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $authors    Array of author names.
	 * @return   string              Formatted author list.
	 */
	private function format_chicago_authors( $authors ) {
		if ( count( $authors ) === 1 ) {
			return $authors[0] . '.';
		} elseif ( count( $authors ) === 2 ) {
			return $authors[0] . ' and ' . $authors[1] . '.';
		} else {
			$last_author = array_pop( $authors );
			return implode( ', ', $authors ) . ', and ' . $last_author . '.';
		}
	}

	/**
	 * Generate cache key for bibliography.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   string    Cache key.
	 */
	private function generate_cache_key() {
		$source_post_id = $this->get_meta( 'source_post_id' );
		$style = $this->get_meta( 'citation_style', 'apa' );
		$sort_order = $this->get_meta( 'sort_order', 'alphabetical' );
		
		// Get citations hash for cache invalidation
		$blog_post = new ABT_Blog_Post( $source_post_id );
		$citations = $blog_post->get_citations();
		$citations_hash = md5( serialize( $citations ) );
		
		return md5( $source_post_id . $style . $sort_order . $citations_hash );
	}

	/**
	 * Check if bibliography needs regeneration.
	 *
	 * @since    1.0.0
	 * @return   bool    True if regeneration is needed.
	 */
	public function needs_regeneration() {
		if ( ! $this->post_id ) {
			return true;
		}

		$stored_cache_key = $this->get_meta( 'cache_key' );
		$current_cache_key = $this->generate_cache_key();

		return $stored_cache_key !== $current_cache_key;
	}

	/**
	 * Get bibliography data.
	 *
	 * @since    1.0.0
	 * @return   array|null    Bibliography data array or null if no bibliography loaded.
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
			'content'     => $post->post_content,
			'source_post_id' => $post->post_parent,
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
	 * @return   int|null    Post ID or null if no bibliography loaded.
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
	 * Get bibliography for a specific post.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Post ID.
	 * @return   ABT_Bibliography|null    Bibliography object or null if not found.
	 */
	public static function get_by_post( $post_id ) {
		$posts = get_posts( array(
			'post_type'   => 'abt_bibliography',
			'post_parent' => $post_id,
			'numberposts' => 1,
		) );

		if ( empty( $posts ) ) {
			return null;
		}

		return new self( $posts[0]->ID );
	}

	/**
	 * Create or update bibliography for a post.
	 *
	 * @since    1.0.0
	 * @param    int      $post_id    Post ID.
	 * @param    array    $options    Generation options.
	 * @return   ABT_Bibliography|WP_Error    Bibliography object or WP_Error on failure.
	 */
	public static function create_or_update_for_post( $post_id, $options = array() ) {
		$existing = self::get_by_post( $post_id );

		if ( $existing ) {
			// Update existing bibliography
			$result = $existing->generate( $options );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			return $existing;
		} else {
			// Create new bibliography
			$bibliography = new self();
			$data = wp_parse_args( $options, array(
				'source_post_id' => $post_id,
			) );
			
			$bib_id = $bibliography->create( $data );
			if ( is_wp_error( $bib_id ) ) {
				return $bib_id;
			}
			
			$result = $bibliography->generate( $options );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			
			return $bibliography;
		}
	}
}