<?php
/**
 * Unit Tests for ABT_Citation_Processor
 * 
 * Tests the citation processing engine including style formatting,
 * bibliography generation, and citation parsing.
 * 
 * @package Academic_Bloggers_Toolkit
 * @subpackage Tests
 * @since 1.0.0
 */

class Test_ABT_Citation_Processor extends WP_UnitTestCase {

    /**
     * Citation processor instance
     * @var ABT_Citation_Processor
     */
    private $processor;

    /**
     * Style manager instance
     * @var ABT_Style_Manager
     */
    private $style_manager;

    /**
     * Test reference and citation IDs
     * @var array
     */
    private $test_data;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        $this->processor = new ABT_Citation_Processor();
        $this->style_manager = new ABT_Style_Manager();
        
        // Create test data
        $this->test_data = ABT_Test_Factory::create_blog_posts_with_citations( 1, 3 );
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        ABT_Test_Helper::cleanup_posts();
        parent::tearDown();
    }

    /**
     * Test citation style processing
     */
    public function test_process_citation_styles() {
        $ref_id = $this->test_data['ref_ids'][0];
        
        // Test APA style
        $apa_citation = $this->processor->format_citation( $ref_id, 'apa', 'in-text' );
        $this->assertIsString( $apa_citation );
        $this->assertStringContainsString( '(', $apa_citation ); // APA uses parentheses
        
        // Test MLA style
        $mla_citation = $this->processor->format_citation( $ref_id, 'mla', 'in-text' );
        $this->assertIsString( $mla_citation );
        
        // Test Chicago style
        $chicago_citation = $this->processor->format_citation( $ref_id, 'chicago', 'footnote' );
        $this->assertIsString( $chicago_citation );
        
        // Test Harvard style
        $harvard_citation = $this->processor->format_citation( $ref_id, 'harvard', 'in-text' );
        $this->assertIsString( $harvard_citation );
    }

    /**
     * Test bibliography generation
     */
    public function test_generate_bibliography() {
        $blog_id = $this->test_data['blog_ids'][0];
        
        // Test APA bibliography
        $apa_bibliography = $this->processor->generate_bibliography( $blog_id, 'apa' );
        $this->assertIsString( $apa_bibliography );
        $this->assertStringContainsString( '<div class="abt-bibliography"', $apa_bibliography );
        
        // Test sorted bibliography
        $sorted_bibliography = $this->processor->generate_bibliography( $blog_id, 'apa', array(
            'sort_by' => 'author'
        ) );
        $this->assertIsString( $sorted_bibliography );
        
        // Test filtered bibliography
        $filtered_bibliography = $this->processor->generate_bibliography( $blog_id, 'mla', array(
            'filter_by_type' => 'article'
        ) );
        $this->assertIsString( $filtered_bibliography );
    }

    /**
     * Test citation parsing in content
     */
    public function test_parse_citations_in_content() {
        $content = 'This is a test with citations [cite:123] and [cite:456] in the text.';
        
        $parsed_content = $this->processor->parse_citations_in_content( $content, 'apa' );
        
        $this->assertIsString( $parsed_content );
        $this->assertStringNotContainsString( '[cite:', $parsed_content );
        $this->assertStringContainsString( 'class="abt-citation"', $parsed_content );
    }

    /**
     * Test footnote processing
     */
    public function test_process_footnotes() {
        $content = 'This has a footnote[footnote]This is the footnote text[/footnote] in it.';
        
        $processed_content = $this->processor->process_footnotes( $content );
        
        $this->assertIsString( $processed_content );
        $this->assertStringContainsString( 'class="abt-footnote"', $processed_content );
        $this->assertStringContainsString( 'sup', $processed_content );
    }

    /**
     * Test style manager functionality
     */
    public function test_style_manager() {
        // Test getting available styles
        $styles = $this->style_manager->get_available_styles();
        $this->assertIsArray( $styles );
        $this->assertArrayHasKey( 'apa', $styles );
        $this->assertArrayHasKey( 'mla', $styles );
        $this->assertArrayHasKey( 'chicago', $styles );
        
        // Test style validation
        $this->assertTrue( $this->style_manager->is_valid_style( 'apa' ) );
        $this->assertTrue( $this->style_manager->is_valid_style( 'mla' ) );
        $this->assertFalse( $this->style_manager->is_valid_style( 'invalid_style' ) );
        
        // Test style configuration
        $apa_config = $this->style_manager->get_style_config( 'apa' );
        $this->assertIsArray( $apa_config );
        $this->assertArrayHasKey( 'name', $apa_config );
        $this->assertArrayHasKey( 'in_text_format', $apa_config );
        $this->assertArrayHasKey( 'bibliography_format', $apa_config );
    }

    /**
     * Test citation formatting with page numbers
     */
    public function test_citation_with_page_numbers() {
        $ref_id = $this->test_data['ref_ids'][0];
        
        // Test with page number
        $citation_with_page = $this->processor->format_citation( $ref_id, 'apa', 'in-text', array(
            'page' => '123'
        ) );
        $this->assertStringContainsString( '123', $citation_with_page );
        
        // Test with page range
        $citation_with_range = $this->processor->format_citation( $ref_id, 'apa', 'in-text', array(
            'page' => '123-125'
        ) );
        $this->assertStringContainsString( '123-125', $citation_with_range );
    }

    /**
     * Test multiple citations in one reference
     */
    public function test_multiple_citations() {
        $ref_ids = array_slice( $this->test_data['ref_ids'], 0, 3 );
        
        $multiple_citation = $this->processor->format_multiple_citations( $ref_ids, 'apa' );
        $this->assertIsString( $multiple_citation );
        
        // Should contain semicolons for APA style
        $this->assertStringContainsString( ';', $multiple_citation );
    }

    /**
     * Test citation style switching
     */
    public function test_citation_style_switching() {
        $blog_id = $this->test_data['blog_ids'][0];
        
        // Update citation style for the blog post
        $result = $this->processor->update_post_citation_style( $blog_id, 'mla' );
        $this->assertTrue( $result );
        
        // Verify the style was updated
        $current_style = get_post_meta( $blog_id, '_abt_citation_style', true );
        $this->assertEquals( 'mla', $current_style );
        
        // Test regenerating bibliography with new style
        $bibliography = $this->processor->generate_bibliography( $blog_id, 'mla' );
        $this->assertIsString( $bibliography );
    }

    /**
     * Test citation validation
     */
    public function test_citation_validation() {
        // Test valid citation data
        $valid_citation = array(
            'ref_id' => $this->test_data['ref_ids'][0],
            'type'   => 'in-text',
            'style'  => 'apa'
        );
        
        $result = $this->processor->validate_citation_data( $valid_citation );
        $this->assertTrue( $result['valid'] );
        $this->assertEmpty( $result['errors'] );
        
        // Test invalid citation data
        $invalid_citation = array(
            'ref_id' => 999999, // Non-existent reference
            'type'   => 'invalid_type',
            'style'  => 'invalid_style'
        );
        
        $result = $this->processor->validate_citation_data( $invalid_citation );
        $this->assertFalse( $result['valid'] );
        $this->assertNotEmpty( $result['errors'] );
    }

    /**
     * Test bibliography sorting and filtering
     */
    public function test_bibliography_sorting_filtering() {
        $blog_id = $this->test_data['blog_ids'][0];
        
        // Test sorting by author
        $sorted_by_author = $this->processor->generate_bibliography( $blog_id, 'apa', array(
            'sort_by' => 'author',
            'sort_order' => 'asc'
        ) );
        $this->assertIsString( $sorted_by_author );
        
        // Test sorting by year
        $sorted_by_year = $this->processor->generate_bibliography( $blog_id, 'apa', array(
            'sort_by' => 'year',
            'sort_order' => 'desc'
        ) );
        $this->assertIsString( $sorted_by_year );
        
        // Test filtering by reference type
        $filtered_articles = $this->processor->generate_bibliography( $blog_id, 'apa', array(
            'filter_by_type' => 'article'
        ) );
        $this->assertIsString( $filtered_articles );
    }

    /**
     * Test citation linking and backlinking
     */
    public function test_citation_linking() {
        $blog_id = $this->test_data['blog_ids'][0];
        $citation_id = $this->test_data['citation_ids'][0];
        
        // Test creating citation link
        $citation_link = $this->processor->create_citation_link( $citation_id, 'apa' );
        $this->assertIsString( $citation_link );
        $this->assertStringContainsString( 'href="#ref-', $citation_link );
        $this->assertStringContainsString( 'class="abt-citation-link"', $citation_link );
        
        // Test creating backlink
        $backlink = $this->processor->create_citation_backlink( $citation_id );
        $this->assertIsString( $backlink );
        $this->assertStringContainsString( 'href="#cite-', $backlink );
    }

    /**
     * Test CSL (Citation Style Language) integration
     */
    public function test_csl_integration() {
        // Test loading CSL file
        $csl_content = $this->style_manager->load_csl_file( 'apa' );
        $this->assertIsString( $csl_content );
        $this->assertStringContainsString( '<?xml', $csl_content );
        
        // Test CSL parsing
        $csl_data = $this->style_manager->parse_csl( $csl_content );
        $this->assertIsArray( $csl_data );
        
        // Test CSL-based formatting
        $ref_id = $this->test_data['ref_ids'][0];
        $csl_formatted = $this->processor->format_citation_with_csl( $ref_id, 'apa' );
        $this->assertIsString( $csl_formatted );
    }

    /**
     * Test citation statistics and analytics
     */
    public function test_citation_statistics() {
        $blog_id = $this->test_data['blog_ids'][0];
        
        $stats = $this->processor->get_citation_statistics( $blog_id );
        
        $this->assertIsArray( $stats );
        $this->assertArrayHasKey( 'total_citations', $stats );
        $this->assertArrayHasKey( 'citations_by_type', $stats );
        $this->assertArrayHasKey( 'most_cited_references', $stats );
        $this->assertGreaterThan( 0, $stats['total_citations'] );
    }

    /**
     * Test bibliography caching
     */
    public function test_bibliography_caching() {
        $blog_id = $this->test_data['blog_ids'][0];
        
        // Generate bibliography (should cache it)
        $bibliography1 = $this->processor->generate_bibliography( $blog_id, 'apa' );
        
        // Generate again (should use cache)
        $bibliography2 = $this->processor->generate_bibliography( $blog_id, 'apa' );
        
        $this->assertEquals( $bibliography1, $bibliography2 );
        
        // Test cache invalidation
        $this->processor->invalidate_bibliography_cache( $blog_id );
        
        // Should regenerate
        $bibliography3 = $this->processor->generate_bibliography( $blog_id, 'apa' );
        $this->assertIsString( $bibliography3 );
    }

    /**
     * Test citation export functionality
     */
    public function test_citation_export() {
        $blog_id = $this->test_data['blog_ids'][0];
        
        // Test exporting as BibTeX
        $bibtex_export = $this->processor->export_citations( $blog_id, 'bibtex' );
        $this->assertIsString( $bibtex_export );
        $this->assertStringContainsString( '@', $bibtex_export );
        
        // Test exporting as RIS
        $ris_export = $this->processor->export_citations( $blog_id, 'ris' );
        $this->assertIsString( $ris_export );
        $this->assertStringContainsString( 'TY  -', $ris_export );
        
        // Test exporting as JSON
        $json_export = $this->processor->export_citations( $blog_id, 'json' );
        $this->assertIsString( $json_export );
        $decoded = json_decode( $json_export, true );
        $this->assertIsArray( $decoded );
    }

    /**
     * Test custom citation formats
     */
    public function test_custom_citation_formats() {
        // Register a custom citation format
        $custom_format = array(
            'name' => 'Custom Test Format',
            'in_text_template' => '[{author_last}, {year}]',
            'bibliography_template' => '{author_full} ({year}). {title}. {journal}.'
        );
        
        $result = $this->style_manager->register_custom_style( 'custom_test', $custom_format );
        $this->assertTrue( $result );
        
        // Test using custom format
        $ref_id = $this->test_data['ref_ids'][0];
        $custom_citation = $this->processor->format_citation( $ref_id, 'custom_test', 'in-text' );
        $this->assertIsString( $custom_citation );
        $this->assertStringContainsString( '[', $custom_citation );
    }

    /**
     * Test citation error handling
     */
    public function test_citation_error_handling() {
        // Test with non-existent reference
        $invalid_citation = $this->processor->format_citation( 999999, 'apa', 'in-text' );
        $this->assertIsString( $invalid_citation );
        $this->assertStringContainsString( 'Reference not found', $invalid_citation );
        
        // Test with invalid style
        $ref_id = $this->test_data['ref_ids'][0];
        $invalid_style_citation = $this->processor->format_citation( $ref_id, 'invalid_style', 'in-text' );
        $this->assertIsString( $invalid_style_citation );
        
        // Test bibliography with no citations
        $empty_blog_id = ABT_Test_Helper::create_blog_post();
        $empty_bibliography = $this->processor->generate_bibliography( $empty_blog_id, 'apa' );
        $this->assertIsString( $empty_bibliography );
        $this->assertStringContainsString( 'No references', $empty_bibliography );
    }

    /**
     * Test citation content filtering
     */
    public function test_citation_content_filtering() {
        $blog_id = $this->test_data['blog_ids'][0];
        
        // Create post content with citations
        $content = 'This is a test post with citations [cite:' . $this->test_data['ref_ids'][0] . '] and more text.';
        
        // Update post content
        wp_update_post( array(
            'ID'           => $blog_id,
            'post_content' => $content
        ) );
        
        // Test content filtering
        $filtered_content = apply_filters( 'the_content', get_post_field( 'post_content', $blog_id ) );
        
        $this->assertIsString( $filtered_content );
        $this->assertStringNotContainsString( '[cite:', $filtered_content );
        $this->assertStringContainsString( 'class="abt-citation"', $filtered_content );
    }
}