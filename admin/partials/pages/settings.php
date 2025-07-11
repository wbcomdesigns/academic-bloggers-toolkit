<?php
/**
 * Provide a admin area view for the settings page.
 *
 * This file is used to markup the admin-facing aspects of the plugin settings.
 *
 * @link       https://github.com/academic-bloggers-toolkit
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/partials/pages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize the settings page class
$settings_page = new ABT_Settings_Page( $this->plugin_name, $this->version );

// Display the page
$settings_page->display();