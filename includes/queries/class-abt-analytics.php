<?php
/**
 * Analytics Class for Academic Blogger's Toolkit
 *
 * Handles statistics and analytics queries for the plugin
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
 * ABT Analytics Class
 *
 * Provides statistical analysis and reporting for Academic Blogger's Toolkit
 */
class ABT_Analytics {

    /**
     * Get overall plugin statistics
     *
     * @since 1.0.0
     * @return array Statistics array
     */
    public static function get_overview_stats() {
        $cache_key = 'abt_overview_stats';
        $stats = wp_cache_get( $cache_key, 'abt_analytics' );

        if ( false === $stats ) {
            $stats = array(
                'total_posts' => self::get_total_academic_posts(),
                'total_references' => self::get_total_references(),
                'total_citations' => self::get_total_citations(),
                'total_footnotes' => self::get_total_footnotes(),
                'posts_with_citations' => self::get_posts_with_citations_count(),
                'average_citations_per_post' => self::get_average_citations_per_post(),
                'most_cited_reference' => self::get_most_cited_reference(),
                'recent_activity' => self::get_recent_activity()
            );

            wp_cache_set( $cache_key, $stats, 'abt_analytics', 3600 ); // Cache for 1 hour
        }

        return $stats;
    }

    /**
     * Get total number of academic blog posts
     *
     * @since 1.0.0
     * @return int Total posts
     */
    public static function get_total_academic_posts() {
        $count = wp_count_posts( 'abt_blog' );
        return isset( $count->publish ) ? intval( $count->publish ) : 0;
    }

    /**
     * Get total number of references
     *
     * @since 1.0.0
     * @return int Total references
     */
    public static function get_total_references() {
        $count = wp_count_posts( 'abt_reference' );
        return isset( $count->publish ) ? intval( $count->publish ) : 0;
    }

