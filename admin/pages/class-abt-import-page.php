<?php
/**
 * Import/Export page functionality.
 *
 * @link       https://github.com/academic-bloggers-toolkit
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/pages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import/Export page functionality.
 *
 * Handles the plugin import/export interface.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/pages
 * @author     Academic Bloggers Toolkit Team
 */
class ABT_Import_Page {

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
	 * Display the import/export page.
	 *
	 * @since    1.0.0
	 */
	public function display() {
		?>
		<div class="wrap">
			<h1><?php _e( 'Import/Export References', 'academic-bloggers-toolkit' ); ?></h1>
			
			<div class="abt-import-export-grid">
				<div class="abt-import-section">
					<div class="abt-section-card">
						<h2><?php _e( 'Import References', 'academic-bloggers-toolkit' ); ?></h2>
						<p><?php _e( 'Import references from various file formats to build your academic library.', 'academic-bloggers-toolkit' ); ?></p>
						
						<form method="post" enctype="multipart/form-data" class="abt-import-form">
							<?php wp_nonce_field( 'abt_import_references', 'abt_import_nonce' ); ?>
							
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row">
											<label for="abt_import_file"><?php _e( 'Select File', 'academic-bloggers-toolkit' ); ?></label>
										</th>
										<td>
											<input type="file" name="abt_import_file" id="abt_import_file" accept=".ris,.bib,.bibtex,.csv,.json" />
											<p class="description">
												<?php _e( 'Supported formats: RIS (.ris), BibTeX (.bib), CSV (.csv), JSON (.json)', 'academic-bloggers-toolkit' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="abt_import_format"><?php _e( 'File Format', 'academic-bloggers-toolkit' ); ?></label>
										</th>
										<td>
											<select name="abt_import_format" id="abt_import_format">
												<option value="auto"><?php _e( 'Auto-detect', 'academic-bloggers-toolkit' ); ?></option>
												<option value="ris"><?php _e( 'RIS Format', 'academic-bloggers-toolkit' ); ?></option>
												<option value="bibtex"><?php _e( 'BibTeX Format', 'academic-bloggers-toolkit' ); ?></option>
												<option value="csv"><?php _e( 'CSV Format', 'academic-bloggers-toolkit' ); ?></option>
												<option value="json"><?php _e( 'JSON Format', 'academic-bloggers-toolkit' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'Import Options', 'academic-bloggers-toolkit' ); ?></th>
										<td>
											<fieldset>
												<legend class="screen-reader-text"><?php _e( 'Import Options', 'academic-bloggers-toolkit' ); ?></legend>
												<label for="abt_check_duplicates">
													<input type="checkbox" name="abt_check_duplicates" id="abt_check_duplicates" value="1" checked />
													<?php _e( 'Check for duplicate references', 'academic-bloggers-toolkit' ); ?>
												</label>
												<br />
												<label for="abt_update_existing">
													<input type="checkbox" name="abt_update_existing" id="abt_update_existing" value="1" />
													<?php _e( 'Update existing references if duplicates found', 'academic-bloggers-toolkit' ); ?>
												</label>
											</fieldset>
										</td>
									</tr>
								</tbody>
							</table>
							
							<p class="submit">
								<input type="submit" name="abt_import_submit" class="button button-primary" value="<?php _e( 'Import References', 'academic-bloggers-toolkit' ); ?>" />
							</p>
						</form>
					</div>
				</div>
				
				<div class="abt-export-section">
					<div class="abt-section-card">
						<h2><?php _e( 'Export References', 'academic-bloggers-toolkit' ); ?></h2>
						<p><?php _e( 'Export your reference library to various formats for backup or sharing.', 'academic-bloggers-toolkit' ); ?></p>
						
						<form method="post" class="abt-export-form">
							<?php wp_nonce_field( 'abt_export_references', 'abt_export_nonce' ); ?>
							
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row">
											<label for="abt_export_format"><?php _e( 'Export Format', 'academic-bloggers-toolkit' ); ?></label>
										</th>
										<td>
											<select name="abt_export_format" id="abt_export_format">
												<option value="ris"><?php _e( 'RIS Format', 'academic-bloggers-toolkit' ); ?></option>
												<option value="bibtex"><?php _e( 'BibTeX Format', 'academic-bloggers-toolkit' ); ?></option>
												<option value="csv"><?php _e( 'CSV Format', 'academic-bloggers-toolkit' ); ?></option>
												<option value="json"><?php _e( 'JSON Format', 'academic-bloggers-toolkit' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="abt_export_filter"><?php _e( 'Filter References', 'academic-bloggers-toolkit' ); ?></label>
										</th>
										<td>
											<select name="abt_export_filter" id="abt_export_filter">
												<option value="all"><?php _e( 'All References', 'academic-bloggers-toolkit' ); ?></option>
												<option value="used"><?php _e( 'Only Used References', 'academic-bloggers-toolkit' ); ?></option>
												<option value="unused"><?php _e( 'Only Unused References', 'academic-bloggers-toolkit' ); ?></option>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
							
							<p class="submit">
								<input type="submit" name="abt_export_submit" class="button button-primary" value="<?php _e( 'Export References', 'academic-bloggers-toolkit' ); ?>" />
							</p>
						</form>
					</div>
				</div>
			</div>
			
			<div class="abt-format-info">
				<h2><?php _e( 'Supported Formats', 'academic-bloggers-toolkit' ); ?></h2>
				
				<div class="abt-format-grid">
					<div class="abt-format-card">
						<h3>RIS</h3>
						<p><?php _e( 'Research Information Systems format. Widely supported by reference managers like EndNote, Zotero, and Mendeley.', 'academic-bloggers-toolkit' ); ?></p>
					</div>
					
					<div class="abt-format-card">
						<h3>BibTeX</h3>
						<p><?php _e( 'LaTeX bibliography format. Popular in academic and scientific writing, especially in STEM fields.', 'academic-bloggers-toolkit' ); ?></p>
					</div>
					
					<div class="abt-format-card">
						<h3>CSV</h3>
						<p><?php _e( 'Comma-separated values format. Easy to edit in spreadsheet applications like Excel or Google Sheets.', 'academic-bloggers-toolkit' ); ?></p>
					</div>
					
					<div class="abt-format-card">
						<h3>JSON</h3>
						<p><?php _e( 'JavaScript Object Notation format. Modern, structured format that preserves all reference data.', 'academic-bloggers-toolkit' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		
		<style>
		.abt-import-export-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 30px;
			margin: 20px 0;
		}
		
		.abt-section-card {
			background: #fff;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			padding: 20px;
		}
		
		.abt-section-card h2 {
			margin-top: 0;
			color: #23282d;
		}
		
		.abt-format-info {
			margin-top: 40px;
		}
		
		.abt-format-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 20px;
			margin-top: 20px;
		}
		
		.abt-format-card {
			background: #f9f9f9;
			border: 1px solid #ddd;
			border-radius: 4px;
			padding: 15px;
		}
		
		.abt-format-card h3 {
			margin-top: 0;
			color: #0073aa;
		}
		
		@media (max-width: 768px) {
			.abt-import-export-grid {
				grid-template-columns: 1fr;
			}
		}
		</style>
		<?php
	}
}