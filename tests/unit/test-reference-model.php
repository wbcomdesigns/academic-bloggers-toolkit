<?php
/**
 * Unit Tests for ABT_Reference Model
 * 
 * Tests the reference model functionality including CRUD operations,
 * validation, search, and auto-cite features.
 * 
 * @package Academic_Bloggers_Toolkit
 * @subpackage Tests
 * @since 1.0.0
 */

class Test_ABT_Reference_Model extends WP_UnitTestCase {

    /**
     * Reference model instance
     * @var ABT_Reference
     */
    private $reference_model;

    /**
     * Test reference ID
     * @var int
     */
    private $test_ref_id;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        $this->reference_model = new ABT_Reference();
        
        // Create a test reference
        $this->test_ref_id = ABT_Test_Helper::create_reference();
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        ABT_Test_Helper::cleanup_posts();
        parent::tearDown();
    }

    /**
     * Test reference creation
     */
    public function test_create_reference() {
        $data = array(
            'title'     => 'Test Journal Article',
            'type'      => 'article',
            'authors'   => 'Smith, J.; Doe, A.',
            'year'      => '2023',
            'journal'   => 'Test Journal',
            'volume'    => '42',
            'issue'     => '3',
            'pages'     => '123-145',
            'doi'       => '10.1000/test.doi'
        );

        $ref_id = $this->reference_model->create_reference( $data );

        $this->assertIsInt( $ref_id );
        $this->assertGreaterThan( 0, $ref_id );

        // Verify post was created correctly
        $post = get_post( $ref_id );
        $this->assertEquals( 'abt_reference', $post->post_type );
        $this->assertEquals( 'Test Journal Article', $post->post_title );

        // Verify meta data
        ABT_Test_Helper::assertPostMeta( $ref_id, '_abt_ref_type', 'article' );
        ABT_Test_Helper::assertPostMeta( $ref_id, '_abt_ref_authors', 'Smith, J.; Doe, A.' );
        ABT_Test_Helper::assertPostMeta( $ref_id, '_abt_ref_year', '2023' );
        ABT_Test_Helper::assertPostMeta( $ref_id, '_abt_ref_doi', '10.1000/test.doi' );
    }

    /**
     * Test reference data validation
     */
    public function test_validate_reference_data() {
        // Valid data should pass
        $valid_data = array(
            'title'   => 'Valid Article',
            'type'    => 'article',
            'authors' => 'Smith, J.',
            'year'    => '2023'
        );

        $result = $this->reference_model->validate_reference_data( $valid_data );
        $this->assertTrue( $result['valid'] );
        $this->assertEmpty( $result['errors'] );

        // Invalid data should fail
        $invalid_data = array(
            'title'   => '', // Empty title
            'type'    => 'invalid_type',
            'authors' => '',
            'year'    => 'not_a_year'
        );

        $result = $this->reference_model->validate_reference_data( $invalid_data );
        $this->assertFalse( $result['valid'] );
        $this->assertNotEmpty( $result['errors'] );
        $this->assertContains( 'Title is required', $result['errors'] );
        $this->assertContains( 'Invalid reference type', $result['errors'] );
    }

    /**
     * Test reference search functionality
     */
    public function test_search_references() {
        // Create multiple test references
        $ref_ids = ABT_Test_Factory::create_references( 5 );

        // Search by title
        $results = $this->reference_model->search_references( array(
            'search' => 'Sample Journal Article'
        ) );

        $this->assertIsArray( $results );
        $this->assertGreaterThan( 0, count( $results ) );

        // Search by author
        $results = $this->reference_model->search_references( array(
            'author' => 'Smith'
        ) );

        $this->assertIsArray( $results );
        $this->assertGreaterThan( 0, count( $results ) );

        // Search by year
        $results = $this->reference_model->search_references( array(
            'year' => '2023'
        ) );

        $this->assertIsArray( $results );

        // Search by type
        $results = $this->reference_model->search_references( array(
            'type' => 'article'
        ) );

        $this->assertIsArray( $results );
        $this->assertGreaterThan( 0, count( $results ) );

        // Advanced search with multiple criteria
        $results = $this->reference_model->search_references( array(
            'search' => 'Sample',
            'type'   => 'article',
            'year'   => '2023'
        ) );

        $this->assertIsArray( $results );
    }

    /**
     * Test reference formatting for different citation styles
     */
    public function test_format_reference() {
        $ref_id = ABT_Test_Helper::create_reference( array(
            'post_title' => 'Test Article for Formatting',
            'meta_input' => array(
                '_abt_ref_type'    => 'article',
                '_abt_ref_authors' => 'Smith, J.; Doe, A.',
                '_abt_ref_year'    => '2023',
                '_abt_ref_journal' => 'Test Journal',
                '_abt_ref_volume'  => '42',
                '_abt_ref_pages'   => '123-145'
            )
        ) );

        // Test APA formatting
        $apa_formatted = $this->reference_model->format_reference( $ref_id, 'apa' );
        $this->assertIsString( $apa_formatted );
        $this->assertStringContainsString( 'Smith, J., & Doe, A.', $apa_formatted );
        $this->assertStringContainsString( '(2023)', $apa_formatted );
        $this->assertStringContainsString( 'Test Journal', $apa_formatted );

        // Test MLA formatting
        $mla_formatted = $this->reference_model->format_reference( $ref_id, 'mla' );
        $this->assertIsString( $mla_formatted );
        $this->assertStringContainsString( 'Smith, J.', $mla_formatted );

        // Test Chicago formatting
        $chicago_formatted = $this->reference_model->format_reference( $ref_id, 'chicago' );
        $this->assertIsString( $chicago_formatted );
    }

    /**
     * Test reference update functionality
     */
    public function test_update_reference() {
        $new_data = array(
            'title'   => 'Updated Test Reference',
            'authors' => 'Updated Author',
            'year'    => '2024'
        );

        $result = $this->reference_model->update_reference( $this->test_ref_id, $new_data );
        $this->assertTrue( $result );

        // Verify the update
        $post = get_post( $this->test_ref_id );
        $this->assertEquals( 'Updated Test Reference', $post->post_title );
        ABT_Test_Helper::assertPostMeta( $this->test_ref_id, '_abt_ref_authors', 'Updated Author' );
        ABT_Test_Helper::assertPostMeta( $this->test_ref_id, '_abt_ref_year', '2024' );
    }

    /**
     * Test reference deletion
     */
    public function test_delete_reference() {
        $result = $this->reference_model->delete_reference( $this->test_ref_id );
        $this->assertTrue( $result );

        // Verify deletion
        $post = get_post( $this->test_ref_id );
        $this->assertNull( $post );
    }

    /**
     * Test getting reference by ID
     */
    public function test_get_reference() {
        $reference = $this->reference_model->get_reference( $this->test_ref_id );

        $this->assertIsArray( $reference );
        $this->assertEquals( $this->test_ref_id, $reference['id'] );
        $this->assertArrayHasKey( 'title', $reference );
        $this->assertArrayHasKey( 'type', $reference );
        $this->assertArrayHasKey( 'authors', $reference );
    }

    /**
     * Test bulk operations
     */
    public function test_bulk_operations() {
        $ref_ids = ABT_Test_Factory::create_references( 3 );

        // Test bulk delete
        $result = $this->reference_model->bulk_delete_references( $ref_ids );
        $this->assertTrue( $result );

        // Verify deletion
        foreach ( $ref_ids as $ref_id ) {
            $post = get_post( $ref_id );
            $this->assertNull( $post );
        }

        // Test bulk update
        $ref_ids = ABT_Test_Factory::create_references( 3 );
        $update_data = array( 'year' => '2024' );

        $result = $this->reference_model->bulk_update_references( $ref_ids, $update_data );
        $this->assertTrue( $result );

        // Verify update
        foreach ( $ref_ids as $ref_id ) {
            ABT_Test_Helper::assertPostMeta( $ref_id, '_abt_ref_year', '2024' );
        }
    }

    /**
     * Test import/export functionality
     */
    public function test_import_export_references() {
        // Test export
        $ref_ids = ABT_Test_Factory::create_references( 2 );
        $exported_data = $this->reference_model->export_references( $ref_ids, 'array' );

        $this->assertIsArray( $exported_data );
        $this->assertCount( 2, $exported_data );

        // Test import
        $import_data = array(
            array(
                'title'   => 'Imported Reference 1',
                'type'    => 'article',
                'authors' => 'Import Author',
                'year'    => '2023'
            ),
            array(
                'title'   => 'Imported Reference 2',
                'type'    => 'book',
                'authors' => 'Book Author',
                'year'    => '2022'
            )
        );

        $result = $this->reference_model->import_references( $import_data );
        $this->assertTrue( $result['success'] );
        $this->assertCount( 2, $result['imported_ids'] );

        // Verify import
        foreach ( $result['imported_ids'] as $ref_id ) {
            $post = get_post( $ref_id );
            $this->assertEquals( 'abt_reference', $post->post_type );
        }
    }

    /**
     * Test auto-cite functionality (DOI)
     * 
     * @group external-api
     */
    public function test_auto_cite_doi() {
        $this->markTestSkipped( 'External API test - run manually' );

        $doi = '10.1038/nature12373';
        $result = $this->reference_model->auto_cite_doi( $doi );

        $this->assertIsArray( $result );
        $this->assertTrue( $result['success'] );
        $this->assertArrayHasKey( 'reference_data', $result );

        $ref_data = $result['reference_data'];
        $this->assertArrayHasKey( 'title', $ref_data );
        $this->assertArrayHasKey( 'authors', $ref_data );
        $this->assertArrayHasKey( 'year', $ref_data );
    }

    /**
     * Test reference statistics
     */
    public function test_get_reference_statistics() {
        // Create references and citations
        $test_data = ABT_Test_Factory::create_blog_posts_with_citations( 2, 3 );

        $stats = $this->reference_model->get_reference_statistics();

        $this->assertIsArray( $stats );
        $this->assertArrayHasKey( 'total_references', $stats );
        $this->assertArrayHasKey( 'references_by_type', $stats );
        $this->assertArrayHasKey( 'most_cited', $stats );
        $this->assertArrayHasKey( 'recent_additions', $stats );

        $this->assertGreaterThan( 0, $stats['total_references'] );
    }

    /**
     * Test reference validation for different types
     */
    public function test_validate_reference_types() {
        // Test article validation
        $article_data = array(
            'title'   => 'Test Article',
            'type'    => 'article',
            'authors' => 'Author, A.',
            'year'    => '2023',
            'journal' => 'Test Journal'
        );

        $result = $this->reference_model->validate_reference_data( $article_data );
        $this->assertTrue( $result['valid'] );

        // Test book validation
        $book_data = array(
            'title'     => 'Test Book',
            'type'      => 'book',
            'authors'   => 'Author, A.',
            'year'      => '2023',
            'publisher' => 'Test Publisher'
        );

        $result = $this->reference_model->validate_reference_data( $book_data );
        $this->assertTrue( $result['valid'] );

        // Test missing required fields
        $incomplete_data = array(
            'title' => 'Incomplete Article',
            'type'  => 'article'
            // Missing authors, year, journal
        );

        $result = $this->reference_model->validate_reference_data( $incomplete_data );
        $this->assertFalse( $result['valid'] );
        $this->assertNotEmpty( $result['errors'] );
    }

    /**
     * Test reference meta field handling
     */
    public function test_reference_meta_fields() {
        $ref_id = ABT_Test_Helper::create_reference( array(
            'meta_input' => array(
                '_abt_ref_type'        => 'article',
                '_abt_ref_authors'     => 'Smith, J.; Doe, A.',
                '_abt_ref_year'        => '2023',
                '_abt_ref_journal'     => 'Test Journal',
                '_abt_ref_doi'         => '10.1000/test.doi',
                '_abt_ref_custom_field' => 'Custom Value'
            )
        ) );

        // Test getting all meta fields
        $meta_fields = $this->reference_model->get_reference_meta( $ref_id );
        $this->assertIsArray( $meta_fields );
        $this->assertArrayHasKey( 'type', $meta_fields );
        $this->assertArrayHasKey( 'authors', $meta_fields );
        $this->assertArrayHasKey( 'doi', $meta_fields );

        // Test updating meta fields
        $new_meta = array(
            'year'    => '2024',
            'journal' => 'Updated Journal'
        );

        $result = $this->reference_model->update_reference_meta( $ref_id, $new_meta );
        $this->assertTrue( $result );

        ABT_Test_Helper::assertPostMeta( $ref_id, '_abt_ref_year', '2024' );
        ABT_Test_Helper::assertPostMeta( $ref_id, '_abt_ref_journal', 'Updated Journal' );
    }

    /**
     * Test reference search with complex queries
     */
    public function test_complex_reference_search() {
        // Create diverse references
        $refs = array();
        $refs[] = ABT_Test_Helper::create_reference( array(
            'post_title' => 'Machine Learning in Healthcare',
            'meta_input' => array(
                '_abt_ref_type'    => 'article',
                '_abt_ref_authors' => 'Johnson, A.; Smith, B.',
                '_abt_ref_year'    => '2023',
                '_abt_ref_journal' => 'Medical AI Journal'
            )
        ) );

        $refs[] = ABT_Test_Helper::create_reference( array(
            'post_title' => 'Deep Learning Fundamentals',
            'meta_input' => array(
                '_abt_ref_type'      => 'book',
                '_abt_ref_authors'   => 'Chen, L.',
                '_abt_ref_year'      => '2022',
                '_abt_ref_publisher' => 'Tech Books'
            )
        ) );

        // Test search with multiple keywords
        $results = $this->reference_model->search_references( array(
            'search' => 'machine learning'
        ) );
        $this->assertGreaterThan( 0, count( $results ) );

        // Test filtered search
        $results = $this->reference_model->search_references( array(
            'search' => 'learning',
            'type'   => 'article',
            'year'   => '2023'
        ) );
        $this->assertGreaterThan( 0, count( $results ) );

        // Test pagination
        $results = $this->reference_model->search_references( array(
            'limit'  => 1,
            'offset' => 0
        ) );
        $this->assertCount( 1, $results );
    }
}