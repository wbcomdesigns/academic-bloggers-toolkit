<?php
/**
 * Reference AJAX Operations Handler
 *
 * Handles all AJAX operations related to reference management:
 * - Auto-cite functionality (DOI, PMID, ISBN, URL)
 * - Reference CRUD operations
 * - Search and filtering
 * - Import/export operations
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Admin/Ajax
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reference AJAX Handler Class
 */
class ABT_Reference_Ajax {

    /**
     * Initialize the AJAX handlers
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        // Auto-cite operations
        add_action('wp_ajax_abt_auto_cite_doi', array($this, 'auto_cite_doi'));
        add_action('wp_ajax_abt_auto_cite_pmid', array($this, 'auto_cite_pmid'));
        add_action('wp_ajax_abt_auto_cite_isbn', array($this, 'auto_cite_isbn'));
        add_action('wp_ajax_abt_auto_cite_url', array($this, 'auto_cite_url'));

        // Reference CRUD operations
        add_action('wp_ajax_abt_save_reference', array($this, 'save_reference'));
        add_action('wp_ajax_abt_delete_reference', array($this, 'delete_reference'));
        add_action('wp_ajax_abt_get_reference', array($this, 'get_reference'));
        add_action('wp_ajax_abt_duplicate_reference', array($this, 'duplicate_reference'));

        // Search and filtering
        add_action('wp_ajax_abt_search_references', array($this, 'search_references'));
        add_action('wp_ajax_abt_filter_references', array($this, 'filter_references'));
        add_action('wp_ajax_abt_get_reference_suggestions', array($this, 'get_reference_suggestions'));

        // Bulk operations
        add_action('wp_ajax_abt_bulk_delete_references', array($this, 'bulk_delete_references'));
        add_action('wp_ajax_abt_bulk_export_references', array($this, 'bulk_export_references'));
        add_action('wp_ajax_abt_bulk_categorize_references', array($this, 'bulk_categorize_references'));

        // Import/export operations
        add_action('wp_ajax_abt_import_references', array($this, 'import_references'));
        add_action('wp_ajax_abt_export_references', array($this, 'export_references'));
        add_action('wp_ajax_abt_validate_import_file', array($this, 'validate_import_file'));
    }

    /**
     * Auto-cite from DOI
     *
     * @since 1.0.0
     */
    public function auto_cite_doi() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_auto_cite_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $doi = sanitize_text_field($_POST['doi']);
        
        if (empty($doi)) {
            wp_send_json_error('DOI is required');
        }

        try {
            // Use the DOI fetcher to get reference data
            $fetcher = new ABT_DOI_Fetcher();
            $reference_data = $fetcher->fetch($doi);

            if ($reference_data) {
                // Create new reference post
                $reference_id = $this->create_reference_from_data($reference_data);
                
                if ($reference_id) {
                    wp_send_json_success(array(
                        'reference_id' => $reference_id,
                        'reference_data' => $reference_data,
                        'message' => 'Reference created successfully from DOI'
                    ));
                } else {
                    wp_send_json_error('Failed to create reference');
                }
            } else {
                wp_send_json_error('No data found for this DOI');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error fetching DOI data: ' . $e->getMessage());
        }
    }

    /**
     * Auto-cite from PubMed ID
     *
     * @since 1.0.0
     */
    public function auto_cite_pmid() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_auto_cite_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $pmid = sanitize_text_field($_POST['pmid']);
        
        if (empty($pmid)) {
            wp_send_json_error('PubMed ID is required');
        }

        try {
            // Use the PubMed fetcher to get reference data
            $fetcher = new ABT_PubMed_Fetcher();
            $reference_data = $fetcher->fetch($pmid);

            if ($reference_data) {
                // Create new reference post
                $reference_id = $this->create_reference_from_data($reference_data);
                
                if ($reference_id) {
                    wp_send_json_success(array(
                        'reference_id' => $reference_id,
                        'reference_data' => $reference_data,
                        'message' => 'Reference created successfully from PubMed ID'
                    ));
                } else {
                    wp_send_json_error('Failed to create reference');
                }
            } else {
                wp_send_json_error('No data found for this PubMed ID');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error fetching PubMed data: ' . $e->getMessage());
        }
    }

