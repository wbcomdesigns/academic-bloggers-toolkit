<?php
/**
 * Base Fetcher - Auto-Cite Foundation Class
 *
 * Abstract base class for all auto-cite fetchers
 *
 * @package Academic_Bloggers_Toolkit
 * @subpackage Fetchers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract Base Fetcher Class
 *
 * Provides common functionality for all auto-cite fetchers
 */
abstract class ABT_Base_Fetcher {

    /**
     * Fetcher name
     *
     * @var string
     */
    protected $name = '';

    /**
     * API endpoint
     *
     * @var string
     */
    protected $api_endpoint = '';

    /**
     * Rate limit settings
     *
     * @var array
     */
    protected $rate_limit = [
        'requests_per_minute' => 60,
        'requests_per_hour' => 1000
    ];

    /**
     * Cache TTL in seconds
     *
     * @var int
     */
    protected $cache_ttl = 86400; // 24 hours

    /**
     * User agent for API requests
     *
     * @var string
     */
    protected $user_agent = '';

    /**
     * Request timeout in seconds
     *
     * @var int
     */
    protected $timeout = 30;

    /**
     * Initialize the fetcher
     */
    public function __construct() {
        $this->user_agent = 'Academic Bloggers Toolkit/' . ABT_VERSION . ' (WordPress Plugin)';
        $this->init();
    }

    /**
     * Initialize the specific fetcher
     * Override in child classes
     */
    protected function init() {
        // Override in child classes
    }

    /**
     * Fetch reference data by identifier
     *
     * @param string $identifier The identifier (DOI, PMID, ISBN, etc.)
     * @return array|WP_Error Reference data or error
     */
    abstract public function fetch($identifier);

    /**
     * Validate identifier format
     *
     * @param string $identifier The identifier to validate
     * @return bool True if valid, false otherwise
     */
    abstract public function validate_identifier($identifier);

    /**
     * Get the identifier type name
     *
     * @return string Identifier type (e.g., 'DOI', 'PMID')
     */
    abstract public function get_identifier_type();

    /**
     * Make HTTP request with error handling and caching
     *
     * @param string $url Request URL
     * @param array $args Request arguments
     * @return array|WP_Error Response data or error
     */
    protected function make_request($url, $args = []) {
        // Check rate limiting
        if (!$this->check_rate_limit()) {
            return new WP_Error('rate_limit', __('Rate limit exceeded. Please try again later.', 'academic-bloggers-toolkit'));
        }

        // Check cache first
        $cache_key = $this->get_cache_key($url, $args);
        $cached_response = $this->get_cached_response($cache_key);
        
        if ($cached_response !== false) {
            return $cached_response;
        }

        // Record request for rate limiting
        $this->record_request();

        // Prepare request arguments
        $default_args = [
            'timeout' => $this->timeout,
            'user-agent' => $this->user_agent,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        ];

        $request_args = wp_parse_args($args, $default_args);

        // Make request
        $response = wp_remote_get($url, $request_args);

        // Handle errors
        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            return new WP_Error('http_error', sprintf(__('HTTP Error %d: %s', 'academic-bloggers-toolkit'), $response_code, $response_body));
        }

