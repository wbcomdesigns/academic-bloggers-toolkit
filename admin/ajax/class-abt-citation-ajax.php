<?php
/**
 * Citation AJAX Operations Handler
 *
 * Handles all AJAX operations related to citation management:
 * - Citation insertion and editing
 * - Bibliography generation
 * - Citation style switching
 * - Footnote management
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
 * Citation AJAX Handler Class
 */
class ABT_Citation_Ajax {

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
        // Citation management
        add_action('wp_ajax_abt_insert_citation', array($this, 'insert_citation'));
        add_action('wp_ajax_abt_update_citation', array($this, 'update_citation'));
        add_action('wp_ajax_abt_remove_citation', array($this, 'remove_citation'));
        add_action('wp_ajax_abt_get_citation_preview', array($this, 'get_citation_preview'));

        // Bibliography operations
        add_action('wp_ajax_abt_generate_bibliography', array($this, 'generate_bibliography'));
        add_action('wp_ajax_abt_update_bibliography', array($this, 'update_bibliography'));
        add_action('wp_ajax_abt_switch_citation_style', array($this, 'switch_citation_style'));
        add_action('wp_ajax_abt_get_bibliography_preview', array($this, 'get_bibliography_preview'));

        // Footnote operations
        add_action('wp_ajax_abt_add_footnote', array($this, 'add_footnote'));
        add_action('wp_ajax_abt_update_footnote', array($this, 'update_footnote'));
        add_action('wp_ajax_abt_remove_footnote', array($this, 'remove_footnote'));
        add_action('wp_ajax_abt_reorder_footnotes', array($this, 'reorder_footnotes'));

        // Citation style management
        add_action('wp_ajax_abt_get_citation_styles', array($this, 'get_citation_styles'));
        add_action('wp_ajax_abt_validate_citation_data', array($this, 'validate_citation_data'));

