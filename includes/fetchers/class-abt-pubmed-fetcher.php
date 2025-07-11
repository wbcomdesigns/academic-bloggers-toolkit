<?php
/**
 * PubMed Fetcher - PMID API Integration
 *
 * Fetches reference data from PMIDs using the NCBI E-utilities API
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
 * PubMed Fetcher Class
 *
 * Handles fetching reference data from PMIDs via NCBI E-utilities API
 */
class ABT_PubMed_Fetcher extends ABT_Base_Fetcher {

    /**
     * Fetcher name
     *
     * @var string
     */
    protected $name = 'pubmed';

    /**
     * NCBI E-utilities API endpoint
     *
     * @var string
     */
    protected $api_endpoint = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/';

    /**
     * Initialize the PubMed fetcher
     */
    protected function init() {
        // Set moderate rate limits for NCBI (they require courtesy)
        $this->rate_limit = [
            'requests_per_minute' => 30,
            'requests_per_hour' => 600
        ];
        
        // Set longer cache TTL for PubMed data (changes rarely)
        $this->cache_ttl = 604800; // 7 days
    }

    /**
     * Fetch reference data by PMID
     *
     * @param string $pmid The PMID to fetch
     * @return array|WP_Error Reference data or error
     */
    public function fetch($pmid) {
        // Clean and validate PMID
        $pmid = $this->clean_identifier($pmid);
        
        if (!$this->validate_identifier($pmid)) {
            return new WP_Error('invalid_pmid', __('Invalid PMID format', 'academic-bloggers-toolkit'));
        }

        // Remove PMID prefix if present
        $pmid = $this->normalize_pmid($pmid);

        try {
            // First, get article details from esummary
            $summary_data = $this->fetch_summary($pmid);
            
            if (is_wp_error($summary_data)) {
                return $summary_data;
            }

            // Then get full details from efetch
            $detail_data = $this->fetch_details($pmid);
            
            if (is_wp_error($detail_data)) {
                // If efetch fails, use summary data
                $this->log_error('PMID efetch failed, using summary data', ['pmid' => $pmid]);
                $detail_data = [];
            }

            // Merge and normalize the data
            $merged_data = array_merge($summary_data, $detail_data);
            $normalized_data = $this->normalize_reference_data($merged_data);

            return $normalized_data;

        } catch (Exception $e) {
            $this->log_error('PubMed fetch failed', ['pmid' => $pmid, 'error' => $e->getMessage()]);
            return new WP_Error('fetch_error', __('Failed to fetch PubMed data', 'academic-bloggers-toolkit'));
        }
    }

    /**
     * Validate PMID format
     *
     * @param string $pmid PMID to validate
     * @return bool True if valid, false otherwise
     */
    public function validate_identifier($pmid) {
        // PMID should be 7-8 digits
        $pattern = '/^(pmid:?\s*)?(\d{7,8})$/i';
        return preg_match($pattern, trim($pmid));
    }

    /**
     * Get identifier type
     *
     * @return string
     */
    public function get_identifier_type() {
        return 'PMID';
    }

    /**
     * Normalize PMID format
     *
     * @param string $pmid Raw PMID
     * @return string Normalized PMID
     */
    private function normalize_pmid($pmid) {
        // Remove common prefixes
        $pmid = preg_replace('/^(pmid:?\s*)/i', '', trim($pmid));
        
        // Ensure it's numeric and right length
        if (preg_match('/^(\d{7,8})$/', $pmid, $matches)) {
            return $matches[1];
        }

        return false;
    }

