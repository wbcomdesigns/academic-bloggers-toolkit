<?php
/**
 * Template for displaying academic blog archives
 *
 * @link https://github.com/navidkashani/academic-bloggers-toolkit
 * @since 1.0.0
 *
 * @package Academic_Bloggers_Toolkit
 */

get_header(); ?>

<div class="abt-archive-wrap">
    <div class="abt-container">
        
        <!-- Archive Header -->
        <header class="abt-archive-header">
            <h1 class="abt-archive-title">
                <?php
                if ( is_post_type_archive( 'abt_blog' ) ) {
                    _e( 'Academic Blog', 'academic-bloggers-toolkit' );
                } elseif ( is_tax( 'abt_blog_category' ) ) {
                    printf( __( 'Category: %s', 'academic-bloggers-toolkit' ), single_term_title( '', false ) );
                } elseif ( is_tax( 'abt_blog_tag' ) ) {
                    printf( __( 'Tag: %s', 'academic-bloggers-toolkit' ), single_term_title( '', false ) );
                } elseif ( is_tax( 'abt_subject' ) ) {
                    printf( __( 'Subject: %s', 'academic-bloggers-toolkit' ), single_term_title( '', false ) );
                } elseif ( is_author() ) {
                    printf( __( 'Author: %s', 'academic-bloggers-toolkit' ), get_the_author() );
                } elseif ( is_date() ) {
                    if ( is_year() ) {
                        printf( __( 'Year: %s', 'academic-bloggers-toolkit' ), get_the_date( 'Y' ) );
                    } elseif ( is_month() ) {
                        printf( __( 'Month: %s', 'academic-bloggers-toolkit' ), get_the_date( 'F Y' ) );
                    } elseif ( is_day() ) {
                        printf( __( 'Day: %s', 'academic-bloggers-toolkit' ), get_the_date() );
                    }
                }
                ?>
            </h1>
            
            <?php
            // Display term description if available
            if ( is_tax() ) {
                $term_description = term_description();
                if ( $term_description ) {
                    echo '<div class="abt-archive-description">' . wp_kses_post( $term_description ) . '</div>';
                }
            }
            ?>
            
            <!-- Archive Stats -->
            <div class="abt-archive-stats">
                <?php
                global $wp_query;
                $total_posts = $wp_query->found_posts;
                printf( 
                    _n( 
                        '%d article found', 
                        '%d articles found', 
                        $total_posts, 
                        'academic-bloggers-toolkit' 
                    ), 
                    number_format_i18n( $total_posts ) 
                );
                ?>
            </div>
        </header>
        
        <!-- Filter and Search Bar -->
        <div class="abt-archive-controls">
            <div class="abt-archive-search">
                <?php echo do_shortcode( '[abt_search_form]' ); ?>
            </div>
            
            <div class="abt-archive-filters">
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
                        
                        $current_subject = is_tax( 'abt_subject' ) ? get_queried_object()->slug : '';
                        
                        foreach ( $subjects as $subject ) {
                            printf(
                                '<option value="%s" %s>%s (%d)</option>',
                                esc_attr( $subject->slug ),
                                selected( $current_subject, $subject->slug, false ),
                                esc_html( $subject->name ),
                                $subject->count
                            );
                        }
                        ?>
                    </select>
                </div>
                
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
                        
                        $current_category = is_tax( 'abt_blog_category' ) ? get_queried_object()->slug : '';
                        
                        foreach ( $categories as $category ) {
                            printf(
                                '<option value="%s" %s>%s (%d)</option>',
                                esc_attr( $category->slug ),
                                selected( $current_category, $category->slug, false ),
                                esc_html( $category->name ),
                                $category->count
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
                        <option value="title-desc"><?php _e( 'Title Z-A', 'academic-bloggers-toolkit' ); ?></option>
                        <option value="author-asc"><?php _e( 'Author A-Z', 'academic-bloggers-toolkit' ); ?></option>
                    </select>
                </div>
                
                <!-- View Toggle -->
                <div class="abt-view-toggle">
                    <button type="button" class="abt-view-btn abt-view-list active" data-view="list">
                        <span class="screen-reader-text"><?php _e( 'List view', 'academic-bloggers-toolkit' ); ?></span>
                        ☰
                    </button>
                    <button type="button" class="abt-view-btn abt-view-grid" data-view="grid">
                        <span class="screen-reader-text"><?php _e( 'Grid view', 'academic-bloggers-toolkit' ); ?></span>
                        ⊞
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Archive Content -->
        <main class="abt-archive-content">
            
            <?php if ( have_posts() ) : ?>
                
                <div class="abt-posts-container abt-view-list" id="abt-posts-container">
                    
                    <?php while ( have_posts() ) : the_post(); ?>
                        
                        <article id="post-<?php the_ID(); ?>" <?php post_class( 'abt-archive-item' ); ?>>
                            
                            <header class="abt-item-header">
                                <h2 class="abt-item-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                                
                                <div class="abt-item-meta">
                                    <div class="abt-author-info">
                                        <span class="abt-author">
                                            <a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>">
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
                                            printf( __( '(%d min read)', 'academic-bloggers-toolkit' ), $reading_time );
                                            ?>
                                        </span>
                                    </a>
                                </div>
                            </footer>
                            
                        </article>
                        
                    <?php endwhile; ?>
                    
                </div>
                
                <!-- Pagination -->
                <nav class="abt-pagination" role="navigation">
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
                    <h2><?php _e( 'No articles found', 'academic-bloggers-toolkit' ); ?></h2>
                    <p><?php _e( 'Sorry, no academic articles match your criteria. Please try adjusting your search or filters.', 'academic-bloggers-toolkit' ); ?></p>
                    
                    <!-- Suggestions -->
                    <div class="abt-suggestions">
                        <h3><?php _e( 'Suggestions:', 'academic-bloggers-toolkit' ); ?></h3>
                        <ul>
                            <li><?php _e( 'Check your spelling', 'academic-bloggers-toolkit' ); ?></li>
                            <li><?php _e( 'Try different keywords', 'academic-bloggers-toolkit' ); ?></li>
                            <li><?php _e( 'Use broader search terms', 'academic-bloggers-toolkit' ); ?></li>
                            <li><?php _e( 'Remove some filters', 'academic-bloggers-toolkit' ); ?></li>
                        </ul>
                    </div>
                    
                    <div class="abt-browse-all">
                        <a href="<?php echo get_post_type_archive_link( 'abt_blog' ); ?>" class="abt-button">
                            <?php _e( 'Browse All Articles', 'academic-bloggers-toolkit' ); ?>
                        </a>
                    </div>
                </div>
                
            <?php endif; ?>
            
        </main>
        
        <!-- Sidebar -->
        <aside class="abt-archive-sidebar">
            
            <!-- Popular Articles Widget -->
            <div class="abt-widget abt-popular-articles">
                <h3 class="abt-widget-title"><?php _e( 'Most Cited Articles', 'academic-bloggers-toolkit' ); ?></h3>
                <?php
                // Get most cited articles
                $popular_posts = get_posts( array(
                    'post_type'      => 'abt_blog',
                    'posts_per_page' => 5,
                    'meta_key'       => '_abt_citation_count',
                    'orderby'        => 'meta_value_num',
                    'order'          => 'DESC',
                    'post_status'    => 'publish',
                ) );
                
                if ( ! empty( $popular_posts ) ) :
                ?>
                    <ul class="abt-popular-list">
                        <?php foreach ( $popular_posts as $popular_post ) : ?>
                            <li>
                                <a href="<?php echo get_permalink( $popular_post->ID ); ?>">
                                    <?php echo esc_html( $popular_post->post_title ); ?>
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
                <?php endif; ?>
            </div>
            
            <!-- Subject Cloud -->
            <div class="abt-widget abt-subject-cloud">
                <h3 class="abt-widget-title"><?php _e( 'Research Areas', 'academic-bloggers-toolkit' ); ?></h3>
                <?php echo do_shortcode( '[abt_subject_filter show_count="true"]' ); ?>
            </div>
            
            <!-- Recent Articles -->
            <div class="abt-widget abt-recent-articles">
                <h3 class="abt-widget-title"><?php _e( 'Recent Articles', 'academic-bloggers-toolkit' ); ?></h3>
                <?php echo do_shortcode( '[abt_recent_posts posts_per_page="5" show_meta="true"]' ); ?>
            </div>
            
            <!-- Statistics -->
            <div class="abt-widget abt-stats">
                <h3 class="abt-widget-title"><?php _e( 'Statistics', 'academic-bloggers-toolkit' ); ?></h3>
                <?php echo do_shortcode( '[abt_citation_stats]' ); ?>
            </div>
            
        </aside>
        
    </div>
</div>

<?php get_footer(); ?>