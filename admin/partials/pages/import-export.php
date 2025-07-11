<?php
/**
 * Provide a admin area view for the import/export page.
 *
 * This file is used to markup the admin-facing aspects of the plugin import/export.
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

// Initialize the import page class
$import_page = new ABT_Import_Page( $this->plugin_name, $this->version );

// Display the page
$import_page->display();