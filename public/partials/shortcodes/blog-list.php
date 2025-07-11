<?php
/**
 * Blog list shortcode template
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

// Query arguments
$query_args = array(
    'post_type'      => 'abt_blog',
    'posts_per_page' => $posts_per_page,
    'orderby'        => $orderby,
    'order'          => $order,
    'post_status'    => 'publish',
);

// Add taxonomy queries if specified
$tax_query = array();

if ( ! empty( $category ) ) {
    $tax_query[] = array(
        'taxonomy' => 'abt_blog_category',
        'field'    => 'slug',
        'terms'    => explode( ',', $category ),
    );
}

if ( ! empty( $tag ) ) {
    $tax_query[] = array(
        'taxonomy' => 'abt_blog_tag',
        'field'    => 'slug',
        'terms'    => explode( ',', $tag ),
    );
}

if ( ! empty( $subject ) ) {
    $tax_query[] = array(
        'taxonomy' => 'abt_subject',
        'field'    => 'slug',
        'terms'    => explode( ',', $subject ),
    );
}

if ( count( $tax_query ) > 1 ) {
    $tax_query['relation'] = 'AND';
}

if ( ! empty( $tax_query ) ) {
    $query_args['tax_query'] = $tax_query;
}

// Add author filter
if ( ! empty( $author ) ) {
    if ( is_numeric( $author ) ) {
        $query_args['author'] = $author;
    } else {
        $query_args['author_name'] = $author;
    }
}

$query = new WP_Query( $query_args );

if ( ! $query->have_posts() ) {
    echo '<p class="abt-no-posts">' . __( 'No academic posts found.', 'academic-bloggers-toolkit' ) . '</p>';
    return;
}
?>

<div class="abt-blog-list abt-layout-<?php echo esc_attr( $layout ); ?> <?php echo esc_attr( $class ); ?>">
    <?php while ( $query->have_posts() ) : $query->the_post(); ?>
        <article class="abt-blog-item" id="post-<?php the_ID(); ?>">
            <header class="abt-blog-header">
                <h3 class="abt-blog-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                
                <?php if ( $show_meta === 'true' ) : ?>
                    <div class="abt-blog-meta">
                        <div class="abt-author-info">
                            <?php echo get_avatar( get_the_author_meta( 'ID' ), 32, '', '', array( 'class' => 'abt-author-avatar' ) ); ?>
                            <span class="abt-author">
                                <a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>">
                                    <?php the_author(); ?>
                                </a>
                            </span>
                        </div>
                        
                        <div class="abt-date-info">
                            <time datetime="<?php echo get_the_date( 'c' ); ?>" class="abt-published-date">
                                <?php echo get_the_date(); ?>
                            </time>
                            
                            <?php if ( get_the_modified_date() !== get_the_date() ) : ?>
                                <time datetime="<?php echo get_the_modified_date( 'c' ); ?>" class="abt-modified-date">
                                    <?php printf( __( '(Updated %s)', 'academic-bloggers-toolkit' ), get_the_modified_date() ); ?>
                                </time>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ( $show_citation_count === 'true' ) : ?>
                            <div class="abt-citation-info">
                                <?php
                                $citations = get_post_meta( get_the_ID(), '_abt_citations', true );
                                $citation_count = is_array( $citations ) ? count( $citations ) : 0;
                                ?>
                                <span class="abt-citations">
                                    <?php printf( _n( '%d citation', '%d citations', $citation_count, 'academic-bloggers-toolkit' ), $citation_count ); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </header>
            
            <?php if ( $show_excerpt === 'true' ) : ?>
                <div class="abt-blog-excerpt">
                    <?php 
                    $abstract = get_post_meta( get_the_ID(), '_abt_abstract', true );
                    if ( $abstract ) {
                        echo '<div class="abt-abstract-excerpt">';
                        echo wp_trim_words( wp_kses_post( $abstract ), 30, '...' );
                        echo '</div>';
                    } else {
                        the_excerpt();
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <footer class="abt-blog-footer">
                <?php
                // Display subjects
                $subjects = wp_get_post_terms( get_the_ID(), 'abt_subject' );
                if ( ! empty( $subjects ) && ! is_wp_error( $subjects ) ) :
                ?>
                    <div class="abt-subjects">
                        <span class="abt-tax-label"><?php _e( 'Subjects:', 'academic-bloggers-toolkit' ); ?></span>
                        <?php foreach ( $subjects as $subject ) : ?>
                            <a href="<?php echo get_term_link( $subject ); ?>" class="abt-subject-tag">
                                <?php echo esc_html( $subject->name ); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php
                // Display categories
                $categories = wp_get_post_terms( get_the_ID(), 'abt_blog_category' );
                if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) :
                ?>
                    <div class="abt-categories">
                        <span class="abt-tax-label"><?php _e( 'Categories:', 'academic-bloggers-toolkit' ); ?></span>
                        <?php foreach ( $categories as $category ) : ?>
                            <a href="<?php echo get_term_link( $category ); ?>" class="abt-category-tag">
                                <?php echo esc_html( $category->name ); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="abt-actions">
                    <a href="<?php the_permalink(); ?>" class="abt-read-more">
                        <?php _e( 'Read Full Article', 'academic-bloggers-toolkit' ); ?>
                        <span class="abt-reading-time">
                            <?php
                            // Calculate reading time
                            $content = get_post_field( 'post_content', get_the_ID() );
                            $word_count = str_word_count( strip_tags( $content ) );
                            $reading_time = ceil( $word_count / 200 ); // 200 words per minute
                            printf( __( '(%d min read)', 'academic-bloggers-toolkit' ), $reading_time );
                            ?>
                        </span>
                    </a>
                </div>
            </footer>
        </article>
    <?php endwhile; ?>
</div>

<?php
wp_reset_postdata();
?>