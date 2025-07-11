<?php
/**
 * Sanitizer Class for Academic Blogger's Toolkit
 *
 * Handles data sanitization for the plugin
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
 * ABT Sanitizer Class
 *
 * Provides data sanitization methods for Academic Blogger's Toolkit
 */
class ABT_Sanitizer {

    /**
     * Sanitize reference data
     *
     * @since 1.0.0
     * @param array $data Reference data to sanitize
     * @return array Sanitized data
     */
    public static function sanitize_reference_data( $data ) {
        $sanitized = array();

        // Text fields
        $text_fields = array( 'title', 'author', 'editor', 'publication', 'publisher', 'location' );
        foreach ( $text_fields as $field ) {
            if ( isset( $data[ $field ] ) ) {
                $sanitized[ $field ] = self::sanitize_text_field( $data[ $field ] );
            }
        }

        // HTML content fields (abstracts, notes)
        $html_fields = array( 'abstract', 'notes' );
        foreach ( $html_fields as $field ) {
            if ( isset( $data[ $field ] ) ) {
                $sanitized[ $field ] = self::sanitize_html_content( $data[ $field ] );
            }
        }

        // Specific field sanitization
        if ( isset( $data['type'] ) ) {
            $sanitized['type'] = self::sanitize_reference_type( $data['type'] );
        }

        if ( isset( $data['year'] ) ) {
            $sanitized['year'] = self::sanitize_year( $data['year'] );
        }

        if ( isset( $data['volume'] ) ) {
            $sanitized['volume'] = self::sanitize_alphanumeric( $data['volume'] );
        }

        if ( isset( $data['issue'] ) ) {
            $sanitized['issue'] = self::sanitize_alphanumeric( $data['issue'] );
        }

        if ( isset( $data['pages'] ) ) {
            $sanitized['pages'] = self::sanitize_pages( $data['pages'] );
        }

        if ( isset( $data['doi'] ) ) {
            $sanitized['doi'] = self::sanitize_doi( $data['doi'] );
        }

        if ( isset( $data['pmid'] ) ) {
            $sanitized['pmid'] = self::sanitize_pmid( $data['pmid'] );
        }

        if ( isset( $data['isbn'] ) ) {
            $sanitized['isbn'] = self::sanitize_isbn( $data['isbn'] );
        }

        if ( isset( $data['url'] ) ) {
            $sanitized['url'] = self::sanitize_url( $data['url'] );
        }

        // Remove empty values
        return array_filter( $sanitized, function( $value ) {
            return ! empty( $value );
        });
    }

    /**
     * Sanitize citation data
     *
     * @since 1.0.0
     * @param array $data Citation data to sanitize
     * @return array Sanitized data
     */
    public static function sanitize_citation_data( $data ) {
        $sanitized = array();

        if ( isset( $data['reference_id'] ) ) {
            $sanitized['reference_id'] = absint( $data['reference_id'] );
        }

        if ( isset( $data['style'] ) ) {
            $sanitized['style'] = self::sanitize_citation_style( $data['style'] );
        }

        if ( isset( $data['pages'] ) ) {
            $sanitized['pages'] = self::sanitize_pages( $data['pages'] );
        }

        if ( isset( $data['prefix'] ) ) {
            $sanitized['prefix'] = self::sanitize_text_field( $data['prefix'] );
        }

        if ( isset( $data['suffix'] ) ) {
            $sanitized['suffix'] = self::sanitize_text_field( $data['suffix'] );
        }

        if ( isset( $data['suppress_author'] ) ) {
            $sanitized['suppress_author'] = (bool) $data['suppress_author'];
        }

        return $sanitized;
    }

    /**
     * Sanitize footnote data
     *
     * @since 1.0.0
     * @param array $data Footnote data to sanitize
     * @return array Sanitized data
     */
    public static function sanitize_footnote_data( $data ) {
        $sanitized = array();

        if ( isset( $data['text'] ) ) {
            $sanitized['text'] = self::sanitize_html_content( $data['text'] );
        }

        if ( isset( $data['order'] ) ) {
            $sanitized['order'] = absint( $data['order'] );
        }

        if ( isset( $data['id'] ) ) {
            $sanitized['id'] = self::sanitize_html_id( $data['id'] );
        }

        return $sanitized;
    }

