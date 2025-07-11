<?php
/**
 * Main Query Class for Academic Blogger's Toolkit
 *
 * Handles complex database queries and operations for the plugin
 *
 * @package Academic_Bloggers_Toolkit
 * @subpackage Queries
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ABT Query Class
 *
 * Provides optimized query methods for Academic Blogger's Toolkit
 */
class ABT_Query {

    /**
     * Get academic blog posts with citations
     *
     * @since 1.0.0
     * @param array $args Query arguments
     * @return WP_Query Query object
     */
    public static function get_academic_posts( $args = array() ) {
        $defaults = array(
            'post_type' => 'abt_blog',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'meta_query' => array(),
            'tax_query' => array()
        );

        $args = wp_parse_args( $args, $defaults );

        // Add citation count meta query if requested
        if ( isset( $args['has_citations'] ) && $args['has_citations'] ) {
            $args['meta_query'][] = array(
                'key' => '_abt_citation_count',
                'value' => 0,
                'compare' => '>'
            );
            unset( $args['has_citations'] );
        }

        return new WP_Query( $args );
    }

    /**
     * Get references by type or search term
     *
     * @since 1.0.0
     * @param array $args Query arguments
     * @return array Array of reference posts
     */
    public static function get_references( $args = array() ) {
        $defaults = array(
            'post_type' => 'abt_reference',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => array()
        );

        $args = wp_parse_args( $args, $defaults );

        // Filter by reference type
        if ( isset( $args['reference_type'] ) && ! empty( $args['reference_type'] ) ) {
            $args['meta_query'][] = array(
                'key' => '_abt_reference_type',
                'value' => sanitize_text_field( $args['reference_type'] ),
                'compare' => '='
            );
            unset( $args['reference_type'] );
        }

        // Search in title, content, and meta
        if ( isset( $args['search'] ) && ! empty( $args['search'] ) ) {
            $search_term = sanitize_text_field( $args['search'] );
            $args['s'] = $search_term;
            
            // Also search in author and publication meta
            $args['meta_query'][] = array(
                'relation' => 'OR',
                array(
                    'key' => '_abt_author',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_abt_publication',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                )
            );
            unset( $args['search'] );
        }

        return get_posts( $args );
    }

    /**
     * Get citations for a specific post
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return array Array of citation data
     */
    public static function get_post_citations( $post_id ) {
        if ( ! $post_id ) {
            return array();
        }

        $citations = get_post_meta( $post_id, '_abt_citations', true );
        
        if ( ! is_array( $citations ) ) {
            return array();
        }

        // Validate and enrich citation data
        $enriched_citations = array();
        foreach ( $citations as $citation ) {
            if ( isset( $citation['reference_id'] ) ) {
                $reference = get_post( $citation['reference_id'] );
                if ( $reference && $reference->post_type === 'abt_reference' ) {
                    $citation['reference_data'] = ABT_Reference::get_reference_meta( $reference->ID );
                    $enriched_citations[] = $citation;
                }
            }
        }

        return $enriched_citations;
    }

    /**
     * Get footnotes for a specific post
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return array Array of footnote data
     */
    public static function get_post_footnotes( $post_id ) {
        if ( ! $post_id ) {
            return array();
        }

        $footnotes = get_post_meta( $post_id, '_abt_footnotes', true );
        
        if ( ! is_array( $footnotes ) ) {
            return array();
        }

        // Sort footnotes by order
        usort( $footnotes, function( $a, $b ) {
            $order_a = isset( $a['order'] ) ? intval( $a['order'] ) : 0;
            $order_b = isset( $b['order'] ) ? intval( $b['order'] ) : 0;
            return $order_a - $order_b;
        });

        return $footnotes;
    }

    /**
     * Get related academic posts based on citations
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @param int $limit Number of related posts to return
     * @return array Array of related post objects
     */
    public static function get_related_posts( $post_id, $limit = 5 ) {
        if ( ! $post_id ) {
            return array();
        }

        $citations = self::get_post_citations( $post_id );
        if ( empty( $citations ) ) {
            return array();
        }

        // Get reference IDs from citations
        $reference_ids = array();
        foreach ( $citations as $citation ) {
            if ( isset( $citation['reference_id'] ) ) {
                $reference_ids[] = $citation['reference_id'];
            }
        }

        if ( empty( $reference_ids ) ) {
            return array();
        }

        // Find other posts that cite the same references
        global $wpdb;

        $reference_ids_str = implode( ',', array_map( 'intval', $reference_ids ) );
        
        $query = $wpdb->prepare( "
            SELECT DISTINCT pm.post_id, COUNT(*) as shared_citations
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_abt_citations'
            AND pm.meta_value LIKE %s
            AND p.post_type = 'abt_blog'
            AND p.post_status = 'publish'
            AND pm.post_id != %d
            GROUP BY pm.post_id
            ORDER BY shared_citations DESC
            LIMIT %d
        ", '%reference_id%', $post_id, $limit );

        $results = $wpdb->get_results( $query );
        
        if ( empty( $results ) ) {
            return array();
        }

        $post_ids = wp_list_pluck( $results, 'post_id' );
        
        return get_posts( array(
            'post_type' => 'abt_blog',
            'post__in' => $post_ids,
            'orderby' => 'post__in',
            'posts_per_page' => $limit
        ));
    }

    /**
     * Get most cited references
     *
     * @since 1.0.0
     * @param int $limit Number of references to return
     * @return array Array of reference data with citation counts
     */
    public static function get_most_cited_references( $limit = 10 ) {
        global $wpdb;

        $query = $wpdb->prepare( "
            SELECT p.ID, p.post_title, pm.meta_value as citation_count
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'abt_reference'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_abt_citation_count'
            AND pm.meta_value > 0
            ORDER BY CAST(pm.meta_value AS UNSIGNED) DESC
            LIMIT %d
        ", $limit );

        return $wpdb->get_results( $query );
    }

    /**
     * Search across academic content
     *
     * @since 1.0.0
     * @param string $search_term Search term
     * @param array $post_types Post types to search (default: academic posts and references)
     * @return array Search results
     */
    public static function search_academic_content( $search_term, $post_types = array( 'abt_blog', 'abt_reference' ) ) {
        if ( empty( $search_term ) ) {
            return array();
        }

        $search_term = sanitize_text_field( $search_term );

        $args = array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            's' => $search_term,
            'posts_per_page' => 50,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_abt_author',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_abt_publication',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_abt_doi',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                )
            )
        );

        return get_posts( $args );
    }

    /**
     * Get posts by taxonomy term
     *
     * @since 1.0.0
     * @param string $taxonomy Taxonomy name
     * @param string|array $terms Term slug(s)
     * @param array $args Additional query arguments
     * @return WP_Query Query object
     */
    public static function get_posts_by_taxonomy( $taxonomy, $terms, $args = array() ) {
        $defaults = array(
            'post_type' => 'abt_blog',
            'post_status' => 'publish',
            'posts_per_page' => 10
        );

        $args = wp_parse_args( $args, $defaults );

        $args['tax_query'] = array(
            array(
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $terms
            )
        );

        return new WP_Query( $args );
    }

    /**
     * Update citation counts for references
     *
     * @since 1.0.0
     * @param int $reference_id Reference ID (optional, updates all if not provided)
     * @return bool Success status
     */
    public static function update_citation_counts( $reference_id = null ) {
        global $wpdb;

        if ( $reference_id ) {
            // Update specific reference
            $count = $wpdb->get_var( $wpdb->prepare( "
                SELECT COUNT(*)
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = '_abt_citations'
                AND pm.meta_value LIKE %s
                AND p.post_status = 'publish'
            ", '%"reference_id";i:' . intval( $reference_id ) . '%' ) );

            return update_post_meta( $reference_id, '_abt_citation_count', intval( $count ) );
        } else {
            // Update all references
            $references = get_posts( array(
                'post_type' => 'abt_reference',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids'
            ));

            foreach ( $references as $ref_id ) {
                self::update_citation_counts( $ref_id );
            }

            return true;
        }
    }

    /**
     * Get query cache key
     *
     * @since 1.0.0
     * @param string $query_type Query type identifier
     * @param array $args Query arguments
     * @return string Cache key
     */
    private static function get_cache_key( $query_type, $args = array() ) {
        return 'abt_query_' . $query_type . '_' . md5( serialize( $args ) );
    }

    /**
     * Get cached query result
     *
     * @since 1.0.0
     * @param string $cache_key Cache key
     * @return mixed Cached data or false
     */
    private static function get_cached_result( $cache_key ) {
        return wp_cache_get( $cache_key, 'abt_queries' );
    }

    /**
     * Set cached query result
     *
     * @since 1.0.0
     * @param string $cache_key Cache key
     * @param mixed $data Data to cache
     * @param int $expiration Cache expiration in seconds (default: 1 hour)
     * @return bool Success status
     */
    private static function set_cached_result( $cache_key, $data, $expiration = 3600 ) {
        return wp_cache_set( $cache_key, $data, 'abt_queries', $expiration );
    }

    /**
     * Clear query cache
     *
     * @since 1.0.0
     * @param string $pattern Cache key pattern (optional)
     * @return bool Success status
     */
    public static function clear_cache( $pattern = null ) {
        if ( $pattern ) {
            // WordPress doesn't support pattern-based cache clearing natively
            // This would need a custom cache implementation
            return true;
        } else {
            // Clear all ABT query cache
            wp_cache_flush_group( 'abt_queries' );
            return true;
        }
    }
}