<?php
/**
 * Shortcode functionality for the plugin.
 *
 * @link       https://github.com/navidkashani/academic-bloggers-toolkit
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/public
 */

/**
 * Shortcode class.
 *
 * Defines shortcodes for displaying academic content.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/public
 * @author     Navid Kashani <navid@example.com>
 */
class ABT_Shortcodes {

    /**
     * Initialize the shortcodes.
     *
     * @since    1.0.0
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_shortcodes' ) );
    }

    /**
     * Register all shortcodes.
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        // Academic blog listing shortcodes
        add_shortcode( 'abt_blog_list', array( $this, 'blog_list_shortcode' ) );
        add_shortcode( 'abt_recent_posts', array( $this, 'recent_posts_shortcode' ) );
        add_shortcode( 'abt_featured_posts', array( $this, 'featured_posts_shortcode' ) );
        
        // Reference and citation shortcodes
        add_shortcode( 'abt_reference_list', array( $this, 'reference_list_shortcode' ) );
        add_shortcode( 'abt_bibliography', array( $this, 'bibliography_shortcode' ) );
        add_shortcode( 'abt_citation', array( $this, 'citation_shortcode' ) );
        add_shortcode( 'abt_footnote', array( $this, 'footnote_shortcode' ) );
        
        // Search and filter shortcodes
        add_shortcode( 'abt_search_form', array( $this, 'search_form_shortcode' ) );
        add_shortcode( 'abt_subject_filter', array( $this, 'subject_filter_shortcode' ) );
        add_shortcode( 'abt_tag_cloud', array( $this, 'tag_cloud_shortcode' ) );
        
        // Author and profile shortcodes
        add_shortcode( 'abt_author_profile', array( $this, 'author_profile_shortcode' ) );
        add_shortcode( 'abt_author_posts', array( $this, 'author_posts_shortcode' ) );
        
        // Statistics shortcodes
        add_shortcode( 'abt_citation_stats', array( $this, 'citation_stats_shortcode' ) );
        add_shortcode( 'abt_reading_stats', array( $this, 'reading_stats_shortcode' ) );
    }

    /**
     * Academic blog list shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string   Shortcode output.
     */
    public function blog_list_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'posts_per_page' => 10,
            'category'       => '',
            'tag'           => '',
            'subject'       => '',
            'author'        => '',
            'orderby'       => 'date',
            'order'         => 'DESC',
            'layout'        => 'list', // list, grid, minimal
            'show_excerpt'  => 'true',
            'show_meta'     => 'true',
            'show_citation_count' => 'false',
            'class'         => '',
        ), $atts, 'abt_blog_list' );

        // Build query args
        $query_args = array(
            'post_type'      => 'abt_blog',
            'posts_per_page' => intval( $atts['posts_per_page'] ),
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order'],
            'post_status'    => 'publish',
        );

        // Add taxonomy queries
        $tax_query = array();
        
        if ( ! empty( $atts['category'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'abt_blog_category',
                'field'    => 'slug',
                'terms'    => explode( ',', $atts['category'] ),
            );
        }

        if ( ! empty( $atts['tag'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'abt_blog_tag',
                'field'    => 'slug',
                'terms'    => explode( ',', $atts['tag'] ),
            );
        }

        if ( ! empty( $atts['subject'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'abt_subject',
                'field'    => 'slug',
                'terms'    => explode( ',', $atts['subject'] ),
            );
        }

        if ( count( $tax_query ) > 1 ) {
            $tax_query['relation'] = 'AND';
        }

        if ( ! empty( $tax_query ) ) {
            $query_args['tax_query'] = $tax_query;
        }

        // Add author filter
        if ( ! empty( $atts['author'] ) ) {
            if ( is_numeric( $atts['author'] ) ) {
                $query_args['author'] = $atts['author'];
            } else {
                $query_args['author_name'] = $atts['author'];
            }
        }

        $query = new WP_Query( $query_args );

        if ( ! $query->have_posts() ) {
            return '<p class="abt-no-posts">' . __( 'No academic posts found.', 'academic-bloggers-toolkit' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="abt-blog-list abt-layout-<?php echo esc_attr( $atts['layout'] ); ?> <?php echo esc_attr( $atts['class'] ); ?>">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <article class="abt-blog-item">
                    <header class="abt-blog-header">
                        <h3 class="abt-blog-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        <?php if ( $atts['show_meta'] === 'true' ) : ?>
                            <div class="abt-blog-meta">
                                <span class="abt-author"><?php _e( 'By', 'academic-bloggers-toolkit' ); ?> <?php the_author(); ?></span>
                                <span class="abt-date"><?php echo get_the_date(); ?></span>
                                <?php if ( $atts['show_citation_count'] === 'true' ) : ?>
                                    <span class="abt-citations"><?php echo $this->get_citation_count( get_the_ID() ); ?> <?php _e( 'citations', 'academic-bloggers-toolkit' ); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </header>
                    
                    <?php if ( $atts['show_excerpt'] === 'true' ) : ?>
                        <div class="abt-blog-excerpt">
                            <?php 
                            $abstract = get_post_meta( get_the_ID(), '_abt_abstract', true );
                            if ( $abstract ) {
                                echo wp_trim_words( wp_kses_post( $abstract ), 30 );
                            } else {
                                the_excerpt();
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <footer class="abt-blog-footer">
                        <?php
                        $subjects = wp_get_post_terms( get_the_ID(), 'abt_subject' );
                        if ( ! empty( $subjects ) && ! is_wp_error( $subjects ) ) :
                        ?>
                            <div class="abt-subjects">
                                <?php foreach ( $subjects as $subject ) : ?>
                                    <span class="abt-subject"><?php echo esc_html( $subject->name ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <a href="<?php the_permalink(); ?>" class="abt-read-more">
                            <?php _e( 'Read Full Article', 'academic-bloggers-toolkit' ); ?>
                        </a>
                    </footer>
                </article>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Bibliography shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string   Shortcode output.
     */
    public function bibliography_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'post_id' => get_the_ID(),
            'style'   => 'apa',
            'title'   => __( 'References', 'academic-bloggers-toolkit' ),
            'class'   => '',
        ), $atts, 'abt_bibliography' );

        if ( ! $atts['post_id'] ) {
            return '';
        }

        // Load citation processor
        if ( ! class_exists( 'ABT_Citation_Processor' ) ) {
            require_once ABT_PLUGIN_DIR . 'includes/processors/class-abt-citation-processor.php';
        }

        $processor = ABT_Citation_Processor::get_instance();
        $bibliography = $processor->generate_bibliography( $atts['post_id'], $atts['style'] );

        if ( empty( $bibliography ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="abt-bibliography <?php echo esc_attr( $atts['class'] ); ?>">
            <?php if ( ! empty( $atts['title'] ) ) : ?>
                <h3 class="abt-bibliography-title"><?php echo esc_html( $atts['title'] ); ?></h3>
            <?php endif; ?>
            <div class="abt-bibliography-list">
                <?php echo $bibliography; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Reference list shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string   Shortcode output.
     */
    public function reference_list_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'posts_per_page' => 20,
            'category'       => '',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'style'          => 'apa',
            'show_abstract'  => 'false',
            'class'          => '',
        ), $atts, 'abt_reference_list' );

        $query_args = array(
            'post_type'      => 'abt_reference',
            'posts_per_page' => intval( $atts['posts_per_page'] ),
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order'],
            'post_status'    => 'publish',
        );

        // Add category filter
        if ( ! empty( $atts['category'] ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'abt_ref_category',
                    'field'    => 'slug',
                    'terms'    => explode( ',', $atts['category'] ),
                ),
            );
        }

        $query = new WP_Query( $query_args );

        if ( ! $query->have_posts() ) {
            return '<p class="abt-no-references">' . __( 'No references found.', 'academic-bloggers-toolkit' ) . '</p>';
        }

        // Load formatter
        if ( ! class_exists( 'ABT_Formatter' ) ) {
            require_once ABT_PLUGIN_DIR . 'includes/processors/class-abt-formatter.php';
        }

        $formatter = new ABT_Formatter();

        ob_start();
        ?>
        <div class="abt-reference-list <?php echo esc_attr( $atts['class'] ); ?>">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <div class="abt-reference-item">
                    <?php
                    // Get reference data
                    $reference_data = array();
                    $meta_fields = get_post_meta( get_the_ID() );
                    foreach ( $meta_fields as $key => $value ) {
                        if ( strpos( $key, '_abt_' ) === 0 ) {
                            $field_name = str_replace( '_abt_', '', $key );
                            $reference_data[ $field_name ] = is_array( $value ) ? $value[0] : $value;
                        }
                    }

                    // Format citation
                    $formatted = $formatter->format_reference( $reference_data, $atts['style'] );
                    echo wp_kses_post( $formatted );
                    ?>
                    
                    <?php if ( $atts['show_abstract'] === 'true' && ! empty( $reference_data['abstract'] ) ) : ?>
                        <div class="abt-reference-abstract">
                            <?php echo wp_kses_post( wpautop( $reference_data['abstract'] ) ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Search form shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string   Shortcode output.
     */
    public function search_form_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'placeholder' => __( 'Search academic posts...', 'academic-bloggers-toolkit' ),
            'button_text' => __( 'Search', 'academic-bloggers-toolkit' ),
            'class'       => '',
        ), $atts, 'abt_search_form' );

        ob_start();
        ?>
        <form class="abt-search-form <?php echo esc_attr( $atts['class'] ); ?>" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
            <div class="abt-search-wrapper">
                <input type="search" 
                       name="s" 
                       placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
                       value="<?php echo get_search_query(); ?>"
                       class="abt-search-field">
                <input type="hidden" name="post_type" value="abt_blog">
                <button type="submit" class="abt-search-submit">
                    <?php echo esc_html( $atts['button_text'] ); ?>
                </button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Citation shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string   Shortcode output.
     */
    public function citation_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'id'    => '',
            'page'  => '',
            'style' => 'apa',
        ), $atts, 'abt_citation' );

        if ( empty( $atts['id'] ) ) {
            return '';
        }

        // Load citation processor
        if ( ! class_exists( 'ABT_Citation_Processor' ) ) {
            require_once ABT_PLUGIN_DIR . 'includes/processors/class-abt-citation-processor.php';
        }

        $processor = ABT_Citation_Processor::get_instance();
        return $processor->format_inline_citation( $atts['id'], $atts['page'], $atts['style'] );
    }

    /**
     * Author profile shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string   Shortcode output.
     */
    public function author_profile_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'user_id'      => get_the_author_meta( 'ID' ),
            'show_avatar'  => 'true',
            'show_bio'     => 'true',
            'show_posts'   => 'true',
            'posts_count'  => 5,
            'class'        => '',
        ), $atts, 'abt_author_profile' );

        if ( ! $atts['user_id'] ) {
            return '';
        }

        $author = get_userdata( $atts['user_id'] );
        if ( ! $author ) {
            return '';
        }

        ob_start();
        ?>
        <div class="abt-author-profile <?php echo esc_attr( $atts['class'] ); ?>">
            <div class="abt-author-header">
                <?php if ( $atts['show_avatar'] === 'true' ) : ?>
                    <div class="abt-author-avatar">
                        <?php echo get_avatar( $author->ID, 80 ); ?>
                    </div>
                <?php endif; ?>
                
                <div class="abt-author-info">
                    <h3 class="abt-author-name"><?php echo esc_html( $author->display_name ); ?></h3>
                    
                    <?php if ( $atts['show_bio'] === 'true' && $author->description ) : ?>
                        <div class="abt-author-bio">
                            <?php echo wp_kses_post( wpautop( $author->description ) ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ( $atts['show_posts'] === 'true' ) : ?>
                <?php
                $posts = get_posts( array(
                    'author'         => $author->ID,
                    'post_type'      => 'abt_blog',
                    'posts_per_page' => intval( $atts['posts_count'] ),
                    'post_status'    => 'publish',
                ) );
                ?>
                
                <?php if ( ! empty( $posts ) ) : ?>
                    <div class="abt-author-posts">
                        <h4><?php _e( 'Recent Posts', 'academic-bloggers-toolkit' ); ?></h4>
                        <ul>
                            <?php foreach ( $posts as $post ) : ?>
                                <li>
                                    <a href="<?php echo get_permalink( $post->ID ); ?>">
                                        <?php echo esc_html( $post->post_title ); ?>
                                    </a>
                                    <span class="abt-post-date"><?php echo get_the_date( '', $post->ID ); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get citation count for a post.
     *
     * @since    1.0.0
     * @param    int    $post_id    Post ID.
     * @return   int    Citation count.
     */
    private function get_citation_count( $post_id ) {
        $citations = get_post_meta( $post_id, '_abt_citations', true );
        return is_array( $citations ) ? count( $citations ) : 0;
    }

    /**
     * Subject filter shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string   Shortcode output.
     */
    public function subject_filter_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'show_count' => 'true',
            'orderby'    => 'name',
            'order'      => 'ASC',
            'class'      => '',
        ), $atts, 'abt_subject_filter' );

        $subjects = get_terms( array(
            'taxonomy'   => 'abt_subject',
            'hide_empty' => true,
            'orderby'    => $atts['orderby'],
            'order'      => $atts['order'],
        ) );

        if ( empty( $subjects ) || is_wp_error( $subjects ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="abt-subject-filter <?php echo esc_attr( $atts['class'] ); ?>">
            <ul class="abt-subject-list">
                <?php foreach ( $subjects as $subject ) : ?>
                    <li class="abt-subject-item">
                        <a href="<?php echo get_term_link( $subject ); ?>">
                            <?php echo esc_html( $subject->name ); ?>
                            <?php if ( $atts['show_count'] === 'true' ) : ?>
                                <span class="abt-subject-count">(<?php echo $subject->count; ?>)</span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Citation stats shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string   Shortcode output.
     */
    public function citation_stats_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'class' => '',
        ), $atts, 'abt_citation_stats' );

        // Get statistics
        $total_posts = wp_count_posts( 'abt_blog' )->publish;
        $total_references = wp_count_posts( 'abt_reference' )->publish;
        
        // Count total citations (simplified)
        $citation_meta = get_posts( array(
            'post_type' => 'abt_blog',
            'meta_key'  => '_abt_citations',
            'fields'    => 'ids',
            'numberposts' => -1,
        ) );

        $total_citations = 0;
        foreach ( $citation_meta as $post_id ) {
            $citations = get_post_meta( $post_id, '_abt_citations', true );
            if ( is_array( $citations ) ) {
                $total_citations += count( $citations );
            }
        }

        ob_start();
        ?>
        <div class="abt-citation-stats <?php echo esc_attr( $atts['class'] ); ?>">
            <div class="abt-stat-item">
                <span class="abt-stat-number"><?php echo number_format( $total_posts ); ?></span>
                <span class="abt-stat-label"><?php _e( 'Academic Posts', 'academic-bloggers-toolkit' ); ?></span>
            </div>
            <div class="abt-stat-item">
                <span class="abt-stat-number"><?php echo number_format( $total_references ); ?></span>
                <span class="abt-stat-label"><?php _e( 'References', 'academic-bloggers-toolkit' ); ?></span>
            </div>
            <div class="abt-stat-item">
                <span class="abt-stat-number"><?php echo number_format( $total_citations ); ?></span>
                <span class="abt-stat-label"><?php _e( 'Citations', 'academic-bloggers-toolkit' ); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}