<?php
/**
 * URL Scraper - Metadata Extraction
 *
 * Extracts reference metadata from web pages using various meta tag standards
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
 * URL Scraper Class
 *
 * Handles extracting reference metadata from web pages
 */
class ABT_URL_Scraper extends ABT_Base_Fetcher {

    /**
     * Fetcher name
     *
     * @var string
     */
    protected $name = 'url_scraper';

    /**
     * Initialize the URL scraper
     */
    protected function init() {
        // Set moderate rate limits for web scraping
        $this->rate_limit = [
            'requests_per_minute' => 30,
            'requests_per_hour' => 500
        ];
        
        // Set shorter cache TTL for web pages (content changes more frequently)
        $this->cache_ttl = 86400; // 1 day
    }

    /**
     * Fetch reference data from URL
     *
     * @param string $url The URL to scrape
     * @return array|WP_Error Reference data or error
     */
    public function fetch($url) {
        // Clean and validate URL
        $url = $this->clean_identifier($url);
        
        if (!$this->validate_identifier($url)) {
            return new WP_Error('invalid_url', __('Invalid URL format', 'academic-bloggers-toolkit'));
        }

        try {
            // Fetch the web page content
            $html_content = $this->fetch_page_content($url);
            
            if (is_wp_error($html_content)) {
                return $html_content;
            }

            // Extract metadata from the HTML
            $metadata = $this->extract_metadata($html_content, $url);

            // Normalize the extracted data
            $normalized_data = $this->normalize_reference_data($metadata);

            return $normalized_data;

        } catch (Exception $e) {
            $this->log_error('URL scraping failed', ['url' => $url, 'error' => $e->getMessage()]);
            return new WP_Error('scraping_error', __('Failed to scrape URL metadata', 'academic-bloggers-toolkit'));
        }
    }

    /**
     * Validate URL format
     *
     * @param string $url URL to validate
     * @return bool True if valid, false otherwise
     */
    public function validate_identifier($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Get identifier type
     *
     * @return string
     */
    public function get_identifier_type() {
        return 'URL';
    }

    /**
     * Fetch page content
     *
     * @param string $url URL to fetch
     * @return string|WP_Error Page content or error
     */
    private function fetch_page_content($url) {
        $args = [
            'timeout' => $this->timeout,
            'user-agent' => $this->user_agent,
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'DNT' => '1',
                'Connection' => 'keep-alive'
            ],
            'sslverify' => false // Allow self-signed certificates for academic sites
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return new WP_Error('http_error', sprintf(__('HTTP Error %d when fetching URL', 'academic-bloggers-toolkit'), $response_code));
        }

        $content = wp_remote_retrieve_body($response);
        
        if (empty($content)) {
            return new WP_Error('empty_content', __('No content found at URL', 'academic-bloggers-toolkit'));
        }

        return $content;
    }

    /**
     * Extract metadata from HTML content
     *
     * @param string $html HTML content
     * @param string $url Original URL
     * @return array Extracted metadata
     */
    private function extract_metadata($html, $url) {
        $metadata = [
            'url' => $url,
            'site_name' => parse_url($url, PHP_URL_HOST)
        ];

        // Parse HTML
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);

        // Extract various metadata formats
        $metadata = array_merge($metadata, $this->extract_open_graph($xpath));
        $metadata = array_merge($metadata, $this->extract_twitter_cards($xpath));
        $metadata = array_merge($metadata, $this->extract_dublin_core($xpath));
        $metadata = array_merge($metadata, $this->extract_highwire_press($xpath));
        $metadata = array_merge($metadata, $this->extract_basic_html($xpath));
        $metadata = array_merge($metadata, $this->extract_json_ld($html));
        $metadata = array_merge($metadata, $this->extract_microdata($xpath));

        // Extract DOI if present
        $doi = $this->extract_doi_from_page($html, $url);
        if ($doi) {
            $metadata['doi'] = $doi;
        }

