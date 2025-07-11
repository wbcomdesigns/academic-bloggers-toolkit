<?php
/**
 * Citation statistics widget template
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
$show_posts = $show_posts ?? true;
$show_references = $show_references ?? true;
$show_citations = $show_citations ?? true;
$show_authors = $show_authors ?? false;

echo $before_widget;

if ( ! empty( $title ) ) {
    echo $before_title . $title . $after_title;
}
?>

<div class="abt-stats-widget">
    <div class="abt-stats-grid">
        
        <?php if ( $show_posts ) : ?>
            <?php $post_count = wp_count_posts( 'abt_blog' )->publish; ?>
            <div class="abt-stat-item abt-stat-posts">
                <div class="abt-stat-icon" aria-hidden="true">📄</div>
                <div class="abt-stat-content">
                    <span class="abt-stat-number"><?php echo number_format( $post_count ); ?></span>
                    <span class="abt-stat-label"><?php _e( 'Academic Posts', 'academic-bloggers-toolkit' ); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( $show_references ) : ?>
            <?php $ref_count = wp_count_posts( 'abt_reference' )->publish; ?>
            <div class="abt-stat-item abt-stat-references">
                <div class="abt-stat-icon" aria-hidden="true">📚</div>
                <div class="abt-stat-content">
                    <span class="abt-stat-number"><?php echo number_format( $ref_count ); ?></span>
                    <span class="abt-stat-label"><?php _e( 'References', 'academic-bloggers-toolkit' ); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( $show_citations ) : ?>
            <?php
            // Calculate total citations
            global $wpdb;
            $citation_count = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'abt_citation' AND post_status = 'publish'"
            );
            $citation_count = $citation_count ? $citation_count : 0;
            ?>
            <div class="abt-stat-item abt-stat-citations">
                <div class="abt-stat-icon" aria-hidden="true">📖</div>
                <div class="abt-stat-content">
                    <span class="abt-stat-number"><?php echo number_format( $citation_count ); ?></span>
                    <span class="abt-stat-label"><?php _e( 'Citations', 'academic-bloggers-toolkit' ); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( $show_authors ) : ?>
            <?php
            // Count unique authors who have published academic posts
            $author_count = $wpdb->get_var(
                "SELECT COUNT(DISTINCT post_author) 
                 FROM {$wpdb->posts} 
                 WHERE post_type = 'abt_blog' 
                 AND post_status = 'publish'"
            );
            $author_count = $author_count ? $author_count : 0;
            ?>
            <div class="abt-stat-item abt-stat-authors">
                <div class="abt-stat-icon" aria-hidden="true">👥</div>
                <div class="abt-stat-content">
                    <span class="abt-stat-number"><?php echo number_format( $author_count ); ?></span>
                    <span class="abt-stat-label"><?php _e( 'Authors', 'academic-bloggers-toolkit' ); ?></span>
                </div>
            </div>
        <?php endif; ?>

    </div>
    
    <!-- Additional Statistics -->
    <div class="abt-extended-stats">
        
        <!-- Most Active Subject -->
        <?php
        $popular_subject = get_terms( array(
            'taxonomy' => 'abt_subject',
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 1,
            'hide_empty' => true
        ) );
        
        if ( ! empty( $popular_subject ) && ! is_wp_error( $popular_subject ) ) :
        ?>
            <div class="abt-popular-subject">
                <h4 class="abt-stat-subtitle"><?php _e( 'Most Active Subject', 'academic-bloggers-toolkit' ); ?></h4>
                <div class="abt-subject-info">
                    <a href="<?php echo get_term_link( $popular_subject[0] ); ?>" class="abt-subject-link">
                        <?php echo esc_html( $popular_subject[0]->name ); ?>
                    </a>
                    <span class="abt-subject-count">
                        <?php printf( _n( '%d post', '%d posts', $popular_subject[0]->count, 'academic-bloggers-toolkit' ), $popular_subject[0]->count ); ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Recent Activity -->
        <div class="abt-recent-activity">
            <h4 class="abt-stat-subtitle"><?php _e( 'Recent Activity', 'academic-bloggers-toolkit' ); ?></h4>
            
            <?php
            // Get posts from last 30 days
            $recent_posts = get_posts( array(
                'post_type' => 'abt_blog',
                'posts_per_page' => 1,
                'date_query' => array(
                    array(
                        'after' => '30 days ago'
                    )
                ),
                'fields' => 'ids'
            ) );
            
            $posts_last_month = count( $recent_posts );
            
            // Get references added in last 30 days
            $recent_refs = get_posts( array(
                'post_type' => 'abt_reference',
                'posts_per_page' => -1,
                'date_query' => array(
                    array(
                        'after' => '30 days ago'
                    )
                ),
                'fields' => 'ids'
            ) );
            
            $refs_last_month = count( $recent_refs );
            ?>
            
            <div class="abt-activity-summary">
                <div class="abt-activity-item">
                    <span class="abt-activity-number"><?php echo $posts_last_month; ?></span>
                    <span class="abt-activity-label"><?php _e( 'posts this month', 'academic-bloggers-toolkit' ); ?></span>
                </div>
                <div class="abt-activity-item">
                    <span class="abt-activity-number"><?php echo $refs_last_month; ?></span>
                    <span class="abt-activity-label"><?php _e( 'references added', 'academic-bloggers-toolkit' ); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Top Authors (if enabled) -->
        <?php if ( $show_authors && $author_count > 1 ) : ?>
            <?php
            $top_authors = $wpdb->get_results(
                "SELECT post_author, COUNT(*) as post_count 
                 FROM {$wpdb->posts} 
                 WHERE post_type = 'abt_blog' 
                 AND post_status = 'publish' 
                 GROUP BY post_author 
                 ORDER BY post_count DESC 
                 LIMIT 3"
            );
            ?>
            
            <?php if ( ! empty( $top_authors ) ) : ?>
                <div class="abt-top-authors">
                    <h4 class="abt-stat-subtitle"><?php _e( 'Top Contributors', 'academic-bloggers-toolkit' ); ?></h4>
                    <ul class="abt-authors-list">
                        <?php foreach ( $top_authors as $author_data ) : ?>
                            <?php $author = get_userdata( $author_data->post_author ); ?>
                            <?php if ( $author ) : ?>
                                <li class="abt-author-item">
                                    <a href="<?php echo get_author_posts_url( $author->ID ); ?>?post_type=abt_blog" class="abt-author-link">
                                        <?php echo get_avatar( $author->ID, 24, '', '', array( 'class' => 'abt-author-mini-avatar' ) ); ?>
                                        <span class="abt-author-name"><?php echo esc_html( $author->display_name ); ?></span>
                                        <span class="abt-author-count"><?php echo $author_data->post_count; ?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Widget Footer -->
    <div class="abt-widget-footer">
        <div class="abt-widget-links">
            <a href="<?php echo get_post_type_archive_link( 'abt_blog' ); ?>" class="abt-browse-posts">
                <?php _e( 'Browse Posts', 'academic-bloggers-toolkit' ); ?>
            </a>
            <span class="abt-link-separator">|</span>
            <a href="<?php echo admin_url( 'edit.php?post_type=abt_reference' ); ?>" class="abt-browse-references">
                <?php _e( 'Browse References', 'academic-bloggers-toolkit' ); ?>
            </a>
        </div>
        
        <div class="abt-last-updated">
            <small class="abt-update-time">
                <?php printf( __( 'Updated %s', 'academic-bloggers-toolkit' ), date_i18n( get_option( 'time_format' ) ) ); ?>
            </small>
        </div>
    </div>
</div>

<?php
echo $after_widget;
?>