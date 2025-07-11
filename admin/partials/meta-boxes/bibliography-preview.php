<?php
/**
 * Provide a admin area view for the bibliography preview meta box.
 *
 * This file is used to markup the bibliography preview functionality.
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

// Get current settings
$citation_style = get_post_meta( $post_id, '_abt_citation_style', true ) ?: 'apa';
$auto_bibliography = get_post_meta( $post_id, '_abt_auto_bibliography', true );

// Get citations for this post
$citations = ABT_Citation::get_by_post( $post_id );

?>

<div class="abt-bibliography-preview">
	<?php if ( empty( $citations ) ) : ?>
		<div class="abt-no-bibliography">
			<p>
				<span class="dashicons dashicons-book" style="font-size: 48px; color: #ddd;"></span>
			</p>
			<p><?php _e( 'No citations added yet.', 'academic-bloggers-toolkit' ); ?></p>
			<p class="description">
				<?php _e( 'Add citations to your post to see the bibliography preview here. The bibliography will be automatically generated based on your citation style.', 'academic-bloggers-toolkit' ); ?>
			</p>
			<p>
				<a href="#abt-add-citation" class="button button-primary" onclick="document.getElementById('abt-add-citation').click(); return false;">
					<span class="dashicons dashicons-plus"></span>
					<?php _e( 'Add Your First Citation', 'academic-bloggers-toolkit' ); ?>
				</a>
			</p>
		</div>
	<?php else : ?>
		<div class="abt-bibliography-header">
			<h4>
				<?php _e( 'Bibliography Preview', 'academic-bloggers-toolkit' ); ?>
				<span class="abt-style-indicator">(<?php echo esc_html( strtoupper( $citation_style ) ); ?>)</span>
				<span class="abt-citation-count"><?php printf( _n( '%d citation', '%d citations', count( $citations ), 'academic-bloggers-toolkit' ), count( $citations ) ); ?></span>
			</h4>
			<div class="abt-bibliography-controls">
				<button type="button" class="button button-small" id="abt-refresh-bibliography" title="<?php _e( 'Refresh bibliography preview', 'academic-bloggers-toolkit' ); ?>">
					<span class="dashicons dashicons-update"></span>
					<?php _e( 'Refresh', 'academic-bloggers-toolkit' ); ?>
				</button>
				<button type="button" class="button button-small" id="abt-copy-bibliography" title="<?php _e( 'Copy bibliography to clipboard', 'academic-bloggers-toolkit' ); ?>">
					<span class="dashicons dashicons-clipboard"></span>
					<?php _e( 'Copy', 'academic-bloggers-toolkit' ); ?>
				</button>
			</div>
		</div>

		<div class="abt-bibliography-content">
			<div class="abt-bibliography-title">
				<h5><?php echo esc_html( get_option( 'abt_bibliography_title', __( 'References', 'academic-bloggers-toolkit' ) ) ); ?></h5>
			</div>
			
			<ol class="abt-bibliography-list">
				<?php foreach ( $citations as $index => $citation ) : ?>
					<?php
					$reference = ABT_Reference::get( $citation->reference_id );
					if ( ! $reference ) continue;
					?>
					<li class="abt-bibliography-item" data-citation-id="<?php echo esc_attr( $citation->id ); ?>">
						<?php echo $this->format_bibliography_entry( $reference, $citation_style ); ?>
						<div class="abt-bibliography-item-meta">
							<small class="abt-citation-info">
								<?php if ( $citation->prefix || $citation->suffix ) : ?>
									<?php _e( 'Used with:', 'academic-bloggers-toolkit' ); ?>
									<?php if ( $citation->prefix ) : ?>
										<span class="abt-prefix-info"><?php echo esc_html( $citation->prefix ); ?></span>
									<?php endif; ?>
									<?php if ( $citation->suffix ) : ?>
										<span class="abt-suffix-info"><?php echo esc_html( $citation->suffix ); ?></span>
									<?php endif; ?>
								<?php endif; ?>
							</small>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>

		<div class="abt-bibliography-actions">
			<div class="abt-bibliography-settings">
				<label for="abt-preview-style"><?php _e( 'Preview Style:', 'academic-bloggers-toolkit' ); ?></label>
				<select id="abt-preview-style" name="preview_style">
					<option value="apa" <?php selected( $citation_style, 'apa' ); ?>>APA</option>
					<option value="mla" <?php selected( $citation_style, 'mla' ); ?>>MLA</option>
					<option value="chicago" <?php selected( $citation_style, 'chicago' ); ?>>Chicago</option>
					<option value="harvard" <?php selected( $citation_style, 'harvard' ); ?>>Harvard</option>
					<option value="ieee" <?php selected( $citation_style, 'ieee' ); ?>>IEEE</option>
				</select>
			</div>
			
			<div class="abt-export-actions">
				<button type="button" class="button" id="abt-export-bibliography-rtf">
					<span class="dashicons dashicons-download"></span>
					<?php _e( 'Export as RTF', 'academic-bloggers-toolkit' ); ?>
				</button>
				<button type="button" class="button" id="abt-export-bibliography-txt">
					<span class="dashicons dashicons-media-text"></span>
					<?php _e( 'Export as Text', 'academic-bloggers-toolkit' ); ?>
				</button>
			</div>
		</div>

		<div class="abt-bibliography-options">
			<h5><?php _e( 'Bibliography Options', 'academic-bloggers-toolkit' ); ?></h5>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="abt_auto_bibliography_display"><?php _e( 'Display', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php _e( 'Bibliography display options', 'academic-bloggers-toolkit' ); ?></legend>
							<label for="abt_auto_bibliography_display">
								<input type="checkbox" 
									   id="abt_auto_bibliography_display" 
									   name="abt_auto_bibliography" 
									   value="1" 
									   <?php checked( $auto_bibliography ); ?> />
								<?php _e( 'Automatically display bibliography at end of post', 'academic-bloggers-toolkit' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="abt_bibliography_title_custom"><?php _e( 'Custom Title', 'academic-bloggers-toolkit' ); ?></label>
					</th>
					<td>
						<input type="text" 
							   id="abt_bibliography_title_custom" 
							   name="abt_bibliography_title" 
							   value="<?php echo esc_attr( get_post_meta( $post_id, '_abt_bibliography_title', true ) ); ?>" 
							   class="regular-text" 
							   placeholder="<?php echo esc_attr( get_option( 'abt_bibliography_title', __( 'References', 'academic-bloggers-toolkit' ) ) ); ?>" />
						<p class="description">
							<?php _e( 'Leave blank to use the default title from plugin settings.', 'academic-bloggers-toolkit' ); ?>
						</p>
					</td>
				</tr>
			</table>
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
	display: flex;
	align-items: center;
	gap: 10px;
}

.abt-style-indicator {
	font-size: 12px;
	color: #666;
	font-weight: normal;
	background: #f0f0f1;
	padding: 2px 6px;
	border-radius: 3px;
}

.abt-citation-count {
	font-size: 12px;
	color: #0073aa;
	font-weight: normal;
	background: #e7f3ff;
	padding: 2px 6px;
	border-radius: 3px;
}

.abt-bibliography-controls {
	display: flex;
	gap: 5px;
}

.abt-bibliography-content {
	margin-bottom: 20px;
}

.abt-bibliography-title h5 {
	margin: 0 0 10px 0;
	font-size: 16px;
	font-weight: 600;
	color: #23282d;
	border-bottom: 1px solid #ddd;
	padding-bottom: 5px;
}

.abt-bibliography-list {
	margin: 0;
	padding-left: 20px;
	line-height: 1.6;
}

.abt-bibliography-item {
	margin-bottom: 15px;
	position: relative;
}

.abt-bibliography-item:hover {
	background-color: #f9f9f9;
	padding: 5px;
	margin: 0 -5px 10px -5px;
	border-radius: 3px;
}

.abt-bibliography-item-meta {
	margin-top: 5px;
}

.abt-citation-info {
	color: #666;
	font-style: italic;
}

.abt-prefix-info,
.abt-suffix-info {
	background: #e5e5e5;
	padding: 1px 4px;
	border-radius: 2px;
	margin: 0 2px;
}

.abt-bibliography-actions {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
	padding: 10px;
	background: #f9f9f9;
	border-radius: 3px;
}

.abt-bibliography-settings {
	display: flex;
	align-items: center;
	gap: 10px;
}

.abt-export-actions {
	display: flex;
	gap: 10px;
}

.abt-bibliography-options {
	margin-top: 20px;
	padding-top: 15px;
	border-top: 1px solid #ddd;
}

.abt-bibliography-options h5 {
	margin: 0 0 15px 0;
	color: #23282d;
}

.abt-no-bibliography {
	text-align: center;
	padding: 40px 20px;
	background: #f9f9f9;
	border: 2px dashed #ddd;
	border-radius: 4px;
}

.abt-no-bibliography p {
	margin: 10px 0;
}

.abt-no-bibliography .description {
	color: #666;
	font-style: italic;
	margin-bottom: 20px;
}

/* Responsive design */
@media (max-width: 768px) {
	.abt-bibliography-header {
		flex-direction: column;
		align-items: flex-start;
		gap: 10px;
	}
	
	.abt-bibliography-actions {
		flex-direction: column;
		align-items: stretch;
		gap: 15px;
	}
	
	.abt-bibliography-settings,
	.abt-export-actions {
		justify-content: center;
	}
}

