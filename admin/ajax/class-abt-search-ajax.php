<?php
/**
 * Search AJAX Operations Handler
 *
 * Handles all AJAX operations related to search functionality:
 * - Advanced search in references and posts
 * - Auto-suggestions and autocomplete
 * - Filter operations
 * - Search analytics
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
 * Search AJAX Handler Class
 */
class ABT_Search_Ajax {

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
        // Reference search
        add_action('wp_ajax_abt_search_references_advanced', array($this, 'search_references_advanced'));
        add_action('wp_ajax_abt_search_references_autocomplete', array($this, 'search_references_autocomplete'));
        add_action('wp_ajax_abt_filter_references_by_type', array($this, 'filter_references_by_type'));
        add_action('wp_ajax_abt_filter_references_by_year', array($this, 'filter_references_by_year'));

        // Blog post search
        add_action('wp_ajax_abt_search_blog_posts', array($this, 'search_blog_posts'));
        add_action('wp_ajax_abt_search_blog_posts_autocomplete', array($this, 'search_blog_posts_autocomplete'));
        add_action('wp_ajax_abt_filter_blog_posts', array($this, 'filter_blog_posts'));

        // Advanced filtering
        add_action('wp_ajax_abt_advanced_search', array($this, 'advanced_search'));
        add_action('wp_ajax_abt_get_search_filters', array($this, 'get_search_filters'));
        add_action('wp_ajax_abt_save_search_preset', array($this, 'save_search_preset'));

        // Frontend search (public)
        add_action('wp_ajax_abt_frontend_search', array($this, 'frontend_search'));
        add_action('wp_ajax_nopriv_abt_frontend_search', array($this, 'frontend_search'));
        add_action('wp_ajax_abt_frontend_autocomplete', array($this, 'frontend_autocomplete'));
        add_action('wp_ajax_nopriv_abt_frontend_autocomplete', array($this, 'frontend_autocomplete'));
        add_action('wp_ajax_abt_frontend_filter', array($this, 'frontend_filter'));
        add_action('wp_ajax_nopriv_abt_frontend_filter', array($this, 'frontend_filter'));
        add_action('wp_ajax_abt_load_more_posts', array($this, 'load_more_posts'));
        add_action('wp_ajax_nopriv_abt_load_more_posts', array($this, 'load_more_posts'));

