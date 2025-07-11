# Academic Blogger's Toolkit - API Reference

## Table of Contents

1. [REST API Endpoints](#rest-api-endpoints)
2. [PHP Functions](#php-functions)
3. [JavaScript API](#javascript-api)
4. [Hooks Reference](#hooks-reference)
5. [Data Structures](#data-structures)
6. [Error Handling](#error-handling)
7. [Authentication](#authentication)
8. [Rate Limiting](#rate-limiting)

## REST API Endpoints

### Base URL
```
https://yoursite.com/wp-json/abt/v1/
```

### References

#### GET /references
Get a list of references with optional filtering.

**Parameters:**
- `search` (string) - Search term for title/author
- `type` (string) - Reference type filter
- `author` (string) - Author name filter
- `year` (string) - Publication year filter
- `limit` (int) - Number of results (default: 20, max: 100)
- `offset` (int) - Pagination offset (default: 0)
- `orderby` (string) - Sort field (title, year, author)
- `order` (string) - Sort direction (asc, desc)

**Example Request:**
```bash
curl -X GET "https://yoursite.com/wp-json/abt/v1/references?search=machine%20learning&type=article&limit=10"
```

**Example Response:**
```json
{
  "references": [
    {
      "id": 123,
      "title": "Machine Learning in Healthcare",
      "authors": "Smith, J.; Doe, A.",
      "year": "2023",
      "type": "article",
      "journal": "Journal of Medical AI",
      "doi": "10.1000/sample.doi",
      "citation_count": 5,
      "created_date": "2023-01-15T10:30:00Z",
      "modified_date": "2023-01-15T10:30:00Z"
    }
  ],
  "total": 1,
  "page": 1,
  "per_page": 10,
  "total_pages": 1
}
```

#### POST /references
Create a new reference.

**Authentication:** Required (edit_posts capability)

**Request Body:**
```json
{
  "title": "Sample Article Title",
  "authors": "Smith, J.; Doe, A.",
  "year": "2023",
  "type": "article",
  "journal": "Sample Journal",
  "volume": "42",
  "issue": "3",
  "pages": "123-145",
  "doi": "10.1000/sample.doi",
  "abstract": "Article abstract text"
}
```

**Example Response:**
```json
{
  "id": 124,
  "title": "Sample Article Title",
  "message": "Reference created successfully",
  "created_date": "2023-12-15T14:22:00Z"
}
```

#### GET /references/{id}
Get a specific reference by ID.

**Example Request:**
```bash
curl -X GET "https://yoursite.com/wp-json/abt/v1/references/123"
```

**Example Response:**
```json
{
  "id": 123,
  "title": "Machine Learning in Healthcare",
  "authors": "Smith, J.; Doe, A.",
  "year": "2023",
  "type": "article",
  "journal": "Journal of Medical AI",
  "volume": "15",
  "issue": "2",
  "pages": "45-67",
  "doi": "10.1000/sample.doi",
  "pmid": "12345678",
  "url": "https://example.com/article",
  "abstract": "This article discusses...",
  "citation_count": 5,
  "created_date": "2023-01-15T10:30:00Z",
  "modified_date": "2023-01-15T10:30:00Z"
}
```

#### PUT /references/{id}
Update an existing reference.

**Authentication:** Required (edit_posts capability)

**Request Body:** Same as POST /references

#### DELETE /references/{id}
Delete a reference.

**Authentication:** Required (delete_posts capability)

**Example Response:**
```json
{
  "message": "Reference deleted successfully",
  "deleted": true
}
```

### Citations

#### POST /citations/format
Format a citation in a specific style.

**Request Body:**
```json
{
  "ref_id": 123,
  "style": "apa",
  "type": "in-text",
  "page": "45"
}
```

**Example Response:**
```json
{
  "citation": "(Smith & Doe, 2023, p. 45)",
  "style": "apa",
  "type": "in-text"
}
```

#### GET /posts/{id}/citations
Get all citations for a specific post.

**Example Response:**
```json
{
  "citations": [
    {
      "id": 456,
      "ref_id": 123,
      "page": "45",
      "type": "in-text",
      "style": "apa",
      "position": 1
    }
  ],
  "total": 1
}
```

#### GET /posts/{id}/bibliography
Generate bibliography for a post.

**Parameters:**
- `style` (string) - Citation style (default: post setting)
- `sort_by` (string) - Sort order (author, year, title)
- `format` (string) - Output format (html, json)

**Example Response:**
```json
{
  "bibliography": "<div class=\"abt-bibliography\">...</div>",
  "style": "apa",
  "reference_count": 5
}
```

### Auto-Cite

#### POST /auto-cite/doi
Auto-cite from DOI.

**Authentication:** Required (edit_posts capability)

**Request Body:**
```json
{
  "doi": "10.1038/nature12373"
}
```

**Example Response:**
```json
{
  "success": true,
  "reference_data": {
    "title": "Article Title from DOI",
    "authors": "Author, A.; Author, B.",
    "year": "2023",
    "journal": "Nature",
    "doi": "10.1038/nature12373"
  },
  "source": "crossref"
}
```

#### POST /auto-cite/pmid
Auto-cite from PubMed ID.

**Request Body:**
```json
{
  "pmid": "12345678"
}
```

#### POST /auto-cite/isbn
Auto-cite from ISBN.

**Request Body:**
```json
{
  "isbn": "978-0123456789"
}
```

#### POST /auto-cite/url
Auto-cite from URL.

**Request Body:**
```json
{
  "url": "https://example.com/article"
}
```

### Search

#### GET /search
Advanced search across all content types.

**Parameters:**
- `query` (string) - Search query
- `type` (string) - Content type (references, blog_posts, all)
- `filters` (object) - Additional filters
- `limit` (int) - Results limit
- `offset` (int) - Pagination offset

**Example Response:**
```json
{
  "results": {
    "references": [
      {
        "id": 123,
        "title": "Matching Reference",
        "type": "reference",
        "relevance": 0.95
      }
    ],
    "blog_posts": [
      {
        "id": 456,
        "title": "Matching Blog Post",
        "type": "blog_post",
        "relevance": 0.87
      }
    ]
  },
  "total": 2,
  "query_time": 0.045
}
```

### Analytics

#### GET /analytics/citations
Get citation analytics.

**Authentication:** Required (manage_options capability)

**Parameters:**
- `period` (string) - Time period (day, week, month, year)
- `start_date` (string) - Start date (YYYY-MM-DD)
- `end_date` (string) - End date (YYYY-MM-DD)

**Example Response:**
```json
{
  "total_citations": 150,
  "period_citations": 25,
  "most_cited": [
    {
      "ref_id": 123,
      "title": "Popular Article",
      "citation_count": 15
    }
  ],
  "citation_trends": [
    {
      "date": "2023-12-01",
      "count": 5
    }
  ]
}
```

## PHP Functions

### Reference Functions

#### abt_get_reference()
```php
/**
 * Get reference data
 * 
 * @param int $ref_id Reference ID
 * @return array|false Reference data or false on failure
 */
function abt_get_reference( $ref_id );
```

**Example:**
```php
$reference = abt_get_reference( 123 );
if ( $reference ) {
    echo $reference['title'];
    echo $reference['authors'];
}
```

#### abt_create_reference()
```php
/**
 * Create new reference
 * 
 * @param array $data Reference data
 * @return int|WP_Error Reference ID or error object
 */
function abt_create_reference( $data );
```

**Example:**
```php
$ref_id = abt_create_reference( array(
    'title'   => 'Article Title',
    'authors' => 'Smith, J.',
    'year'    => '2023',
    'type'    => 'article',
    'journal' => 'Sample Journal'
) );

if ( is_wp_error( $ref_id ) ) {
    echo $ref_id->get_error_message();
} else {
    echo "Reference created with ID: " . $ref_id;
}
```

#### abt_update_reference()
```php
/**
 * Update existing reference
 * 
 * @param int   $ref_id Reference ID
 * @param array $data   Updated data
 * @return bool Success status
 */
function abt_update_reference( $ref_id, $data );
```

#### abt_delete_reference()
```php
/**
 * Delete reference
 * 
 * @param int $ref_id Reference ID
 * @return bool Success status
 */
function abt_delete_reference( $ref_id );
```

#### abt_search_references()
```php
/**
 * Search references
 * 
 * @param array $args Search arguments
 * @return array Search results
 */
function abt_search_references( $args = array() );
```

**Example:**
```php
$results = abt_search_references( array(
    'search' => 'machine learning',
    'type'   => 'article',
    'year'   => '2023',
    'limit'  => 10
) );

foreach ( $results['references'] as $reference ) {
    echo $reference->post_title;
}
```

### Citation Functions

#### abt_format_citation()
```php
/**
 * Format a citation
 * 
 * @param int    $ref_id Reference ID
 * @param string $style  Citation style (default: 'apa')
 * @param string $type   Citation type (default: 'in-text')
 * @param array  $args   Additional arguments
 * @return string Formatted citation
 */
function abt_format_citation( $ref_id, $style = 'apa', $type = 'in-text', $args = array() );
```

**Example:**
```php
// Basic citation
$citation = abt_format_citation( 123 );
echo $citation; // (Smith, 2023)

// Citation with page number
$citation = abt_format_citation( 123, 'apa', 'in-text', array( 'page' => '45' ) );
echo $citation; // (Smith, 2023, p. 45)

// MLA style
$citation = abt_format_citation( 123, 'mla' );
echo $citation; // (Smith 45)
```

#### abt_get_bibliography()
```php
/**
 * Generate bibliography for post
 * 
 * @param int    $post_id Post ID (default: current post)
 * @param string $style   Citation style (default: post setting)
 * @param array  $args    Additional arguments
 * @return string Bibliography HTML
 */
function abt_get_bibliography( $post_id = null, $style = null, $args = array() );
```

**Example:**
```php
// Basic bibliography
echo abt_get_bibliography();

// Custom style bibliography
echo abt_get_bibliography( get_the_ID(), 'mla' );

// Bibliography with custom options
echo abt_get_bibliography( get_the_ID(), 'apa', array(
    'sort_by'    => 'author',
    'show_title' => true,
    'title'      => 'References'
) );
```

#### abt_get_post_citations()
```php
/**
 * Get citations for a post
 * 
 * @param int $post_id Post ID
 * @return array Citation objects
 */
function abt_get_post_citations( $post_id );
```

### Academic Post Functions

#### abt_get_academic_meta()
```php
/**
 * Get academic metadata for post
 * 
 * @param int $post_id Post ID (default: current post)
 * @return array Academic metadata
 */
function abt_get_academic_meta( $post_id = null );
```

**Example:**
```php
$meta = abt_get_academic_meta();
echo $meta['abstract'];
echo $meta['keywords'];
echo $meta['reading_time'];
```

#### abt_get_reading_time()
```php
/**
 * Get estimated reading time
 * 
 * @param int $post_id Post ID (default: current post)
 * @return int Reading time in minutes
 */
function abt_get_reading_time( $post_id = null );
```

#### abt_is_academic_post()
```php
/**
 * Check if post is academic blog post
 * 
 * @param int $post_id Post ID (default: current post)
 * @return bool True if academic post
 */
function abt_is_academic_post( $post_id = null );
```

### Auto-Cite Functions

#### abt_auto_cite_doi()
```php
/**
 * Auto-cite from DOI
 * 
 * @param string $doi DOI identifier
 * @return array|WP_Error Reference data or error
 */
function abt_auto_cite_doi( $doi );
```

**Example:**
```php
$result = abt_auto_cite_doi( '10.1038/nature12373' );
if ( ! is_wp_error( $result ) ) {
    $ref_id = abt_create_reference( $result['reference_data'] );
}
```

#### abt_auto_cite_pmid()
```php
/**
 * Auto-cite from PubMed ID
 * 
 * @param string $pmid PubMed ID
 * @return array|WP_Error Reference data or error
 */
function abt_auto_cite_pmid( $pmid );
```

#### abt_auto_cite_isbn()
```php
/**
 * Auto-cite from ISBN
 * 
 * @param string $isbn ISBN identifier
 * @return array|WP_Error Reference data or error
 */
function abt_auto_cite_isbn( $isbn );
```

### Utility Functions

#### abt_get_citation_styles()
```php
/**
 * Get available citation styles
 * 
 * @return array Citation styles
 */
function abt_get_citation_styles();
```

#### abt_validate_doi()
```php
/**
 * Validate DOI format
 * 
 * @param string $doi DOI to validate
 * @return bool True if valid
 */
function abt_validate_doi( $doi );
```

#### abt_sanitize_authors()
```php
/**
 * Sanitize author list
 * 
 * @param string $authors Author string
 * @return string Sanitized authors
 */
function abt_sanitize_authors( $authors );
```

## JavaScript API

### Global Object
```javascript
// ABT global object
window.ABT = {
    ajax_url: '/wp-admin/admin-ajax.php',
    nonce: 'security_nonce',
    settings: {
        default_style: 'apa',
        enable_tooltips: true
    }
};
```

### Citation Management

#### ABT.Citation.format()
```javascript
/**
 * Format citation via AJAX
 * 
 * @param {Object} options - Citation options
 * @returns {Promise} Formatted citation
 */
ABT.Citation.format({
    ref_id: 123,
    style: 'apa',
    type: 'in-text',
    page: '45'
}).then(function(citation) {
    console.log(citation);
});
```

#### ABT.Citation.insert()
```javascript
/**
 * Insert citation into editor
 * 
 * @param {number} ref_id - Reference ID
 * @param {Object} options - Citation options
 */
ABT.Citation.insert(123, {
    style: 'apa',
    page: '45'
});
```

### Reference Management

#### ABT.Reference.search()
```javascript
/**
 * Search references
 * 
 * @param {Object} params - Search parameters
 * @returns {Promise} Search results
 */
ABT.Reference.search({
    query: 'machine learning',
    type: 'article',
    limit: 10
}).then(function(results) {
    console.log(results.references);
});
```

#### ABT.Reference.create()
```javascript
/**
 * Create new reference
 * 
 * @param {Object} data - Reference data
 * @returns {Promise} Created reference
 */
ABT.Reference.create({
    title: 'Article Title',
    authors: 'Smith, J.',
    year: '2023'
}).then(function(reference) {
    console.log('Created reference:', reference.id);
});
```

### Auto-Cite

#### ABT.AutoCite.fetchDOI()
```javascript
/**
 * Fetch reference from DOI
 * 
 * @param {string} doi - DOI identifier
 * @returns {Promise} Reference data
 */
ABT.AutoCite.fetchDOI('10.1038/nature12373').then(function(data) {
    if (data.success) {
        console.log(data.reference_data);
    }
});
```

### UI Components

#### ABT.UI.showTooltip()
```javascript
/**
 * Show citation tooltip
 * 
 * @param {Element} element - Target element
 * @param {number} ref_id - Reference ID
 */
ABT.UI.showTooltip(element, 123);
```

#### ABT.UI.openReferenceModal()
```javascript
/**
 * Open reference selection modal
 * 
 * @param {Function} callback - Selection callback
 */
ABT.UI.openReferenceModal(function(selected_refs) {
    console.log('Selected references:', selected_refs);
});
```

## Hooks Reference

### Actions

#### Plugin Lifecycle
```php
// Plugin activation
do_action( 'abt_activated' );

// Plugin deactivation
do_action( 'abt_deactivated' );

// Plugin loaded
do_action( 'abt_loaded' );
```

#### Reference Hooks
```php
// Before reference creation
do_action( 'abt_before_create_reference', $data );

// After reference creation
do_action( 'abt_after_create_reference', $ref_id, $data );

// Before reference update
do_action( 'abt_before_update_reference', $ref_id, $data );

// After reference update
do_action( 'abt_after_update_reference', $ref_id, $data );

// Before reference deletion
do_action( 'abt_before_delete_reference', $ref_id );

// After reference deletion
do_action( 'abt_after_delete_reference', $ref_id );
```

#### Citation Hooks
```php
// Before citation formatting
do_action( 'abt_before_format_citation', $ref_id, $style, $type );

// After citation formatting
do_action( 'abt_after_format_citation', $ref_id, $style, $type, $formatted );

// Before bibliography generation
do_action( 'abt_before_generate_bibliography', $post_id, $style );

// After bibliography generation
do_action( 'abt_after_generate_bibliography', $post_id, $style, $bibliography );
```

### Filters

#### Data Filters
```php
// Filter reference data before saving
$data = apply_filters( 'abt_reference_data', $data, $ref_id );

// Filter citation format
$citation = apply_filters( 'abt_formatted_citation', $citation, $ref_id, $style );

// Filter bibliography HTML
$bibliography = apply_filters( 'abt_bibliography_html', $bibliography, $post_id, $style );

// Filter search results
$results = apply_filters( 'abt_search_results', $results, $query, $args );
```

#### Configuration Filters
```php
// Filter available citation styles
$styles = apply_filters( 'abt_citation_styles', $styles );

// Filter reference types
$types = apply_filters( 'abt_reference_types', $types );

// Filter auto-cite APIs
$apis = apply_filters( 'abt_auto_cite_apis', $apis );

// Filter validation rules
$rules = apply_filters( 'abt_validation_rules', $rules, $ref_type );
```

## Data Structures

### Reference Object
```php
array(
    'id'              => 123,
    'title'           => 'Article Title',
    'authors'         => 'Smith, J.; Doe, A.',
    'year'            => '2023',
    'type'            => 'article',
    'journal'         => 'Journal Name',
    'volume'          => '42',
    'issue'           => '3',
    'pages'           => '123-145',
    'publisher'       => 'Publisher Name',
    'location'        => 'City, State',
    'doi'             => '10.1000/sample.doi',
    'pmid'            => '12345678',
    'isbn'            => '978-0123456789',
    'url'             => 'https://example.com',
    'access_date'     => '2023-12-15',
    'abstract'        => 'Article abstract...',
    'note'            => 'Additional notes',
    'citation_count'  => 5,
    'created_date'    => '2023-01-15T10:30:00Z',
    'modified_date'   => '2023-01-15T10:30:00Z'
)
```

### Citation Object
```php
array(
    'id'         => 456,
    'ref_id'     => 123,
    'blog_id'    => 789,
    'page'       => '45',
    'type'       => 'in-text',
    'style'      => 'apa',
    'position'   => 1,
    'context'    => 'Surrounding text context'
)
```

### Academic Post Meta
```php
array(
    'type'           => 'research-article',
    'abstract'       => 'Post abstract text',
    'keywords'       => 'keyword1, keyword2, keyword3',
    'citation_style' => 'apa',
    'reading_time'   => 8,
    'peer_reviewed'  => 'yes',
    'doi'            => '10.1000/blog.doi'
)
```

### Search Results
```php
array(
    'references' => array(
        // Array of reference objects
    ),
    'total'      => 25,
    'page'       => 1,
    'per_page'   => 10,
    'total_pages' => 3,
    'query_time' => 0.045
)
```

## Error Handling

### Error Codes

#### Reference Errors
- `invalid_reference_data` - Invalid or missing reference data
- `reference_not_found` - Reference ID does not exist
- `duplicate_reference` - Reference already exists
- `reference_in_use` - Cannot delete referenced item

#### Citation Errors
- `invalid_citation_style` - Unknown citation style
- `citation_format_error` - Error formatting citation
- `invalid_reference_id` - Invalid reference ID for citation

#### Auto-Cite Errors
- `invalid_doi` - Invalid DOI format
- `doi_not_found` - DOI not found in database
- `api_request_failed` - External API request failed
- `rate_limit_exceeded` - API rate limit exceeded

#### Authentication Errors
- `insufficient_permissions` - User lacks required capabilities
- `invalid_nonce` - Security nonce verification failed
- `authentication_required` - Authentication required for endpoint

### Error Response Format

#### REST API Errors
```json
{
  "code": "invalid_reference_data",
  "message": "Title is required for references",
  "data": {
    "status": 400,
    "details": {
      "field": "title",
      "value": ""
    }
  }
}
```

#### PHP Function Errors
```php
// WP_Error object
$error = new WP_Error(
    'invalid_reference_data',
    'Title is required for references',
    array(
        'field' => 'title',
        'value' => ''
    )
);
```

### Error Handling Examples

#### PHP Error Handling
```php
$result = abt_create_reference( $data );

if ( is_wp_error( $result ) ) {
    $error_code = $result->get_error_code();
    $error_message = $result->get_error_message();
    $error_data = $result->get_error_data();
    
    // Handle specific errors
    switch ( $error_code ) {
        case 'invalid_reference_data':
            // Handle validation error
            break;
        case 'duplicate_reference':
            // Handle duplicate
            break;
        default:
            // Handle general error
            break;
    }
}
```

#### JavaScript Error Handling
```javascript
ABT.Reference.create(data)
    .then(function(result) {
        console.log('Success:', result);
    })
    .catch(function(error) {
        console.error('Error:', error.message);
        
        if (error.code === 'invalid_reference_data') {
            // Handle validation error
        }
    });
```

## Authentication

### WordPress Authentication
Most endpoints require WordPress authentication via:

1. **Session Authentication** (logged-in users)
2. **Application Passwords** (WordPress 5.6+)
3. **OAuth** (with OAuth plugin)

### Capability Requirements

#### Reference Management
- `edit_posts` - Create/edit references
- `delete_posts` - Delete references
- `read` - View references

#### Settings Management
- `manage_options` - Modify plugin settings
- `manage_options` - View analytics

### API Key Authentication (Optional)
```php
// Add API key support
add_filter( 'abt_authenticate_request', function( $user_id, $request ) {
    $api_key = $request->get_header( 'X-ABT-API-Key' );
    
    if ( $api_key ) {
        $user_id = abt_verify_api_key( $api_key );
    }
    
    return $user_id;
}, 10, 2 );
```

## Rate Limiting

### Default Limits
- **Auto-Cite APIs:** 100 requests per hour per user
- **Search Endpoints:** 1000 requests per hour per user
- **General Endpoints:** 5000 requests per hour per user

### Rate Limit Headers
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1640995200
```

### Custom Rate Limiting
```php
// Modify rate limits
add_filter( 'abt_rate_limits', function( $limits ) {
    $limits['auto_cite'] = array(
        'requests' => 200,  // Increase limit
        'window'   => 3600  // Per hour
    );
    
    return $limits;
} );
```

---

*For additional API documentation and examples, visit the [plugin documentation site](https://wbcomdesigns.com/docs) or the [GitHub repository](https://github.com/wbcomdesigns/plugin).*