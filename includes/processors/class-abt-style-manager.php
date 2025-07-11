<?php
/**
 * Style Manager - Citation Style Management
 *
 * Handles citation style registration, loading, and configuration
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
 * Citation Style Manager Class
 *
 * Manages citation styles and CSL integration
 */
class ABT_Style_Manager {

    /**
     * Registered citation styles
     *
     * @var array
     */
    private $styles = [];

    /**
     * CSL cache directory
     *
     * @var string
     */
    private $cache_dir;

    /**
     * Initialize the style manager
     */
    public function __construct() {
        $this->cache_dir = WP_CONTENT_DIR . '/uploads/abt-cache/csl/';
        
        // Ensure cache directory exists
        $this->ensure_cache_directory();
    }

    /**
     * Register default citation styles
     */
    public function register_default_styles() {
        // APA Style
        $this->register_style('apa', [
            'name' => __('APA (American Psychological Association)', 'academic-bloggers-toolkit'),
            'description' => __('Standard APA citation style', 'academic-bloggers-toolkit'),
            'csl_file' => ABT_PLUGIN_DIR . 'assets/citation-styles/apa.csl',
            'inline_format' => 'author-date',
            'bibliography_format' => 'hanging-indent'
        ]);

        // MLA Style
        $this->register_style('mla', [
            'name' => __('MLA (Modern Language Association)', 'academic-bloggers-toolkit'),
            'description' => __('Standard MLA citation style', 'academic-bloggers-toolkit'),
            'csl_file' => ABT_PLUGIN_DIR . 'assets/citation-styles/mla.csl',
            'inline_format' => 'author-page',
            'bibliography_format' => 'hanging-indent'
        ]);

        // Chicago Style
        $this->register_style('chicago', [
            'name' => __('Chicago Manual of Style', 'academic-bloggers-toolkit'),
            'description' => __('Chicago author-date citation style', 'academic-bloggers-toolkit'),
            'csl_file' => ABT_PLUGIN_DIR . 'assets/citation-styles/chicago.csl',
            'inline_format' => 'author-date',
            'bibliography_format' => 'hanging-indent'
        ]);

        // Harvard Style
        $this->register_style('harvard', [
            'name' => __('Harvard Reference Style', 'academic-bloggers-toolkit'),
            'description' => __('Standard Harvard citation style', 'academic-bloggers-toolkit'),
            'csl_file' => ABT_PLUGIN_DIR . 'assets/citation-styles/harvard.csl',
            'inline_format' => 'author-date',
            'bibliography_format' => 'hanging-indent'
        ]);

        // IEEE Style
        $this->register_style('ieee', [
            'name' => __('IEEE (Institute of Electrical and Electronics Engineers)', 'academic-bloggers-toolkit'),
            'description' => __('Standard IEEE citation style', 'academic-bloggers-toolkit'),
            'csl_file' => ABT_PLUGIN_DIR . 'assets/citation-styles/ieee.csl',
            'inline_format' => 'numeric',
            'bibliography_format' => 'numbered'
        ]);

        // Vancouver Style
        $this->register_style('vancouver', [
            'name' => __('Vancouver Style', 'academic-bloggers-toolkit'),
            'description' => __('Medical journal citation style', 'academic-bloggers-toolkit'),
            'csl_file' => null, // Built-in format
            'inline_format' => 'numeric',
            'bibliography_format' => 'numbered'
        ]);

        // Allow custom styles to be registered
        do_action('abt_register_citation_styles', $this);
    }

    /**
     * Register a citation style
     *
     * @param string $style_id Unique style identifier
     * @param array $config Style configuration
     */
    public function register_style($style_id, $config) {
        $default_config = [
            'name' => $style_id,
            'description' => '',
            'csl_file' => null,
            'inline_format' => 'author-date',
            'bibliography_format' => 'hanging-indent',
            'custom_formatter' => null
        ];

        $this->styles[$style_id] = wp_parse_args($config, $default_config);
    }

    /**
     * Get a citation style configuration
     *
     * @param string $style_id Style identifier
     * @return array|false Style configuration or false if not found
     */
    public function get_style($style_id) {
        return $this->styles[$style_id] ?? false;
    }

    /**
     * Get all available citation styles
     *
     * @return array Available styles
     */
    public function get_available_styles() {
        $styles = [];
        
        foreach ($this->styles as $style_id => $config) {
            $styles[$style_id] = [
                'id' => $style_id,
                'name' => $config['name'],
                'description' => $config['description'],
                'inline_format' => $config['inline_format'],
                'bibliography_format' => $config['bibliography_format']
            ];
        }

        return $styles;
    }

