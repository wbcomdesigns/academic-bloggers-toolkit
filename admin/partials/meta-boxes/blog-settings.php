<?php
/**
 * Provide a admin area view for the blog settings meta box.
 *
 * This file is used to markup the academic blog post settings.
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
wp_nonce_field( 'abt_save_settings', 'abt_settings_nonce' );

// Get current settings
$citation_style = get_post_meta( $post_id, '_abt_citation_style', true );
$enable_footnotes = get_post_meta( $post_id, '_abt_enable_footnotes', true );
$auto_bibliography = get_post_meta( $post_id, '_abt_auto_bibliography', true );
$footnote_style = get_post_meta( $post_id, '_abt_footnote_style', true );
$bibliography_title = get_post_meta( $post_id, '_abt_bibliography_title', true );
$enable_analytics = get_post_meta( $post_id, '_abt_enable_analytics', true );

// Set defaults from global settings
$global_settings = get_option( 'abt_settings', array() );
if ( empty( $citation_style ) ) {
	$citation_style = $global_settings['citation_style'] ?? 'apa';
}
if ( empty( $footnote_style ) ) {
	$footnote_style = $global_settings['footnote_style'] ?? 'numeric';
}
if ( $auto_bibliography === '' ) {
	$auto_bibliography = $global_settings['auto_bibliography'] ?? true;
}
if ( $enable_footnotes === '' ) {
	$enable_footnotes = $global_settings['enable_footnotes'] ?? true;
}

?>

<div class="abt-academic-settings">
	<table class="form-table">
		<tbody>
			<!-- Citation Style -->
			<tr>
				<th scope="row">
					<label for="abt_citation_style"><?php _e( 'Citation Style', 'academic-bloggers-toolkit' ); ?></label>
				</th>
				<td>
					<select id="abt_citation_style" name="abt_citation_style" class="abt-setting-field">
						<option value="apa" <?php selected( $citation_style, 'apa' ); ?>><?php _e( 'APA (American Psychological Association)', 'academic-bloggers-toolkit' ); ?></option>
						<option value="mla" <?php selected( $citation_style, 'mla' ); ?>><?php _e( 'MLA (Modern Language Association)', 'academic-bloggers-toolkit' ); ?></option>
						<option value="chicago" <?php selected( $citation_style, 'chicago' ); ?>><?php _e( 'Chicago Manual of Style', 'academic-bloggers-toolkit' ); ?></option>
						<option value="harvard" <?php selected( $citation_style, 'harvard' ); ?>><?php _e( 'Harvard Referencing', 'academic-bloggers-toolkit' ); ?></option>
						<option value="ieee" <?php selected( $citation_style, 'ieee' ); ?>><?php _e( 'IEEE (Institute of Electrical and Electronics Engineers)', 'academic-bloggers-toolkit' ); ?></option>
					</select>
					<p class="description">
						<?php _e( 'Choose the citation format for this post. This affects how citations and bibliography appear.', 'academic-bloggers-toolkit' ); ?>
					</p>
				</td>
			</tr>

			<!-- Academic Features -->
			<tr>
				<th scope="row"><?php _e( 'Academic Features', 'academic-bloggers-toolkit' ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php _e( 'Academic Features', 'academic-bloggers-toolkit' ); ?></legend>
						
						<label for="abt_enable_footnotes">
							<input type="checkbox" 
								   id="abt_enable_footnotes" 
								   name="abt_enable_footnotes" 
								   value="1" 
								   <?php checked( $enable_footnotes ); ?>
								   class="abt-setting-field" />
							<?php _e( 'Enable footnotes for this post', 'academic-bloggers-toolkit' ); ?>
						</label>
						<p class="description">
							<?php _e( 'Allow adding footnotes with automatic numbering and formatting.', 'academic-bloggers-toolkit' ); ?>
						</p>
						
						<br />
						
						<label for="abt_auto_bibliography">
							<input type="checkbox" 
								   id="abt_auto_bibliography" 
								   name="abt_auto_bibliography" 
								   value="1" 
								   <?php checked( $auto_bibliography ); ?>
								   class="abt-setting-field" />
							<?php _e( 'Auto-generate bibliography', 'academic-bloggers-toolkit' ); ?>
						</label>
						<p class="description">
							<?php _e( 'Automatically display a formatted bibliography at the end of this post.', 'academic-bloggers-toolkit' ); ?>
						</p>
						
						<br />
						
						<label for="abt_enable_analytics">
							<input type="checkbox" 
								   id="abt_enable_analytics" 
								   name="abt_enable_analytics" 
								   value="1" 
								   <?php checked( $enable_analytics ); ?>
								   class="abt-setting-field" />
							<?php _e( 'Enable reading analytics', 'academic-bloggers-toolkit' ); ?>
						</label>
						<p class="description">
							<?php _e( 'Track reading time, citation clicks, and other engagement metrics for this post.', 'academic-bloggers-toolkit' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>

			<!-- Footnote Style -->
			<tr class="abt-footnote-settings" style="<?php echo $enable_footnotes ? '' : 'opacity: 0.5;'; ?>">
				<th scope="row">
					<label for="abt_footnote_style"><?php _e( 'Footnote Style', 'academic-bloggers-toolkit' ); ?></label>
				</th>
				<td>
					<select id="abt_footnote_style" 
							name="abt_footnote_style" 
							class="abt-setting-field"
							<?php disabled( ! $enable_footnotes ); ?>>
						<option value="numeric" <?php selected( $footnote_style, 'numeric' ); ?>><?php _e( 'Numeric (1, 2, 3, ...)', 'academic-bloggers-toolkit' ); ?></option>
						<option value="roman" <?php selected( $footnote_style, 'roman' ); ?>><?php _e( 'Roman (i, ii, iii, ...)', 'academic-bloggers-toolkit' ); ?></option>
						<option value="alpha" <?php selected( $footnote_style, 'alpha' ); ?>><?php _e( 'Alphabetic (a, b, c, ...)', 'academic-bloggers-toolkit' ); ?></option>
						<option value="symbols" <?php selected( $footnote_style, 'symbols' ); ?>><?php _e( 'Symbols (*, †, ‡, ...)', 'academic-bloggers-toolkit' ); ?></option>
					</select>
					<p class="description">
						<?php _e( 'Choose how footnotes are numbered in this post.', 'academic-bloggers-toolkit' ); ?>
					</p>
				</td>
			</tr>

			<!-- Bibliography Settings -->
			<tr class="abt-bibliography-settings" style="<?php echo $auto_bibliography ? '' : 'opacity: 0.5;'; ?>">
				<th scope="row">
					<label for="abt_bibliography_title"><?php _e( 'Bibliography Title', 'academic-bloggers-toolkit' ); ?></label>
				</th>
				<td>
					<input type="text" 
						   id="abt_bibliography_title" 
						   name="abt_bibliography_title" 
						   value="<?php echo esc_attr( $bibliography_title ); ?>" 
						   class="regular-text abt-setting-field"
						   placeholder="<?php echo esc_attr( $global_settings['bibliography_title'] ?? __( 'References', 'academic-bloggers-toolkit' ) ); ?>"
						   <?php disabled( ! $auto_bibliography ); ?> />
					<p class="description">
						<?php _e( 'Custom title for the bibliography section. Leave blank to use the default title.', 'academic-bloggers-toolkit' ); ?>
					</p>
				</td>
			</tr>

			<!-- Subject Areas -->
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
						$selected_terms = wp_get_post_terms( $post_id, 'abt_subject', array( 'fields' => 'ids' ) );
						echo '<div class="abt-subject-checklist" style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 8px;">';
						foreach ( $terms as $term ) {
							$checked = in_array( $term->term_id, $selected_terms ) ? 'checked' : '';
							echo '<label style="display: block; margin-bottom: 5px;">';
							echo '<input type="checkbox" name="abt_subject_areas[]" value="' . esc_attr( $term->term_id ) . '" ' . $checked . ' class="abt-subject-checkbox" /> ';
							echo esc_html( $term->name );
							echo ' <small style="color: #666;">(' . $term->count . ')</small>';
							echo '</label>';
						}
						echo '</div>';
						?>
						<p class="description">
							<?php _e( 'Select the academic subject areas that best describe this post.', 'academic-bloggers-toolkit' ); ?>
						</p>
						<?php
					} else {
						?>
						<p><?php _e( 'No subject areas available.', 'academic-bloggers-toolkit' ); ?></p>
						<p>
							<a href="<?php echo admin_url( 'edit-tags.php?taxonomy=abt_subject&post_type=abt_blog' ); ?>" 
							   target="_blank" 
							   class="button button-small">
								<span class="dashicons dashicons-plus"></span>
								<?php _e( 'Create Subject Areas', 'academic-bloggers-toolkit' ); ?>
							</a>
						</p>
						<?php
					}
					?>
				</td>
			</tr>

			<!-- Advanced Settings -->
			<tr>
				<th scope="row"><?php _e( 'Advanced Options', 'academic-bloggers-toolkit' ); ?></th>
				<td>
					<details class="abt-advanced-settings">
						<summary style="cursor: pointer; font-weight: 600; margin-bottom: 10px;">
							<?php _e( 'Show advanced settings', 'academic-bloggers-toolkit' ); ?>
						</summary>
						
						<table class="form-table" style="margin: 0;">
							<tr>
								<th scope="row" style="padding-left: 0;">
									<label for="abt_custom_css_class"><?php _e( 'Custom CSS Class', 'academic-bloggers-toolkit' ); ?></label>
								</th>
								<td style="padding-left: 0;">
									<input type="text" 
										   id="abt_custom_css_class" 
										   name="abt_custom_css_class" 
										   value="<?php echo esc_attr( get_post_meta( $post_id, '_abt_custom_css_class', true ) ); ?>" 
										   class="regular-text" 
										   placeholder="academic-post special-formatting" />
									<p class="description">
										<?php _e( 'Add custom CSS classes to this post for specialized styling.', 'academic-bloggers-toolkit' ); ?>
									</p>
								</td>
							</tr>
							
							<tr>
								<th scope="row" style="padding-left: 0;">
									<label for="abt_reading_time"><?php _e( 'Estimated Reading Time', 'academic-bloggers-toolkit' ); ?></label>
								</th>
								<td style="padding-left: 0;">
									<input type="number" 
										   id="abt_reading_time" 
										   name="abt_reading_time" 
										   value="<?php echo esc_attr( get_post_meta( $post_id, '_abt_reading_time', true ) ); ?>" 
										   class="small-text" 
										   min="1" 
										   max="999" 
										   placeholder="Auto" />
									<span><?php _e( 'minutes', 'academic-bloggers-toolkit' ); ?></span>
									<p class="description">
										<?php _e( 'Override automatic reading time calculation. Leave blank for automatic calculation.', 'academic-bloggers-toolkit' ); ?>
									</p>
								</td>
							</tr>
							
							<tr>
								<th scope="row" style="padding-left: 0;">
									<label for="abt_academic_level"><?php _e( 'Academic Level', 'academic-bloggers-toolkit' ); ?></label>
								</th>
								<td style="padding-left: 0;">
									<select id="abt_academic_level" name="abt_academic_level">
										<?php
										$academic_level = get_post_meta( $post_id, '_abt_academic_level', true );
										$levels = array(
											'' => __( 'Not specified', 'academic-bloggers-toolkit' ),
											'undergraduate' => __( 'Undergraduate', 'academic-bloggers-toolkit' ),
											'graduate' => __( 'Graduate', 'academic-bloggers-toolkit' ),
											'professional' => __( 'Professional', 'academic-bloggers-toolkit' ),
											'general' => __( 'General Audience', 'academic-bloggers-toolkit' ),
										);
										foreach ( $levels as $value => $label ) {
											echo '<option value="' . esc_attr( $value ) . '" ' . selected( $academic_level, $value, false ) . '>' . esc_html( $label ) . '</option>';
										}
										?>
									</select>
									<p class="description">
										<?php _e( 'Indicate the intended academic level for this post.', 'academic-bloggers-toolkit' ); ?>
									</p>
								</td>
							</tr>
						</table>
					</details>
				</td>
			</tr>
		</tbody>
	</table>
	
	<!-- Quick Actions -->
	<div class="abt-settings-actions">
		<h4><?php _e( 'Quick Actions', 'academic-bloggers-toolkit' ); ?></h4>
		<p>
			<button type="button" class="button" id="abt-reset-to-defaults">
				<span class="dashicons dashicons-undo"></span>
				<?php _e( 'Reset to Global Defaults', 'academic-bloggers-toolkit' ); ?>
			</button>
			<button type="button" class="button" id="abt-save-as-template">
				<span class="dashicons dashicons-admin-page"></span>
				<?php _e( 'Save as Template', 'academic-bloggers-toolkit' ); ?>
			</button>
			<a href="<?php echo admin_url( 'admin.php?page=abt-settings' ); ?>" 
			   class="button button-secondary" 
			   target="_blank">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php _e( 'Global Settings', 'academic-bloggers-toolkit' ); ?>
			</a>
		</p>
	</div>
	
	<!-- Settings Summary -->
	<div class="abt-settings-summary">
		<h4><?php _e( 'Current Configuration Summary', 'academic-bloggers-toolkit' ); ?></h4>
		<div class="abt-summary-grid">
			<div class="abt-summary-item">
				<strong><?php _e( 'Citation Style:', 'academic-bloggers-toolkit' ); ?></strong>
				<span id="abt-summary-citation-style"><?php echo esc_html( strtoupper( $citation_style ) ); ?></span>
			</div>
			<div class="abt-summary-item">
				<strong><?php _e( 'Footnotes:', 'academic-bloggers-toolkit' ); ?></strong>
				<span id="abt-summary-footnotes"><?php echo $enable_footnotes ? __( 'Enabled', 'academic-bloggers-toolkit' ) : __( 'Disabled', 'academic-bloggers-toolkit' ); ?></span>
			</div>
			<div class="abt-summary-item">
				<strong><?php _e( 'Auto Bibliography:', 'academic-bloggers-toolkit' ); ?></strong>
				<span id="abt-summary-bibliography"><?php echo $auto_bibliography ? __( 'Enabled', 'academic-bloggers-toolkit' ) : __( 'Disabled', 'academic-bloggers-toolkit' ); ?></span>
			</div>
			<div class="abt-summary-item">
				<strong><?php _e( 'Subject Areas:', 'academic-bloggers-toolkit' ); ?></strong>
				<span id="abt-summary-subjects">
					<?php
					$subject_count = count( wp_get_post_terms( $post_id, 'abt_subject' ) );
					printf( _n( '%d selected', '%d selected', $subject_count, 'academic-bloggers-toolkit' ), $subject_count );
					?>
				</span>
			</div>
		</div>
	</div>
</div>

<style>
.abt-academic-settings {
	padding: 10px 0;
}

.abt-academic-settings .form-table th {
	width: 150px;
	padding: 15px 10px 15px 0;
	vertical-align: top;
}

.abt-academic-settings .form-table td {
	padding: 15px 10px;
}

.abt-academic-settings .description {
	margin-top: 5px;
	font-style: italic;
	color: #666;
	font-size: 13px;
}

.abt-subject-checklist {
	border-radius: 3px;
}

.abt-subject-checklist label {
	transition: background-color 0.2s ease;
}

.abt-subject-checklist label:hover {
	background-color: #f5f5f5;
	padding: 2px 5px;
	margin: 0 -5px;
	border-radius: 2px;
}

.abt-advanced-settings {
	margin-top: 10px;
	padding: 15px;
	background: #f9f9f9;
	border: 1px solid #ddd;
	border-radius: 3px;
}

.abt-advanced-settings[open] {
	padding-bottom: 5px;
}

.abt-advanced-settings summary {
	margin-bottom: 15px;
	color: #0073aa;
}

.abt-advanced-settings summary:hover {
	color: #005a87;
}

.abt-settings-actions {
	margin-top: 20px;
	padding-top: 15px;
	border-top: 1px solid #ddd;
}

.abt-settings-actions h4 {
	margin: 0 0 10px 0;
	color: #23282d;
}

.abt-settings-actions .button {
	margin-right: 10px;
	margin-bottom: 5px;
}

.abt-settings-summary {
	margin-top: 20px;
	padding: 15px;
	background: #f0f6fc;
	border: 1px solid #c3d9ff;
	border-radius: 3px;
}

.abt-settings-summary h4 {
	margin: 0 0 15px 0;
	color: #0d47a1;
}

.abt-summary-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 10px;
}

.abt-summary-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 8px 10px;
	background: #fff;
	border-radius: 3px;
	border: 1px solid #e0e0e0;
}

.abt-summary-item strong {
	color: #23282d;
}

.abt-summary-item span {
	color: #0073aa;
	font-weight: 600;
}

/* Conditional visibility styles */
.abt-footnote-settings.disabled,
.abt-bibliography-settings.disabled {
	opacity: 0.5;
	pointer-events: none;
}