    /**
     * Sanitize academic blog post data
     *
     * @since 1.0.0
     * @param array $data Post data to sanitize
     * @return array Sanitized data
     */
    public static function sanitize_blog_post_data( $data ) {
        $sanitized = array();

        if ( isset( $data['title'] ) ) {
            $sanitized['title'] = self::sanitize_text_field( $data['title'] );
        }

        if ( isset( $data['content'] ) ) {
            $sanitized['content'] = self::sanitize_post_content( $data['content'] );
        }

        if ( isset( $data['excerpt'] ) ) {
            $sanitized['excerpt'] = self::sanitize_html_content( $data['excerpt'] );
        }

        if ( isset( $data['academic_level'] ) ) {
            $sanitized['academic_level'] = self::sanitize_academic_level( $data['academic_level'] );
        }

        if ( isset( $data['peer_reviewed'] ) ) {
            $sanitized['peer_reviewed'] = self::sanitize_peer_review_status( $data['peer_reviewed'] );
        }

        if ( isset( $data['keywords'] ) ) {
            $sanitized['keywords'] = self::sanitize_keywords( $data['keywords'] );
        }

        return $sanitized;
    }

    /**
     * Sanitize import data
     *
     * @since 1.0.0
     * @param array $data Import data to sanitize
     * @param string $format Import format
     * @return array Sanitized data
     */
    public static function sanitize_import_data( $data, $format ) {
        $sanitized = array();

        foreach ( $data as $index => $record ) {
            switch ( $format ) {
                case 'ris':
                    $sanitized[ $index ] = self::sanitize_ris_record( $record );
                    break;
                case 'bibtex':
                    $sanitized[ $index ] = self::sanitize_bibtex_record( $record );
                    break;
                case 'csv':
                    $sanitized[ $index ] = self::sanitize_csv_record( $record );
                    break;
                default:
                    $sanitized[ $index ] = self::sanitize_reference_data( $record );
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize text field
     *
     * @since 1.0.0
     * @param string $text Text to sanitize
     * @return string Sanitized text
     */
    public static function sanitize_text_field( $text ) {
        return sanitize_text_field( trim( $text ) );
    }

    /**
     * Sanitize HTML content (allow basic formatting)
     *
     * @since 1.0.0
     * @param string $content HTML content to sanitize
     * @return string Sanitized content
     */
    public static function sanitize_html_content( $content ) {
        $allowed_html = array(
            'p' => array(),
            'br' => array(),
            'strong' => array(),
            'b' => array(),
            'em' => array(),
            'i' => array(),
            'u' => array(),
            'sup' => array(),
            'sub' => array(),
            'a' => array(
                'href' => array(),
                'title' => array(),
                'target' => array()
            ),
            'blockquote' => array(),
            'ul' => array(),
            'ol' => array(),
            'li' => array()
        );

        return wp_kses( trim( $content ), $allowed_html );
    }

    /**
     * Sanitize post content (WordPress editor content)
     *
     * @since 1.0.0
     * @param string $content Post content
     * @return string Sanitized content
     */
    public static function sanitize_post_content( $content ) {
        // Use WordPress's post content filtering
        return wp_kses_post( $content );
    }

    /**
     * Sanitize reference type
     *
     * @since 1.0.0
     * @param string $type Reference type
     * @return string Sanitized type
     */
    public static function sanitize_reference_type( $type ) {
        $valid_types = array(
            'journal', 'book', 'chapter', 'conference', 'thesis', 
            'report', 'website', 'newspaper', 'magazine', 'other'
        );

        $type = sanitize_key( $type );
        return in_array( $type, $valid_types ) ? $type : 'other';
    }

    /**
     * Sanitize year
     *
     * @since 1.0.0
     * @param mixed $year Year value
     * @return int|null Sanitized year
     */
    public static function sanitize_year( $year ) {
        $year = absint( $year );
        $current_year = intval( date( 'Y' ) );
        
        if ( $year >= 1000 && $year <= ( $current_year + 5 ) ) {
            return $year;
        }
        
        return null;
    }

    /**
     * Sanitize alphanumeric field (volume, issue)
     *
     * @since 1.0.0
     * @param string $value Alphanumeric value
     * @return string Sanitized value
     */
    public static function sanitize_alphanumeric( $value ) {
        return preg_replace( '/[^a-zA-Z0-9\-\.\/]/', '', trim( $value ) );
    }

    /**
     * Sanitize pages format
     *
     * @since 1.0.0
     * @param string $pages Pages string
     * @return string Sanitized pages
     */
    public static function sanitize_pages( $pages ) {
        // Allow digits, hyphens, commas, and spaces
        $pages = preg_replace( '/[^\d\-,\s]/', '', trim( $pages ) );
        // Clean up multiple spaces and normalize separators
        $pages = preg_replace( '/\s+/', ' ', $pages );
        $pages = str_replace( ' - ', '-', $pages );
        $pages = str_replace( ' , ', ', ', $pages );
        
        return trim( $pages );
    }

    /**
     * Sanitize DOI
     *
     * @since 1.0.0
     * @param string $doi DOI string
     * @return string|null Sanitized DOI
     */
    public static function sanitize_doi( $doi ) {
        // Remove common prefixes and clean
        $doi = preg_replace( '/^(https?:\/\/)?(dx\.)?doi\.org\//', '', trim( $doi ) );
        $doi = preg_replace( '/^doi:?\s*/i', '', $doi );
        
        // Extract clean DOI
        $clean_doi = ABT_Utils::extract_doi( $doi );
        return $clean_doi ? $clean_doi : null;
    }

    /**
     * Sanitize PMID
     *
     * @since 1.0.0
     * @param string $pmid PMID string
     * @return string|null Sanitized PMID
     */
    public static function sanitize_pmid( $pmid ) {
        $clean_pmid = ABT_Utils::extract_pmid( $pmid );
        return $clean_pmid ? $clean_pmid : null;
    }

    /**
     * Sanitize ISBN
     *
     * @since 1.0.0
     * @param string $isbn ISBN string
     * @return string|null Sanitized ISBN
     */
    public static function sanitize_isbn( $isbn ) {
        $clean_isbn = ABT_Utils::extract_isbn( $isbn );
        return $clean_isbn ? $clean_isbn : null;
    }

    /**
     * Sanitize URL
     *
     * @since 1.0.0
     * @param string $url URL string
     * @return string|null Sanitized URL
     */
    public static function sanitize_url( $url ) {
        $url = esc_url_raw( trim( $url ) );
        return ! empty( $url ) ? $url : null;
    }

    /**
     * Sanitize citation style
     *
     * @since 1.0.0
     * @param string $style Citation style
     * @return string Sanitized style
     */
    public static function sanitize_citation_style( $style ) {
        $valid_styles = array( 'apa', 'mla', 'chicago', 'harvard', 'ieee', 'vancouver' );
        $style = sanitize_key( $style );
        return in_array( $style, $valid_styles ) ? $style : 'apa';
    }

    /**
     * Sanitize academic level
     *
     * @since 1.0.0
     * @param string $level Academic level
     * @return string Sanitized level
     */
    public static function sanitize_academic_level( $level ) {
        $valid_levels = array( 'undergraduate', 'graduate', 'postgraduate', 'professional', 'general' );
        $level = sanitize_key( $level );
        return in_array( $level, $valid_levels ) ? $level : 'general';
    }

    /**
     * Sanitize peer review status
     *
     * @since 1.0.0
     * @param string $status Peer review status
     * @return string Sanitized status
     */
    public static function sanitize_peer_review_status( $status ) {
        $valid_statuses = array( 'yes', 'no', 'pending' );
        $status = sanitize_key( $status );
        return in_array( $status, $valid_statuses ) ? $status : 'no';
    }

    /**
     * Sanitize keywords
     *
     * @since 1.0.0
     * @param string|array $keywords Keywords string or array
     * @return array Sanitized keywords array
     */
    public static function sanitize_keywords( $keywords ) {
        if ( is_string( $keywords ) ) {
            $keywords = explode( ',', $keywords );
        }

        if ( ! is_array( $keywords ) ) {
            return array();
        }

        $sanitized = array();
        foreach ( $keywords as $keyword ) {
            $keyword = trim( sanitize_text_field( $keyword ) );
            if ( ! empty( $keyword ) ) {
                $sanitized[] = $keyword;
            }
        }

        return array_unique( $sanitized );
    }

    /**
     * Sanitize HTML ID
     *
     * @since 1.0.0
     * @param string $id HTML ID
     * @return string Sanitized ID
     */
    public static function sanitize_html_id( $id ) {
        return sanitize_html_class( $id );
    }

    /**
     * Sanitize RIS record
     *
     * @since 1.0.0
     * @param array $record RIS record
     * @return array Sanitized record
     */
    private static function sanitize_ris_record( $record ) {
        $sanitized = array();

        // Map common RIS fields
        $field_map = array(
            'TY' => 'type',
            'TI' => 'title',
            'T1' => 'title',
            'AU' => 'author',
            'A1' => 'author',
            'JO' => 'publication',
            'T2' => 'publication',
            'PY' => 'year',
            'Y1' => 'year',
            'VL' => 'volume',
            'IS' => 'issue',
            'SP' => 'start_page',
            'EP' => 'end_page',
            'DO' => 'doi',
            'UR' => 'url',
            'AB' => 'abstract',
            'N2' => 'abstract'
        );

        foreach ( $record as $key => $value ) {
            if ( isset( $field_map[ $key ] ) ) {
                $field = $field_map[ $key ];
                
                switch ( $field ) {
                    case 'type':
                        $sanitized[ $field ] = self::map_ris_type_to_reference_type( $value );
                        break;
                    case 'year':
                        $sanitized[ $field ] = self::sanitize_year( $value );
                        break;
                    case 'doi':
                        $sanitized[ $field ] = self::sanitize_doi( $value );
                        break;
                    case 'url':
                        $sanitized[ $field ] = self::sanitize_url( $value );
                        break;
                    case 'abstract':
                        $sanitized[ $field ] = self::sanitize_html_content( $value );
                        break;
                    default:
                        $sanitized[ $field ] = self::sanitize_text_field( $value );
                }
            }
        }

        // Combine start and end pages
        if ( isset( $sanitized['start_page'] ) || isset( $sanitized['end_page'] ) ) {
            $pages = '';
            if ( isset( $sanitized['start_page'] ) ) {
                $pages = $sanitized['start_page'];
                if ( isset( $sanitized['end_page'] ) ) {
                    $pages .= '-' . $sanitized['end_page'];
                }
            } elseif ( isset( $sanitized['end_page'] ) ) {
                $pages = $sanitized['end_page'];
            }
            $sanitized['pages'] = $pages;
            unset( $sanitized['start_page'], $sanitized['end_page'] );
        }

        return $sanitized;
    }

    /**
     * Sanitize BibTeX record
     *
     * @since 1.0.0
     * @param array $record BibTeX record
     * @return array Sanitized record
     */
    private static function sanitize_bibtex_record( $record ) {
        $sanitized = array();

        // Map BibTeX entry types
        if ( isset( $record['type'] ) ) {
            $sanitized['type'] = self::map_bibtex_type_to_reference_type( $record['type'] );
        }

        // Standard BibTeX fields
        $field_map = array(
            'title' => 'title',
            'author' => 'author',
            'journal' => 'publication',
            'booktitle' => 'publication',
            'year' => 'year',
            'volume' => 'volume',
            'number' => 'issue',
            'pages' => 'pages',
            'doi' => 'doi',
            'url' => 'url',
            'abstract' => 'abstract',
            'publisher' => 'publisher',
            'address' => 'location'
        );

        foreach ( $record as $key => $value ) {
            if ( isset( $field_map[ $key ] ) ) {
                $field = $field_map[ $key ];
                
                switch ( $field ) {
                    case 'year':
                        $sanitized[ $field ] = self::sanitize_year( $value );
                        break;
                    case 'doi':
                        $sanitized[ $field ] = self::sanitize_doi( $value );
                        break;
                    case 'url':
                        $sanitized[ $field ] = self::sanitize_url( $value );
                        break;
                    case 'abstract':
                        $sanitized[ $field ] = self::sanitize_html_content( $value );
                        break;
                    default:
                        $sanitized[ $field ] = self::sanitize_text_field( $value );
                }
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize CSV record
     *
     * @since 1.0.0
     * @param array $record CSV record
     * @return array Sanitized record
     */
    private static function sanitize_csv_record( $record ) {
        // CSV records are already in our format, just sanitize
        return self::sanitize_reference_data( $record );
    }

    /**
     * Map RIS type to reference type
     *
     * @since 1.0.0
     * @param string $ris_type RIS type code
     * @return string Reference type
     */
    private static function map_ris_type_to_reference_type( $ris_type ) {
        $type_map = array(
            'JOUR' => 'journal',
            'BOOK' => 'book',
            'CHAP' => 'chapter',
            'CONF' => 'conference',
            'THES' => 'thesis',
            'RPRT' => 'report',
            'ELEC' => 'website',
            'NEWS' => 'newspaper',
            'MGZN' => 'magazine',
            'GEN' => 'other'
        );

        return isset( $type_map[ $ris_type ] ) ? $type_map[ $ris_type ] : 'other';
    }

    /**
     * Map BibTeX type to reference type
     *
     * @since 1.0.0
     * @param string $bibtex_type BibTeX entry type
     * @return string Reference type
     */
    private static function map_bibtex_type_to_reference_type( $bibtex_type ) {
        $type_map = array(
            'article' => 'journal',
            'book' => 'book',
            'incollection' => 'chapter',
            'inproceedings' => 'conference',
            'proceedings' => 'conference',
            'phdthesis' => 'thesis',
            'mastersthesis' => 'thesis',
            'techreport' => 'report',
            'misc' => 'other',
            'unpublished' => 'other'
        );

        return isset( $type_map[ $bibtex_type ] ) ? $type_map[ $bibtex_type ] : 'other';
    }

    /**
     * Sanitize array recursively
     *
     * @since 1.0.0
     * @param array $array Array to sanitize
     * @param string $sanitize_function Sanitization function to use
     * @return array Sanitized array
     */
    public static function sanitize_array( $array, $sanitize_function = 'sanitize_text_field' ) {
        $sanitized = array();

        foreach ( $array as $key => $value ) {
            $key = sanitize_key( $key );
            
            if ( is_array( $value ) ) {
                $sanitized[ $key ] = self::sanitize_array( $value, $sanitize_function );
            } else {
                $sanitized[ $key ] = call_user_func( $sanitize_function, $value );
            }
        }

        return $sanitized;
    }
}