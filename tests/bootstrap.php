<?php
/**
 * PHPUnit Test Bootstrap for Academic Blogger's Toolkit
 * 
 * This file sets up the WordPress testing environment for the plugin.
 * 
 * @package Academic_Bloggers_Toolkit
 * @subpackage Tests
 * @since 1.0.0
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Direct access forbidden.' );
}

// Define testing constants
define( 'ABT_TESTS_DIR', __DIR__ );
define( 'ABT_PLUGIN_DIR', dirname( ABT_TESTS_DIR ) );
define( 'ABT_PLUGIN_FILE', ABT_PLUGIN_DIR . '/academic-bloggers-toolkit.php' );

// WordPress test config file
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    require ABT_PLUGIN_FILE;
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Setup test environment
 */
function _setup_test_environment() {
    // Create test taxonomy terms
    wp_insert_term( 'Computer Science', 'abt_subject' );
    wp_insert_term( 'Psychology', 'abt_subject' );
    wp_insert_term( 'Medicine', 'abt_subject' );
    wp_insert_term( 'Academic News', 'abt_blog_category' );
    wp_insert_term( 'Research', 'abt_blog_category' );
    wp_insert_term( 'teaching', 'abt_blog_tag' );
    wp_insert_term( 'research-methods', 'abt_blog_tag' );

    // Create test users
    $admin_id = wp_create_user( 'testadmin', 'password', 'admin@test.com' );
    $user = new WP_User( $admin_id );
    $user->set_role( 'administrator' );

    $author_id = wp_create_user( 'testauthor', 'password', 'author@test.com' );
    $user = new WP_User( $author_id );
    $user->set_role( 'author' );
}

tests_add_filter( 'wp_loaded', '_setup_test_environment' );

/**
 * Setup test data
 */
function _setup_test_data() {
    // Create sample reference
    $reference_id = wp_insert_post( array(
        'post_type'    => 'abt_reference',
        'post_title'   => 'Test Reference Article',
        'post_status'  => 'publish',
        'post_content' => 'Test reference content',
        'meta_input'   => array(
            '_abt_ref_type'        => 'article',
            '_abt_ref_authors'     => 'Smith, J.; Doe, A.',
            '_abt_ref_year'        => '2023',
            '_abt_ref_journal'     => 'Test Journal',
            '_abt_ref_volume'      => '42',
            '_abt_ref_issue'       => '3',
            '_abt_ref_pages'       => '123-145',
            '_abt_ref_doi'         => '10.1000/test.doi',
            '_abt_ref_pmid'        => '12345678',
            '_abt_ref_isbn'        => '978-0123456789',
            '_abt_ref_url'         => 'https://example.com/article'
        )
    ) );

    // Create sample academic blog post
    $blog_id = wp_insert_post( array(
        'post_type'    => 'abt_blog',
        'post_title'   => 'Test Academic Blog Post',
        'post_status'  => 'publish',
        'post_content' => 'This is a test academic blog post with citations.',
        'meta_input'   => array(
            '_abt_blog_type'         => 'research-article',
            '_abt_abstract'          => 'This is a test abstract',
            '_abt_keywords'          => 'test, academic, blogging',
            '_abt_citation_style'    => 'apa',
            '_abt_enable_footnotes'  => 'yes',
            '_abt_peer_reviewed'     => 'no',
            '_abt_reading_time'      => '5'
        )
    ) );

    // Set taxonomy terms for the blog post
    wp_set_post_terms( $blog_id, array( 'Computer Science' ), 'abt_subject' );
    wp_set_post_terms( $blog_id, array( 'Research' ), 'abt_blog_category' );
    wp_set_post_terms( $blog_id, array( 'research-methods' ), 'abt_blog_tag' );

    // Create sample citation
    $citation_id = wp_insert_post( array(
        'post_type'    => 'abt_citation',
        'post_title'   => 'Citation 1',
        'post_status'  => 'publish',
        'post_parent'  => $blog_id,
        'meta_input'   => array(
            '_abt_citation_ref_id' => $reference_id,
            '_abt_citation_page'   => '125',
            '_abt_citation_type'   => 'in-text',
            '_abt_citation_style'  => 'apa'
        )
    ) );
}

tests_add_filter( 'wp_loaded', '_setup_test_data', 20 );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Include test helper functions
require_once ABT_TESTS_DIR . '/includes/class-abt-test-helper.php';
require_once ABT_TESTS_DIR . '/includes/class-abt-test-factory.php';

/**
 * Test Helper class for common test utilities
 */
class ABT_Test_Helper {
    
    /**
     * Create a test reference post
     * 
     * @param array $args Override default values
     * @return int Post ID
     */
    public static function create_reference( $args = array() ) {
        $defaults = array(
            'post_type'    => 'abt_reference',
            'post_title'   => 'Test Reference',
            'post_status'  => 'publish',
            'meta_input'   => array(
                '_abt_ref_type'    => 'article',
                '_abt_ref_authors' => 'Test Author',
                '_abt_ref_year'    => '2023'
            )
        );
        
        $args = wp_parse_args( $args, $defaults );
        return wp_insert_post( $args );
    }
    
    /**
     * Create a test academic blog post
     * 
     * @param array $args Override default values
     * @return int Post ID
     */
    public static function create_blog_post( $args = array() ) {
        $defaults = array(
            'post_type'    => 'abt_blog',
            'post_title'   => 'Test Blog Post',
            'post_status'  => 'publish',
            'meta_input'   => array(
                '_abt_blog_type'      => 'research-article',
                '_abt_citation_style' => 'apa'
            )
        );
        
        $args = wp_parse_args( $args, $defaults );
        return wp_insert_post( $args );
    }
    
