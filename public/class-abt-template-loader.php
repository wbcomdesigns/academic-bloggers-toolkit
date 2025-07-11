<?php
/**
 * Template loader for academic blog posts.
 *
 * @link       https://github.com/navidkashani/academic-bloggers-toolkit
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/public
 */

/**
 * Template loader class.
 *
 * Handles loading custom templates for academic blog posts and archives.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/public
 * @author     Navid Kashani <navid@example.com>
 */
class ABT_Template_Loader {

    /**
     * The template path within the plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $template_path    Template path.
     */
    private $template_path;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->template_path = ABT_PLUGIN_DIR . 'templates/';
        
        // Hook into template loading
        add_filter( 'template_include', array( $this, 'template_loader' ) );
        add_filter( 'theme_page_templates', array( $this, 'add_page_templates' ) );
        add_filter( 'wp_insert_post_data', array( $this, 'register_project_templates' ) );
    }

    /**
     * Load custom templates for academic blog posts.
     *
     * @since    1.0.0
     * @param    string    $template    Path to the template.
     * @return   string    Path to the template.
     */
    public function template_loader( $template ) {
        global $post;

        // Single academic blog post
        if ( is_singular( 'abt_blog' ) ) {
            $custom_template = $this->locate_template( 'single-abt_blog.php' );
            if ( $custom_template ) {
                return $custom_template;
            }
        }

        // Academic blog archive
        if ( is_post_type_archive( 'abt_blog' ) ) {
            $custom_template = $this->locate_template( 'archive-abt_blog.php' );
            if ( $custom_template ) {
                return $custom_template;
            }
        }

        // Academic blog category archive
        if ( is_tax( 'abt_blog_category' ) ) {
            $term = get_queried_object();
            $templates = array(
                'taxonomy-abt_blog_category-' . $term->slug . '.php',
                'taxonomy-abt_blog_category.php'
            );
            $custom_template = $this->locate_template( $templates );
            if ( $custom_template ) {
                return $custom_template;
            }
        }

        // Academic blog tag archive
        if ( is_tax( 'abt_blog_tag' ) ) {
            $term = get_queried_object();
            $templates = array(
                'taxonomy-abt_blog_tag-' . $term->slug . '.php',
                'taxonomy-abt_blog_tag.php'
            );
            $custom_template = $this->locate_template( $templates );
            if ( $custom_template ) {
                return $custom_template;
            }
        }

        // Subject archive
        if ( is_tax( 'abt_subject' ) ) {
            $term = get_queried_object();
            $templates = array(
                'taxonomy-abt_subject-' . $term->slug . '.php',
                'taxonomy-abt_subject.php'
            );
            $custom_template = $this->locate_template( $templates );
            if ( $custom_template ) {
                return $custom_template;
            }
        }

        // Search results with academic blog posts
        if ( is_search() ) {
            global $wp_query;
            $post_types = $wp_query->get( 'post_type' );
            if ( is_array( $post_types ) && in_array( 'abt_blog', $post_types ) ) {
                $custom_template = $this->locate_template( 'search-abt_blog.php' );
                if ( $custom_template ) {
                    return $custom_template;
                }
            }
        }

        return $template;
    }

    /**
     * Locate template files.
     *
     * @since    1.0.0
     * @param    string|array    $template_names    Template file(s) to search for.
     * @return   string|false    The template path or false if not found.
     */
    private function locate_template( $template_names ) {
        if ( ! is_array( $template_names ) ) {
            $template_names = array( $template_names );
        }

        foreach ( $template_names as $template_name ) {
            // Check if template exists in theme
            $theme_template = locate_template( array(
                'academic-bloggers-toolkit/' . $template_name,
                $template_name
            ) );

            if ( $theme_template ) {
                return $theme_template;
            }

            // Check if template exists in plugin
            $plugin_template = $this->template_path . $template_name;
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }

        return false;
    }

    /**
     * Add custom page templates.
     *
     * @since    1.0.0
     * @param    array    $templates    Page templates.
     * @return   array    Modified page templates.
     */
    public function add_page_templates( $templates ) {
        $templates['page-academic-blog.php'] = __( 'Academic Blog', 'academic-bloggers-toolkit' );
        $templates['page-academic-archive.php'] = __( 'Academic Archive', 'academic-bloggers-toolkit' );
        return $templates;
    }

    /**
     * Register project templates.
     *
     * @since    1.0.0
     * @param    array    $atts    Post data.
     * @return   array    Post data.
     */
    public function register_project_templates( $atts ) {
        // Cache to avoid multiple file system reads
        $cache_key = 'abt_page_templates';
        if ( wp_cache_get( $cache_key ) ) {
            return $atts;
        }

        $templates = array();

        $files = scandir( $this->template_path );
        foreach ( $files as $file ) {
            if ( strpos( $file, 'page-' ) === 0 && substr( $file, -4 ) === '.php' ) {
                $templates[] = $file;
            }
        }

        wp_cache_set( $cache_key, $templates );

        return $atts;
    }

    /**
     * Get template part for academic blog posts.
     *
     * @since    1.0.0
     * @param    string    $slug    The slug name for the generic template.
     * @param    string    $name    The name of the specialised template.
     * @param    array     $args    Arguments to pass to the template.
     */
    public static function get_template_part( $slug, $name = null, $args = array() ) {
        do_action( "get_template_part_{$slug}", $slug, $name );

        $templates = array();
        $name = (string) $name;
        if ( '' !== $name ) {
            $templates[] = "{$slug}-{$name}.php";
        }

        $templates[] = "{$slug}.php";

        // Extract args if provided
        if ( is_array( $args ) && ! empty( $args ) ) {
            extract( $args );
        }

        $located = '';
        foreach ( $templates as $template_name ) {
            // Check theme first
            $theme_template = locate_template( array(
                'academic-bloggers-toolkit/' . $template_name,
                $template_name
            ) );

            if ( $theme_template ) {
                $located = $theme_template;
                break;
            }

            // Check plugin templates
            $plugin_template = ABT_PLUGIN_DIR . 'templates/' . $template_name;
            if ( file_exists( $plugin_template ) ) {
                $located = $plugin_template;
                break;
            }
        }

        if ( $located ) {
            load_template( $located, false );
        }
    }

    /**
     * Include template file.
     *
     * @since    1.0.0
     * @param    string    $template_name    Template name.
     * @param    array     $args            Arguments to pass to template.
     * @param    string    $template_path   Path to templates.
     * @param    string    $default_path    Default path to templates.
     */
    public static function get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
        if ( ! empty( $args ) && is_array( $args ) ) {
            extract( $args );
        }

        $located = self::locate_template_file( $template_name, $template_path, $default_path );

        if ( ! file_exists( $located ) ) {
            return;
        }

        // Allow 3rd party plugin filter template file from their plugin.
        $located = apply_filters( 'abt_get_template', $located, $template_name, $args, $template_path, $default_path );

        do_action( 'abt_before_template_part', $template_name, $template_path, $located, $args );

        include $located;

        do_action( 'abt_after_template_part', $template_name, $template_path, $located, $args );
    }

    /**
     * Locate a template file.
     *
     * @since    1.0.0
     * @param    string    $template_name   Template name.
     * @param    string    $template_path   Path to templates.
     * @param    string    $default_path    Default path to templates.
     * @return   string    Template file path.
     */
    public static function locate_template_file( $template_name, $template_path = '', $default_path = '' ) {
        if ( ! $template_path ) {
            $template_path = 'academic-bloggers-toolkit/';
        }

        if ( ! $default_path ) {
            $default_path = ABT_PLUGIN_DIR . 'templates/';
        }

        // Look within passed path within the theme - this is priority.
        $template = locate_template(
            array(
                trailingslashit( $template_path ) . $template_name,
                $template_name,
            )
        );

        // Get default template/
        if ( ! $template ) {
            $template = $default_path . $template_name;
        }

        // Return what we found.
        return apply_filters( 'abt_locate_template', $template, $template_name, $template_path );
    }

    /**
     * Get template HTML.
     *
     * @since    1.0.0
     * @param    string    $template_name    Template name.
     * @param    array     $args            Arguments to pass to template.
     * @return   string    Template HTML.
     */
    public static function get_template_html( $template_name, $args = array() ) {
        ob_start();
        self::get_template( $template_name, $args );
        return ob_get_clean();
    }
}