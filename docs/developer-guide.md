# Academic Blogger's Toolkit - Developer Guide

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Custom Post Types](#custom-post-types)
3. [Core Classes](#core-classes)
4. [Hooks & Filters](#hooks--filters)
5. [Database Schema](#database-schema)
6. [API Reference](#api-reference)
7. [Extending the Plugin](#extending-the-plugin)
8. [Theme Development](#theme-development)
9. [Testing](#testing)
10. [Contributing](#contributing)

## Architecture Overview

### Plugin Structure

The Academic Blogger's Toolkit follows a modular, object-oriented architecture designed for scalability and maintainability:

```
academic-bloggers-toolkit/
├── Core System (includes/)
├── Admin Interface (admin/)
├── Frontend Display (public/)
├── Templates (templates/)
├── Assets (assets/)
└── Tests (tests/)
```

### Design Principles

1. **Separation of Concerns** - Clear boundaries between admin, public, and core functionality
2. **WordPress Standards** - Follows WordPress coding standards and best practices
3. **Hook-Driven Architecture** - Extensive use of actions and filters for extensibility
4. **Performance First** - Lazy loading, caching, and optimized database queries
5. **Security by Design** - Input validation, output escaping, and proper capability checks

### Core Components

#### Custom Post Types (CPT) System
- `abt_blog` - Academic blog posts (public)
- `abt_reference` - Reference library (admin-only)
- `abt_citation` - Citation instances (hidden)
- `abt_footnote` - Footnotes (hidden)
- `abt_bibliography` - Generated bibliographies (hidden)

#### Processing Engines
- **Citation Processor** - Handles citation formatting and style management
- **Auto-Cite System** - Fetches reference data from external APIs
- **Import/Export Engine** - Handles file format conversions

#### User Interfaces
- **Admin Interface** - Reference management, citation tools, analytics
- **Frontend Display** - Academic templates, shortcodes, widgets

## Custom Post Types

### ABT_Blog (Academic Blog Posts)

#### Post Type Registration
```php
register_post_type( 'abt_blog', array(
    'labels' => array(
        'name' => 'Academic Blog Posts',
        'singular_name' => 'Academic Blog Post'
    ),
    'public' => true,
    'has_archive' => true,
    'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
    'taxonomies' => array( 'abt_subject', 'abt_blog_category', 'abt_blog_tag' ),
    'rewrite' => array( 'slug' => 'academic-blog' )
) );
```

#### Meta Fields
```php
// Academic metadata
'_abt_blog_type'         // research-article, review, case-study, opinion, news
'_abt_abstract'          // Post abstract
'_abt_keywords'          // Comma-separated keywords
'_abt_citation_style'    // apa, mla, chicago, harvard, ieee
'_abt_enable_footnotes'  // yes/no
'_abt_peer_reviewed'     // yes/no
'_abt_publication_date'  // Original publication date
'_abt_reading_time'      // Calculated reading time in minutes
'_abt_doi'               // DOI if available
'_abt_custom_fields'     // JSON array of custom fields
```

#### Taxonomies
```php
// Subject areas (hierarchical)
register_taxonomy( 'abt_subject', 'abt_blog', array(
    'hierarchical' => true,
    'public' => true,
    'rewrite' => array( 'slug' => 'subject' )
) );

// Blog categories (hierarchical)
register_taxonomy( 'abt_blog_category', 'abt_blog', array(
    'hierarchical' => true,
    'public' => true,
    'rewrite' => array( 'slug' => 'academic-category' )
) );

// Blog tags (non-hierarchical)
register_taxonomy( 'abt_blog_tag', 'abt_blog', array(
    'hierarchical' => false,
    'public' => true,
    'rewrite' => array( 'slug' => 'academic-tag' )
) );
```

### ABT_Reference (Reference Library)

#### Post Type Registration
```php
register_post_type( 'abt_reference', array(
    'labels' => array(
        'name' => 'References',
        'singular_name' => 'Reference'
    ),
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => 'abt-admin',
    'supports' => array( 'title', 'editor' ),
    'taxonomies' => array( 'abt_ref_category' )
) );
```

#### Meta Fields
```php
// Core reference data
'_abt_ref_type'          // article, book, chapter, website, conference, thesis
'_abt_ref_authors'       // Semicolon-separated author list
'_abt_ref_year'          // Publication year
'_abt_ref_title'         // Full title
'_abt_ref_journal'       // Journal name (for articles)
'_abt_ref_volume'        // Volume number
'_abt_ref_issue'         // Issue number
'_abt_ref_pages'         // Page range
'_abt_ref_publisher'     // Publisher name (for books)
'_abt_ref_location'      // Publication location
'_abt_ref_doi'           // Digital Object Identifier
'_abt_ref_pmid'          // PubMed ID
'_abt_ref_isbn'          // ISBN for books
'_abt_ref_url'           // URL for online sources
'_abt_ref_access_date'   // Access date for online sources
'_abt_ref_note'          // Additional notes
'_abt_ref_citation_count' // Number of times cited
'_abt_ref_import_source' // Source of import (doi, pmid, manual, etc.)
```

### ABT_Citation (Citation Instances)

#### Post Type Registration
```php
register_post_type( 'abt_citation', array(
    'public' => false,
    'show_ui' => false,
    'supports' => array( 'title' )
) );
```

#### Meta Fields
```php
'_abt_citation_ref_id'   // Reference post ID
'_abt_citation_blog_id'  // Blog post ID where cited
'_abt_citation_page'     // Specific page number
'_abt_citation_type'     // in-text, footnote, endnote
'_abt_citation_style'    // Citation style used
'_abt_citation_position' // Position in text
'_abt_citation_context'  // Surrounding text context
```

## Core Classes

### ABT_Core

Main plugin orchestrator that initializes all components:

```php
class ABT_Core {
    
    private $loader;
    private $plugin_name;
    private $version;
    
    public function __construct() {
        $this->plugin_name = 'academic-bloggers-toolkit';
        $this->version = ABT_VERSION;
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    private function load_dependencies() {
        require_once ABT_PLUGIN_DIR . 'includes/class-abt-loader.php';
        require_once ABT_PLUGIN_DIR . 'includes/post-types/class-abt-post-types.php';
        // ... load other dependencies
        
        $this->loader = new ABT_Loader();
    }
}
```

### ABT_Reference

Handles all reference-related operations:

```php
class ABT_Reference {
    
    /**
     * Create a new reference
     * 
     * @param array $data Reference data
     * @return int|WP_Error Post ID or error
     */
    public function create_reference( $data ) {
        // Validate data
        $validation = $this->validate_reference_data( $data );
        if ( ! $validation['valid'] ) {
            return new WP_Error( 'invalid_data', 'Invalid reference data' );
        }
        
        // Create post
        $post_id = wp_insert_post( array(
            'post_type'    => 'abt_reference',
            'post_title'   => sanitize_text_field( $data['title'] ),
            'post_status'  => 'publish',
            'post_content' => sanitize_textarea_field( $data['abstract'] ?? '' )
        ) );
        
        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }
        
        // Save meta data
        $this->save_reference_meta( $post_id, $data );
        
        return $post_id;
    }
    
    /**
     * Search references
     * 
     * @param array $args Search parameters
     * @return array Search results
     */
    public function search_references( $args = array() ) {
        $defaults = array(
            'search'  => '',
            'type'    => '',
            'author'  => '',
            'year'    => '',
            'journal' => '',
            'limit'   => 20,
            'offset'  => 0,
            'orderby' => 'title',
            'order'   => 'ASC'
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        // Build query
        $query_args = array(
            'post_type'      => 'abt_reference',
            'post_status'    => 'publish',
            'posts_per_page' => $args['limit'],
            'offset'         => $args['offset'],
            'orderby'        => $args['orderby'],
            'order'          => $args['order']
        );
        
        // Add search parameters
        if ( ! empty( $args['search'] ) ) {
            $query_args['s'] = $args['search'];
        }
        
        // Add meta queries
        $meta_query = array();
        
        if ( ! empty( $args['type'] ) ) {
            $meta_query[] = array(
                'key'   => '_abt_ref_type',
                'value' => $args['type']
            );
        }
        
        if ( ! empty( $args['year'] ) ) {
            $meta_query[] = array(
                'key'   => '_abt_ref_year',
                'value' => $args['year']
            );
        }
        
        if ( ! empty( $meta_query ) ) {
            $query_args['meta_query'] = $meta_query;
        }
        
        $query = new WP_Query( $query_args );
        
        return array(
            'references' => $query->posts,
            'total'      => $query->found_posts
        );
    }
}
```

### ABT_Citation_Processor

Handles citation formatting and bibliography generation:

```php
class ABT_Citation_Processor {
    
    private $style_manager;
    
    public function __construct() {
        $this->style_manager = new ABT_Style_Manager();
    }
    
    /**
     * Format a citation
     * 
     * @param int    $ref_id Reference ID
     * @param string $style  Citation style
     * @param string $type   Citation type
     * @param array  $args   Additional arguments
     * @return string Formatted citation
     */
    public function format_citation( $ref_id, $style = 'apa', $type = 'in-text', $args = array() ) {
        // Get reference data
        $reference = get_post( $ref_id );
        if ( ! $reference || $reference->post_type !== 'abt_reference' ) {
            return '<span class="abt-citation-error">Reference not found</span>';
        }
        
        // Get reference metadata
        $meta = $this->get_reference_meta( $ref_id );
        
        // Get style configuration
        $style_config = $this->style_manager->get_style_config( $style );
        if ( ! $style_config ) {
            $style = 'apa'; // Fallback to APA
            $style_config = $this->style_manager->get_style_config( 'apa' );
        }
        
        // Format based on type
        switch ( $type ) {
            case 'in-text':
                return $this->format_in_text_citation( $meta, $style_config, $args );
            case 'footnote':
                return $this->format_footnote_citation( $meta, $style_config, $args );
            case 'bibliography':
                return $this->format_bibliography_entry( $meta, $style_config, $args );
            default:
                return $this->format_in_text_citation( $meta, $style_config, $args );
        }
    }
    
    /**
     * Generate bibliography for a post
     * 
     * @param int    $post_id Blog post ID
     * @param string $style   Citation style
     * @param array  $args    Additional arguments
     * @return string Bibliography HTML
     */
    public function generate_bibliography( $post_id, $style = 'apa', $args = array() ) {
        // Get all citations for this post
        $citations = $this->get_post_citations( $post_id );
        
        if ( empty( $citations ) ) {
            return '<div class="abt-bibliography-empty">No references found.</div>';
        }
        
        // Get unique references
        $references = array();
        foreach ( $citations as $citation ) {
            $ref_id = get_post_meta( $citation->ID, '_abt_citation_ref_id', true );
            if ( $ref_id && ! isset( $references[ $ref_id ] ) ) {
                $references[ $ref_id ] = get_post( $ref_id );
            }
        }
        
        // Sort references
        $sort_by = $args['sort_by'] ?? 'author';
        $references = $this->sort_references( $references, $sort_by );
        
        // Generate bibliography HTML
        $html = '<div class="abt-bibliography">';
        
        if ( isset( $args['show_title'] ) && $args['show_title'] ) {
            $title = $args['title'] ?? 'Bibliography';
            $html .= '<h3 class="abt-bibliography-title">' . esc_html( $title ) . '</h3>';
        }
        
        $html .= '<ol class="abt-bibliography-list">';
        
        foreach ( $references as $reference ) {
            $formatted = $this->format_citation( $reference->ID, $style, 'bibliography', $args );
            $html .= '<li class="abt-bibliography-item" id="ref-' . $reference->ID . '">';
            $html .= $formatted;
            $html .= '</li>';
        }
        
        $html .= '</ol>';
        $html .= '</div>';
        
        return $html;
    }
}
```

## Hooks & Filters

### Action Hooks

#### Plugin Lifecycle
```php
// Plugin activation
do_action( 'abt_activated' );

// Plugin deactivation
do_action( 'abt_deactivated' );

// Plugin loaded
do_action( 'abt_loaded' );
```

#### Reference Management
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

#### Citation Processing
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

#### Import/Export
```php
// Before import
do_action( 'abt_before_import', $file_path, $format );

// After import
do_action( 'abt_after_import', $imported_ids, $format );

// Before export
do_action( 'abt_before_export', $ref_ids, $format );

// After export
do_action( 'abt_after_export', $exported_data, $format );
```

### Filter Hooks

#### Reference Data
```php
// Filter reference data before saving
$data = apply_filters( 'abt_reference_data', $data, $ref_id );

// Filter reference metadata
$meta = apply_filters( 'abt_reference_meta', $meta, $ref_id );

// Filter reference validation rules
$rules = apply_filters( 'abt_reference_validation_rules', $rules, $ref_type );

// Filter reference search results
$results = apply_filters( 'abt_reference_search_results', $results, $args );
```

#### Citation Formatting
```php
// Filter citation styles
$styles = apply_filters( 'abt_citation_styles', $styles );

// Filter citation format templates
$template = apply_filters( 'abt_citation_template', $template, $style, $type );

// Filter formatted citation
$citation = apply_filters( 'abt_formatted_citation', $citation, $ref_id, $style );

// Filter bibliography HTML
$bibliography = apply_filters( 'abt_bibliography_html', $bibliography, $post_id, $style );
```

#### Content Processing
```php
// Filter post content before citation processing
$content = apply_filters( 'abt_pre_process_content', $content, $post_id );

// Filter post content after citation processing
$content = apply_filters( 'abt_post_process_content', $content, $post_id );

// Filter shortcode output
$output = apply_filters( 'abt_shortcode_output', $output, $shortcode, $atts );
```

#### Auto-Cite
```php
// Filter auto-cite APIs
$apis = apply_filters( 'abt_auto_cite_apis', $apis );

// Filter fetched reference data
$data = apply_filters( 'abt_fetched_reference_data', $data, $identifier, $source );

// Filter API request arguments
$args = apply_filters( 'abt_api_request_args', $args, $api, $identifier );
```

### Usage Examples

#### Adding Custom Citation Style
```php
function add_custom_citation_style( $styles ) {
    $styles['nature'] = array(
        'name' => 'Nature',
        'in_text_format' => '[{number}]',
        'bibliography_format' => '{number}. {authors} {title} {journal} {volume}, {pages} ({year}).'
    );
    return $styles;
}
add_filter( 'abt_citation_styles', 'add_custom_citation_style' );
```

#### Customizing Reference Validation
```php
function custom_reference_validation( $rules, $ref_type ) {
    if ( $ref_type === 'article' ) {
        $rules['impact_factor'] = array(
            'required' => false,
            'type' => 'number'
        );
    }
    return $rules;
}
add_filter( 'abt_reference_validation_rules', 'custom_reference_validation', 10, 2 );
```

#### Adding Custom Auto-Cite Source
```php
function add_custom_auto_cite_api( $apis ) {
    $apis['arxiv'] = array(
        'name' => 'arXiv',
        'url_pattern' => 'https://export.arxiv.org/api/query?id_list={id}',
        'parser' => 'parse_arxiv_response'
    );
    return $apis;
}
add_filter( 'abt_auto_cite_apis', 'add_custom_auto_cite_api' );
```

## Database Schema

### Post Meta Tables

The plugin primarily uses WordPress's built-in post meta system for storing reference and citation data:

#### Reference Meta Fields
```sql
-- Core reference information
wp_postmeta.meta_key = '_abt_ref_type'          -- VARCHAR(50)
wp_postmeta.meta_key = '_abt_ref_authors'       -- TEXT
wp_postmeta.meta_key = '_abt_ref_year'          -- VARCHAR(4)
wp_postmeta.meta_key = '_abt_ref_journal'       -- VARCHAR(255)
wp_postmeta.meta_key = '_abt_ref_doi'           -- VARCHAR(255)

-- Extended reference data stored as JSON
wp_postmeta.meta_key = '_abt_ref_extended_data' -- LONGTEXT (JSON)
```

#### Citation Meta Fields
```sql
-- Citation relationships and formatting
wp_postmeta.meta_key = '_abt_citation_ref_id'   -- BIGINT
wp_postmeta.meta_key = '_abt_citation_blog_id'  -- BIGINT
wp_postmeta.meta_key = '_abt_citation_style'    -- VARCHAR(50)
wp_postmeta.meta_key = '_abt_citation_page'     -- VARCHAR(50)
```

### Custom Tables (Optional)

For high-performance installations, the plugin can optionally create custom tables:

#### Citation Analytics Table
```sql
CREATE TABLE wp_abt_citation_analytics (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    ref_id BIGINT(20) UNSIGNED NOT NULL,
    blog_id BIGINT(20) UNSIGNED NOT NULL,
    citation_date DATETIME NOT NULL,
    citation_style VARCHAR(50) NOT NULL,
    page_views INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ref_id (ref_id),
    KEY blog_id (blog_id),
    KEY citation_date (citation_date)
);
```

#### Search Cache Table
```sql
CREATE TABLE wp_abt_search_cache (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    search_hash VARCHAR(32) NOT NULL,
    search_args TEXT NOT NULL,
    results LONGTEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY search_hash (search_hash),
    KEY expires_at (expires_at)
);
```

### Database Queries

#### Optimized Reference Search
```php
function get_references_optimized( $args ) {
    global $wpdb;
    
    $sql = "
        SELECT p.ID, p.post_title,
               MAX(CASE WHEN pm.meta_key = '_abt_ref_authors' THEN pm.meta_value END) as authors,
               MAX(CASE WHEN pm.meta_key = '_abt_ref_year' THEN pm.meta_value END) as year,
               MAX(CASE WHEN pm.meta_key = '_abt_ref_type' THEN pm.meta_value END) as ref_type
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'abt_reference'
        AND p.post_status = 'publish'
    ";
    
    // Add search conditions
    if ( ! empty( $args['search'] ) ) {
        $search = $wpdb->esc_like( $args['search'] );
        $sql .= $wpdb->prepare( " AND p.post_title LIKE %s", "%{$search}%" );
    }
    
    $sql .= " GROUP BY p.ID";
    
    // Add ordering
    $orderby = sanitize_sql_orderby( $args['orderby'] ?? 'post_title' );
    $order = in_array( strtoupper( $args['order'] ?? 'ASC' ), array( 'ASC', 'DESC' ) ) 
           ? strtoupper( $args['order'] ) : 'ASC';
    
    $sql .= " ORDER BY {$orderby} {$order}";
    
    // Add pagination
    if ( isset( $args['limit'] ) ) {
        $limit = absint( $args['limit'] );
        $offset = absint( $args['offset'] ?? 0 );
        $sql .= " LIMIT {$offset}, {$limit}";
    }
    
    return $wpdb->get_results( $sql );
}
```

## API Reference

### Public API Functions

#### Reference Functions
```php
/**
 * Get reference data
 * 
 * @param int $ref_id Reference ID
 * @return array|false Reference data or false
 */
function abt_get_reference( $ref_id ) {
    $reference_model = new ABT_Reference();
    return $reference_model->get_reference( $ref_id );
}

/**
 * Create new reference
 * 
 * @param array $data Reference data
 * @return int|WP_Error Reference ID or error
 */
function abt_create_reference( $data ) {
    $reference_model = new ABT_Reference();
    return $reference_model->create_reference( $data );
}

/**
 * Search references
 * 
 * @param array $args Search arguments
 * @return array Search results
 */
function abt_search_references( $args = array() ) {
    $reference_model = new ABT_Reference();
    return $reference_model->search_references( $args );
}
```

#### Citation Functions
```php
/**
 * Format a citation
 * 
 * @param int    $ref_id Reference ID
 * @param string $style  Citation style
 * @param string $type   Citation type
 * @param array  $args   Additional arguments
 * @return string Formatted citation
 */
function abt_format_citation( $ref_id, $style = 'apa', $type = 'in-text', $args = array() ) {
    $processor = new ABT_Citation_Processor();
    return $processor->format_citation( $ref_id, $style, $type, $args );
}

/**
 * Generate bibliography for post
 * 
 * @param int    $post_id Post ID
 * @param string $style   Citation style
 * @param array  $args    Additional arguments
 * @return string Bibliography HTML
 */
function abt_get_bibliography( $post_id = null, $style = null, $args = array() ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }
    
    if ( ! $style ) {
        $style = get_post_meta( $post_id, '_abt_citation_style', true ) ?: 'apa';
    }
    
    $processor = new ABT_Citation_Processor();
    return $processor->generate_bibliography( $post_id, $style, $args );
}

/**
 * Get post citations
 * 
 * @param int $post_id Post ID
 * @return array Citation objects
 */
function abt_get_post_citations( $post_id ) {
    $citations = get_posts( array(
        'post_type'      => 'abt_citation',
        'post_parent'    => $post_id,
        'posts_per_page' => -1,
        'post_status'    => 'publish'
    ) );
    
    return $citations;
}
```

#### Academic Post Functions
```php
/**
 * Get academic post metadata
 * 
 * @param int $post_id Post ID
 * @return array Academic metadata
 */
function abt_get_academic_meta( $post_id = null ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }
    
    return array(
        'type'           => get_post_meta( $post_id, '_abt_blog_type', true ),
        'abstract'       => get_post_meta( $post_id, '_abt_abstract', true ),
        'keywords'       => get_post_meta( $post_id, '_abt_keywords', true ),
        'citation_style' => get_post_meta( $post_id, '_abt_citation_style', true ),
        'reading_time'   => get_post_meta( $post_id, '_abt_reading_time', true ),
        'peer_reviewed'  => get_post_meta( $post_id, '_abt_peer_reviewed', true ),
        'doi'            => get_post_meta( $post_id, '_abt_doi', true )
    );
}

/**
 * Get reading time for post
 * 
 * @param int $post_id Post ID
 * @return int Reading time in minutes
 */
function abt_get_reading_time( $post_id = null ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }
    
    $reading_time = get_post_meta( $post_id, '_abt_reading_time', true );
    
    if ( ! $reading_time ) {
        // Calculate reading time
        $content = get_post_field( 'post_content', $post_id );
        $word_count = str_word_count( strip_tags( $content ) );
        $reading_time = ceil( $word_count / 200 ); // 200 words per minute
        
        update_post_meta( $post_id, '_abt_reading_time', $reading_time );
    }
    
    return $reading_time;
}
```

#### Utility Functions
```php
/**
 * Check if current post is academic blog post
 * 
 * @param int $post_id Post ID
 * @return bool True if academic post
 */
function abt_is_academic_post( $post_id = null ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }
    
    return get_post_type( $post_id ) === 'abt_blog';
}

/**
 * Get available citation styles
 * 
 * @return array Citation styles
 */
function abt_get_citation_styles() {
    $style_manager = new ABT_Style_Manager();
    return $style_manager->get_available_styles();
}

/**
 * Auto-cite from DOI
 * 
 * @param string $doi DOI identifier
 * @return array|WP_Error Reference data or error
 */
function abt_auto_cite_doi( $doi ) {
    $fetcher = new ABT_DOI_Fetcher();
    return $fetcher->fetch_reference( $doi );
}
```

### REST API Endpoints

#### Reference Endpoints
```php
// GET /wp-json/abt/v1/references
// Get references with optional filtering
register_rest_route( 'abt/v1', '/references', array(
    'methods'  => 'GET',
    'callback' => 'abt_api_get_references',
    'args'     => array(
        'search'  => array( 'sanitize_callback' => 'sanitize_text_field' ),
        'type'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
        'limit'   => array( 'sanitize_callback' => 'absint' ),
        'offset'  => array( 'sanitize_callback' => 'absint' )
    )
) );

// POST /wp-json/abt/v1/references
// Create new reference
register_rest_route( 'abt/v1', '/references', array(
    'methods'             => 'POST',
    'callback'            => 'abt_api_create_reference',
    'permission_callback' => function() {
        return current_user_can( 'edit_posts' );
    }
) );

// GET /wp-json/abt/v1/references/{id}
// Get specific reference
register_rest_route( 'abt/v1', '/references/(?P<id>\d+)', array(
    'methods'  => 'GET',
    'callback' => 'abt_api_get_reference'
) );
```

#### Citation Endpoints
```php
// POST /wp-json/abt/v1/citations/format
// Format citation
register_rest_route( 'abt/v1', '/citations/format', array(
    'methods'  => 'POST',
    'callback' => 'abt_api_format_citation',
    'args'     => array(
        'ref_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
        'style'  => array( 'sanitize_callback' => 'sanitize_text_field' ),
        'type'   => array( 'sanitize_callback' => 'sanitize_text_field' )
    )
) );

// GET /wp-json/abt/v1/posts/{id}/bibliography
// Get bibliography for post
register_rest_route( 'abt/v1', '/posts/(?P<id>\d+)/bibliography', array(
    'methods'  => 'GET',
    'callback' => 'abt_api_get_bibliography',
    'args'     => array(
        'style' => array( 'sanitize_callback' => 'sanitize_text_field' )
    )
) );
```

#### Auto-Cite Endpoints
```php
// POST /wp-json/abt/v1/auto-cite/doi
// Auto-cite from DOI
register_rest_route( 'abt/v1', '/auto-cite/doi', array(
    'methods'             => 'POST',
    'callback'            => 'abt_api_auto_cite_doi',
    'permission_callback' => function() {
        return current_user_can( 'edit_posts' );
    },
    'args' => array(
        'doi' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' )
    )
) );
```

## Extending the Plugin

### Adding Custom Reference Types

```php
/**
 * Add custom reference type
 */
function add_dataset_reference_type( $types ) {
    $types['dataset'] = array(
        'name'   => 'Dataset',
        'fields' => array(
            'title'       => array( 'required' => true ),
            'authors'     => array( 'required' => true ),
            'year'        => array( 'required' => true ),
            'repository'  => array( 'required' => true ),
            'version'     => array( 'required' => false ),
            'doi'         => array( 'required' => false ),
            'access_date' => array( 'required' => true )
        ),
        'citation_template' => '{authors} ({year}). {title} [Dataset]. {repository}. {doi}'
    );
    
    return $types;
}
add_filter( 'abt_reference_types', 'add_dataset_reference_type' );
```

### Custom Citation Processors

```php
/**
 * Custom citation processor for institutional style
 */
class Custom_Citation_Processor extends ABT_Citation_Processor {
    
    public function format_institutional_citation( $ref_id, $args = array() ) {
        $reference = $this->get_reference( $ref_id );
        
        // Custom formatting logic
        $citation = sprintf(
            '%s (%s) "%s" %s',
            $reference['authors'],
            $reference['year'],
            $reference['title'],
            $reference['journal']
        );
        
        return apply_filters( 'abt_institutional_citation', $citation, $ref_id, $args );
    }
}

// Register custom processor
function register_custom_citation_processor() {
    $GLOBALS['abt_custom_processor'] = new Custom_Citation_Processor();
}
add_action( 'abt_loaded', 'register_custom_citation_processor' );
```

### Custom Auto-Cite Sources

```php
/**
 * Custom auto-cite fetcher for institutional repository
 */
class Institutional_Repository_Fetcher extends ABT_Base_Fetcher {
    
    protected $api_url = 'https://repository.university.edu/api/';
    
    public function fetch_reference( $identifier ) {
        $url = $this->api_url . 'item/' . urlencode( $identifier );
        
        $response = wp_remote_get( $url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ) );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( ! $data ) {
            return new WP_Error( 'invalid_response', 'Invalid API response' );
        }
        
        return $this->parse_response( $data );
    }
    
    protected function parse_response( $data ) {
        return array(
            'title'      => $data['title'],
            'authors'    => $data['authors'],
            'year'       => $data['publication_year'],
            'type'       => 'thesis',
            'publisher'  => $data['institution'],
            'url'        => $data['url']
        );
    }
}

// Register custom fetcher
function register_institutional_fetcher( $fetchers ) {
    $fetchers['institutional'] = new Institutional_Repository_Fetcher();
    return $fetchers;
}
add_filter( 'abt_auto_cite_fetchers', 'register_institutional_fetcher' );
```

### Custom Shortcodes

```php
/**
 * Custom shortcode for author publications
 */
function abt_author_publications_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'author'     => '',
        'limit'      => 10,
        'style'      => 'apa',
        'show_count' => 'false'
    ), $atts );
    
    if ( empty( $atts['author'] ) ) {
        return '<p>Author parameter is required</p>';
    }
    
    // Search for references by author
    $reference_model = new ABT_Reference();
    $references = $reference_model->search_references( array(
        'author' => $atts['author'],
        'limit'  => $atts['limit']
    ) );
    
    if ( empty( $references['references'] ) ) {
        return '<p>No publications found for this author</p>';
    }
    
    $output = '<div class="abt-author-publications">';
    
    if ( $atts['show_count'] === 'true' ) {
        $output .= '<p class="publication-count">Publications: ' . $references['total'] . '</p>';
    }
    
    $output .= '<ol class="publication-list">';
    
    $processor = new ABT_Citation_Processor();
    
    foreach ( $references['references'] as $reference ) {
        $formatted = $processor->format_citation( $reference->ID, $atts['style'], 'bibliography' );
        $output .= '<li>' . $formatted . '</li>';
    }
    
    $output .= '</ol>';
    $output .= '</div>';
    
    return $output;
}
add_shortcode( 'abt_author_publications', 'abt_author_publications_shortcode' );
```

## Theme Development

### Template Hierarchy

The plugin follows WordPress template hierarchy with custom templates:

```
single-abt_blog.php           → Single academic post
archive-abt_blog.php          → Academic blog archive
taxonomy-abt_subject.php      → Subject taxonomy
taxonomy-abt_blog_category.php → Category taxonomy
taxonomy-abt_blog_tag.php     → Tag taxonomy
search-abt_blog.php           → Search results
```

### Template Tags

#### In Academic Post Templates
```php
// In single-abt_blog.php
<?php while ( have_posts() ) : the_post(); ?>
    <article class="academic-post">
        <header>
            <h1><?php the_title(); ?></h1>
            
            <!-- Academic metadata -->
            <div class="academic-meta">
                <?php 
                $meta = abt_get_academic_meta();
                if ( $meta['abstract'] ) : ?>
                    <div class="abstract">
                        <h3>Abstract</h3>
                        <p><?php echo esc_html( $meta['abstract'] ); ?></p>
                    </div>
                <?php endif;
                
                if ( $meta['keywords'] ) : ?>
                    <div class="keywords">
                        <strong>Keywords:</strong> <?php echo esc_html( $meta['keywords'] ); ?>
                    </div>
                <?php endif; ?>
                
                <div class="reading-time">
                    Reading time: <?php echo abt_get_reading_time(); ?> minutes
                </div>
            </div>
        </header>
        
        <div class="academic-content">
            <?php the_content(); ?>
        </div>
        
        <footer class="academic-footer">
            <!-- Automatic bibliography -->
            <?php echo abt_get_bibliography(); ?>
            
            <!-- Subject taxonomy -->
            <?php 
            $subjects = get_the_terms( get_the_ID(), 'abt_subject' );
            if ( $subjects ) : ?>
                <div class="subjects">
                    <strong>Subjects:</strong>
                    <?php foreach ( $subjects as $subject ) : ?>
                        <a href="<?php echo get_term_link( $subject ); ?>"><?php echo esc_html( $subject->name ); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </footer>
    </article>
<?php endwhile; ?>
```

#### In Archive Templates
```php
// In archive-abt_blog.php
<div class="academic-archive">
    <header class="archive-header">
        <h1>Academic Blog</h1>
        
        <!-- Subject filter -->
        <div class="subject-filter">
            <?php echo do_shortcode( '[abt_subjects show_count="true"]' ); ?>
        </div>
        
        <!-- Search form -->
        <?php echo do_shortcode( '[abt_search_form show_filters="true"]' ); ?>
    </header>
    
    <main class="archive-content">
        <?php if ( have_posts() ) : ?>
            <div class="post-grid">
                <?php while ( have_posts() ) : the_post(); ?>
                    <article class="post-card">
                        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        
                        <div class="post-meta">
                            <?php 
                            $meta = abt_get_academic_meta();
                            echo '<span class="post-type">' . esc_html( ucfirst( $meta['type'] ) ) . '</span>';
                            echo '<span class="reading-time">' . abt_get_reading_time() . ' min read</span>';
                            ?>
                        </div>
                        
                        <?php if ( $meta['abstract'] ) : ?>
                            <div class="post-abstract">
                                <?php echo wp_trim_words( $meta['abstract'], 30 ); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-subjects">
                            <?php echo get_the_term_list( get_the_ID(), 'abt_subject', '', ', ' ); ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p>No academic posts found.</p>
        <?php endif; ?>
    </main>
</div>
```

### CSS Styling

#### Base Academic Styles
```css
/* Academic post layout */
.academic-post {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
    font-family: "Times New Roman", serif;
    line-height: 1.6;
}

.academic-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #ddd;
}

.academic-meta {
    background: #f8f9fa;
    padding: 1rem;
    margin: 1rem 0;
    border-left: 4px solid #007cba;
}

.abstract {
    margin-bottom: 1rem;
}

.abstract h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    font-weight: bold;
}

.keywords {
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.reading-time {
    font-size: 0.8rem;
    color: #666;
}

/* Citation styles */
.abt-citation {
    color: #007cba;
    text-decoration: none;
    cursor: pointer;
}

.abt-citation:hover {
    text-decoration: underline;
}

.abt-citation-tooltip {
    position: absolute;
    background: #333;
    color: white;
    padding: 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    max-width: 300px;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

/* Bibliography styles */
.abt-bibliography {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid #ddd;
}

.abt-bibliography-title {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    font-weight: bold;
}

.abt-bibliography-list {
    list-style-type: none;
    padding: 0;
}

.abt-bibliography-item {
    margin-bottom: 1rem;
    padding-left: 2rem;
    text-indent: -2rem;
    line-height: 1.5;
}

/* Footnote styles */
.abt-footnote {
    vertical-align: super;
    font-size: 0.8rem;
    color: #007cba;
    text-decoration: none;
}

.abt-footnotes {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #ddd;
    font-size: 0.9rem;
}

.abt-footnote-item {
    margin-bottom: 0.5rem;
}

/* Responsive design */
@media (max-width: 768px) {
    .academic-post {
        padding: 1rem;
    }
    
    .academic-meta {
        padding: 0.75rem;
    }
    
    .abt-bibliography-item {
        padding-left: 1rem;
        text-indent: -1rem;
    }
}
```

### JavaScript Integration

#### Citation Tooltips
```javascript
// Citation tooltip functionality
jQuery(document).ready(function($) {
    $('.abt-citation').hover(
        function() {
            var refId = $(this).data('ref-id');
            var tooltip = $('<div class="abt-citation-tooltip">Loading...</div>');
            
            $('body').append(tooltip);
            
            // Position tooltip
            var offset = $(this).offset();
            tooltip.css({
                top: offset.top - tooltip.outerHeight() - 10,
                left: offset.left
            });
            
            // Load reference data via AJAX
            $.ajax({
                url: abt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_get_reference_tooltip',
                    ref_id: refId,
                    nonce: abt_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        tooltip.html(response.data.tooltip);
                    }
                }
            });
        },
        function() {
            $('.abt-citation-tooltip').remove();
        }
    );
});
```

## Testing

### Unit Test Examples

#### Testing Reference Creation
```php
class Test_Reference_Creation extends WP_UnitTestCase {
    
    public function test_create_valid_reference() {
        $data = array(
            'title'   => 'Test Article',
            'authors' => 'Smith, J.',
            'year'    => '2023',
            'type'    => 'article',
            'journal' => 'Test Journal'
        );
        
        $reference_model = new ABT_Reference();
        $ref_id = $reference_model->create_reference( $data );
        
        $this->assertIsInt( $ref_id );
        $this->assertGreaterThan( 0, $ref_id );
        
        // Verify data was saved correctly
        $saved_data = $reference_model->get_reference( $ref_id );
        $this->assertEquals( $data['title'], $saved_data['title'] );
        $this->assertEquals( $data['authors'], $saved_data['authors'] );
    }
    
    public function test_create_invalid_reference() {
        $data = array(
            'title' => '', // Invalid: empty title
            'type'  => 'invalid_type' // Invalid: unknown type
        );
        
        $reference_model = new ABT_Reference();
        $result = $reference_model->create_reference( $data );
        
        $this->assertInstanceOf( 'WP_Error', $result );
    }
}
```

#### Testing Citation Formatting
```php
class Test_Citation_Formatting extends WP_UnitTestCase {
    
    public function test_apa_citation_format() {
        // Create test reference
        $ref_id = $this->create_test_reference( array(
            'authors' => 'Smith, J.; Doe, A.',
            'year'    => '2023',
            'title'   => 'Test Article'
        ) );
        
        $processor = new ABT_Citation_Processor();
        $citation = $processor->format_citation( $ref_id, 'apa', 'in-text' );
        
        $this->assertStringContainsString( '(Smith & Doe, 2023)', $citation );
    }
    
    public function test_mla_citation_format() {
        $ref_id = $this->create_test_reference( array(
            'authors' => 'Smith, John',
            'year'    => '2023'
        ) );
        
        $processor = new ABT_Citation_Processor();
        $citation = $processor->format_citation( $ref_id, 'mla', 'in-text' );
        
        $this->assertStringContainsString( '(Smith)', $citation );
    }
}
```

### Integration Test Examples

#### Testing Shortcode Output
```php
class Test_Shortcode_Integration extends WP_UnitTestCase {
    
    public function test_blog_list_shortcode() {
        // Create test posts
        $this->create_test_blog_posts( 3 );
        
        $output = do_shortcode( '[abt_blog_list posts_per_page="2"]' );
        
        $this->assertStringContainsString( 'class="abt-blog-list"', $output );
        $this->assertStringContainsString( 'Academic Blog Post', $output );
    }
    
    public function test_reference_list_shortcode() {
        // Create test references
        $this->create_test_references( 5 );
        
        $output = do_shortcode( '[abt_reference_list limit="3"]' );
        
        $this->assertStringContainsString( 'class="abt-reference-list"', $output );
    }
}
```

### Performance Testing

#### Load Testing
```php
/**
 * @group performance
 */
class Test_Performance extends WP_UnitTestCase {
    
    public function test_large_bibliography_generation() {
        // Create blog post with many citations
        $blog_id = $this->create_test_blog_post();
        $ref_ids = $this->create_test_references( 100 );
        
        // Create citations
        foreach ( $ref_ids as $ref_id ) {
            $this->create_test_citation( $blog_id, $ref_id );
        }
        
        $start_time = microtime( true );
        
        $processor = new ABT_Citation_Processor();
        $bibliography = $processor->generate_bibliography( $blog_id, 'apa' );
        
        $end_time = microtime( true );
        $execution_time = $end_time - $start_time;
        
        // Should complete within 2 seconds
        $this->assertLessThan( 2.0, $execution_time );
        $this->assertStringContainsString( 'abt-bibliography', $bibliography );
    }
}
```

## Contributing

### Development Setup

1. **Clone the Repository**
   ```bash
   git clone https://github.com/academic-bloggers-toolkit/plugin.git
   cd plugin
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Set Up Testing Environment**
   ```bash
   bin/install-wp-tests.sh abt_test root '' localhost latest
   ```

4. **Run Tests**
   ```bash
   composer test
   npm run test
   ```

### Coding Standards

#### PHP Code Standards
- Follow WordPress Coding Standards
- Use meaningful variable and function names
- Add PHPDoc comments for all functions
- Sanitize all input data
- Escape all output data

#### JavaScript Standards
- Use ES6+ features where supported
- Follow WordPress JavaScript standards
- Add JSDoc comments
- Use meaningful variable names

#### CSS Standards
- Follow BEM methodology for class naming
- Use mobile-first responsive design
- Optimize for performance
- Ensure accessibility compliance

### Contribution Guidelines

1. **Fork the Repository**
2. **Create Feature Branch**
   ```bash
   git checkout -b feature/new-citation-style
   ```

3. **Make Changes**
   - Write tests for new functionality
   - Ensure all tests pass
   - Follow coding standards

4. **Submit Pull Request**
   - Provide clear description
   - Include test coverage
   - Update documentation

### Release Process

1. **Version Bump**
   - Update version numbers
   - Update changelog
   - Tag release

2. **Testing**
   - Run full test suite
   - Test on multiple WordPress versions
   - Verify compatibility

3. **Documentation**
   - Update user guide
   - Update developer docs
   - Create release notes

---

For more information about contributing to the Academic Blogger's Toolkit, visit our [GitHub repository](https://github.com/academic-bloggers-toolkit/plugin) or contact the development team.