/* Print styles */
@media print {
	.abt-bibliography-header,
	.abt-bibliography-actions,
	.abt-bibliography-options {
		display: none;
	}
	
	.abt-bibliography-list {
		page-break-inside: avoid;
	}
	
	.abt-bibliography-item {
		page-break-inside: avoid;
		margin-bottom: 10px;
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	// Style change handler
	$('#abt-preview-style').on('change', function() {
		var newStyle = $(this).val();
		
		// Update the main citation style field if it exists
		$('#abt_citation_style').val(newStyle);
		
		// Trigger bibliography refresh
		$('#abt-refresh-bibliography').click();
		
		// Update style indicator
		$('.abt-style-indicator').text('(' + newStyle.toUpperCase() + ')');
	});
	
	// Copy bibliography functionality
	$('#abt-copy-bibliography').on('click', function() {
		var bibliographyText = $('.abt-bibliography-list').text();
		
		if (navigator.clipboard) {
			navigator.clipboard.writeText(bibliographyText).then(function() {
				// Show success feedback
				var $button = $('#abt-copy-bibliography');
				var originalText = $button.html();
				$button.html('<span class="dashicons dashicons-yes"></span> Copied!');
				setTimeout(function() {
					$button.html(originalText);
				}, 2000);
			});
		} else {
			// Fallback for older browsers
			var $temp = $('<textarea>').val(bibliographyText).appendTo('body').select();
			document.execCommand('copy');
			$temp.remove();
			
			// Show success feedback
			var $button = $('#abt-copy-bibliography');
			var originalText = $button.html();
			$button.html('<span class="dashicons dashicons-yes"></span> Copied!');
			setTimeout(function() {
				$button.html(originalText);
			}, 2000);
		}
	});
	
	// Auto bibliography toggle
	$('#abt_auto_bibliography_display').on('change', function() {
		var isChecked = $(this).is(':checked');
		
		// Save the setting via AJAX
		$.post(ajaxurl, {
			action: 'abt_update_bibliography_setting',
			post_id: $('#post_ID').val(),
			auto_bibliography: isChecked ? 1 : 0,
			nonce: $('#abt_citations_nonce').val()
		});
	});
	
	// Custom title field
	$('#abt_bibliography_title_custom').on('input', function() {
		var customTitle = $(this).val();
		var defaultTitle = $(this).attr('placeholder');
		
		// Update preview title
		var displayTitle = customTitle || defaultTitle;
		$('.abt-bibliography-title h5').text(displayTitle);
	});
});
</script>

