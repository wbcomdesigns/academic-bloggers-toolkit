<?php
/**
 * Enhanced Admin Class with Fixed Menu and Settings
 *
 * @link       https://github.com/wbcomdesigns
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enhanced admin functionality with proper menu structure and permissions.
 */
class ABT_Admin {

	/**
	 * The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		
		// Hook into WordPress
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
		
		// Add custom columns
		add_filter( 'manage_abt_blog_posts_columns', array( $this, 'add_blog_columns' ) );
		add_action( 'manage_abt_blog_posts_custom_column', array( $this, 'display_blog_columns' ), 10, 2 );
		add_filter( 'manage_abt_reference_posts_columns', array( $this, 'add_reference_columns' ) );
		add_action( 'manage_abt_reference_posts_custom_column', array( $this, 'display_reference_columns' ), 10, 2 );
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 
			$this->plugin_name, 
			ABT_PLUGIN_URL . 'admin/css/admin-main.css', 
			array(), 
			$this->version, 
			'all' 
		);

		// Add custom admin styles
		$custom_css = "
		.abt-admin-page {
			max-width: 1200px;
		}
		.abt-settings-section {
			background: #fff;
			padding: 20px;
			margin-bottom: 20px;
			border: 1px solid #ccd0d4;
			box-shadow: 0 1px 1px rgba(0,0,0,.04);
		}
		.abt-settings-section h3 {
			margin-top: 0;
			border-bottom: 1px solid #eee;
			padding-bottom: 10px;
		}
		.abt-form-table th {
			width: 200px;
			padding: 15px 10px 15px 0;
		}
		.abt-form-table td {
			padding: 15px 10px;
		}
		.abt-notice {
			background: #fff;
			border-left: 4px solid #00a0d2;
			padding: 12px;
			margin: 15px 0;
		}
		.abt-notice.error {
			border-left-color: #dc3232;
		}
		.abt-notice.success {
			border-left-color: #46b450;
		}
		.abt-stats-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 20px;
			margin: 20px 0;
		}
		.abt-stat-card {
			background: #fff;
			padding: 20px;
			border: 1px solid #ccd0d4;
			text-align: center;
		}
		.abt-stat-number {
			font-size: 2em;
			font-weight: bold;
			color: #0073aa;
		}
		";
		wp_add_inline_style( $this->plugin_name, $custom_css );
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 
			$this->plugin_name, 
			ABT_PLUGIN_URL . 'admin/js/admin-main.js', 
			array( 'jquery' ), 
			$this->version, 
			false 
		);

		// Localize script for AJAX
		wp_localize_script(
			$this->plugin_name,
			'abt_admin_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'abt_admin_nonce' ),
				'strings'  => array(
					'confirm_delete' => __( 'Are you sure you want to delete this item?', 'academic-bloggers-toolkit' ),
					'saving' => __( 'Saving...', 'academic-bloggers-toolkit' ),
					'saved' => __( 'Saved!', 'academic-bloggers-toolkit' ),
					'error' => __( 'An error occurred. Please try again.', 'academic-bloggers-toolkit' ),
				),
			)
		);
	}

	/**
	 * Add admin menu pages with proper capabilities.
	 */
	public function add_admin_menu() {
		// Main menu page - Dashboard
		$dashboard_hook = add_menu_page(
			__( 'Academic Blogger\'s Toolkit', 'academic-bloggers-toolkit' ),
			__( 'ABT', 'academic-bloggers-toolkit' ),
			'edit_posts', // Lower capability requirement
			'academic-bloggers-toolkit',
			array( $this, 'display_dashboard_page' ),
			'dashicons-book-alt',
			30
		);

		// References submenu
		$references_hook = add_submenu_page(
			'academic-bloggers-toolkit',
			__( 'References', 'academic-bloggers-toolkit' ),
			__( 'References', 'academic-bloggers-toolkit' ),
			'edit_posts',
			'abt-references',
			array( $this, 'display_references_page' )
		);

		// Statistics submenu
		$stats_hook = add_submenu_page(
			'academic-bloggers-toolkit',
			__( 'Statistics', 'academic-bloggers-toolkit' ),
			__( 'Statistics', 'academic-bloggers-toolkit' ),
			'edit_posts',
			'abt-statistics',
			array( $this, 'display_statistics_page' )
		);

		// Settings submenu - FIXED URL
		$settings_hook = add_submenu_page(
			'academic-bloggers-toolkit',
			__( 'Settings', 'academic-bloggers-toolkit' ),
			__( 'Settings', 'academic-bloggers-toolkit' ),
			'manage_options', // Higher capability for settings
			'abt-settings',
			array( $this, 'display_settings_page' )
		);

		// Import/Export submenu
		$import_hook = add_submenu_page(
			'academic-bloggers-toolkit',
			__( 'Import/Export', 'academic-bloggers-toolkit' ),
			__( 'Import/Export', 'academic-bloggers-toolkit' ),
			'edit_posts',
			'abt-import-export',
			array( $this, 'display_import_export_page' )
		);

		// Help submenu
		add_submenu_page(
			'academic-bloggers-toolkit',
			__( 'Help & Documentation', 'academic-bloggers-toolkit' ),
			__( 'Help', 'academic-bloggers-toolkit' ),
			'edit_posts',
			'abt-help',
			array( $this, 'display_help_page' )
		);

		// Add contextual help
		add_action( "load-{$dashboard_hook}", array( $this, 'add_dashboard_help' ) );
		add_action( "load-{$settings_hook}", array( $this, 'add_settings_help' ) );
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		// Register settings group
		register_setting( 
			'abt_settings_group',
			'abt_settings',
			array( $this, 'sanitize_settings' )
		);

		// General Settings Section
		add_settings_section(
			'abt_general_settings',
			__( 'General Settings', 'academic-bloggers-toolkit' ),
			array( $this, 'general_settings_callback' ),
			'abt-settings'
		);

		// Citation Style Setting
		add_settings_field(
			'default_citation_style',
			__( 'Default Citation Style', 'academic-bloggers-toolkit' ),
			array( $this, 'citation_style_callback' ),
			'abt-settings',
			'abt_general_settings'
		);

		// Bibliography Settings
		add_settings_field(
			'auto_bibliography',
			__( 'Auto-generate Bibliography', 'academic-bloggers-toolkit' ),
			array( $this, 'auto_bibliography_callback' ),
			'abt-settings',
			'abt_general_settings'
		);

		// Footnote Settings
		add_settings_field(
			'footnote_style',
			__( 'Footnote Style', 'academic-bloggers-toolkit' ),
			array( $this, 'footnote_style_callback' ),
			'abt-settings',
			'abt_general_settings'
		);

		// Auto-cite Settings Section
		add_settings_section(
			'abt_autocite_settings',
			__( 'Auto-cite Settings', 'academic-bloggers-toolkit' ),
			array( $this, 'autocite_settings_callback' ),
			'abt-settings'
		);

		// DOI Fetcher
		add_settings_field(
			'enable_doi_fetcher',
			__( 'Enable DOI Fetcher', 'academic-bloggers-toolkit' ),
			array( $this, 'doi_fetcher_callback' ),
			'abt-settings',
			'abt_autocite_settings'
		);

		// URL Scraper
		add_settings_field(
			'enable_url_scraper',
			__( 'Enable URL Scraper', 'academic-bloggers-toolkit' ),
			array( $this, 'url_scraper_callback' ),
			'abt-settings',
			'abt_autocite_settings'
		);
	}

