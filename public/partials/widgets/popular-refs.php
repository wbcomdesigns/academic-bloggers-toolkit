<?php
/**
 * Popular references widget template
 *
 * @link https://github.com/navidkashani/academic-bloggers-toolkit
 * @since 1.0.0
 *
 * @package Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/public/partials/widgets
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Extract widget arguments and instance data
extract( $args );
extract( $instance );

$title = apply_filters( 'widget_title', $title ?? '' );
$number = $number ?? 5;
$show_citation_count = $show_citation_count ?? true;
$show_authors = $show_authors ?? true;

echo $before_widget;

if ( ! empty( $title ) ) {
    echo $before_title . $title . $after_title;
}

// Get references ordered by citation count
$references = get_posts( array(
    'post_type' => 'abt_reference',
    'posts_per_page' => $number,
    'meta_key' => '_abt_citation_count',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => '_abt_citation_count',
            'value' => 0,
            'compare' => '>'
        )
    )
) );

if ( ! empty( $references ) ) :
?>
    <ol class="abt-popular-references-list">
        <?php foreach ( $references as $reference ) : ?>
            <li class="abt-popular-reference-item">
                <article class="abt-widget-reference">
                    <h4 class="abt-widget-reference-title">
                        <?php echo esc_html( wp_trim_words( $reference->post_title, 12 ) ); ?>
                    </h4>
                    
                    <div class="abt-widget-reference-meta">
                        <?php if ( $show_authors ) : ?>
                            <?php $authors = get_post_meta( $reference->ID, '_abt_author', true ); ?>
                            <?php if ( $authors ) : ?>
                                <div class="abt-widget-reference-authors">
                                    <span class="abt-meta-icon" aria-hidden="true">👥</span>
                                    <span class="abt-authors-text">
                                        <?php echo esc_html( wp_trim_words( $authors, 8 ) ); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php $year = get_post_meta( $reference->ID, '_abt_year', true ); ?>
                        <?php if ( $year ) : ?>
                            <div class="abt-widget-reference-year">
                                <span class="abt-meta-icon" aria-hidden="true">📅</span>
                                <span class="abt-year-text"><?php echo esc_html( $year ); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php $journal = get_post_meta( $reference->ID, '_abt_journal', true ); ?>
                        <?php if ( $journal ) : ?>
                            <div class="abt-widget-reference-journal">
                                <span class="abt-meta-icon" aria-hidden="true">📰</span>
                                <span class="abt-journal-text">
                                    <em><?php echo esc_html( wp_trim_words( $journal, 6 ) ); ?></em>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( $show_citation_count ) : ?>
                            <?php $citation_count = get_post_meta( $reference->ID, '_abt_citation_count', true ); ?>
                            <div class="abt-widget-reference-citations">
                                <span class="abt-meta-icon" aria-hidden="true">📖</span>
                                <span class="abt-citation-count-text">
                                    <?php printf( _n( '%d citation', '%d citations', $citation_count, 'academic-bloggers-toolkit' ), $citation_count ); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php
                    // Display reference type
                    $ref_type = get_post_meta( $reference->ID, '_abt_type', true );
                    if ( $ref_type ) :
                    ?>
                        <div class="abt-widget-reference-type">
                            <span class="abt-type-badge abt-type-<?php echo esc_attr( sanitize_html_class( $ref_type ) ); ?>">
                                <?php echo esc_html( ucfirst( $ref_type ) ); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    // Display DOI or URL if available
                    $doi = get_post_meta( $reference->ID, '_abt_doi', true );
                    $url = get_post_meta( $reference->ID, '_abt_url', true );
                    if ( $doi ) :
                    ?>
                        <div class="abt-widget-reference-link">
                            <a href="https://doi.org/<?php echo esc_attr( $doi ); ?>" target="_blank" rel="noopener" class="abt-doi-link">
                                <span class="abt-meta-icon" aria-hidden="true">🔗</span>
                                <?php _e( 'View DOI', 'academic-bloggers-toolkit' ); ?>
                            </a>
                        </div>
                    <?php elseif ( $url ) : ?>
                        <div class="abt-widget-reference-link">
                            <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" class="abt-url-link">
                                <span class="abt-meta-icon" aria-hidden="true">🔗</span>
                                <?php _e( 'View Source', 'academic-bloggers-toolkit' ); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    // Display categories if available
                    $categories = wp_get_post_terms( $reference->ID, 'abt_ref_category' );
                    if ( ! empty( $categories ) && ! is_wp_error( $categories ) && count( $categories ) <= 2 ) :
                    ?>
                        <div class="abt-widget-reference-categories">
                            <?php foreach ( array_slice( $categories, 0, 2 ) as $category ) : ?>
                                <span class="abt-widget-category-tag">
                                    <?php echo esc_html( $category->name ); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            </li>
        <?php endforeach; ?>
    </ol>
    
    <div class="abt-widget-footer">
        <a href="<?php echo admin_url( 'edit.php?post_type=abt_reference' ); ?>" class="abt-view-all-references">
            <?php _e( 'View All References', 'academic-bloggers-toolkit' ); ?> →
        </a>
    </div>

<?php else : ?>
    <p class="abt-no-references"><?php _e( 'No popular references found.', 'academic-bloggers-toolkit' ); ?></p>
<?php endif;

echo $after_widget;
?>