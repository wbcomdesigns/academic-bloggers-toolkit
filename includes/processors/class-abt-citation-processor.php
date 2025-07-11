<?php
/**
 * Citation Processor - Core citation processing engine
 *
 * Handles citation formatting, bibliography generation, and CSL integration
 *
 * @package Academic_Bloggers_Toolkit
 * @subpackage Processors
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Citation Processor Class
 *
 * Orchestrates citation processing, formatting, and bibliography generation
 */
class ABT_Citation_Processor {

    /**
     * Style manager instance
     *
     * @var ABT_Style_Manager
     */
    private $style_manager;

    /**
     * Formatter instance
     *
     * @var ABT_Formatter
     */
    private $formatter;

    /**
     * Cache for processed citations
     *
     * @var array
     */
    private $citation_cache = [];

    /**
     * Initialize the citation processor
     */
    public function __construct() {
        $this->style_manager = new ABT_Style_Manager();
        $this->formatter = new ABT_Formatter();
        
        // Hook into WordPress
        add_action('init', [$this, 'init']);
        add_filter('the_content', [$this, 'process_post_citations'], 10);
        add_shortcode('abt_bibliography', [$this, 'bibliography_shortcode']);
    }

    /**
     * Initialize the processor
     */
    public function init() {
        // Register citation styles
        $this->style_manager->register_default_styles();
        
        // Clear cache on post save
        add_action('save_post', [$this, 'clear_citation_cache']);
    }

    /**
     * Process citations in post content
     *
     * @param string $content Post content
     * @return string Processed content with formatted citations
     */
    public function process_post_citations($content) {
        if (!is_singular('abt_blog')) {
            return $content;
        }

        global $post;
        
        // Get post citations
        $citations = $this->get_post_citations($post->ID);
        
        if (empty($citations)) {
            return $content;
        }

        // Get citation style
        $style = get_post_meta($post->ID, '_abt_citation_style', true) ?: 'apa';
        
        // Process inline citations
        $content = $this->process_inline_citations($content, $citations, $style);
        
        // Add bibliography if enabled
        if (get_post_meta($post->ID, '_abt_auto_bibliography', true)) {
            $content .= $this->generate_bibliography($citations, $style);
        }

        return $content;
    }

