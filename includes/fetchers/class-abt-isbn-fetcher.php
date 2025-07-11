<?php
/**
 * ISBN Fetcher - Google Books API Integration
 *
 * Fetches reference data from ISBNs using the Google Books API
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
 * ISBN Fetcher Class
 *
 * Handles fetching reference data from ISBNs via Google Books API
 */
class ABT_ISBN_Fetcher extends ABT_Base_Fetcher {

    /**
     * Fetcher name
     *
     * @var string
     */
    protected $name = 'isbn';

    /**
     * Google Books API endpoint
     *
     * @var string
     */
    protected $api_endpoint = 'https://www.googleapis.com/books/v1/volumes';

    /**
     * Initialize the ISBN fetcher
     */
    protected function init() {
        // Set higher rate limits for Google Books (generous API)
        $this->rate_limit = [
            'requests_per_minute' => 100,
            'requests_per_hour' => 2000
        ];
        
        // Set longer cache TTL for book data (changes rarely)
        $this->cache_ttl = 2592000; // 30 days
    }

    /**
     * Fetch reference data by ISBN
     *
     * @param string $isbn The ISBN to fetch
     * @return array|WP_Error Reference data or error
     */
    public function fetch($isbn) {
        // Clean and validate ISBN
        $isbn = $this->clean_identifier($isbn);
        
        if (!$this->validate_identifier($isbn)) {
            return new WP_Error('invalid_isbn', __('Invalid ISBN format', 'academic-bloggers-toolkit'));
        }

        // Normalize ISBN
        $clean_isbn = $this->normalize_isbn($isbn);

        if (!$clean_isbn) {
            return new WP_Error('invalid_isbn', __('Could not normalize ISBN', 'academic-bloggers-toolkit'));
        }

        // Build API URL
        $url = $this->api_endpoint;

        $args = [
            'headers' => [
                'User-Agent' => $this->user_agent
            ]
        ];

        // Build query parameters
        $query_params = [
            'q' => 'isbn:' . $clean_isbn,
            'maxResults' => 1,
            'projection' => 'full'
        ];

        $url .= '?' . http_build_query($query_params);

        // Make request
        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            $this->log_error('ISBN fetch failed', ['isbn' => $isbn, 'error' => $response->get_error_message()]);
            return $response;
        }

        // Check if we got results
        if (!isset($response['items']) || empty($response['items'])) {
            return new WP_Error('isbn_not_found', __('ISBN not found in Google Books', 'academic-bloggers-toolkit'));
        }

        $book_data = $response['items'][0];

        // Normalize the response data
        $normalized_data = $this->normalize_reference_data($book_data);

