<?php
/**
 * Provide a admin area view for the blog citations meta box.
 *
 * This file is used to markup the admin-facing aspects of the citations manager.
 *
 * @link       https://github.com/academic-bloggers-toolkit
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/partials/meta-boxes
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current post data
global $post;
$post_id = $post->ID;

// Add nonce field for security
wp_nonce_field( 'abt_save_citations', 'abt_citations_nonce' );

// Get existing citations
$citations = ABT_Citation::get_by_post( $post_id );
$references = ABT_Reference::get_all();

?>

<div class="abt-citations-manager">
	<div class="abt-citations-toolbar abt-toolbar">
		<div class="abt-toolbar-left">
			<button type="button" class="button button-primary" id="abt-add-citation">
				<span class="dashicons dashicons-plus"></span>
				<?php _e( 'Add Citation', 'academic-bloggers-toolkit' ); ?>
			</button>
			<button type="button" class="button" id="abt-import-citation">
				<span class="dashicons dashicons-download"></span>
				<?php _e( 'Auto-Cite', 'academic-bloggers-toolkit' ); ?>
			</button>
		</div>
		<div class="abt-toolbar-right">
			<a href="<?php echo admin_url( 'post-new.php?post_type=abt_reference' ); ?>" 
			   class="button" 
			   target="_blank">
				<span class="dashicons dashicons-book-alt"></span>
				<?php _e( 'Manage References', 'academic-bloggers-toolkit' ); ?>
			</a>
		</div>
	</div>

	<div class="abt-citations-list">
		<?php if ( empty( $citations ) ) : ?>
			<div class="abt-no-citations">
				<p>
					<span class="dashicons dashicons-book-alt" style="font-size: 48px; color: #ddd;"></span>
				</p>
				<p><?php _e( 'No citations added yet.', 'academic-bloggers-toolkit' ); ?></p>
				<p class="description">
					<?php _e( 'Click "Add Citation" to start building your reference list, or use "Auto-Cite" to import references from DOI, URL, or other sources.', 'academic-bloggers-toolkit' ); ?>
				</p>
			</div>
		<?php else : ?>
			<div class="abt-citations-table-wrapper">
				<table class="abt-citations-table">
					<thead>
						<tr>
							<th class="abt-column-handle">
								<span class="abt-sr-only"><?php _e( 'Sort', 'academic-bloggers-toolkit' ); ?></span>
							</th>
							<th class="abt-column-reference"><?php _e( 'Reference', 'academic-bloggers-toolkit' ); ?></th>
							<th class="abt-column-position"><?php _e( 'Position', 'academic-bloggers-toolkit' ); ?></th>
							<th class="abt-column-format"><?php _e( 'Format', 'academic-bloggers-toolkit' ); ?></th>
							<th class="abt-column-actions"><?php _e( 'Actions', 'academic-bloggers-toolkit' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $citations as $index => $citation ) : ?>
							<?php
							$reference = ABT_Reference::get( $citation->reference_id );
							if ( ! $reference ) continue;
							?>
							<tr data-citation-id="<?php echo esc_attr( $citation->id ); ?>">
								<td class="abt-column-handle">
									<span class="dashicons dashicons-menu abt-citation-handle" 
										  title="<?php _e( 'Drag to reorder', 'academic-bloggers-toolkit' ); ?>"></span>
								</td>
								<td class="abt-column-reference">
									<div class="abt-reference-info">
										<strong class="abt-reference-title">
											<?php echo esc_html( $reference->post_title ); ?>
										</strong>
										<?php
										$authors = $reference->get_meta( 'authors' );
										$year = $reference->get_meta( 'year' );
										if ( $authors || $year ) :
										?>
											<div class="abt-reference-meta">
												<?php 
												if ( $authors ) {
													$author_list = explode( ';', $authors );
													if ( count( $author_list ) > 1 ) {
														echo esc_html( trim( $author_list[0] ) ) . ' <em>et al.</em>';
													} else {
														echo esc_html( trim( $authors ) );
													}
												}
												if ( $authors && $year ) echo ' • ';
												if ( $year ) echo esc_html( $year );
												?>
											</div>
										<?php endif; ?>
									</div>
									<div class="row-actions">
										<span class="edit">
											<a href="<?php echo get_edit_post_link( $reference->id ); ?>" 
											   target="_blank"
											   title="<?php _e( 'Edit reference in new tab', 'academic-bloggers-toolkit' ); ?>">
												<?php _e( 'Edit Reference', 'academic-bloggers-toolkit' ); ?>
											</a> |
										</span>
										<span class="view">
											<a href="#" 
											   class="abt-preview-reference" 
											   data-ref-id="<?php echo esc_attr( $reference->id ); ?>"
											   title="<?php _e( 'Preview reference details', 'academic-bloggers-toolkit' ); ?>">
												<?php _e( 'Preview', 'academic-bloggers-toolkit' ); ?>
											</a>
										</span>
									</div>
								</td>
								<td class="abt-column-position">
									<span class="abt-citation-position"><?php echo esc_html( $citation->position ?: $index + 1 ); ?></span>
								</td>
								<td class="abt-column-format">
									<div class="abt-citation-format">
										<?php
										$format_parts = array();
										if ( $citation->prefix ) {
											$format_parts[] = '<span class="abt-prefix">' . esc_html( $citation->prefix ) . '</span>';
										}
										
										if ( $citation->suppress_author ) {
											$format_parts[] = '<span class="abt-citation-year">(' . esc_html( $year ?: 'Year' ) . ')</span>';
										} else {
											$author_short = $authors ? explode( ';', $authors )[0] : 'Author';
											$format_parts[] = '<span class="abt-citation-author">(' . esc_html( trim( $author_short ) ) . ', ' . esc_html( $year ?: 'Year' ) . ')</span>';
										}
										
										if ( $citation->suffix ) {
											$format_parts[] = '<span class="abt-suffix">' . esc_html( $citation->suffix ) . '</span>';
										}
										
										echo implode( ' ', $format_parts );
										?>
									</div>
									<?php if ( $citation->prefix || $citation->suffix || $citation->suppress_author ) : ?>
										<div class="abt-format-details">
											<?php if ( $citation->prefix ) : ?>
												<span class="abt-detail-item">Prefix: <?php echo esc_html( $citation->prefix ); ?></span>
											<?php endif; ?>
											<?php if ( $citation->suffix ) : ?>
												<span class="abt-detail-item">Suffix: <?php echo esc_html( $citation->suffix ); ?></span>
											<?php endif; ?>
											<?php if ( $citation->suppress_author ) : ?>
												<span class="abt-detail-item">Author suppressed</span>
											<?php endif; ?>
										</div>
									<?php endif; ?>
								</td>
								<td class="abt-column-actions">
									<button type="button" 
											class="button-link abt-edit-citation"
											data-citation-id="<?php echo esc_attr( $citation->id ); ?>"
											title="<?php _e( 'Edit citation settings', 'academic-bloggers-toolkit' ); ?>">
										<span class="dashicons dashicons-edit"></span>
										<?php _e( 'Edit', 'academic-bloggers-toolkit' ); ?>
									</button>
									<button type="button" 
											class="button-link abt-delete-citation" 
											data-citation-id="<?php echo esc_attr( $citation->id ); ?>"
											style="color: #a00;"
											title="<?php _e( 'Remove citation', 'academic-bloggers-toolkit' ); ?>">
										<span class="dashicons dashicons-trash"></span>
										<?php _e( 'Remove', 'academic-bloggers-toolkit' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div class="abt-citations-summary">
				<p class="abt-info">
					<?php 
					printf( 
						_n( 
							'%d citation added to this post.', 
							'%d citations added to this post.', 
							count( $citations ), 
							'academic-bloggers-toolkit' 
						), 
						count( $citations ) 
					);
					?>
					<strong><?php _e( 'Tip:', 'academic-bloggers-toolkit' ); ?></strong>
					<?php _e( 'Drag citations to reorder them, or use the position numbers for precise control.', 'academic-bloggers-toolkit' ); ?>
				</p>
			</div>
		<?php endif; ?>
	</div>

	<!-- Citation Modal -->
	<div class="abt-modal-overlay abt-citation-modal" id="abt-citation-modal" style="display: none;">
		<div class="abt-modal-content abt-modal-medium">
			<div class="abt-modal-header">
				<h3 id="abt-citation-modal-title"><?php _e( 'Add Citation', 'academic-bloggers-toolkit' ); ?></h3>
				<button type="button" class="abt-modal-close" aria-label="<?php _e( 'Close modal', 'academic-bloggers-toolkit' ); ?>">
					<span class="dashicons dashicons-no"></span>
				</button>
			</div>
			<div class="abt-modal-body">
				<form id="abt-citation-form">
					<input type="hidden" id="abt_citation_id" name="citation_id" value="" />
					
					<table class="abt-form-table form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="abt_citation_reference_search"><?php _e( 'Reference', 'academic-bloggers-toolkit' ); ?></label>
								</th>
								<td>
									<div class="abt-reference-search">
										<input type="text" 
											   id="abt_citation_reference_search" 
											   class="regular-text abt-search-input"
											   placeholder="<?php _e( 'Search references by title, author, or keyword...', 'academic-bloggers-toolkit' ); ?>" 
											   autocomplete="off" />
										<input type="hidden" id="abt_citation_reference" name="reference_id" value="" />
										<input type="hidden" id="abt_citation_reference_display" name="reference_display" value="" />
										
										<div id="abt-reference-results" class="abt-reference-results" style="display: none;">
											<!-- Search results will be populated here -->
										</div>
									</div>
									<p class="description">
										<?php 
										printf( 
											__( 'Start typing to search your reference library. Don\'t see what you need? <a href="%s" target="_blank">Add a new reference</a>.', 'academic-bloggers-toolkit' ),
											admin_url( 'post-new.php?post_type=abt_reference' )
										);
										?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="abt_citation_position"><?php _e( 'Position', 'academic-bloggers-toolkit' ); ?></label>
								</th>
								<td>
									<input type="number" 
										   id="abt_citation_position" 
										   name="position" 
										   min="1" 
										   step="1" 
										   class="small-text" 
										   value="<?php echo count( $citations ) + 1; ?>" />
									<p class="description">
										<?php _e( 'Order in which this citation appears in the post. Citations will be automatically renumbered.', 'academic-bloggers-toolkit' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="abt_citation_prefix"><?php _e( 'Prefix', 'academic-bloggers-toolkit' ); ?></label>
								</th>
								<td>
									<input type="text" 
										   id="abt_citation_prefix" 
										   name="prefix" 
										   class="regular-text" 
										   placeholder="<?php _e( 'e.g., see, cf., according to', 'academic-bloggers-toolkit' ); ?>" />
									<p class="description">
										<?php _e( 'Text to appear before the citation (optional).', 'academic-bloggers-toolkit' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="abt_citation_suffix"><?php _e( 'Suffix', 'academic-bloggers-toolkit' ); ?></label>
								</th>
								<td>
									<input type="text" 
										   id="abt_citation_suffix" 
										   name="suffix" 
										   class="regular-text" 
										   placeholder="<?php _e( 'e.g., p. 15, pp. 23-25, chap. 3', 'academic-bloggers-toolkit' ); ?>" />
									<p class="description">
										<?php _e( 'Text to appear after the citation, such as page numbers or chapter references (optional).', 'academic-bloggers-toolkit' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="abt_citation_suppress_author"><?php _e( 'Author Display', 'academic-bloggers-toolkit' ); ?></label>
								</th>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><?php _e( 'Author display options', 'academic-bloggers-toolkit' ); ?></legend>
										<label for="abt_citation_suppress_author">
											<input type="checkbox" 
												   id="abt_citation_suppress_author" 
												   name="suppress_author" 
												   value="1" />
											<?php _e( 'Suppress author name (show year only)', 'academic-bloggers-toolkit' ); ?>
										</label>
										<p class="description">
											<?php _e( 'Check this if the author is already mentioned in your text and you only want to show the year.', 'academic-bloggers-toolkit' ); ?>
										</p>
									</fieldset>
								</td>
							</tr>
						</tbody>
					</table>
					
					<div class="abt-citation-preview" id="abt-citation-preview" style="display: none;">
						<h4><?php _e( 'Citation Preview', 'academic-bloggers-toolkit' ); ?></h4>
						<div class="abt-preview-content">
							<div class="abt-preview-inline">
								<strong><?php _e( 'In-text:', 'academic-bloggers-toolkit' ); ?></strong>
								<span id="abt-preview-inline-text"></span>
							</div>
							<div class="abt-preview-bibliography">
								<strong><?php _e( 'Bibliography:', 'academic-bloggers-toolkit' ); ?></strong>
								<span id="abt-preview-bibliography-text"></span>
							</div>
						</div>
					</div>
				</form>
			</div>
			<div class="abt-modal-footer">
				<button type="submit" form="abt-citation-form" class="button button-primary">
					<span class="dashicons dashicons-yes"></span>
					<?php _e( 'Save Citation', 'academic-bloggers-toolkit' ); ?>
				</button>
				<button type="button" class="button abt-modal-close">
					<?php _e( 'Cancel', 'academic-bloggers-toolkit' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Auto-Cite Modal -->
	<div class="abt-modal-overlay abt-autocite-modal" id="abt-autocite-modal" style="display: none;">
		<div class="abt-modal-content abt-modal-medium">
			<div class="abt-modal-header">
				<h3><?php _e( 'Auto-Cite Reference', 'academic-bloggers-toolkit' ); ?></h3>
				<button type="button" class="abt-modal-close" aria-label="<?php _e( 'Close modal', 'academic-bloggers-toolkit' ); ?>">
					<span class="dashicons dashicons-no"></span>
				</button>
			</div>
			<div class="abt-modal-body">
				<div class="abt-autocite-tabs">
					<nav class="abt-tab-nav">
						<button type="button" class="abt-tab-button active" data-tab="doi">
							<span class="dashicons dashicons-admin-links"></span>
							<?php _e( 'DOI', 'academic-bloggers-toolkit' ); ?>
						</button>
						<button type="button" class="abt-tab-button" data-tab="url">
							<span class="dashicons dashicons-admin-site"></span>
							<?php _e( 'URL', 'academic-bloggers-toolkit' ); ?>
						</button>
						<button type="button" class="abt-tab-button" data-tab="pmid">
							<span class="dashicons dashicons-admin-users"></span>
							<?php _e( 'PMID', 'academic-bloggers-toolkit' ); ?>
						</button>
						<button type="button" class="abt-tab-button" data-tab="isbn">
							<span class="dashicons dashicons-book"></span>
							<?php _e( 'ISBN', 'academic-bloggers-toolkit' ); ?>
						</button>
					</nav>

					<div class="abt-tab-content">
						<div class="abt-tab-pane active" id="abt-tab-doi">
							<h4><?php _e( 'Import from DOI', 'academic-bloggers-toolkit' ); ?></h4>
							<p class="description">
								<?php _e( 'Enter a Digital Object Identifier (DOI) to automatically fetch reference information.', 'academic-bloggers-toolkit' ); ?>
							</p>
							<div class="abt-field-group">
								<input type="text" 
									   id="abt-doi-input" 
									   class="regular-text" 
									   placeholder="<?php _e( '10.1000/182', 'academic-bloggers-toolkit' ); ?>" />
								<button type="button" class="button button-primary" id="abt-fetch-doi">
									<span class="dashicons dashicons-download"></span>
									<?php _e( 'Fetch', 'academic-bloggers-toolkit' ); ?>
								</button>
							</div>
							<p class="description">
								<?php _e( 'Example: 10.1038/nature12373', 'academic-bloggers-toolkit' ); ?>
							</p>
						</div>

						<div class="abt-tab-pane" id="abt-tab-url">
							<h4><?php _e( 'Import from URL', 'academic-bloggers-toolkit' ); ?></h4>
							<p class="description">
								<?php _e( 'Enter a webpage URL to automatically extract reference information.', 'academic-bloggers-toolkit' ); ?>
							</p>
							<div class="abt-field-group">
								<input type="url" 
									   id="abt-url-input" 
									   class="regular-text" 
									   placeholder="<?php _e( 'https://example.com/article', 'academic-bloggers-toolkit' ); ?>" />
								<button type="button" class="button button-primary" id="abt-fetch-url">
									<span class="dashicons dashicons-download"></span>
									<?php _e( 'Fetch', 'academic-bloggers-toolkit' ); ?>
								</button>
							</div>
							<p class="description">
								<?php _e( 'Works best with academic journal websites and news articles.', 'academic-bloggers-toolkit' ); ?>
							</p>
						</div>

						<div class="abt-tab-pane" id="abt-tab-pmid">
							<h4><?php _e( 'Import from PubMed ID', 'academic-bloggers-toolkit' ); ?></h4>
							<p class="description">
								<?php _e( 'Enter a PubMed ID (PMID) to fetch biomedical literature information.', 'academic-bloggers-toolkit' ); ?>
							</p>
							<div class="abt-field-group">
								<input type="text" 
									   id="abt-pmid-input" 
									   class="regular-text" 
									   placeholder="<?php _e( '12345678', 'academic-bloggers-toolkit' ); ?>" />
								<button type="button" class="button button-primary" id="abt-fetch-pmid">
									<span class="dashicons dashicons-download"></span>
									<?php _e( 'Fetch', 'academic-bloggers-toolkit' ); ?>
								</button>
							</div>
							<p class="description">
								<?php _e( 'PubMed IDs are numeric identifiers for articles in the PubMed database.', 'academic-bloggers-toolkit' ); ?>
							</p>
						</div>

						<div class="abt-tab-pane" id="abt-tab-isbn">
							<h4><?php _e( 'Import from ISBN', 'academic-bloggers-toolkit' ); ?></h4>
							<p class="description">
								<?php _e( 'Enter an ISBN to automatically fetch book information.', 'academic-bloggers-toolkit' ); ?>
							</p>
							<div class="abt-field-group">
								<input type="text" 
									   id="abt-isbn-input" 
									   class="regular-text" 
									   placeholder="<?php _e( '978-0-123456-78-9', 'academic-bloggers-toolkit' ); ?>" />
								<button type="button" class="button button-primary" id="abt-fetch-isbn">
									<span class="dashicons dashicons-download"></span>
									<?php _e( 'Fetch', 'academic-bloggers-toolkit' ); ?>
								</button>
							</div>
							<p class="description">
								<?php _e( 'ISBN can be 10 or 13 digits, with or without hyphens.', 'academic-bloggers-toolkit' ); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="abt-autocite-results" id="abt-autocite-results" style="display: none;">
					<h4><?php _e( 'Fetched Reference Data', 'academic-bloggers-toolkit' ); ?></h4>
					<div class="abt-results-content">
						<!-- Results will be populated here -->
					</div>
					<div class="abt-results-actions">
						<button type="button" class="button button-primary" id="abt-create-reference">
							<span class="dashicons dashicons-plus"></span>
							<?php _e( 'Create Reference & Add Citation', 'academic-bloggers-toolkit' ); ?>
						</button>
						<button type="button" class="button" id="abt-edit-before-save">
							<span class="dashicons dashicons-edit"></span>
							<?php _e( 'Edit Before Saving', 'academic-bloggers-toolkit' ); ?>
						</button>
					</div>
				</div>
			</div>
			<div class="abt-modal-footer">
				<button type="button" class="button abt-modal-close">
					<?php _e( 'Close', 'academic-bloggers-toolkit' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>

<style>
/* Modal-specific styles */
.abt-citation-modal .abt-reference-search {
	position: relative;
}

