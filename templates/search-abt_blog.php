<?php
/**
 * Template for displaying academic blog search results
 *
 * @link https://github.com/navidkashani/academic-bloggers-toolkit
 * @since 1.0.0
 *
 * @package Academic_Bloggers_Toolkit
 */

get_header(); ?>

<div class="abt-search-wrap">
    <div class="abt-container">
        
        <!-- Search Header -->
        <header class="abt-search-header">
            <div class="abt-breadcrumb">
                <nav aria-label="<?php _e( 'Breadcrumb', 'academic-bloggers-toolkit' ); ?>">
                    <ol class="abt-breadcrumb-list">
                        <li><a href="<?php echo home_url(); ?>"><?php _e( 'Home', 'academic-bloggers-toolkit' ); ?></a></li>
                        <li><a href="<?php echo get_post_type_archive_link( 'abt_blog' ); ?>"><?php _e( 'Academic Blog', 'academic-bloggers-toolkit' ); ?></a></li>
                        <li><span class="abt-current"><?php _e( 'Search Results', 'academic-bloggers-toolkit' ); ?></span></li>
                    </ol>
                </nav>
            </div>
            
            <h1 class="abt-search-title">
                <span class="abt-search-icon" aria-hidden="true">🔍</span>
                <?php if ( get_search_query() ) : ?>
                    <?php printf( __( 'Search Results for: "%s"', 'academic-bloggers-toolkit' ), '<span class="abt-search-term">' . get_search_query() . '</span>' ); ?>
                <?php else : ?>
                    <?php _e( 'Academic Research Search', 'academic-bloggers-toolkit' ); ?>
                <?php endif; ?>
            </h1>
            
            <!-- Search Stats -->
            <div class="abt-search-stats">
                <?php
                global $wp_query;
                $total_results = $wp_query->found_posts;
                $search_query = get_search_query();
                
                if ( $search_query ) {
                    printf( 
                        _n( 
                            'Found %d research article matching your search', 
                            'Found %d research articles matching your search', 
                            $total_results, 
                            'academic-bloggers-toolkit' 
                        ), 
                        number_format_i18n( $total_results ) 
                    );
                } else {
                    _e( 'Use the search form below to find academic research articles', 'academic-bloggers-toolkit' );
                }
                ?>
            </div>
        </header>
        
        <div class="abt-search-content">
            <div class="abt-main-content">
                
                <!-- Enhanced Search Form -->
                <div class="abt-search-form-container">
                    <?php echo do_shortcode( '[abt_search_form placeholder="' . esc_attr__( 'Search academic research...', 'academic-bloggers-toolkit' ) . '" button_text="' . esc_attr__( 'Search', 'academic-bloggers-toolkit' ) . '"]' ); ?>
                </div>
                
                <!-- Search Filters -->
                <div class="abt-search-filters">
                    <div class="abt-filter-row">
                        <!-- Subject Filter -->
                        <div class="abt-filter-group">
                            <label for="abt-subject-filter"><?php _e( 'Subject:', 'academic-bloggers-toolkit' ); ?></label>
                            <select id="abt-subject-filter" class="abt-filter-select">
                                <option value=""><?php _e( 'All Subjects', 'academic-bloggers-toolkit' ); ?></option>
                                <?php
                                $subjects = get_terms( array(
                                    'taxonomy'   => 'abt_subject',
                                    'hide_empty' => true,
                                    'orderby'    => 'name',
                                ) );
                                
                                $current_subject = get_query_var( 'abt_subject', '' );
                                
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
                                    'orderby'    => 'name',
                                ) );
                                
                                $current_category = get_query_var( 'abt_category', '' );
                                
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
                        
                        <!-- Date Range -->
                        <div class="abt-filter-group">
                            <label for="abt-date-from"><?php _e( 'From:', 'academic-bloggers-toolkit' ); ?></label>
                            <input type="date" id="abt-date-from" name="abt_date_from" class="abt-date-input" value="<?php echo esc_attr( get_query_var( 'abt_date_from', '' ) ); ?>">
                        </div>
                        
                        <div class="abt-filter-group">
                            <label for="abt-date-to"><?php _e( 'To:', 'academic-bloggers-toolkit' ); ?></label>
                            <input type="date" id="abt-date-to" name="abt_date_to" class="abt-date-input" value="<?php echo esc_attr( get_query_var( 'abt_date_to', '' ) ); ?>">
                        </div>
                        
                        <!-- Sort Options -->
                        <div class="abt-filter-group">
                            <label for="abt-sort-filter"><?php _e( 'Sort by:', 'academic-bloggers-toolkit' ); ?></label>
                            <select id="abt-sort-filter" class="abt-filter-select">
                                <option value="relevance"><?php _e( 'Relevance', 'academic-bloggers-toolkit' ); ?></option>
                                <option value="date-desc"><?php _e( 'Newest First', 'academic-bloggers-toolkit' ); ?></option>
                                <option value="date-asc"><?php _e( 'Oldest First', 'academic-bloggers-toolkit' ); ?></option>
                                <option value="citations-desc"><?php _e( 'Most Cited', 'academic-bloggers-toolkit' ); ?></option>
                                <option value="title-asc"><?php _e( 'Title A-Z', 'academic-bloggers-toolkit' ); ?></option>
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
                    
                    <div class="abt-filter-actions">
                        <button type="button" class="abt-apply-filters">
                            <?php _e( 'Apply Filters', 'academic-bloggers-toolkit' ); ?>
                        </button>
                        <button type="button" class="abt-clear-filters">
                            <?php _e( 'Clear All', 'academic-bloggers-toolkit' ); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Search Results -->
                <?php if ( have_posts() && get_search_query() ) : ?>
                    
                    <!-- Search Suggestions -->
                    <?php if ( $total_results > 0 ) : ?>
                        <div class="abt-search-suggestions">
                            <h2 class="abt-suggestions-title"><?php _e( 'Search Suggestions', 'academic-bloggers-toolkit' ); ?></h2>
                            <div class="abt-suggestions-list">
                                <?php
                                // Generate search suggestions based on current query
                                $search_term = get_search_query();
                                $related_terms = array();
                                
                                // Get related subjects
                                $related_subjects = get_terms( array(
                                    'taxonomy' => 'abt_subject',
                                    'name__like' => $search_term,
                                    'number' => 3
                                ) );
                                
                                foreach ( $related_subjects as $subject ) {
                                    $related_terms[] = array(
                                        'term' => $subject->name,
                                        'url' => get_term_link( $subject ),
                                        'type' => 'subject'
                                    );
                                }
                                
                                // Get related tags
                                $related_tags = get_terms( array(
                                    'taxonomy' => 'abt_blog_tag',
                                    'name__like' => $search_term,
                                    'number' => 3
                                ) );
                                
                                foreach ( $related_tags as $tag ) {
                                    $related_terms[] = array(
                                        'term' => $tag->name,
                                        'url' => get_term_link( $tag ),
                                        'type' => 'tag'
                                    );
                                }
                                
                                if ( ! empty( $related_terms ) ) :
                                ?>
                                    <p><?php _e( 'You might also be interested in:', 'academic-bloggers-toolkit' ); ?></p>
                                    <ul class="abt-related-terms">
                                        <?php foreach ( $related_terms as $term_data ) : ?>
                                            <li>
                                                <a href="<?php echo esc_url( $term_data['url'] ); ?>" class="abt-related-term abt-term-<?php echo esc_attr( $term_data['type'] ); ?>">
                                                    <?php echo esc_html( $term_data['term'] ); ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="abt-search-results">
                        <h2 class="abt-results-title screen-reader-text"><?php _e( 'Search Results', 'academic-bloggers-toolkit' ); ?></h2>
                        
                        <div class="abt-posts-container abt-view-list" id="abt-search-results-container">
                            
                            <?php while ( have_posts() ) : the_post(); ?>
                                
                                <article id="post-<?php the_ID(); ?>" <?php post_class( 'abt-search-item abt-result-item' ); ?>>
                                    
                                    <header class="abt-item-header">
                                        <h3 class="abt-item-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                        
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
                                            
                                            <div class="abt-search-metrics">
                                                <?php
                                                // Citation count
                                                $citations = get_post_meta( get_the_ID(), '_abt_citations', true );
                                                $citation_count = is_array( $citations ) ? count( $citations ) : 0;
                                                ?>
                                                <span class="abt-metric-item abt-citations">
                                                    <span class="abt-metric-icon" aria-hidden="true">📖</span>
                                                    <?php printf( _n( '%d citation', '%d citations', $citation_count, 'academic-bloggers-toolkit' ), $citation_count ); ?>
                                                </span>
                                                
                                                <?php
                                                // Reading time
                                                $content = get_post_field( 'post_content', get_the_ID() );
                                                $word_count = str_word_count( strip_tags( $content ) );
                                                $reading_time = ceil( $word_count / 200 );
                                                ?>
                                                <span class="abt-metric-item abt-reading-time">
                                                    <span class="abt-metric-icon" aria-hidden="true">⏱️</span>
                                                    <?php printf( __( '%d min', 'academic-bloggers-toolkit' ), $reading_time ); ?>
                                                </span>
                                                
                                                <?php
                                                // Search relevance score (simplified)
                                                $relevance_score = 85; // This would be calculated based on search algorithm
                                                ?>
                                                <span class="abt-metric-item abt-relevance">
                                                    <span class="abt-metric-icon" aria-hidden="true">🎯</span>
                                                    <?php printf( __( '%d%% match', 'academic-bloggers-toolkit' ), $relevance_score ); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </header>
                                    
                                    <div class="abt-item-content">
                                        <?php
                                        // Show highlighted search excerpt
                                        $search_query = get_search_query();
                                        $excerpt = get_the_excerpt();
                                        $abstract = get_post_meta( get_the_ID(), '_abt_abstract', true );
                                        
                                        if ( $abstract ) {
                                            $content_to_search = $abstract;
                                            $content_label = '<strong>' . __( 'Abstract:', 'academic-bloggers-toolkit' ) . '</strong> ';
                                        } else {
                                            $content_to_search = $excerpt;
                                            $content_label = '';
                                        }
                                        
                                        // Highlight search terms
                                        if ( $search_query ) {
                                            $highlighted = preg_replace(
                                                '/(' . preg_quote( $search_query, '/' ) . ')/i',
                                                '<mark class="abt-search-highlight">$1</mark>',
                                                $content_to_search
                                            );
                                        } else {
                                            $highlighted = $content_to_search;
                                        }
                                        
                                        echo '<div class="abt-search-excerpt">';
                                        echo $content_label . wp_kses_post( wp_trim_words( $highlighted, 40, '...' ) );
                                        echo '</div>';
                                        
                                        // Display keywords if they match search
                                        $keywords = get_post_meta( get_the_ID(), '_abt_keywords', true );
                                        if ( $keywords && $search_query && stripos( $keywords, $search_query ) !== false ) :
                                        ?>
                                            <div class="abt-matching-keywords">
                                                <strong><?php _e( 'Matching keywords:', 'academic-bloggers-toolkit' ); ?></strong>
                                                <span class="abt-keywords-list">
                                                    <?php
                                                    echo preg_replace(
                                                        '/(' . preg_quote( $search_query, '/' ) . ')/i',
                                                        '<mark class="abt-search-highlight">$1</mark>',
                                                        esc_html( $keywords )
                                                    );
                                                    ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
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
                                            <a href="<?php the_permalink(); ?>" class="abt-read-more">
                                                <?php _e( 'Read Full Article', 'academic-bloggers-toolkit' ); ?>
                                            </a>
                                            
                                            <div class="abt-quick-actions">
                                                <button type="button" class="abt-save-article" data-post-id="<?php the_ID(); ?>" title="<?php _e( 'Save for later', 'academic-bloggers-toolkit' ); ?>">
                                                    <span class="abt-save-icon" aria-hidden="true">🔖</span>
                                                    <span class="screen-reader-text"><?php _e( 'Save article', 'academic-bloggers-toolkit' ); ?></span>
                                                </button>
                                                
                                                <button type="button" class="abt-cite-article" data-post-id="<?php the_ID(); ?>" title="<?php _e( 'Get citation', 'academic-bloggers-toolkit' ); ?>">
                                                    <span class="abt-cite-icon" aria-hidden="true">📝</span>
                                                    <span class="screen-reader-text"><?php _e( 'Cite article', 'academic-bloggers-toolkit' ); ?></span>
                                                </button>
                                                
                                                <button type="button" class="abt-share-article" data-post-id="<?php the_ID(); ?>" title="<?php _e( 'Share article', 'academic-bloggers-toolkit' ); ?>">
                                                    <span class="abt-share-icon" aria-hidden="true">↗️</span>
                                                    <span class="screen-reader-text"><?php _e( 'Share article', 'academic-bloggers-toolkit' ); ?></span>
                                                </button>
                                            </div>
                                        </div>
                                    </footer>
                                    
                                </article>
                                
                            <?php endwhile; ?>
                            
                        </div>
                        
                        <!-- Pagination -->
                        <nav class="abt-pagination" role="navigation" aria-label="<?php _e( 'Search results navigation', 'academic-bloggers-toolkit' ); ?>">
                            <?php
                            $big = 999999999; // Need an unlikely integer
                            echo paginate_links( array(
                                'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                                'format'    => '?paged=%#%',
                                'current'   => max( 1, get_query_var( 'paged' ) ),
                                'total'     => $wp_query->max_num_pages,
                                'prev_text' => __( '&laquo; Previous', 'academic-bloggers-toolkit' ),
                                'next_text' => __( 'Next &raquo;', 'academic-bloggers-toolkit' ),
                                'type'      => 'list',
                            ) );
                            ?>
                        </nav>
                        
                    </div>
                    
                <?php elseif ( get_search_query() ) : ?>
                    
                    <!-- No Results Found -->
                    <div class="abt-no-results">
                        <h2><?php _e( 'No research articles found', 'academic-bloggers-toolkit' ); ?></h2>
                        <p><?php printf( __( 'Sorry, no academic articles match your search for "%s".', 'academic-bloggers-toolkit' ), '<strong>' . get_search_query() . '</strong>' ); ?></p>
                        
                        <div class="abt-search-suggestions">
                            <h3><?php _e( 'Search suggestions:', 'academic-bloggers-toolkit' ); ?></h3>
                            <ul>
                                <li><?php _e( 'Check your spelling', 'academic-bloggers-toolkit' ); ?></li>
                                <li><?php _e( 'Try different or more general keywords', 'academic-bloggers-toolkit' ); ?></li>
                                <li><?php _e( 'Use fewer keywords', 'academic-bloggers-toolkit' ); ?></li>
                                <li><?php _e( 'Try browsing by subject or category', 'academic-bloggers-toolkit' ); ?></li>
                            </ul>
                        </div>
                        
                        <!-- Alternative Search Options -->
                        <div class="abt-alternative-searches">
                            <h3><?php _e( 'Alternative search options:', 'academic-bloggers-toolkit' ); ?></h3>
                            
                            <!-- Quick Subject Links -->
                            <?php
                            $popular_subjects = get_terms( array(
                                'taxonomy'   => 'abt_subject',
                                'hide_empty' => true,
                                'number'     => 6,
                                'orderby'    => 'count',
                                'order'      => 'DESC'
                            ) );
                            
                            if ( ! empty( $popular_subjects ) ) :
                            ?>
                                <div class="abt-popular-subjects">
                                    <h4><?php _e( 'Popular research subjects:', 'academic-bloggers-toolkit' ); ?></h4>
                                    <div class="abt-subject-links">
                                        <?php foreach ( $popular_subjects as $subject ) : ?>
                                            <a href="<?php echo get_term_link( $subject ); ?>" class="abt-subject-pill">
                                                <?php echo esc_html( $subject->name ); ?>
                                                <span class="abt-subject-count"><?php echo $subject->count; ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Recent Articles -->
                            <?php
                            $recent_articles = get_posts( array(
                                'post_type'      => 'abt_blog',
                                'posts_per_page' => 5,
                                'post_status'    => 'publish',
                                'orderby'        => 'date',
                                'order'          => 'DESC'
                            ) );
                            
                            if ( ! empty( $recent_articles ) ) :
                            ?>
                                <div class="abt-recent-articles">
                                    <h4><?php _e( 'Recent research articles:', 'academic-bloggers-toolkit' ); ?></h4>
                                    <ul class="abt-recent-list">
                                        <?php foreach ( $recent_articles as $article ) : ?>
                                            <li>
                                                <a href="<?php echo get_permalink( $article->ID ); ?>">
                                                    <?php echo esc_html( wp_trim_words( $article->post_title, 12 ) ); ?>
                                                </a>
                                                <span class="abt-article-date"><?php echo get_the_date( '', $article->ID ); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Browse All -->
                            <div class="abt-browse-all">
                                <a href="<?php echo get_post_type_archive_link( 'abt_blog' ); ?>" class="abt-button abt-button-primary">
                                    <?php _e( 'Browse All Research Articles', 'academic-bloggers-toolkit' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                <?php else : ?>
                    
                    <!-- Search Landing -->
                    <div class="abt-search-landing">
                        <h2><?php _e( 'Search Academic Research', 'academic-bloggers-toolkit' ); ?></h2>
                        <p><?php _e( 'Discover academic articles, research papers, and scholarly content using our advanced search tools.', 'academic-bloggers-toolkit' ); ?></p>
                        
                        <!-- Search Features -->
                        <div class="abt-search-features">
                            <div class="abt-feature-grid">
                                <div class="abt-feature-item">
                                    <div class="abt-feature-icon" aria-hidden="true">🔍</div>
                                    <h3><?php _e( 'Advanced Search', 'academic-bloggers-toolkit' ); ?></h3>
                                    <p><?php _e( 'Search across titles, abstracts, content, and keywords with powerful filtering options.', 'academic-bloggers-toolkit' ); ?></p>
                                </div>
                                
                                <div class="abt-feature-item">
                                    <div class="abt-feature-icon" aria-hidden="true">🎓</div>
                                    <h3><?php _e( 'Subject Areas', 'academic-bloggers-toolkit' ); ?></h3>
                                    <p><?php _e( 'Browse research by academic discipline and subject area classifications.', 'academic-bloggers-toolkit' ); ?></p>
                                </div>
                                
                                <div class="abt-feature-item">
                                    <div class="abt-feature-icon" aria-hidden="true">📊</div>
                                    <h3><?php _e( 'Citation Metrics', 'academic-bloggers-toolkit' ); ?></h3>
                                    <p><?php _e( 'Sort and filter by citation counts and research impact metrics.', 'academic-bloggers-toolkit' ); ?></p>
                                </div>
                                
                                <div class="abt-feature-item">
                                    <div class="abt-feature-icon" aria-hidden="true">👥</div>
                                    <h3><?php _e( 'Author Search', 'academic-bloggers-toolkit' ); ?></h3>
                                    <p><?php _e( 'Find research by specific authors and academic contributors.', 'academic-bloggers-toolkit' ); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div class="abt-search-stats">
                            <div class="abt-stats-grid">
                                <?php
                                $total_articles = wp_count_posts( 'abt_blog' )->publish;
                                $total_authors = count( get_users( array( 'has_published_posts' => array( 'abt_blog' ) ) ) );
                                $total_subjects = wp_count_terms( 'abt_subject' );
                                ?>
                                
                                <div class="abt-stat-item">
                                    <span class="abt-stat-number"><?php echo number_format( $total_articles ); ?></span>
                                    <span class="abt-stat-label"><?php _e( 'Research Articles', 'academic-bloggers-toolkit' ); ?></span>
                                </div>
                                
                                <div class="abt-stat-item">
                                    <span class="abt-stat-number"><?php echo number_format( $total_authors ); ?></span>
                                    <span class="abt-stat-label"><?php _e( 'Researchers', 'academic-bloggers-toolkit' ); ?></span>
                                </div>
                                
                                <div class="abt-stat-item">
                                    <span class="abt-stat-number"><?php echo number_format( $total_subjects ); ?></span>
                                    <span class="abt-stat-label"><?php _e( 'Subject Areas', 'academic-bloggers-toolkit' ); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php endif; ?>
                
            </div>
            
            <!-- Sidebar -->
            <aside class="abt-search-sidebar">
                
                <!-- Search Tips -->
                <div class="abt-widget abt-search-tips">
                    <h3 class="abt-widget-title"><?php _e( 'Search Tips', 'academic-bloggers-toolkit' ); ?></h3>
                    <div class="abt-tips-content">
                        <ul class="abt-tips-list">
                            <li>
                                <strong><?php _e( 'Use quotes', 'academic-bloggers-toolkit' ); ?></strong>
                                <?php _e( 'for exact phrases: "machine learning"', 'academic-bloggers-toolkit' ); ?>
                            </li>
                            <li>
                                <strong><?php _e( 'Combine terms', 'academic-bloggers-toolkit' ); ?></strong>
                                <?php _e( 'with AND/OR: climate AND change', 'academic-bloggers-toolkit' ); ?>
                            </li>
                            <li>
                                <strong><?php _e( 'Use wildcards', 'academic-bloggers-toolkit' ); ?></strong>
                                <?php _e( 'with *: psycholog* finds psychology, psychological', 'academic-bloggers-toolkit' ); ?>
                            </li>
                            <li>
                                <strong><?php _e( 'Filter results', 'academic-bloggers-toolkit' ); ?></strong>
                                <?php _e( 'by date, subject, or author', 'academic-bloggers-toolkit' ); ?>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Popular Searches -->
                <?php
                // This would typically come from a search analytics system
                $popular_searches = array(
                    'artificial intelligence',
                    'climate change',
                    'machine learning',
                    'quantum computing',
                    'biomedical engineering',
                    'sustainable development'
                );
                ?>
                <div class="abt-widget abt-popular-searches">
                    <h3 class="abt-widget-title"><?php _e( 'Popular Searches', 'academic-bloggers-toolkit' ); ?></h3>
                    <ul class="abt-popular-list">
                        <?php foreach ( $popular_searches as $search_term ) : ?>
                            <li>
                                <a href="<?php echo home_url( '/?s=' . urlencode( $search_term ) . '&post_type=abt_blog' ); ?>">
                                    <?php echo esc_html( $search_term ); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Browse by Subject -->
                <?php
                $subjects = get_terms( array(
                    'taxonomy'   => 'abt_subject',
                    'hide_empty' => true,
                    'number'     => 10,
                    'orderby'    => 'count',
                    'order'      => 'DESC'
                ) );
                
                if ( ! empty( $subjects ) ) :
                ?>
                    <div class="abt-widget abt-browse-subjects">
                        <h3 class="abt-widget-title"><?php _e( 'Browse by Subject', 'academic-bloggers-toolkit' ); ?></h3>
                        <ul class="abt-subject-list">
                            <?php foreach ( $subjects as $subject ) : ?>
                                <li>
                                    <a href="<?php echo get_term_link( $subject ); ?>">
                                        <?php echo esc_html( $subject->name ); ?>
                                        <span class="abt-subject-count">(<?php echo $subject->count; ?>)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="abt-view-all-subjects">
                            <a href="<?php echo get_post_type_archive_link( 'abt_blog' ); ?>"><?php _e( 'View all subjects →', 'academic-bloggers-toolkit' ); ?></a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Recent Activity -->
                <div class="abt-widget abt-recent-activity">
                    <h3 class="abt-widget-title"><?php _e( 'Recent Activity', 'academic-bloggers-toolkit' ); ?></h3>
                    <?php
                    $recent_posts = get_posts( array(
                        'post_type'      => 'abt_blog',
                        'posts_per_page' => 3,
                        'post_status'    => 'publish',
                        'orderby'        => 'date',
                        'order'          => 'DESC'
                    ) );
                    
                    if ( ! empty( $recent_posts ) ) :
                    ?>
                        <ul class="abt-recent-activity-list">
                            <?php foreach ( $recent_posts as $post ) : ?>
                                <li class="abt-activity-item">
                                    <div class="abt-activity-content">
                                        <a href="<?php echo get_permalink( $post->ID ); ?>" class="abt-activity-title">
                                            <?php echo esc_html( wp_trim_words( $post->post_title, 8 ) ); ?>
                                        </a>
                                        <div class="abt-activity-meta">
                                            <span class="abt-activity-author"><?php echo get_the_author_meta( 'display_name', $post->post_author ); ?></span>
                                            <span class="abt-activity-date"><?php echo human_time_diff( strtotime( $post->post_date ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'academic-bloggers-toolkit' ); ?></span>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <!-- Export Options -->
                <div class="abt-widget abt-export-options">
                    <h3 class="abt-widget-title"><?php _e( 'Export Results', 'academic-bloggers-toolkit' ); ?></h3>
                    <div class="abt-export-buttons">
                        <button type="button" class="abt-export-btn" data-format="bibtex" data-search="<?php echo esc_attr( get_search_query() ); ?>">
                            <span class="abt-export-icon" aria-hidden="true">📄</span>
                            <?php _e( 'BibTeX', 'academic-bloggers-toolkit' ); ?>
                        </button>
                        <button type="button" class="abt-export-btn" data-format="ris" data-search="<?php echo esc_attr( get_search_query() ); ?>">
                            <span class="abt-export-icon" aria-hidden="true">📋</span>
                            <?php _e( 'RIS', 'academic-bloggers-toolkit' ); ?>
                        </button>
                        <button type="button" class="abt-export-btn" data-format="csv" data-search="<?php echo esc_attr( get_search_query() ); ?>">
                            <span class="abt-export-icon" aria-hidden="true">📊</span>
                            <?php _e( 'CSV', 'academic-bloggers-toolkit' ); ?>
                        </button>
                    </div>
                    <p class="abt-export-description">
                        <?php _e( 'Export search results for use in reference managers and spreadsheets.', 'academic-bloggers-toolkit' ); ?>
                    </p>
                </div>
                
            </aside>
            
        </div>
    </div>
</div>

<?php get_footer(); ?>