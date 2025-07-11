<?php
/**
 * Reference meta box functionality.
 *
 * @link       https://github.com/academic-bloggers-toolkit
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/meta-boxes
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reference meta box functionality.
 *
 * Handles the meta boxes for reference posts including
 * reference details form and metadata management.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/meta-boxes
 * @author     Academic Bloggers Toolkit Team
 */
class ABT_Reference_Metabox {

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
	 * Register meta boxes for reference posts.
	 *
	 * @since    1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'abt_reference_details',
			__( 'Reference Details', 'academic-bloggers-toolkit' ),
			array( $this, 'render_reference_details_metabox' ),
			'abt_reference',
			'normal',
			'high'
		);

		add_meta_box(
			'abt_reference_identifiers',
			__( 'Identifiers & Links', 'academic-bloggers-toolkit' ),
			array( $this, 'render_identifiers_metabox' ),
			'abt_reference',
			'side',
			'default'
		);

		add_meta_box(
			'abt_reference_usage',
			__( 'Citation Usage', 'academic-bloggers-toolkit' ),
			array( $this, 'render_usage_metabox' ),
			'abt_reference',
			'side',
			'low'
		);
	}

	/**
	 * Render the reference details meta box.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Current post object.
	 */
	public function render_reference_details_metabox( $post ) {
		// Add nonce field for security
		wp_nonce_field( 'abt_save_reference', 'abt_reference_nonce' );

		// Get current metadata
		$reference_type = get_post_meta( $post->ID, '_abt_reference_type', true );
		$authors = get_post_meta( $post->ID, '_abt_authors', true );
		$editors = get_post_meta( $post->ID, '_abt_editors', true );
		$publication_year = get_post_meta( $post->ID, '_abt_publication_year', true );
		$journal = get_post_meta( $post->ID, '_abt_journal', true );
		$volume = get_post_meta( $post->ID, '_abt_volume', true );
		$issue = get_post_meta( $post->ID, '_abt_issue', true );
		$pages = get_post_meta( $post->ID, '_abt_pages', true );
		$publisher = get_post_meta( $post->ID, '_abt_publisher', true );
		$publication_place = get_post_meta( $post->ID, '_abt_publication_place', true );

		?>
		<table class="form-table abt-reference-details">
			<tbody>
				<tr>
					<th scope="row">
						<label for="abt_reference_type"><?php _e( 'Reference Type', 'academic-bloggers-toolkit' ); ?> <span class="required">*</span></label>
					</th>
					<td>
						<select name="abt_reference_type" id="abt_reference_type" required>
							<option value=""><?php _e( 'Select type...', 'academic-bloggers-toolkit' ); ?></option>
							<option value="journal_article" <?php selected( $reference_type, 'journal_article' ); ?>><?php _e( 'Journal Article', 'academic-bloggers-toolkit' ); ?></option>
							<option value="book" <?php selected( $reference_type, 'book' ); ?>><?php _e( 'Book', 'academic-bloggers-toolkit' ); ?></option>
							<option value="book_chapter" <?php selected( $reference_type, 'book_chapter' ); ?>><?php _e( 'Book Chapter', 'academic-bloggers-toolkit' ); ?></option>
							<option value="conference_paper" <?php selected( $reference_type, 'conference_paper' ); ?>><?php _e( 'Conference Paper', 'academic-bloggers-toolkit' ); ?></option>
							<option value="thesis" <?php selected( $reference_type, 'thesis' ); ?>><?php _e( 'Thesis/Dissertation', 'academic-bloggers-toolkit' ); ?></option>
							<option value="website" <?php selected( $reference_type, 'website' ); ?>><?php _e( 'Website', 'academic-bloggers-toolkit' ); ?></option>
							<option value="report" <?php selected( $reference_type, 'report' ); ?>><?php _e( 'Report', 'academic-bloggers-toolkit' ); ?></option>
							<option value="newspaper_article" <?php selected( $reference_type, 'newspaper_article' ); ?>><?php _e( 'Newspaper Article', 'academic-bloggers-toolkit' ); ?></option>
							<option value="magazine_article" <?php selected( $reference_type, 'magazine_article' ); ?>><?php _e( 'Magazine Article', 'academic-bloggers-toolkit' ); ?></option>
							<option value="other" <?php selected( $reference_type, 'other' ); ?>><?php _e( 'Other', 'academic-bloggers-toolkit' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="abt_authors"><?php _e( 'Authors', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<textarea name="abt_authors" id="abt_authors" rows="3" class="large-text"><?php echo esc_textarea( $authors ); ?></textarea>
						<p class="description"><?php _e( 'Enter one author per line, or separate with semicolons. Format: Last, First M.', 'academic-bloggers-toolkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="abt_editors"><?php _e( 'Editors', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<textarea name="abt_editors" id="abt_editors" rows="2" class="large-text"><?php echo esc_textarea( $editors ); ?></textarea>
						<p class="description"><?php _e( 'For edited books or special journal issues.', 'academic-bloggers-toolkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="abt_publication_year"><?php _e( 'Publication Year', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="number" name="abt_publication_year" id="abt_publication_year" value="<?php echo esc_attr( $publication_year ); ?>" min="1000" max="<?php echo date( 'Y' ) + 5; ?>" class="small-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="abt_journal"><?php _e( 'Journal/Publication', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="text" name="abt_journal" id="abt_journal" value="<?php echo esc_attr( $journal ); ?>" class="regular-text" />
						<p class="description"><?php _e( 'Journal name, book title, conference proceedings, etc.', 'academic-bloggers-toolkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="abt_volume"><?php _e( 'Volume', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="text" name="abt_volume" id="abt_volume" value="<?php echo esc_attr( $volume ); ?>" class="small-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="abt_issue"><?php _e( 'Issue/Number', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="text" name="abt_issue" id="abt_issue" value="<?php echo esc_attr( $issue ); ?>" class="small-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="abt_pages"><?php _e( 'Pages', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="text" name="abt_pages" id="abt_pages" value="<?php echo esc_attr( $pages ); ?>" class="regular-text" />
						<p class="description"><?php _e( 'Examples: 123-145, 15-20, 45', 'academic-bloggers-toolkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="abt_publisher"><?php _e( 'Publisher', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="text" name="abt_publisher" id="abt_publisher" value="<?php echo esc_attr( $publisher ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="abt_publication_place"><?php _e( 'Publication Place', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="text" name="abt_publication_place" id="abt_publication_place" value="<?php echo esc_attr( $publication_place ); ?>" class="regular-text" />
						<p class="description"><?php _e( 'City, State/Country', 'academic-bloggers-toolkit' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<style>
		.abt-reference-details .required {
			color: #d63638;
		}
		</style>
		<?php
	}

	/**
	 * Render the identifiers meta box.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Current post object.
	 */
	public function render_identifiers_metabox( $post ) {
		$doi = get_post_meta( $post->ID, '_abt_doi', true );
		$pmid = get_post_meta( $post->ID, '_abt_pmid', true );
		$isbn = get_post_meta( $post->ID, '_abt_isbn', true );
		$url = get_post_meta( $post->ID, '_abt_url', true );
		$access_date = get_post_meta( $post->ID, '_abt_access_date', true );

		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="abt_doi"><?php _e( 'DOI', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="text" name="abt_doi" id="abt_doi" value="<?php echo esc_attr( $doi ); ?>" class="regular-text" placeholder="10.1000/182" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="abt_pmid"><?php _e( 'PMID', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="text" name="abt_pmid" id="abt_pmid" value="<?php echo esc_attr( $pmid ); ?>" class="regular-text" placeholder="12345678" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="abt_isbn"><?php _e( 'ISBN', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="text" name="abt_isbn" id="abt_isbn" value="<?php echo esc_attr( $isbn ); ?>" class="regular-text" placeholder="978-0-123456-78-9" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="abt_url"><?php _e( 'URL', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="url" name="abt_url" id="abt_url" value="<?php echo esc_attr( $url ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="abt_access_date"><?php _e( 'Access Date', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="date" name="abt_access_date" id="abt_access_date" value="<?php echo esc_attr( $access_date ); ?>" class="regular-text" />
						<p class="description"><?php _e( 'For online sources', 'academic-bloggers-toolkit' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render the usage meta box.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Current post object.
	 */
	public function render_usage_metabox( $post ) {
		// Get citation count (placeholder for now)
		$citation_count = 0; // This will be implemented later
		$created_date = get_the_date( 'F j, Y', $post );
		$modified_date = get_the_modified_date( 'F j, Y', $post );

		?>
		<div class="abt-reference-usage">
			<p><strong><?php _e( 'Citation Count:', 'academic-bloggers-toolkit' ); ?></strong> <?php echo $citation_count; ?></p>
			<p><strong><?php _e( 'Created:', 'academic-bloggers-toolkit' ); ?></strong> <?php echo $created_date; ?></p>
			<p><strong><?php _e( 'Last Modified:', 'academic-bloggers-toolkit' ); ?></strong> <?php echo $modified_date; ?></p>
			
			<?php if ( $citation_count > 0 ) : ?>
				<p>
					<a href="#" class="button button-small"><?php _e( 'View Citations', 'academic-bloggers-toolkit' ); ?></a>
				</p>
			<?php endif; ?>
		</div>

		<style>
		.abt-reference-usage p {
			margin-bottom: 8px;
		}
		</style>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Post ID.
	 */
	public function save_meta_boxes( $post_id ) {
		// Verify nonce
		if ( ! isset( $_POST['abt_reference_nonce'] ) || ! wp_verify_nonce( $_POST['abt_reference_nonce'], 'abt_save_reference' ) ) {
			return;
		}

		// Check if user has permission to edit
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check for autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Save reference metadata
		$meta_fields = array(
			'abt_reference_type',
			'abt_authors',
			'abt_editors',
			'abt_publication_year',
			'abt_journal',
			'abt_volume',
			'abt_issue',
			'abt_pages',
			'abt_publisher',
			'abt_publication_place',
			'abt_doi',
			'abt_pmid',
			'abt_isbn',
			'abt_url',
			'abt_access_date'
		);

		foreach ( $meta_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = $_POST[ $field ];
				
				// Sanitize based on field type
				if ( $field === 'abt_publication_year' ) {
					$value = intval( $value );
				} elseif ( $field === 'abt_url' ) {
					$value = esc_url_raw( $value );
				} elseif ( in_array( $field, array( 'abt_authors', 'abt_editors' ) ) ) {
					$value = sanitize_textarea_field( $value );
				} else {
					$value = sanitize_text_field( $value );
				}
				
				update_post_meta( $post_id, '_' . $field, $value );
			}
		}
	}
}