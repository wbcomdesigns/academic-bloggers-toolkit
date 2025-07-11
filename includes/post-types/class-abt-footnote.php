<?php
/**
 * Footnote model class
 *
 * Handles footnote management for academic blog posts.
 *
 * @link       https://wbcomdesigns.com
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes/post-types
 */

/**
 * Footnote model class.
 *
 * Manages footnotes with support for different numbering styles,
 * positioning, and content formatting.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes/post-types
 * @author     Academic Blogger's Toolkit Team
 */
class ABT_Footnote {

	/**
	 * Post ID of the footnote.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $post_id    The footnote post ID.
	 */
	private $post_id;

	/**
	 * Footnote metadata.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $metadata    Footnote metadata array.
	 */
	private $metadata;

	/**
	 * Valid footnote numbering styles.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $valid_styles    Array of valid numbering styles.
	 */
	private static $valid_styles = array(
		'numeric',     // 1, 2, 3...
		'roman',       // i, ii, iii...
		'alpha',       // a, b, c...
		'symbols',     // *, †, ‡...
	);

	/**
	 * Initialize the footnote.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Optional. Post ID of existing footnote.
	 */
	public function __construct( $post_id = null ) {
		if ( $post_id && get_post_type( $post_id ) === 'abt_footnote' ) {
			$this->post_id = $post_id;
			$this->load_metadata();
		}
	}