.abt-citation-modal .abt-reference-results {
	position: absolute;
	top: 100%;
	left: 0;
	right: 0;
	background: #fff;
	border: 1px solid #ddd;
	border-top: none;
	border-radius: 0 0 3px 3px;
	box-shadow: 0 2px 5px rgba(0,0,0,0.1);
	z-index: 1000;
	max-height: 200px;
	overflow-y: auto;
}

.abt-citation-modal .abt-reference-result {
	padding: 10px;
	border-bottom: 1px solid #eee;
	cursor: pointer;
}

.abt-citation-modal .abt-reference-result:hover {
	background-color: #f5f5f5;
}

.abt-citation-modal .abt-reference-result:last-child {
	border-bottom: none;
}

.abt-citation-modal .abt-ref-title {
	font-weight: 600;
	margin-bottom: 3px;
}

.abt-citation-modal .abt-ref-meta {
	font-size: 13px;
	color: #666;
}

.abt-citation-preview {
	margin-top: 20px;
	padding: 15px;
	background: #f9f9f9;
	border-radius: 3px;
	border: 1px solid #ddd;
}

.abt-citation-preview h4 {
	margin-top: 0;
	margin-bottom: 10px;
}

.abt-preview-content > div {
	margin-bottom: 8px;
}

.abt-preview-content > div:last-child {
	margin-bottom: 0;
}