        return $normalized_data;
    }

    /**
     * Validate ISBN format
     *
     * @param string $isbn ISBN to validate
     * @return bool True if valid, false otherwise
     */
    public function validate_identifier($isbn) {
        // Remove hyphens and spaces
        $clean_isbn = preg_replace('/[^0-9X]/i', '', $isbn);
        
        // Check length (ISBN-10 or ISBN-13)
        return strlen($clean_isbn) === 10 || strlen($clean_isbn) === 13;
    }

    /**
     * Get identifier type
     *
     * @return string
     */
    public function get_identifier_type() {
        return 'ISBN';
    }

    /**
     * Normalize ISBN format
     *
     * @param string $isbn Raw ISBN
     * @return string|false Normalized ISBN
     */
    private function normalize_isbn($isbn) {
        // Remove common prefixes and clean
        $isbn = preg_replace('/^isbn:?\s*/i', '', trim($isbn));
        $isbn = preg_replace('/[^0-9X]/i', '', $isbn);
        
        // Validate length
        if (strlen($isbn) === 10 || strlen($isbn) === 13) {
            return strtoupper($isbn);
        }

        return false;
    }

    /**
     * Normalize Google Books response data
     *
     * @param array $book_data Raw Google Books response
     * @return array Normalized reference data
     */
    protected function normalize_reference_data($book_data) {
        $volume_info = $book_data['volumeInfo'] ?? [];
        
        $normalized = [
            'type' => 'book',
            'title' => $volume_info['title'] ?? '',
        ];

        // Subtitle
        if (!empty($volume_info['subtitle'])) {
            $normalized['title'] .= ': ' . $volume_info['subtitle'];
        }

        // Authors
        if (isset($volume_info['authors'])) {
            $normalized['author'] = $this->extract_authors($volume_info['authors']);
        }

        // Editors (if no authors but have editors in description)
        if (empty($normalized['author']) && isset($volume_info['description'])) {
            $editors = $this->extract_editors_from_description($volume_info['description']);
            if (!empty($editors)) {
                $normalized['editor'] = $editors;
            }
        }

        // Publication date
        if (isset($volume_info['publishedDate'])) {
            $normalized['issued'] = $this->extract_date($volume_info['publishedDate']);
        }

        // Publisher
        if (isset($volume_info['publisher'])) {
            $normalized['publisher'] = $volume_info['publisher'];
        }

        // ISBN
        if (isset($volume_info['industryIdentifiers'])) {
            foreach ($volume_info['industryIdentifiers'] as $identifier) {
                if (in_array($identifier['type'], ['ISBN_10', 'ISBN_13'])) {
                    $normalized['ISBN'] = $identifier['identifier'];
                    break;
                }
            }
        }

        // Page count
        if (isset($volume_info['pageCount'])) {
            $normalized['number-of-pages'] = $volume_info['pageCount'];
        }

        // Language
        if (isset($volume_info['language'])) {
            $normalized['language'] = $volume_info['language'];
        }

        // Categories/subjects
        if (isset($volume_info['categories'])) {
            $normalized['keyword'] = implode(', ', $volume_info['categories']);
        }

        // Description/Abstract
        if (isset($volume_info['description'])) {
            $normalized['abstract'] = wp_strip_all_tags($volume_info['description']);
        }

        // URLs
        if (isset($volume_info['canonicalVolumeLink'])) {
            $normalized['URL'] = $volume_info['canonicalVolumeLink'];
        } elseif (isset($volume_info['infoLink'])) {
            $normalized['URL'] = $volume_info['infoLink'];
        }

        // Edition
        if (isset($volume_info['edition'])) {
            $normalized['edition'] = $volume_info['edition'];
        }

        // Series information
        if (isset($volume_info['seriesInfo'])) {
            $normalized['collection-title'] = $volume_info['seriesInfo']['volumeSeries'][0]['seriesId'] ?? '';
        }

        // Add Google Books specific metadata
        $normalized['_google_books_data'] = [
            'google_books_id' => $book_data['id'] ?? '',
            'preview_link' => $volume_info['previewLink'] ?? '',
            'info_link' => $volume_info['infoLink'] ?? '',
            'canonical_link' => $volume_info['canonicalVolumeLink'] ?? '',
            'average_rating' => $volume_info['averageRating'] ?? 0,
            'ratings_count' => $volume_info['ratingsCount'] ?? 0,
            'maturity_rating' => $volume_info['maturityRating'] ?? '',
            'print_type' => $volume_info['printType'] ?? '',
            'content_version' => $volume_info['contentVersion'] ?? '',
            'image_links' => $volume_info['imageLinks'] ?? []
        ];

        return $normalized;
    }

    /**
     * Extract editors from book description
     *
     * @param string $description Book description
     * @return array Extracted editors
     */
    private function extract_editors_from_description($description) {
        $editors = [];
        
        // Look for common editor patterns
        $patterns = [
            '/edited by ([^.]+)/i',
            '/editor[s]?:\s*([^.]+)/i',
            '/\(ed[s]?\.\)\s*([^.]+)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                $editor_string = trim($matches[1]);
                $editors = $this->extract_authors($editor_string);
                break;
            }
        }

        return $editors;
    }

    /**
     * Search Google Books
     *
     * @param string $query Search query
     * @param int $limit Results limit
     * @param int $offset Results offset
     * @return array|WP_Error Search results or error
     */
    public function search($query, $limit = 10, $offset = 0) {
        $url = $this->api_endpoint;
        
        $args = [
            'headers' => [
                'User-Agent' => $this->user_agent
            ]
        ];

        // Build query parameters
        $query_params = [
            'q' => urlencode($query),
            'maxResults' => min($limit, 40), // Google Books max is 40
            'startIndex' => $offset,
            'projection' => 'lite',
            'orderBy' => 'relevance'
        ];

        $url .= '?' . http_build_query($query_params);

        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['items'])) {
            return [
                'items' => [],
                'total_results' => 0,
                'items_per_page' => $limit,
                'query' => $query
            ];
        }

        $results = [];
        
        foreach ($response['items'] as $item) {
            $normalized = $this->normalize_reference_data($item);
            $normalized['_search_snippet'] = $this->generate_search_snippet($normalized);
            $results[] = $normalized;
        }

        return [
            'items' => $results,
            'total_results' => $response['totalItems'] ?? count($results),
            'items_per_page' => $limit,
            'query' => $query
        ];
    }

    /**
     * Generate search snippet for display
     *
     * @param array $reference_data Normalized reference data
     * @return string Search snippet
     */
    private function generate_search_snippet($reference_data) {
        $snippet_parts = [];

        // Authors
        if (!empty($reference_data['author'])) {
            $authors = array_slice($reference_data['author'], 0, 3);
            $author_names = [];
            
            foreach ($authors as $author) {
                if (is_array($author)) {
                    $name = trim(($author['given'] ?? '') . ' ' . ($author['family'] ?? ''));
                    if (!empty($name)) {
                        $author_names[] = $name;
                    }
                }
            }
            
            if (!empty($author_names)) {
                $snippet_parts[] = implode(', ', $author_names);
                if (count($reference_data['author']) > 3) {
                    $snippet_parts[0] .= ' et al.';
                }
            }
        }

        // Year
        if (!empty($reference_data['issued']['date-parts'][0][0])) {
            $snippet_parts[] = '(' . $reference_data['issued']['date-parts'][0][0] . ')';
        }

        // Title
        if (!empty($reference_data['title'])) {
            $snippet_parts[] = '<em>' . $reference_data['title'] . '</em>';
        }

        // Publisher
        if (!empty($reference_data['publisher'])) {
            $snippet_parts[] = $reference_data['publisher'];
        }

        // ISBN
        if (!empty($reference_data['ISBN'])) {
            $snippet_parts[] = 'ISBN: ' . $reference_data['ISBN'];
        }

        return implode(' ', $snippet_parts);
    }

    /**
     * Get ISBN from text or URL
     *
     * @param string $text Text that might contain an ISBN
     * @return string|false ISBN if found, false otherwise
     */
    public function extract_isbn_from_text($text) {
        // Patterns to match ISBN in various formats
        $patterns = [
            '/isbn[-:]?\s*([0-9-]{10,17})/i',
            '/(\d{9}[\dX])/i', // ISBN-10
            '/(\d{13})/i', // ISBN-13
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $potential_isbn = preg_replace('/[^0-9X]/i', '', $matches[1]);
                
                if ($this->validate_identifier($potential_isbn)) {
                    return $this->normalize_isbn($potential_isbn);
                }
            }
        }

        return false;
    }

    /**
     * Convert ISBN-10 to ISBN-13
     *
     * @param string $isbn10 ISBN-10
     * @return string|false ISBN-13 or false if invalid
     */
    public function isbn10_to_isbn13($isbn10) {
        if (strlen($isbn10) !== 10) {
            return false;
        }

        // Remove check digit and add 978 prefix
        $isbn13_base = '978' . substr($isbn10, 0, 9);
        
        // Calculate new check digit
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $multiplier = ($i % 2 === 0) ? 1 : 3;
            $sum += intval($isbn13_base[$i]) * $multiplier;
        }
        
        $check_digit = (10 - ($sum % 10)) % 10;
        
        return $isbn13_base . $check_digit;
    }

    /**
     * Convert ISBN-13 to ISBN-10
     *
     * @param string $isbn13 ISBN-13
     * @return string|false ISBN-10 or false if invalid
     */
    public function isbn13_to_isbn10($isbn13) {
        if (strlen($isbn13) !== 13 || substr($isbn13, 0, 3) !== '978') {
            return false;
        }

        // Extract ISBN-10 base (remove 978 prefix and check digit)
        $isbn10_base = substr($isbn13, 3, 9);
        
        // Calculate check digit for ISBN-10
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($isbn10_base[$i]) * (10 - $i);
        }
        
        $check_digit = (11 - ($sum % 11)) % 11;
        $check_char = ($check_digit === 10) ? 'X' : (string) $check_digit;
        
        return $isbn10_base . $check_char;
    }

    /**
     * Bulk fetch ISBNs
     *
     * @param array $isbns Array of ISBNs to fetch
     * @return array Array of results (successful and failed)
     */
    public function bulk_fetch($isbns) {
        $results = [
            'successful' => [],
            'failed' => []
        ];

        foreach ($isbns as $isbn) {
            $result = $this->fetch($isbn);
            
            if (is_wp_error($result)) {
                $results['failed'][] = [
                    'isbn' => $isbn,
                    'error' => $result->get_error_message()
                ];
            } else {
                $results['successful'][] = $result;
            }

            // Add small delay to be respectful to the API
            usleep(100000); // 0.1 second
        }

        return $results;
    }

    /**
     * Get book details with enhanced metadata
     *
     * @param string $google_books_id Google Books volume ID
     * @return array|WP_Error Enhanced book data or error
     */
    public function get_enhanced_details($google_books_id) {
        $url = $this->api_endpoint . '/' . urlencode($google_books_id);
        
        $args = [
            'headers' => [
                'User-Agent' => $this->user_agent
            ]
        ];

        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        return $this->normalize_reference_data($response);
    }

    /**
     * Search by specific fields
     *
     * @param array $search_fields Search criteria
     * @return array|WP_Error Search results or error
     */
    public function advanced_search($search_fields) {
        $query_parts = [];

        // Build advanced query
        if (!empty($search_fields['title'])) {
            $query_parts[] = 'intitle:' . urlencode($search_fields['title']);
        }

        if (!empty($search_fields['author'])) {
            $query_parts[] = 'inauthor:' . urlencode($search_fields['author']);
        }

        if (!empty($search_fields['publisher'])) {
            $query_parts[] = 'inpublisher:' . urlencode($search_fields['publisher']);
        }

        if (!empty($search_fields['subject'])) {
            $query_parts[] = 'subject:' . urlencode($search_fields['subject']);
        }

        if (!empty($search_fields['isbn'])) {
            $query_parts[] = 'isbn:' . urlencode($search_fields['isbn']);
        }

        if (empty($query_parts)) {
            return new WP_Error('empty_query', __('No search criteria provided', 'academic-bloggers-toolkit'));
        }

        $query = implode('+', $query_parts);
        $limit = $search_fields['limit'] ?? 10;
        $offset = $search_fields['offset'] ?? 0;

        return $this->search($query, $limit, $offset);
    }
}