	/**
	 * Create a new footnote.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Footnote data array.
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
			'post_type'    => 'abt_footnote',
			'post_status'  => 'publish',
			'post_title'   => sprintf( 
				'Footnote %d for post %d', 
				$data['footnote_number'] ?? 1, 
				$data['post_id'] 
			),
			'post_content' => wp_kses_post( $data['content'] ),
			'post_parent'  => $data['post_id'],
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
	 * Update an existing footnote.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Footnote data array.
	 * @return   bool|WP_Error    True on success, WP_Error on failure.
	 */
	public function update( $data ) {
		if ( ! $this->post_id ) {
			return new WP_Error( 'no_footnote', __( 'No footnote loaded for update.', 'academic-bloggers-toolkit' ) );
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
	 * Delete the footnote.
	 *
	 * @since    1.0.0
	 * @param    bool    $force_delete    Whether to force delete or move to trash.
	 * @return   bool|WP_Error           True on success, WP_Error on failure.
	 */
	public function delete( $force_delete = true ) {
		if ( ! $this->post_id ) {
			return new WP_Error( 'no_footnote', __( 'No footnote loaded for deletion.', 'academic-bloggers-toolkit' ) );
		}

		$result = wp_delete_post( $this->post_id, $force_delete );

		if ( $result ) {
			$this->post_id = null;
			$this->metadata = array();
			return true;
		}

		return new WP_Error( 'delete_failed', __( 'Failed to delete footnote.', 'academic-bloggers-toolkit' ) );
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
	 * @param    array    $data    Footnote data array.
	 */
	private function save_metadata( $data ) {
		if ( ! $this->post_id ) {
			return;
		}

		// Define metadata fields
		$meta_fields = array(
			'post_id',
			'footnote_number',
			'footnote_style',
			'position_in_text',
			'anchor_text',
			'reference_id',
			'citation_id',
			'is_citation_footnote',
			'display_symbol',
			'back_link_text',
			'tooltip_enabled',
			'created_date',
			'last_updated',
		);

		// Save each metadata field
		foreach ( $meta_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$value = $data[ $field ];

				// Sanitize based on field type
				switch ( $field ) {
					case 'post_id':
					case 'footnote_number':
					case 'position_in_text':
					case 'reference_id':
					case 'citation_id':
						$value = intval( $value );
						break;
					case 'is_citation_footnote':
					case 'tooltip_enabled':
						$value = (bool) $value;
						break;
					default:
						$value = sanitize_text_field( $value );
						break;
				}

				update_post_meta( $this->post_id, '_abt_' . $field, $value );
			}
		}

		// Set timestamps
		if ( ! get_post_meta( $this->post_id, '_abt_created_date', true ) ) {
			update_post_meta( $this->post_id, '_abt_created_date', current_time( 'mysql' ) );
		}
		update_post_meta( $this->post_id, '_abt_last_updated', current_time( 'mysql' ) );

		// Update our internal metadata
		$this->load_metadata();
	}

	/**
	 * Validate footnote data.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $data    Footnote data to validate.
	 * @return   bool|WP_Error    True if valid, WP_Error if not.
	 */
	private function validate_data( $data ) {
		// Check required fields
		if ( empty( $data['post_id'] ) ) {
			return new WP_Error( 'missing_post', __( 'Post ID is required.', 'academic-bloggers-toolkit' ) );
		}

		if ( empty( $data['content'] ) ) {
			return new WP_Error( 'missing_content', __( 'Footnote content is required.', 'academic-bloggers-toolkit' ) );
		}

		// Validate post exists
		if ( get_post_type( $data['post_id'] ) !== 'abt_blog' ) {
			return new WP_Error( 'invalid_post', __( 'Invalid post ID.', 'academic-bloggers-toolkit' ) );
		}

		// Validate footnote style if provided
		if ( ! empty( $data['footnote_style'] ) && ! in_array( $data['footnote_style'], self::$valid_styles, true ) ) {
			return new WP_Error( 'invalid_style', __( 'Invalid footnote style.', 'academic-bloggers-toolkit' ) );
		}

		return true;
	}

	/**
	 * Generate footnote symbol based on number and style.
	 *
	 * @since    1.0.0
	 * @param    int     $number    Footnote number.
	 * @param    string  $style     Footnote style.
	 * @return   string            Generated symbol.
	 */
	public static function generate_symbol( $number, $style = 'numeric' ) {
		switch ( $style ) {
			case 'roman':
				return self::number_to_roman( $number );
			case 'alpha':
				return self::number_to_alpha( $number );
			case 'symbols':
				return self::number_to_symbols( $number );
			case 'numeric':
			default:
				return (string) $number;
		}
	}

	/**
	 * Convert number to roman numerals.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    int    $number    Number to convert.
	 * @return   string           Roman numeral.
	 */
	private static function number_to_roman( $number ) {
		$map = array(
			'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
			'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
			'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1
		);
		
		$result = '';
		foreach ( $map as $roman => $value ) {
			$count = intval( $number / $value );
			$result .= str_repeat( $roman, $count );
			$number %= $value;
		}
		
		return strtolower( $result );
	}

	/**
	 * Convert number to alphabetic.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    int    $number    Number to convert.
	 * @return   string           Alphabetic character(s).
	 */
	private static function number_to_alpha( $number ) {
		$result = '';
		while ( $number > 0 ) {
			$number--;
			$result = chr( 97 + ( $number % 26 ) ) . $result;
			$number = intval( $number / 26 );
		}
		return $result;
	}

	/**
	 * Convert number to symbols.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    int    $number    Number to convert.
	 * @return   string           Symbol(s).
	 */
	private static function number_to_symbols( $number ) {
		$symbols = array( '*', '†', '‡', '§', '¶', '#' );
		$symbol_count = count( $symbols );
		
		if ( $number <= $symbol_count ) {
			return $symbols[ $number - 1 ];
		}
		
		// For numbers beyond available symbols, repeat the last symbol
		$repeats = ceil( $number / $symbol_count );
		$symbol_index = ( $number - 1 ) % $symbol_count;
		
		return str_repeat( $symbols[ $symbol_index ], $repeats );
	}

	/**
	 * Format footnote for display.
	 *
	 * @since    1.0.0
	 * @param    string    $format    Display format ('inline', 'popup', 'bottom').
	 * @return   array              Formatted footnote data.
	 */
	public function format_footnote( $format = 'bottom' ) {
		if ( ! $this->post_id ) {
			return array();
		}

		$post = get_post( $this->post_id );
		if ( ! $post ) {
			return array();
		}

		$number = $this->get_meta( 'footnote_number', 1 );
		$style = $this->get_meta( 'footnote_style', 'numeric' );
		$symbol = $this->generate_symbol( $number, $style );

		$formatted = array(
			'id'           => $this->post_id,
			'number'       => $number,
			'symbol'       => $symbol,
			'content'      => $post->post_content,
			'anchor_id'    => 'abt-footnote-' . $this->post_id,
			'back_link_id' => 'abt-footnote-ref-' . $this->post_id,
		);

		switch ( $format ) {
			case 'inline':
				$formatted['html'] = $this->format_inline_footnote( $formatted );
				break;
			case 'popup':
				$formatted['html'] = $this->format_popup_footnote( $formatted );
				break;
			case 'bottom':
			default:
				$formatted['html'] = $this->format_bottom_footnote( $formatted );
				break;
		}

		return $formatted;
	}

	/**
	 * Format inline footnote.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $data    Footnote data.
	 * @return   string           Formatted HTML.
	 */
	private function format_inline_footnote( $data ) {
		return sprintf(
			'<span class="abt-footnote-inline" id="%s">%s</span>',
			esc_attr( $data['anchor_id'] ),
			wp_kses_post( $data['content'] )
		);
	}

	/**
	 * Format popup footnote.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $data    Footnote data.
	 * @return   string           Formatted HTML.
	 */
	private function format_popup_footnote( $data ) {
		return sprintf(
			'<span class="abt-footnote-popup" id="%s" data-content="%s" title="%s">%s</span>',
			esc_attr( $data['anchor_id'] ),
			esc_attr( wp_strip_all_tags( $data['content'] ) ),
			esc_attr( wp_strip_all_tags( $data['content'] ) ),
			esc_html( $data['symbol'] )
		);
	}

	/**
	 * Format bottom footnote.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $data    Footnote data.
	 * @return   string           Formatted HTML.
	 */
	private function format_bottom_footnote( $data ) {
		return sprintf(
			'<li class="abt-footnote-item" id="%s">%s <a href="#%s" class="abt-footnote-backlink">↩</a></li>',
			esc_attr( $data['anchor_id'] ),
			wp_kses_post( $data['content'] ),
			esc_attr( $data['back_link_id'] )
		);
	}

	/**
	 * Get footnote data.
	 *
	 * @since    1.0.0
	 * @return   array|null    Footnote data array or null if no footnote loaded.
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
	 * @return   int|null    Post ID or null if no footnote loaded.
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
	 * Search footnotes.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Search arguments.
	 * @return   array           Array of footnote objects.
	 */
	public static function search( $args = array() ) {
		$defaults = array(
			'post_type'      => 'abt_footnote',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_abt_footnote_number',
			'order'          => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );
		$posts = get_posts( $args );

		$footnotes = array();
		foreach ( $posts as $post ) {
			$footnote = new self( $post->ID );
			$footnotes[] = $footnote;
		}

		return $footnotes;
	}

	/**
	 * Get footnotes for a specific post.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Post ID.
	 * @return   array             Array of footnote objects.
	 */
	public static function get_by_post( $post_id ) {
		return self::search( array(
			'post_parent' => $post_id,
		) );
	}

	/**
	 * Get valid footnote styles.
	 *
	 * @since    1.0.0
	 * @return   array    Array of valid styles.
	 */
	public static function get_valid_styles() {
		return self::$valid_styles;
	}
}