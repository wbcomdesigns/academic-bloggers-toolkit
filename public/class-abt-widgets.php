<?php
/**
 * Widget functionality for the plugin.
 *
 * @link       https://github.com/navidkashani/academic-bloggers-toolkit
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/public
 */

/**
 * Recent Academic Posts Widget
 */
class ABT_Recent_Posts_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'abt_recent_posts',
            __( 'Recent Academic Posts', 'academic-bloggers-toolkit' ),
            array(
                'description' => __( 'Display recent academic blog posts', 'academic-bloggers-toolkit' ),
                'classname' => 'abt-recent-posts-widget'
            )
        );
    }

    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] ?? '' );
        $number = $instance['number'] ?? 5;
        $show_date = $instance['show_date'] ?? true;
        $show_author = $instance['show_author'] ?? true;

        echo $args['before_widget'];

        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $posts = get_posts( array(
            'post_type' => 'abt_blog',
            'posts_per_page' => $number,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ) );

        if ( ! empty( $posts ) ) {
            echo '<ul class="abt-recent-posts-list">';
            foreach ( $posts as $post ) {
                echo '<li class="abt-recent-post-item">';
                echo '<h4><a href="' . get_permalink( $post->ID ) . '">' . esc_html( $post->post_title ) . '</a></h4>';
                
                if ( $show_author ) {
                    echo '<span class="abt-post-author">' . get_the_author_meta( 'display_name', $post->post_author ) . '</span>';
                }
                
                if ( $show_date ) {
                    echo '<span class="abt-post-date">' . get_the_date( '', $post->ID ) . '</span>';
                }
                
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . __( 'No recent posts found.', 'academic-bloggers-toolkit' ) . '</p>';
        }

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = $instance['title'] ?? '';
        $number = $instance['number'] ?? 5;
        $show_date = $instance['show_date'] ?? true;
        $show_author = $instance['show_author'] ?? true;

        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts:' ); ?></label>
            <input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" value="<?php echo esc_attr( $number ); ?>" min="1" max="20">
        </p>
        <p>
            <input id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" type="checkbox" <?php checked( $show_date ); ?>>
            <label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Show date' ); ?></label>
        </p>
        <p>
            <input id="<?php echo $this->get_field_id( 'show_author' ); ?>" name="<?php echo $this->get_field_name( 'show_author' ); ?>" type="checkbox" <?php checked( $show_author ); ?>>
            <label for="<?php echo $this->get_field_id( 'show_author' ); ?>"><?php _e( 'Show author' ); ?></label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['number'] = absint( $new_instance['number'] );
        $instance['show_date'] = isset( $new_instance['show_date'] );
        $instance['show_author'] = isset( $new_instance['show_author'] );
        return $instance;
    }
}

/**
 * Popular References Widget
 */
class ABT_Popular_References_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'abt_popular_references',
            __( 'Popular References', 'academic-bloggers-toolkit' ),
            array(
                'description' => __( 'Display most cited references', 'academic-bloggers-toolkit' ),
                'classname' => 'abt-popular-references-widget'
            )
        );
    }

    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] ?? '' );
        $number = $instance['number'] ?? 5;

        echo $args['before_widget'];

        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        // Get references with citation counts
        $references = get_posts( array(
            'post_type' => 'abt_reference',
            'posts_per_page' => $number,
            'meta_key' => '_abt_citation_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'post_status' => 'publish'
        ) );

        if ( ! empty( $references ) ) {
            echo '<ul class="abt-popular-references-list">';
            foreach ( $references as $reference ) {
                $citation_count = get_post_meta( $reference->ID, '_abt_citation_count', true );
                echo '<li class="abt-popular-reference-item">';
                echo '<h4>' . esc_html( $reference->post_title ) . '</h4>';
                echo '<span class="abt-citation-count">' . sprintf( _n( '%d citation', '%d citations', $citation_count, 'academic-bloggers-toolkit' ), $citation_count ) . '</span>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . __( 'No references found.', 'academic-bloggers-toolkit' ) . '</p>';
        }

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = $instance['title'] ?? '';
        $number = $instance['number'] ?? 5;

        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of references:' ); ?></label>
            <input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" value="<?php echo esc_attr( $number ); ?>" min="1" max="20">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['number'] = absint( $new_instance['number'] );
        return $instance;
    }
}