        // Search analytics
        add_action('wp_ajax_abt_track_search', array($this, 'track_search'));
        add_action('wp_ajax_nopriv_abt_track_search', array($this, 'track_search'));
        add_action('wp_ajax_abt_get_search_stats', array($this, 'get_search_stats'));
        add_action('wp_ajax_abt_track_page_view', array($this, 'track_page_view'));
        add_action('wp_ajax_nopriv_abt_track_page_view', array($this, 'track_page_view'));
    }

    /**
     * Advanced reference search
     *
     * @since 1.0.0
     */
    public function search_references_advanced() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_search_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }

        $search_params = array(
            'search_term' => sanitize_text_field($_POST['search_term']),
            'reference_type' => sanitize_text_field($_POST['reference_type']),
            'year_from' => intval($_POST['year_from']),
            'year_to' => intval($_POST['year_to']),
            'author' => sanitize_text_field($_POST['author']),
            'journal' => sanitize_text_field($_POST['journal']),
            'category' => sanitize_text_field($_POST['category']),
            'sort_by' => sanitize_text_field($_POST['sort_by']),
            'sort_order' => sanitize_text_field($_POST['sort_order']),
            'page' => intval($_POST['page']),
            'per_page' => intval($_POST['per_page'])
        );

        try {
            $results = $this->perform_advanced_reference_search($search_params);

            wp_send_json_success(array(
                'references' => $results['references'],
                'total_found' => $results['total_found'],
                'total_pages' => $results['total_pages'],
                'current_page' => $search_params['page'],
                'search_params' => $search_params
            ));

        } catch (Exception $e) {
            wp_send_json_error('Search error: ' . $e->getMessage());
        }
    }

    /**
     * Reference autocomplete search
     *
     * @since 1.0.0
     */
    public function search_references_autocomplete() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_autocomplete_nonce')) {
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

        try {
            $suggestions = $this->get_reference_suggestions($search_term, $limit);

            wp_send_json_success(array(
                'suggestions' => $suggestions
            ));

        } catch (Exception $e) {
            wp_send_json_error('Autocomplete error: ' . $e->getMessage());
        }
    }

    /**
     * Filter references by type
     *
     * @since 1.0.0
     */
    public function filter_references_by_type() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_filter_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }

        $reference_type = sanitize_text_field($_POST['reference_type']);
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;

        $args = array(
            'post_type' => 'abt_reference',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'title',
            'order' => 'ASC'
        );

        if (!empty($reference_type) && $reference_type !== 'all') {
            $args['meta_query'] = array(
                array(
                    'key' => '_abt_type',
                    'value' => $reference_type,
                    'compare' => '='
                )
            );
        }

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
                    'type' => get_post_meta($reference_id, '_abt_type', true),
                    'year' => get_post_meta($reference_id, '_abt_year', true),
                    'journal' => get_post_meta($reference_id, '_abt_journal', true)
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
     * Search blog posts
     *
     * @since 1.0.0
     */
    public function search_blog_posts() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_search_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }

        $search_term = sanitize_text_field($_POST['search_term']);
        $subject = sanitize_text_field($_POST['subject']);
        $category = sanitize_text_field($_POST['category']);
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        $page = intval($_POST['page']);
        $per_page = intval($_POST['per_page']);

        $args = array(
            'post_type' => 'abt_blog',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            's' => $search_term
        );

        // Add taxonomy filters
        $tax_query = array();

        if (!empty($subject)) {
            $tax_query[] = array(
                'taxonomy' => 'abt_subject',
                'field' => 'slug',
                'terms' => $subject
            );
        }

        if (!empty($category)) {
            $tax_query[] = array(
                'taxonomy' => 'abt_blog_category',
                'field' => 'slug',
                'terms' => $category
            );
        }

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        // Add date filters
        if (!empty($date_from) || !empty($date_to)) {
            $date_query = array();
            
            if (!empty($date_from)) {
                $date_query['after'] = $date_from;
            }
            
            if (!empty($date_to)) {
                $date_query['before'] = $date_to;
            }
            
            if (!empty($date_query)) {
                $args['date_query'] = array($date_query);
            }
        }

        $query = new WP_Query($args);
        $posts = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $posts[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'date' => get_the_date('Y-m-d'),
                    'author' => get_the_author(),
                    'subjects' => wp_get_post_terms($post_id, 'abt_subject', array('fields' => 'names')),
                    'categories' => wp_get_post_terms($post_id, 'abt_blog_category', array('fields' => 'names')),
                    'citation_count' => count(get_post_meta($post_id, '_abt_citations', true) ?: array()),
                    'permalink' => get_permalink($post_id)
                );
            }
            wp_reset_postdata();
        }

        wp_send_json_success(array(
            'posts' => $posts,
            'total_found' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'current_page' => $page
        ));
    }

    /**
     * Frontend search (public facing)
     *
     * @since 1.0.0
     */
    public function frontend_search() {
        $search_term = sanitize_text_field($_POST['search_term']);
        $post_type = sanitize_text_field($_POST['post_type']);
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;

        if (empty($search_term)) {
            wp_send_json_error('Search term is required');
        }

        // Validate post type
        $allowed_post_types = array('abt_blog', 'abt_reference');
        if (!in_array($post_type, $allowed_post_types)) {
            $post_type = 'abt_blog'; // Default to blog posts
        }

        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            's' => $search_term
        );

        $query = new WP_Query($args);
        $results = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                if ($post_type === 'abt_blog') {
                    $results[] = array(
                        'id' => $post_id,
                        'title' => get_the_title(),
                        'excerpt' => get_the_excerpt(),
                        'permalink' => get_permalink($post_id),
                        'date' => get_the_date('Y-m-d'),
                        'author' => get_the_author(),
                        'subjects' => wp_get_post_terms($post_id, 'abt_subject', array('fields' => 'names'))
                    );
                } else {
                    $results[] = array(
                        'id' => $post_id,
                        'title' => get_the_title(),
                        'authors' => get_post_meta($post_id, '_abt_authors', true),
                        'year' => get_post_meta($post_id, '_abt_year', true),
                        'type' => get_post_meta($post_id, '_abt_type', true),
                        'journal' => get_post_meta($post_id, '_abt_journal', true)
                    );
                }
            }
            wp_reset_postdata();
        }

        wp_send_json_success(array(
            'results' => $results,
            'total_found' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'current_page' => $page,
            'post_type' => $post_type
        ));
    }

    /**
     * Frontend autocomplete
     *
     * @since 1.0.0
     */
    public function frontend_autocomplete() {
        $search_term = sanitize_text_field($_POST['search_term']);
        $post_type = sanitize_text_field($_POST['post_type']);
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;

        if (strlen($search_term) < 2) {
            wp_send_json_success(array('suggestions' => array()));
        }

        // Validate post type
        $allowed_post_types = array('abt_blog', 'abt_reference');
        if (!in_array($post_type, $allowed_post_types)) {
            $post_type = 'abt_blog';
        }

        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            's' => $search_term,
            'fields' => 'ids'
        );

        $post_ids = get_posts($args);
        $suggestions = array();

        foreach ($post_ids as $post_id) {
            $title = get_the_title($post_id);
            
            if ($post_type === 'abt_blog') {
                $suggestions[] = array(
                    'id' => $post_id,
                    'label' => $title,
                    'url' => get_permalink($post_id),
                    'type' => 'blog_post'
                );
            } else {
                $authors = get_post_meta($post_id, '_abt_authors', true);
                $year = get_post_meta($post_id, '_abt_year', true);
                $label = $this->format_reference_label($authors, $title, $year);
                
                $suggestions[] = array(
                    'id' => $post_id,
                    'label' => $label,
                    'type' => 'reference'
                );
            }
        }

        wp_send_json_success(array('suggestions' => $suggestions));
    }

    /**
     * Frontend filter
     *
     * @since 1.0.0
     */
    public function frontend_filter() {
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        $post_type = sanitize_text_field($_POST['post_type']);
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;

        // Validate post type
        $allowed_post_types = array('abt_blog', 'abt_reference');
        if (!in_array($post_type, $allowed_post_types)) {
            $post_type = 'abt_blog';
        }

        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page
        );

        // Apply filters
        $this->apply_filters_to_query($args, $filters);

        $query = new WP_Query($args);
        $results = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                if ($post_type === 'abt_blog') {
                    $results[] = array(
                        'id' => $post_id,
                        'title' => get_the_title(),
                        'excerpt' => get_the_excerpt(),
                        'permalink' => get_permalink($post_id),
                        'date' => get_the_date('Y-m-d'),
                        'author' => get_the_author(),
                        'subjects' => wp_get_post_terms($post_id, 'abt_subject', array('fields' => 'names'))
                    );
                }
            }
            wp_reset_postdata();
        }

        wp_send_json_success(array(
            'results' => $results,
            'total_found' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'current_page' => $page,
            'facets' => $this->get_filter_facets($post_type)
        ));
    }

    /**
     * Load more posts for infinite scroll
     *
     * @since 1.0.0
     */
    public function load_more_posts() {
        $page = intval($_POST['page']);
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        $post_type = sanitize_text_field($_POST['post_type']);
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;

        if ($page < 1) {
            wp_send_json_error('Invalid page number');
        }

        $args = array(
            'post_type' => $post_type ?: 'abt_blog',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page
        );

        // Apply filters
        $this->apply_filters_to_query($args, $filters);

        $query = new WP_Query($args);
        $html = '';

        if ($query->have_posts()) {
            ob_start();
            while ($query->have_posts()) {
                $query->the_post();
                
                // Load the appropriate template part
                if ($post_type === 'abt_blog') {
                    get_template_part('template-parts/content', 'abt-blog-excerpt');
                } else {
                    get_template_part('template-parts/content', 'abt-reference');
                }
            }
            $html = ob_get_clean();
            wp_reset_postdata();
        }

        wp_send_json_success(array(
            'posts' => array(), // Could add post data here if needed
            'html' => $html,
            'has_more' => $page < $query->max_num_pages,
            'total_pages' => $query->max_num_pages
        ));
    }

    /**
     * Advanced search across multiple content types
     *
     * @since 1.0.0
     */
    public function advanced_search() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_advanced_search_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }

        $search_params = array(
            'search_term' => sanitize_text_field($_POST['search_term']),
            'content_types' => array_map('sanitize_text_field', $_POST['content_types']),
            'subjects' => array_map('sanitize_text_field', $_POST['subjects']),
            'categories' => array_map('sanitize_text_field', $_POST['categories']),
            'date_from' => sanitize_text_field($_POST['date_from']),
            'date_to' => sanitize_text_field($_POST['date_to']),
            'author' => sanitize_text_field($_POST['author']),
            'sort_by' => sanitize_text_field($_POST['sort_by']),
            'sort_order' => sanitize_text_field($_POST['sort_order'])
        );

        try {
            $results = $this->perform_advanced_search($search_params);

            wp_send_json_success(array(
                'results' => $results['results'],
                'total_found' => $results['total_found'],
                'search_params' => $search_params,
                'facets' => $results['facets']
            ));

        } catch (Exception $e) {
            wp_send_json_error('Advanced search error: ' . $e->getMessage());
        }
    }

    /**
     * Get available search filters
     *
     * @since 1.0.0
     */
    public function get_search_filters() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_filters_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }

        $filters = array(
            'subjects' => $this->get_available_subjects(),
            'categories' => $this->get_available_categories(),
            'reference_types' => $this->get_available_reference_types(),
            'years' => $this->get_available_years(),
            'authors' => $this->get_available_authors()
        );

        wp_send_json_success(array('filters' => $filters));
    }

    /**
     * Track search for analytics
     *
     * @since 1.0.0
     */
    public function track_search() {
        $search_term = sanitize_text_field($_POST['search_term']);
        $result_count = intval($_POST['result_count']);
        $search_type = sanitize_text_field($_POST['search_type']);

        if (empty($search_term)) {
            wp_send_json_error('Search term is required');
        }

        // Store search analytics
        $search_data = array(
            'term' => $search_term,
            'results' => $result_count,
            'type' => $search_type,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip()
        );

        // Save to options table (could be enhanced with custom table)
        $searches = get_option('abt_search_analytics', array());
        $searches[] = $search_data;

        // Keep only last 1000 searches
        if (count($searches) > 1000) {
            $searches = array_slice($searches, -1000);
        }

        update_option('abt_search_analytics', $searches);

        wp_send_json_success(array('tracked' => true));
    }

    /**
     * Track page view
     *
     * @since 1.0.0
     */
    public function track_page_view() {
        $post_id = intval($_POST['post_id']);

        if (!$post_id) {
            wp_send_json_error('Post ID is required');
        }

        // Increment view count
        $view_count = get_post_meta($post_id, '_abt_view_count', true);
        $view_count = $view_count ? intval($view_count) + 1 : 1;
        update_post_meta($post_id, '_abt_view_count', $view_count);

        // Store detailed view data
        $view_data = array(
            'post_id' => $post_id,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        // Save view analytics
        $views = get_option('abt_view_analytics', array());
        $views[] = $view_data;

        // Keep only last 5000 views
        if (count($views) > 5000) {
            $views = array_slice($views, -5000);
        }

        update_option('abt_view_analytics', $views);

        wp_send_json_success(array(
            'tracked' => true,
            'view_count' => $view_count
        ));
    }

    /**
     * Get search statistics
     *
     * @since 1.0.0
     */
    public function get_search_stats() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abt_stats_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $searches = get_option('abt_search_analytics', array());
        
        // Calculate statistics
        $stats = array(
            'total_searches' => count($searches),
            'unique_terms' => count(array_unique(array_column($searches, 'term'))),
            'top_searches' => $this->get_top_search_terms($searches),
            'search_trends' => $this->get_search_trends($searches),
            'zero_result_searches' => $this->get_zero_result_searches($searches)
        );

        wp_send_json_success(array('stats' => $stats));
    }

    /**
     * Apply filters to query arguments
     *
     * @param array $args Query arguments
     * @param array $filters Filter values
     * @since 1.0.0
     */
    private function apply_filters_to_query(&$args, $filters) {
        $tax_query = array();
        $meta_query = array();

        // Subject filter
        if (!empty($filters['subject'])) {
            $tax_query[] = array(
                'taxonomy' => 'abt_subject',
                'field' => 'slug',
                'terms' => $filters['subject']
            );
        }

        // Category filter
        if (!empty($filters['category'])) {
            $tax_query[] = array(
                'taxonomy' => 'abt_blog_category',
                'field' => 'slug',
                'terms' => $filters['category']
            );
        }

        // Date filters
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $date_query = array();
            
            if (!empty($filters['date_from'])) {
                $date_query['after'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $date_query['before'] = $filters['date_to'];
            }
            
            if (!empty($date_query)) {
                $args['date_query'] = array($date_query);
            }
        }

        // Apply taxonomy queries
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        // Apply meta queries
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        // Apply sorting
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'date_desc':
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
                    break;
                case 'date_asc':
                    $args['orderby'] = 'date';
                    $args['order'] = 'ASC';
                    break;
                case 'title_asc':
                    $args['orderby'] = 'title';
                    $args['order'] = 'ASC';
                    break;
                case 'title_desc':
                    $args['orderby'] = 'title';
                    $args['order'] = 'DESC';
                    break;
            }
        }
    }

    /**
     * Get filter facets for current query
     *
     * @param string $post_type Post type
     * @return array Filter facets
     * @since 1.0.0
     */
    private function get_filter_facets($post_type) {
        $facets = array();

        if ($post_type === 'abt_blog') {
            $facets['subjects'] = $this->get_available_subjects();
            $facets['categories'] = $this->get_available_categories();
        }

        return $facets;
    }

    /**
     * Perform advanced reference search
     *
     * @param array $params Search parameters
     * @return array Search results
     * @since 1.0.0
     */
    private function perform_advanced_reference_search($params) {
        $args = array(
            'post_type' => 'abt_reference',
            'post_status' => 'publish',
            'posts_per_page' => $params['per_page'],
            'paged' => $params['page']
        );

        // Add search term
        if (!empty($params['search_term'])) {
            $args['s'] = $params['search_term'];
        }

        // Add meta queries
        $meta_query = array();

        if (!empty($params['reference_type'])) {
            $meta_query[] = array(
                'key' => '_abt_type',
                'value' => $params['reference_type'],
                'compare' => '='
            );
        }

        if (!empty($params['author'])) {
            $meta_query[] = array(
                'key' => '_abt_authors',
                'value' => $params['author'],
                'compare' => 'LIKE'
            );
        }

        if (!empty($params['journal'])) {
            $meta_query[] = array(
                'key' => '_abt_journal',
                'value' => $params['journal'],
                'compare' => 'LIKE'
            );
        }

        // Year range filter
        if (!empty($params['year_from']) || !empty($params['year_to'])) {
            $year_query = array('key' => '_abt_year');
            
            if (!empty($params['year_from']) && !empty($params['year_to'])) {
                $year_query['value'] = array($params['year_from'], $params['year_to']);
                $year_query['compare'] = 'BETWEEN';
                $year_query['type'] = 'NUMERIC';
            } elseif (!empty($params['year_from'])) {
                $year_query['value'] = $params['year_from'];
                $year_query['compare'] = '>=';
                $year_query['type'] = 'NUMERIC';
            } elseif (!empty($params['year_to'])) {
                $year_query['value'] = $params['year_to'];
                $year_query['compare'] = '<=';
                $year_query['type'] = 'NUMERIC';
            }
            
            $meta_query[] = $year_query;
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        // Add sorting
        if (!empty($params['sort_by'])) {
            switch ($params['sort_by']) {
                case 'title':
                    $args['orderby'] = 'title';
                    break;
                case 'date':
                    $args['orderby'] = 'date';
                    break;
                case 'year':
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_key'] = '_abt_year';
                    break;
                case 'author':
                    $args['orderby'] = 'meta_value';
                    $args['meta_key'] = '_abt_authors';
                    break;
                default:
                    $args['orderby'] = 'title';
            }
            
            $args['order'] = ($params['sort_order'] === 'desc') ? 'DESC' : 'ASC';
        }

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
                    'year' => get_post_meta($reference_id, '_abt_year', true),
                    'type' => get_post_meta($reference_id, '_abt_type', true),
                    'journal' => get_post_meta($reference_id, '_abt_journal', true),
                    'publisher' => get_post_meta($reference_id, '_abt_publisher', true),
                    'doi' => get_post_meta($reference_id, '_abt_doi', true),
                    'url' => get_post_meta($reference_id, '_abt_url', true),
                    'usage_count' => $this->get_reference_usage_count($reference_id)
                );
            }
            wp_reset_postdata();
        }

        return array(
            'references' => $references,
            'total_found' => $query->found_posts,
            'total_pages' => $query->max_num_pages
        );
    }

    /**
     * Get reference suggestions for autocomplete
     *
     * @param string $search_term Search term
     * @param int $limit Number of suggestions
     * @return array Suggestions
     * @since 1.0.0
     */
    private function get_reference_suggestions($search_term, $limit) {
        $args = array(
            'post_type' => 'abt_reference',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            's' => $search_term,
            'fields' => 'ids'
        );

        $reference_ids = get_posts($args);
        $suggestions = array();

        foreach ($reference_ids as $reference_id) {
            $title = get_the_title($reference_id);
            $authors = get_post_meta($reference_id, '_abt_authors', true);
            $year = get_post_meta($reference_id, '_abt_year', true);
            
            $suggestions[] = array(
                'id' => $reference_id,
                'label' => $this->format_reference_label($authors, $title, $year),
                'value' => $reference_id,
                'title' => $title,
                'authors' => $authors,
                'year' => $year
            );
        }

        return $suggestions;
    }

    /**
     * Get available subjects for filtering
     *
     * @return array Available subjects
     * @since 1.0.0
     */
    private function get_available_subjects() {
        $terms = get_terms(array(
            'taxonomy' => 'abt_subject',
            'hide_empty' => true
        ));

        $subjects = array();
        foreach ($terms as $term) {
            $subjects[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count
            );
        }

        return $subjects;
    }

    /**
     * Get available categories for filtering
     *
     * @return array Available categories
     * @since 1.0.0
     */
    private function get_available_categories() {
        $terms = get_terms(array(
            'taxonomy' => 'abt_blog_category',
            'hide_empty' => true
        ));

        $categories = array();
        foreach ($terms as $term) {
            $categories[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count
            );
        }

        return $categories;
    }

    /**
     * Get available reference types
     *
     * @return array Available reference types
     * @since 1.0.0
     */
    private function get_available_reference_types() {
        global $wpdb;

        $types = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_abt_type' 
            AND meta_value != ''
            ORDER BY meta_value
        ");

        return array_values($types);
    }

    /**
     * Get available years
     *
     * @return array Available years
     * @since 1.0.0
     */
    private function get_available_years() {
        global $wpdb;

        $years = $wpdb->get_col("
            SELECT DISTINCT CAST(meta_value AS UNSIGNED) as year
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_abt_year' 
            AND meta_value REGEXP '^[0-9]{4}$'
            ORDER BY year DESC
        ");

        return array_map('intval', $years);
    }

    /**
     * Get available authors
     *
     * @return array Available authors
     * @since 1.0.0
     */
    private function get_available_authors() {
        global $wpdb;

        $authors = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_abt_authors' 
            AND meta_value != ''
            ORDER BY meta_value
        ");

        return array_values($authors);
    }

    /**
     * Format reference label for suggestions
     *
     * @param string $authors Authors
     * @param string $title Title
     * @param string $year Year
     * @return string Formatted label
     * @since 1.0.0
     */
    private function format_reference_label($authors, $title, $year) {
        $parts = array();
        
        if (!empty($authors)) {
            $authors_array = explode(',', $authors);
            $first_author = trim($authors_array[0]);
            if (count($authors_array) > 1) {
                $first_author .= ' et al.';
            }
            $parts[] = $first_author;
        }
        
        if (!empty($year)) {
            $parts[] = "({$year})";
        }
        
        if (!empty($title)) {
            $parts[] = wp_trim_words($title, 8);
        }

        return implode(' ', $parts);
    }

    /**
     * Get reference usage count
     *
     * @param int $reference_id Reference ID
     * @return int Usage count
     * @since 1.0.0
     */
    private function get_reference_usage_count($reference_id) {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
             WHERE meta_key = '_abt_citations' 
             AND meta_value LIKE %s",
            '%' . $wpdb->esc_like($reference_id) . '%'
        ));

        return intval($count);
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     * @since 1.0.0
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    /**
     * Get top search terms
     *
     * @param array $searches Search data
     * @return array Top search terms
     * @since 1.0.0
     */
    private function get_top_search_terms($searches) {
        $terms = array();
        
        foreach ($searches as $search) {
            $term = $search['term'];
            if (!isset($terms[$term])) {
                $terms[$term] = 0;
            }
            $terms[$term]++;
        }
        
        arsort($terms);
        return array_slice($terms, 0, 10);
    }

    /**
     * Get search trends over time
     *
     * @param array $searches Search data
     * @return array Search trends
     * @since 1.0.0
     */
    private function get_search_trends($searches) {
        $trends = array();
        
        foreach ($searches as $search) {
            $date = date('Y-m-d', strtotime($search['timestamp']));
            if (!isset($trends[$date])) {
                $trends[$date] = 0;
            }
            $trends[$date]++;
        }
        
        ksort($trends);
        return $trends;
    }

    /**
     * Get zero result searches
     *
     * @param array $searches Search data
     * @return array Zero result searches
     * @since 1.0.0
     */
    private function get_zero_result_searches($searches) {
        $zero_results = array();
        
        foreach ($searches as $search) {
            if ($search['results'] == 0) {
                $term = $search['term'];
                if (!isset($zero_results[$term])) {
                    $zero_results[$term] = 0;
                }
                $zero_results[$term]++;
            }
        }
        
        arsort($zero_results);
        return array_slice($zero_results, 0, 10);
    }

    /**
     * Perform advanced search across multiple content types
     *
     * @param array $params Search parameters
     * @return array Search results
     * @since 1.0.0
     */
    private function perform_advanced_search($params) {
        $results = array();
        $total_found = 0;

        // Search in blog posts if requested
        if (in_array('abt_blog', $params['content_types'])) {
            $blog_results = $this->search_blog_posts_advanced($params);
            $results['blog_posts'] = $blog_results['posts'];
            $total_found += $blog_results['total_found'];
        }

        // Search in references if requested
        if (in_array('abt_reference', $params['content_types'])) {
            $ref_results = $this->search_references_advanced_internal($params);
            $results['references'] = $ref_results['references'];
            $total_found += $ref_results['total_found'];
        }

        return array(
            'results' => $results,
            'total_found' => $total_found,
            'facets' => $this->get_search_facets($params)
        );
    }

    /**
     * Get search facets for advanced search
     *
     * @param array $params Search parameters
     * @return array Search facets
     * @since 1.0.0
     */
    private function get_search_facets($params) {
        return array(
            'subjects' => $this->get_available_subjects(),
            'categories' => $this->get_available_categories(),
            'reference_types' => $this->get_available_reference_types(),
            'years' => $this->get_available_years()
        );
    }
}