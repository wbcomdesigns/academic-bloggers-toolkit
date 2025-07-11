<?php
/**
 * Academic blog post meta box functionality.
 *
 * @link       https://github.com/wbcomdesigns
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
 * Academic blog post meta box functionality.
 *
 * Handles the meta boxes for academic blog posts including
 * citation management, academic settings, and bibliography preview.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/meta-boxes
 * @author     Academic Bloggers Toolkit Team
 */
class ABT_Blog_Metabox {

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
	 * Register meta boxes for academic blog posts.
	 *
	 * @since    1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'abt_citations_manager',
			__( 'Citations & References Manager', 'academic-bloggers-toolkit' ),
			array( $this, 'render_citations_metabox' ),
			'abt_blog',
			'normal',
			'high'
		);

		add_meta_box(
			'abt_academic_settings',
			__( 'Academic Settings', 'academic-bloggers-toolkit' ),
			array( $this, 'render_settings_metabox' ),
			'abt_blog',
			'side',
			'default'
		);

		add_meta_box(
			'abt_bibliography_preview',
			__( 'Bibliography Preview', 'academic-bloggers-toolkit' ),
			array( $this, 'render_bibliography_metabox' ),
			'abt_blog',
			'normal',
			'low'
		);

		add_meta_box(
			'abt_footnotes_manager',
			__( 'Footnotes Manager', 'academic-bloggers-toolkit' ),
			array( $this, 'render_footnotes_metabox' ),
			'abt_blog',
			'normal',
			'default'
		);
	}

	/**
	 * Render the citations manager meta box.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Current post object.
	 */
	public function render_citations_metabox( $post ) {
		// Add nonce field for security
		wp_nonce_field( 'abt_save_citations', 'abt_citations_nonce' );

		// Get existing citations
		$citations = ABT_Citation::get_by_post( $post->ID );
		$references = ABT_Reference::get_all();

		?>
		<div class="abt-citations-manager">
			<div class="abt-citations-toolbar">
				<button type="button" class="button button-primary" id="abt-add-citation">
					<?php _e( 'Add Citation', 'academic-bloggers-toolkit' ); ?>
				</button>
				<button type="button" class="button" id="abt-import-citation">
					<?php _e( 'Import from DOI/URL', 'academic-bloggers-toolkit' ); ?>
				</button>
				<button type="button" class="button" id="abt-manage-references">
					<?php _e( 'Manage References', 'academic-bloggers-toolkit' ); ?>
				</button>
			</div>

			<div class="abt-citations-list">
				<?php if ( empty( $citations ) ) : ?>
					<div class="abt-no-citations">
						<p><?php _e( 'No citations added yet. Click "Add Citation" to get started.', 'academic-bloggers-toolkit' ); ?></p>
					</div>
				<?php else : ?>
					<div class="abt-citations-table-wrapper">
						<table class="abt-citations-table">
							<thead>
								<tr>
									<th><?php _e( 'Reference', 'academic-bloggers-toolkit' ); ?></th>
									<th><?php _e( 'Position', 'academic-bloggers-toolkit' ); ?></th>
									<th><?php _e( 'Format', 'academic-bloggers-toolkit' ); ?></th>
									<th><?php _e( 'Actions', 'academic-bloggers-toolkit' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $citations as $citation ) : ?>
									<?php $this->render_citation_row( $citation ); ?>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>

			<div class="abt-citation-modal" id="abt-citation-modal" style="display: none;">
				<div class="abt-modal-content">
					<div class="abt-modal-header">
						<h3><?php _e( 'Add/Edit Citation', 'academic-bloggers-toolkit' ); ?></h3>
						<span class="abt-modal-close">&times;</span>
					</div>
					<div class="abt-modal-body">
						<?php $this->render_citation_form(); ?>
					</div>
				</div>
			</div>
		</div>

		<style>
		.abt-citations-manager {
			padding: 10px 0;
		}

		.abt-citations-toolbar {
			margin-bottom: 15px;
			padding-bottom: 10px;
			border-bottom: 1px solid #ddd;
		}

		.abt-citations-toolbar .button {
			margin-right: 10px;
		}

		.abt-citations-table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 10px;
		}

		.abt-citations-table th,
		.abt-citations-table td {
			padding: 8px 12px;
			text-align: left;
			border-bottom: 1px solid #ddd;
		}

		.abt-citations-table th {
			background-color: #f5f5f5;
			font-weight: 600;
		}

		.abt-no-citations {
			text-align: center;
			padding: 40px 20px;
			background: #f9f9f9;
			border: 2px dashed #ddd;
			border-radius: 4px;
		}

		.abt-citation-modal {
			position: fixed;
			z-index: 100000;
			left: 0;
			top: 0;
			width: 100%;
			height: 100%;
			background-color: rgba(0,0,0,0.5);
		}

		.abt-modal-content {
			background-color: #fff;
			margin: 5% auto;
			padding: 0;
			border: 1px solid #888;
			width: 80%;
			max-width: 600px;
			border-radius: 4px;
		}

		.abt-modal-header {
			padding: 15px 20px;
			border-bottom: 1px solid #ddd;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.abt-modal-header h3 {
			margin: 0;
		}

		.abt-modal-close {
			color: #aaa;
			font-size: 24px;
			font-weight: bold;
			cursor: pointer;
		}

		.abt-modal-close:hover {
			color: #000;
		}

		.abt-modal-body {
			padding: 20px;
		}
		</style>
		<?php
	}

	/**
	 * Render a single citation row.
	 *
	 * @since    1.0.0
	 * @param    ABT_Citation    $citation    Citation object.
	 */
	private function render_citation_row( $citation ) {
		$reference = ABT_Reference::get( $citation->reference_id );
		?>
		<tr data-citation-id="<?php echo esc_attr( $citation->id ); ?>">
			<td>
				<strong><?php echo esc_html( $reference ? $reference->get_formatted_title() : 'Unknown Reference' ); ?></strong>
				<?php if ( $reference ) : ?>
					<div class="row-actions">
						<span class="edit">
							<a href="#" class="abt-edit-citation"><?php _e( 'Edit', 'academic-bloggers-toolkit' ); ?></a> |
						</span>
						<span class="view">
							<a href="<?php echo get_edit_post_link( $reference->id ); ?>" target="_blank"><?php _e( 'View Reference', 'academic-bloggers-toolkit' ); ?></a> |
						</span>
						<span class="delete">
							<a href="#" class="abt-delete-citation" style="color: #a00;"><?php _e( 'Remove', 'academic-bloggers-toolkit' ); ?></a>
						</span>
					</div>
				<?php endif; ?>
			</td>
			<td>
				<span class="abt-citation-position"><?php echo esc_html( $citation->position ); ?></span>
			</td>
			<td>
				<span class="abt-citation-format">
					<?php 
					$format = '';
					if ( $citation->prefix ) $format .= $citation->prefix . ' ';
					$format .= 'Citation';
					if ( $citation->suffix ) $format .= ' ' . $citation->suffix;
					echo esc_html( $format );
					?>
				</span>
			</td>
			<td>
				<button type="button" class="button-link abt-edit-citation">
					<?php _e( 'Edit', 'academic-bloggers-toolkit' ); ?>
				</button>
				<button type="button" class="button-link abt-delete-citation" style="color: #a00;">
					<?php _e( 'Remove', 'academic-bloggers-toolkit' ); ?>
				</button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render the citation form in modal.
	 *
	 * @since    1.0.0
	 */
	private function render_citation_form() {
		$references = ABT_Reference::get_all();
		?>
		<form id="abt-citation-form">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="abt_citation_reference"><?php _e( 'Reference', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<select id="abt_citation_reference" name="reference_id" required>
								<option value=""><?php _e( 'Select a reference...', 'academic-bloggers-toolkit' ); ?></option>
								<?php foreach ( $references as $reference ) : ?>
									<option value="<?php echo esc_attr( $reference->id ); ?>">
										<?php echo esc_html( $reference->get_formatted_title() ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								<?php 
								printf( 
									__( 'Don\'t see your reference? <a href="%s" target="_blank">Add a new reference</a> first.', 'academic-bloggers-toolkit' ),
									admin_url( 'post-new.php?post_type=abt_reference' )
								);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="abt_citation_position"><?php _e( 'Position in Text', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<input type="number" id="abt_citation_position" name="position" min="1" step="1" class="small-text" />
							<p class="description"><?php _e( 'Order in which this citation appears in the post.', 'academic-bloggers-toolkit' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="abt_citation_prefix"><?php _e( 'Prefix', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<input type="text" id="abt_citation_prefix" name="prefix" class="regular-text" />
							<p class="description"><?php _e( 'Text to appear before the citation (e.g., "see", "cf.").', 'academic-bloggers-toolkit' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="abt_citation_suffix"><?php _e( 'Suffix', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<input type="text" id="abt_citation_suffix" name="suffix" class="regular-text" />
							<p class="description"><?php _e( 'Text to appear after the citation (e.g., "p. 15", "chap. 3").', 'academic-bloggers-toolkit' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="abt_citation_suppress_author"><?php _e( 'Suppress Author', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="abt_citation_suppress_author" name="suppress_author" value="1" />
							<label for="abt_citation_suppress_author"><?php _e( 'Show only year in citation (author mentioned in text)', 'academic-bloggers-toolkit' ); ?></label>
						</td>
					</tr>
				</tbody>
			</table>

			<div class="abt-form-actions">
				<button type="submit" class="button button-primary"><?php _e( 'Save Citation', 'academic-bloggers-toolkit' ); ?></button>
				<button type="button" class="button abt-modal-close"><?php _e( 'Cancel', 'academic-bloggers-toolkit' ); ?></button>
			</div>
		</form>
		<?php
	}

	/**
	 * Render the academic settings meta box.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Current post object.
	 */
	public function render_settings_metabox( $post ) {
		// Add nonce field for security
		wp_nonce_field( 'abt_save_settings', 'abt_settings_nonce' );

		// Get current settings
		$citation_style = get_post_meta( $post->ID, '_abt_citation_style', true );
		$enable_footnotes = get_post_meta( $post->ID, '_abt_enable_footnotes', true );
		$auto_bibliography = get_post_meta( $post->ID, '_abt_auto_bibliography', true );
		$footnote_style = get_post_meta( $post->ID, '_abt_footnote_style', true );

		// Set defaults
		if ( empty( $citation_style ) ) {
			$citation_style = 'apa';
		}
		if ( empty( $footnote_style ) ) {
			$footnote_style = 'numeric';
		}

		?>
		<div class="abt-academic-settings">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="abt_citation_style"><?php _e( 'Citation Style', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<select id="abt_citation_style" name="abt_citation_style">
								<option value="apa" <?php selected( $citation_style, 'apa' ); ?>><?php _e( 'APA', 'academic-bloggers-toolkit' ); ?></option>
								<option value="mla" <?php selected( $citation_style, 'mla' ); ?>><?php _e( 'MLA', 'academic-bloggers-toolkit' ); ?></option>
								<option value="chicago" <?php selected( $citation_style, 'chicago' ); ?>><?php _e( 'Chicago', 'academic-bloggers-toolkit' ); ?></option>
								<option value="harvard" <?php selected( $citation_style, 'harvard' ); ?>><?php _e( 'Harvard', 'academic-bloggers-toolkit' ); ?></option>
								<option value="ieee" <?php selected( $citation_style, 'ieee' ); ?>><?php _e( 'IEEE', 'academic-bloggers-toolkit' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Features', 'academic-bloggers-toolkit' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php _e( 'Academic Features', 'academic-bloggers-toolkit' ); ?></legend>
								<label for="abt_enable_footnotes">
									<input type="checkbox" id="abt_enable_footnotes" name="abt_enable_footnotes" value="1" <?php checked( $enable_footnotes ); ?> />
									<?php _e( 'Enable footnotes', 'academic-bloggers-toolkit' ); ?>
								</label>
								<br />
								<label for="abt_auto_bibliography">
									<input type="checkbox" id="abt_auto_bibliography" name="abt_auto_bibliography" value="1" <?php checked( $auto_bibliography ); ?> />
									<?php _e( 'Auto-generate bibliography', 'academic-bloggers-toolkit' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="abt_footnote_style"><?php _e( 'Footnote Style', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<select id="abt_footnote_style" name="abt_footnote_style">
								<option value="numeric" <?php selected( $footnote_style, 'numeric' ); ?>><?php _e( 'Numeric (1, 2, 3)', 'academic-bloggers-toolkit' ); ?></option>
								<option value="roman" <?php selected( $footnote_style, 'roman' ); ?>><?php _e( 'Roman (i, ii, iii)', 'academic-bloggers-toolkit' ); ?></option>
								<option value="alpha" <?php selected( $footnote_style, 'alpha' ); ?>><?php _e( 'Alphabetic (a, b, c)', 'academic-bloggers-toolkit' ); ?></option>
								<option value="symbols" <?php selected( $footnote_style, 'symbols' ); ?>><?php _e( 'Symbols (*, †, ‡)', 'academic-bloggers-toolkit' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="abt_subject_areas"><?php _e( 'Subject Areas', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<?php
							$terms = get_terms( array(
								'taxonomy' => 'abt_subject',
								'hide_empty' => false,
							) );

							if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
								$selected_terms = wp_get_post_terms( $post->ID, 'abt_subject', array( 'fields' => 'ids' ) );
								echo '<div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 8px;">';
								foreach ( $terms as $term ) {
									$checked = in_array( $term->term_id, $selected_terms ) ? 'checked' : '';
									echo '<label style="display: block; margin-bottom: 5px;">';
									echo '<input type="checkbox" name="abt_subject_areas[]" value="' . esc_attr( $term->term_id ) . '" ' . $checked . ' /> ';
									echo esc_html( $term->name );
									echo '</label>';
								}
								echo '</div>';
							} else {
								echo '<p>' . __( 'No subject areas available.', 'academic-bloggers-toolkit' ) . '</p>';
								echo '<p><a href="' . admin_url( 'edit-tags.php?taxonomy=abt_subject' ) . '">' . __( 'Create subject areas', 'academic-bloggers-toolkit' ) . '</a></p>';
							}
							?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render the bibliography preview meta box.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Current post object.
	 */
	public function render_bibliography_metabox( $post ) {
		$citations = ABT_Citation::get_by_post( $post->ID );
		$citation_style = get_post_meta( $post->ID, '_abt_citation_style', true ) ?: 'apa';

		?>
		<div class="abt-bibliography-preview">
			<?php if ( empty( $citations ) ) : ?>
				<div class="abt-no-bibliography">
					<p><?php _e( 'No citations added yet. Add citations to see the bibliography preview.', 'academic-bloggers-toolkit' ); ?></p>
				</div>
			<?php else : ?>
				<div class="abt-bibliography-header">
					<h4><?php _e( 'Bibliography Preview', 'academic-bloggers-toolkit' ); ?> 
						<span class="abt-style-indicator">(<?php echo esc_html( strtoupper( $citation_style ) ); ?>)</span>
					</h4>
					<button type="button" class="button button-small" id="abt-refresh-bibliography">
						<?php _e( 'Refresh', 'academic-bloggers-toolkit' ); ?>
					</button>
				</div>
				
				<div class="abt-bibliography-content">
					<ol class="abt-bibliography-list">
						<?php foreach ( $citations as $citation ) : ?>
							<?php
							$reference = ABT_Reference::get( $citation->reference_id );
							if ( $reference ) :
							?>
								<li class="abt-bibliography-item">
									<?php echo $this->format_bibliography_entry( $reference, $citation_style ); ?>
								</li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ol>
				</div>

				<div class="abt-bibliography-actions">
					<button type="button" class="button" id="abt-copy-bibliography">
						<?php _e( 'Copy Bibliography', 'academic-bloggers-toolkit' ); ?>
					</button>
					<button type="button" class="button" id="abt-export-bibliography">
						<?php _e( 'Export as RTF', 'academic-bloggers-toolkit' ); ?>
					</button>
				</div>
			<?php endif; ?>
		</div>

		<style>
		.abt-bibliography-preview {
			padding: 10px 0;
		}

		.abt-bibliography-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 15px;
			padding-bottom: 10px;
			border-bottom: 1px solid #ddd;
		}

		.abt-bibliography-header h4 {
			margin: 0;
		}

		.abt-style-indicator {
			font-size: 12px;
			color: #666;
			font-weight: normal;
		}

		.abt-bibliography-list {
			margin: 0;
			padding-left: 20px;
		}

		.abt-bibliography-item {
			margin-bottom: 12px;
			line-height: 1.5;
		}

		.abt-no-bibliography {
			text-align: center;
			padding: 20px;
			background: #f9f9f9;
			border: 2px dashed #ddd;
			border-radius: 4px;
		}

		.abt-bibliography-actions {
			margin-top: 15px;
			padding-top: 10px;
			border-top: 1px solid #ddd;
		}

		.abt-bibliography-actions .button {
			margin-right: 10px;
		}
		</style>
		<?php
	}

	/**
	 * Render the footnotes manager meta box.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Current post object.
	 */
	public function render_footnotes_metabox( $post ) {
		$footnotes = ABT_Footnote::get_by_post( $post->ID );
		$footnote_style = get_post_meta( $post->ID, '_abt_footnote_style', true ) ?: 'numeric';

		?>
		<div class="abt-footnotes-manager">
			<div class="abt-footnotes-toolbar">
				<button type="button" class="button button-primary" id="abt-add-footnote">
					<?php _e( 'Add Footnote', 'academic-bloggers-toolkit' ); ?>
				</button>
				<span class="abt-footnote-style-info">
					<?php 
					printf( 
						__( 'Style: %s', 'academic-bloggers-toolkit' ), 
						esc_html( ucfirst( $footnote_style ) )
					);
					?>
				</span>
			</div>

			<div class="abt-footnotes-list">
				<?php if ( empty( $footnotes ) ) : ?>
					<div class="abt-no-footnotes">
						<p><?php _e( 'No footnotes added yet. Click "Add Footnote" to get started.', 'academic-bloggers-toolkit' ); ?></p>
					</div>
				<?php else : ?>
					<div class="abt-footnotes-table-wrapper">
						<table class="abt-footnotes-table">
							<thead>
								<tr>
									<th><?php _e( '#', 'academic-bloggers-toolkit' ); ?></th>
									<th><?php _e( 'Content', 'academic-bloggers-toolkit' ); ?></th>
									<th><?php _e( 'Position', 'academic-bloggers-toolkit' ); ?></th>
									<th><?php _e( 'Actions', 'academic-bloggers-toolkit' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $footnotes as $index => $footnote ) : ?>
									<tr data-footnote-id="<?php echo esc_attr( $footnote->id ); ?>">
										<td>
											<span class="abt-footnote-number">
												<?php echo $this->format_footnote_number( $index + 1, $footnote_style ); ?>
											</span>
										</td>
										<td>
											<div class="abt-footnote-content">
												<?php echo wp_trim_words( $footnote->content, 15, '...' ); ?>
											</div>
										</td>
										<td>
											<span class="abt-footnote-position"><?php echo esc_html( $footnote->position ); ?></span>
										</td>
										<td>
											<button type="button" class="button-link abt-edit-footnote">
												<?php _e( 'Edit', 'academic-bloggers-toolkit' ); ?>
											</button>
											<button type="button" class="button-link abt-delete-footnote" style="color: #a00;">
												<?php _e( 'Remove', 'academic-bloggers-toolkit' ); ?>
											</button>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<style>
		.abt-footnotes-toolbar {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 15px;
			padding-bottom: 10px;
			border-bottom: 1px solid #ddd;
		}

		.abt-footnote-style-info {
			font-size: 12px;
			color: #666;
		}

		.abt-footnotes-table {
			width: 100%;
			border-collapse: collapse;
		}

		.abt-footnotes-table th,
		.abt-footnotes-table td {
			padding: 8px 12px;
			text-align: left;
			border-bottom: 1px solid #ddd;
		}

		.abt-footnotes-table th {
			background-color: #f5f5f5;
			font-weight: 600;
		}

		.abt-footnote-number {
			font-weight: bold;
			color: #0073aa;
		}

		.abt-footnote-content {
			max-width: 300px;
		}

		.abt-no-footnotes {
			text-align: center;
			padding: 40px 20px;
			background: #f9f9f9;
			border: 2px dashed #ddd;
			border-radius: 4px;
		}
		</style>
		<?php
	}

	/**
	 * Format a bibliography entry according to the specified style.
	 *
	 * @since    1.0.0
	 * @param    ABT_Reference    $reference    Reference object.
	 * @param    string          $style        Citation style.
	 * @return   string                        Formatted bibliography entry.
	 */
	private function format_bibliography_entry( $reference, $style ) {
		// This is a basic implementation - will be enhanced in Phase 3
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

	/**
	 * Format footnote number according to style.
	 *
	 * @since    1.0.0
	 * @param    int       $number    Footnote number.
	 * @param    string    $style     Footnote style.
	 * @return   string               Formatted footnote number.
	 */
	private function format_footnote_number( $number, $style ) {
		switch ( $style ) {
			case 'roman':
				$roman_numerals = array( '', 'i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x' );
				return isset( $roman_numerals[$number] ) ? $roman_numerals[$number] : $number;

			case 'alpha':
				return chr( 96 + $number ); // a, b, c, etc.

			case 'symbols':
				$symbols = array( '', '*', '†', '‡', '§', '‖', '¶' );
				return isset( $symbols[$number] ) ? $symbols[$number] : $number;

			case 'numeric':
			default:
				return $number;
		}
	}

	/**
	 * Save meta box data.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Post ID.
	 */
	public function save_meta_boxes( $post_id ) {
		// Verify nonces
		if ( isset( $_POST['abt_citations_nonce'] ) && wp_verify_nonce( $_POST['abt_citations_nonce'], 'abt_save_citations' ) ) {
			$this->save_citations_data( $post_id );
		}

		if ( isset( $_POST['abt_settings_nonce'] ) && wp_verify_nonce( $_POST['abt_settings_nonce'], 'abt_save_settings' ) ) {
			$this->save_settings_data( $post_id );
		}
	}

	/**
	 * Save citations data.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Post ID.
	 */
	private function save_citations_data( $post_id ) {
		// Citations will be saved via AJAX in the JavaScript implementation
		// This method is prepared for future enhancement
	}

	/**
	 * Save settings data.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Post ID.
	 */
	private function save_settings_data( $post_id ) {
		// Save citation style
		if ( isset( $_POST['abt_citation_style'] ) ) {
			update_post_meta( $post_id, '_abt_citation_style', sanitize_text_field( $_POST['abt_citation_style'] ) );
		}

		// Save footnote style
		if ( isset( $_POST['abt_footnote_style'] ) ) {
			update_post_meta( $post_id, '_abt_footnote_style', sanitize_text_field( $_POST['abt_footnote_style'] ) );
		}

		// Save feature toggles
		if ( isset( $_POST['abt_enable_footnotes'] ) ) {
			update_post_meta( $post_id, '_abt_enable_footnotes', 1 );
		} else {
			delete_post_meta( $post_id, '_abt_enable_footnotes' );
		}

		if ( isset( $_POST['abt_auto_bibliography'] ) ) {
			update_post_meta( $post_id, '_abt_auto_bibliography', 1 );
		} else {
			delete_post_meta( $post_id, '_abt_auto_bibliography' );
		}

		// Save subject areas
		if ( isset( $_POST['abt_subject_areas'] ) && is_array( $_POST['abt_subject_areas'] ) ) {
			$subject_areas = array_map( 'intval', $_POST['abt_subject_areas'] );
			wp_set_post_terms( $post_id, $subject_areas, 'abt_subject' );
		} else {
			wp_set_post_terms( $post_id, array(), 'abt_subject' );
		}
	}

	/**
	 * Enqueue meta box scripts and styles.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_metabox_assets() {
		global $post_type;

		if ( 'abt_blog' === $post_type ) {
			wp_enqueue_script(
				'abt-metabox',
				ABT_PLUGIN_URL . 'admin/js/dist/meta-box.js',
				array( 'jquery' ),
				$this->version,
				true
			);

			wp_enqueue_style(
				'abt-metabox',
				ABT_PLUGIN_URL . 'admin/css/dist/meta-box.css',
				array(),
				$this->version
			);

			// Localize script for AJAX
			wp_localize_script(
				'abt-metabox',
				'abt_metabox_ajax',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'abt_metabox_nonce' ),
					'post_id'  => get_the_ID(),
					'strings'  => array(
						'confirm_delete' => __( 'Are you sure you want to remove this citation?', 'academic-bloggers-toolkit' ),
						'confirm_delete_footnote' => __( 'Are you sure you want to remove this footnote?', 'academic-bloggers-toolkit' ),
						'error_saving' => __( 'Error saving data. Please try again.', 'academic-bloggers-toolkit' ),
						'success_saved' => __( 'Data saved successfully.', 'academic-bloggers-toolkit' ),
					),
				)
			);
		}
	}
}