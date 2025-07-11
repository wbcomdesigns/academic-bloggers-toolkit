<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/navidkashani/academic-bloggers-toolkit
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/public
 * @author     Navid Kashani <navid@example.com>
 */
class ABT_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The template loader instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      ABT_Template_Loader    $template_loader    Template loader instance.
     */
    private $template_loader;

    /**
     * The shortcodes instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      ABT_Shortcodes    $shortcodes    Shortcodes instance.
     */
    private $shortcodes;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Main frontend styles
        wp_enqueue_style( 
            $this->plugin_name . '-frontend', 
            ABT_PLUGIN_URL . 'public/css/dist/frontend-main.css', 
            array(), 
            $this->version, 
            'all' 
        );

        // Academic blog specific styles
        if ( is_singular( 'abt_blog' ) || is_post_type_archive( 'abt_blog' ) ) {
            wp_enqueue_style( 
                $this->plugin_name . '-academic-blog', 
                ABT_PLUGIN_URL . 'public/css/dist/academic-blog.css', 
                array( $this->plugin_name . '-frontend' ), 
                $this->version, 
                'all' 
            );

            wp_enqueue_style( 
                $this->plugin_name . '-citations', 
                ABT_PLUGIN_URL . 'public/css/dist/citations.css', 
                array( $this->plugin_name . '-frontend' ), 
                $this->version, 
                'all' 
            );
        }

        // Print-friendly styles
        wp_enqueue_style( 
            $this->plugin_name . '-print', 
            ABT_PLUGIN_URL . 'public/css/dist/print.css', 
            array( $this->plugin_name . '-frontend' ), 
            $this->version, 
            'print' 
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Main frontend script
        wp_enqueue_script( 
            $this->plugin_name . '-frontend', 
            ABT_PLUGIN_URL . 'public/js/dist/frontend-main.js', 
            array( 'jquery' ), 
            $this->version, 
            false 
        );

        // Academic blog specific scripts
        if ( is_singular( 'abt_blog' ) || is_post_type_archive( 'abt_blog' ) ) {
            wp_enqueue_script( 
                $this->plugin_name . '-citations', 
                ABT_PLUGIN_URL . 'public/js/dist/citation-tooltips.js', 
                array( 'jquery', $this->plugin_name . '-frontend' ), 
                $this->version, 
                false 
            );

            wp_enqueue_script( 
                $this->plugin_name . '-footnotes', 
                ABT_PLUGIN_URL . 'public/js/dist/footnote-handler.js', 
                array( 'jquery', $this->plugin_name . '-frontend' ), 
                $this->version, 
                false 
            );

            wp_enqueue_script( 
                $this->plugin_name . '-reading-progress', 
                ABT_PLUGIN_URL . 'public/js/dist/reading-progress.js', 
                array( 'jquery', $this->plugin_name . '-frontend' ), 
                $this->version, 
                false 
            );
        }

        // Localize script for AJAX
        wp_localize_script( $this->plugin_name . '-frontend', 'abt_frontend_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'abt_frontend_nonce' ),
            'strings' => array(
                'loading' => __( 'Loading...', 'academic-bloggers-toolkit' ),
                'error' => __( 'An error occurred. Please try again.', 'academic-bloggers-toolkit' ),
                'no_results' => __( 'No results found.', 'academic-bloggers-toolkit' ),
            )
        ));
    }

    /**
     * Initialize template loader.
     *
     * @since    1.0.0
     */
    public function init_template_loader() {
        if ( ! class_exists( 'ABT_Template_Loader' ) ) {
            require_once ABT_PLUGIN_DIR . 'public/class-abt-template-loader.php';
        }
        $this->template_loader = new ABT_Template_Loader();
    }

    /**
     * Initialize shortcodes.
     *
     * @since    1.0.0
     */
    public function init_shortcodes() {
        if ( ! class_exists( 'ABT_Shortcodes' ) ) {
            require_once ABT_PLUGIN_DIR . 'public/class-abt-shortcodes.php';
        }
        $this->shortcodes = new ABT_Shortcodes();
    }

    /**
     * Filter the content to process citations and footnotes.
     *
     * @since    1.0.0
     * @param    string    $content    The post content.
     * @return   string    The processed content.
     */
    public function process_post_content( $content ) {
        global $post;
        
        // Only process abt_blog posts
        if ( ! isset( $post->post_type ) || $post->post_type !== 'abt_blog' ) {
            return $content;
        }

        // Load citation processor if not already loaded
        if ( ! class_exists( 'ABT_Citation_Processor' ) ) {
            require_once ABT_PLUGIN_DIR . 'includes/processors/class-abt-citation-processor.php';
        }

        $processor = ABT_Citation_Processor::get_instance();
        return $processor->process_post_content( $content, $post->ID );
    }

    /**
     * Add custom body classes for academic blog posts.
     *
     * @since    1.0.0
     * @param    array    $classes    Existing body classes.
     * @return   array    Modified body classes.
     */
    public function add_body_classes( $classes ) {
        global $post;

        if ( isset( $post->post_type ) && $post->post_type === 'abt_blog' ) {
            $classes[] = 'academic-blog-post';
            
            // Add citation style class
            $style = get_post_meta( $post->ID, '_abt_citation_style', true );
            if ( $style ) {
                $classes[] = 'citation-style-' . sanitize_html_class( $style );
            }

            // Add subject classes
            $subjects = wp_get_post_terms( $post->ID, 'abt_subject' );
            if ( ! empty( $subjects ) && ! is_wp_error( $subjects ) ) {
                foreach ( $subjects as $subject ) {
                    $classes[] = 'subject-' . sanitize_html_class( $subject->slug );
                }
            }
        }

        return $classes;
    }

    /**
     * Add structured data for academic posts.
     *
     * @since    1.0.0
     */
    public function add_structured_data() {
        global $post;

        if ( ! is_singular( 'abt_blog' ) || ! isset( $post ) ) {
            return;
        }

        $structured_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'ScholarlyArticle',
            'headline' => get_the_title( $post->ID ),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author_meta( 'display_name', $post->post_author )
            ),
            'datePublished' => get_the_date( 'c', $post->ID ),
            'dateModified' => get_the_modified_date( 'c', $post->ID ),
            'description' => get_the_excerpt( $post ),
            'url' => get_permalink( $post->ID ),
        );

        // Add additional metadata
        $abstract = get_post_meta( $post->ID, '_abt_abstract', true );
        if ( $abstract ) {
            $structured_data['abstract'] = $abstract;
        }

        $keywords = get_post_meta( $post->ID, '_abt_keywords', true );
        if ( $keywords ) {
            $structured_data['keywords'] = explode( ',', $keywords );
        }

        // Add subjects as about
        $subjects = wp_get_post_terms( $post->ID, 'abt_subject' );
        if ( ! empty( $subjects ) && ! is_wp_error( $subjects ) ) {
            $about = array();
            foreach ( $subjects as $subject ) {
                $about[] = array(
                    '@type' => 'Thing',
                    'name' => $subject->name
                );
            }
            $structured_data['about'] = $about;
        }

        echo '<script type="application/ld+json">' . wp_json_encode( $structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . PHP_EOL;
    }

    /**
     * Modify post excerpt for academic posts.
     *
     * @since    1.0.0
     * @param    string    $excerpt    The post excerpt.
     * @return   string    The modified excerpt.
     */
    public function modify_excerpt( $excerpt ) {
        global $post;

        if ( isset( $post->post_type ) && $post->post_type === 'abt_blog' ) {
            // Use abstract if available
            $abstract = get_post_meta( $post->ID, '_abt_abstract', true );
            if ( $abstract ) {
                return wp_trim_words( $abstract, 55, '...' );
            }
        }

        return $excerpt;
    }

    /**
     * Add citation metadata to post queries.
     *
     * @since    1.0.0
     * @param    WP_Query    $query    The WP_Query instance.
     */
    public function modify_main_query( $query ) {
        if ( ! is_admin() && $query->is_main_query() ) {
            // Add academic blog posts to search results
            if ( $query->is_search() ) {
                $post_types = $query->get( 'post_type' );
                if ( empty( $post_types ) ) {
                    $post_types = array( 'post', 'abt_blog' );
                } elseif ( is_array( $post_types ) ) {
                    $post_types[] = 'abt_blog';
                }
                $query->set( 'post_type', $post_types );
            }
        }
    }

    /**
     * Add academic post navigation.
     *
     * @since    1.0.0
     */
    public function add_academic_navigation() {
        if ( is_singular( 'abt_blog' ) ) {
            $prev_post = get_previous_post( true, '', 'abt_subject' );
            $next_post = get_next_post( true, '', 'abt_subject' );

            if ( $prev_post || $next_post ) {
                echo '<nav class="academic-post-navigation" role="navigation">';
                echo '<h2 class="screen-reader-text">' . esc_html__( 'Post navigation', 'academic-bloggers-toolkit' ) . '</h2>';
                echo '<div class="nav-links">';

                if ( $prev_post ) {
                    echo '<div class="nav-previous">';
                    echo '<a href="' . esc_url( get_permalink( $prev_post->ID ) ) . '" rel="prev">';
                    echo '<span class="nav-subtitle">' . esc_html__( 'Previous Article', 'academic-bloggers-toolkit' ) . '</span>';
                    echo '<span class="nav-title">' . esc_html( get_the_title( $prev_post->ID ) ) . '</span>';
                    echo '</a>';
                    echo '</div>';
                }

                if ( $next_post ) {
                    echo '<div class="nav-next">';
                    echo '<a href="' . esc_url( get_permalink( $next_post->ID ) ) . '" rel="next">';
                    echo '<span class="nav-subtitle">' . esc_html__( 'Next Article', 'academic-bloggers-toolkit' ) . '</span>';
                    echo '<span class="nav-title">' . esc_html( get_the_title( $next_post->ID ) ) . '</span>';
                    echo '</a>';
                    echo '</div>';
                }

                echo '</div>';
                echo '</nav>';
            }
        }
    }

    /**
     * Register custom widgets.
     *
     * @since    1.0.0
     */
    public function register_widgets() {
        // Load widget classes
        require_once ABT_PLUGIN_DIR . 'public/class-abt-widgets.php';
        
        // Register widgets
        register_widget( 'ABT_Recent_Posts_Widget' );
        register_widget( 'ABT_Popular_References_Widget' );
        register_widget( 'ABT_Citation_Stats_Widget' );
    }
}