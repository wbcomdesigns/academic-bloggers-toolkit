<?php
/**
 * DOI Fetcher - CrossRef API Integration
 *
 * Fetches reference data from DOIs using the CrossRef API
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
 * DOI Fetcher Class
 *
 * Handles fetching reference data from DOIs via CrossRef API
 */
class ABT_DOI_Fetcher extends ABT_Base_Fetcher {

    /**
     * Fetcher name
     *
     * @var string
     */
    protected $name = 'doi';

    /**
     * CrossRef API endpoint
     *
     * @var string
     */
    protected $api_endpoint = 'https://api.crossref.org/works/';

    /**
     * Initialize the DOI fetcher
     */
    protected function init() {
        // Set higher rate limits for CrossRef (they're generous)
        $this->rate_limit = [
            'requests_per_minute' => 50,
            'requests_per_hour' => 1000
        ];
        
        // Set longer cache TTL for DOI data (rarely changes)
        $this->cache_ttl = 604800; // 7 days
    }

    /**
     * Fetch reference data by DOI
     *
     * @param string $doi The DOI to fetch
     * @return array|WP_Error Reference data or error
     */
    public function fetch($doi) {
        // Clean and validate DOI
        $doi = $this->clean_identifier($doi);
        
        if (!$this->validate_identifier($doi)) {
            return new WP_Error('invalid_doi', __('Invalid DOI format', 'academic-bloggers-toolkit'));
        }

        // Remove DOI prefix if present
        $doi = $this->normalize_doi($doi);

        // Build API URL
        $url = $this->api_endpoint . urlencode($doi);

        // Set request headers for JSON response
        $args = [
            'headers' => [
                'Accept' => 'application/vnd.citationstyles.csl+json',
                'User-Agent' => $this->user_agent . ' (mailto:contact@example.com)' // Polite pool
            ]
        ];

        // Make request
        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            $this->log_error('DOI fetch failed', ['doi' => $doi, 'error' => $response->get_error_message()]);
            return $response;
        }

        // CrossRef returns the work data directly in CSL-JSON format
        if (!isset($response['message'])) {
            return new WP_Error('invalid_response', __('Invalid response from CrossRef API', 'academic-bloggers-toolkit'));
        }

        $work_data = $response['message'];

        // Normalize the response data
        $normalized_data = $this->normalize_reference_data($work_data);

