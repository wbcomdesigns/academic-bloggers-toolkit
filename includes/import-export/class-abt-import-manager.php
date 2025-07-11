<?php
/**
 * Import Manager - Citation Format Parser
 *
 * Handles importing references from various formats (RIS, BibTeX, CSV)
 *
 * @package Academic_Bloggers_Toolkit
 * @subpackage Import_Export
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Import Manager Class
 *
 * Orchestrates importing references from various file formats
 */
class ABT_Import_Manager {

    /**
     * Supported import formats
     *
     * @var array
     */
    private $supported_formats = ['ris', 'bibtex', 'csv', 'json'];

    /**
     * Import parsers
     *
     * @var array
     */
    private $parsers = [];

    /**
     * Import statistics
     *
     * @var array
     */
    private $import_stats = [
        'total_processed' => 0,
        'successful_imports' => 0,
        'failed_imports' => 0,
        'duplicates_found' => 0,
        'errors' => []
    ];

    /**
     * Initialize the import manager
     */
    public function __construct() {
        $this->init_parsers();
    }

    /**
     * Initialize format parsers
     */
    private function init_parsers() {
        $this->parsers = [
            'ris' => new ABT_RIS_Parser(),
            'bibtex' => new ABT_BibTeX_Parser(),
            'csv' => new ABT_CSV_Handler(),
            'json' => [$this, 'parse_json']
        ];
    }

    /**
     * Import citations from file
     *
     * @param string $file_path Path to import file
     * @param array $options Import options
     * @return array|WP_Error Import results or error
     */
    public function import_from_file($file_path, $options = []) {
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', __('Import file not found', 'academic-bloggers-toolkit'));
        }

        // Detect file format
        $format = $this->detect_format($file_path);
        
        if (!$format) {
            return new WP_Error('unknown_format', __('Unable to detect file format', 'academic-bloggers-toolkit'));
        }

