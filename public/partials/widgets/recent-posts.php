<?php
/**
 * Recent posts widget template
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
$show_date = $show_date ?? true;
$show_author = $show_author ?? true;
$show_excerpt = $show_excerpt ?? false;

echo $before_widget;

if ( ! empty( $title ) ) {
    echo $before_title . $title . $after_title;
}

$posts = get_posts( array(
    'post_type' => 'abt_blog',
    'posts_per_page' => $number,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
) );

if ( ! empty( $posts ) ) :
?>
    <ul class="abt-recent-posts-list">
        <?php foreach ( $posts as $post ) : ?>
            <li class="abt-recent-post-item">
                <article class="abt-widget-post">
                    <h4 class="abt-widget-post-title">
                        <a href="<?php echo get_permalink( $post->ID ); ?>" title="<?php echo esc_attr( $post->post_title ); ?>">
                            <?php echo esc_html( wp_trim_words( $post->post_title, 8 ) ); ?>
                        </a>
                    </h4>
                    
                    <div class="abt-widget-post-meta">
                        <?php if ( $show_author ) : ?>
                            <span class="abt-widget-post-author">
                                <span class="abt-meta-icon" aria-hidden="true">👤</span>
                                <a href="<?php echo get_author_posts_url( $post->post_author ); ?>">
                                    <?php echo get_the_author_meta( 'display_name', $post->post_author ); ?>
                                </a>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ( $show_date ) : ?>
                            <time class="abt-widget-post-date" datetime="<?php echo get_the_date( 'c', $post->ID ); ?>">
                                <span class="abt-meta-icon" aria-hidden="true">📅</span>
                                <?php echo get_the_date( '', $post->ID ); ?>
                            </time>
                        <?php endif; ?>
                        
                        <?php
                        // Show citation count
                        $citations = get_post_meta( $post->ID, '_abt_citations', true );
                        $citation_count = is_array( $citations ) ? count( $citations ) : 0;
                        if ( $citation_count > 0 ) :
                        ?>
                            <span class="abt-widget-post-citations">
                                <span class="abt-meta-icon" aria-hidden="true">📖</span>
                                <?php printf( _n( '%d citation', '%d citations', $citation_count, 'academic-bloggers-toolkit' ), $citation_count ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ( $show_excerpt ) : ?>
                        <div class="abt-widget-post-excerpt">
                            <?php
                            $abstract = get_post_meta( $post->ID, '_abt_abstract', true );
                            if ( $abstract ) {
                                echo wp_trim_words( wp_kses_post( $abstract ), 15, '...' );
                            } else {
                                echo wp_trim_words( get_post_field( 'post_content', $post->ID ), 15, '...' );
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    // Display subjects for this post
                    $subjects = wp_get_post_terms( $post->ID, 'abt_subject' );
                    if ( ! empty( $subjects ) && ! is_wp_error( $subjects ) && count( $subjects ) <= 3 ) :
                    ?>
                        <div class="abt-widget-post-subjects">
                            <?php foreach ( array_slice( $subjects, 0, 3 ) as $subject ) : ?>
                                <a href="<?php echo get_term_link( $subject ); ?>" class="abt-widget-subject-tag">
                                    <?php echo esc_html( $subject->name ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <div class="abt-widget-footer">
        <a href="<?php echo get_post_type_archive_link( 'abt_blog' ); ?>" class="abt-view-all-posts">
            <?php _e( 'View All Academic Posts', 'academic-bloggers-toolkit' ); ?> →
        </a>
    </div>

<?php else : ?>
    <p class="abt-no-posts"><?php _e( 'No recent academic posts found.', 'academic-bloggers-toolkit' ); ?></p>
<?php endif;

echo $after_widget;
?>