    /**
     * Get total number of citations across all posts
     *
     * @since 1.0.0
     * @return int Total citations
     */
    public static function get_total_citations() {
        global $wpdb;

        $total = $wpdb->get_var( "
            SELECT SUM(CAST(meta_value AS UNSIGNED))
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_abt_citation_count'
        " );

        return intval( $total );
    }

    /**
     * Get total number of footnotes across all posts
     *
     * @since 1.0.0
     * @return int Total footnotes
     */
    public static function get_total_footnotes() {
        global $wpdb;

        $total = $wpdb->get_var( "
            SELECT SUM(CAST(meta_value AS UNSIGNED))
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_abt_footnote_count'
        " );

        return intval( $total );
    }

    /**
     * Get number of posts that have citations
     *
     * @since 1.0.0
     * @return int Posts with citations count
     */
    public static function get_posts_with_citations_count() {
        global $wpdb;

        $count = $wpdb->get_var( "
            SELECT COUNT(DISTINCT pm.post_id)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_abt_citation_count'
            AND CAST(pm.meta_value AS UNSIGNED) > 0
            AND p.post_type = 'abt_blog'
            AND p.post_status = 'publish'
        " );

        return intval( $count );
    }

    /**
     * Get average number of citations per post
     *
     * @since 1.0.0
     * @return float Average citations
     */
    public static function get_average_citations_per_post() {
        $total_posts = self::get_total_academic_posts();
        $total_citations = self::get_total_citations();

        if ( $total_posts === 0 ) {
            return 0;
        }

        return round( $total_citations / $total_posts, 2 );
    }

    /**
     * Get the most cited reference
     *
     * @since 1.0.0
     * @return array|null Reference data
     */
    public static function get_most_cited_reference() {
        global $wpdb;

        $result = $wpdb->get_row( "
            SELECT p.ID, p.post_title, pm.meta_value as citation_count
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'abt_reference'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_abt_citation_count'
            AND pm.meta_value > 0
            ORDER BY CAST(pm.meta_value AS UNSIGNED) DESC
            LIMIT 1
        " );

        if ( $result ) {
            return array(
                'id' => intval( $result->ID ),
                'title' => $result->post_title,
                'citation_count' => intval( $result->citation_count )
            );
        }

        return null;
    }

    /**
     * Get recent activity (posts, references, citations)
     *
     * @since 1.0.0
     * @param int $days Number of days to look back (default: 30)
     * @return array Recent activity data
     */
    public static function get_recent_activity( $days = 30 ) {
        $date_query = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $recent_posts = get_posts( array(
            'post_type' => 'abt_blog',
            'post_status' => 'publish',
            'date_query' => array(
                array(
                    'after' => $date_query,
                    'inclusive' => true
                )
            ),
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        $recent_references = get_posts( array(
            'post_type' => 'abt_reference',
            'post_status' => 'publish',
            'date_query' => array(
                array(
                    'after' => $date_query,
                    'inclusive' => true
                )
            ),
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        return array(
            'recent_posts' => $recent_posts,
            'recent_references' => $recent_references,
            'posts_count' => count( $recent_posts ),
            'references_count' => count( $recent_references )
        );
    }

    /**
     * Get citation statistics by reference type
     *
     * @since 1.0.0
     * @return array Citation stats by type
     */
    public static function get_citation_stats_by_type() {
        global $wpdb;

        $results = $wpdb->get_results( "
            SELECT pm2.meta_value as reference_type, 
                   SUM(CAST(pm1.meta_value AS UNSIGNED)) as total_citations,
                   COUNT(DISTINCT p.ID) as reference_count
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_abt_citation_count'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_abt_reference_type'
            WHERE p.post_type = 'abt_reference'
            AND p.post_status = 'publish'
            GROUP BY pm2.meta_value
            ORDER BY total_citations DESC
        " );

        $stats = array();
        foreach ( $results as $result ) {
            $stats[] = array(
                'type' => $result->reference_type,
                'total_citations' => intval( $result->total_citations ),
                'reference_count' => intval( $result->reference_count ),
                'average_citations' => $result->reference_count > 0 ? 
                    round( $result->total_citations / $result->reference_count, 2 ) : 0
            );
        }

        return $stats;
    }

    /**
     * Get monthly posting statistics
     *
     * @since 1.0.0
     * @param int $months Number of months to include (default: 12)
     * @return array Monthly stats
     */
    public static function get_monthly_stats( $months = 12 ) {
        global $wpdb;

        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT 
                DATE_FORMAT(post_date, '%%Y-%%m') as month,
                COUNT(*) as post_count,
                SUM(CASE WHEN pm.meta_value > 0 THEN 1 ELSE 0 END) as posts_with_citations
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_abt_citation_count'
            WHERE p.post_type = 'abt_blog'
            AND p.post_status = 'publish'
            AND p.post_date >= DATE_SUB(NOW(), INTERVAL %d MONTH)
            GROUP BY DATE_FORMAT(post_date, '%%Y-%%m')
            ORDER BY month DESC
        ", $months ) );

        $stats = array();
        foreach ( $results as $result ) {
            $stats[] = array(
                'month' => $result->month,
                'post_count' => intval( $result->post_count ),
                'posts_with_citations' => intval( $result->posts_with_citations ),
                'percentage_with_citations' => $result->post_count > 0 ? 
                    round( ( $result->posts_with_citations / $result->post_count ) * 100, 1 ) : 0
            );
        }

        return $stats;
    }

    /**
     * Get top cited authors
     *
     * @since 1.0.0
     * @param int $limit Number of authors to return (default: 10)
     * @return array Top authors by citation count
     */
    public static function get_top_cited_authors( $limit = 10 ) {
        global $wpdb;

        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT 
                pm2.meta_value as author,
                SUM(CAST(pm1.meta_value AS UNSIGNED)) as total_citations,
                COUNT(DISTINCT p.ID) as reference_count
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_abt_citation_count'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_abt_author'
            WHERE p.post_type = 'abt_reference'
            AND p.post_status = 'publish'
            AND pm1.meta_value > 0
            AND pm2.meta_value != ''
            GROUP BY pm2.meta_value
            ORDER BY total_citations DESC
            LIMIT %d
        ", $limit ) );

        $authors = array();
        foreach ( $results as $result ) {
            $authors[] = array(
                'author' => $result->author,
                'total_citations' => intval( $result->total_citations ),
                'reference_count' => intval( $result->reference_count ),
                'average_citations' => $result->reference_count > 0 ? 
                    round( $result->total_citations / $result->reference_count, 2 ) : 0
            );
        }

        return $authors;
    }

    /**
     * Get taxonomy usage statistics
     *
     * @since 1.0.0
     * @param string $taxonomy Taxonomy name
     * @return array Taxonomy usage stats
     */
    public static function get_taxonomy_stats( $taxonomy ) {
        global $wpdb;

        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT 
                t.name,
                t.slug,
                tt.count,
                (SELECT COUNT(*) 
                 FROM {$wpdb->term_relationships} tr2
                 INNER JOIN {$wpdb->posts} p2 ON tr2.object_id = p2.ID
                 INNER JOIN {$wpdb->postmeta} pm ON p2.ID = pm.post_id
                 WHERE tr2.term_taxonomy_id = tt.term_taxonomy_id
                 AND pm.meta_key = '_abt_citation_count'
                 AND pm.meta_value > 0
                ) as posts_with_citations
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = %s
            AND tt.count > 0
            ORDER BY tt.count DESC
        ", $taxonomy ) );

        $stats = array();
        foreach ( $results as $result ) {
            $stats[] = array(
                'name' => $result->name,
                'slug' => $result->slug,
                'post_count' => intval( $result->count ),
                'posts_with_citations' => intval( $result->posts_with_citations ),
                'citation_percentage' => $result->count > 0 ? 
                    round( ( $result->posts_with_citations / $result->count ) * 100, 1 ) : 0
            );
        }

        return $stats;
    }

    /**
     * Get performance metrics for academic posts
     *
     * @since 1.0.0
     * @param int $post_id Specific post ID (optional)
     * @return array Performance metrics
     */
    public static function get_post_performance( $post_id = null ) {
        if ( $post_id ) {
            // Get metrics for specific post
            $citations = ABT_Query::get_post_citations( $post_id );
            $footnotes = ABT_Query::get_post_footnotes( $post_id );
            
            return array(
                'post_id' => $post_id,
                'citation_count' => count( $citations ),
                'footnote_count' => count( $footnotes ),
                'reference_diversity' => self::calculate_reference_diversity( $citations ),
                'academic_score' => self::calculate_academic_score( $post_id )
            );
        } else {
            // Get average metrics across all posts
            global $wpdb;
            
            $avg_stats = $wpdb->get_row( "
                SELECT 
                    AVG(CAST(pm1.meta_value AS UNSIGNED)) as avg_citations,
                    AVG(CAST(pm2.meta_value AS UNSIGNED)) as avg_footnotes
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_abt_citation_count'
                LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_abt_footnote_count'
                WHERE p.post_type = 'abt_blog'
                AND p.post_status = 'publish'
            " );

            return array(
                'average_citations' => $avg_stats ? round( $avg_stats->avg_citations, 2 ) : 0,
                'average_footnotes' => $avg_stats ? round( $avg_stats->avg_footnotes, 2 ) : 0,
                'total_posts_analyzed' => self::get_total_academic_posts()
            );
        }
    }

    /**
     * Calculate reference diversity for a post
     *
     * @since 1.0.0
     * @param array $citations Array of citations
     * @return float Diversity score (0-1)
     */
    private static function calculate_reference_diversity( $citations ) {
        if ( empty( $citations ) ) {
            return 0;
        }

        $types = array();
        foreach ( $citations as $citation ) {
            if ( isset( $citation['reference_data']['type'] ) ) {
                $type = $citation['reference_data']['type'];
                $types[ $type ] = isset( $types[ $type ] ) ? $types[ $type ] + 1 : 1;
            }
        }

        $total_citations = count( $citations );
        $unique_types = count( $types );
        
        if ( $unique_types <= 1 ) {
            return 0;
        }

        // Calculate Shannon diversity index
        $diversity = 0;
        foreach ( $types as $count ) {
            $proportion = $count / $total_citations;
            $diversity -= $proportion * log( $proportion );
        }

        // Normalize to 0-1 scale
        $max_diversity = log( $unique_types );
        return $max_diversity > 0 ? round( $diversity / $max_diversity, 3 ) : 0;
    }

    /**
     * Calculate academic score for a post
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return int Academic score (0-100)
     */
    private static function calculate_academic_score( $post_id ) {
        $citations = ABT_Query::get_post_citations( $post_id );
        $footnotes = ABT_Query::get_post_footnotes( $post_id );
        
        $citation_count = count( $citations );
        $footnote_count = count( $footnotes );
        $diversity = self::calculate_reference_diversity( $citations );
        
        // Calculate score based on multiple factors
        $score = 0;
        
        // Citation count (max 40 points)
        $score += min( $citation_count * 4, 40 );
        
        // Footnote count (max 20 points)
        $score += min( $footnote_count * 2, 20 );
        
        // Reference diversity (max 25 points)
        $score += $diversity * 25;
        
        // Check for DOI/PMID references (max 15 points)
        $verified_refs = 0;
        foreach ( $citations as $citation ) {
            if ( isset( $citation['reference_data'] ) ) {
                $ref_data = $citation['reference_data'];
                if ( ! empty( $ref_data['doi'] ) || ! empty( $ref_data['pmid'] ) ) {
                    $verified_refs++;
                }
            }
        }
        $score += min( $verified_refs * 3, 15 );
        
        return min( round( $score ), 100 );
    }

    /**
     * Clear analytics cache
     *
     * @since 1.0.0
     * @return bool Success status
     */
    public static function clear_cache() {
        wp_cache_flush_group( 'abt_analytics' );
        return true;
    }

    /**
     * Get export data for analytics
     *
     * @since 1.0.0
     * @param array $include Array of data types to include
     * @return array Export data
     */
    public static function get_export_data( $include = array( 'overview', 'monthly', 'citations' ) ) {
        $data = array(
            'exported_at' => current_time( 'mysql' ),
            'site_url' => get_site_url()
        );

        if ( in_array( 'overview', $include ) ) {
            $data['overview'] = self::get_overview_stats();
        }

        if ( in_array( 'monthly', $include ) ) {
            $data['monthly_stats'] = self::get_monthly_stats();
        }

        if ( in_array( 'citations', $include ) ) {
            $data['citation_stats'] = self::get_citation_stats_by_type();
        }

        if ( in_array( 'authors', $include ) ) {
            $data['top_authors'] = self::get_top_cited_authors();
        }

        return $data;
    }
}