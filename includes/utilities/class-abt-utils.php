<?php
/**
 * Utilities Class for Academic Blogger's Toolkit
 *
 * Provides general utility functions for the plugin
 *
 * @package Academic_Bloggers_Toolkit
 * @subpackage Utilities
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ABT Utils Class
 *
 * General utility functions for Academic Blogger's Toolkit
 */
class ABT_Utils {

    /**
     * Generate unique citation ID
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @param int $reference_id Reference ID
     * @return string Unique citation ID
     */
    public static function generate_citation_id( $post_id, $reference_id ) {
        return 'abt_cite_' . $post_id . '_' . $reference_id . '_' . uniqid();
    }

    /**
     * Generate unique footnote ID
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @param int $number Footnote number
     * @return string Unique footnote ID
     */
    public static function generate_footnote_id( $post_id, $number ) {
        return 'abt_footnote_' . $post_id . '_' . $number;
    }

    /**
     * Format author names for citations
     *
     * @since 1.0.0
     * @param string $authors Author string
     * @param string $format Format type ('apa', 'mla', 'chicago')
     * @return string Formatted author names
     */
    public static function format_authors( $authors, $format = 'apa' ) {
        if ( empty( $authors ) ) {
            return '';
        }

        // Split authors by common delimiters
        $author_array = preg_split( '/[,;]|\sand\s|\&/', $authors );
        $author_array = array_map( 'trim', $author_array );
        $author_array = array_filter( $author_array );

        if ( empty( $author_array ) ) {
            return $authors;
        }

        switch ( $format ) {
            case 'apa':
                return self::format_authors_apa( $author_array );
            case 'mla':
                return self::format_authors_mla( $author_array );
            case 'chicago':
                return self::format_authors_chicago( $author_array );
            default:
                return implode( ', ', $author_array );
        }
    }

    /**
     * Format authors for APA style
     *
     * @since 1.0.0
     * @param array $authors Array of author names
     * @return string APA formatted authors
     */
    private static function format_authors_apa( $authors ) {
        $count = count( $authors );
        
        if ( $count === 1 ) {
            return self::format_single_author_apa( $authors[0] );
        } elseif ( $count === 2 ) {
            return self::format_single_author_apa( $authors[0] ) . ', & ' . self::format_single_author_apa( $authors[1] );
        } elseif ( $count <= 7 ) {
            $formatted = array();
            foreach ( $authors as $author ) {
                $formatted[] = self::format_single_author_apa( $author );
            }
            $last_author = array_pop( $formatted );
            return implode( ', ', $formatted ) . ', & ' . $last_author;
        } else {
            // More than 7 authors - use et al.
            $formatted = array();
            for ( $i = 0; $i < 6; $i++ ) {
                $formatted[] = self::format_single_author_apa( $authors[ $i ] );
            }
            return implode( ', ', $formatted ) . ', ... ' . self::format_single_author_apa( end( $authors ) );
        }
    }

    /**
     * Format single author for APA style (Last, F. M.)
     *
     * @since 1.0.0
     * @param string $author Author name
     * @return string APA formatted author
     */
    private static function format_single_author_apa( $author ) {
        $author = trim( $author );
        
        // Check if already in Last, F. format
        if ( preg_match( '/^[^,]+,\s*[A-Z]\.?(\s*[A-Z]\.?)*$/', $author ) ) {
            return $author;
        }

        // Split by spaces
        $parts = explode( ' ', $author );
        if ( count( $parts ) < 2 ) {
            return $author; // Return as is if can't parse
        }

        $last_name = array_pop( $parts );
        $initials = array();
        
        foreach ( $parts as $part ) {
            if ( ! empty( $part ) ) {
                $initials[] = strtoupper( substr( $part, 0, 1 ) ) . '.';
            }
        }

        return $last_name . ', ' . implode( ' ', $initials );
    }