    /**
     * Fetch article summary from esummary
     *
     * @param string $pmid PMID
     * @return array|WP_Error Summary data or error
     */
    private function fetch_summary($pmid) {
        $url = $this->api_endpoint . 'esummary.fcgi';
        
        $args = [
            'headers' => [
                'User-Agent' => $this->user_agent
            ]
        ];

        // Build query parameters
        $query_params = [
            'db' => 'pubmed',
            'id' => $pmid,
            'retmode' => 'json'
        ];

        $url .= '?' . http_build_query($query_params);

        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['result'][$pmid])) {
            return new WP_Error('pmid_not_found', __('PMID not found in PubMed', 'academic-bloggers-toolkit'));
        }

        return $response['result'][$pmid];
    }

    /**
     * Fetch article details from efetch
     *
     * @param string $pmid PMID
     * @return array|WP_Error Detail data or error
     */
    private function fetch_details($pmid) {
        $url = $this->api_endpoint . 'efetch.fcgi';
        
        $args = [
            'headers' => [
                'User-Agent' => $this->user_agent
            ]
        ];

        // Build query parameters
        $query_params = [
            'db' => 'pubmed',
            'id' => $pmid,
            'retmode' => 'xml'
        ];

        $url .= '?' . http_build_query($query_params);

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            return new WP_Error('http_error', sprintf(__('HTTP Error %d', 'academic-bloggers-toolkit'), $response_code));
        }

        // Parse XML response
        return $this->parse_pubmed_xml($response_body);
    }

    /**
     * Parse PubMed XML response
     *
     * @param string $xml_content XML content
     * @return array Parsed data
     */
    private function parse_pubmed_xml($xml_content) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_content);
        
        if ($xml === false) {
            return [];
        }

        $article_data = [];
        
        // Navigate to article data
        if (isset($xml->PubmedArticle->MedlineCitation->Article)) {
            $article = $xml->PubmedArticle->MedlineCitation->Article;
            
            // Abstract
            if (isset($article->Abstract->AbstractText)) {
                $abstract_parts = [];
                foreach ($article->Abstract->AbstractText as $abstract_text) {
                    $abstract_parts[] = (string) $abstract_text;
                }
                $article_data['abstract'] = implode(' ', $abstract_parts);
            }

            // Keywords
            if (isset($xml->PubmedArticle->MedlineCitation->KeywordList->Keyword)) {
                $keywords = [];
                foreach ($xml->PubmedArticle->MedlineCitation->KeywordList->Keyword as $keyword) {
                    $keywords[] = (string) $keyword;
                }
                $article_data['keywords'] = implode(', ', $keywords);
            }

            // DOI from ArticleIdList
            if (isset($xml->PubmedArticle->PubmedData->ArticleIdList->ArticleId)) {
                foreach ($xml->PubmedArticle->PubmedData->ArticleIdList->ArticleId as $article_id) {
                    $id_type = (string) $article_id['IdType'];
                    if ($id_type === 'doi') {
                        $article_data['doi'] = (string) $article_id;
                        break;
                    }
                }
            }
        }

        return $article_data;
    }

    /**
     * Normalize PubMed response data
     *
     * @param array $pubmed_data Raw PubMed response
     * @return array Normalized reference data
     */
    protected function normalize_reference_data($pubmed_data) {
        $normalized = [
            'type' => 'article-journal',
            'title' => $pubmed_data['title'] ?? '',
            'PMID' => $pubmed_data['uid'] ?? '',
        ];

        // Authors
        if (isset($pubmed_data['authors'])) {
            $normalized['author'] = $this->extract_authors($pubmed_data['authors']);
        }

        // Publication date
        if (isset($pubmed_data['pubdate'])) {
            $normalized['issued'] = $this->parse_pubmed_date($pubmed_data['pubdate']);
        }

        // Journal information
        if (isset($pubmed_data['fulljournalname'])) {
            $normalized['container-title'] = $pubmed_data['fulljournalname'];
        } elseif (isset($pubmed_data['source'])) {
            $normalized['container-title'] = $pubmed_data['source'];
        }

        // Volume and issue
        if (isset($pubmed_data['volume'])) {
            $normalized['volume'] = $pubmed_data['volume'];
        }

        if (isset($pubmed_data['issue'])) {
            $normalized['issue'] = $pubmed_data['issue'];
        }

        // Pages
        if (isset($pubmed_data['pages'])) {
            $normalized['page'] = $pubmed_data['pages'];
        }

        // DOI
        if (isset($pubmed_data['doi'])) {
            $normalized['DOI'] = $pubmed_data['doi'];
            $normalized['URL'] = 'https://doi.org/' . $pubmed_data['doi'];
        }

        // Abstract
        if (isset($pubmed_data['abstract'])) {
            $normalized['abstract'] = wp_strip_all_tags($pubmed_data['abstract']);
        }

        // Keywords
        if (isset($pubmed_data['keywords'])) {
            $normalized['keyword'] = $pubmed_data['keywords'];
        }

        // PubMed URL
        if (isset($pubmed_data['uid'])) {
            $normalized['URL'] = $normalized['URL'] ?? 'https://pubmed.ncbi.nlm.nih.gov/' . $pubmed_data['uid'] . '/';
        }

        // Add PubMed-specific metadata
        $normalized['_pubmed_data'] = [
            'pmid' => $pubmed_data['uid'] ?? '',
            'pmcid' => $pubmed_data['pmcid'] ?? '',
            'publication_types' => $pubmed_data['pubtype'] ?? [],
            'mesh_terms' => $pubmed_data['keywords'] ?? '',
            'language' => $pubmed_data['lang'] ?? ['eng'],
            'indexed_date' => $pubmed_data['entrezdate'] ?? '',
            'sort_date' => $pubmed_data['sortdate'] ?? ''
        ];

        return $normalized;
    }

    /**
     * Parse PubMed date format
     *
     * @param string $pubmed_date PubMed date string
     * @return array CSL date format
     */
    private function parse_pubmed_date($pubmed_date) {
        // PubMed dates can be in various formats: "2023", "2023 Jan", "2023 Jan 15"
        $date_parts = [];
        
        if (preg_match('/(\d{4})(?:\s+(\w+))?(?:\s+(\d{1,2}))?/', $pubmed_date, $matches)) {
            $date_parts[] = (int) $matches[1]; // Year
            
            if (isset($matches[2])) {
                $month_names = [
                    'Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4,
                    'May' => 5, 'Jun' => 6, 'Jul' => 7, 'Aug' => 8,
                    'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12
                ];
                
                $month_abbr = substr($matches[2], 0, 3);
                if (isset($month_names[$month_abbr])) {
                    $date_parts[] = $month_names[$month_abbr];
                    
                    if (isset($matches[3])) {
                        $date_parts[] = (int) $matches[3]; // Day
                    }
                }
            }
        }

        return ['date-parts' => [$date_parts]];
    }

    /**
     * Search PubMed for articles
     *
     * @param string $query Search query
     * @param int $limit Results limit
     * @param int $offset Results offset
     * @return array|WP_Error Search results or error
     */
    public function search($query, $limit = 10, $offset = 0) {
        $url = $this->api_endpoint . 'esearch.fcgi';
        
        $args = [
            'headers' => [
                'User-Agent' => $this->user_agent
            ]
        ];

        // Build query parameters
        $query_params = [
            'db' => 'pubmed',
            'term' => urlencode($query),
            'retmax' => min($limit, 100), // NCBI max is 10000, but we limit to 100
            'retstart' => $offset,
            'retmode' => 'json',
            'sort' => 'relevance'
        ];

        $url .= '?' . http_build_query($query_params);

        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['esearchresult']['idlist'])) {
            return new WP_Error('invalid_search_response', __('Invalid search response from PubMed', 'academic-bloggers-toolkit'));
        }

        $pmids = $response['esearchresult']['idlist'];
        
        if (empty($pmids)) {
            return [
                'items' => [],
                'total_results' => 0,
                'items_per_page' => $limit,
                'query' => $query
            ];
        }

        // Fetch summaries for found PMIDs
        $results = [];
        foreach (array_slice($pmids, 0, $limit) as $pmid) {
            $summary = $this->fetch_summary($pmid);
            
            if (!is_wp_error($summary)) {
                $normalized = $this->normalize_reference_data($summary);
                $normalized['_search_snippet'] = $this->generate_search_snippet($normalized);
                $results[] = $normalized;
            }
        }

        return [
            'items' => $results,
            'total_results' => (int) ($response['esearchresult']['count'] ?? count($results)),
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
            $snippet_parts[] = $reference_data['title'];
        }

        // Journal
        if (!empty($reference_data['container-title'])) {
            $snippet_parts[] = '<em>' . $reference_data['container-title'] . '</em>';
        }

        // PMID
        if (!empty($reference_data['PMID'])) {
            $snippet_parts[] = 'PMID: ' . $reference_data['PMID'];
        }

        return implode(' ', $snippet_parts);
    }

    /**
     * Get PMID from URL
     *
     * @param string $url URL that might contain a PMID
     * @return string|false PMID if found, false otherwise
     */
    public function extract_pmid_from_url($url) {
        // Pattern to match PMID in URLs
        $patterns = [
            '/pubmed\.ncbi\.nlm\.nih\.gov\/(\d{7,8})/i',
            '/ncbi\.nlm\.nih\.gov\/pubmed\/(\d{7,8})/i',
            '/pmid:?\s*(\d{7,8})/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return false;
    }

    /**
     * Bulk fetch PMIDs
     *
     * @param array $pmids Array of PMIDs to fetch
     * @return array Array of results (successful and failed)
     */
    public function bulk_fetch($pmids) {
        $results = [
            'successful' => [],
            'failed' => []
        ];

        // NCBI allows multiple IDs in one request
        $batch_size = 20;
        $batches = array_chunk($pmids, $batch_size);

        foreach ($batches as $batch) {
            $batch_results = $this->fetch_batch_summaries($batch);
            
            if (is_wp_error($batch_results)) {
                foreach ($batch as $pmid) {
                    $results['failed'][] = [
                        'pmid' => $pmid,
                        'error' => $batch_results->get_error_message()
                    ];
                }
                continue;
            }

            foreach ($batch as $pmid) {
                if (isset($batch_results[$pmid])) {
                    $normalized = $this->normalize_reference_data($batch_results[$pmid]);
                    $results['successful'][] = $normalized;
                } else {
                    $results['failed'][] = [
                        'pmid' => $pmid,
                        'error' => 'PMID not found'
                    ];
                }
            }

            // Add delay between batches to be respectful
            usleep(200000); // 0.2 second
        }

        return $results;
    }

    /**
     * Fetch batch of summaries
     *
     * @param array $pmids Array of PMIDs
     * @return array|WP_Error Batch results or error
     */
    private function fetch_batch_summaries($pmids) {
        $url = $this->api_endpoint . 'esummary.fcgi';
        
        $args = [
            'headers' => [
                'User-Agent' => $this->user_agent
            ]
        ];

        $query_params = [
            'db' => 'pubmed',
            'id' => implode(',', $pmids),
            'retmode' => 'json'
        ];

        $url .= '?' . http_build_query($query_params);

        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        return $response['result'] ?? [];
    }
}