    /**
     * Get citations for a post
     *
     * @param int $post_id Post ID
     * @return array Array of citation data
     */
    public function get_post_citations($post_id) {
        $cache_key = 'abt_citations_' . $post_id;
        
        if (isset($this->citation_cache[$cache_key])) {
            return $this->citation_cache[$cache_key];
        }

        $citations_meta = get_post_meta($post_id, '_abt_citations', true);
        
        if (!$citations_meta || !is_array($citations_meta)) {
            return [];
        }

        $citations = [];
        
        foreach ($citations_meta as $citation_data) {
            // Get reference data
            $reference_id = $citation_data['reference_id'] ?? 0;
            $reference = get_post($reference_id);
            
            if (!$reference || $reference->post_type !== 'abt_reference') {
                continue;
            }

            // Build citation object
            $citation = [
                'id' => $citation_data['id'] ?? uniqid('cite_'),
                'reference_id' => $reference_id,
                'pages' => $citation_data['pages'] ?? '',
                'prefix' => $citation_data['prefix'] ?? '',
                'suffix' => $citation_data['suffix'] ?? '',
                'suppress_author' => $citation_data['suppress_author'] ?? false,
                'order' => $citation_data['order'] ?? 0,
                'reference_data' => $this->get_reference_data($reference_id)
            ];
            
            $citations[] = $citation;
        }

        // Sort by order
        usort($citations, function($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        $this->citation_cache[$cache_key] = $citations;
        
        return $citations;
    }

    /**
     * Get reference data for citation processing
     *
     * @param int $reference_id Reference post ID
     * @return array Reference data array
     */
    private function get_reference_data($reference_id) {
        $reference = get_post($reference_id);
        
        if (!$reference) {
            return [];
        }

        // Get all reference meta
        $meta = get_post_meta($reference_id);
        
        // Build CSL-JSON compatible data
        $reference_data = [
            'id' => $reference_id,
            'type' => $meta['_abt_type'][0] ?? 'article',
            'title' => $reference->post_title,
            'abstract' => $reference->post_content,
        ];

        // Map common fields
        $field_mapping = [
            '_abt_author' => 'author',
            '_abt_editor' => 'editor',
            '_abt_publisher' => 'publisher',
            '_abt_publication' => 'container-title',
            '_abt_journal' => 'container-title',
            '_abt_volume' => 'volume',
            '_abt_issue' => 'issue',
            '_abt_pages' => 'page',
            '_abt_year' => 'issued',
            '_abt_date' => 'issued',
            '_abt_doi' => 'DOI',
            '_abt_isbn' => 'ISBN',
            '_abt_issn' => 'ISSN',
            '_abt_pmid' => 'PMID',
            '_abt_url' => 'URL',
            '_abt_accessed' => 'accessed'
        ];

        foreach ($field_mapping as $meta_key => $csl_key) {
            if (isset($meta[$meta_key][0]) && !empty($meta[$meta_key][0])) {
                $value = $meta[$meta_key][0];
                
                // Special handling for dates
                if (in_array($csl_key, ['issued', 'accessed'])) {
                    $reference_data[$csl_key] = $this->format_date_for_csl($value);
                }
                // Special handling for names
                elseif (in_array($csl_key, ['author', 'editor'])) {
                    $reference_data[$csl_key] = $this->format_names_for_csl($value);
                }
                else {
                    $reference_data[$csl_key] = $value;
                }
            }
        }

        return $reference_data;
    }

    /**
     * Format date for CSL-JSON
     *
     * @param string $date Date string
     * @return array CSL date format
     */
    private function format_date_for_csl($date) {
        $timestamp = strtotime($date);
        
        if (!$timestamp) {
            return ['literal' => $date];
        }

        return [
            'date-parts' => [[
                (int) date('Y', $timestamp),
                (int) date('n', $timestamp),
                (int) date('j', $timestamp)
            ]]
        ];
    }

    /**
     * Format names for CSL-JSON
     *
     * @param string $names Names string
     * @return array CSL names format
     */
    private function format_names_for_csl($names) {
        $parsed_names = [];
        
        // Split multiple names (handle various separators)
        $name_list = preg_split('/[;,]|\band\b|\&/', $names);
        
        foreach ($name_list as $name) {
            $name = trim($name);
            if (empty($name)) continue;
            
            // Parse name parts
            $parts = explode(' ', $name);
            $last_name = array_pop($parts);
            $first_names = implode(' ', $parts);
            
            $parsed_names[] = [
                'family' => $last_name,
                'given' => $first_names
            ];
        }

        return $parsed_names;
    }

    /**
     * Process inline citations in content
     *
     * @param string $content Post content
     * @param array $citations Citations array
     * @param string $style Citation style
     * @return string Processed content
     */
    private function process_inline_citations($content, $citations, $style) {
        // Build citation lookup
        $citation_lookup = [];
        foreach ($citations as $citation) {
            $citation_lookup[$citation['id']] = $citation;
        }

        // Find and replace citation placeholders
        $pattern = '/\[cite:([^\]]+)\]/';
        
        return preg_replace_callback($pattern, function($matches) use ($citation_lookup, $style) {
            $citation_id = $matches[1];
            
            if (!isset($citation_lookup[$citation_id])) {
                return $matches[0]; // Return original if citation not found
            }

            $citation = $citation_lookup[$citation_id];
            return $this->format_inline_citation($citation, $style);
        }, $content);
    }

    /**
     * Format inline citation
     *
     * @param array $citation Citation data
     * @param string $style Citation style
     * @return string Formatted citation
     */
    private function format_inline_citation($citation, $style) {
        $reference_data = $citation['reference_data'];
        
        // Get style configuration
        $style_config = $this->style_manager->get_style($style);
        
        if (!$style_config) {
            return '[Citation Error]';
        }

        // Use formatter to generate citation
        return $this->formatter->format_citation($reference_data, $citation, $style_config);
    }

    /**
     * Generate bibliography for citations
     *
     * @param array $citations Citations array
     * @param string $style Citation style
     * @return string HTML bibliography
     */
    public function generate_bibliography($citations, $style = 'apa') {
        if (empty($citations)) {
            return '';
        }

        $style_config = $this->style_manager->get_style($style);
        
        if (!$style_config) {
            return '<div class="abt-error">Bibliography Error: Invalid citation style</div>';
        }

        $bibliography_items = [];
        
        foreach ($citations as $citation) {
            $reference_data = $citation['reference_data'];
            $formatted = $this->formatter->format_bibliography_entry($reference_data, $style_config);
            
            if ($formatted) {
                $bibliography_items[] = $formatted;
            }
        }

        if (empty($bibliography_items)) {
            return '';
        }

        // Build bibliography HTML
        $html = '<div class="abt-bibliography">';
        $html .= '<h3 class="abt-bibliography-title">' . __('References', 'academic-bloggers-toolkit') . '</h3>';
        $html .= '<ol class="abt-bibliography-list">';
        
        foreach ($bibliography_items as $item) {
            $html .= '<li class="abt-bibliography-item">' . $item . '</li>';
        }
        
        $html .= '</ol>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Bibliography shortcode handler
     *
     * @param array $atts Shortcode attributes
     * @return string Bibliography HTML
     */
    public function bibliography_shortcode($atts) {
        $atts = shortcode_atts([
            'post_id' => get_the_ID(),
            'style' => 'apa',
            'title' => __('References', 'academic-bloggers-toolkit'),
            'numbered' => 'true'
        ], $atts);

        $post_id = (int) $atts['post_id'];
        
        if (!$post_id) {
            return '<div class="abt-error">Bibliography Error: Invalid post ID</div>';
        }

        $citations = $this->get_post_citations($post_id);
        
        if (empty($citations)) {
            return '<div class="abt-notice">No citations found for bibliography</div>';
        }

        return $this->generate_bibliography($citations, $atts['style']);
    }

    /**
     * Clear citation cache
     *
     * @param int $post_id Post ID
     */
    public function clear_citation_cache($post_id = null) {
        if ($post_id) {
            $cache_key = 'abt_citations_' . $post_id;
            unset($this->citation_cache[$cache_key]);
        } else {
            $this->citation_cache = [];
        }
    }

    /**
     * Get available citation styles
     *
     * @return array Available styles
     */
    public function get_available_styles() {
        return $this->style_manager->get_available_styles();
    }

    /**
     * Validate citation data
     *
     * @param array $citation_data Citation data to validate
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_citation($citation_data) {
        $required_fields = ['reference_id'];
        
        foreach ($required_fields as $field) {
            if (!isset($citation_data[$field]) || empty($citation_data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Missing required field: %s', 'academic-bloggers-toolkit'), $field));
            }
        }

        // Validate reference exists
        $reference = get_post($citation_data['reference_id']);
        if (!$reference || $reference->post_type !== 'abt_reference') {
            return new WP_Error('invalid_reference', __('Invalid reference ID', 'academic-bloggers-toolkit'));
        }

        return true;
    }

    /**
     * Export citations to various formats
     *
     * @param array $citations Citations to export
     * @param string $format Export format (bibtex, ris, csv)
     * @return string Exported data
     */
    public function export_citations($citations, $format = 'bibtex') {
        $export_manager = new ABT_Export_Manager();
        return $export_manager->export_citations($citations, $format);
    }

    /**
     * Import citations from various formats
     *
     * @param string $data Import data
     * @param string $format Import format (bibtex, ris, csv)
     * @return array|WP_Error Imported citations or error
     */
    public function import_citations($data, $format = 'bibtex') {
        $import_manager = new ABT_Import_Manager();
        return $import_manager->import_citations($data, $format);
    }
}