        // Parse JSON
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('Invalid JSON response', 'academic-bloggers-toolkit'));
        }

        // Cache successful response
        $this->cache_response($cache_key, $data);

        return $data;
    }

    /**
     * Normalize reference data to common format
     *
     * @param array $raw_data Raw API response data
     * @return array Normalized reference data
     */
    protected function normalize_reference_data($raw_data) {
        // Override in child classes to implement specific normalization
        return $raw_data;
    }

    /**
     * Extract authors from various formats
     *
     * @param mixed $authors Author data in various formats
     * @return array Normalized author array
     */
    protected function extract_authors($authors) {
        if (empty($authors)) {
            return [];
        }

        $normalized_authors = [];

        // Handle different author formats
        if (is_string($authors)) {
            // Split by common separators
            $author_list = preg_split('/[;,]|\band\b|\&/', $authors);
            
            foreach ($author_list as $author) {
                $author = trim($author);
                if (!empty($author)) {
                    $normalized_authors[] = $this->parse_author_name($author);
                }
            }
        } elseif (is_array($authors)) {
            foreach ($authors as $author) {
                if (is_string($author)) {
                    $normalized_authors[] = $this->parse_author_name($author);
                } elseif (is_array($author)) {
                    $normalized_authors[] = $this->normalize_author_object($author);
                }
            }
        }

        return $normalized_authors;
    }

    /**
     * Parse author name string into structured format
     *
     * @param string $name Author name string
     * @return array Structured author data
     */
    protected function parse_author_name($name) {
        $name = trim($name);
        
        // Handle "Last, First" format
        if (strpos($name, ',') !== false) {
            $parts = explode(',', $name, 2);
            $family = trim($parts[0]);
            $given = trim($parts[1]);
        } else {
            // Handle "First Last" format
            $parts = explode(' ', $name);
            $family = array_pop($parts);
            $given = implode(' ', $parts);
        }

        return [
            'family' => $family,
            'given' => $given
        ];
    }

    /**
     * Normalize author object from API response
     *
     * @param array $author_data Author data from API
     * @return array Normalized author data
     */
    protected function normalize_author_object($author_data) {
        // Common field mappings
        $field_mappings = [
            ['family', 'lastName', 'last_name', 'surname'],
            ['given', 'firstName', 'first_name', 'forename']
        ];

        $normalized = [];

        foreach ($field_mappings as $aliases) {
            $target_field = $aliases[0];
            
            foreach ($aliases as $field) {
                if (isset($author_data[$field]) && !empty($author_data[$field])) {
                    $normalized[$target_field] = $author_data[$field];
                    break;
                }
            }
        }

        return $normalized;
    }

    /**
     * Extract date from various formats
     *
     * @param mixed $date_data Date data in various formats
     * @return array CSL-JSON date format
     */
    protected function extract_date($date_data) {
        if (empty($date_data)) {
            return null;
        }

        // Handle string dates
        if (is_string($date_data)) {
            $timestamp = strtotime($date_data);
            if ($timestamp) {
                return [
                    'date-parts' => [[
                        (int) date('Y', $timestamp),
                        (int) date('n', $timestamp),
                        (int) date('j', $timestamp)
                    ]]
                ];
            }
        }

        // Handle array dates (like CrossRef format)
        if (is_array($date_data)) {
            if (isset($date_data['date-parts'][0])) {
                return $date_data; // Already in CSL format
            }
            
            if (isset($date_data[0]) && is_array($date_data[0])) {
                return ['date-parts' => $date_data];
            }
            
            if (isset($date_data['year'])) {
                $parts = [(int) $date_data['year']];
                if (isset($date_data['month'])) {
                    $parts[] = (int) $date_data['month'];
                }
                if (isset($date_data['day'])) {
                    $parts[] = (int) $date_data['day'];
                }
                return ['date-parts' => [$parts]];
            }
        }

        return null;
    }

    /**
     * Check rate limiting
     *
     * @return bool True if request is allowed, false if rate limited
     */
    protected function check_rate_limit() {
        $current_time = time();
        $minute_key = 'abt_rate_limit_' . $this->name . '_minute_' . floor($current_time / 60);
        $hour_key = 'abt_rate_limit_' . $this->name . '_hour_' . floor($current_time / 3600);

        $minute_count = (int) get_transient($minute_key);
        $hour_count = (int) get_transient($hour_key);

        return ($minute_count < $this->rate_limit['requests_per_minute']) && 
               ($hour_count < $this->rate_limit['requests_per_hour']);
    }

    /**
     * Record a request for rate limiting
     */
    protected function record_request() {
        $current_time = time();
        $minute_key = 'abt_rate_limit_' . $this->name . '_minute_' . floor($current_time / 60);
        $hour_key = 'abt_rate_limit_' . $this->name . '_hour_' . floor($current_time / 3600);

        $minute_count = (int) get_transient($minute_key);
        $hour_count = (int) get_transient($hour_key);

        set_transient($minute_key, $minute_count + 1, 60);
        set_transient($hour_key, $hour_count + 1, 3600);
    }

    /**
     * Get cache key for request
     *
     * @param string $url Request URL
     * @param array $args Request arguments
     * @return string Cache key
     */
    protected function get_cache_key($url, $args = []) {
        return 'abt_cache_' . $this->name . '_' . md5($url . serialize($args));
    }

    /**
     * Get cached response
     *
     * @param string $cache_key Cache key
     * @return mixed Cached data or false if not found
     */
    protected function get_cached_response($cache_key) {
        return get_transient($cache_key);
    }

    /**
     * Cache response data
     *
     * @param string $cache_key Cache key
     * @param mixed $data Data to cache
     */
    protected function cache_response($cache_key, $data) {
        set_transient($cache_key, $data, $this->cache_ttl);
    }

    /**
     * Clean identifier for processing
     *
     * @param string $identifier Raw identifier
     * @return string Cleaned identifier
     */
    protected function clean_identifier($identifier) {
        return trim($identifier);
    }

    /**
     * Log error for debugging
     *
     * @param string $message Error message
     * @param array $context Additional context
     */
    protected function log_error($message, $context = []) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[ABT %s Fetcher] %s - Context: %s', $this->name, $message, json_encode($context)));
        }
    }

    /**
     * Validate required fields in API response
     *
     * @param array $data API response data
     * @param array $required_fields Required field names
     * @return bool True if all required fields present
     */
    protected function validate_response_data($data, $required_fields = []) {
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get fetcher statistics
     *
     * @return array Fetcher usage statistics
     */
    public function get_statistics() {
        $current_time = time();
        $minute_key = 'abt_rate_limit_' . $this->name . '_minute_' . floor($current_time / 60);
        $hour_key = 'abt_rate_limit_' . $this->name . '_hour_' . floor($current_time / 3600);

        return [
            'name' => $this->name,
            'identifier_type' => $this->get_identifier_type(),
            'requests_this_minute' => (int) get_transient($minute_key),
            'requests_this_hour' => (int) get_transient($hour_key),
            'rate_limit' => $this->rate_limit,
            'cache_ttl' => $this->cache_ttl
        ];
    }

    /**
     * Clear cache for this fetcher
     */
    public function clear_cache() {
        global $wpdb;
        
        $cache_prefix = 'abt_cache_' . $this->name . '_';
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_' . $cache_prefix . '%'
        ));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_timeout_' . $cache_prefix . '%'
        ));
    }

    /**
     * Get fetcher configuration
     *
     * @return array Fetcher configuration
     */
    public function get_config() {
        return [
            'name' => $this->name,
            'identifier_type' => $this->get_identifier_type(),
            'api_endpoint' => $this->api_endpoint,
            'rate_limit' => $this->rate_limit,
            'cache_ttl' => $this->cache_ttl,
            'timeout' => $this->timeout
        ];
    }

    /**
     * Update fetcher configuration
     *
     * @param array $config New configuration
     */
    public function update_config($config) {
        if (isset($config['rate_limit'])) {
            $this->rate_limit = wp_parse_args($config['rate_limit'], $this->rate_limit);
        }
        
        if (isset($config['cache_ttl'])) {
            $this->cache_ttl = (int) $config['cache_ttl'];
        }
        
        if (isset($config['timeout'])) {
            $this->timeout = (int) $config['timeout'];
        }
    }

    /**
     * Test fetcher connectivity
     *
     * @return bool|WP_Error True if working, WP_Error if not
     */
    public function test_connection() {
        if (empty($this->api_endpoint)) {
            return new WP_Error('no_endpoint', __('No API endpoint configured', 'academic-bloggers-toolkit'));
        }

        $response = wp_remote_get($this->api_endpoint, [
            'timeout' => 10,
            'user-agent' => $this->user_agent
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code >= 400) {
            return new WP_Error('connection_failed', sprintf(__('API returned error code: %d', 'academic-bloggers-toolkit'), $response_code));
        }

        return true;
    }
}