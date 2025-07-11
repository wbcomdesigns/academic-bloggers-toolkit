<?php
/**
 * Export Manager - Citation Format Exporter
 *
 * Handles exporting references to various formats (RIS, BibTeX, CSV)
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
 * Export Manager Class
 *
 * Orchestrates exporting references to various file formats
 */
class ABT_Export_Manager {

    /**
     * Supported export formats
     *
     * @var array
     */
    private $supported_formats = ['ris', 'bibtex', 'csv', 'json'];

    /**
     * Export formatters
     *
     * @var array
     */
    private $formatters = [];

    /**
     * Export statistics
     *
     * @var array
     */
    private $export_stats = [
        'total_processed' => 0,
        'successful_exports' => 0,
        'failed_exports' => 0,
        'errors' => []
    ];

    /**
     * Initialize the export manager
     */
    public function __construct() {
        $this->init_formatters();
    }

    /**
     * Initialize format exporters
     */
    private function init_formatters() {
        $this->formatters = [
            'ris' => new ABT_RIS_Parser(),
            'bibtex' => new ABT_BibTeX_Parser(),
            'csv' => new ABT_CSV_Handler(),
            'json' => [$this, 'format_json']
        ];
    }

    /**
     * Export references to file
     *
     * @param array $reference_ids Array of reference post IDs
     * @param string $format Export format
     * @param array $options Export options
     * @return array|WP_Error Export results or error
     */
    public function export_references($reference_ids, $format, $options = []) {
        if (!in_array($format, $this->supported_formats)) {
            return new WP_Error('unsupported_format', sprintf(__('Unsupported export format: %s', 'academic-bloggers-toolkit'), $format));
        }

        if (empty($reference_ids)) {
            return new WP_Error('no_references', __('No references provided for export', 'academic-bloggers-toolkit'));
        }

        // Reset statistics
        $this->reset_export_stats();

        // Parse default options
        $default_options = [
            'include_abstracts' => true,
            'include_keywords' => true,
            'include_urls' => true,
            'filename' => 'abt_references_' . date('Y-m-d'),
            'batch_size' => 100,
            'validate_data' => true
        ];

        $options = wp_parse_args($options, $default_options);

        // Get reference data
        $references_data = $this->get_references_data($reference_ids, $options);

        if (is_wp_error($references_data)) {
            return $references_data;
        }

        if (empty($references_data)) {
            return new WP_Error('no_data', __('No valid reference data found for export', 'academic-bloggers-toolkit'));
        }

        $this->export_stats['total_processed'] = count($references_data);

        // Format the data
        $formatted_data = $this->format_data($references_data, $format, $options);

        if (is_wp_error($formatted_data)) {
            return $formatted_data;
        }

        $this->export_stats['successful_exports'] = count($references_data);

        return [
            'content' => $formatted_data,
            'filename' => $options['filename'] . '.' . $format,
            'mime_type' => $this->get_mime_type($format),
            'format' => $format,
            'statistics' => $this->export_stats,
            'options_used' => $options
        ];
    }

    /**
     * Export citations from a specific post
     *
     * @param int $post_id Post ID
     * @param string $format Export format
     * @param array $options Export options
     * @return array|WP_Error Export results or error
     */
    public function export_post_citations($post_id, $format, $options = []) {
        if (!$post_id || get_post_type($post_id) !== 'abt_blog') {
            return new WP_Error('invalid_post', __('Invalid academic blog post ID', 'academic-bloggers-toolkit'));
        }

        // Get citations from post
        $citations = ABT_Query::get_post_citations($post_id);
        
        if (empty($citations)) {
            return new WP_Error('no_citations', __('No citations found in this post', 'academic-bloggers-toolkit'));
        }

        // Extract reference IDs
        $reference_ids = [];
        foreach ($citations as $citation) {
            if (isset($citation['reference_id'])) {
                $reference_ids[] = $citation['reference_id'];
            }
        }

        $reference_ids = array_unique($reference_ids);

        // Set default filename
        $post_title = get_the_title($post_id);
        $safe_title = sanitize_file_name($post_title);
        $options['filename'] = $options['filename'] ?? ('citations_' . $safe_title . '_' . date('Y-m-d'));

        return $this->export_references($reference_ids, $format, $options);
    }