        // Real-time updates
        add_action('wp_ajax_abt_refresh_citation_count', array($this, 'refresh_citation_count'));
        add_action('wp_ajax_abt_update_citation_order', array($this, 'update_citation_order'));
    }

    /**
     * Insert citation into post
     *
     * @since 1.0.0
     */
    public function insert_citation() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_citation_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $post_id = intval($_POST['post_id']);
        $reference_id = intval($_POST['reference_id']);
        $citation_type = sanitize_text_field($_POST['citation_type']);
        $page_numbers = sanitize_text_field($_POST['page_numbers']);
        $prefix = sanitize_text_field($_POST['prefix']);
        $suffix = sanitize_text_field($_POST['suffix']);

        if (empty($post_id) || empty($reference_id)) {
            wp_send_json_error('Post ID and Reference ID are required');
        }

        try {
            // Get existing citations for this post
            $existing_citations = get_post_meta($post_id, '_abt_citations', true);
            if (!is_array($existing_citations)) {
                $existing_citations = array();
            }

            // Create new citation data
            $citation_data = array(
                'reference_id' => $reference_id,
                'type' => $citation_type,
                'page_numbers' => $page_numbers,
                'prefix' => $prefix,
                'suffix' => $suffix,
                'created' => current_time('mysql')
            );

            // Add to citations array
            $existing_citations[] = $citation_data;

            // Update post meta
            update_post_meta($post_id, '_abt_citations', $existing_citations);

            // Generate citation text using the processor
            $processor = new ABT_Citation_Processor();
            $citation_text = $processor->format_citation($citation_data);

            // Get current citation style
            $citation_style = get_post_meta($post_id, '_abt_citation_style', true);
            if (empty($citation_style)) {
                $citation_style = 'apa'; // Default to APA
            }

            wp_send_json_success(array(
                'citation_text' => $citation_text,
                'citation_data' => $citation_data,
                'citation_count' => count($existing_citations),
                'message' => 'Citation inserted successfully'
            ));

        } catch (Exception $e) {
            wp_send_json_error('Error inserting citation: ' . $e->getMessage());
        }
    }

    /**
     * Update existing citation
     *
     * @since 1.0.0
     */
    public function update_citation() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_citation_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $post_id = intval($_POST['post_id']);
        $citation_index = intval($_POST['citation_index']);
        $citation_data = $_POST['citation_data'];

        if (empty($post_id) || !isset($citation_index)) {
            wp_send_json_error('Post ID and Citation Index are required');
        }

        try {
            // Get existing citations
            $existing_citations = get_post_meta($post_id, '_abt_citations', true);
            if (!is_array($existing_citations) || !isset($existing_citations[$citation_index])) {
                wp_send_json_error('Citation not found');
            }

            // Sanitize and update citation data
            $sanitized_data = array(
                'reference_id' => intval($citation_data['reference_id']),
                'type' => sanitize_text_field($citation_data['type']),
                'page_numbers' => sanitize_text_field($citation_data['page_numbers']),
                'prefix' => sanitize_text_field($citation_data['prefix']),
                'suffix' => sanitize_text_field($citation_data['suffix']),
                'created' => $existing_citations[$citation_index]['created'], // Keep original creation time
                'updated' => current_time('mysql')
            );

            // Update the citation
            $existing_citations[$citation_index] = $sanitized_data;
            update_post_meta($post_id, '_abt_citations', $existing_citations);

            // Generate updated citation text
            $processor = new ABT_Citation_Processor();
            $citation_text = $processor->format_citation($sanitized_data);

            wp_send_json_success(array(
                'citation_text' => $citation_text,
                'citation_data' => $sanitized_data,
                'message' => 'Citation updated successfully'
            ));

        } catch (Exception $e) {
            wp_send_json_error('Error updating citation: ' . $e->getMessage());
        }
    }

    /**
     * Remove citation from post
     *
     * @since 1.0.0
     */
    public function remove_citation() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_citation_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $post_id = intval($_POST['post_id']);
        $citation_index = intval($_POST['citation_index']);

        if (empty($post_id) || !isset($citation_index)) {
            wp_send_json_error('Post ID and Citation Index are required');
        }

        try {
            // Get existing citations
            $existing_citations = get_post_meta($post_id, '_abt_citations', true);
            if (!is_array($existing_citations) || !isset($existing_citations[$citation_index])) {
                wp_send_json_error('Citation not found');
            }

            // Remove the citation
            unset($existing_citations[$citation_index]);
            
            // Re-index array to maintain proper indexing
            $existing_citations = array_values($existing_citations);
            
            // Update post meta
            update_post_meta($post_id, '_abt_citations', $existing_citations);

            wp_send_json_success(array(
                'citation_count' => count($existing_citations),
                'message' => 'Citation removed successfully'
            ));

        } catch (Exception $e) {
            wp_send_json_error('Error removing citation: ' . $e->getMessage());
        }
    }

    /**
     * Get citation preview
     *
     * @since 1.0.0
     */
    public function get_citation_preview() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_citation_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }

        $reference_id = intval($_POST['reference_id']);
        $citation_style = sanitize_text_field($_POST['citation_style']);
        $citation_type = sanitize_text_field($_POST['citation_type']);

        if (empty($reference_id)) {
            wp_send_json_error('Reference ID is required');
        }

        try {
            $processor = new ABT_Citation_Processor();
            
            // Create temporary citation data for preview
            $citation_data = array(
                'reference_id' => $reference_id,
                'type' => $citation_type,
                'style' => $citation_style
            );

            $citation_preview = $processor->format_citation($citation_data);

            wp_send_json_success(array(
                'citation_preview' => $citation_preview
            ));

        } catch (Exception $e) {
            wp_send_json_error('Error generating preview: ' . $e->getMessage());
        }
    }

    /**
     * Generate bibliography for post
     *
     * @since 1.0.0
     */
    public function generate_bibliography() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_bibliography_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $post_id = intval($_POST['post_id']);
        $citation_style = sanitize_text_field($_POST['citation_style']);

        if (empty($post_id)) {
            wp_send_json_error('Post ID is required');
        }

        try {
            // Get citations for this post
            $citations = get_post_meta($post_id, '_abt_citations', true);
            if (empty($citations) || !is_array($citations)) {
                wp_send_json_error('No citations found for this post');
            }

            // Generate bibliography
            $processor = new ABT_Citation_Processor();
            $bibliography = $processor->generate_bibliography($citations, $citation_style);

            // Save bibliography to post meta
            update_post_meta($post_id, '_abt_bibliography', $bibliography);
            update_post_meta($post_id, '_abt_citation_style', $citation_style);

            wp_send_json_success(array(
                'bibliography' => $bibliography,
                'citation_count' => count($citations),
                'message' => 'Bibliography generated successfully'
            ));

        } catch (Exception $e) {
            wp_send_json_error('Error generating bibliography: ' . $e->getMessage());
        }
    }

    /**
     * Switch citation style
     *
     * @since 1.0.0
     */
    public function switch_citation_style() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_citation_style_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $post_id = intval($_POST['post_id']);
        $new_style = sanitize_text_field($_POST['citation_style']);

        if (empty($post_id) || empty($new_style)) {
            wp_send_json_error('Post ID and Citation Style are required');
        }

        $allowed_styles = array('apa', 'mla', 'chicago', 'harvard', 'ieee');
        if (!in_array($new_style, $allowed_styles)) {
            wp_send_json_error('Invalid citation style');
        }

        try {
            // Get citations for this post
            $citations = get_post_meta($post_id, '_abt_citations', true);
            
            if (!empty($citations) && is_array($citations)) {
                // Regenerate bibliography with new style
                $processor = new ABT_Citation_Processor();
                $bibliography = $processor->generate_bibliography($citations, $new_style);
                
                // Update post meta
                update_post_meta($post_id, '_abt_bibliography', $bibliography);
                update_post_meta($post_id, '_abt_citation_style', $new_style);

                wp_send_json_success(array(
                    'bibliography' => $bibliography,
                    'citation_style' => $new_style,
                    'message' => "Citation style changed to {$new_style}"
                ));
            } else {
                // Just update the style setting
                update_post_meta($post_id, '_abt_citation_style', $new_style);
                
                wp_send_json_success(array(
                    'citation_style' => $new_style,
                    'message' => "Citation style changed to {$new_style}"
                ));
            }

        } catch (Exception $e) {
            wp_send_json_error('Error switching citation style: ' . $e->getMessage());
        }
    }

    /**
     * Add footnote to post
     *
     * @since 1.0.0
     */
    public function add_footnote() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_footnote_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $post_id = intval($_POST['post_id']);
        $footnote_text = wp_kses_post($_POST['footnote_text']);
        $footnote_position = intval($_POST['footnote_position']);

        if (empty($post_id) || empty($footnote_text)) {
            wp_send_json_error('Post ID and footnote text are required');
        }

        try {
            // Get existing footnotes
            $footnotes = get_post_meta($post_id, '_abt_footnotes', true);
            if (!is_array($footnotes)) {
                $footnotes = array();
            }

            // Create new footnote
            $footnote_data = array(
                'text' => $footnote_text,
                'position' => $footnote_position,
                'created' => current_time('mysql')
            );

            // Add footnote
            $footnotes[] = $footnote_data;

            // Update post meta
            update_post_meta($post_id, '_abt_footnotes', $footnotes);

            wp_send_json_success(array(
                'footnote_data' => $footnote_data,
                'footnote_count' => count($footnotes),
                'footnote_index' => count($footnotes) - 1,
                'message' => 'Footnote added successfully'
            ));

        } catch (Exception $e) {
            wp_send_json_error('Error adding footnote: ' . $e->getMessage());
        }
    }

    /**
     * Update footnote
     *
     * @since 1.0.0
     */
    public function update_footnote() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_footnote_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $post_id = intval($_POST['post_id']);
        $footnote_index = intval($_POST['footnote_index']);
        $footnote_text = wp_kses_post($_POST['footnote_text']);

        if (empty($post_id) || !isset($footnote_index) || empty($footnote_text)) {
            wp_send_json_error('Post ID, footnote index, and footnote text are required');
        }

        try {
            // Get existing footnotes
            $footnotes = get_post_meta($post_id, '_abt_footnotes', true);
            if (!is_array($footnotes) || !isset($footnotes[$footnote_index])) {
                wp_send_json_error('Footnote not found');
            }

            // Update footnote text
            $footnotes[$footnote_index]['text'] = $footnote_text;
            $footnotes[$footnote_index]['updated'] = current_time('mysql');

            // Update post meta
            update_post_meta($post_id, '_abt_footnotes', $footnotes);

            wp_send_json_success(array(
                'footnote_data' => $footnotes[$footnote_index],
                'message' => 'Footnote updated successfully'
            ));

        } catch (Exception $e) {
            wp_send_json_error('Error updating footnote: ' . $e->getMessage());
        }
    }

    /**
     * Remove footnote
     *
     * @since 1.0.0
     */
    public function remove_footnote() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_footnote_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $post_id = intval($_POST['post_id']);
        $footnote_index = intval($_POST['footnote_index']);

        if (empty($post_id) || !isset($footnote_index)) {
            wp_send_json_error('Post ID and footnote index are required');
        }

        try {
            // Get existing footnotes
            $footnotes = get_post_meta($post_id, '_abt_footnotes', true);
            if (!is_array($footnotes) || !isset($footnotes[$footnote_index])) {
                wp_send_json_error('Footnote not found');
            }

            // Remove footnote
            unset($footnotes[$footnote_index]);
            
            // Re-index array
            $footnotes = array_values($footnotes);
            
            // Update post meta
            update_post_meta($post_id, '_abt_footnotes', $footnotes);

            wp_send_json_success(array(
                'footnote_count' => count($footnotes),
                'message' => 'Footnote removed successfully'
            ));

        } catch (Exception $e) {
            wp_send_json_error('Error removing footnote: ' . $e->getMessage());
        }
    }

    /**
     * Get available citation styles
     *
     * @since 1.0.0
     */
    public function get_citation_styles() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_citation_style_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }

        $style_manager = new ABT_Style_Manager();
        $available_styles = $style_manager->get_available_styles();

        wp_send_json_success(array(
            'styles' => $available_styles
        ));
    }

    /**
     * Refresh citation count for post
     *
     * @since 1.0.0
     */
    public function refresh_citation_count() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_citation_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }

        $post_id = intval($_POST['post_id']);

        if (empty($post_id)) {
            wp_send_json_error('Post ID is required');
        }

        $citations = get_post_meta($post_id, '_abt_citations', true);
        $footnotes = get_post_meta($post_id, '_abt_footnotes', true);

        $citation_count = is_array($citations) ? count($citations) : 0;
        $footnote_count = is_array($footnotes) ? count($footnotes) : 0;

        wp_send_json_success(array(
            'citation_count' => $citation_count,
            'footnote_count' => $footnote_count
        ));
    }

    /**
     * Validate citation data
     *
     * @since 1.0.0
     */
    public function validate_citation_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_citation_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }

        $citation_data = $_POST['citation_data'];
        $errors = array();

        // Validate reference ID
        if (empty($citation_data['reference_id'])) {
            $errors[] = 'Reference is required';
        } else {
            $reference = get_post($citation_data['reference_id']);
            if (!$reference || $reference->post_type !== 'abt_reference') {
                $errors[] = 'Invalid reference selected';
            }
        }

        // Validate citation type
        $allowed_types = array('in-text', 'footnote', 'bibliography');
        if (empty($citation_data['type']) || !in_array($citation_data['type'], $allowed_types)) {
            $errors[] = 'Valid citation type is required';
        }

        wp_send_json_success(array(
            'valid' => empty($errors),
            'errors' => $errors
        ));
    }
}