<?php
/**
 * Helper function to format bibliography entry.
 * This would normally be in the main class, but including here for the partial.
 */
if ( ! function_exists( 'abt_format_bibliography_entry' ) ) {
	function abt_format_bibliography_entry( $reference, $style ) {
		$authors = $reference->get_meta( 'authors' );
		$year = $reference->get_meta( 'year' );
		$title = $reference->post_title;
		$journal = $reference->get_meta( 'journal' );
		$volume = $reference->get_meta( 'volume' );
		$issue = $reference->get_meta( 'issue' );
		$pages = $reference->get_meta( 'pages' );
		$doi = $reference->get_meta( 'doi' );
		$url = $reference->get_meta( 'url' );

		switch ( $style ) {
			case 'apa':
				$formatted = '';
				if ( $authors ) $formatted .= $authors . ' ';
				if ( $year ) $formatted .= '(' . $year . '). ';
				$formatted .= '<em>' . $title . '</em>';
				if ( $journal ) {
					$formatted .= '. <em>' . $journal . '</em>';
					if ( $volume ) $formatted .= ', <em>' . $volume . '</em>';
					if ( $issue ) $formatted .= '(' . $issue . ')';
					if ( $pages ) $formatted .= ', ' . $pages;
				}
				if ( $doi ) $formatted .= '. https://doi.org/' . $doi;
				break;

			case 'mla':
				$formatted = '';
				if ( $authors ) $formatted .= $authors . '. ';
				$formatted .= '"' . $title . '"';
				if ( $journal ) {
					$formatted .= '. <em>' . $journal . '</em>';
					if ( $volume ) $formatted .= ', vol. ' . $volume;
					if ( $issue ) $formatted .= ', no. ' . $issue;
					if ( $year ) $formatted .= ', ' . $year;
					if ( $pages ) $formatted .= ', pp. ' . $pages;
				}
				break;

			case 'chicago':
				$formatted = '';
				if ( $authors ) $formatted .= $authors . '. ';
				$formatted .= '"' . $title . '"';
				if ( $journal ) {
					$formatted .= '. <em>' . $journal . '</em>';
					if ( $volume ) $formatted .= ' ' . $volume;
					if ( $issue ) $formatted .= ', no. ' . $issue;
					if ( $year ) $formatted .= ' (' . $year . ')';
					if ( $pages ) $formatted .= ': ' . $pages;
				}
				break;

			default:
				$formatted = $title;
				if ( $authors ) $formatted = $authors . '. ' . $formatted;
				if ( $year ) $formatted .= ' (' . $year . ')';
				if ( $journal ) $formatted .= '. ' . $journal;
				break;
		}

		return $formatted;
	}
}

// Use the helper function if the main method doesn't exist
if ( ! method_exists( $this, 'format_bibliography_entry' ) ) {
	// Create a simple function reference for this partial
	$format_entry_func = 'abt_format_bibliography_entry';
} else {
	$format_entry_func = array( $this, 'format_bibliography_entry' );
}
?>