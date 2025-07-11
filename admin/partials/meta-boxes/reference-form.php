<?php
/**
 * Provide a admin area view for the reference form meta box.
 *
 * This file is used to markup the reference editing form.
 *
 * @link       https://github.com/wbcomdesigns
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
wp_nonce_field( 'abt_save_reference', 'abt_reference_nonce' );

// Get current metadata
$reference_type = get_post_meta( $post_id, '_abt_reference_type', true );
$authors = get_post_meta( $post_id, '_abt_authors', true );
$editors = get_post_meta( $post_id, '_abt_editors', true );
$publication_year = get_post_meta( $post_id, '_abt_publication_year', true );
$journal = get_post_meta( $post_id, '_abt_journal', true );
$volume = get_post_meta( $post_id, '_abt_volume', true );
$issue = get_post_meta( $post_id, '_abt_issue', true );
$pages = get_post_meta( $post_id, '_abt_pages', true );
$publisher = get_post_meta( $post_id, '_abt_publisher', true );
$publication_place = get_post_meta( $post_id, '_abt_publication_place', true );
$edition = get_post_meta( $post_id, '_abt_edition', true );
$chapter_title = get_post_meta( $post_id, '_abt_chapter_title', true );
$abstract = get_post_meta( $post_id, '_abt_abstract', true );
$keywords = get_post_meta( $post_id, '_abt_keywords', true );
$language = get_post_meta( $post_id, '_abt_language', true );

// Identifiers
$doi = get_post_meta( $post_id, '_abt_doi', true );
$pmid = get_post_meta( $post_id, '_abt_pmid', true );
$isbn = get_post_meta( $post_id, '_abt_isbn', true );
$issn = get_post_meta( $post_id, '_abt_issn', true );
$url = get_post_meta( $post_id, '_abt_url', true );
$access_date = get_post_meta( $post_id, '_abt_access_date', true );

?>

<div class="abt-reference-form">
	<!-- Auto-Cite Section -->
	<div class="abt-auto-cite-section">
		<h4>
			<span class="dashicons dashicons-download"></span>
			<?php _e( 'Auto-Cite', 'academic-bloggers-toolkit' ); ?>
		</h4>
		<p class="description">
			<?php _e( 'Automatically populate reference fields from external sources.', 'academic-bloggers-toolkit' ); ?>
		</p>
		
		<div class="abt-auto-cite-controls">
			<div class="abt-auto-cite-input-group">
				<label for="abt-auto-doi"><?php _e( 'DOI:', 'academic-bloggers-toolkit' ); ?></label>
				<input type="text" id="abt-auto-doi" placeholder="10.1000/182" class="regular-text" />
				<button type="button" class="button" id="abt-fetch-doi-btn">
					<span class="dashicons dashicons-search"></span>
					<?php _e( 'Fetch', 'academic-bloggers-toolkit' ); ?>
				</button>
			</div>
			
			<div class="abt-auto-cite-input-group">
				<label for="abt-auto-pmid"><?php _e( 'PMID:', 'academic-bloggers-toolkit' ); ?></label>
				<input type="text" id="abt-auto-pmid" placeholder="12345678" class="regular-text" />
				<button type="button" class="button" id="abt-fetch-pmid-btn">
					<span class="dashicons dashicons-search"></span>
					<?php _e( 'Fetch', 'academic-bloggers-toolkit' ); ?>
				</button>
			</div>
			
			<div class="abt-auto-cite-input-group">
				<label for="abt-auto-url"><?php _e( 'URL:', 'academic-bloggers-toolkit' ); ?></label>
				<input type="url" id="abt-auto-url" placeholder="https://example.com/article" class="regular-text" />
				<button type="button" class="button" id="abt-fetch-url-btn">
					<span class="dashicons dashicons-search"></span>
					<?php _e( 'Fetch', 'academic-bloggers-toolkit' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Manual Form -->
	<div class="abt-manual-form">
		<table class="form-table abt-reference-details">
			<tbody>
				<!-- Reference Type -->
				<tr class="abt-required-field">
					<th scope="row">
						<label for="abt_reference_type"><?php _e( 'Reference Type', 'academic-bloggers-toolkit' ); ?> <span class="required">*</span></label>
					</th>
					<td>
						<select name="abt_reference_type" id="abt_reference_type" required class="abt-reference-type-select">
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
							<option value="patent" <?php selected( $reference_type, 'patent' ); ?>><?php _e( 'Patent', 'academic-bloggers-toolkit' ); ?></option>
							<option value="software" <?php selected( $reference_type, 'software' ); ?>><?php _e( 'Software', 'academic-bloggers-toolkit' ); ?></option>
							<option value="dataset" <?php selected( $reference_type, 'dataset' ); ?>><?php _e( 'Dataset', 'academic-bloggers-toolkit' ); ?></option>
							<option value="other" <?php selected( $reference_type, 'other' ); ?>><?php _e( 'Other', 'academic-bloggers-toolkit' ); ?></option>
						</select>
						<p class="description"><?php _e( 'Choose the type of reference to show relevant fields.', 'academic-bloggers-toolkit' ); ?></p>
					</td>
				</tr>

				<!-- Authors -->
				<tr>
					<th scope="row">
						<label for="abt_authors"><?php _e( 'Authors', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<textarea name="abt_authors" id="abt_authors" rows="3" class="large-text"><?php echo esc_textarea( $authors ); ?></textarea>
						<p class="description">
							<?php _e( 'Enter one author per line or separate with semicolons. Format: Last, First M. or First Last', 'academic-bloggers-toolkit' ); ?>
							<br>
							<strong><?php _e( 'Examples:', 'academic-bloggers-toolkit' ); ?></strong>
							Smith, John A.; Brown, Mary B. <em><?php _e( 'or', 'academic-bloggers-toolkit' ); ?></em>
							John A. Smith; Mary B. Brown
						</p>
						<div class="abt-author-tools">
							<button type="button" class="button button-small" id="abt-format-authors">
								<span class="dashicons dashicons-editor-textcolor"></span>
								<?php _e( 'Format Names', 'academic-bloggers-toolkit' ); ?>
							</button>
							<button type="button" class="button button-small" id="abt-reverse-authors">
								<span class="dashicons dashicons-image-flip-horizontal"></span>
								<?php _e( 'Reverse Order', 'academic-bloggers-toolkit' ); ?>
							</button>
						</div>
					</td>
				</tr>

				<!-- Editors (conditional) -->
				<tr class="abt-field-editors" style="<?php echo in_array( $reference_type, array( 'book', 'book_chapter' ) ) ? '' : 'display: none;'; ?>">
					<th scope="row">
						<label for="abt_editors"><?php _e( 'Editors', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<textarea name="abt_editors" id="abt_editors" rows="2" class="large-text"><?php echo esc_textarea( $editors ); ?></textarea>
						<p class="description"><?php _e( 'For edited books or special journal issues. Same format as authors.', 'academic-bloggers-toolkit' ); ?></p>
					</td>
				</tr>

				<!-- Publication Year -->
				<tr>
					<th scope="row">
						<label for="abt_publication_year"><?php _e( 'Publication Year', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="number" 
							   name="abt_publication_year" 
							   id="abt_publication_year" 
							   value="<?php echo esc_attr( $publication_year ); ?>" 
							   min="1000" 
							   max="<?php echo date( 'Y' ) + 5; ?>" 
							   class="small-text" />
						<p class="description"><?php _e( 'Four-digit publication year.', 'academic-bloggers-toolkit' ); ?></p>
					</td>
				</tr>

				<!-- Chapter Title (conditional) -->
				<tr class="abt-field-chapter-title" style="<?php echo $reference_type === 'book_chapter' ? '' : 'display: none;'; ?>">
					<th scope="row">
						<label for="abt_chapter_title"><?php _e( 'Chapter Title', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="text" 
							   name="abt_chapter_title" 
							   id="abt_chapter_title" 
							   value="<?php echo esc_attr( $chapter_title ); ?>" 
							   class="regular-text" />
						<p class="description"><?php _e( 'Title of the specific chapter or section.', 'academic-bloggers-toolkit' ); ?></p>
					</td>
				</tr>

				<!-- Journal/Publication -->
				<tr>
					<th scope="row">
						<label for="abt_journal"><?php _e( 'Journal/Publication', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="text" 
							   name="abt_journal" 
							   id="abt_journal" 
							   value="<?php echo esc_attr( $journal ); ?>" 
							   class="regular-text" 
							   list="abt-journal-datalist" />
						<datalist id="abt-journal-datalist">
							<!-- Common journals will be populated here via JavaScript -->
						</datalist>
						<p class="description">
							<span class="abt-field-description-journal"><?php _e( 'Journal name, book title, conference proceedings, etc.', 'academic-bloggers-toolkit' ); ?></span>
						</p>
					</td>
				</tr>

				<!-- Volume and Issue -->
				<tr class="abt-field-volume-issue" style="<?php echo in_array( $reference_type, array( 'journal_article', 'magazine_article' ) ) ? '' : 'display: none;'; ?>">
					<th scope="row"><?php _e( 'Volume & Issue', 'academic-bloggers-toolkit' ); ?></th>
					<td>
						<div class="abt-inline-fields">
							<label for="abt_volume"><?php _e( 'Volume:', 'academic-bloggers-toolkit' ); ?></label>
							<input type="text" 
								   name="abt_volume" 
								   id="abt_volume" 
								   value="<?php echo esc_attr( $volume ); ?>" 
								   class="small-text" />
							
							<label for="abt_issue"><?php _e( 'Issue/Number:', 'academic-bloggers-toolkit' ); ?></label>
							<input type="text" 
								   name="abt_issue" 
								   id="abt_issue" 
								   value="<?php echo esc_attr( $issue ); ?>" 
								   class="small-text" />
						</div>
						<p class="description"><?php _e( 'Volume and issue numbers for journal articles.', 'academic-bloggers-toolkit' ); ?></p>
					</td>
				</tr>

				<!-- Pages -->
				<tr class="abt-field-pages">
					<th scope="row">
						<label for="abt_pages"><?php _e( 'Pages', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="text" 
							   name="abt_pages" 
							   id="abt_pages" 
							   value="<?php echo esc_attr( $pages ); ?>" 
							   class="regular-text" 
							   placeholder="123-145" />
						<p class="description">
							<?php _e( 'Page range or specific pages.', 'academic-bloggers-toolkit' ); ?>
							<strong><?php _e( 'Examples:', 'academic-bloggers-toolkit' ); ?></strong>
							123-145, 15-20, 45, pp. 123-145
						</p>
					</td>
				</tr>

				<!-- Publisher and Place -->
				<tr class="abt-field-publisher" style="<?php echo in_array( $reference_type, array( 'book', 'book_chapter', 'report', 'thesis' ) ) ? '' : 'display: none;'; ?>">
					<th scope="row"><?php _e( 'Publisher Info', 'academic-bloggers-toolkit' ); ?></th>
					<td>
						<div class="abt-publisher-fields">
							<label for="abt_publisher"><?php _e( 'Publisher:', 'academic-bloggers-toolkit' ); ?></label>
							<input type="text" 
								   name="abt_publisher" 
								   id="abt_publisher" 
								   value="<?php echo esc_attr( $publisher ); ?>" 
								   class="regular-text" 
								   list="abt-publisher-datalist" />
							<datalist id="abt-publisher-datalist">
								<!-- Common publishers will be populated here -->
							</datalist>
							
							<br><br>
							
							<label for="abt_publication_place"><?php _e( 'Place:', 'academic-bloggers-toolkit' ); ?></label>
							<input type="text" 
								   name="abt_publication_place" 
								   id="abt_publication_place" 
								   value="<?php echo esc_attr( $publication_place ); ?>" 
								   class="regular-text" 
								   placeholder="New York, NY" />
						</div>
						<p class="description"><?php _e( 'Publisher name and location (City, State/Country).', 'academic-bloggers-toolkit' ); ?></p>
					</td>
				</tr>

				<!-- Edition -->
				<tr class="abt-field-edition" style="<?php echo in_array( $reference_type, array( 'book', 'book_chapter' ) ) ? '' : 'display: none;'; ?>">
					<th scope="row">
						<label for="abt_edition"><?php _e( 'Edition', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="text" 
							   name="abt_edition" 
							   id="abt_edition" 
							   value="<?php echo esc_attr( $edition ); ?>" 
							   class="small-text" 
							   placeholder="2nd" />
						<p class="description"><?php _e( 'Edition number (e.g., 2nd, 3rd, Revised).', 'academic-bloggers-toolkit' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<!-- Identifiers Section -->
		<div class="abt-form-section">
			<h4>
				<span class="dashicons dashicons-admin-links"></span>
				<?php _e( 'Identifiers & Links', 'academic-bloggers-toolkit' ); ?>
			</h4>
			
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="abt_doi"><?php _e( 'DOI', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<input type="text" 
								   name="abt_doi" 
								   id="abt_doi" 
								   value="<?php echo esc_attr( $doi ); ?>" 
								   class="regular-text" 
								   placeholder="10.1000/182" />
							<p class="description">
								<?php _e( 'Digital Object Identifier.', 'academic-bloggers-toolkit' ); ?>
								<a href="https://www.doi.org/" target="_blank"><?php _e( 'Learn more', 'academic-bloggers-toolkit' ); ?></a>
							</p>
						</td>
					</tr>

					<tr class="abt-field-pmid" style="<?php echo in_array( $reference_type, array( 'journal_article' ) ) ? '' : 'display: none;'; ?>">
						<th scope="row">
							<label for="abt_pmid"><?php _e( 'PMID', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<input type="text" 
								   name="abt_pmid" 
								   id="abt_pmid" 
								   value="<?php echo esc_attr( $pmid ); ?>" 
								   class="regular-text" 
								   placeholder="12345678" />
							<p class="description">
								<?php _e( 'PubMed ID for biomedical literature.', 'academic-bloggers-toolkit' ); ?>
								<a href="https://pubmed.ncbi.nlm.nih.gov/" target="_blank"><?php _e( 'Search PubMed', 'academic-bloggers-toolkit' ); ?></a>
							</p>
						</td>
					</tr>

					<tr class="abt-field-isbn" style="<?php echo in_array( $reference_type, array( 'book', 'book_chapter' ) ) ? '' : 'display: none;'; ?>">
						<th scope="row">
							<label for="abt_isbn"><?php _e( 'ISBN', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<input type="text" 
								   name="abt_isbn" 
								   id="abt_isbn" 
								   value="<?php echo esc_attr( $isbn ); ?>" 
								   class="regular-text" 
								   placeholder="978-0-123456-78-9" />
							<p class="description"><?php _e( 'International Standard Book Number (10 or 13 digits).', 'academic-bloggers-toolkit' ); ?></p>
						</td>
					</tr>

					<tr class="abt-field-issn" style="<?php echo in_array( $reference_type, array( 'journal_article', 'magazine_article' ) ) ? '' : 'display: none;'; ?>">
						<th scope="row">
							<label for="abt_issn"><?php _e( 'ISSN', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<input type="text" 
								   name="abt_issn" 
								   id="abt_issn" 
								   value="<?php echo esc_attr( $issn ); ?>" 
								   class="regular-text" 
								   placeholder="1234-5678" />
							<p class="description"><?php _e( 'International Standard Serial Number for periodicals.', 'academic-bloggers-toolkit' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="abt_url"><?php _e( 'URL', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<input type="url" 
								   name="abt_url" 
								   id="abt_url" 
								   value="<?php echo esc_attr( $url ); ?>" 
								   class="regular-text" />
							<p class="description"><?php _e( 'Web address where the resource can be accessed.', 'academic-bloggers-toolkit' ); ?></p>
						</td>
					</tr>

					<tr class="abt-field-access-date" style="<?php echo in_array( $reference_type, array( 'website' ) ) || $url ? '' : 'display: none;'; ?>">
						<th scope="row">
							<label for="abt_access_date"><?php _e( 'Access Date', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<input type="date" 
								   name="abt_access_date" 
								   id="abt_access_date" 
								   value="<?php echo esc_attr( $access_date ); ?>" 
								   class="regular-text" />
							<p class="description"><?php _e( 'Date you accessed the online resource.', 'academic-bloggers-toolkit' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Additional Information Section -->
		<div class="abt-form-section">
			<h4>
				<span class="dashicons dashicons-text-page"></span>
				<?php _e( 'Additional Information', 'academic-bloggers-toolkit' ); ?>
			</h4>
			
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="abt_abstract"><?php _e( 'Abstract', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<textarea name="abt_abstract" 
									  id="abt_abstract" 
									  rows="4" 
									  class="large-text"><?php echo esc_textarea( $abstract ); ?></textarea>
							<p class="description"><?php _e( 'Brief summary of the work (optional, for your reference).', 'academic-bloggers-toolkit' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="abt_keywords"><?php _e( 'Keywords', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<input type="text" 
								   name="abt_keywords" 
								   id="abt_keywords" 
								   value="<?php echo esc_attr( $keywords ); ?>" 
								   class="regular-text" 
								   placeholder="keyword1, keyword2, keyword3" />
							<p class="description"><?php _e( 'Comma-separated keywords for organizing and searching.', 'academic-bloggers-toolkit' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="abt_language"><?php _e( 'Language', 'academic-bloggers-toolkit' ); ?></label>
						</th>
						<td>
							<select name="abt_language" id="abt_language">
								<option value=""><?php _e( 'Not specified', 'academic-bloggers-toolkit' ); ?></option>
								<option value="en" <?php selected( $language, 'en' ); ?>><?php _e( 'English', 'academic-bloggers-toolkit' ); ?></option>
								<option value="es" <?php selected( $language, 'es' ); ?>><?php _e( 'Spanish', 'academic-bloggers-toolkit' ); ?></option>
								<option value="fr" <?php selected( $language, 'fr' ); ?>><?php _e( 'French', 'academic-bloggers-toolkit' ); ?></option>
								<option value="de" <?php selected( $language, 'de' ); ?>><?php _e( 'German', 'academic-bloggers-toolkit' ); ?></option>
								<option value="it" <?php selected( $language, 'it' ); ?>><?php _e( 'Italian', 'academic-bloggers-toolkit' ); ?></option>
								<option value="pt" <?php selected( $language, 'pt' ); ?>><?php _e( 'Portuguese', 'academic-bloggers-toolkit' ); ?></option>
								<option value="zh" <?php selected( $language, 'zh' ); ?>><?php _e( 'Chinese', 'academic-bloggers-toolkit' ); ?></option>
								<option value="ja" <?php selected( $language, 'ja' ); ?>><?php _e( 'Japanese', 'academic-bloggers-toolkit' ); ?></option>
								<option value="other"><?php _e( 'Other', 'academic-bloggers-toolkit' ); ?></option>
							</select>
							<p class="description"><?php _e( 'Language of the publication.', 'academic-bloggers-toolkit' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Citation Preview -->
		<div class="abt-form-section">
			<h4>
				<span class="dashicons dashicons-visibility"></span>
				<?php _e( 'Citation Preview', 'academic-bloggers-toolkit' ); ?>
			</h4>
			
			<div class="abt-citation-preview-container">
				<div class="abt-preview-controls">
					<label for="abt-preview-style"><?php _e( 'Style:', 'academic-bloggers-toolkit' ); ?></label>
					<select id="abt-preview-style">
						<option value="apa">APA</option>
						<option value="mla">MLA</option>
						<option value="chicago">Chicago</option>
						<option value="harvard">Harvard</option>
						<option value="ieee">IEEE</option>
					</select>
					<button type="button" class="button button-small" id="abt-refresh-preview">
						<span class="dashicons dashicons-update"></span>
						<?php _e( 'Refresh', 'academic-bloggers-toolkit' ); ?>
					</button>
				</div>
				
				<div class="abt-citation-preview" id="abt-citation-preview">
					<p class="description"><?php _e( 'Fill in the fields above to see a formatted citation preview.', 'academic-bloggers-toolkit' ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.abt-reference-form {
	padding: 10px 0;
}

.abt-auto-cite-section {
	background: #f0f6fc;
	border: 1px solid #c3d9ff;
	border-radius: 5px;
	padding: 15px;
	margin-bottom: 20px;
}

.abt-auto-cite-section h4 {
	margin: 0 0 10px 0;
	color: #0d47a1;
	display: flex;
	align-items: center;
	gap: 8px;
}

.abt-auto-cite-controls {
	display: grid;
	grid-template-columns: 1fr;
	gap: 10px;
	margin-top: 15px;
}

.abt-auto-cite-input-group {
	display: flex;
	align-items: center;
	gap: 10px;
}

.abt-auto-cite-input-group label {
	min-width: 50px;
	font-weight: 600;
}

.abt-auto-cite-input-group input {
	flex: 1;
}

.abt-manual-form .form-table th {
	width: 150px;
	padding: 15px 10px 15px 0;
	vertical-align: top;
}

.abt-manual-form .form-table td {
	padding: 15px 10px;
}

.abt-required-field .required {
	color: #d63638;
}

.abt-inline-fields {
	display: flex;
	align-items: center;
	gap: 15px;
	flex-wrap: wrap;
}

.abt-inline-fields label {
	font-weight: 600;
	margin-right: 5px;
}

.abt-publisher-fields {
	display: grid;
	grid-template-columns: auto 1fr;
	gap: 10px;
	align-items: center;
}

.abt-author-tools {
	margin-top: 8px;
}

.abt-author-tools .button {
	margin-right: 5px;
}

.abt-form-section {
	margin-top: 30px;
	padding-top: 20px;
	border-top: 1px solid #ddd;
}

.abt-form-section h4 {
	margin: 0 0 15px 0;
	color: #23282d;
	display: flex;
	align-items: center;
	gap: 8px;
}

.abt-citation-preview-container {
	background: #f9f9f9;
	border: 1px solid #ddd;
	border-radius: 3px;
	padding: 15px;
}

.abt-preview-controls {
	display: flex;
	align-items: center;
	gap: 10px;
	margin-bottom: 15px;
	padding-bottom: 10px;
	border-bottom: 1px solid #ddd;
}

.abt-citation-preview {
	font-family: Georgia, serif;
	line-height: 1.6;
	min-height: 40px;
	padding: 10px;
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 3px;
}

/* Conditional field styling */
.abt-field-hidden {
	display: none !important;
}

