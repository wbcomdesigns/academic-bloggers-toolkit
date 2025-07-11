<?php
/**
 * Author profile shortcode template
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

if ( ! $user_id ) {
    echo '<p class="abt-error">' . __( 'No user specified.', 'academic-bloggers-toolkit' ) . '</p>';
    return;
}

$author = get_userdata( $user_id );
if ( ! $author ) {
    echo '<p class="abt-error">' . __( 'User not found.', 'academic-bloggers-toolkit' ) . '</p>';
    return;
}

// Get additional author meta
$affiliation = get_user_meta( $user_id, 'affiliation', true );
$orcid = get_user_meta( $user_id, 'orcid', true );
$research_interests = get_user_meta( $user_id, 'research_interests', true );
$website = $author->user_url;
?>

<div class="abt-author-profile <?php echo esc_attr( $class ); ?>" itemscope itemtype="https://schema.org/Person">
    <div class="abt-author-header">
        <?php if ( $show_avatar === 'true' ) : ?>
            <div class="abt-author-avatar">
                <?php echo get_avatar( $author->ID, 120, '', $author->display_name, array( 'class' => 'abt-avatar-image' ) ); ?>
            </div>
        <?php endif; ?>
        
        <div class="abt-author-info">
            <h3 class="abt-author-name" itemprop="name"><?php echo esc_html( $author->display_name ); ?></h3>
            
            <?php if ( $affiliation ) : ?>
                <div class="abt-author-affiliation" itemprop="affiliation">
                    <?php echo esc_html( $affiliation ); ?>
                </div>
            <?php endif; ?>
            
            <div class="abt-author-contact">
                <?php if ( $website ) : ?>
                    <a href="<?php echo esc_url( $website ); ?>" class="abt-author-website" target="_blank" rel="noopener" itemprop="url">
                        <span class="abt-icon">🌐</span>
                        <?php _e( 'Website', 'academic-bloggers-toolkit' ); ?>
                    </a>
                <?php endif; ?>
                
                <?php if ( $orcid ) : ?>
                    <a href="https://orcid.org/<?php echo esc_attr( $orcid ); ?>" class="abt-author-orcid" target="_blank" rel="noopener">
                        <span class="abt-icon">🆔</span>
                        <?php _e( 'ORCID', 'academic-bloggers-toolkit' ); ?>
                    </a>
                <?php endif; ?>
                
                <a href="mailto:<?php echo esc_attr( $author->user_email ); ?>" class="abt-author-email" itemprop="email">
                    <span class="abt-icon">✉️</span>
                    <?php _e( 'Contact', 'academic-bloggers-toolkit' ); ?>
                </a>
            </div>
        </div>
    </div>
    
    <?php if ( $show_bio === 'true' && $author->description ) : ?>
        <div class="abt-author-bio" itemprop="description">
            <h4><?php _e( 'Biography', 'academic-bloggers-toolkit' ); ?></h4>
            <?php echo wp_kses_post( wpautop( $author->description ) ); ?>
        </div>
    <?php endif; ?>
    
    <?php if ( $research_interests ) : ?>
        <div class="abt-research-interests">
            <h4><?php _e( 'Research Interests', 'academic-bloggers-toolkit' ); ?></h4>
            <div class="abt-interests-list">
                <?php
                $interests = explode( ',', $research_interests );
                foreach ( $interests as $interest ) {
                    $interest = trim( $interest );
                    if ( $interest ) {
                        echo '<span class="abt-interest-tag">' . esc_html( $interest ) . '</span>';
                    }
                }
                ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ( $show_posts === 'true' ) : ?>
        <?php
        $posts = get_posts( array(
            'author'         => $author->ID,
            'post_type'      => 'abt_blog',
            'posts_per_page' => intval( $posts_count ),
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC'
        ) );
        ?>
        
        <?php if ( ! empty( $posts ) ) : ?>
            <div class="abt-author-posts">
                <h4><?php _e( 'Recent Publications', 'academic-bloggers-toolkit' ); ?></h4>
                <div class="abt-posts-list">
                    <?php foreach ( $posts as $post ) : ?>
                        <article class="abt-post-item">
                            <h5 class="abt-post-title">
                                <a href="<?php echo get_permalink( $post->ID ); ?>">
                                    <?php echo esc_html( $post->post_title ); ?>
                                </a>
                            </h5>
                            
                            <div class="abt-post-meta">
                                <time class="abt-post-date" datetime="<?php echo get_the_date( 'c', $post->ID ); ?>">
                                    <?php echo get_the_date( '', $post->ID ); ?>
                                </time>
                                
                                <?php
                                $citations = get_post_meta( $post->ID, '_abt_citations', true );
                                $citation_count = is_array( $citations ) ? count( $citations ) : 0;
                                if ( $citation_count > 0 ) :
                                ?>
                                    <span class="abt-post-citations">
                                        <?php printf( _n( '%d citation', '%d citations', $citation_count, 'academic-bloggers-toolkit' ), $citation_count ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="abt-post-excerpt">
                                <?php
                                $abstract = get_post_meta( $post->ID, '_abt_abstract', true );
                                if ( $abstract ) {
                                    echo wp_trim_words( wp_kses_post( $abstract ), 20, '...' );
                                } else {
                                    echo wp_trim_words( get_post_field( 'post_content', $post->ID ), 20, '...' );
                                }
                                ?>
                            </div>
                            
                            <?php
                            // Display subjects for this post
                            $subjects = wp_get_post_terms( $post->ID, 'abt_subject' );
                            if ( ! empty( $subjects ) && ! is_wp_error( $subjects ) ) :
                            ?>
                                <div class="abt-post-subjects">
                                    <?php foreach ( $subjects as $subject ) : ?>
                                        <span class="abt-subject-tag"><?php echo esc_html( $subject->name ); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
                
                <?php
                // Show "View All" link if there are more posts
                $total_posts = count_user_posts( $author->ID, 'abt_blog' );
                if ( $total_posts > $posts_count ) :
                ?>
                    <div class="abt-view-all-posts">
                        <a href="<?php echo get_author_posts_url( $author->ID ); ?>?post_type=abt_blog" class="abt-view-all-link">
                            <?php printf( __( 'View all %d publications', 'academic-bloggers-toolkit' ), $total_posts ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="abt-no-posts">
                <p><?php _e( 'No publications found.', 'academic-bloggers-toolkit' ); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Author Statistics -->
    <div class="abt-author-stats">
        <div class="abt-stats-grid">
            <div class="abt-stat-item">
                <span class="abt-stat-number"><?php echo count_user_posts( $author->ID, 'abt_blog' ); ?></span>
                <span class="abt-stat-label"><?php _e( 'Publications', 'academic-bloggers-toolkit' ); ?></span>
            </div>
            
            <?php
            // Calculate total citations for this author
            $total_citations = 0;
            $author_posts = get_posts( array(
                'author' => $author->ID,
                'post_type' => 'abt_blog',
                'posts_per_page' => -1,
                'fields' => 'ids'
            ) );
            
            foreach ( $author_posts as $post_id ) {
                $citations = get_post_meta( $post_id, '_abt_citations', true );
                if ( is_array( $citations ) ) {
                    $total_citations += count( $citations );
                }
            }
            ?>
            
            <div class="abt-stat-item">
                <span class="abt-stat-number"><?php echo $total_citations; ?></span>
                <span class="abt-stat-label"><?php _e( 'Citations', 'academic-bloggers-toolkit' ); ?></span>
            </div>
            
            <?php
            // Calculate H-index (simplified version)
            $h_index = 0;
            $citation_counts = array();
            foreach ( $author_posts as $post_id ) {
                $citations = get_post_meta( $post_id, '_abt_citations', true );
                $citation_counts[] = is_array( $citations ) ? count( $citations ) : 0;
            }
            rsort( $citation_counts );
            
            for ( $i = 0; $i < count( $citation_counts ); $i++ ) {
                if ( $citation_counts[$i] >= ( $i + 1 ) ) {
                    $h_index = $i + 1;
                }
            }
            ?>
            
            <div class="abt-stat-item">
                <span class="abt-stat-number"><?php echo $h_index; ?></span>
                <span class="abt-stat-label"><?php _e( 'H-Index', 'academic-bloggers-toolkit' ); ?></span>
            </div>
        </div>
    </div>
</div>