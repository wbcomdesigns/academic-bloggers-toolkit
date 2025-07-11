<?php
/**
 * Template for displaying single academic blog posts
 *
 * @link https://github.com/navidkashani/academic-bloggers-toolkit
 * @since 1.0.0
 *
 * @package Academic_Bloggers_Toolkit
 */

get_header(); ?>

<div class="abt-single-wrap">
    <div class="abt-container">
        
        <?php while ( have_posts() ) : the_post(); ?>
            
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'abt-academic-article' ); ?>>
                
                <!-- Article Header -->
                <header class="abt-article-header">
                    <div class="abt-article-meta-top">
                        <?php
                        // Get subjects
                        $subjects = wp_get_post_terms( get_the_ID(), 'abt_subject' );
                        if ( ! empty( $subjects ) && ! is_wp_error( $subjects ) ) :
                        ?>
                            <div class="abt-subjects">
                                <?php foreach ( $subjects as $subject ) : ?>
                                    <a href="<?php echo get_term_link( $subject ); ?>" class="abt-subject-link">
                                        <?php echo esc_html( $subject->name ); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="abt-article-title"><?php the_title(); ?></h1>
                    
                    <?php
                    // Display abstract if available
                    $abstract = get_post_meta( get_the_ID(), '_abt_abstract', true );
                    if ( $abstract ) :
                    ?>
                        <div class="abt-abstract">
                            <h2 class="abt-abstract-title"><?php _e( 'Abstract', 'academic-bloggers-toolkit' ); ?></h2>
                            <div class="abt-abstract-content">
                                <?php echo wp_kses_post( wpautop( $abstract ) ); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="abt-article-meta">
                        <div class="abt-author-info">
                            <div class="abt-author-avatar">
                                <?php echo get_avatar( get_the_author_meta( 'ID' ), 48 ); ?>
                            </div>
                            <div class="abt-author-details">
                                <span class="abt-author-name">
                                    <a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>">
                                        <?php the_author(); ?>
                                    </a>
                                </span>
                                <div class="abt-author-affiliation">
                                    <?php
                                    $affiliation = get_the_author_meta( 'affiliation' );
                                    if ( $affiliation ) {
                                        echo esc_html( $affiliation );
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="abt-publication-info">
                            <time class="abt-published-date" datetime="<?php echo get_the_date( 'c' ); ?>">
                                <?php printf( __( 'Published %s', 'academic-bloggers-toolkit' ), get_the_date() ); ?>
                            </time>
                            
                            <?php if ( get_the_modified_date() !== get_the_date() ) : ?>
                                <time class="abt-modified-date" datetime="<?php echo get_the_modified_date( 'c' ); ?>">
                                    <?php printf( __( 'Updated %s', 'academic-bloggers-toolkit' ), get_the_modified_date() ); ?>
                                </time>
                            <?php endif; ?>
                            
                            <?php
                            // Display DOI if available
                            $doi = get_post_meta( get_the_ID(), '_abt_doi', true );
                            if ( $doi ) :
                            ?>
                                <div class="abt-doi">
                                    <strong><?php _e( 'DOI:', 'academic-bloggers-toolkit' ); ?></strong>
                                    <a href="https://doi.org/<?php echo esc_attr( $doi ); ?>" target="_blank" rel="noopener">
                                        <?php echo esc_html( $doi ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php
                    // Display keywords if available
                    $keywords = get_post_meta( get_the_ID(), '_abt_keywords', true );
                    if ( $keywords ) :
                    ?>
                        <div class="abt-keywords">
                            <strong><?php _e( 'Keywords:', 'academic-bloggers-toolkit' ); ?></strong>
                            <span class="abt-keywords-list"><?php echo esc_html( $keywords ); ?></span>
                        </div>
                    <?php endif; ?>
                </header>
                
                <!-- Article Content -->
                <div class="abt-article-content">
                    <?php
                    // Content is automatically processed for citations by ABT_Public::process_post_content()
                    the_content();
                    ?>
                    
                    <?php
                    wp_link_pages( array(
                        'before' => '<div class="abt-page-links">' . __( 'Pages:', 'academic-bloggers-toolkit' ),
                        'after'  => '</div>',
                    ) );
                    ?>
                </div>
                
                <!-- Bibliography Section -->
                <?php
                // Display bibliography if citations exist
                $citations = get_post_meta( get_the_ID(), '_abt_citations', true );
                if ( ! empty( $citations ) && is_array( $citations ) ) :
                    $citation_style = get_post_meta( get_the_ID(), '_abt_citation_style', true );
                    if ( ! $citation_style ) {
                        $citation_style = 'apa';
                    }
                ?>
                    <section class="abt-bibliography-section">
                        <?php echo do_shortcode( '[abt_bibliography post_id="' . get_the_ID() . '" style="' . esc_attr( $citation_style ) . '"]' ); ?>
                    </section>
                <?php endif; ?>
                
                <!-- Article Footer -->
                <footer class="abt-article-footer">
                    <?php
                    // Display categories and tags
                    $categories = wp_get_post_terms( get_the_ID(), 'abt_blog_category' );
                    $tags = wp_get_post_terms( get_the_ID(), 'abt_blog_tag' );
                    ?>
                    
                    <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                        <div class="abt-categories">
                            <strong><?php _e( 'Categories:', 'academic-bloggers-toolkit' ); ?></strong>
                            <?php foreach ( $categories as $category ) : ?>
                                <a href="<?php echo get_term_link( $category ); ?>" class="abt-category-link">
                                    <?php echo esc_html( $category->name ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) : ?>
                        <div class="abt-tags">
                            <strong><?php _e( 'Tags:', 'academic-bloggers-toolkit' ); ?></strong>
                            <?php foreach ( $tags as $tag ) : ?>
                                <a href="<?php echo get_term_link( $tag ); ?>" class="abt-tag-link">
                                    <?php echo esc_html( $tag->name ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Sharing Section -->
                    <div class="abt-sharing">
                        <h3><?php _e( 'Share this article', 'academic-bloggers-toolkit' ); ?></h3>
                        <div class="abt-share-buttons">
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode( get_permalink() ); ?>&text=<?php echo urlencode( get_the_title() ); ?>" 
                               target="_blank" 
                               rel="noopener"
                               class="abt-share-twitter">
                                <?php _e( 'Twitter', 'academic-bloggers-toolkit' ); ?>
                            </a>
                            
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode( get_permalink() ); ?>" 
                               target="_blank" 
                               rel="noopener"
                               class="abt-share-linkedin">
                                <?php _e( 'LinkedIn', 'academic-bloggers-toolkit' ); ?>
                            </a>
                            
                            <a href="mailto:?subject=<?php echo urlencode( get_the_title() ); ?>&body=<?php echo urlencode( get_permalink() ); ?>" 
                               class="abt-share-email">
                                <?php _e( 'Email', 'academic-bloggers-toolkit' ); ?>
                            </a>
                            
                            <button type="button" 
                                    class="abt-share-copy" 
                                    data-url="<?php echo esc_attr( get_permalink() ); ?>"
                                    title="<?php _e( 'Copy link', 'academic-bloggers-toolkit' ); ?>">
                                <?php _e( 'Copy Link', 'academic-bloggers-toolkit' ); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Citation Information -->
                    <div class="abt-citation-info">
                        <h3><?php _e( 'How to cite this article', 'academic-bloggers-toolkit' ); ?></h3>
                        <div class="abt-citation-formats">
                            <?php
                            // Generate citation for this article
                            $citation_data = array(
                                'title' => get_the_title(),
                                'author' => get_the_author(),
                                'published_date' => get_the_date( 'Y-m-d' ),
                                'url' => get_permalink(),
                                'website_title' => get_bloginfo( 'name' ),
                                'access_date' => date( 'Y-m-d' ),
                            );
                            
                            // Load formatter
                            if ( class_exists( 'ABT_Formatter' ) ) {
                                $formatter = new ABT_Formatter();
                                
                                // APA Style
                                echo '<div class="abt-citation-format">';
                                echo '<strong>APA:</strong> ';
                                echo esc_html( $formatter->format_website_citation( $citation_data, 'apa' ) );
                                echo '</div>';
                                
                                // MLA Style
                                echo '<div class="abt-citation-format">';
                                echo '<strong>MLA:</strong> ';
                                echo esc_html( $formatter->format_website_citation( $citation_data, 'mla' ) );
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </footer>
                
            </article>
            
            <!-- Related Articles -->
            <?php
            $related_posts = get_posts( array(
                'post_type'      => 'abt_blog',
                'posts_per_page' => 3,
                'post__not_in'   => array( get_the_ID() ),
                'meta_query'     => array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_abt_subject',
                        'value'   => wp_get_post_terms( get_the_ID(), 'abt_subject', array( 'fields' => 'ids' ) ),
                        'compare' => 'IN',
                    ),
                ),
                'orderby' => 'rand',
            ) );
            
            if ( ! empty( $related_posts ) ) :
            ?>
                <aside class="abt-related-articles">
                    <h2><?php _e( 'Related Articles', 'academic-bloggers-toolkit' ); ?></h2>
                    <div class="abt-related-grid">
                        <?php foreach ( $related_posts as $related_post ) : ?>
                            <article class="abt-related-item">
                                <h3 class="abt-related-title">
                                    <a href="<?php echo get_permalink( $related_post->ID ); ?>">
                                        <?php echo esc_html( $related_post->post_title ); ?>
                                    </a>
                                </h3>
                                <div class="abt-related-meta">
                                    <span class="abt-related-author">
                                        <?php echo get_the_author_meta( 'display_name', $related_post->post_author ); ?>
                                    </span>
                                    <span class="abt-related-date">
                                        <?php echo get_the_date( '', $related_post->ID ); ?>
                                    </span>
                                </div>
                                <div class="abt-related-excerpt">
                                    <?php
                                    $abstract = get_post_meta( $related_post->ID, '_abt_abstract', true );
                                    if ( $abstract ) {
                                        echo wp_trim_words( wp_kses_post( $abstract ), 20 );
                                    } else {
                                        echo wp_trim_words( get_post_field( 'post_content', $related_post->ID ), 20 );
                                    }
                                    ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </aside>
            <?php endif; ?>
            
            <!-- Comments Section -->
            <?php
            if ( comments_open() || get_comments_number() ) :
                comments_template();
            endif;
            ?>
            
        <?php endwhile; ?>
        
    </div>
</div>

<!-- Reading Progress Indicator -->
<div class="abt-reading-progress">
    <div class="abt-progress-bar"></div>
</div>

<!-- Back to Top Button -->
<button type="button" class="abt-back-to-top" title="<?php _e( 'Back to top', 'academic-bloggers-toolkit' ); ?>">
    <span class="screen-reader-text"><?php _e( 'Back to top', 'academic-bloggers-toolkit' ); ?></span>
    ↑
</button>

<?php get_footer(); ?>