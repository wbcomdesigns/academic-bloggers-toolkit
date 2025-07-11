<?php
/**
 * Admin notices functionality.
 *
 * @link       https://github.com/academic-bloggers-toolkit
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
 * Admin notices functionality.
 *
 * Handles admin notifications and messages for the plugin.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin
 * @author     Academic Bloggers Toolkit Team
 */
class ABT_Admin_Notices {

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
	 * Display setup notice if plugin needs initial configuration.
	 *
	 * @since    1.0.0
	 */
	public function show_setup_notice() {
		if ( get_option( 'abt_needs_setup', true ) ) {
			?>
			<div class="notice notice-info is-dismissible" data-dismissible="abt-setup-notice">
				<p>
					<?php 
					printf(
						__( 'Welcome to Academic Blogger\'s Toolkit! <a href="%s">Complete the setup</a> to get started with academic citations and references.', 'academic-bloggers-toolkit' ),
						admin_url( 'admin.php?page=abt-settings' )
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Display success notices.
	 *
	 * @since    1.0.0
	 */
	public function show_success_notices() {
		if ( isset( $_GET['abt_message'] ) ) {
			$message = sanitize_text_field( $_GET['abt_message'] );
			$messages = $this->get_success_messages();
			
			if ( isset( $messages[ $message ] ) ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( $messages[ $message ] ); ?></p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Display error notices.
	 *
	 * @since    1.0.0
	 */
	public function show_error_notices() {
		if ( isset( $_GET['abt_error'] ) ) {
			$error = sanitize_text_field( $_GET['abt_error'] );
			$errors = $this->get_error_messages();
			
			if ( isset( $errors[ $error ] ) ) {
				?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html( $errors[ $error ] ); ?></p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Get predefined success messages.
	 *
	 * @since    1.0.0
	 * @return   array    Success messages.
	 */
	private function get_success_messages() {
		return array(
			'reference_created' => __( 'Reference created successfully.', 'academic-bloggers-toolkit' ),
			'reference_updated' => __( 'Reference updated successfully.', 'academic-bloggers-toolkit' ),
			'reference_deleted' => __( 'Reference deleted successfully.', 'academic-bloggers-toolkit' ),
			'settings_saved' => __( 'Settings saved successfully.', 'academic-bloggers-toolkit' ),
			'import_complete' => __( 'Import completed successfully.', 'academic-bloggers-toolkit' ),
			'export_complete' => __( 'Export completed successfully.', 'academic-bloggers-toolkit' ),
		);
	}

	/**
	 * Get predefined error messages.
	 *
	 * @since    1.0.0
	 * @return   array    Error messages.
	 */
	private function get_error_messages() {
		return array(
			'reference_not_found' => __( 'Reference not found.', 'academic-bloggers-toolkit' ),
			'invalid_file_format' => __( 'Invalid file format for import.', 'academic-bloggers-toolkit' ),
			'import_failed' => __( 'Import failed. Please check your file format.', 'academic-bloggers-toolkit' ),
			'export_failed' => __( 'Export failed. Please try again.', 'academic-bloggers-toolkit' ),
			'permission_denied' => __( 'You do not have permission to perform this action.', 'academic-bloggers-toolkit' ),
		);
	}

	/**
	 * Add a success notice to be displayed on next page load.
	 *
	 * @since    1.0.0
	 * @param    string    $message_key    Message key.
	 */
	public static function add_success_notice( $message_key ) {
		set_transient( 'abt_success_notice', $message_key, 30 );
	}

	/**
	 * Add an error notice to be displayed on next page load.
	 *
	 * @since    1.0.0
	 * @param    string    $message_key    Message key.
	 */
	public static function add_error_notice( $message_key ) {
		set_transient( 'abt_error_notice', $message_key, 30 );
	}

	/**
	 * Display transient notices.
	 *
	 * @since    1.0.0
	 */
	public function show_transient_notices() {
		// Show success notices
		$success_notice = get_transient( 'abt_success_notice' );
		if ( $success_notice ) {
			$messages = $this->get_success_messages();
			if ( isset( $messages[ $success_notice ] ) ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( $messages[ $success_notice ] ); ?></p>
				</div>
				<?php
			}
			delete_transient( 'abt_success_notice' );
		}

		// Show error notices
		$error_notice = get_transient( 'abt_error_notice' );
		if ( $error_notice ) {
			$errors = $this->get_error_messages();
			if ( isset( $errors[ $error_notice ] ) ) {
				?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html( $errors[ $error_notice ] ); ?></p>
				</div>
				<?php
			}
			delete_transient( 'abt_error_notice' );
		}
	}
}