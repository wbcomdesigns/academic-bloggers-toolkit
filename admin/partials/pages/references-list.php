<?php
/**
 * Provide a admin area view for the references list page.
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/wbcomdesigns
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/partials/pages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize the references page class
$references_page = new ABT_References_Page( $this->plugin_name, $this->version );

// Display the page
$references_page->display();