    /**
     * Load CSL style file
     *
     * @param string $style_id Style identifier
     * @return string|false CSL XML content or false if not found
     */
    public function load_csl_style($style_id) {
        $style_config = $this->get_style($style_id);
        
        if (!$style_config || !$style_config['csl_file']) {
            return false;
        }

        if (!file_exists($style_config['csl_file'])) {
            return false;
        }

        return file_get_contents($style_config['csl_file']);
    }

    /**
     * Parse CSL style for configuration
     *
     * @param string $csl_content CSL XML content
     * @return array Parsed style configuration
     */
    public function parse_csl_style($csl_content) {
        $config = [
            'title' => '',
            'id' => '',
            'class' => 'in-text',
            'default_locale' => 'en-US',
            'citation_format' => '',
            'bibliography_format' => ''
        ];

        if (empty($csl_content)) {
            return $config;
        }

        // Parse XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($csl_content);
        
        if ($xml === false) {
            return $config;
        }

        // Extract basic info
        $info = $xml->info ?? null;
        if ($info) {
            $config['title'] = (string) ($info->title ?? '');
            $config['id'] = (string) ($info->id ?? '');
        }

        // Extract citation format
        $citation = $xml->citation ?? null;
        if ($citation) {
            $attributes = $citation->attributes();
            $config['citation_format'] = (string) ($attributes['collapse'] ?? '');
        }

        // Extract bibliography format
        $bibliography = $xml->bibliography ?? null;
        if ($bibliography) {
            $attributes = $bibliography->attributes();
            $config['bibliography_format'] = (string) ($attributes['hanging_indent'] ?? '');
        }

        return $config;
    }

    /**
     * Get built-in style formatters
     *
     * @param string $style_id Style identifier
     * @return array Style formatting rules
     */
    public function get_builtin_formatter($style_id) {
        $formatters = [
            'apa' => [
                'inline_citation' => [
                    'format' => '({author}, {year})',
                    'multiple_separator' => '; ',
                    'page_format' => ', p. {page}',
                    'author_format' => 'last_name_only',
                    'suppress_author_format' => '({year})'
                ],
                'bibliography' => [
                    'hanging_indent' => true,
                    'entry_spacing' => 'double',
                    'author_format' => 'last_first',
                    'title_format' => 'sentence_case',
                    'journal_format' => 'italic'
                ]
            ],
            'mla' => [
                'inline_citation' => [
                    'format' => '({author} {page})',
                    'multiple_separator' => '; ',
                    'author_format' => 'last_name_only',
                    'suppress_author_format' => '({page})'
                ],
                'bibliography' => [
                    'hanging_indent' => true,
                    'entry_spacing' => 'double',
                    'author_format' => 'last_first',
                    'title_format' => 'title_case',
                    'journal_format' => 'italic'
                ]
            ],
            'chicago' => [
                'inline_citation' => [
                    'format' => '({author} {year})',
                    'multiple_separator' => '; ',
                    'page_format' => ', {page}',
                    'author_format' => 'last_name_only',
                    'suppress_author_format' => '({year})'
                ],
                'bibliography' => [
                    'hanging_indent' => true,
                    'entry_spacing' => 'single',
                    'author_format' => 'last_first',
                    'title_format' => 'title_case',
                    'journal_format' => 'italic'
                ]
            ],
            'harvard' => [
                'inline_citation' => [
                    'format' => '({author}, {year})',
                    'multiple_separator' => '; ',
                    'page_format' => ', p.{page}',
                    'author_format' => 'last_name_only',
                    'suppress_author_format' => '({year})'
                ],
                'bibliography' => [
                    'hanging_indent' => true,
                    'entry_spacing' => 'single',
                    'author_format' => 'last_first',
                    'title_format' => 'sentence_case',
                    'journal_format' => 'italic'
                ]
            ],
            'ieee' => [
                'inline_citation' => [
                    'format' => '[{number}]',
                    'multiple_separator' => ', ',
                    'page_format' => ', p. {page}',
                    'numbered' => true
                ],
                'bibliography' => [
                    'hanging_indent' => false,
                    'entry_spacing' => 'single',
                    'numbered' => true,
                    'author_format' => 'first_last',
                    'title_format' => 'title_case',
                    'journal_format' => 'italic'
                ]
            ],
            'vancouver' => [
                'inline_citation' => [
                    'format' => '({number})',
                    'multiple_separator' => ',',
                    'numbered' => true
                ],
                'bibliography' => [
                    'hanging_indent' => false,
                    'entry_spacing' => 'single',
                    'numbered' => true,
                    'author_format' => 'last_first_abbreviated',
                    'title_format' => 'sentence_case',
                    'journal_format' => 'abbreviated'
                ]
            ]
        ];

        return $formatters[$style_id] ?? [];
    }