    /**
     * Format authors for MLA style
     *
     * @since 1.0.0
     * @param array $authors Array of author names
     * @return string MLA formatted authors
     */
    private static function format_authors_mla( $authors ) {
        $count = count( $authors );
        
        if ( $count === 1 ) {
            return self::format_single_author_mla( $authors[0] );
        } elseif ( $count === 2 ) {
            return self::format_single_author_mla( $authors[0] ) . ', and ' . $authors[1];
        } else {
            $formatted = array( self::format_single_author_mla( $authors[0] ) );
            for ( $i = 1; $i < $count - 1; $i++ ) {
                $formatted[] = $authors[ $i ];
            }
            $formatted[] = 'and ' . end( $authors );
            return implode( ', ', $formatted );
        }
    }

    /**
     * Format single author for MLA style (Last, First)
     *
     * @since 1.0.0
     * @param string $author Author name
     * @return string MLA formatted author
     */
    private static function format_single_author_mla( $author ) {
        $author = trim( $author );
        
        // Check if already in Last, First format
        if ( strpos( $author, ',' ) !== false ) {
            return $author;
        }

        // Split by spaces
        $parts = explode( ' ', $author );
        if ( count( $parts ) < 2 ) {
            return $author;
        }

        $last_name = array_pop( $parts );
        $first_names = implode( ' ', $parts );

        return $last_name . ', ' . $first_names;
    }

    /**
     * Format authors for Chicago style
     *
     * @since 1.0.0
     * @param array $authors Array of author names
     * @return string Chicago formatted authors
     */
    private static function format_authors_chicago( $authors ) {
        // Chicago style is similar to MLA for multiple authors
        return self::format_authors_mla( $authors );
    }

    /**
     * Extract DOI from various formats
     *
     * @since 1.0.0
     * @param string $input DOI string or URL
     * @return string|false Clean DOI or false if not found
     */
    public static function extract_doi( $input ) {
        if ( empty( $input ) ) {
            return false;
        }

        // Remove whitespace
        $input = trim( $input );

        // DOI regex pattern
        $doi_pattern = '/10\.\d{4,}\/[^\s]+/';

        // Try to extract DOI
        if ( preg_match( $doi_pattern, $input, $matches ) ) {
            return $matches[0];
        }

        return false;
    }