.abt-field-visible {
	display: table-row !important;
}

/* Responsive design */
@media (max-width: 768px) {
	.abt-auto-cite-input-group {
		flex-direction: column;
		align-items: stretch;
	}
	
	.abt-auto-cite-input-group label {
		min-width: auto;
	}
	
	.abt-inline-fields {
		flex-direction: column;
		align-items: stretch;
	}
	
	.abt-publisher-fields {
		grid-template-columns: 1fr;
	}
	
	.abt-preview-controls {
		flex-wrap: wrap;
	}
}

/* Loading states */
.abt-loading .button::after {
	content: '';
	display: inline-block;
	width: 12px;
	height: 12px;
	margin-left: 5px;
	border: 2px solid #ccc;
	border-top-color: #0073aa;
	border-radius: 50%;
	animation: abt-spin 1s linear infinite;
}

@keyframes abt-spin {
	to {
		transform: rotate(360deg);
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	
	// Reference type change handler
	$('#abt_reference_type').on('change', function() {
		var referenceType = $(this).val();
		updateConditionalFields(referenceType);
		updateFieldDescriptions(referenceType);
	});
	
	// Update conditional fields based on reference type
	function updateConditionalFields(type) {
		// Hide all conditional fields first
		$('.abt-field-editors, .abt-field-chapter-title, .abt-field-volume-issue, .abt-field-publisher, .abt-field-edition, .abt-field-pmid, .abt-field-isbn, .abt-field-issn').hide();
		
		// Show relevant fields based on type
		switch(type) {
			case 'journal_article':
				$('.abt-field-volume-issue, .abt-field-pmid, .abt-field-issn').show();
				break;
			case 'book':
				$('.abt-field-editors, .abt-field-publisher, .abt-field-edition, .abt-field-isbn').show();
				break;
			case 'book_chapter':
				$('.abt-field-editors, .abt-field-chapter-title, .abt-field-publisher, .abt-field-edition, .abt-field-isbn').show();
				break;
			case 'conference_paper':
				$('.abt-field-volume-issue, .abt-field-publisher').show();
				break;
			case 'thesis':
			case 'report':
				$('.abt-field-publisher').show();
				break;
			case 'magazine_article':
			case 'newspaper_article':
				$('.abt-field-volume-issue, .abt-field-issn').show();
				break;
			case 'website':
				$('.abt-field-access-date').show();
				break;
		}
		
		// Always show access date if URL is filled
		if ($('#abt_url').val()) {
			$('.abt-field-access-date').show();
		}
	}
	
	// Update field descriptions based on reference type
	function updateFieldDescriptions(type) {
		var journalDesc = $('.abt-field-description-journal');
		
		switch(type) {
			case 'journal_article':
				journalDesc.text('Name of the journal or periodical.');
				break;
			case 'book':
			case 'book_chapter':
				journalDesc.text('Title of the book or collection.');
				break;
			case 'conference_paper':
				journalDesc.text('Name of the conference or proceedings.');
				break;
			case 'website':
				journalDesc.text('Name of the website or organization.');
				break;
			default:
				journalDesc.text('Journal name, book title, conference proceedings, etc.');
		}
	}
	
	// URL change handler - show access date field
	$('#abt_url').on('input', function() {
		if ($(this).val()) {
			$('.abt-field-access-date').show();
		} else {
			$('.abt-field-access-date').hide();
		}
	});
	
	// Auto-cite functionality
	$('#abt-fetch-doi-btn').on('click', function() {
		var doi = $('#abt-auto-doi').val().trim();
		if (doi) {
			fetchFromDOI(doi);
		}
	});
	
	$('#abt-fetch-pmid-btn').on('click', function() {
		var pmid = $('#abt-auto-pmid').val().trim();
		if (pmid) {
			fetchFromPMID(pmid);
		}
	});
	
	$('#abt-fetch-url-btn').on('click', function() {
		var url = $('#abt-auto-url').val().trim();
		if (url) {
			fetchFromURL(url);
		}
	});
	
	// Auto-cite functions
	function fetchFromDOI(doi) {
		var $btn = $('#abt-fetch-doi-btn');
		$btn.prop('disabled', true).addClass('abt-loading');
		
		$.post(ajaxurl, {
			action: 'abt_fetch_from_doi',
			doi: doi,
			nonce: $('#abt_reference_nonce').val()
		})
		.done(function(response) {
			if (response.success) {
				populateFields(response.data.reference_data);
				showNotice('success', 'Reference data fetched successfully from DOI.');
			} else {
				showNotice('error', response.data.message || 'Failed to fetch DOI data.');
			}
		})
		.fail(function() {
			showNotice('error', 'Error fetching DOI data. Please try again.');
		})
		.always(function() {
			$btn.prop('disabled', false).removeClass('abt-loading');
		});
	}
	
	function fetchFromPMID(pmid) {
		var $btn = $('#abt-fetch-pmid-btn');
		$btn.prop('disabled', true).addClass('abt-loading');
		
		$.post(ajaxurl, {
			action: 'abt_fetch_from_pmid',
			pmid: pmid,
			nonce: $('#abt_reference_nonce').val()
		})
		.done(function(response) {
			if (response.success) {
				populateFields(response.data.reference_data);
				showNotice('success', 'Reference data fetched successfully from PMID.');
			} else {
				showNotice('error', response.data.message || 'Failed to fetch PMID data.');
			}
		})
		.fail(function() {
			showNotice('error', 'Error fetching PMID data. Please try again.');
		})
		.always(function() {
			$btn.prop('disabled', false).removeClass('abt-loading');
		});
	}
	
	function fetchFromURL(url) {
		var $btn = $('#abt-fetch-url-btn');
		$btn.prop('disabled', true).addClass('abt-loading');
		
		$.post(ajaxurl, {
			action: 'abt_fetch_from_url',
			url: url,
			nonce: $('#abt_reference_nonce').val()
		})
		.done(function(response) {
			if (response.success) {
				populateFields(response.data.reference_data);
				showNotice('success', 'Reference data fetched successfully from URL.');
			} else {
				showNotice('error', response.data.message || 'Failed to fetch URL data.');
			}
		})
		.fail(function() {
			showNotice('error', 'Error fetching URL data. Please try again.');
		})
		.always(function() {
			$btn.prop('disabled', false).removeClass('abt-loading');
		});
	}
	
	// Populate form fields with fetched data
	function populateFields(data) {
		for (var field in data) {
			if (data.hasOwnProperty(field)) {
				var $field = $('#abt_' + field);
				if ($field.length && data[field]) {
					$field.val(data[field]);
				}
			}
		}
		
		// Update conditional fields after populating
		var referenceType = $('#abt_reference_type').val();
		if (referenceType) {
			updateConditionalFields(referenceType);
		}
		
		// Refresh citation preview
		refreshCitationPreview();
	}
	
	// Author formatting tools
	$('#abt-format-authors').on('click', function() {
		var authors = $('#abt_authors').val();
		var formatted = formatAuthorNames(authors);
		$('#abt_authors').val(formatted);
	});
	
	$('#abt-reverse-authors').on('click', function() {
		var authors = $('#abt_authors').val();
		var lines = authors.split('\n');
		var reversed = lines.reverse().join('\n');
		$('#abt_authors').val(reversed);
	});
	
	// Format author names
	function formatAuthorNames(authors) {
		// Simple formatting - convert "First Last" to "Last, First"
		var lines = authors.split('\n');
		var formatted = lines.map(function(line) {
			line = line.trim();
			if (line && !line.includes(',')) {
				var parts = line.split(' ');
				if (parts.length >= 2) {
					var last = parts.pop();
					var first = parts.join(' ');
					return last + ', ' + first;
				}
			}
			return line;
		});
		return formatted.join('\n');
	}
	
	// Citation preview
	$('#abt-refresh-preview').on('click', function() {
		refreshCitationPreview();
	});
	
	$('#abt-preview-style').on('change', function() {
		refreshCitationPreview();
	});
	
	// Auto-refresh preview when key fields change
	$('#post_title, #abt_authors, #abt_publication_year, #abt_journal').on('input', function() {
		clearTimeout(window.abtPreviewTimeout);
		window.abtPreviewTimeout = setTimeout(refreshCitationPreview, 1000);
	});
	
	function refreshCitationPreview() {
		var data = {
			title: $('#post_title').val(),
			authors: $('#abt_authors').val(),
			year: $('#abt_publication_year').val(),
			journal: $('#abt_journal').val(),
			volume: $('#abt_volume').val(),
			issue: $('#abt_issue').val(),
			pages: $('#abt_pages').val(),
			type: $('#abt_reference_type').val(),
			style: $('#abt-preview-style').val()
		};
		
		if (!data.title && !data.authors) {
			$('#abt-citation-preview').html('<p class="description">Fill in the title and authors to see a citation preview.</p>');
			return;
		}
		
		// Generate a basic preview (this would be enhanced with proper formatting)
		var preview = generateCitationPreview(data);
		$('#abt-citation-preview').html(preview);
	}
	
	// Generate basic citation preview
	function generateCitationPreview(data) {
		var preview = '';
		
		switch(data.style) {
			case 'apa':
				if (data.authors) preview += data.authors + ' ';
				if (data.year) preview += '(' + data.year + '). ';
				if (data.title) preview += '<em>' + data.title + '</em>';
				if (data.journal) preview += '. <em>' + data.journal + '</em>';
				if (data.volume) preview += ', <em>' + data.volume + '</em>';
				if (data.issue) preview += '(' + data.issue + ')';
				if (data.pages) preview += ', ' + data.pages;
				break;
			case 'mla':
				if (data.authors) preview += data.authors + '. ';
				if (data.title) preview += '"' + data.title + '"';
				if (data.journal) preview += '. <em>' + data.journal + '</em>';
				if (data.volume) preview += ', vol. ' + data.volume;
				if (data.issue) preview += ', no. ' + data.issue;
				if (data.year) preview += ', ' + data.year;
				if (data.pages) preview += ', pp. ' + data.pages;
				break;
			default:
				if (data.authors) preview += data.authors + '. ';
				if (data.title) preview += data.title;
				if (data.year) preview += ' (' + data.year + ')';
				if (data.journal) preview += '. ' + data.journal;
		}
		
		return preview || '<p class="description">Fill in more fields to see a complete citation.</p>';
	}
	
	// Show admin notice
	function showNotice(type, message) {
		var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
		$('.abt-reference-form').prepend($notice);
		
		setTimeout(function() {
			$notice.fadeOut(function() {
				$(this).remove();
			});
		}, 5000);
	}
	
	// Initialize on page load
	var initialType = $('#abt_reference_type').val();
	if (initialType) {
		updateConditionalFields(initialType);
		updateFieldDescriptions(initialType);
	}
	
	// Initial preview refresh
	refreshCitationPreview();
	
	// Populate common journals and publishers (this would come from a database)
	var commonJournals = [
		'Nature', 'Science', 'Cell', 'The Lancet', 'New England Journal of Medicine',
		'PLOS ONE', 'Proceedings of the National Academy of Sciences', 'Journal of Biological Chemistry'
	];
	
	var commonPublishers = [
		'Springer', 'Elsevier', 'Wiley', 'Oxford University Press', 'Cambridge University Press',
		'MIT Press', 'University of Chicago Press', 'Yale University Press'
	];
	
	// Populate datalists
	commonJournals.forEach(function(journal) {
		$('#abt-journal-datalist').append('<option value="' + journal + '">');
	});
	
	commonPublishers.forEach(function(publisher) {
		$('#abt-publisher-datalist').append('<option value="' + publisher + '">');
	});
});
</script>