    /**
     * Validate style configuration
     *
     * @param array $config Style configuration
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_style_config($config) {
        $required_fields = ['name', 'inline_format', 'bibliography_format'];
        
        foreach ($required_fields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                return new WP_Error('missing_field', sprintf(__('Missing required field: %s', 'academic-bloggers-toolkit'), $field));
            }
        }

        // Validate inline format
        $valid_inline_formats = ['author-date', 'author-page', 'numeric'];
        if (!in_array($config['inline_format'], $valid_inline_formats)) {
            return new WP_Error('invalid_inline_format', __('Invalid inline citation format', 'academic-bloggers-toolkit'));
        }

        // Validate bibliography format
        $valid_bib_formats = ['hanging-indent', 'numbered', 'flush-left'];
        if (!in_array($config['bibliography_format'], $valid_bib_formats)) {
            return new WP_Error('invalid_bibliography_format', __('Invalid bibliography format', 'academic-bloggers-toolkit'));
        }

        return true;
    }

    /**
     * Import CSL style from file
     *
     * @param string $file_path Path to CSL file
     * @param string $style_id Style identifier
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function import_csl_style($file_path, $style_id) {
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', __('CSL file not found', 'academic-bloggers-toolkit'));
        }

        $csl_content = file_get_contents($file_path);
        
        if ($csl_content === false) {
            return new WP_Error('read_error', __('Could not read CSL file', 'academic-bloggers-toolkit'));
        }

        // Parse CSL to extract metadata
        $csl_config = $this->parse_csl_style($csl_content);
        
        // Copy file to assets directory
        $destination = ABT_PLUGIN_DIR . 'assets/citation-styles/' . $style_id . '.csl';
        
        if (!copy($file_path, $destination)) {
            return new WP_Error('copy_error', __('Could not copy CSL file', 'academic-bloggers-toolkit'));
        }

        // Register the style
        $this->register_style($style_id, [
            'name' => $csl_config['title'] ?: $style_id,
            'description' => __('Custom imported CSL style', 'academic-bloggers-toolkit'),
            'csl_file' => $destination,
            'inline_format' => 'author-date', // Default, could be parsed from CSL
            'bibliography_format' => 'hanging-indent'
        ]);

        return true;
    }

    /**
     * Export style configuration
     *
     * @param string $style_id Style identifier
     * @return array|false Style export data or false if not found
     */
    public function export_style_config($style_id) {
        $style_config = $this->get_style($style_id);
        
        if (!$style_config) {
            return false;
        }

        $export_data = [
            'style_id' => $style_id,
            'config' => $style_config,
            'csl_content' => null
        ];

        // Include CSL content if available
        if ($style_config['csl_file']) {
            $export_data['csl_content'] = $this->load_csl_style($style_id);
        }

        return $export_data;
    }

    /**
     * Ensure cache directory exists
     */
    private function ensure_cache_directory() {
        if (!file_exists($this->cache_dir)) {
            wp_mkdir_p($this->cache_dir);
            
            // Add .htaccess for security
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents($this->cache_dir . '.htaccess', $htaccess_content);
        }
    }

    /**
     * Clear style cache
     */
    public function clear_cache() {
        $files = glob($this->cache_dir . '*');
        
        foreach ($files as $file) {
            if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'htaccess') {
                unlink($file);
            }
        }
    }

    /**
     * Get style preferences for user
     *
     * @param int $user_id User ID (default: current user)
     * @return array User style preferences
     */
    public function get_user_style_preferences($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $preferences = get_user_meta($user_id, 'abt_style_preferences', true);
        
        return wp_parse_args($preferences, [
            'default_style' => 'apa',
            'inline_style' => 'parenthetical',
            'bibliography_style' => 'hanging',
            'auto_generate_bibliography' => true
        ]);
    }

    /**
     * Update user style preferences
     *
     * @param array $preferences Style preferences
     * @param int $user_id User ID (default: current user)
     * @return bool Success status
     */
    public function update_user_style_preferences($preferences, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return update_user_meta($user_id, 'abt_style_preferences', $preferences);
    }
}