    /**
     * Export all references
     *
     * @param string $format Export format
     * @param array $options Export options
     * @return array|WP_Error Export results or error
     */
    public function export_all_references($format, $options = []) {
        $reference_ids = get_posts([
            'post_type' => 'abt_reference',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);

        if (empty($reference_ids)) {
            return new WP_Error('no_references', __('No references found to export', 'academic-bloggers-toolkit'));
        }

        $options['filename'] = $options['filename'] ?? ('all_references_' . date('Y-m-d'));

        return $this->export_references($reference_ids, $format, $options);
    }

    /**
     * Get references data for export
     *
     * @param array $reference_ids Reference IDs
     * @param array $options Export options
     * @return array|WP_Error References data or error
     */
    private function get_references_data($reference_ids, $options) {
        $references_data = [];

        foreach ($reference_ids as $ref_id) {
            $reference = get_post($ref_id);
            
            if (!$reference || $reference->post_type !== 'abt_reference') {
                $this->export_stats['failed_exports']++;
                $this->export_stats['errors'][] = [
                    'reference_id' => $ref_id,
                    'error' => 'Reference not found or invalid type'
                ];
                continue;
            }

            $ref_data = $this->format_reference_for_export($ref_id, $options);
            
            if ($ref_data) {
                $references_data[] = $ref_data;
            } else {
                $this->export_stats['failed_exports']++;
                $this->export_stats['errors'][] = [
                    'reference_id' => $ref_id,
                    'error' => 'Failed to format reference data'
                ];
            }
        }

        return $references_data;
    }

    /**
     * Format reference for export
     *
     * @param int $reference_id Reference ID
     * @param array $options Export options
     * @return array|false Formatted reference data or false on failure
     */
    private function format_reference_for_export($reference_id, $options) {
        $reference = get_post($reference_id);
        
        if (!$reference) {
            return false;
        }

        $data = [
            'id' => $reference_id,
            'title' => $reference->post_title,
            'type' => get_post_meta($reference_id, '_abt_type', true) ?: 'other',
            'author' => get_post_meta($reference_id, '_abt_author', true),
            'editor' => get_post_meta($reference_id, '_abt_editor', true),
            'year' => get_post_meta($reference_id, '_abt_year', true),
            'publication' => get_post_meta($reference_id, '_abt_publication', true),
            'journal' => get_post_meta($reference_id, '_abt_journal', true),
            'publisher' => get_post_meta($reference_id, '_abt_publisher', true),
            'volume' => get_post_meta($reference_id, '_abt_volume', true),
            'issue' => get_post_meta($reference_id, '_abt_issue', true),
            'pages' => get_post_meta($reference_id, '_abt_pages', true),
            'doi' => get_post_meta($reference_id, '_abt_doi', true),
            'pmid' => get_post_meta($reference_id, '_abt_pmid', true),
            'isbn' => get_post_meta($reference_id, '_abt_isbn', true),
            'issn' => get_post_meta($reference_id, '_abt_issn', true),
            'url' => get_post_meta($reference_id, '_abt_url', true),
            'language' => get_post_meta($reference_id, '_abt_language', true),
            'location' => get_post_meta($reference_id, '_abt_location', true),
            'edition' => get_post_meta($reference_id, '_abt_edition', true)
        ];

        // Include abstracts if requested
        if ($options['include_abstracts']) {
            $data['abstract'] = $reference->post_content;
        }

        // Include keywords if requested
        if ($options['include_keywords']) {
            $data['keywords'] = get_post_meta($reference_id, '_abt_keywords', true);
        }

        // Include URLs if requested
        if (!$options['include_urls']) {
            unset($data['url']);
        }

        // Remove empty values
        $data = array_filter($data, function($value) {
            return !empty($value);
        });

        return $data;
    }

    /**
     * Format data using appropriate formatter
     *
     * @param array $references_data References data
     * @param string $format Export format
     * @param array $options Export options
     * @return string|WP_Error Formatted data or error
     */
    private function format_data($references_data, $format, $options) {
        if (!isset($this->formatters[$format])) {
            return new WP_Error('no_formatter', sprintf(__('No formatter available for format: %s', 'academic-bloggers-toolkit'), $format));
        }

        $formatter = $this->formatters[$format];

        try {
            if (is_callable($formatter)) {
                return call_user_func($formatter, $references_data, $options);
            } else {
                return $formatter->export($references_data, $options);
            }
        } catch (Exception $e) {
            return new WP_Error('format_error', sprintf(__('Format error: %s', 'academic-bloggers-toolkit'), $e->getMessage()));
        }
    }

    /**
     * Format data as JSON
     *
     * @param array $references_data References data
     * @param array $options Export options
     * @return string JSON formatted data
     */
    public function format_json($references_data, $options = []) {
        $json_data = [
            'export_info' => [
                'format' => 'json',
                'exported_at' => current_time('c'),
                'exported_by' => 'Academic Bloggers Toolkit v' . ABT_VERSION,
                'total_references' => count($references_data),
                'options' => $options
            ],
            'references' => []
        ];

        foreach ($references_data as $reference) {
            $json_reference = $this->format_reference_for_json($reference);
            $json_data['references'][] = $json_reference;
        }

        return wp_json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Format reference for JSON export (CSL-JSON compatible)
     *
     * @param array $reference Reference data
     * @return array CSL-JSON formatted reference
     */
    private function format_reference_for_json($reference) {
        $csl_json = [
            'id' => 'ref_' . $reference['id'],
            'type' => $this->map_type_to_csl($reference['type']),
            'title' => $reference['title']
        ];

        // Authors
        if (!empty($reference['author'])) {
            $csl_json['author'] = $this->format_authors_for_csl($reference['author']);
        }

        // Editors
        if (!empty($reference['editor'])) {
            $csl_json['editor'] = $this->format_authors_for_csl($reference['editor']);
        }

        // Publication date
        if (!empty($reference['year'])) {
            $csl_json['issued'] = [
                'date-parts' => [[(int) $reference['year']]]
            ];
        }

        // Container title (journal, book, etc.)
        if (!empty($reference['journal'])) {
            $csl_json['container-title'] = $reference['journal'];
        } elseif (!empty($reference['publication'])) {
            $csl_json['container-title'] = $reference['publication'];
        }

        // Publisher
        if (!empty($reference['publisher'])) {
            $csl_json['publisher'] = $reference['publisher'];
        }

        // Volume, issue, pages
        if (!empty($reference['volume'])) {
            $csl_json['volume'] = $reference['volume'];
        }

        if (!empty($reference['issue'])) {
            $csl_json['issue'] = $reference['issue'];
        }

        if (!empty($reference['pages'])) {
            $csl_json['page'] = $reference['pages'];
        }

        // Identifiers
        if (!empty($reference['doi'])) {
            $csl_json['DOI'] = $reference['doi'];
        }

        if (!empty($reference['pmid'])) {
            $csl_json['PMID'] = $reference['pmid'];
        }

        if (!empty($reference['isbn'])) {
            $csl_json['ISBN'] = $reference['isbn'];
        }

        if (!empty($reference['issn'])) {
            $csl_json['ISSN'] = $reference['issn'];
        }

        if (!empty($reference['url'])) {
            $csl_json['URL'] = $reference['url'];
        }

        // Abstract
        if (!empty($reference['abstract'])) {
            $csl_json['abstract'] = $reference['abstract'];
        }

        // Keywords
        if (!empty($reference['keywords'])) {
            $csl_json['keyword'] = $reference['keywords'];
        }

        // Language
        if (!empty($reference['language'])) {
            $csl_json['language'] = $reference['language'];
        }

        // Edition
        if (!empty($reference['edition'])) {
            $csl_json['edition'] = $reference['edition'];
        }

        return $csl_json;
    }

    /**
     * Map reference type to CSL type
     *
     * @param string $abt_type ABT reference type
     * @return string CSL type
     */
    private function map_type_to_csl($abt_type) {
        $type_mapping = [
            'journal' => 'article-journal',
            'book' => 'book',
            'chapter' => 'chapter',
            'conference' => 'paper-conference',
            'thesis' => 'thesis',
            'report' => 'report',
            'website' => 'webpage',
            'newspaper' => 'article-newspaper',
            'magazine' => 'article-magazine',
            'other' => 'article'
        ];

        return $type_mapping[$abt_type] ?? 'article';
    }

    /**
     * Format authors for CSL-JSON
     *
     * @param string $authors_string Authors string
     * @return array CSL author array
     */
    private function format_authors_for_csl($authors_string) {
        $authors = [];
        
        // Split by common separators
        $author_list = preg_split('/[;,]|\band\b|\&/', $authors_string);
        
        foreach ($author_list as $author) {
            $author = trim($author);
            if (empty($author)) continue;
            
            // Parse name parts
            if (strpos($author, ',') !== false) {
                // "Last, First" format
                $parts = explode(',', $author, 2);
                $family = trim($parts[0]);
                $given = trim($parts[1]);
            } else {
                // "First Last" format
                $parts = explode(' ', $author);
                $family = array_pop($parts);
                $given = implode(' ', $parts);
            }
            
            $authors[] = [
                'family' => $family,
                'given' => $given
            ];
        }

        return $authors;
    }

    /**
     * Get MIME type for format
     *
     * @param string $format Export format
     * @return string MIME type
     */
    private function get_mime_type($format) {
        $mime_types = [
            'ris' => 'application/x-research-info-systems',
            'bibtex' => 'application/x-bibtex',
            'csv' => 'text/csv',
            'json' => 'application/json'
        ];

        return $mime_types[$format] ?? 'text/plain';
    }

    /**
     * Generate export filename
     *
     * @param string $base_name Base filename
     * @param string $format Export format
     * @param array $options Export options
     * @return string Generated filename
     */
    public function generate_filename($base_name, $format, $options = []) {
        $filename = sanitize_file_name($base_name);
        
        // Add timestamp if requested
        if (!empty($options['include_timestamp'])) {
            $filename .= '_' . date('Y-m-d_H-i-s');
        }

        // Add reference count if available
        if (!empty($options['reference_count'])) {
            $filename .= '_' . $options['reference_count'] . '_refs';
        }

        return $filename . '.' . $format;
    }

    /**
     * Validate export data before formatting
     *
     * @param array $references_data References data
     * @return bool|WP_Error True if valid, error if not
     */
    private function validate_export_data($references_data) {
        if (empty($references_data)) {
            return new WP_Error('empty_data', __('No reference data to export', 'academic-bloggers-toolkit'));
        }

        foreach ($references_data as $index => $reference) {
            if (empty($reference['title'])) {
                return new WP_Error('missing_title', sprintf(__('Reference at index %d is missing title', 'academic-bloggers-toolkit'), $index));
            }
        }

        return true;
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
                'extension' => 'ris',
                'mime_type' => 'application/x-research-info-systems',
                'description' => __('Standard format used by reference managers like EndNote, Zotero', 'academic-bloggers-toolkit')
            ],
            'bibtex' => [
                'name' => 'BibTeX',
                'extension' => 'bib',
                'mime_type' => 'application/x-bibtex',
                'description' => __('LaTeX bibliography format', 'academic-bloggers-toolkit')
            ],
            'csv' => [
                'name' => 'CSV (Comma Separated Values)',
                'extension' => 'csv',
                'mime_type' => 'text/csv',
                'description' => __('Spreadsheet format with column headers', 'academic-bloggers-toolkit')
            ],
            'json' => [
                'name' => 'JSON (CSL-JSON)',
                'extension' => 'json',
                'mime_type' => 'application/json',
                'description' => __('Citation Style Language JSON format', 'academic-bloggers-toolkit')
            ]
        ];
    }