    /**
     * Extract PMID from various formats
     *
     * @since 1.0.0
     * @param string $input PMID string or URL
     * @return string|false Clean PMID or false if not found
     */
    public static function extract_pmid( $input ) {
        if ( empty( $input ) ) {
            return false;
        }

        // Remove whitespace
        $input = trim( $input );

        // PMID patterns
        $patterns = array(
            '/(?:PMID:?\s*)?(\d{7,8})/',  // PMID: followed by 7-8 digits
            '/pubmed\/(\d{7,8})/',        // PubMed URL
        );

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $input, $matches ) ) {
                return $matches[1];
            }
        }

        return false;
    }

    /**
     * Extract ISBN from various formats
     *
     * @since 1.0.0
     * @param string $input ISBN string
     * @return string|false Clean ISBN or false if not found
     */
    public static function extract_isbn( $input ) {
        if ( empty( $input ) ) {
            return false;
        }

        // Remove common prefixes and clean
        $input = preg_replace( '/^isbn:?\s*/i', '', trim( $input ) );
        $input = preg_replace( '/[^\dX]/i', '', $input );

        // Check if valid ISBN-10 or ISBN-13
        if ( strlen( $input ) === 10 || strlen( $input ) === 13 ) {
            return strtoupper( $input );
        }

        return false;
    }

    /**
     * Validate URL
     *
     * @since 1.0.0
     * @param string $url URL to validate
     * @return bool True if valid URL
     */
    public static function is_valid_url( $url ) {
        return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
    }

    /**
     * Generate slug from title
     *
     * @since 1.0.0
     * @param string $title Title to convert
     * @return string Slug
     */
    public static function generate_slug( $title ) {
        return sanitize_title( $title );
    }

    /**
     * Convert date to various formats
     *
     * @since 1.0.0
     * @param string $date Date string
     * @param string $format Target format
     * @return string|false Formatted date or false on failure
     */
    public static function format_date( $date, $format = 'Y-m-d' ) {
        if ( empty( $date ) ) {
            return false;
        }

        $timestamp = strtotime( $date );
        if ( $timestamp === false ) {
            return false;
        }

        return date( $format, $timestamp );
    }

    /**
     * Truncate text with proper word boundaries
     *
     * @since 1.0.0
     * @param string $text Text to truncate
     * @param int $length Maximum length
     * @param string $suffix Suffix to add if truncated
     * @return string Truncated text
     */
    public static function truncate_text( $text, $length = 100, $suffix = '...' ) {
        if ( strlen( $text ) <= $length ) {
            return $text;
        }

        $truncated = substr( $text, 0, $length );
        $last_space = strrpos( $truncated, ' ' );
        
        if ( $last_space !== false ) {
            $truncated = substr( $truncated, 0, $last_space );
        }

        return $truncated . $suffix;
    }

    /**
     * Clean and normalize reference data
     *
     * @since 1.0.0
     * @param array $data Reference data array
     * @return array Cleaned data
     */
    public static function clean_reference_data( $data ) {
        $cleaned = array();

        $fields = array( 'title', 'author', 'publication', 'year', 'volume', 'issue', 'pages', 'doi', 'pmid', 'isbn', 'url' );

        foreach ( $fields as $field ) {
            if ( isset( $data[ $field ] ) ) {
                $value = trim( $data[ $field ] );
                if ( ! empty( $value ) ) {
                    $cleaned[ $field ] = $value;
                }
            }
        }

        // Clean specific fields
        if ( isset( $cleaned['doi'] ) ) {
            $clean_doi = self::extract_doi( $cleaned['doi'] );
            if ( $clean_doi ) {
                $cleaned['doi'] = $clean_doi;
            } else {
                unset( $cleaned['doi'] );
            }
        }

        if ( isset( $cleaned['pmid'] ) ) {
            $clean_pmid = self::extract_pmid( $cleaned['pmid'] );
            if ( $clean_pmid ) {
                $cleaned['pmid'] = $clean_pmid;
            } else {
                unset( $cleaned['pmid'] );
            }
        }

        if ( isset( $cleaned['isbn'] ) ) {
            $clean_isbn = self::extract_isbn( $cleaned['isbn'] );
            if ( $clean_isbn ) {
                $cleaned['isbn'] = $clean_isbn;
            } else {
                unset( $cleaned['isbn'] );
            }
        }

        if ( isset( $cleaned['url'] ) && ! self::is_valid_url( $cleaned['url'] ) ) {
            unset( $cleaned['url'] );
        }

        return $cleaned;
    }

    /**
     * Generate bibliographic hash for duplicate detection
     *
     * @since 1.0.0
     * @param array $reference_data Reference data
     * @return string Hash string
     */
    public static function generate_reference_hash( $reference_data ) {
        $key_fields = array( 'title', 'author', 'year', 'publication' );
        $hash_data = array();

        foreach ( $key_fields as $field ) {
            if ( isset( $reference_data[ $field ] ) ) {
                $hash_data[] = strtolower( trim( $reference_data[ $field ] ) );
            }
        }

        return md5( implode( '|', $hash_data ) );
    }

    /**
     * Convert reference type to human readable format
     *
     * @since 1.0.0
     * @param string $type Reference type
     * @return string Human readable type
     */
    public static function get_reference_type_label( $type ) {
        $types = array(
            'journal' => __( 'Journal Article', 'academic-bloggers-toolkit' ),
            'book' => __( 'Book', 'academic-bloggers-toolkit' ),
            'chapter' => __( 'Book Chapter', 'academic-bloggers-toolkit' ),
            'conference' => __( 'Conference Paper', 'academic-bloggers-toolkit' ),
            'thesis' => __( 'Thesis/Dissertation', 'academic-bloggers-toolkit' ),
            'report' => __( 'Report', 'academic-bloggers-toolkit' ),
            'website' => __( 'Website', 'academic-bloggers-toolkit' ),
            'newspaper' => __( 'Newspaper Article', 'academic-bloggers-toolkit' ),
            'magazine' => __( 'Magazine Article', 'academic-bloggers-toolkit' ),
            'other' => __( 'Other', 'academic-bloggers-toolkit' )
        );

        return isset( $types[ $type ] ) ? $types[ $type ] : ucfirst( $type );
    }

    /**
     * Get file extension from filename or URL
     *
     * @since 1.0.0
     * @param string $filename Filename or URL
     * @return string File extension
     */
    public static function get_file_extension( $filename ) {
        return strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
    }

    /**
     * Check if string is JSON
     *
     * @since 1.0.0
     * @param string $string String to check
     * @return bool True if valid JSON
     */
    public static function is_json( $string ) {
        json_decode( $string );
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Convert array to CSV string
     *
     * @since 1.0.0
     * @param array $data Array data
     * @param array $headers Optional headers
     * @return string CSV string
     */
    public static function array_to_csv( $data, $headers = null ) {
        if ( empty( $data ) ) {
            return '';
        }

        $output = fopen( 'php://temp', 'r+' );
        
        if ( $headers ) {
            fputcsv( $output, $headers );
        }

        foreach ( $data as $row ) {
            fputcsv( $output, $row );
        }

        rewind( $output );
        $csv = stream_get_contents( $output );
        fclose( $output );

        return $csv;
    }

    /**
     * Log debug information
     *
     * @since 1.0.0
     * @param mixed $message Message to log
     * @param string $level Log level (debug, info, warning, error)
     */
    public static function log( $message, $level = 'debug' ) {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return;
        }

        $log_message = '[ABT] ';
        
        if ( is_array( $message ) || is_object( $message ) ) {
            $log_message .= print_r( $message, true );
        } else {
            $log_message .= $message;
        }

        error_log( $log_message );
    }

    /**
     * Get plugin version
     *
     * @since 1.0.0
     * @return string Plugin version
     */
    public static function get_plugin_version() {
        return defined( 'ABT_VERSION' ) ? ABT_VERSION : '1.0.0';
    }

    /**
     * Get WordPress version compatibility
     *
     * @since 1.0.0
     * @param string $min_version Minimum required version
     * @return bool True if compatible
     */
    public static function is_wp_version_compatible( $min_version = '5.0' ) {
        global $wp_version;
        return version_compare( $wp_version, $min_version, '>=' );
    }

    /**
     * Get PHP version compatibility
     *
     * @since 1.0.0
     * @param string $min_version Minimum required version
     * @return bool True if compatible
     */
    public static function is_php_version_compatible( $min_version = '7.4' ) {
        return version_compare( PHP_VERSION, $min_version, '>=' );
    }

    /**
     * Check if current user can manage academic content
     *
     * @since 1.0.0
     * @return bool True if user has capability
     */
    public static function current_user_can_manage_academic_content() {
        return current_user_can( 'edit_abt_blogs' ) || current_user_can( 'manage_options' );
    }

    /**
     * Get default citation style
     *
     * @since 1.0.0
     * @return string Default citation style
     */
    public static function get_default_citation_style() {
        return get_option( 'abt_default_citation_style', 'apa' );
    }

    /**
     * Format file size
     *
     * @since 1.0.0
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    public static function format_file_size( $bytes ) {
        $units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
        
        for ( $i = 0; $bytes > 1024 && $i < count( $units ) - 1; $i++ ) {
            $bytes /= 1024;
        }

        return round( $bytes, 2 ) . ' ' . $units[ $i ];
    }
}