	/**
	 * Display the main dashboard page.
	 */
	public function display_dashboard_page() {
		$stats = $this->get_dashboard_stats();
		?>
		<div class="wrap abt-admin-page">
			<h1><?php _e( 'Academic Blogger\'s Toolkit Dashboard', 'academic-bloggers-toolkit' ); ?></h1>
			
			<?php $this->display_admin_notices(); ?>
			
			<div class="abt-stats-grid">
				<div class="abt-stat-card">
					<div class="abt-stat-number"><?php echo esc_html( $stats['total_posts'] ); ?></div>
					<div class="abt-stat-label"><?php _e( 'Academic Posts', 'academic-bloggers-toolkit' ); ?></div>
				</div>
				<div class="abt-stat-card">
					<div class="abt-stat-number"><?php echo esc_html( $stats['total_references'] ); ?></div>
					<div class="abt-stat-label"><?php _e( 'References', 'academic-bloggers-toolkit' ); ?></div>
				</div>
				<div class="abt-stat-card">
					<div class="abt-stat-number"><?php echo esc_html( $stats['total_citations'] ); ?></div>
					<div class="abt-stat-label"><?php _e( 'Citations', 'academic-bloggers-toolkit' ); ?></div>
				</div>
				<div class="abt-stat-card">
					<div class="abt-stat-number"><?php echo esc_html( $stats['avg_citations'] ); ?></div>
					<div class="abt-stat-label"><?php _e( 'Avg Citations/Post', 'academic-bloggers-toolkit' ); ?></div>
				</div>
			</div>

			<div class="abt-settings-section">
				<h3><?php _e( 'Quick Actions', 'academic-bloggers-toolkit' ); ?></h3>
				<p>
					<a href="<?php echo admin_url( 'post-new.php?post_type=abt_blog' ); ?>" class="button button-primary">
						<?php _e( 'Create Academic Post', 'academic-bloggers-toolkit' ); ?>
					</a>
					<a href="<?php echo admin_url( 'post-new.php?post_type=abt_reference' ); ?>" class="button">
						<?php _e( 'Add Reference', 'academic-bloggers-toolkit' ); ?>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=abt-import-export' ); ?>" class="button">
						<?php _e( 'Import References', 'academic-bloggers-toolkit' ); ?>
					</a>
				</p>
			</div>

			<div class="abt-settings-section">
				<h3><?php _e( 'Recent Activity', 'academic-bloggers-toolkit' ); ?></h3>
				<?php $this->display_recent_activity(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Display the references page.
	 */
	public function display_references_page() {
		$references_page = new ABT_References_Page( $this->plugin_name, $this->version );
		$references_page->display();
	}

	/**
	 * Display the statistics page.
	 */
	public function display_statistics_page() {
		$analytics = new ABT_Analytics();
		$stats = $analytics->get_overview_stats();
		?>
		<div class="wrap abt-admin-page">
			<h1><?php _e( 'Academic Statistics', 'academic-bloggers-toolkit' ); ?></h1>
			
			<div class="abt-settings-section">
				<h3><?php _e( 'Overview Statistics', 'academic-bloggers-toolkit' ); ?></h3>
				<div class="abt-stats-grid">
					<div class="abt-stat-card">
						<div class="abt-stat-number"><?php echo esc_html( $stats['total_posts'] ); ?></div>
						<div class="abt-stat-label"><?php _e( 'Total Academic Posts', 'academic-bloggers-toolkit' ); ?></div>
					</div>
					<div class="abt-stat-card">
						<div class="abt-stat-number"><?php echo esc_html( $stats['total_references'] ); ?></div>
						<div class="abt-stat-label"><?php _e( 'Total References', 'academic-bloggers-toolkit' ); ?></div>
					</div>
					<div class="abt-stat-card">
						<div class="abt-stat-number"><?php echo esc_html( $stats['total_citations'] ); ?></div>
						<div class="abt-stat-label"><?php _e( 'Total Citations', 'academic-bloggers-toolkit' ); ?></div>
					</div>
					<div class="abt-stat-card">
						<div class="abt-stat-number"><?php echo esc_html( number_format( $stats['average_citations_per_post'], 1 ) ); ?></div>
						<div class="abt-stat-label"><?php _e( 'Avg Citations per Post', 'academic-bloggers-toolkit' ); ?></div>
					</div>
				</div>
			</div>

			<?php if ( isset( $stats['most_cited_reference'] ) && $stats['most_cited_reference'] ): ?>
			<div class="abt-settings-section">
				<h3><?php _e( 'Most Cited Reference', 'academic-bloggers-toolkit' ); ?></h3>
				<p>
					<strong><?php echo esc_html( $stats['most_cited_reference']['title'] ); ?></strong><br>
					<?php printf( 
						_n( 'Cited %d time', 'Cited %d times', $stats['most_cited_reference']['citation_count'], 'academic-bloggers-toolkit' ),
						$stats['most_cited_reference']['citation_count']
					); ?>
				</p>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Display the settings page.
	 */
	public function display_settings_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'academic-bloggers-toolkit' ) );
		}