    /**
     * Auto-cite from ISBN
     *
     * @since 1.0.0
     */
    public function auto_cite_isbn() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_auto_cite_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $isbn = sanitize_text_field($_POST['isbn']);
        
        if (empty($isbn)) {
            wp_send_json_error('ISBN is required');
        }

        try {
            // Use the ISBN fetcher to get reference data
            $fetcher = new ABT_ISBN_Fetcher();
            $reference_data = $fetcher->fetch($isbn);

            if ($reference_data) {
                // Create new reference post
                $reference_id = $this->create_reference_from_data($reference_data);
                
                if ($reference_id) {
                    wp_send_json_success(array(
                        'reference_id' => $reference_id,
                        'reference_data' => $reference_data,
                        'message' => 'Reference created successfully from ISBN'
                    ));
                } else {
                    wp_send_json_error('Failed to create reference');
                }
            } else {
                wp_send_json_error('No data found for this ISBN');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error fetching ISBN data: ' . $e->getMessage());
        }
    }

    /**
     * Auto-cite from URL
     *
     * @since 1.0.0
     */
    public function auto_cite_url() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_auto_cite_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $url = esc_url_raw($_POST['url']);
        
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error('Valid URL is required');
        }

        try {
            // Use the URL scraper to get reference data
            $scraper = new ABT_URL_Scraper();
            $reference_data = $scraper->scrape($url);

            if ($reference_data) {
                // Create new reference post
                $reference_id = $this->create_reference_from_data($reference_data);
                
                if ($reference_id) {
                    wp_send_json_success(array(
                        'reference_id' => $reference_id,
                        'reference_data' => $reference_data,
                        'message' => 'Reference created successfully from URL'
                    ));
                } else {
                    wp_send_json_error('Failed to create reference');
                }
            } else {
                wp_send_json_error('No metadata found for this URL');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error scraping URL data: ' . $e->getMessage());
        }
    }

    /**
     * Save reference (create or update)
     *
     * @since 1.0.0
     */
    public function save_reference() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_save_reference_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $reference_id = isset($_POST['reference_id']) ? intval($_POST['reference_id']) : 0;
        $reference_data = isset($_POST['reference_data']) ? $_POST['reference_data'] : array();

        // Sanitize reference data
        $sanitized_data = $this->sanitize_reference_data($reference_data);

        try {
            if ($reference_id) {
                // Update existing reference
                $result = $this->update_reference($reference_id, $sanitized_data);
            } else {
                // Create new reference
                $result = $this->create_reference_from_data($sanitized_data);
            }

            if ($result) {
                wp_send_json_success(array(
                    'reference_id' => $result,
                    'message' => $reference_id ? 'Reference updated successfully' : 'Reference created successfully'
                ));
            } else {
                wp_send_json_error('Failed to save reference');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error saving reference: ' . $e->getMessage());
        }
    }

    /**
     * Delete reference
     *
     * @since 1.0.0
     */
    public function delete_reference() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_delete_reference_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('delete_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $reference_id = intval($_POST['reference_id']);

        if (empty($reference_id)) {
            wp_send_json_error('Reference ID is required');
        }

        // Check if reference exists and is of correct post type
        $reference = get_post($reference_id);
        if (!$reference || $reference->post_type !== 'abt_reference') {
            wp_send_json_error('Invalid reference');
        }

        // Check if reference is being used in any posts
        $usage_count = $this->get_reference_usage_count($reference_id);
        
        if ($usage_count > 0) {
            wp_send_json_error("Reference is being used in {$usage_count} posts. Please remove citations before deleting.");
        }

        // Delete the reference
        $result = wp_delete_post($reference_id, true);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Reference deleted successfully'
            ));
        } else {
            wp_send_json_error('Failed to delete reference');
        }
    }

    /**
     * Get reference data
     *
     * @since 1.0.0
     */
    public function get_reference() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_get_reference_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }

        $reference_id = intval($_POST['reference_id']);

        if (empty($reference_id)) {
            wp_send_json_error('Reference ID is required');
        }

        $reference = get_post($reference_id);
        
        if (!$reference || $reference->post_type !== 'abt_reference') {
            wp_send_json_error('Reference not found');
        }

        // Get all meta data
        $meta_data = get_post_meta($reference_id);
        $reference_data = array();

        // Process meta data
        foreach ($meta_data as $key => $value) {
            if (strpos($key, '_abt_') === 0) {
                $clean_key = str_replace('_abt_', '', $key);
                $reference_data[$clean_key] = is_array($value) ? $value[0] : $value;
            }
        }

        wp_send_json_success(array(
            'reference_id' => $reference_id,
            'reference_data' => $reference_data,
            'post_title' => $reference->post_title,
            'post_status' => $reference->post_status
        ));
    }

    /**
     * Search references
     *
     * @since 1.0.0
     */
    public function search_references() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_search_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }

        $search_term = sanitize_text_field($_POST['search_term']);
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;

        if (empty($search_term)) {
            wp_send_json_error('Search term is required');
        }

        // Search query
        $args = array(
            'post_type' => 'abt_reference',
            'post_status' => 'publish',
            's' => $search_term,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_abt_authors',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_abt_title',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_abt_journal',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                )
            )
        );

        $query = new WP_Query($args);
        $references = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $reference_id = get_the_ID();
                
                $references[] = array(
                    'id' => $reference_id,
                    'title' => get_the_title(),
                    'authors' => get_post_meta($reference_id, '_abt_authors', true),
                    'journal' => get_post_meta($reference_id, '_abt_journal', true),
                    'year' => get_post_meta($reference_id, '_abt_year', true),
                    'type' => get_post_meta($reference_id, '_abt_type', true)
                );
            }
            wp_reset_postdata();
        }

        wp_send_json_success(array(
            'references' => $references,
            'total_found' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'current_page' => $page
        ));
    }

    /**
     * Get reference suggestions for autocomplete
     *
     * @since 1.0.0
     */
    public function get_reference_suggestions() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_suggestions_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }

        $search_term = sanitize_text_field($_POST['search_term']);
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;

        if (strlen($search_term) < 2) {
            wp_send_json_success(array('suggestions' => array()));
        }

        // Quick search query for suggestions
        $args = array(
            'post_type' => 'abt_reference',
            'post_status' => 'publish',
            's' => $search_term,
            'posts_per_page' => $limit,
            'fields' => 'ids'
        );

        $reference_ids = get_posts($args);
        $suggestions = array();

        foreach ($reference_ids as $reference_id) {
            $authors = get_post_meta($reference_id, '_abt_authors', true);
            $title = get_post_meta($reference_id, '_abt_title', true);
            $year = get_post_meta($reference_id, '_abt_year', true);

            $suggestions[] = array(
                'id' => $reference_id,
                'label' => $this->format_reference_suggestion($authors, $title, $year),
                'value' => $reference_id
            );
        }

        wp_send_json_success(array('suggestions' => $suggestions));
    }

    /**
     * Import references from file
     *
     * @since 1.0.0
     */
    public function import_references() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_import_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        if (!isset($_FILES['import_file'])) {
            wp_send_json_error('No file uploaded');
        }

        $file = $_FILES['import_file'];
        $file_type = sanitize_text_field($_POST['file_type']);

        // Validate file
        $allowed_types = array('ris', 'bibtex', 'csv');
        if (!in_array($file_type, $allowed_types)) {
            wp_send_json_error('Invalid file type');
        }

        try {
            $import_manager = new ABT_Import_Manager();
            $result = $import_manager->import_file($file, $file_type);

            if ($result['success']) {
                wp_send_json_success(array(
                    'imported_count' => $result['imported_count'],
                    'skipped_count' => $result['skipped_count'],
                    'errors' => $result['errors'],
                    'message' => "Successfully imported {$result['imported_count']} references"
                ));
            } else {
                wp_send_json_error('Import failed: ' . $result['message']);
            }
        } catch (Exception $e) {
            wp_send_json_error('Import error: ' . $e->getMessage());
        }
    }

    /**
     * Create reference from fetched data
     *
     * @param array $reference_data The reference data
     * @return int|false The reference ID or false on failure
     * @since 1.0.0
     */
    private function create_reference_from_data($reference_data) {
        $post_data = array(
            'post_type' => 'abt_reference',
            'post_title' => $reference_data['title'] ?? 'Untitled Reference',
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        );

        $reference_id = wp_insert_post($post_data);

        if ($reference_id && !is_wp_error($reference_id)) {
            // Save meta data
            foreach ($reference_data as $key => $value) {
                if ($key !== 'title') {
                    update_post_meta($reference_id, '_abt_' . $key, $value);
                }
            }
            
            return $reference_id;
        }

        return false;
    }

    /**
     * Update reference with new data
     *
     * @param int $reference_id The reference ID
     * @param array $reference_data The reference data
     * @return int|false The reference ID or false on failure
     * @since 1.0.0
     */
    private function update_reference($reference_id, $reference_data) {
        $post_data = array(
            'ID' => $reference_id,
            'post_title' => $reference_data['title'] ?? 'Untitled Reference'
        );

        $result = wp_update_post($post_data);

        if ($result && !is_wp_error($result)) {
            // Update meta data
            foreach ($reference_data as $key => $value) {
                if ($key !== 'title') {
                    update_post_meta($reference_id, '_abt_' . $key, $value);
                }
            }
            
            return $reference_id;
        }

        return false;
    }

    /**
     * Sanitize reference data
     *
     * @param array $data Raw reference data
     * @return array Sanitized reference data
     * @since 1.0.0
     */
    private function sanitize_reference_data($data) {
        $sanitized = array();

        $text_fields = array('title', 'authors', 'journal', 'publisher', 'doi', 'pmid', 'isbn', 'url', 'abstract');
        $number_fields = array('year', 'volume', 'issue', 'pages');

        foreach ($data as $key => $value) {
            if (in_array($key, $text_fields)) {
                $sanitized[$key] = sanitize_text_field($value);
            } elseif (in_array($key, $number_fields)) {
                $sanitized[$key] = sanitize_text_field($value);
            } elseif ($key === 'url') {
                $sanitized[$key] = esc_url_raw($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * Get reference usage count
     *
     * @param int $reference_id The reference ID
     * @return int Usage count
     * @since 1.0.0
     */
    private function get_reference_usage_count($reference_id) {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
             WHERE meta_key LIKE '_abt_citations' 
             AND meta_value LIKE %s",
            '%' . $wpdb->esc_like($reference_id) . '%'
        ));

        return intval($count);
    }

    /**
     * Format reference suggestion for autocomplete
     *
     * @param string $authors Authors
     * @param string $title Title
     * @param string $year Year
     * @return string Formatted suggestion
     * @since 1.0.0
     */
    private function format_reference_suggestion($authors, $title, $year) {
        $parts = array();
        
        if (!empty($authors)) {
            $authors_array = explode(',', $authors);
            $first_author = trim($authors_array[0]);
            $parts[] = $first_author;
        }
        
        if (!empty($year)) {
            $parts[] = "({$year})";
        }
        
        if (!empty($title)) {
            $parts[] = $title;
        }

        return implode(' ', $parts);
    }
}