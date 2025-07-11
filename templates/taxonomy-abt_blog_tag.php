<?php
/**
 * Template for displaying academic blog tag archives
 *
 * @link https://github.com/navidkashani/academic-bloggers-toolkit
 * @since 1.0.0
 *
 * @package Academic_Bloggers_Toolkit
 */

get_header(); ?>

<div class="abt-taxonomy-wrap abt-tag-archive">
    <div class="abt-container">
        
        <!-- Archive Header -->
        <header class="abt-archive-header">
            <?php $term = get_queried_object(); ?>
            
            <div class="abt-breadcrumb">
                <nav aria-label="<?php _e( 'Breadcrumb', 'academic-bloggers-toolkit' ); ?>">
                    <ol class="abt-breadcrumb-list">
                        <li><a href="<?php echo home_url(); ?>"><?php _e( 'Home', 'academic-bloggers-toolkit' ); ?></a></li>
                        <li><a href="<?php echo get_post_type_archive_link( 'abt_blog' ); ?>"><?php _e( 'Academic Blog', 'academic-bloggers-toolkit' ); ?></a></li>
                        <li><span class="abt-current"><?php echo esc_html( $term->name ); ?></span></li>
                    </ol>
                </nav>
            </div>
            
            <h1 class="abt-archive-title">
                <span class="abt-tag-icon" aria-hidden="true">🏷️</span>
                <?php printf( __( 'Tagged: %s', 'academic-bloggers-toolkit' ), '<span class="abt-term-name">' . single_term_title( '', false ) . '</span>' ); ?>
            </h1>
            
            <?php if ( $term->description ) : ?>
                <div class="abt-archive-description">
                    <?php echo wp_kses_post( wpautop( $term->description ) ); ?>
                </div>
            <?php endif; ?>
            
            <!-- Archive Stats -->
            <div class="abt-archive-stats">
                <?php
                global $wp_query;
                $total_posts = $wp_query->found_posts;
                printf( 
                    _n( 
                        '%d article with this tag', 
                        '%d articles with this tag', 
                        $total_posts, 
                        'academic-bloggers-toolkit' 
                    ), 
                    number_format_i18n( $total_posts ) 
                );
                ?>
            </div>
        </header>
        
        <div class="abt-archive-content">
            <div class="abt-main-content">
                
                <!-- Filter and Sort Controls -->
                <div class="abt-archive-controls">
                    <div class="abt-filter-row">
                        <!-- Category Filter -->
                        <div class="abt-filter-group">
                            <label for="abt-category-filter"><?php _e( 'Category:', 'academic-bloggers-toolkit' ); ?></label>
                            <select id="abt-category-filter" class="abt-filter-select">
                                <option value=""><?php _e( 'All Categories', 'academic-bloggers-toolkit' ); ?></option>
                                <?php
                                $categories = get_terms( array(
                                    'taxonomy'   => 'abt_blog_category',
                                    'hide_empty' => true,
                                ) );
                                
                                foreach ( $categories as $category ) {
                                    printf(
                                        '<option value="%s">%s (%d)</option>',
                                        esc_attr( $category->slug ),
                                        esc_html( $category->name ),
                                        $category->count
                                    );
                                }
                                ?>
                            </select>
                        </div>
                        
                        <!-- Subject Filter -->
                        <div class="abt-filter-group">
                            <label for="abt-subject-filter"><?php _e( 'Subject:', 'academic-bloggers-toolkit' ); ?></label>
                            <select id="abt-subject-filter" class="abt-filter-select">
                                <option value=""><?php _e( 'All Subjects', 'academic-bloggers-toolkit' ); ?></option>
                                <?php
                                $subjects = get_terms( array(
                                    'taxonomy'   => 'abt_subject',
                                    'hide_empty' => true,
                                ) );
                                
                                foreach ( $subjects as $subject ) {
                                    printf(
                                        '<option value="%s">%s (%d)</option>',
                                        esc_attr( $subject->slug ),
                                        esc_html( $subject->name ),
                                        $subject->count
                                    );
                                }
                                ?>
                            </select>
                        </div>
                        
                        <!-- Sort Filter -->
                        <div class="abt-filter-group">
                            <label for="abt-sort-filter"><?php _e( 'Sort by:', 'academic-bloggers-toolkit' ); ?></label>
                            <select id="abt-sort-filter" class="abt-filter-select">
                                <option value="date-desc"><?php _e( 'Newest First', 'academic-bloggers-toolkit' ); ?></option>
                                <option value="date-asc"><?php _e( 'Oldest First', 'academic-bloggers-toolkit' ); ?></option>
                                <option value="title-asc"><?php _e( 'Title A-Z', 'academic-bloggers-toolkit' ); ?></option>
                                <option value="citations-desc"><?php _e( 'Most Cited', 'academic-bloggers-toolkit' ); ?></option>
                                <option value="relevance"><?php _e( 'Most Relevant', 'academic-bloggers-toolkit' ); ?></option>
                            </select>
                        </div>
                        
                        <!-- View Toggle -->
                        <div class="abt-view-toggle">
                            <button type="button" class="abt-view-btn abt-view-list active" data-view="list" title="<?php _e( 'List view', 'academic-bloggers-toolkit' ); ?>">
                                <span class="screen-reader-text"><?php _e( 'List view', 'academic-bloggers-toolkit' ); ?></span>
                                ☰
                            </button>
                            <button type="button" class="abt-view-btn abt-view-grid" data-view="grid" title="<?php _e( 'Grid view', 'academic-bloggers-toolkit' ); ?>">
                                <span class="screen-reader-text"><?php _e( 'Grid view', 'academic-bloggers-toolkit' ); ?></span>
                                ⊞
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Posts List -->
                <?php if ( have_posts() ) : ?>
                    
                    <div class="abt-posts-container abt-view-list" id="abt-posts-container">
                        
                        <?php while ( have_posts() ) : the_post(); ?>
                            
                            <article id="post-<?php the_ID(); ?>" <?php post_class( 'abt-archive-item abt-tag-item' ); ?>>
                                
                                <header class="abt-item-header">
                                    <h2 class="abt-item-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h2>
                                    
                                    <div class="abt-item-meta">
                                        <div class="abt-author-info">
                                            <?php echo get_avatar( get_the_author_meta( 'ID' ), 32 ); ?>
                                            <span class="abt-author">
                                                <a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>?post_type=abt_blog">
                                                    <?php the_author(); ?>
                                                </a>
                                            </span>
                                            
                                            <?php
                                            $affiliation = get_the_author_meta( 'affiliation' );
                                            if ( $affiliation ) :
                                            ?>
                                                <span class="abt-affiliation"><?php echo esc_html( $affiliation ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="abt-date-info">
                                            <time datetime="<?php echo get_the_date( 'c' ); ?>" class="abt-published-date">
                                                <?php echo get_the_date(); ?>
                                            </time>
                                            
                                            <?php if ( get_the_modified_date() !== get_the_date() ) : ?>
                                                <time datetime="<?php echo get_the_modified_date( 'c' ); ?>" class="abt-modified-date">
                                                    <?php printf( __( 'Updated %s', 'academic-bloggers-toolkit' ), get_the_modified_date() ); ?>
                                                </time>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </header>
                                
                                <div class="abt-item-content">
                                    <?php
                                    // Show abstract if available, otherwise excerpt
                                    $abstract = get_post_meta( get_the_ID(), '_abt_abstract', true );
                                    if ( $abstract ) {
                                        echo '<div class="abt-abstract-excerpt">';
                                        echo wp_trim_words( wp_kses_post( $abstract ), 40, '...' );
                                        echo '</div>';
                                    } else {
                                        the_excerpt();
                                    }
                                    ?>
                                </div>
                                
                                <footer class="abt-item-footer">
                                    <div class="abt-item-taxonomy">
                                        <?php
                                        // Display categories
                                        $categories = wp_get_post_terms( get_the_ID(), 'abt_blog_category' );
                                        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) :
                                        ?>
                                            <div class="abt-categories">
                                                <span class="abt-tax-label"><?php _e( 'Categories:', 'academic-bloggers-toolkit' ); ?></span>
                                                <?php foreach ( $categories as $category ) : ?>
                                                    <a href="<?php echo get_term_link( $category ); ?>" class="abt-category-link">
                                                        <?php echo esc_html( $category->name ); ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // Display subjects
                                        $subjects = wp_get_post_terms( get_the_ID(), 'abt_subject' );
                                        if ( ! empty( $subjects ) && ! is_wp_error( $subjects ) ) :
                                        ?>
                                            <div class="abt-subjects">
                                                <span class="abt-tax-label"><?php _e( 'Subjects:', 'academic-bloggers-toolkit' ); ?></span>
                                                <?php foreach ( $subjects as $subject ) : ?>
                                                    <a href="<?php echo get_term_link( $subject ); ?>" class="abt-subject-link">
                                                        <?php echo esc_html( $subject->name ); ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // Display other tags
                                        $other_tags = wp_get_post_terms( get_the_ID(), 'abt_blog_tag', array( 'exclude' => $term->term_id ) );
                                        if ( ! empty( $other_tags ) && ! is_wp_error( $other_tags ) ) :
                                        ?>
                                            <div class="abt-other-tags">
                                                <span class="abt-tax-label"><?php _e( 'Other tags:', 'academic-bloggers-toolkit' ); ?></span>
                                                <?php foreach ( array_slice( $other_tags, 0, 5 ) as $tag ) : ?>
                                                    <a href="<?php echo get_term_link( $tag ); ?>" class="abt-tag-link">
                                                        <?php echo esc_html( $tag->name ); ?>
                                                    </a>
                                                <?php endforeach; ?>
                                                <?php if ( count( $other_tags ) > 5 ) : ?>
                                                    <span class="abt-more-tags">+<?php echo count( $other_tags ) - 5; ?> <?php _e( 'more', 'academic-bloggers-toolkit' ); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="abt-item-actions">
                                        <?php
                                        // Citation count
                                        $citations = get_post_meta( get_the_ID(), '_abt_citations', true );
                                        $citation_count = is_array( $citations ) ? count( $citations ) : 0;
                                        ?>
                                        <span class="abt-citation-count">
                                            <?php printf( _n( '%d citation', '%d citations', $citation_count, 'academic-bloggers-toolkit' ), $citation_count ); ?>
                                        </span>
                                        
                                        <a href="<?php the_permalink(); ?>" class="abt-read-more">
                                            <?php _e( 'Read Full Article', 'academic-bloggers-toolkit' ); ?>
                                            <span class="abt-reading-time">
                                                <?php
                                                // Estimate reading time
                                                $content = get_post_field( 'post_content', get_the_ID() );
                                                $word_count = str_word_count( strip_tags( $content ) );
                                                $reading_time = ceil( $word_count / 200 ); // 200 words per minute
                                                printf( __( '(%d min)', 'academic-bloggers-toolkit' ), $reading_time );
                                                ?>
                                            </span>
                                        </a>
                                    </div>
                                </footer>
                                
                            </article>
                            
                        <?php endwhile; ?>
                        
                    </div>
                    
                    <!-- Pagination -->
                    <nav class="abt-pagination" role="navigation" aria-label="<?php _e( 'Posts navigation', 'academic-bloggers-toolkit' ); ?>">
                        <?php
                        echo paginate_links( array(
                            'prev_text' => __( '&laquo; Previous', 'academic-bloggers-toolkit' ),
                            'next_text' => __( 'Next &raquo;', 'academic-bloggers-toolkit' ),
                            'type'      => 'list',
                        ) );
                        ?>
                    </nav>
                    
                <?php else : ?>
                    
                    <!-- No Posts Found -->
                    <div class="abt-no-posts">
                        <h2><?php _e( 'No articles found with this tag', 'academic-bloggers-toolkit' ); ?></h2>
                        <p><?php _e( 'Sorry, there are no published articles with this tag yet.', 'academic-bloggers-toolkit' ); ?></p>
                        
                        <div class="abt-browse-alternatives">
                            <h3><?php _e( 'Explore other options:', 'academic-bloggers-toolkit' ); ?></h3>
                            <ul>
                                <li><a href="<?php echo get_post_type_archive_link( 'abt_blog' ); ?>"><?php _e( 'Browse all academic posts', 'academic-bloggers-toolkit' ); ?></a></li>
                                <li><a href="<?php echo home_url( '/?s=' . urlencode( $term->name ) . '&post_type=abt_blog' ); ?>"><?php printf( __( 'Search for "%s"', 'academic-bloggers-toolkit' ), esc_html( $term->name ) ); ?></a></li>
                            </ul>
                        </div>
                    </div>
                    
                <?php endif; ?>
                
            </div>
            
            <!-- Sidebar -->
            <aside class="abt-archive-sidebar">
                
                <!-- Tag Info Widget -->
                <div class="abt-widget abt-tag-info">
                    <h3 class="abt-widget-title"><?php _e( 'About This Tag', 'academic-bloggers-toolkit' ); ?></h3>
                    <div class="abt-tag-details">
                        <div class="abt-tag-stats">
                            <div class="abt-stat">
                                <span class="abt-stat-number"><?php echo $term->count; ?></span>
                                <span class="abt-stat-label"><?php _e( 'Articles', 'academic-bloggers-toolkit' ); ?></span>
                            </div>
                        </div>
                        
                        <?php if ( $term->description ) : ?>
                            <div class="abt-tag-description">
                                <?php echo wp_kses_post( wpautop( $term->description ) ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Related Tags -->
                <?php
                $related_tags = get_terms( array(
                    'taxonomy'   => 'abt_blog_tag',
                    'exclude'    => array( $term->term_id ),
                    'hide_empty' => true,
                    'number'     => 15,
                    'orderby'    => 'count',
                    'order'      => 'DESC'
                ) );
                
                if ( ! empty( $related_tags ) ) :
                ?>
                    <div class="abt-widget abt-related-tags">
                        <h3 class="abt-widget-title"><?php _e( 'Related Tags', 'academic-bloggers-toolkit' ); ?></h3>
                        <div class="abt-tag-cloud">
                            <?php foreach ( $related_tags as $tag ) : ?>
                                <a href="<?php echo get_term_link( $tag ); ?>" class="abt-tag-cloud-item" data-count="<?php echo $tag->count; ?>">
                                    <?php echo esc_html( $tag->name ); ?>
                                    <span class="abt-tag-count"><?php echo $tag->count; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Popular with Tag -->
                <?php
                $popular_with_tag = get_posts( array(
                    'post_type'      => 'abt_blog',
                    'posts_per_page' => 5,
                    'meta_key'       => '_abt_citation_count',
                    'orderby'        => 'meta_value_num',
                    'order'          => 'DESC',
                    'tax_query'      => array(
                        array(
                            'taxonomy' => 'abt_blog_tag',
                            'field'    => 'term_id',
                            'terms'    => $term->term_id,
                        ),
                    ),
                ) );
                
                if ( ! empty( $popular_with_tag ) ) :
                ?>
                    <div class="abt-widget abt-popular-with-tag">
                        <h3 class="abt-widget-title"><?php _e( 'Most Cited with This Tag', 'academic-bloggers-toolkit' ); ?></h3>
                        <ul class="abt-popular-list">
                            <?php foreach ( $popular_with_tag as $popular_post ) : ?>
                                <li>
                                    <a href="<?php echo get_permalink( $popular_post->ID ); ?>">
                                        <?php echo esc_html( wp_trim_words( $popular_post->post_title, 8 ) ); ?>
                                    </a>
                                    <span class="abt-citation-count">
                                        <?php
                                        $count = get_post_meta( $popular_post->ID, '_abt_citation_count', true );
                                        printf( _n( '%d citation', '%d citations', $count, 'academic-bloggers-toolkit' ), $count );
                                        ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Tag Statistics -->
                <div class="abt-widget abt-tag-statistics">
                    <h3 class="abt-widget-title"><?php _e( 'Tag Statistics', 'academic-bloggers-toolkit' ); ?></h3>
                    <div class="abt-tag-stats-grid">
                        <?php
                        // Calculate average citations for posts with this tag
                        $tag_posts = get_posts( array(
                            'post_type' => 'abt_blog',
                            'posts_per_page' => -1,
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'abt_blog_tag',
                                    'field' => 'term_id',
                                    'terms' => $term->term_id,
                                ),
                            ),
                            'fields' => 'ids'
                        ) );
                        
                        $total_citations = 0;
                        $posts_with_citations = 0;
                        
                        foreach ( $tag_posts as $post_id ) {
                            $citations = get_post_meta( $post_id, '_abt_citations', true );
                            $citation_count = is_array( $citations ) ? count( $citations ) : 0;
                            $total_citations += $citation_count;
                            if ( $citation_count > 0 ) {
                                $posts_with_citations++;
                            }
                        }
                        
                        $avg_citations = count( $tag_posts ) > 0 ? round( $total_citations / count( $tag_posts ), 1 ) : 0;
                        ?>
                        
                        <div class="abt-stat-item">
                            <span class="abt-stat-number"><?php echo $total_citations; ?></span>
                            <span class="abt-stat-label"><?php _e( 'Total Citations', 'academic-bloggers-toolkit' ); ?></span>
                        </div>
                        
                        <div class="abt-stat-item">
                            <span class="abt-stat-number"><?php echo $avg_citations; ?></span>
                            <span class="abt-stat-label"><?php _e( 'Avg Citations', 'academic-bloggers-toolkit' ); ?></span>
                        </div>
                        
                        <div class="abt-stat-item">
                            <span class="abt-stat-number"><?php echo $posts_with_citations; ?></span>
                            <span class="abt-stat-label"><?php _e( 'Cited Articles', 'academic-bloggers-toolkit' ); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Authors in Tag -->
                <?php
                $tag_authors = get_users( array(
                    'has_published_posts' => array( 'abt_blog' ),
                    'meta_query' => array(
                        array(
                            'key' => 'published_in_tag_' . $term->term_id,
                            'compare' => 'EXISTS'
                        )
                    ),
                    'number' => 5
                ) );
                
                // If meta query doesn't work, get authors manually
                if ( empty( $tag_authors ) ) {
                    $author_ids = array();
                    foreach ( $tag_posts as $post_id ) {
                        $author_id = get_post_field( 'post_author', $post_id );
                        if ( ! in_array( $author_id, $author_ids ) ) {
                            $author_ids[] = $author_id;
                        }
                    }
                    
                    if ( ! empty( $author_ids ) ) {
                        $tag_authors = get_users( array(
                            'include' => array_slice( $author_ids, 0, 5 )
                        ) );
                    }
                }
                
                if ( ! empty( $tag_authors ) ) :
                ?>
                    <div class="abt-widget abt-tag-authors">
                        <h3 class="abt-widget-title"><?php _e( 'Contributors', 'academic-bloggers-toolkit' ); ?></h3>
                        <ul class="abt-authors-list">
                            <?php foreach ( $tag_authors as $author ) : ?>
                                <li class="abt-author-item">
                                    <a href="<?php echo get_author_posts_url( $author->ID ); ?>?post_type=abt_blog" class="abt-author-link">
                                        <?php echo get_avatar( $author->ID, 32, '', '', array( 'class' => 'abt-author-avatar' ) ); ?>
                                        <span class="abt-author-name"><?php echo esc_html( $author->display_name ); ?></span>
                                        <?php
                                        $author_post_count = count_user_posts( $author->ID, 'abt_blog' );
                                        ?>
                                        <span class="abt-author-count"><?php echo $author_post_count; ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Tag RSS Feed -->
                <div class="abt-widget abt-tag-feed">
                    <h3 class="abt-widget-title"><?php _e( 'Follow This Tag', 'academic-bloggers-toolkit' ); ?></h3>
                    <div class="abt-feed-links">
                        <a href="<?php echo get_term_feed_link( $term->term_id, 'abt_blog_tag' ); ?>" class="abt-rss-link">
                            <span class="abt-rss-icon" aria-hidden="true">📡</span>
                            <?php _e( 'RSS Feed', 'academic-bloggers-toolkit' ); ?>
                        </a>
                        
                        <div class="abt-share-tag">
                            <span class="abt-share-label"><?php _e( 'Share:', 'academic-bloggers-toolkit' ); ?></span>
                            <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode( sprintf( __( 'Check out articles tagged "%s"', 'academic-bloggers-toolkit' ), $term->name ) ); ?>&url=<?php echo urlencode( get_term_link( $term ) ); ?>" target="_blank" rel="noopener" class="abt-share-twitter">
                                <?php _e( 'Twitter', 'academic-bloggers-toolkit' ); ?>
                            </a>
                        </div>
                    </div>
                </div>
                
            </aside>
            
        </div>
    </div>
</div>

<?php get_footer(); ?>