        return $normalized_data;
    }

    /**
     * Validate DOI format
     *
     * @param string $doi DOI to validate
     * @return bool True if valid, false otherwise
     */
    public function validate_identifier($doi) {
        // DOI regex pattern
        $pattern = '/^(doi:)?(10\.\d{4,}\/[^\s]+)$/i';
        return preg_match($pattern, trim($doi));
    }

    /**
     * Get identifier type
     *
     * @return string
     */
    public function get_identifier_type() {
        return 'DOI';
    }

    /**
     * Normalize DOI format
     *
     * @param string $doi Raw DOI
     * @return string Normalized DOI
     */
    private function normalize_doi($doi) {
        // Remove common prefixes
        $doi = preg_replace('/^(doi:|https?:\/\/(dx\.)?doi\.org\/)/i', '', trim($doi));
        
        // Ensure it starts with 10.
        if (!preg_match('/^10\./', $doi)) {
            return false;
        }

        return $doi;
    }

    /**
     * Normalize CrossRef response data
     *
     * @param array $crossref_data Raw CrossRef response
     * @return array Normalized reference data
     */
    protected function normalize_reference_data($crossref_data) {
        $normalized = [
            'type' => $this->map_crossref_type($crossref_data['type'] ?? ''),
            'title' => $crossref_data['title'][0] ?? '',
            'DOI' => $crossref_data['DOI'] ?? '',
            'URL' => isset($crossref_data['DOI']) ? 'https://doi.org/' . $crossref_data['DOI'] : '',
        ];

        // Authors
        if (isset($crossref_data['author'])) {
            $normalized['author'] = $this->extract_authors($crossref_data['author']);
        }

        // Editors
        if (isset($crossref_data['editor'])) {
            $normalized['editor'] = $this->extract_authors($crossref_data['editor']);
        }

        // Publication date
        if (isset($crossref_data['published-print']['date-parts'][0])) {
            $normalized['issued'] = $crossref_data['published-print'];
        } elseif (isset($crossref_data['published-online']['date-parts'][0])) {
            $normalized['issued'] = $crossref_data['published-online'];
        } elseif (isset($crossref_data['created']['date-parts'][0])) {
            $normalized['issued'] = $crossref_data['created'];
        }

        // Container title (journal, book, etc.)
        if (isset($crossref_data['container-title'][0])) {
            $normalized['container-title'] = $crossref_data['container-title'][0];
        }

        // Volume, issue, pages
        if (isset($crossref_data['volume'])) {
            $normalized['volume'] = $crossref_data['volume'];
        }

        if (isset($crossref_data['issue'])) {
            $normalized['issue'] = $crossref_data['issue'];
        }

        if (isset($crossref_data['page'])) {
            $normalized['page'] = $crossref_data['page'];
        }

        // Publisher
        if (isset($crossref_data['publisher'])) {
            $normalized['publisher'] = $crossref_data['publisher'];
        }

        // ISBN/ISSN
        if (isset($crossref_data['ISBN'][0])) {
            $normalized['ISBN'] = $crossref_data['ISBN'][0];
        }

        if (isset($crossref_data['ISSN'][0])) {
            $normalized['ISSN'] = $crossref_data['ISSN'][0];
        }

        // Abstract
        if (isset($crossref_data['abstract'])) {
            $normalized['abstract'] = wp_strip_all_tags($crossref_data['abstract']);
        }

        // Language
        if (isset($crossref_data['language'])) {
            $normalized['language'] = $crossref_data['language'];
        }

        // Subject/Keywords
        if (isset($crossref_data['subject'])) {
            $normalized['keyword'] = implode(', ', $crossref_data['subject']);
        }

        // License
        if (isset($crossref_data['license'][0]['URL'])) {
            $normalized['license'] = $crossref_data['license'][0]['URL'];
        }

        // Add CrossRef-specific metadata
        $normalized['_crossref_data'] = [
            'is_referenced_by_count' => $crossref_data['is-referenced-by-count'] ?? 0,
            'references_count' => $crossref_data['references-count'] ?? 0,
            'score' => $crossref_data['score'] ?? 0,
            'indexed' => $crossref_data['indexed']['date-time'] ?? '',
            'deposited' => $crossref_data['deposited']['date-time'] ?? '',
            'prefix' => $crossref_data['prefix'] ?? '',
            'member' => $crossref_data['member'] ?? ''
        ];

        return $normalized;
    }

    /**
     * Map CrossRef type to CSL type
     *
     * @param string $crossref_type CrossRef work type
     * @return string CSL item type
     */
    private function map_crossref_type($crossref_type) {
        $type_mapping = [
            'journal-article' => 'article-journal',
            'book-chapter' => 'chapter',
            'book' => 'book',
            'proceedings-article' => 'paper-conference',
            'dissertation' => 'thesis',
            'report' => 'report',
            'dataset' => 'dataset',
            'book-section' => 'chapter',
            'monograph' => 'book',
            'reference-book' => 'book',
            'book-series' => 'book',
            'book-set' => 'book',
            'book-track' => 'chapter',
            'edited-book' => 'book',
            'journal' => 'periodical',
            'journal-issue' => 'article-journal',
            'journal-volume' => 'article-journal',
            'proceedings' => 'book',
            'standard' => 'standard',
            'posted-content' => 'manuscript'
        ];

        return $type_mapping[$crossref_type] ?? 'article';
    }

    /**
     * Search CrossRef for works
     *
     * @param string $query Search query
     * @param int $limit Results limit
     * @param int $offset Results offset
     * @return array|WP_Error Search results or error
     */
    public function search($query, $limit = 10, $offset = 0) {
        $url = 'https://api.crossref.org/works';
        
        $args = [
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => $this->user_agent . ' (mailto:contact@example.com)'
            ]
        ];

        // Build query parameters
        $query_params = [
            'query' => urlencode($query),
            'rows' => min($limit, 100), // CrossRef max is 1000, but we limit to 100
            'offset' => $offset,
            'sort' => 'relevance'
        ];

        $url .= '?' . http_build_query($query_params);

        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['message']['items'])) {
            return new WP_Error('invalid_search_response', __('Invalid search response from CrossRef', 'academic-bloggers-toolkit'));
        }

        $results = [];
        
        foreach ($response['message']['items'] as $item) {
            $normalized = $this->normalize_reference_data($item);
            
            // Add search-specific data
            $normalized['_search_score'] = $item['score'] ?? 0;
            $normalized['_search_snippet'] = $this->generate_search_snippet($normalized);
            
            $results[] = $normalized;
        }

        return [
            'items' => $results,
            'total_results' => $response['message']['total-results'] ?? count($results),
            'items_per_page' => $response['message']['items-per-page'] ?? $limit,
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
            $authors = array_slice($reference_data['author'], 0, 3); // First 3 authors
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
            $snippet_parts[] = $reference_data['title'];
        }

        // Journal/Container
        if (!empty($reference_data['container-title'])) {
            $snippet_parts[] = '<em>' . $reference_data['container-title'] . '</em>';
        }

        return implode(' ', $snippet_parts);
    }

    /**
     * Get DOI from URL
     *
     * @param string $url URL that might contain a DOI
     * @return string|false DOI if found, false otherwise
     */
    public function extract_doi_from_url($url) {
        // Pattern to match DOI in URLs
        $pattern = '/(?:doi:|https?:\/\/(?:dx\.)?doi\.org\/)(10\.\d{4,}\/[^\s&]+)/i';
        
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        return false;
    }

    /**
     * Bulk fetch DOIs
     *
     * @param array $dois Array of DOIs to fetch
     * @return array Array of results (successful and failed)
     */
    public function bulk_fetch($dois) {
        $results = [
            'successful' => [],
            'failed' => []
        ];

        foreach ($dois as $doi) {
            $result = $this->fetch($doi);
            
            if (is_wp_error($result)) {
                $results['failed'][] = [
                    'doi' => $doi,
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
     * Get CrossRef member information
     *
     * @param string $member_id CrossRef member ID
     * @return array|WP_Error Member information or error
     */
    public function get_member_info($member_id) {
        $url = "https://api.crossref.org/members/{$member_id}";
        
        $args = [
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => $this->user_agent
            ]
        ];

        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        return $response['message'] ?? [];
    }
}