/**
 * Citation Statistics Widget
 */
class ABT_Citation_Stats_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'abt_citation_stats',
            __( 'Citation Statistics', 'academic-bloggers-toolkit' ),
            array(
                'description' => __( 'Display citation and reference statistics', 'academic-bloggers-toolkit' ),
                'classname' => 'abt-citation-stats-widget'
            )
        );
    }

    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] ?? '' );
        $show_posts = $instance['show_posts'] ?? true;
        $show_references = $instance['show_references'] ?? true;
        $show_citations = $instance['show_citations'] ?? true;

        echo $args['before_widget'];

        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        echo '<div class="abt-stats-grid">';

        if ( $show_posts ) {
            $post_count = wp_count_posts( 'abt_blog' )->publish;
            echo '<div class="abt-stat-item">';
            echo '<span class="abt-stat-number">' . number_format( $post_count ) . '</span>';
            echo '<span class="abt-stat-label">' . __( 'Academic Posts', 'academic-bloggers-toolkit' ) . '</span>';
            echo '</div>';
        }

        if ( $show_references ) {
            $ref_count = wp_count_posts( 'abt_reference' )->publish;
            echo '<div class="abt-stat-item">';
            echo '<span class="abt-stat-number">' . number_format( $ref_count ) . '</span>';
            echo '<span class="abt-stat-label">' . __( 'References', 'academic-bloggers-toolkit' ) . '</span>';
            echo '</div>';
        }

        if ( $show_citations ) {
            $citation_count = $this->get_total_citations();
            echo '<div class="abt-stat-item">';
            echo '<span class="abt-stat-number">' . number_format( $citation_count ) . '</span>';
            echo '<span class="abt-stat-label">' . __( 'Citations', 'academic-bloggers-toolkit' ) . '</span>';
            echo '</div>';
        }

        echo '</div>';
        echo $args['after_widget'];
    }

    private function get_total_citations() {
        global $wpdb;
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'abt_citation' AND post_status = 'publish'"
        );
        return $count ? $count : 0;
    }

    public function form( $instance ) {
        $title = $instance['title'] ?? '';
        $show_posts = $instance['show_posts'] ?? true;
        $show_references = $instance['show_references'] ?? true;
        $show_citations = $instance['show_citations'] ?? true;

        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <input id="<?php echo $this->get_field_id( 'show_posts' ); ?>" name="<?php echo $this->get_field_name( 'show_posts' ); ?>" type="checkbox" <?php checked( $show_posts ); ?>>
            <label for="<?php echo $this->get_field_id( 'show_posts' ); ?>"><?php _e( 'Show post count' ); ?></label>
        </p>
        <p>
            <input id="<?php echo $this->get_field_id( 'show_references' ); ?>" name="<?php echo $this->get_field_name( 'show_references' ); ?>" type="checkbox" <?php checked( $show_references ); ?>>
            <label for="<?php echo $this->get_field_id( 'show_references' ); ?>"><?php _e( 'Show reference count' ); ?></label>
        </p>
        <p>
            <input id="<?php echo $this->get_field_id( 'show_citations' ); ?>" name="<?php echo $this->get_field_name( 'show_citations' ); ?>" type="checkbox" <?php checked( $show_citations ); ?>>
            <label for="<?php echo $this->get_field_id( 'show_citations' ); ?>"><?php _e( 'Show citation count' ); ?></label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['show_posts'] = isset( $new_instance['show_posts'] );
        $instance['show_references'] = isset( $new_instance['show_references'] );
        $instance['show_citations'] = isset( $new_instance['show_citations'] );
        return $instance;
    }
}