.abt-preview-content strong {
	display: inline-block;
	min-width: 80px;
	color: #23282d;
}

/* Auto-cite modal styles */
.abt-autocite-tabs {
	margin-bottom: 20px;
}

.abt-tab-nav {
	display: flex;
	border-bottom: 1px solid #ddd;
	margin-bottom: 20px;
}

.abt-tab-button {
	background: none;
	border: none;
	padding: 10px 15px;
	cursor: pointer;
	border-bottom: 2px solid transparent;
	display: flex;
	align-items: center;
	gap: 5px;
	color: #666;
}

.abt-tab-button:hover {
	background-color: #f5f5f5;
	color: #23282d;
}

.abt-tab-button.active {
	color: #0073aa;
	border-bottom-color: #0073aa;
}

.abt-tab-pane {
	display: none;
}

.abt-tab-pane.active {
	display: block;
}

.abt-tab-pane h4 {
	margin-top: 0;
	margin-bottom: 10px;
}

.abt-field-group {
	display: flex;
	gap: 10px;
	margin: 10px 0;
}

.abt-field-group input {
	flex: 1;
}

.abt-autocite-results {
	margin-top: 20px;
	padding: 15px;
	background: #f9f9f9;
	border-radius: 3px;
	border: 1px solid #ddd;
}