		// Handle form submission
		if ( isset( $_POST['submit'] ) && wp_verify_nonce( $_POST['abt_settings_nonce'], 'abt_save_settings' ) ) {
			$this->save_settings();
		}

		$settings = get_option( 'abt_settings', array() );
		?>
		<div class="wrap abt-admin-page">
			<h1><?php _e( 'Academic Blogger\'s Toolkit Settings', 'academic-bloggers-toolkit' ); ?></h1>
			
			<?php $this->display_admin_notices(); ?>
			
			<form method="post" action="">
				<?php 
				wp_nonce_field( 'abt_save_settings', 'abt_settings_nonce' );
				settings_fields( 'abt_settings_group' );
				?>

				<div class="abt-settings-section">
					<h3><?php _e( 'General Settings', 'academic-bloggers-toolkit' ); ?></h3>
					<table class="form-table abt-form-table">
						<tr>
							<th scope="row"><?php _e( 'Default Citation Style', 'academic-bloggers-toolkit' ); ?></th>
							<td>
								<select name="abt_settings[citation_style]">
									<option value="apa" <?php selected( $settings['citation_style'] ?? 'apa', 'apa' ); ?>>APA</option>
									<option value="mla" <?php selected( $settings['citation_style'] ?? 'apa', 'mla' ); ?>>MLA</option>
									<option value="chicago" <?php selected( $settings['citation_style'] ?? 'apa', 'chicago' ); ?>>Chicago</option>
									<option value="harvard" <?php selected( $settings['citation_style'] ?? 'apa', 'harvard' ); ?>>Harvard</option>
									<option value="ieee" <?php selected( $settings['citation_style'] ?? 'apa', 'ieee' ); ?>>IEEE</option>
								</select>
								<p class="description"><?php _e( 'Default citation style for new academic posts.', 'academic-bloggers-toolkit' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Auto-generate Bibliography', 'academic-bloggers-toolkit' ); ?></th>
							<td>
								<input type="checkbox" name="abt_settings[auto_bibliography]" value="1" <?php checked( $settings['auto_bibliography'] ?? true ); ?> />
								<label><?php _e( 'Automatically add bibliography to posts with citations', 'academic-bloggers-toolkit' ); ?></label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Bibliography Title', 'academic-bloggers-toolkit' ); ?></th>
							<td>
								<input type="text" name="abt_settings[bibliography_title]" value="<?php echo esc_attr( $settings['bibliography_title'] ?? __( 'References', 'academic-bloggers-toolkit' ) ); ?>" class="regular-text" />
								<p class="description"><?php _e( 'Default title for auto-generated bibliographies.', 'academic-bloggers-toolkit' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Footnote Style', 'academic-bloggers-toolkit' ); ?></th>
							<td>
								<select name="abt_settings[footnote_style]">
									<option value="numeric" <?php selected( $settings['footnote_style'] ?? 'numeric', 'numeric' ); ?>><?php _e( 'Numeric (1, 2, 3)', 'academic-bloggers-toolkit' ); ?></option>
									<option value="roman" <?php selected( $settings['footnote_style'] ?? 'numeric', 'roman' ); ?>><?php _e( 'Roman (i, ii, iii)', 'academic-bloggers-toolkit' ); ?></option>
									<option value="alpha" <?php selected( $settings['footnote_style'] ?? 'numeric', 'alpha' ); ?>><?php _e( 'Alphabetic (a, b, c)', 'academic-bloggers-toolkit' ); ?></option>
									<option value="symbols" <?php selected( $settings['footnote_style'] ?? 'numeric', 'symbols' ); ?>><?php _e( 'Symbols (*, †, ‡)', 'academic-bloggers-toolkit' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>

				<div class="abt-settings-section">
					<h3><?php _e( 'Auto-cite Features', 'academic-bloggers-toolkit' ); ?></h3>
					<table class="form-table abt-form-table">
						<tr>
							<th scope="row"><?php _e( 'DOI Fetcher', 'academic-bloggers-toolkit' ); ?></th>
							<td>
								<input type="checkbox" name="abt_settings[doi_fetcher_enabled]" value="1" <?php checked( $settings['doi_fetcher_enabled'] ?? true ); ?> />
								<label><?php _e( 'Enable automatic reference fetching from DOI', 'academic-bloggers-toolkit' ); ?></label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'PubMed Fetcher', 'academic-bloggers-toolkit' ); ?></th>
							<td>
								<input type="checkbox" name="abt_settings[pubmed_fetcher_enabled]" value="1" <?php checked( $settings['pubmed_fetcher_enabled'] ?? true ); ?> />
								<label><?php _e( 'Enable automatic reference fetching from PubMed ID', 'academic-bloggers-toolkit' ); ?></label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'URL Scraper', 'academic-bloggers-toolkit' ); ?></th>
							<td>
								<input type="checkbox" name="abt_settings[url_scraper_enabled]" value="1" <?php checked( $settings['url_scraper_enabled'] ?? true ); ?> />
								<label><?php _e( 'Enable automatic reference extraction from URLs', 'academic-bloggers-toolkit' ); ?></label>
							</td>
						</tr>
					</table>
				</div>

				<div class="abt-settings-section">
					<h3><?php _e( 'Performance & Privacy', 'academic-bloggers-toolkit' ); ?></h3>
					<table class="form-table abt-form-table">
						<tr>
							<th scope="row"><?php _e( 'Enable Analytics', 'academic-bloggers-toolkit' ); ?></th>
							<td>
								<input type="checkbox" name="abt_settings[enable_analytics]" value="1" <?php checked( $settings['enable_analytics'] ?? true ); ?> />
								<label><?php _e( 'Track usage statistics (anonymous, stored locally)', 'academic-bloggers-toolkit' ); ?></label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Cache Duration', 'academic-bloggers-toolkit' ); ?></th>
							<td>
								<select name="abt_settings[cache_duration]">
									<option value="3600" <?php selected( $settings['cache_duration'] ?? '3600', '3600' ); ?>><?php _e( '1 Hour', 'academic-bloggers-toolkit' ); ?></option>
									<option value="86400" <?php selected( $settings['cache_duration'] ?? '3600', '86400' ); ?>><?php _e( '1 Day', 'academic-bloggers-toolkit' ); ?></option>
									<option value="604800" <?php selected( $settings['cache_duration'] ?? '3600', '604800' ); ?>><?php _e( '1 Week', 'academic-bloggers-toolkit' ); ?></option>
								</select>
								<p class="description"><?php _e( 'How long to cache auto-cite results.', 'academic-bloggers-toolkit' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<?php submit_button( __( 'Save Settings', 'academic-bloggers-toolkit' ) ); ?>
			</form>

			<div class="abt-settings-section">
				<h3><?php _e( 'Tools', 'academic-bloggers-toolkit' ); ?></h3>
				<p>
					<button type="button" class="button" onclick="abtClearCache()"><?php _e( 'Clear Cache', 'academic-bloggers-toolkit' ); ?></button>
					<button type="button" class="button" onclick="abtExportSettings()"><?php _e( 'Export Settings', 'academic-bloggers-toolkit' ); ?></button>
					<button type="button" class="button" onclick="abtImportSettings()"><?php _e( 'Import Settings', 'academic-bloggers-toolkit' ); ?></button>
				</p>
			</div>
		</div>

		<script>
		function abtClearCache() {
			if (confirm('<?php _e( 'Are you sure you want to clear the cache?', 'academic-bloggers-toolkit' ); ?>')) {
				jQuery.post(ajaxurl, {
					action: 'abt_clear_cache',
					nonce: '<?php echo wp_create_nonce( 'abt_clear_cache' ); ?>'
				}, function(response) {
					if (response.success) {
						alert('<?php _e( 'Cache cleared successfully.', 'academic-bloggers-toolkit' ); ?>');
					} else {
						alert('<?php _e( 'Error clearing cache.', 'academic-bloggers-toolkit' ); ?>');
					}
				});
			}
		}

		function abtExportSettings() {
			window.location.href = '<?php echo admin_url( 'admin.php?page=abt-settings&action=export' ); ?>';
		}

		function abtImportSettings() {
			alert('<?php _e( 'Import functionality coming soon.', 'academic-bloggers-toolkit' ); ?>');
		}
		</script>
		<?php
	}

	/**
	 * Display the import/export page.
	 */
	public function display_import_export_page() {
		?>
		<div class="wrap abt-admin-page">
			<h1><?php _e( 'Import/Export References', 'academic-bloggers-toolkit' ); ?></h1>
			
			<div class="abt-settings-section">
				<h3><?php _e( 'Import References', 'academic-bloggers-toolkit' ); ?></h3>
				<p><?php _e( 'Import references from various formats including RIS, BibTeX, and CSV.', 'academic-bloggers-toolkit' ); ?></p>
				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'abt_import_references', 'abt_import_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th scope="row"><?php _e( 'Import Format', 'academic-bloggers-toolkit' ); ?></th>
							<td>
								<select name="import_format">
									<option value="ris"><?php _e( 'RIS Format', 'academic-bloggers-toolkit' ); ?></option>
									<option value="bibtex"><?php _e( 'BibTeX Format', 'academic-bloggers-toolkit' ); ?></option>
									<option value="csv"><?php _e( 'CSV Format', 'academic-bloggers-toolkit' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Import File', 'academic-bloggers-toolkit' ); ?></th>
							<td>
								<input type="file" name="import_file" accept=".ris,.bib,.csv" required />
								<p class="description"><?php _e( 'Maximum file size: 5MB', 'academic-bloggers-toolkit' ); ?></p>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Import References', 'academic-bloggers-toolkit' ), 'primary', 'import_submit' ); ?>
				</form>
			</div>

			<div class="abt-settings-section">
				<h3><?php _e( 'Export References', 'academic-bloggers-toolkit' ); ?></h3>
				<p><?php _e( 'Export your reference library in various formats.', 'academic-bloggers-toolkit' ); ?></p>
				<form method="post">
					<?php wp_nonce_field( 'abt_export_references', 'abt_export_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th scope="row"><?php _e( 'Export Format', 'academic-bloggers-toolkit' ); ?></th>
							<td>
								<select name="export_format">
									<option value="ris"><?php _e( 'RIS Format', 'academic-bloggers-toolkit' ); ?></option>
									<option value="bibtex"><?php _e( 'BibTeX Format', 'academic-bloggers-toolkit' ); ?></option>
									<option value="csv"><?php _e( 'CSV Format', 'academic-bloggers-toolkit' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Include', 'academic-bloggers-toolkit' ); ?></th>
							<td>
								<label><input type="checkbox" name="include_used" value="1" checked /> <?php _e( 'Referenced items only', 'academic-bloggers-toolkit' ); ?></label><br>
								<label><input type="checkbox" name="include_unused" value="1" checked /> <?php _e( 'Unused references', 'academic-bloggers-toolkit' ); ?></label>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Export References', 'academic-bloggers-toolkit' ), 'secondary', 'export_submit' ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Display the help page.
	 */
	public function display_help_page() {
		?>
		<div class="wrap abt-admin-page">
			<h1><?php _e( 'Help & Documentation', 'academic-bloggers-toolkit' ); ?></h1>
			
			<div class="abt-settings-section">
				<h3><?php _e( 'Getting Started', 'academic-bloggers-toolkit' ); ?></h3>
				<ol>
					<li><?php _e( 'Create your first academic post using the "Academic Posts" menu.', 'academic-bloggers-toolkit' ); ?></li>
					<li><?php _e( 'Add references to your library using the "References" menu.', 'academic-bloggers-toolkit' ); ?></li>
					<li><?php _e( 'Insert citations into your posts using the citation manager in the post editor.', 'academic-bloggers-toolkit' ); ?></li>
					<li><?php _e( 'Bibliography will be automatically generated at the end of your post.', 'academic-bloggers-toolkit' ); ?></li>
				</ol>
			</div>

			<div class="abt-settings-section">
				<h3><?php _e( 'Features', 'academic-bloggers-toolkit' ); ?></h3>
				<ul>
					<li><strong><?php _e( 'Auto-cite:', 'academic-bloggers-toolkit' ); ?></strong> <?php _e( 'Automatically fetch reference data from DOI, PMID, or URLs.', 'academic-bloggers-toolkit' ); ?></li>
					<li><strong><?php _e( 'Multiple Citation Styles:', 'academic-bloggers-toolkit' ); ?></strong> <?php _e( 'Support for APA, MLA, Chicago, Harvard, and IEEE styles.', 'academic-bloggers-toolkit' ); ?></li>
					<li><strong><?php _e( 'Import/Export:', 'academic-bloggers-toolkit' ); ?></strong> <?php _e( 'Import references from RIS, BibTeX, or CSV files.', 'academic-bloggers-toolkit' ); ?></li>
					<li><strong><?php _e( 'Footnotes:', 'academic-bloggers-toolkit' ); ?></strong> <?php _e( 'Add numbered or symbolic footnotes to your posts.', 'academic-bloggers-toolkit' ); ?></li>
				</ul>
			</div>

			<div class="abt-settings-section">
				<h3><?php _e( 'Troubleshooting', 'academic-bloggers-toolkit' ); ?></h3>
				<p><strong><?php _e( 'Q: Citations not appearing in my post?', 'academic-bloggers-toolkit' ); ?></strong><br>
				<?php _e( 'A: Make sure you have added citations in the post editor and that auto-bibliography is enabled in settings.', 'academic-bloggers-toolkit' ); ?></p>
				
				<p><strong><?php _e( 'Q: Auto-cite not working?', 'academic-bloggers-toolkit' ); ?></strong><br>
				<?php _e( 'A: Check that the DOI/PMID is valid and that auto-cite features are enabled in settings.', 'academic-bloggers-toolkit' ); ?></p>
			</div>

			<div class="abt-settings-section">
				<h3><?php _e( 'Support', 'academic-bloggers-toolkit' ); ?></h3>
				<p><?php _e( 'For additional help and support:', 'academic-bloggers-toolkit' ); ?></p>
				<ul>
					<li><a href="https://wbcomdesigns.com/docs/" target="_blank"><?php _e( 'Documentation', 'academic-bloggers-toolkit' ); ?></a></li>
					<li><a href="https://wbcomdesigns.com/support/" target="_blank"><?php _e( 'Support Forum', 'academic-bloggers-toolkit' ); ?></a></li>
					<li><a href="https://github.com/wbcomdesigns/academic-bloggers-toolkit" target="_blank"><?php _e( 'GitHub Repository', 'academic-bloggers-toolkit' ); ?></a></li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Add meta boxes for academic posts.
	 */
	public function add_meta_boxes() {
		// Academic blog post meta boxes
		add_meta_box(
			'abt_blog_citations',
			__( 'Citations & References', 'academic-bloggers-toolkit' ),
			array( $this, 'display_citations_metabox' ),
			'abt_blog',
			'normal',
			'high'
		);

		add_meta_box(
			'abt_blog_settings',
			__( 'Academic Settings', 'academic-bloggers-toolkit' ),
			array( $this, 'display_settings_metabox' ),
			'abt_blog',
			'side',
			'default'
		);

		// Reference meta boxes
		add_meta_box(
			'abt_reference_details',
			__( 'Reference Details', 'academic-bloggers-toolkit' ),
			array( $this, 'display_reference_metabox' ),
			'abt_reference',
			'normal',
			'high'
		);
	}

	/**
	 * Display citations meta box.
	 */
	public function display_citations_metabox( $post ) {
		require_once ABT_PLUGIN_DIR . 'admin/partials/meta-boxes/blog-citations.php';
	}

	/**
	 * Display academic settings meta box.
	 */
	public function display_settings_metabox( $post ) {
		wp_nonce_field( 'abt_save_meta', 'abt_meta_nonce' );
		
		$citation_style = get_post_meta( $post->ID, '_abt_citation_style', true ) ?: 'apa';
		$auto_bibliography = get_post_meta( $post->ID, '_abt_auto_bibliography', true );
		$enable_footnotes = get_post_meta( $post->ID, '_abt_enable_footnotes', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="abt_citation_style"><?php _e( 'Citation Style', 'academic-bloggers-toolkit' ); ?></label></th>
				<td>
					<select name="abt_citation_style" id="abt_citation_style">
						<option value="apa" <?php selected( $citation_style, 'apa' ); ?>>APA</option>
						<option value="mla" <?php selected( $citation_style, 'mla' ); ?>>MLA</option>
						<option value="chicago" <?php selected( $citation_style, 'chicago' ); ?>>Chicago</option>
						<option value="harvard" <?php selected( $citation_style, 'harvard' ); ?>>Harvard</option>
						<option value="ieee" <?php selected( $citation_style, 'ieee' ); ?>>IEEE</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Options', 'academic-bloggers-toolkit' ); ?></th>
				<td>
					<label><input type="checkbox" name="abt_auto_bibliography" value="1" <?php checked( $auto_bibliography ); ?> /> <?php _e( 'Auto-generate bibliography', 'academic-bloggers-toolkit' ); ?></label><br>
					<label><input type="checkbox" name="abt_enable_footnotes" value="1" <?php checked( $enable_footnotes ); ?> /> <?php _e( 'Enable footnotes', 'academic-bloggers-toolkit' ); ?></label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Display reference meta box.
	 */
	public function display_reference_metabox( $post ) {
		wp_nonce_field( 'abt_save_meta', 'abt_meta_nonce' );
		
		$reference_type = get_post_meta( $post->ID, '_abt_reference_type', true );
		$authors = get_post_meta( $post->ID, '_abt_authors', true );
		$year = get_post_meta( $post->ID, '_abt_year', true );
		$journal = get_post_meta( $post->ID, '_abt_journal', true );
		$doi = get_post_meta( $post->ID, '_abt_doi', true );
		$url = get_post_meta( $post->ID, '_abt_url', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="abt_reference_type"><?php _e( 'Reference Type', 'academic-bloggers-toolkit' ); ?></label></th>
				<td>
					<select name="abt_reference_type" id="abt_reference_type" required>
						<option value=""><?php _e( 'Select type...', 'academic-bloggers-toolkit' ); ?></option>
						<option value="journal" <?php selected( $reference_type, 'journal' ); ?>><?php _e( 'Journal Article', 'academic-bloggers-toolkit' ); ?></option>
						<option value="book" <?php selected( $reference_type, 'book' ); ?>><?php _e( 'Book', 'academic-bloggers-toolkit' ); ?></option>
						<option value="chapter" <?php selected( $reference_type, 'chapter' ); ?>><?php _e( 'Book Chapter', 'academic-bloggers-toolkit' ); ?></option>
						<option value="conference" <?php selected( $reference_type, 'conference' ); ?>><?php _e( 'Conference Paper', 'academic-bloggers-toolkit' ); ?></option>
						<option value="website" <?php selected( $reference_type, 'website' ); ?>><?php _e( 'Website', 'academic-bloggers-toolkit' ); ?></option>
						<option value="other" <?php selected( $reference_type, 'other' ); ?>><?php _e( 'Other', 'academic-bloggers-toolkit' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="abt_authors"><?php _e( 'Authors', 'academic-bloggers-toolkit' ); ?></label></th>
				<td><input type="text" name="abt_authors" id="abt_authors" value="<?php echo esc_attr( $authors ); ?>" class="widefat" /></td>
			</tr>
			<tr>
				<th><label for="abt_year"><?php _e( 'Publication Year', 'academic-bloggers-toolkit' ); ?></label></th>
				<td><input type="number" name="abt_year" id="abt_year" value="<?php echo esc_attr( $year ); ?>" min="1000" max="<?php echo date('Y') + 5; ?>" /></td>
			</tr>
			<tr>
				<th><label for="abt_journal"><?php _e( 'Journal/Publication', 'academic-bloggers-toolkit' ); ?></label></th>
				<td><input type="text" name="abt_journal" id="abt_journal" value="<?php echo esc_attr( $journal ); ?>" class="widefat" /></td>
			</tr>
			<tr>
				<th><label for="abt_doi"><?php _e( 'DOI', 'academic-bloggers-toolkit' ); ?></label></th>
				<td><input type="text" name="abt_doi" id="abt_doi" value="<?php echo esc_attr( $doi ); ?>" class="widefat" placeholder="10.1000/182" /></td>
			</tr>
			<tr>
				<th><label for="abt_url"><?php _e( 'URL', 'academic-bloggers-toolkit' ); ?></label></th>
				<td><input type="url" name="abt_url" id="abt_url" value="<?php echo esc_attr( $url ); ?>" class="widefat" /></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save meta box data.
	 */
	public function save_meta_boxes( $post_id ) {
		// Verify nonce
		if ( ! isset( $_POST['abt_meta_nonce'] ) || ! wp_verify_nonce( $_POST['abt_meta_nonce'], 'abt_save_meta' ) ) {
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

		$post_type = get_post_type( $post_id );

		if ( 'abt_blog' === $post_type ) {
			$this->save_blog_meta( $post_id );
		} elseif ( 'abt_reference' === $post_type ) {
			$this->save_reference_meta( $post_id );
		}
	}

	/**
	 * Save blog post meta data.
	 */
	private function save_blog_meta( $post_id ) {
		$fields = array( 'citation_style', 'auto_bibliography', 'enable_footnotes' );
		
		foreach ( $fields as $field ) {
			if ( isset( $_POST["abt_{$field}"] ) ) {
				update_post_meta( $post_id, "_abt_{$field}", sanitize_text_field( $_POST["abt_{$field}"] ) );
			} else {
				delete_post_meta( $post_id, "_abt_{$field}" );
			}
		}
	}

	/**
	 * Save reference meta data.
	 */
	private function save_reference_meta( $post_id ) {
		$fields = array( 'reference_type', 'authors', 'year', 'journal', 'doi', 'url' );
		
		foreach ( $fields as $field ) {
			if ( isset( $_POST["abt_{$field}"] ) ) {
				$value = $_POST["abt_{$field}"];
				
				if ( $field === 'year' ) {
					$value = intval( $value );
				} elseif ( $field === 'url' ) {
					$value = esc_url_raw( $value );
				} else {
					$value = sanitize_text_field( $value );
				}
				
				update_post_meta( $post_id, "_abt_{$field}", $value );
			}
		}
	}

	/**
	 * Add custom columns to blog posts.
	 */
	public function add_blog_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[$key] = $value;
			if ( 'title' === $key ) {
				$new_columns['abt_citations'] = __( 'Citations', 'academic-bloggers-toolkit' );
				$new_columns['abt_style'] = __( 'Style', 'academic-bloggers-toolkit' );
			}
		}
		return $new_columns;
	}

	/**
	 * Display custom column content for blog posts.
	 */
	public function display_blog_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'abt_citations':
				$citations = get_post_meta( $post_id, '_abt_citations', true );
				echo is_array( $citations ) ? count( $citations ) : '0';
				break;
			case 'abt_style':
				$style = get_post_meta( $post_id, '_abt_citation_style', true );
				echo $style ? strtoupper( $style ) : 'APA';
				break;
		}
	}

	/**
	 * Add custom columns to references.
	 */
	public function add_reference_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[$key] = $value;
			if ( 'title' === $key ) {
				$new_columns['abt_type'] = __( 'Type', 'academic-bloggers-toolkit' );
				$new_columns['abt_authors'] = __( 'Authors', 'academic-bloggers-toolkit' );
				$new_columns['abt_year'] = __( 'Year', 'academic-bloggers-toolkit' );
			}
		}
		return $new_columns;
	}

	/**
	 * Display custom column content for references.
	 */
	public function display_reference_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'abt_type':
				$type = get_post_meta( $post_id, '_abt_reference_type', true );
				echo $type ? ucfirst( $type ) : '—';
				break;
			case 'abt_authors':
				$authors = get_post_meta( $post_id, '_abt_authors', true );
				echo $authors ? esc_html( wp_trim_words( $authors, 3 ) ) : '—';
				break;
			case 'abt_year':
				$year = get_post_meta( $post_id, '_abt_year', true );
				echo $year ? esc_html( $year ) : '—';
				break;
		}
	}

	/**
	 * Get dashboard statistics.
	 */
	private function get_dashboard_stats() {
		$stats = array(
			'total_posts' => wp_count_posts( 'abt_blog' )->publish ?? 0,
			'total_references' => wp_count_posts( 'abt_reference' )->publish ?? 0,
			'total_citations' => 0,
			'avg_citations' => 0,
		);

		// Calculate total citations
		$posts = get_posts( array(
			'post_type' => 'abt_blog',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'fields' => 'ids'
		) );

		$total_citations = 0;
		foreach ( $posts as $post_id ) {
			$citations = get_post_meta( $post_id, '_abt_citations', true );
			if ( is_array( $citations ) ) {
				$total_citations += count( $citations );
			}
		}

		$stats['total_citations'] = $total_citations;
		$stats['avg_citations'] = $stats['total_posts'] > 0 ? round( $total_citations / $stats['total_posts'], 1 ) : 0;

		return $stats;
	}

	/**
	 * Display recent activity.
	 */
	private function display_recent_activity() {
		$recent_posts = get_posts( array(
			'post_type' => array( 'abt_blog', 'abt_reference' ),
			'post_status' => 'publish',
			'posts_per_page' => 5,
			'orderby' => 'date',
			'order' => 'DESC'
		) );

		if ( empty( $recent_posts ) ) {
			echo '<p>' . __( 'No recent activity.', 'academic-bloggers-toolkit' ) . '</p>';
			return;
		}

		echo '<ul>';
		foreach ( $recent_posts as $post ) {
			$type_label = $post->post_type === 'abt_blog' ? __( 'Academic Post', 'academic-bloggers-toolkit' ) : __( 'Reference', 'academic-bloggers-toolkit' );
			echo '<li>';
			echo '<strong>' . esc_html( $type_label ) . ':</strong> ';
			echo '<a href="' . get_edit_post_link( $post->ID ) . '">' . esc_html( $post->post_title ) . '</a> ';
			echo '<small>(' . human_time_diff( strtotime( $post->post_date ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'academic-bloggers-toolkit' ) . ')</small>';
			echo '</li>';
		}
		echo '</ul>';
	}

	/**
	 * Save settings.
	 */
	private function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $_POST['abt_settings'] ?? array();
		
		// Sanitize settings
		$sanitized_settings = array(
			'citation_style' => sanitize_text_field( $settings['citation_style'] ?? 'apa' ),
			'auto_bibliography' => ! empty( $settings['auto_bibliography'] ),
			'bibliography_title' => sanitize_text_field( $settings['bibliography_title'] ?? __( 'References', 'academic-bloggers-toolkit' ) ),
			'footnote_style' => sanitize_text_field( $settings['footnote_style'] ?? 'numeric' ),
			'doi_fetcher_enabled' => ! empty( $settings['doi_fetcher_enabled'] ),
			'pubmed_fetcher_enabled' => ! empty( $settings['pubmed_fetcher_enabled'] ),
			'url_scraper_enabled' => ! empty( $settings['url_scraper_enabled'] ),
			'enable_analytics' => ! empty( $settings['enable_analytics'] ),
			'cache_duration' => intval( $settings['cache_duration'] ?? 3600 ),
		);

		update_option( 'abt_settings', $sanitized_settings );
		
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Settings saved successfully.', 'academic-bloggers-toolkit' ) . '</p></div>';
		});
	}

	/**
	 * Display admin notices.
	 */
	private function display_admin_notices() {
		// This method is called in the page templates to show any admin notices
	}

	/**
	 * Sanitize settings.
	 */
	public function sanitize_settings( $settings ) {
		$sanitized = array();
		
		if ( isset( $settings['citation_style'] ) ) {
			$sanitized['citation_style'] = in_array( $settings['citation_style'], array( 'apa', 'mla', 'chicago', 'harvard', 'ieee' ) ) ? $settings['citation_style'] : 'apa';
		}
		
		$sanitized['auto_bibliography'] = ! empty( $settings['auto_bibliography'] );
		$sanitized['doi_fetcher_enabled'] = ! empty( $settings['doi_fetcher_enabled'] );
		
		return $sanitized;
	}

	/**
	 * Settings section callbacks.
	 */
	public function general_settings_callback() {
		echo '<p>' . __( 'Configure general settings for Academic Blogger\'s Toolkit.', 'academic-bloggers-toolkit' ) . '</p>';
	}

	public function autocite_settings_callback() {
		echo '<p>' . __( 'Configure auto-cite features for automatically fetching reference data.', 'academic-bloggers-toolkit' ) . '</p>';
	}

	public function citation_style_callback() {
		$settings = get_option( 'abt_settings', array() );
		$value = $settings['citation_style'] ?? 'apa';
		echo '<select name="abt_settings[citation_style]">';
		echo '<option value="apa"' . selected( $value, 'apa', false ) . '>APA</option>';
		echo '<option value="mla"' . selected( $value, 'mla', false ) . '>MLA</option>';
		echo '<option value="chicago"' . selected( $value, 'chicago', false ) . '>Chicago</option>';
		echo '</select>';
	}

	public function auto_bibliography_callback() {
		$settings = get_option( 'abt_settings', array() );
		$value = $settings['auto_bibliography'] ?? true;
		echo '<input type="checkbox" name="abt_settings[auto_bibliography]" value="1"' . checked( $value, true, false ) . ' />';
	}

	public function footnote_style_callback() {
		$settings = get_option( 'abt_settings', array() );
		$value = $settings['footnote_style'] ?? 'numeric';
		echo '<select name="abt_settings[footnote_style]">';
		echo '<option value="numeric"' . selected( $value, 'numeric', false ) . '>' . __( 'Numeric', 'academic-bloggers-toolkit' ) . '</option>';
		echo '<option value="alpha"' . selected( $value, 'alpha', false ) . '>' . __( 'Alphabetic', 'academic-bloggers-toolkit' ) . '</option>';
		echo '</select>';
	}

	public function doi_fetcher_callback() {
		$settings = get_option( 'abt_settings', array() );
		$value = $settings['doi_fetcher_enabled'] ?? true;
		echo '<input type="checkbox" name="abt_settings[doi_fetcher_enabled]" value="1"' . checked( $value, true, false ) . ' />';
	}

	public function url_scraper_callback() {
		$settings = get_option( 'abt_settings', array() );
		$value = $settings['url_scraper_enabled'] ?? true;
		echo '<input type="checkbox" name="abt_settings[url_scraper_enabled]" value="1"' . checked( $value, true, false ) . ' />';
	}

	/**
	 * Add contextual help.
	 */
	public function add_dashboard_help() {
		$screen = get_current_screen();
		$screen->add_help_tab( array(
			'id'      => 'abt_dashboard_help',
			'title'   => __( 'Dashboard', 'academic-bloggers-toolkit' ),
			'content' => '<p>' . __( 'The dashboard shows an overview of your academic content and recent activity.', 'academic-bloggers-toolkit' ) . '</p>',
		) );
	}

	public function add_settings_help() {
		$screen = get_current_screen();
		$screen->add_help_tab( array(
			'id'      => 'abt_settings_help',
			'title'   => __( 'Settings', 'academic-bloggers-toolkit' ),
			'content' => '<p>' . __( 'Configure default citation styles, auto-cite features, and other plugin settings.', 'academic-bloggers-toolkit' ) . '</p>',
		) );
	}
}