/* Responsive design */
@media (max-width: 768px) {
	.abt-academic-settings .form-table th,
	.abt-academic-settings .form-table td {
		display: block;
		width: 100%;
		padding: 10px 0;
	}
	
	.abt-summary-grid {
		grid-template-columns: 1fr;
	}
	
	.abt-settings-actions .button {
		display: block;
		width: 100%;
		margin: 5px 0;
		text-align: center;
	}
}

/* Animation for settings changes */
.abt-setting-changed {
	background-color: #fff3cd !important;
	transition: background-color 0.3s ease;
}

.abt-setting-saved {
	background-color: #d4edda !important;
	transition: background-color 0.3s ease;
}
</style>

<script>
jQuery(document).ready(function($) {
	
	// Real-time setting updates
	$('.abt-setting-field').on('change', function() {
		var $field = $(this);
		var fieldName = $field.attr('name');
		var fieldValue = $field.is(':checkbox') ? ($field.is(':checked') ? 1 : 0) : $field.val();
		
		// Visual feedback
		$field.addClass('abt-setting-changed');
		setTimeout(function() {
			$field.removeClass('abt-setting-changed').addClass('abt-setting-saved');
			setTimeout(function() {
				$field.removeClass('abt-setting-saved');
			}, 1000);
		}, 500);
		
		// Update dependent fields
		updateDependentFields();
		
		// Update summary
		updateSettingsSummary();
		
		// Auto-save (optional - can be enabled)
		// autoSaveSetting(fieldName, fieldValue);
	});
	
	// Update dependent field states
	function updateDependentFields() {
		var footnotesEnabled = $('#abt_enable_footnotes').is(':checked');
		var bibliographyEnabled = $('#abt_auto_bibliography').is(':checked');
		
		// Footnote settings
		if (footnotesEnabled) {
			$('.abt-footnote-settings').css('opacity', '1');
			$('#abt_footnote_style').prop('disabled', false);
		} else {
			$('.abt-footnote-settings').css('opacity', '0.5');
			$('#abt_footnote_style').prop('disabled', true);
		}
		
		// Bibliography settings
		if (bibliographyEnabled) {
			$('.abt-bibliography-settings').css('opacity', '1');
			$('#abt_bibliography_title').prop('disabled', false);
		} else {
			$('.abt-bibliography-settings').css('opacity', '0.5');
			$('#abt_bibliography_title').prop('disabled', true);
		}
	}
	
	// Update settings summary
	function updateSettingsSummary() {
		var citationStyle = $('#abt_citation_style').val().toUpperCase();
		var footnotesEnabled = $('#abt_enable_footnotes').is(':checked');
		var bibliographyEnabled = $('#abt_auto_bibliography').is(':checked');
		var subjectCount = $('.abt-subject-checkbox:checked').length;
		
		$('#abt-summary-citation-style').text(citationStyle);
		$('#abt-summary-footnotes').text(footnotesEnabled ? 'Enabled' : 'Disabled');
		$('#abt-summary-bibliography').text(bibliographyEnabled ? 'Enabled' : 'Disabled');
		$('#abt-summary-subjects').text(subjectCount + ' selected');
	}
	
	// Subject area change handler
	$('.abt-subject-checkbox').on('change', function() {
		updateSettingsSummary();
	});
	
	// Reset to defaults
	$('#abt-reset-to-defaults').on('click', function() {
		if (confirm('Are you sure you want to reset all settings to global defaults? This will override any custom settings for this post.')) {
			// Reset to default values (you would get these from the global settings)
			$('#abt_citation_style').val('apa');
			$('#abt_enable_footnotes').prop('checked', true);
			$('#abt_auto_bibliography').prop('checked', true);
			$('#abt_footnote_style').val('numeric');
			$('#abt_bibliography_title').val('');
			$('#abt_enable_analytics').prop('checked', false);
			
			// Clear subject areas
			$('.abt-subject-checkbox').prop('checked', false);
			
			// Clear advanced settings
			$('#abt_custom_css_class').val('');
			$('#abt_reading_time').val('');
			$('#abt_academic_level').val('');
			
			// Update dependent fields and summary
			updateDependentFields();
			updateSettingsSummary();
			
			// Show success message
			$('<div class="notice notice-success is-dismissible"><p>Settings reset to defaults.</p></div>')
				.insertAfter('.abt-academic-settings').delay(3000).fadeOut();
		}
	});
	
	// Save as template
	$('#abt-save-as-template').on('click', function() {
		var templateName = prompt('Enter a name for this settings template:');
		if (templateName) {
			// This would save the current settings as a template
			// Implementation would involve AJAX call to save template
			alert('Template "' + templateName + '" saved! (Feature coming in next update)');
		}
	});
	
	// Auto-save function (commented out - can be enabled if needed)
	function autoSaveSetting(fieldName, fieldValue) {
		$.post(ajaxurl, {
			action: 'abt_auto_save_setting',
			post_id: $('#post_ID').val(),
			field_name: fieldName,
			field_value: fieldValue,
			nonce: $('#abt_settings_nonce').val()
		});
	}
	
	// Initialize on page load
	updateDependentFields();
	updateSettingsSummary();
	
	// Citation style change handler - trigger bibliography refresh if available
	$('#abt_citation_style').on('change', function() {
		var newStyle = $(this).val();
		
		// Update bibliography preview if it exists
		if ($('#abt-refresh-bibliography').length) {
			$('#abt-refresh-bibliography').click();
		}
		
		// Show a notice about the change
		$('<div class="notice notice-info is-dismissible" style="margin: 10px 0;"><p>Citation style changed to ' + newStyle.toUpperCase() + '. Bibliography and citation previews will be updated.</p></div>')
			.insertAfter('#abt_citation_style').delay(4000).fadeOut();
	});
	
	// Footnote style change - update footnote previews if available
	$('#abt_footnote_style').on('change', function() {
		var newStyle = $(this).val();
		
		// Update footnote numbering if footnotes exist
		if ($('.abt-footnote-number').length) {
			// This would update footnote numbering in real-time
			// Implementation depends on footnote manager being loaded
		}
	});
	
	// Reading time calculation
	$('#abt_reading_time').on('focus', function() {
		if (!$(this).val()) {
			// Calculate estimated reading time based on content
			var content = '';
			if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
				content = tinymce.activeEditor.getContent();
			} else {
				content = $('#content').val();
			}
			
			if (content) {
				// Strip HTML and count words
				var wordCount = content.replace(/<[^>]*>/g, '').split(/\s+/).length;
				var readingTime = Math.ceil(wordCount / 200); // Assuming 200 words per minute
				
				$(this).attr('placeholder', readingTime + ' min (auto-calculated)');
			}
		}
	});
});
</script>