    /**
     * Create a test citation
     * 
     * @param int   $blog_id Blog post ID
     * @param int   $ref_id  Reference post ID
     * @param array $args    Override default values
     * @return int Post ID
     */
    public static function create_citation( $blog_id, $ref_id, $args = array() ) {
        $defaults = array(
            'post_type'    => 'abt_citation',
            'post_title'   => 'Test Citation',
            'post_status'  => 'publish',
            'post_parent'  => $blog_id,
            'meta_input'   => array(
                '_abt_citation_ref_id' => $ref_id,
                '_abt_citation_type'   => 'in-text',
                '_abt_citation_style'  => 'apa'
            )
        );
        
        $args = wp_parse_args( $args, $defaults );
        return wp_insert_post( $args );
    }
    
    /**
     * Clean up test posts
     */
    public static function cleanup_posts() {
        $post_types = array( 'abt_blog', 'abt_reference', 'abt_citation', 'abt_footnote', 'abt_bibliography' );
        
        foreach ( $post_types as $post_type ) {
            $posts = get_posts( array(
                'post_type'      => $post_type,
                'posts_per_page' => -1,
                'post_status'    => 'any'
            ) );
            
            foreach ( $posts as $post ) {
                wp_delete_post( $post->ID, true );
            }
        }
    }
    
    /**
     * Assert that a post has specific meta values
     * 
     * @param int    $post_id Post ID
     * @param string $meta_key Meta key
     * @param mixed  $expected Expected value
     */
    public static function assertPostMeta( $post_id, $meta_key, $expected ) {
        $actual = get_post_meta( $post_id, $meta_key, true );
        PHPUnit\Framework\Assert::assertEquals( $expected, $actual );
    }
    
    /**
     * Assert that a post has a specific taxonomy term
     * 
     * @param int    $post_id Post ID
     * @param string $taxonomy Taxonomy name
     * @param string $term_name Term name
     */
    public static function assertPostHasTerm( $post_id, $taxonomy, $term_name ) {
        $terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
        PHPUnit\Framework\Assert::assertContains( $term_name, $terms );
    }
}

/**
 * Test Factory for creating test data
 */
class ABT_Test_Factory {
    
    /**
     * Sample reference data sets
     */
    public static function get_sample_references() {
        return array(
            'article' => array(
                'post_title' => 'Sample Journal Article',
                'meta_input' => array(
                    '_abt_ref_type'    => 'article',
                    '_abt_ref_authors' => 'Smith, J.; Johnson, A.',
                    '_abt_ref_year'    => '2023',
                    '_abt_ref_journal' => 'Journal of Test Studies',
                    '_abt_ref_volume'  => '15',
                    '_abt_ref_issue'   => '2',
                    '_abt_ref_pages'   => '45-67',
                    '_abt_ref_doi'     => '10.1000/sample.doi'
                )
            ),
            'book' => array(
                'post_title' => 'Sample Academic Book',
                'meta_input' => array(
                    '_abt_ref_type'      => 'book',
                    '_abt_ref_authors'   => 'Brown, M.',
                    '_abt_ref_year'      => '2022',
                    '_abt_ref_publisher' => 'Academic Press',
                    '_abt_ref_location'  => 'New York',
                    '_abt_ref_isbn'      => '978-0123456789'
                )
            ),
            'website' => array(
                'post_title' => 'Sample Website Article',
                'meta_input' => array(
                    '_abt_ref_type'       => 'website',
                    '_abt_ref_authors'    => 'Digital Author',
                    '_abt_ref_year'       => '2023',
                    '_abt_ref_url'        => 'https://example.com/article',
                    '_abt_ref_access_date' => '2023-12-15'
                )
            )
        );
    }
    
    /**
     * Create multiple test references
     * 
     * @param int $count Number of references to create
     * @return array Array of post IDs
     */
    public static function create_references( $count = 5 ) {
        $references = self::get_sample_references();
        $ref_types = array_keys( $references );
        $created = array();
        
        for ( $i = 0; $i < $count; $i++ ) {
            $type = $ref_types[ $i % count( $ref_types ) ];
            $data = $references[ $type ];
            $data['post_title'] .= ' ' . ( $i + 1 );
            
            $created[] = ABT_Test_Helper::create_reference( $data );
        }
        
        return $created;
    }
    
    /**
     * Create test blog posts with citations
     * 
     * @param int $post_count Number of blog posts
     * @param int $citation_count Citations per post
     * @return array Array with blog_ids and citation_ids
     */
    public static function create_blog_posts_with_citations( $post_count = 3, $citation_count = 2 ) {
        $references = self::create_references( $citation_count );
        $blog_ids = array();
        $citation_ids = array();
        
        for ( $i = 0; $i < $post_count; $i++ ) {
            $blog_id = ABT_Test_Helper::create_blog_post( array(
                'post_title' => 'Academic Blog Post ' . ( $i + 1 )
            ) );
            $blog_ids[] = $blog_id;
            
            foreach ( $references as $j => $ref_id ) {
                if ( $j < $citation_count ) {
                    $citation_ids[] = ABT_Test_Helper::create_citation( $blog_id, $ref_id );
                }
            }
        }
        
        return array(
            'blog_ids'     => $blog_ids,
            'citation_ids' => $citation_ids,
            'ref_ids'      => $references
        );
    }
}

echo "Academic Blogger's Toolkit test environment loaded successfully!" . PHP_EOL;