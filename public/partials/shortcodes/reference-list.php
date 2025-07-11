<?php
/**
 * Reference list shortcode template
 *
 * @link https://github.com/navidkashani/academic-bloggers-toolkit
 * @since 1.0.0
 *
 * @package Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/public/partials/shortcodes
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Extract variables from shortcode attributes
extract( $args );

$query_args = array(
    'post_type'      => 'abt_reference',
    'posts_per_page' => $posts_per_page,
    'orderby'        => $orderby,
    'order'          => $order,
    'post_status'    => 'publish',
);

// Add category filter
if ( ! empty( $category ) ) {
    $query_args['tax_query'] = array(
        array(
            'taxonomy' => 'abt_ref_category',
            'field'    => 'slug',
            'terms'    => explode( ',', $category ),
        ),
    );
}

$query = new WP_Query( $query_args );

if ( ! $query->have_posts() ) {
    echo '<p class="abt-no-references">' . __( 'No references found.', 'academic-bloggers-toolkit' ) . '</p>';
    return;
}

// Load formatter if available
$formatter = null;
if ( class_exists( 'ABT_Formatter' ) ) {
    $formatter = new ABT_Formatter();
}
?>

<div class="abt-reference-list <?php echo esc_attr( $class ); ?>">
    <ol class="abt-reference-items" start="1">
        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
            <li class="abt-reference-item" id="reference-<?php the_ID(); ?>">
                <div class="abt-reference-content">
                    <?php
                    if ( $formatter ) {
                        // Get reference metadata
                        $reference_data = array(
                            'title' => get_the_title(),
                            'author' => get_post_meta( get_the_ID(), '_abt_author', true ),
                            'year' => get_post_meta( get_the_ID(), '_abt_year', true ),
                            'journal' => get_post_meta( get_the_ID(), '_abt_journal', true ),
                            'volume' => get_post_meta( get_the_ID(), '_abt_volume', true ),
                            'issue' => get_post_meta( get_the_ID(), '_abt_issue', true ),
                            'pages' => get_post_meta( get_the_ID(), '_abt_pages', true ),
                            'doi' => get_post_meta( get_the_ID(), '_abt_doi', true ),
                            'url' => get_post_meta( get_the_ID(), '_abt_url', true ),
                            'publisher' => get_post_meta( get_the_ID(), '_abt_publisher', true ),
                            'publication_place' => get_post_meta( get_the_ID(), '_abt_publication_place', true ),
                            'isbn' => get_post_meta( get_the_ID(), '_abt_isbn', true ),
                            'type' => get_post_meta( get_the_ID(), '_abt_type', true ),
                        );

                        // Format citation
                        $formatted = $formatter->format_reference( $reference_data, $style );
                        echo wp_kses_post( $formatted );
                    } else {
                        // Fallback: display basic reference information
                        $author = get_post_meta( get_the_ID(), '_abt_author', true );
                        $year = get_post_meta( get_the_ID(), '_abt_year', true );
                        $journal = get_post_meta( get_the_ID(), '_abt_journal', true );
                        
                        echo '<span class="abt-ref-author">' . esc_html( $author ) . '</span> ';
                        if ( $year ) {
                            echo '<span class="abt-ref-year">(' . esc_html( $year ) . ')</span> ';
                        }
                        echo '<span class="abt-ref-title"><em>' . get_the_title() . '</em></span>';
                        if ( $journal ) {
                            echo ' <span class="abt-ref-journal">' . esc_html( $journal ) . '</span>';
                        }
                    }
                    ?>
                </div>
                
                <?php if ( $show_abstract === 'true' ) : ?>
                    <?php $abstract = get_post_meta( get_the_ID(), '_abt_abstract', true ); ?>
                    <?php if ( $abstract ) : ?>
                        <div class="abt-reference-abstract">
                            <strong><?php _e( 'Abstract:', 'academic-bloggers-toolkit' ); ?></strong>
                            <?php echo wp_kses_post( wpautop( $abstract ) ); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="abt-reference-meta">
                    <?php
                    // Display DOI if available
                    $doi = get_post_meta( get_the_ID(), '_abt_doi', true );
                    if ( $doi ) :
                    ?>
                        <div class="abt-ref-doi">
                            <strong><?php _e( 'DOI:', 'academic-bloggers-toolkit' ); ?></strong>
                            <a href="https://doi.org/<?php echo esc_attr( $doi ); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html( $doi ); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    // Display URL if available
                    $url = get_post_meta( get_the_ID(), '_abt_url', true );
                    if ( $url && ! $doi ) : // Only show URL if no DOI
                    ?>
                        <div class="abt-ref-url">
                            <strong><?php _e( 'URL:', 'academic-bloggers-toolkit' ); ?></strong>
                            <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html( $url ); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    // Display reference categories
                    $categories = wp_get_post_terms( get_the_ID(), 'abt_ref_category' );
                    if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) :
                    ?>
                        <div class="abt-ref-categories">
                            <strong><?php _e( 'Categories:', 'academic-bloggers-toolkit' ); ?></strong>
                            <?php foreach ( $categories as $category ) : ?>
                                <span class="abt-ref-category-tag"><?php echo esc_html( $category->name ); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    // Show citation count
                    $citation_count = get_post_meta( get_the_ID(), '_abt_citation_count', true );
                    if ( $citation_count ) :
                    ?>
                        <div class="abt-ref-citations">
                            <strong><?php _e( 'Cited:', 'academic-bloggers-toolkit' ); ?></strong>
                            <span class="abt-citation-count">
                                <?php printf( _n( '%d time', '%d times', $citation_count, 'academic-bloggers-toolkit' ), $citation_count ); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </li>
        <?php endwhile; ?>
    </ol>
</div>

<?php
wp_reset_postdata();
?>