        return $metadata;
    }

    /**
     * Extract Open Graph metadata
     *
     * @param DOMXPath $xpath XPath object
     * @return array Extracted data
     */
    private function extract_open_graph($xpath) {
        $data = [];
        
        $og_tags = $xpath->query('//meta[starts-with(@property, "og:")]');
        
        foreach ($og_tags as $tag) {
            $property = $tag->getAttribute('property');
            $content = $tag->getAttribute('content');
            
            switch ($property) {
                case 'og:title':
                    $data['title'] = $content;
                    break;
                case 'og:description':
                    $data['description'] = $content;
                    break;
                case 'og:site_name':
                    $data['site_name'] = $content;
                    break;
                case 'og:type':
                    $data['og_type'] = $content;
                    break;
                case 'og:url':
                    $data['canonical_url'] = $content;
                    break;
                case 'og:image':
                    $data['image'] = $content;
                    break;
            }
        }

        return $data;
    }

    /**
     * Extract Twitter Card metadata
     *
     * @param DOMXPath $xpath XPath object
     * @return array Extracted data
     */
    private function extract_twitter_cards($xpath) {
        $data = [];
        
        $twitter_tags = $xpath->query('//meta[starts-with(@name, "twitter:")]');
        
        foreach ($twitter_tags as $tag) {
            $name = $tag->getAttribute('name');
            $content = $tag->getAttribute('content');
            
            switch ($name) {
                case 'twitter:title':
                    $data['title'] = $data['title'] ?? $content;
                    break;
                case 'twitter:description':
                    $data['description'] = $data['description'] ?? $content;
                    break;
                case 'twitter:site':
                    $data['twitter_site'] = $content;
                    break;
                case 'twitter:creator':
                    $data['twitter_creator'] = $content;
                    break;
            }
        }

        return $data;
    }

    /**
     * Extract Dublin Core metadata
     *
     * @param DOMXPath $xpath XPath object
     * @return array Extracted data
     */
    private function extract_dublin_core($xpath) {
        $data = [];
        
        $dc_tags = $xpath->query('//meta[starts-with(@name, "DC.") or starts-with(@name, "dc.")]');
        
        foreach ($dc_tags as $tag) {
            $name = strtolower($tag->getAttribute('name'));
            $content = $tag->getAttribute('content');
            
            switch ($name) {
                case 'dc.title':
                    $data['title'] = $data['title'] ?? $content;
                    break;
                case 'dc.creator':
                case 'dc.author':
                    $data['author'] = $this->append_author($data['author'] ?? '', $content);
                    break;
                case 'dc.date':
                    $data['date'] = $content;
                    break;
                case 'dc.publisher':
                    $data['publisher'] = $content;
                    break;
                case 'dc.description':
                    $data['description'] = $data['description'] ?? $content;
                    break;
                case 'dc.subject':
                    $data['keywords'] = $this->append_keyword($data['keywords'] ?? '', $content);
                    break;
                case 'dc.identifier':
                    if (strpos($content, 'doi:') === 0 || strpos($content, '10.') === 0) {
                        $data['doi'] = str_replace('doi:', '', $content);
                    }
                    break;
            }
        }

        return $data;
    }

    /**
     * Extract Highwire Press metadata (used by many academic publishers)
     *
     * @param DOMXPath $xpath XPath object
     * @return array Extracted data
     */
    private function extract_highwire_press($xpath) {
        $data = [];
        
        $hw_tags = $xpath->query('//meta[starts-with(@name, "citation_")]');
        
        foreach ($hw_tags as $tag) {
            $name = $tag->getAttribute('name');
            $content = $tag->getAttribute('content');
            
            switch ($name) {
                case 'citation_title':
                    $data['title'] = $data['title'] ?? $content;
                    break;
                case 'citation_author':
                    $data['author'] = $this->append_author($data['author'] ?? '', $content);
                    break;
                case 'citation_publication_date':
                case 'citation_date':
                    $data['date'] = $content;
                    break;
                case 'citation_journal_title':
                    $data['journal'] = $content;
                    break;
                case 'citation_volume':
                    $data['volume'] = $content;
                    break;
                case 'citation_issue':
                    $data['issue'] = $content;
                    break;
                case 'citation_firstpage':
                    $data['start_page'] = $content;
                    break;
                case 'citation_lastpage':
                    $data['end_page'] = $content;
                    break;
                case 'citation_doi':
                    $data['doi'] = $content;
                    break;
                case 'citation_pmid':
                    $data['pmid'] = $content;
                    break;
                case 'citation_isbn':
                    $data['isbn'] = $content;
                    break;
                case 'citation_issn':
                    $data['issn'] = $content;
                    break;
                case 'citation_publisher':
                    $data['publisher'] = $content;
                    break;
                case 'citation_abstract':
                    $data['abstract'] = $content;
                    break;
                case 'citation_keywords':
                    $data['keywords'] = $content;
                    break;
            }
        }

        return $data;
    }

    /**
     * Extract basic HTML metadata
     *
     * @param DOMXPath $xpath XPath object
     * @return array Extracted data
     */
    private function extract_basic_html($xpath) {
        $data = [];

        // Title tag
        $title_nodes = $xpath->query('//title');
        if ($title_nodes->length > 0) {
            $data['title'] = $data['title'] ?? trim($title_nodes->item(0)->textContent);
        }

        // Meta description
        $desc_nodes = $xpath->query('//meta[@name="description"]/@content');
        if ($desc_nodes->length > 0) {
            $data['description'] = $data['description'] ?? $desc_nodes->item(0)->textContent;
        }

        // Meta keywords
        $keyword_nodes = $xpath->query('//meta[@name="keywords"]/@content');
        if ($keyword_nodes->length > 0) {
            $data['keywords'] = $data['keywords'] ?? $keyword_nodes->item(0)->textContent;
        }

        // Meta author
        $author_nodes = $xpath->query('//meta[@name="author"]/@content');
        if ($author_nodes->length > 0) {
            $data['author'] = $data['author'] ?? $author_nodes->item(0)->textContent;
        }

        // Canonical URL
        $canonical_nodes = $xpath->query('//link[@rel="canonical"]/@href');
        if ($canonical_nodes->length > 0) {
            $data['canonical_url'] = $canonical_nodes->item(0)->textContent;
        }

        return $data;
    }

    /**
     * Extract JSON-LD structured data
     *
     * @param string $html HTML content
     * @return array Extracted data
     */
    private function extract_json_ld($html) {
        $data = [];
        
        // Find JSON-LD scripts
        if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            foreach ($matches[1] as $json_content) {
                $structured_data = json_decode(trim($json_content), true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($structured_data)) {
                    $data = array_merge($data, $this->parse_json_ld($structured_data));
                }
            }
        }

        return $data;
    }

    /**
     * Parse JSON-LD structured data
     *
     * @param array $json_data JSON-LD data
     * @return array Extracted metadata
     */
    private function parse_json_ld($json_data) {
        $data = [];

        // Handle arrays of structured data
        if (isset($json_data[0])) {
            foreach ($json_data as $item) {
                $data = array_merge($data, $this->parse_single_json_ld($item));
            }
        } else {
            $data = $this->parse_single_json_ld($json_data);
        }

        return $data;
    }

    /**
     * Parse single JSON-LD item
     *
     * @param array $item JSON-LD item
     * @return array Extracted metadata
     */
    private function parse_single_json_ld($item) {
        $data = [];
        $type = $item['@type'] ?? '';

        switch ($type) {
            case 'Article':
            case 'ScholarlyArticle':
            case 'NewsArticle':
                $data['title'] = $data['title'] ?? $item['headline'] ?? $item['name'] ?? '';
                $data['description'] = $data['description'] ?? $item['description'] ?? '';
                $data['date'] = $item['datePublished'] ?? $item['dateCreated'] ?? '';
                
                if (isset($item['author'])) {
                    $data['author'] = $this->parse_json_ld_author($item['author']);
                }
                
                if (isset($item['publisher'])) {
                    $data['publisher'] = is_string($item['publisher']) ? $item['publisher'] : ($item['publisher']['name'] ?? '');
                }
                break;

            case 'Book':
                $data['title'] = $data['title'] ?? $item['name'] ?? '';
                $data['description'] = $data['description'] ?? $item['description'] ?? '';
                $data['date'] = $item['datePublished'] ?? '';
                $data['isbn'] = $item['isbn'] ?? '';
                
                if (isset($item['author'])) {
                    $data['author'] = $this->parse_json_ld_author($item['author']);
                }
                
                if (isset($item['publisher'])) {
                    $data['publisher'] = is_string($item['publisher']) ? $item['publisher'] : ($item['publisher']['name'] ?? '');
                }
                break;

            case 'WebPage':
            case 'WebSite':
                $data['title'] = $data['title'] ?? $item['name'] ?? '';
                $data['description'] = $data['description'] ?? $item['description'] ?? '';
                break;
        }

        return $data;
    }

    /**
     * Parse JSON-LD author data
     *
     * @param mixed $author_data Author data
     * @return string Formatted author string
     */
    private function parse_json_ld_author($author_data) {
        $authors = [];

        if (is_string($author_data)) {
            return $author_data;
        }

        if (isset($author_data['name'])) {
            return $author_data['name'];
        }

        if (is_array($author_data)) {
            foreach ($author_data as $author) {
                if (is_string($author)) {
                    $authors[] = $author;
                } elseif (isset($author['name'])) {
                    $authors[] = $author['name'];
                }
            }
        }

        return implode('; ', $authors);
    }

    /**
     * Extract microdata
     *
     * @param DOMXPath $xpath XPath object
     * @return array Extracted data
     */
    private function extract_microdata($xpath) {
        $data = [];

        // Look for Schema.org microdata
        $microdata_nodes = $xpath->query('//*[@itemtype]');
        
        foreach ($microdata_nodes as $node) {
            $itemtype = $node->getAttribute('itemtype');
            
            if (strpos($itemtype, 'schema.org') !== false) {
                $type = basename($itemtype);
                
                switch ($type) {
                    case 'Article':
                    case 'ScholarlyArticle':
                        $data = array_merge($data, $this->extract_article_microdata($xpath, $node));
                        break;
                    case 'Book':
                        $data = array_merge($data, $this->extract_book_microdata($xpath, $node));
                        break;
                }
            }
        }

        return $data;
    }

    /**
     * Extract article microdata
     *
     * @param DOMXPath $xpath XPath object
     * @param DOMElement $context Context node
     * @return array Extracted data
     */
    private function extract_article_microdata($xpath, $context) {
        $data = [];

        $title_nodes = $xpath->query('.//*[@itemprop="headline" or @itemprop="name"]', $context);
        if ($title_nodes->length > 0) {
            $data['title'] = $data['title'] ?? trim($title_nodes->item(0)->textContent);
        }

        $author_nodes = $xpath->query('.//*[@itemprop="author"]', $context);
        if ($author_nodes->length > 0) {
            $authors = [];
            foreach ($author_nodes as $author_node) {
                $authors[] = trim($author_node->textContent);
            }
            $data['author'] = implode('; ', $authors);
        }

        return $data;
    }

    /**
     * Extract book microdata
     *
     * @param DOMXPath $xpath XPath object
     * @param DOMElement $context Context node
     * @return array Extracted data
     */
    private function extract_book_microdata($xpath, $context) {
        $data = [];

        $title_nodes = $xpath->query('.//*[@itemprop="name"]', $context);
        if ($title_nodes->length > 0) {
            $data['title'] = $data['title'] ?? trim($title_nodes->item(0)->textContent);
        }

        $isbn_nodes = $xpath->query('.//*[@itemprop="isbn"]', $context);
        if ($isbn_nodes->length > 0) {
            $data['isbn'] = trim($isbn_nodes->item(0)->textContent);
        }

        return $data;
    }

    /**
     * Extract DOI from page content
     *
     * @param string $html HTML content
     * @param string $url Page URL
     * @return string|false DOI if found
     */
    private function extract_doi_from_page($html, $url) {
        // Try multiple DOI patterns
        $patterns = [
            '/(?:doi:|DOI:)\s*(10\.\d{4,}\/[^\s<>"]+)/i',
            '/https?:\/\/(?:dx\.)?doi\.org\/(10\.\d{4,}\/[^\s<>"]+)/i',
            '/"doi"\s*:\s*"(10\.\d{4,}\/[^"]+)"/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return $matches[1];
            }
        }

        return false;
    }

    /**
     * Append author to existing author string
     *
     * @param string $existing_authors Existing authors
     * @param string $new_author New author to add
     * @return string Combined author string
     */
    private function append_author($existing_authors, $new_author) {
        if (empty($existing_authors)) {
            return $new_author;
        }
        
        return $existing_authors . '; ' . $new_author;
    }

    /**
     * Append keyword to existing keywords string
     *
     * @param string $existing_keywords Existing keywords
     * @param string $new_keyword New keyword to add
     * @return string Combined keywords string
     */
    private function append_keyword($existing_keywords, $new_keyword) {
        if (empty($existing_keywords)) {
            return $new_keyword;
        }
        
        return $existing_keywords . ', ' . $new_keyword;
    }

    /**
     * Normalize URL scraper data
     *
     * @param array $scraped_data Raw scraped data
     * @return array Normalized reference data
     */
    protected function normalize_reference_data($scraped_data) {
        $normalized = [
            'type' => $this->determine_reference_type($scraped_data),
            'title' => $scraped_data['title'] ?? '',
            'URL' => $scraped_data['canonical_url'] ?? $scraped_data['url'] ?? '',
        ];

        // Authors
        if (!empty($scraped_data['author'])) {
            $normalized['author'] = $this->extract_authors($scraped_data['author']);
        }

        // Publication date
        if (!empty($scraped_data['date'])) {
            $normalized['issued'] = $this->extract_date($scraped_data['date']);
        }

        // Container title (website, journal, etc.)
        if (!empty($scraped_data['site_name'])) {
            $normalized['container-title'] = $scraped_data['site_name'];
        } elseif (!empty($scraped_data['journal'])) {
            $normalized['container-title'] = $scraped_data['journal'];
        }

        // Publisher
        if (!empty($scraped_data['publisher'])) {
            $normalized['publisher'] = $scraped_data['publisher'];
        }

        // Abstract/Description
        if (!empty($scraped_data['abstract'])) {
            $normalized['abstract'] = wp_strip_all_tags($scraped_data['abstract']);
        } elseif (!empty($scraped_data['description'])) {
            $normalized['abstract'] = wp_strip_all_tags($scraped_data['description']);
        }

        // Keywords
        if (!empty($scraped_data['keywords'])) {
            $normalized['keyword'] = $scraped_data['keywords'];
        }

        // Identifiers
        if (!empty($scraped_data['doi'])) {
            $normalized['DOI'] = $scraped_data['doi'];
        }

        if (!empty($scraped_data['pmid'])) {
            $normalized['PMID'] = $scraped_data['pmid'];
        }

        if (!empty($scraped_data['isbn'])) {
            $normalized['ISBN'] = $scraped_data['isbn'];
        }

        if (!empty($scraped_data['issn'])) {
            $normalized['ISSN'] = $scraped_data['issn'];
        }

        // Journal-specific fields
        if (!empty($scraped_data['volume'])) {
            $normalized['volume'] = $scraped_data['volume'];
        }

        if (!empty($scraped_data['issue'])) {
            $normalized['issue'] = $scraped_data['issue'];
        }

        // Pages
        if (!empty($scraped_data['start_page']) || !empty($scraped_data['end_page'])) {
            $pages = '';
            if (!empty($scraped_data['start_page'])) {
                $pages = $scraped_data['start_page'];
                if (!empty($scraped_data['end_page'])) {
                    $pages .= '-' . $scraped_data['end_page'];
                }
            } elseif (!empty($scraped_data['end_page'])) {
                $pages = $scraped_data['end_page'];
            }
            $normalized['page'] = $pages;
        }

        // Access date
        $normalized['accessed'] = [
            'date-parts' => [[
                (int) date('Y'),
                (int) date('n'),
                (int) date('j')
            ]]
        ];

        // Add scraping metadata
        $normalized['_scraping_data'] = [
            'scraped_date' => current_time('mysql'),
            'original_url' => $scraped_data['url'],
            'canonical_url' => $scraped_data['canonical_url'] ?? $scraped_data['url'],
            'site_name' => $scraped_data['site_name'] ?? '',
            'og_type' => $scraped_data['og_type'] ?? '',
            'twitter_site' => $scraped_data['twitter_site'] ?? '',
            'twitter_creator' => $scraped_data['twitter_creator'] ?? '',
            'image' => $scraped_data['image'] ?? ''
        ];

        return $normalized;
    }

    /**
     * Determine reference type from scraped data
     *
     * @param array $scraped_data Scraped data
     * @return string Reference type
     */
    private function determine_reference_type($scraped_data) {
        // Check for specific indicators
        if (!empty($scraped_data['journal']) || !empty($scraped_data['volume']) || !empty($scraped_data['issue'])) {
            return 'article-journal';
        }

        if (!empty($scraped_data['isbn'])) {
            return 'book';
        }

        if (!empty($scraped_data['og_type'])) {
            switch ($scraped_data['og_type']) {
                case 'article':
                    return 'article-journal';
                case 'book':
                    return 'book';
                case 'video':
                    return 'motion_picture';
            }
        }

        // Check URL patterns for common academic sites
        $url = $scraped_data['url'] ?? '';
        $domain = parse_url($url, PHP_URL_HOST);

        $academic_patterns = [
            'arxiv.org' => 'manuscript',
            'pubmed.ncbi.nlm.nih.gov' => 'article-journal',
            'scholar.google' => 'article-journal',
            'researchgate.net' => 'article-journal',
            'academia.edu' => 'manuscript',
            'ssrn.com' => 'manuscript',
            'biorxiv.org' => 'manuscript',
            'psyarxiv.com' => 'manuscript'
        ];

        foreach ($academic_patterns as $pattern => $type) {
            if (strpos($domain, $pattern) !== false) {
                return $type;
            }
        }

        // Default to webpage
        return 'webpage';
    }

    /**
     * Search URLs (not applicable for URL scraper)
     *
     * @param string $query Search query
     * @param int $limit Results limit
     * @param int $offset Results offset
     * @return WP_Error Error indicating search not supported
     */
    public function search($query, $limit = 10, $offset = 0) {
        return new WP_Error('search_not_supported', __('Search is not supported for URL scraper', 'academic-bloggers-toolkit'));
    }

    /**
     * Bulk fetch URLs
     *
     * @param array $urls Array of URLs to scrape
     * @return array Array of results (successful and failed)
     */
    public function bulk_fetch($urls) {
        $results = [
            'successful' => [],
            'failed' => []
        ];

        foreach ($urls as $url) {
            $result = $this->fetch($url);
            
            if (is_wp_error($result)) {
                $results['failed'][] = [
                    'url' => $url,
                    'error' => $result->get_error_message()
                ];
            } else {
                $results['successful'][] = $result;
            }

            // Add delay to be respectful to websites
            usleep(500000); // 0.5 second
        }

        return $results;
    }

    /**
     * Test if a URL is scrapeable
     *
     * @param string $url URL to test
     * @return bool|WP_Error True if scrapeable, error if not
     */
    public function test_url($url) {
        if (!$this->validate_identifier($url)) {
            return new WP_Error('invalid_url', __('Invalid URL format', 'academic-bloggers-toolkit'));
        }

        // Make a HEAD request first
        $args = [
            'method' => 'HEAD',
            'timeout' => 10,
            'user-agent' => $this->user_agent
        ];

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');

        if ($response_code !== 200) {
            return new WP_Error('http_error', sprintf(__('HTTP Error %d', 'academic-bloggers-toolkit'), $response_code));
        }

        if (strpos($content_type, 'text/html') === false) {
            return new WP_Error('not_html', __('URL does not return HTML content', 'academic-bloggers-toolkit'));
        }

        return true;
    }

    /**
     * Get site-specific extraction rules
     *
     * @param string $url URL to get rules for
     * @return array Extraction rules
     */
    public function get_site_rules($url) {
        $domain = parse_url($url, PHP_URL_HOST);
        
        $site_rules = [
            'arxiv.org' => [
                'title_selector' => 'h1.title',
                'author_selector' => '.authors a',
                'abstract_selector' => '.abstract',
                'date_selector' => '.submission-history'
            ],
            'pubmed.ncbi.nlm.nih.gov' => [
                'title_selector' => 'h1.heading-title',
                'author_selector' => '.authors .item',
                'journal_selector' => '.journal-actions a',
                'abstract_selector' => '.abstract-content'
            ],
            'biorxiv.org' => [
                'title_selector' => '#page-title',
                'author_selector' => '.highwire-citation-authors .nlm-given-names, .highwire-citation-authors .nlm-surname',
                'abstract_selector' => '.section.abstract p',
                'date_selector' => '.published'
            ]
        ];

        return $site_rules[$domain] ?? [];
    }

    /**
     * Extract using site-specific rules
     *
     * @param string $html HTML content
     * @param array $rules Site-specific rules
     * @return array Extracted data
     */
    private function extract_with_site_rules($html, $rules) {
        if (empty($rules)) {
            return [];
        }

        $data = [];
        
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($doc);

        foreach ($rules as $field => $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                if ($field === 'author_selector') {
                    $authors = [];
                    foreach ($nodes as $node) {
                        $authors[] = trim($node->textContent);
                    }
                    $data['author'] = implode('; ', $authors);
                } else {
                    $data[str_replace('_selector', '', $field)] = trim($nodes->item(0)->textContent);
                }
            }
        }

        return $data;
    }

    /**
     * Generate fallback metadata for failed scraping
     *
     * @param string $url Original URL
     * @return array Basic metadata
     */
    public function generate_fallback_metadata($url) {
        $parsed_url = parse_url($url);
        $domain = $parsed_url['host'] ?? '';
        
        // Try to extract title from URL path
        $path = $parsed_url['path'] ?? '';
        $title = basename($path);
        $title = str_replace(['-', '_'], ' ', $title);
        $title = ucwords($title);

        return [
            'type' => 'webpage',
            'title' => $title ?: __('Web Page', 'academic-bloggers-toolkit'),
            'URL' => $url,
            'container-title' => $domain,
            'accessed' => [
                'date-parts' => [[
                    (int) date('Y'),
                    (int) date('n'),
                    (int) date('j')
                ]]
            ],
            '_scraping_data' => [
                'scraped_date' => current_time('mysql'),
                'original_url' => $url,
                'fallback_used' => true,
                'site_name' => $domain
            ]
        ];
    }
}