        return $this->import_from_data(file_get_contents($file_path), $format, $options);
    }

    /**
     * Import citations from data string
     *
     * @param string $data Import data
     * @param string $format Data format
     * @param array $options Import options
     * @return array|WP_Error Import results or error
     */
    public function import_from_data($data, $format, $options = []) {
        if (!in_array($format, $this->supported_formats)) {
            return new WP_Error('unsupported_format', sprintf(__('Unsupported format: %s', 'academic-bloggers-toolkit'), $format));
        }

        // Reset statistics
        $this->reset_import_stats();

        // Parse default options
        $default_options = [
            'check_duplicates' => true,
            'update_existing' => false,
            'default_status' => 'publish',
            'assign_to_user' => get_current_user_id(),
            'batch_size' => 50,
            'validate_data' => true
        ];

        $options = wp_parse_args($options, $default_options);

        // Parse the data
        $parsed_references = $this->parse_data($data, $format);

        if (is_wp_error($parsed_references)) {
            return $parsed_references;
        }

        if (empty($parsed_references)) {
            return new WP_Error('no_data', __('No valid references found in import data', 'academic-bloggers-toolkit'));
        }

        $this->import_stats['total_processed'] = count($parsed_references);

        // Process references in batches
        $imported_references = [];
        $batches = array_chunk($parsed_references, $options['batch_size']);

        foreach ($batches as $batch) {
            $batch_results = $this->process_reference_batch($batch, $options);
            $imported_references = array_merge($imported_references, $batch_results);

            // Prevent memory issues
            if (function_exists('wp_suspend_cache_addition')) {
                wp_suspend_cache_addition(true);
            }
        }

        return [
            'imported_references' => $imported_references,
            'statistics' => $this->import_stats,
            'format' => $format,
            'options_used' => $options
        ];
    }

    /**
     * Detect file format
     *
     * @param string $file_path File path
     * @return string|false Format name or false if unknown
     */
    private function detect_format($file_path) {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        // Check by extension first
        $extension_mapping = [
            'ris' => 'ris',
            'bib' => 'bibtex',
            'bibtex' => 'bibtex',
            'csv' => 'csv',
            'json' => 'json'
        ];

        if (isset($extension_mapping[$extension])) {
            return $extension_mapping[$extension];
        }

        // Check by content
        $content = file_get_contents($file_path, false, null, 0, 1000); // Read first 1KB
        
        if (strpos($content, 'TY  -') !== false) {
            return 'ris';
        }
        
        if (strpos($content, '@') !== false && preg_match('/@\w+\{/', $content)) {
            return 'bibtex';
        }
        
        if (preg_match('/^[^,\r\n]+,/', $content)) {
            return 'csv';
        }
        
        $json_data = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
            return 'json';
        }

        return false;
    }

    /**
     * Parse data using appropriate parser
     *
     * @param string $data Data to parse
     * @param string $format Data format
     * @return array|WP_Error Parsed references or error
     */
    private function parse_data($data, $format) {
        if (!isset($this->parsers[$format])) {
            return new WP_Error('no_parser', sprintf(__('No parser available for format: %s', 'academic-bloggers-toolkit'), $format));
        }

        $parser = $this->parsers[$format];

        try {
            if (is_callable($parser)) {
                return call_user_func($parser, $data);
            } else {
                return $parser->parse($data);
            }
        } catch (Exception $e) {
            return new WP_Error('parse_error', sprintf(__('Parse error: %s', 'academic-bloggers-toolkit'), $e->getMessage()));
        }
    }

    /**
     * Process batch of references
     *
     * @param array $references Reference data batch
     * @param array $options Import options
     * @return array Imported reference IDs
     */
    private function process_reference_batch($references, $options) {
        $imported_ids = [];

        foreach ($references as $reference_data) {
            $result = $this->process_single_reference($reference_data, $options);
            
            if (is_wp_error($result)) {
                $this->import_stats['failed_imports']++;
                $this->import_stats['errors'][] = [
                    'reference' => $reference_data['title'] ?? 'Unknown',
                    'error' => $result->get_error_message()
                ];
            } else {
                if ($result['action'] === 'imported') {
                    $this->import_stats['successful_imports']++;
                    $imported_ids[] = $result['post_id'];
                } elseif ($result['action'] === 'duplicate') {
                    $this->import_stats['duplicates_found']++;
                }
            }
        }

        return $imported_ids;
    }

    /**
     * Process single reference
     *
     * @param array $reference_data Reference data
     * @param array $options Import options
     * @return array|WP_Error Processing result or error
     */
    private function process_single_reference($reference_data, $options) {
        // Validate reference data
        if ($options['validate_data']) {
            $validation_result = $this->validate_reference_data($reference_data);
            if (is_wp_error($validation_result)) {
                return $validation_result;
            }
        }

        // Check for duplicates
        if ($options['check_duplicates']) {
            $existing_post = $this->find_duplicate_reference($reference_data);
            
            if ($existing_post) {
                if ($options['update_existing']) {
                    return $this->update_existing_reference($existing_post->ID, $reference_data);
                } else {
                    return ['action' => 'duplicate', 'post_id' => $existing_post->ID];
                }
            }
        }

        // Create new reference
        return $this->create_new_reference($reference_data, $options);
    }

    /**
     * Validate reference data
     *
     * @param array $reference_data Reference data
     * @return bool|WP_Error True if valid, error if invalid
     */
    private function validate_reference_data($reference_data) {
        // Required fields
        $required_fields = ['title'];
        
        foreach ($required_fields as $field) {
            if (!isset($reference_data[$field]) || empty(trim($reference_data[$field]))) {
                return new WP_Error('missing_required_field', sprintf(__('Missing required field: %s', 'academic-bloggers-toolkit'), $field));
            }
        }

        // Validate authors format
        if (isset($reference_data['author']) && !empty($reference_data['author'])) {
            if (!is_array($reference_data['author']) && !is_string($reference_data['author'])) {
                return new WP_Error('invalid_author_format', __('Invalid author format', 'academic-bloggers-toolkit'));
            }
        }

        // Validate dates
        if (isset($reference_data['issued']) && !empty($reference_data['issued'])) {
            if (!$this->validate_date_format($reference_data['issued'])) {
                return new WP_Error('invalid_date_format', __('Invalid date format', 'academic-bloggers-toolkit'));
            }
        }

        return true;
    }

    /**
     * Validate date format
     *
     * @param mixed $date Date data
     * @return bool True if valid format
     */
    private function validate_date_format($date) {
        if (is_string($date)) {
            return strtotime($date) !== false;
        }
        
        if (is_array($date) && isset($date['date-parts'][0][0])) {
            return is_numeric($date['date-parts'][0][0]);
        }

        return false;
    }

    /**
     * Find duplicate reference
     *
     * @param array $reference_data Reference data
     * @return WP_Post|false Existing post or false if not found
     */
    private function find_duplicate_reference($reference_data) {
        $search_criteria = [];

        // Search by DOI first (most reliable)
        if (!empty($reference_data['DOI'])) {
            $posts = get_posts([
                'post_type' => 'abt_reference',
                'meta_query' => [
                    [
                        'key' => '_abt_doi',
                        'value' => $reference_data['DOI'],
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1
            ]);
            
            if (!empty($posts)) {
                return $posts[0];
            }
        }

        // Search by title and first author
        if (!empty($reference_data['title'])) {
            $title = sanitize_text_field($reference_data['title']);
            
            $posts = get_posts([
                'post_type' => 'abt_reference',
                'title' => $title,
                'posts_per_page' => 5
            ]);

            // If we found posts with same title, check author match
            if (!empty($posts) && !empty($reference_data['author'])) {
                $first_author = $this->extract_first_author($reference_data['author']);
                
                foreach ($posts as $post) {
                    $existing_author = get_post_meta($post->ID, '_abt_author', true);
                    $existing_first_author = $this->extract_first_author($existing_author);
                    
                    if ($this->authors_match($first_author, $existing_first_author)) {
                        return $post;
                    }
                }
            } elseif (!empty($posts)) {
                // Return first match if no author to compare
                return $posts[0];
            }
        }

        return false;
    }

    /**
     * Extract first author from author data
     *
     * @param mixed $author_data Author data
     * @return string First author name
     */
    private function extract_first_author($author_data) {
        if (empty($author_data)) {
            return '';
        }

        if (is_string($author_data)) {
            // Split by common separators and get first
            $authors = preg_split('/[;,]|\band\b|\&/', $author_data);
            return trim($authors[0]);
        }

        if (is_array($author_data)) {
            $first_author = $author_data[0];
            
            if (is_array($first_author)) {
                return trim(($first_author['given'] ?? '') . ' ' . ($first_author['family'] ?? ''));
            } else {
                return trim($first_author);
            }
        }

        return '';
    }

    /**
     * Check if two author names match
     *
     * @param string $author1 First author name
     * @param string $author2 Second author name
     * @return bool True if they match
     */
    private function authors_match($author1, $author2) {
        if (empty($author1) || empty($author2)) {
            return false;
        }

        // Normalize names for comparison
        $name1 = strtolower(preg_replace('/[^a-zA-Z\s]/', '', $author1));
        $name2 = strtolower(preg_replace('/[^a-zA-Z\s]/', '', $author2));

        // Exact match
        if ($name1 === $name2) {
            return true;
        }

        // Check if last names match (handle "First Last" vs "Last, First" formats)
        $parts1 = explode(' ', $name1);
        $parts2 = explode(' ', $name2);
        
        $last1 = end($parts1);
        $last2 = end($parts2);

        return $last1 === $last2 && strlen($last1) > 2; // Avoid matching single letters
    }

    /**
     * Create new reference post
     *
     * @param array $reference_data Reference data
     * @param array $options Import options
     * @return array|WP_Error Creation result or error
     */
    private function create_new_reference($reference_data, $options) {
        $post_data = [
            'post_type' => 'abt_reference',
            'post_title' => sanitize_text_field($reference_data['title'] ?? ''),
            'post_content' => sanitize_textarea_field($reference_data['abstract'] ?? ''),
            'post_status' => $options['default_status'],
            'post_author' => $options['assign_to_user']
        ];

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Save reference metadata
        $this->save_reference_metadata($post_id, $reference_data);

        return ['action' => 'imported', 'post_id' => $post_id];
    }

    /**
     * Update existing reference
     *
     * @param int $post_id Post ID
     * @param array $reference_data New reference data
     * @return array Update result
     */
    private function update_existing_reference($post_id, $reference_data) {
        $post_data = [
            'ID' => $post_id,
            'post_title' => sanitize_text_field($reference_data['title'] ?? ''),
            'post_content' => sanitize_textarea_field($reference_data['abstract'] ?? '')
        ];

        wp_update_post($post_data);

        // Update metadata
        $this->save_reference_metadata($post_id, $reference_data);

        return ['action' => 'updated', 'post_id' => $post_id];
    }

    /**
     * Save reference metadata
     *
     * @param int $post_id Post ID
     * @param array $reference_data Reference data
     */
    private function save_reference_metadata($post_id, $reference_data) {
        $meta_mapping = [
            'type' => '_abt_type',
            'author' => '_abt_author',
            'editor' => '_abt_editor',
            'publisher' => '_abt_publisher',
            'container-title' => '_abt_publication',
            'volume' => '_abt_volume',
            'issue' => '_abt_issue',
            'page' => '_abt_pages',
            'issued' => '_abt_date',
            'DOI' => '_abt_doi',
            'ISBN' => '_abt_isbn',
            'ISSN' => '_abt_issn',
            'PMID' => '_abt_pmid',
            'URL' => '_abt_url',
            'language' => '_abt_language',
            'keyword' => '_abt_keywords'
        ];

        foreach ($meta_mapping as $source_key => $meta_key) {
            if (isset($reference_data[$source_key])) {
                $value = $reference_data[$source_key];
                
                // Convert arrays to strings for storage
                if (is_array($value)) {
                    if ($source_key === 'author' || $source_key === 'editor') {
                        $value = $this->format_authors_for_storage($value);
                    } elseif ($source_key === 'issued') {
                        $value = $this->format_date_for_storage($value);
                    } else {
                        $value = implode(', ', $value);
                    }
                }
                
                update_post_meta($post_id, $meta_key, $value);
            }
        }

        // Save import metadata
        update_post_meta($post_id, '_abt_import_date', current_time('mysql'));
        update_post_meta($post_id, '_abt_import_source', 'file_import');
    }

    /**
     * Format authors for storage
     *
     * @param array $authors Author array
     * @return string Formatted author string
     */
    private function format_authors_for_storage($authors) {
        $formatted_authors = [];
        
        foreach ($authors as $author) {
            if (is_array($author)) {
                $name = trim(($author['given'] ?? '') . ' ' . ($author['family'] ?? ''));
                if (!empty($name)) {
                    $formatted_authors[] = $name;
                }
            } else {
                $formatted_authors[] = $author;
            }
        }

        return implode('; ', $formatted_authors);
    }

    /**
     * Format date for storage
     *
     * @param mixed $date Date data
     * @return string Formatted date string
     */
    private function format_date_for_storage($date) {
        if (is_array($date) && isset($date['date-parts'][0])) {
            $parts = $date['date-parts'][0];
            return sprintf('%04d-%02d-%02d', $parts[0], $parts[1] ?? 1, $parts[2] ?? 1);
        }
        
        if (is_string($date)) {
            return date('Y-m-d', strtotime($date));
        }

        return '';
    }

    /**
     * Parse JSON data
     *
     * @param string $data JSON data
     * @return array|WP_Error Parsed references or error
     */
    public function parse_json($data) {
        $json_data = json_decode($data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_parse_error', __('Invalid JSON data', 'academic-bloggers-toolkit'));
        }

        // Handle different JSON structures
        if (isset($json_data['items']) && is_array($json_data['items'])) {
            return $json_data['items']; // CSL-JSON format
        } elseif (is_array($json_data)) {
            return $json_data; // Array of references
        } else {
            return [$json_data]; // Single reference
        }
    }

    /**
     * Reset import statistics
     */
    private function reset_import_stats() {
        $this->import_stats = [
            'total_processed' => 0,
            'successful_imports' => 0,
            'failed_imports' => 0,
            'duplicates_found' => 0,
            'errors' => []
        ];
    }

    /**
     * Get supported formats
     *
     * @return array Supported format information
     */
    public function get_supported_formats() {
        return [
            'ris' => [
                'name' => 'RIS (Research Information Systems)',
                'extensions' => ['ris'],
                'mime_types' => ['application/x-research-info-systems', 'text/plain'],
                'description' => __('Standard format used by reference managers like EndNote, Zotero', 'academic-bloggers-toolkit')
            ],
            'bibtex' => [
                'name' => 'BibTeX',
                'extensions' => ['bib', 'bibtex'],
                'mime_types' => ['application/x-bibtex', 'text/plain'],
                'description' => __('LaTeX bibliography format', 'academic-bloggers-toolkit')
            ],
            'csv' => [
                'name' => 'CSV (Comma Separated Values)',
                'extensions' => ['csv'],
                'mime_types' => ['text/csv', 'application/csv'],
                'description' => __('Spreadsheet format with defined column headers', 'academic-bloggers-toolkit')
            ],
            'json' => [
                'name' => 'JSON (CSL-JSON)',
                'extensions' => ['json'],
                'mime_types' => ['application/json'],
                'description' => __('Citation Style Language JSON format', 'academic-bloggers-toolkit')
            ]
        ];
    }

    /**
     * Get import statistics
     *
     * @return array Current import statistics
     */
    public function get_import_stats() {
        return $this->import_stats;
    }
}