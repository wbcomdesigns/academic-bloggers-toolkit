<?php
/**
 * Provide a admin area view for the statistics page.
 *
 * This file is used to markup the admin-facing aspects of the plugin statistics.
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

// Initialize the statistics page class
$statistics_page = new ABT_Statistics_Page( $this->plugin_name, $this->version );

// Display the page
$statistics_page->display();