.abt-results-content {
	margin-bottom: 15px;
}

.abt-results-actions {
	text-align: right;
}

.abt-results-actions .button {
	margin-left: 10px;
}

/* Citation format details */
.abt-format-details {
	margin-top: 3px;
	font-size: 12px;
	color: #666;
}

.abt-detail-item {
	display: inline-block;
	margin-right: 10px;
	padding: 2px 6px;
	background: #e5e5e5;
	border-radius: 2px;
}

/* Citation format preview styles */
.abt-prefix,
.abt-suffix {
	font-style: italic;
	color: #666;
}

.abt-citation-author,
.abt-citation-year {
	font-weight: 600;
}

/* Responsive adjustments */
@media (max-width: 768px) {
	.abt-citations-table th,
	.abt-citations-table td {
		padding: 8px;
		font-size: 14px;
	}
	
	.abt-column-handle,
	.abt-column-position {
		display: none;
	}
	
	.abt-tab-nav {
		flex-wrap: wrap;
	}
	
	.abt-tab-button {
		flex: 1;
		min-width: 0;
		padding: 8px 10px;
		font-size: 13px;
	}
	
	.abt-field-group {
		flex-direction: column;
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	// Auto-cite tab switching
	$('.abt-tab-button').on('click', function() {
		var tab = $(this).data('tab');
		
		// Update active tab button
		$('.abt-tab-button').removeClass('active');
		$(this).addClass('active');
		
		// Show corresponding tab pane
		$('.abt-tab-pane').removeClass('active');
		$('#abt-tab-' + tab).addClass('active');
	});
	
	// Auto-cite modal trigger
	$('#abt-import-citation').on('click', function(e) {
		e.preventDefault();
		$('#abt-autocite-modal').show().addClass('abt-fade-in');
	});
});
</script>