<?php
/**
 * Integration Tests for ABT Shortcodes
 * 
 * Tests the shortcode system including blog listing, reference display,
 * search functionality, and widget integration.
 * 
 * @package Academic_Bloggers_Toolkit
 * @subpackage Tests
 * @since 1.0.0
 */

class Test_ABT_Shortcodes extends WP_UnitTestCase {

    /**
     * Shortcodes instance
     * @var ABT_Shortcodes
     */
    private $shortcodes;

    /**
     * Test data
     * @var array
     */
    private $test_data;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        $this->shortcodes = new ABT_Shortcodes();
        
        // Create test data
        $this->test_data = ABT_Test_Factory::create_blog_posts_with_citations( 3, 2 );
        
        // Set up taxonomies
        wp_set_post_terms( $this->test_data['blog_ids'][0], array( 'Computer Science' ), 'abt_subject' );
        wp_set_post_terms( $this->test_data['blog_ids'][1], array( 'Psychology' ), 'abt_subject' );
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        ABT_Test_Helper::cleanup_posts();
        parent::tearDown();
    }

    /**
     * Test academic blog list shortcode
     */
    public function test_academic_blog_list_shortcode() {
        // Basic blog list
        $output = do_shortcode( '[abt_blog_list]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-blog-list"', $output );
        $this->assertStringContainsString( 'Academic Blog Post', $output );
        
        // Limited number of posts
        $output = do_shortcode( '[abt_blog_list posts_per_page="2"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-blog-list"', $output );
        
        // Filter by subject
        $output = do_shortcode( '[abt_blog_list subject="Computer Science"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'Computer Science', $output );
        
        // Show excerpts
        $output = do_shortcode( '[abt_blog_list show_excerpt="true"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-blog-excerpt"', $output );
        
        // Show metadata
        $output = do_shortcode( '[abt_blog_list show_meta="true"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-blog-meta"', $output );
    }

    /**
     * Test reference list shortcode
     */
    public function test_reference_list_shortcode() {
        // Basic reference list
        $output = do_shortcode( '[abt_reference_list]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-reference-list"', $output );
        $this->assertStringContainsString( 'Sample', $output ); // From test data
        
        // Filter by type
        $output = do_shortcode( '[abt_reference_list type="article"]' );
        $this->assertIsString( $output );
        
        // Custom citation style
        $output = do_shortcode( '[abt_reference_list style="mla"]' );
        $this->assertIsString( $output );
        
        // Limited number
        $output = do_shortcode( '[abt_reference_list limit="5"]' );
        $this->assertIsString( $output );
        
        // Show only recent references
        $output = do_shortcode( '[abt_reference_list recent="true" days="30"]' );
        $this->assertIsString( $output );
    }

    /**
     * Test search form shortcode
     */
    public function test_search_form_shortcode() {
        // Basic search form
        $output = do_shortcode( '[abt_search_form]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-search-form"', $output );
        $this->assertStringContainsString( '<form', $output );
        $this->assertStringContainsString( 'type="search"', $output );
        
        // Search form with filters
        $output = do_shortcode( '[abt_search_form show_filters="true"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-search-filters"', $output );
        
        // AJAX search
        $output = do_shortcode( '[abt_search_form ajax="true"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'data-ajax="true"', $output );
        
        // Custom placeholder
        $output = do_shortcode( '[abt_search_form placeholder="Search academic content..."]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'Search academic content...', $output );
    }

    /**
     * Test author profile shortcode
     */
    public function test_author_profile_shortcode() {
        // Create test user
        $author_id = wp_create_user( 'testauthor', 'password', 'author@test.com' );
        $user = new WP_User( $author_id );
        $user->set_role( 'author' );
        
        // Add author meta
        update_user_meta( $author_id, 'abt_author_bio', 'Test author biography' );
        update_user_meta( $author_id, 'abt_author_orcid', '0000-0000-0000-0000' );
        update_user_meta( $author_id, 'abt_author_institution', 'Test University' );
        
        // Basic author profile
        $output = do_shortcode( '[abt_author_profile user_id="' . $author_id . '"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-author-profile"', $output );
        $this->assertStringContainsString( 'Test author biography', $output );
        
        // Show ORCID
        $output = do_shortcode( '[abt_author_profile user_id="' . $author_id . '" show_orcid="true"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( '0000-0000-0000-0000', $output );
        
        // Show institution
        $output = do_shortcode( '[abt_author_profile user_id="' . $author_id . '" show_institution="true"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'Test University', $output );
    }

    /**
     * Test bibliography shortcode
     */
    public function test_bibliography_shortcode() {
        $blog_id = $this->test_data['blog_ids'][0];
        
        // Basic bibliography
        $output = do_shortcode( '[abt_bibliography post_id="' . $blog_id . '"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-bibliography"', $output );
        
        // Custom style
        $output = do_shortcode( '[abt_bibliography post_id="' . $blog_id . '" style="mla"]' );
        $this->assertIsString( $output );
        
        // Sorted bibliography
        $output = do_shortcode( '[abt_bibliography post_id="' . $blog_id . '" sort_by="author"]' );
        $this->assertIsString( $output );
        
        // Show title
        $output = do_shortcode( '[abt_bibliography post_id="' . $blog_id . '" show_title="true"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( '<h', $output );
    }

    /**
     * Test citation shortcode
     */
    public function test_citation_shortcode() {
        $ref_id = $this->test_data['ref_ids'][0];
        
        // Basic citation
        $output = do_shortcode( '[abt_cite ref_id="' . $ref_id . '"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-citation"', $output );
        
        // With page number
        $output = do_shortcode( '[abt_cite ref_id="' . $ref_id . '" page="123"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( '123', $output );
        
        // Custom style
        $output = do_shortcode( '[abt_cite ref_id="' . $ref_id . '" style="mla"]' );
        $this->assertIsString( $output );
        
        // Footnote style
        $output = do_shortcode( '[abt_cite ref_id="' . $ref_id . '" type="footnote"]' );
        $this->assertIsString( $output );
    }

    /**
     * Test recent posts widget shortcode
     */
    public function test_recent_posts_widget_shortcode() {
        // Basic recent posts
        $output = do_shortcode( '[abt_recent_posts]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-recent-posts"', $output );
        
        // Limited number
        $output = do_shortcode( '[abt_recent_posts count="3"]' );
        $this->assertIsString( $output );
        
        // Show excerpts
        $output = do_shortcode( '[abt_recent_posts show_excerpt="true"]' );
        $this->assertIsString( $output );
        
        // Filter by subject
        $output = do_shortcode( '[abt_recent_posts subject="Computer Science"]' );
        $this->assertIsString( $output );
    }

    /**
     * Test popular references widget shortcode
     */
    public function test_popular_references_widget_shortcode() {
        // Basic popular references
        $output = do_shortcode( '[abt_popular_references]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-popular-references"', $output );
        
        // Limited number
        $output = do_shortcode( '[abt_popular_references count="5"]' );
        $this->assertIsString( $output );
        
        // Show citation count
        $output = do_shortcode( '[abt_popular_references show_count="true"]' );
        $this->assertIsString( $output );
        
        // Time period
        $output = do_shortcode( '[abt_popular_references period="month"]' );
        $this->assertIsString( $output );
    }

    /**
     * Test citation statistics widget shortcode
     */
    public function test_citation_stats_widget_shortcode() {
        // Basic stats
        $output = do_shortcode( '[abt_citation_stats]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-citation-stats"', $output );
        
        // Show specific stats
        $output = do_shortcode( '[abt_citation_stats show="total_references,total_citations"]' );
        $this->assertIsString( $output );
        
        // Chart format
        $output = do_shortcode( '[abt_citation_stats format="chart"]' );
        $this->assertIsString( $output );
    }

    /**
     * Test reading time shortcode
     */
    public function test_reading_time_shortcode() {
        $blog_id = $this->test_data['blog_ids'][0];
        
        // Basic reading time
        $output = do_shortcode( '[abt_reading_time post_id="' . $blog_id . '"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'min read', $output );
        
        // Custom format
        $output = do_shortcode( '[abt_reading_time post_id="' . $blog_id . '" format="Reading time: {time} minutes"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'Reading time:', $output );
        
        // With icon
        $output = do_shortcode( '[abt_reading_time post_id="' . $blog_id . '" show_icon="true"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-reading-time-icon"', $output );
    }

    /**
     * Test subject taxonomy shortcode
     */
    public function test_subject_taxonomy_shortcode() {
        // Subject list
        $output = do_shortcode( '[abt_subjects]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-subjects"', $output );
        $this->assertStringContainsString( 'Computer Science', $output );
        
        // Show post counts
        $output = do_shortcode( '[abt_subjects show_count="true"]' );
        $this->assertIsString( $output );
        
        // Hierarchical display
        $output = do_shortcode( '[abt_subjects hierarchical="true"]' );
        $this->assertIsString( $output );
        
        // Custom separator
        $output = do_shortcode( '[abt_subjects separator=" | "]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( ' | ', $output );
    }

    /**
     * Test shortcode parameter validation
     */
    public function test_shortcode_parameter_validation() {
        // Test invalid post ID
        $output = do_shortcode( '[abt_bibliography post_id="999999"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'Post not found', $output );
        
        // Test invalid reference ID
        $output = do_shortcode( '[abt_cite ref_id="999999"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'Reference not found', $output );
        
        // Test invalid citation style
        $output = do_shortcode( '[abt_cite ref_id="' . $this->test_data['ref_ids'][0] . '" style="invalid_style"]' );
        $this->assertIsString( $output );
        // Should fallback to default style
        
        // Test invalid user ID
        $output = do_shortcode( '[abt_author_profile user_id="999999"]' );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'Author not found', $output );
    }

    /**
     * Test shortcode caching
     */
    public function test_shortcode_caching() {
        // First call should generate cache
        $output1 = do_shortcode( '[abt_blog_list posts_per_page="5"]' );
        
        // Second call should use cache
        $output2 = do_shortcode( '[abt_blog_list posts_per_page="5"]' );
        
        $this->assertEquals( $output1, $output2 );
        
        // Test cache invalidation when new post is created
        ABT_Test_Helper::create_blog_post( array(
            'post_title' => 'New Test Post'
        ) );
        
        $output3 = do_shortcode( '[abt_blog_list posts_per_page="5"]' );
        $this->assertStringContainsString( 'New Test Post', $output3 );
    }

    /**
     * Test nested shortcodes
     */
    public function test_nested_shortcodes() {
        // Test shortcode inside shortcode
        $content = '[abt_blog_list posts_per_page="1" show_excerpt="true"]';
        $output = do_shortcode( $content );
        
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-blog-list"', $output );
    }

    /**
     * Test shortcode accessibility features
     */
    public function test_shortcode_accessibility() {
        // Test ARIA labels
        $output = do_shortcode( '[abt_search_form]' );
        $this->assertStringContainsString( 'aria-label', $output );
        
        // Test semantic HTML
        $output = do_shortcode( '[abt_blog_list]' );
        $this->assertStringContainsString( '<article', $output );
        
        // Test heading hierarchy
        $output = do_shortcode( '[abt_bibliography post_id="' . $this->test_data['blog_ids'][0] . '" show_title="true"]' );
        $this->assertStringContainsString( '<h', $output );
    }

    /**
     * Test shortcode responsive features
     */
    public function test_shortcode_responsive_features() {
        // Test responsive classes
        $output = do_shortcode( '[abt_blog_list layout="grid"]' );
        $this->assertStringContainsString( 'abt-grid', $output );
        
        // Test mobile-friendly search
        $output = do_shortcode( '[abt_search_form]' );
        $this->assertStringContainsString( 'class="abt-search-form"', $output );
    }

    /**
     * Test shortcode performance with large datasets
     * 
     * @group performance
     */
    public function test_shortcode_performance() {
        $this->markTestSkipped( 'Performance test - run manually' );
        
        // Create large dataset
        ABT_Test_Factory::create_references( 100 );
        ABT_Test_Factory::create_blog_posts_with_citations( 50, 5 );
        
        $start_time = microtime( true );
        
        // Test blog list performance
        $output = do_shortcode( '[abt_blog_list posts_per_page="20"]' );
        
        $end_time = microtime( true );
        $execution_time = $end_time - $start_time;
        
        // Should complete within reasonable time (adjust threshold as needed)
        $this->assertLessThan( 2.0, $execution_time, 'Blog list shortcode took too long to execute' );
        
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'class="abt-blog-list"', $output );
    }

    /**
     * Test shortcode internationalization
     */
    public function test_shortcode_internationalization() {
        // Test with different locale (if available)
        $current_locale = get_locale();
        
        // Switch to Spanish if available
        if ( function_exists( 'switch_to_locale' ) ) {
            switch_to_locale( 'es_ES' );
        }
        
        $output = do_shortcode( '[abt_blog_list]' );
        $this->assertIsString( $output );
        
        // Restore original locale
        if ( function_exists( 'restore_current_locale' ) ) {
            restore_current_locale();
        }
    }

    /**
     * Test shortcode AJAX integration
     */
    public function test_shortcode_ajax_integration() {
        // Test AJAX search form
        $output = do_shortcode( '[abt_search_form ajax="true"]' );
        $this->assertStringContainsString( 'data-ajax="true"', $output );
        $this->assertStringContainsString( 'data-nonce', $output );
        
        // Test AJAX pagination
        $output = do_shortcode( '[abt_blog_list ajax_pagination="true"]' );
        $this->assertStringContainsString( 'data-ajax-pagination', $output );
    }

    /**
     * Test shortcode error handling and fallbacks
     */
    public function test_shortcode_error_handling() {
        // Test with missing required parameters
        $output = do_shortcode( '[abt_bibliography]' ); // Missing post_id
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'post_id is required', $output );
        
        // Test with database connection issues (simulate)
        // This would require more complex mocking
        
        // Test graceful degradation
        $output = do_shortcode( '[abt_blog_list posts_per_page="invalid"]' );
        $this->assertIsString( $output );
        // Should use default posts_per_page
    }

    /**
     * Test shortcode template customization
     */
    public function test_shortcode_template_customization() {
        // Test custom template loading
        add_filter( 'abt_shortcode_template_path', function( $template, $shortcode ) {
            if ( $shortcode === 'blog_list' ) {
                return get_template_directory() . '/abt-templates/custom-blog-list.php';
            }
            return $template;
        }, 10, 2 );
        
        // Even if custom template doesn't exist, should fallback gracefully
        $output = do_shortcode( '[abt_blog_list]' );
        $this->assertIsString( $output );
        
        remove_all_filters( 'abt_shortcode_template_path' );
    }

    /**
     * Test shortcode hooks and filters
     */
    public function test_shortcode_hooks_and_filters() {
        // Test output filtering
        add_filter( 'abt_shortcode_output', function( $output, $shortcode ) {
            if ( $shortcode === 'blog_list' ) {
                return '<div class="custom-wrapper">' . $output . '</div>';
            }
            return $output;
        }, 10, 2 );
        
        $output = do_shortcode( '[abt_blog_list]' );
        $this->assertStringContainsString( 'custom-wrapper', $output );
        
        remove_all_filters( 'abt_shortcode_output' );
        
        // Test attribute filtering
        add_filter( 'abt_shortcode_atts', function( $atts, $shortcode ) {
            if ( $shortcode === 'blog_list' ) {
                $atts['posts_per_page'] = '10';
            }
            return $atts;
        }, 10, 2 );
        
        $output = do_shortcode( '[abt_blog_list posts_per_page="5"]' );
        // Should use filtered value of 10 instead of 5
        
        remove_all_filters( 'abt_shortcode_atts' );
    }
}