    /**
     * Reset export statistics
     */
    private function reset_export_stats() {
        $this->export_stats = [
            'total_processed' => 0,
            'successful_exports' => 0,
            'failed_exports' => 0,
            'errors' => []
        ];
    }

    /**
     * Get export statistics
     *
     * @return array Current export statistics
     */
    public function get_export_stats() {
        return $this->export_stats;
    }

    /**
     * Create downloadable file
     *
     * @param string $content File content
     * @param string $filename Filename
     * @param string $mime_type MIME type
     * @return void Outputs file for download
     */
    public function download_file($content, $filename, $mime_type) {
        // Set headers for file download
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        // Output content
        echo $content;
        exit;
    }

    /**
     * Save export to file
     *
     * @param string $content File content
     * @param string $filename Filename
     * @param string $directory Directory to save in (optional)
     * @return string|WP_Error File path on success, error on failure
     */
    public function save_to_file($content, $filename, $directory = null) {
        if (!$directory) {
            $upload_dir = wp_upload_dir();
            $directory = $upload_dir['basedir'] . '/abt-exports/';
        }

        // Create directory if it doesn't exist
        if (!file_exists($directory)) {
            wp_mkdir_p($directory);
        }

        $file_path = $directory . $filename;

        $result = file_put_contents($file_path, $content);

        if ($result === false) {
            return new WP_Error('save_failed', __('Failed to save export file', 'academic-bloggers-toolkit'));
        }

        return $file_path;
    }

    /**
     * Schedule automatic export
     *
     * @param array $export_config Export configuration
     * @return bool Success status
     */
    public function schedule_export($export_config) {
        $hook = 'abt_scheduled_export';
        
        // Clear existing scheduled export
        wp_clear_scheduled_hook($hook);

        // Schedule new export
        $timestamp = wp_next_scheduled($hook);
        
        if (!$timestamp) {
            $interval = $export_config['interval'] ?? 'weekly';
            $timestamp = strtotime('next ' . $interval);
            
            wp_schedule_event($timestamp, $interval, $hook, [$export_config]);
        }

        return true;
    }
}