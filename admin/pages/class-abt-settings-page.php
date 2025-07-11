<?php
/**
 * Settings page functionality.
 *
 * @link       https://github.com/wbcomdesigns
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
 * Settings page functionality.
 *
 * Handles the plugin settings interface and configuration.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/pages
 * @author     Academic Bloggers Toolkit Team
 */
class ABT_Settings_Page {

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
	 * Display the settings page.
	 *
	 * @since    1.0.0
	 */
	public function display() {
		?>
		<div class="wrap">
			<h1><?php _e( 'Academic Blogger\'s Toolkit Settings', 'academic-bloggers-toolkit' ); ?></h1>
			
			<form method="post" action="options.php">
				<?php
				settings_fields( 'abt_settings' );
				do_settings_sections( 'abt_settings' );
				?>
				
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="abt_default_citation_style"><?php _e( 'Default Citation Style', 'academic-bloggers-toolkit' ); ?></label>
							</th>
							<td>
								<select name="abt_default_citation_style" id="abt_default_citation_style">
									<option value="apa" <?php selected( get_option( 'abt_default_citation_style', 'apa' ), 'apa' ); ?>><?php _e( 'APA', 'academic-bloggers-toolkit' ); ?></option>
									<option value="mla" <?php selected( get_option( 'abt_default_citation_style', 'apa' ), 'mla' ); ?>><?php _e( 'MLA', 'academic-bloggers-toolkit' ); ?></option>
									<option value="chicago" <?php selected( get_option( 'abt_default_citation_style', 'apa' ), 'chicago' ); ?>><?php _e( 'Chicago', 'academic-bloggers-toolkit' ); ?></option>
									<option value="harvard" <?php selected( get_option( 'abt_default_citation_style', 'apa' ), 'harvard' ); ?>><?php _e( 'Harvard', 'academic-bloggers-toolkit' ); ?></option>
									<option value="ieee" <?php selected( get_option( 'abt_default_citation_style', 'apa' ), 'ieee' ); ?>><?php _e( 'IEEE', 'academic-bloggers-toolkit' ); ?></option>
								</select>
								<p class="description"><?php _e( 'Default citation style for new academic posts.', 'academic-bloggers-toolkit' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="abt_auto_bibliography"><?php _e( 'Auto-generate Bibliography', 'academic-bloggers-toolkit' ); ?></label>
							</th>
							<td>
								<input type="checkbox" name="abt_auto_bibliography" id="abt_auto_bibliography" value="1" <?php checked( get_option( 'abt_auto_bibliography', 1 ) ); ?> />
								<label for="abt_auto_bibliography"><?php _e( 'Automatically generate bibliography at the end of academic posts', 'academic-bloggers-toolkit' ); ?></label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="abt_enable_footnotes"><?php _e( 'Enable Footnotes', 'academic-bloggers-toolkit' ); ?></label>
							</th>
							<td>
								<input type="checkbox" name="abt_enable_footnotes" id="abt_enable_footnotes" value="1" <?php checked( get_option( 'abt_enable_footnotes', 1 ) ); ?> />
								<label for="abt_enable_footnotes"><?php _e( 'Enable footnote functionality for academic posts', 'academic-bloggers-toolkit' ); ?></label>
							</td>
						</tr>
					</tbody>
				</table>
				
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}