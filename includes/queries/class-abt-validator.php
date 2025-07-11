<?php
/**
 * Validator Class for Academic Blogger's Toolkit
 *
 * Handles data validation for the plugin
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
 * ABT Validator Class
 *
 * Provides data validation methods for Academic Blogger's Toolkit
 */
class ABT_Validator {

    /**
     * Validation errors
     *
     * @since 1.0.0
     * @var array
     */
    private static $errors = array();

    /**
     * Validate reference data
     *
     * @since 1.0.0
     * @param array $data Reference data to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate_reference_data( $data ) {
        self::$errors = array();

        // Required fields
        $required_fields = array( 'title', 'type' );
        
        foreach ( $required_fields as $field ) {
            if ( empty( $data[ $field ] ) ) {
                self::$errors[] = sprintf( 
                    __( 'Field "%s" is required.', 'academic-bloggers-toolkit' ), 
                    $field 
                );
            }
        }

        // Validate title length
        if ( isset( $data['title'] ) && strlen( $data['title'] ) > 500 ) {
            self::$errors[] = __( 'Title must be 500 characters or less.', 'academic-bloggers-toolkit' );
        }

        // Validate reference type
        if ( isset( $data['type'] ) && ! self::is_valid_reference_type( $data['type'] ) ) {
            self::$errors[] = __( 'Invalid reference type.', 'academic-bloggers-toolkit' );
        }

        // Validate year
        if ( isset( $data['year'] ) && ! self::is_valid_year( $data['year'] ) ) {
            self::$errors[] = __( 'Year must be a valid 4-digit year between 1000 and current year + 5.', 'academic-bloggers-toolkit' );
        }

        // Validate DOI
        if ( isset( $data['doi'] ) && ! empty( $data['doi'] ) && ! self::is_valid_doi( $data['doi'] ) ) {
            self::$errors[] = __( 'DOI format is invalid.', 'academic-bloggers-toolkit' );
        }

        // Validate PMID
        if ( isset( $data['pmid'] ) && ! empty( $data['pmid'] ) && ! self::is_valid_pmid( $data['pmid'] ) ) {
            self::$errors[] = __( 'PMID must be a valid 7-8 digit number.', 'academic-bloggers-toolkit' );
        }

        // Validate ISBN
        if ( isset( $data['isbn'] ) && ! empty( $data['isbn'] ) && ! self::is_valid_isbn( $data['isbn'] ) ) {
            self::$errors[] = __( 'ISBN format is invalid.', 'academic-bloggers-toolkit' );
        }

        // Validate URL
        if ( isset( $data['url'] ) && ! empty( $data['url'] ) && ! self::is_valid_url( $data['url'] ) ) {
            self::$errors[] = __( 'URL format is invalid.', 'academic-bloggers-toolkit' );
        }

        // Validate pages format
        if ( isset( $data['pages'] ) && ! empty( $data['pages'] ) && ! self::is_valid_pages( $data['pages'] ) ) {
            self::$errors[] = __( 'Pages format is invalid. Use formats like "123-145" or "123".', 'academic-bloggers-toolkit' );
        }

        // Type-specific validations
        if ( isset( $data['type'] ) ) {
            switch ( $data['type'] ) {
                case 'journal':
                    if ( empty( $data['publication'] ) ) {
                        self::$errors[] = __( 'Journal name is required for journal articles.', 'academic-bloggers-toolkit' );
                    }
                    break;
                case 'book':
                    if ( empty( $data['author'] ) && empty( $data['editor'] ) ) {
                        self::$errors[] = __( 'Author or editor is required for books.', 'academic-bloggers-toolkit' );
                    }
                    break;
                case 'chapter':
                    if ( empty( $data['book_title'] ) ) {
                        self::$errors[] = __( 'Book title is required for book chapters.', 'academic-bloggers-toolkit' );
                    }
                    break;
            }
        }

        return empty( self::$errors );
    }

    /**
     * Validate citation data
     *
     * @since 1.0.0
     * @param array $data Citation data to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate_citation_data( $data ) {
        self::$errors = array();

        // Required fields
        if ( empty( $data['reference_id'] ) || ! is_numeric( $data['reference_id'] ) ) {
            self::$errors[] = __( 'Valid reference ID is required.', 'academic-bloggers-toolkit' );
        }

        // Validate reference exists
        if ( isset( $data['reference_id'] ) && is_numeric( $data['reference_id'] ) ) {
            $reference = get_post( $data['reference_id'] );
            if ( ! $reference || $reference->post_type !== 'abt_reference' ) {
                self::$errors[] = __( 'Referenced item does not exist.', 'academic-bloggers-toolkit' );
            }
        }

        // Validate citation style
        if ( isset( $data['style'] ) && ! self::is_valid_citation_style( $data['style'] ) ) {
            self::$errors[] = __( 'Invalid citation style.', 'academic-bloggers-toolkit' );
        }

        // Validate page numbers
        if ( isset( $data['pages'] ) && ! empty( $data['pages'] ) && ! self::is_valid_pages( $data['pages'] ) ) {
            self::$errors[] = __( 'Page numbers format is invalid.', 'academic-bloggers-toolkit' );
        }

        return empty( self::$errors );
    }

    /**
     * Validate footnote data
     *
     * @since 1.0.0
     * @param array $data Footnote data to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate_footnote_data( $data ) {
        self::$errors = array();

        // Required fields
        if ( empty( $data['text'] ) ) {
            self::$errors[] = __( 'Footnote text is required.', 'academic-bloggers-toolkit' );
        }

        // Validate text length
        if ( isset( $data['text'] ) && strlen( $data['text'] ) > 2000 ) {
            self::$errors[] = __( 'Footnote text must be 2000 characters or less.', 'academic-bloggers-toolkit' );
        }

        // Validate order number
        if ( isset( $data['order'] ) && ( ! is_numeric( $data['order'] ) || intval( $data['order'] ) < 1 ) ) {
            self::$errors[] = __( 'Footnote order must be a positive number.', 'academic-bloggers-toolkit' );
        }

        return empty( self::$errors );
    }

    /**
     * Validate academic blog post data
     *
     * @since 1.0.0
     * @param array $data Post data to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate_blog_post_data( $data ) {
        self::$errors = array();

        // Required fields
        if ( empty( $data['title'] ) ) {
            self::$errors[] = __( 'Post title is required.', 'academic-bloggers-toolkit' );
        }

        if ( empty( $data['content'] ) ) {
            self::$errors[] = __( 'Post content is required.', 'academic-bloggers-toolkit' );
        }

        // Validate title length
        if ( isset( $data['title'] ) && strlen( $data['title'] ) > 200 ) {
            self::$errors[] = __( 'Post title must be 200 characters or less.', 'academic-bloggers-toolkit' );
        }

        // Validate academic metadata
        if ( isset( $data['academic_level'] ) && ! self::is_valid_academic_level( $data['academic_level'] ) ) {
            self::$errors[] = __( 'Invalid academic level.', 'academic-bloggers-toolkit' );
        }

        // Validate peer review status
        if ( isset( $data['peer_reviewed'] ) && ! in_array( $data['peer_reviewed'], array( 'yes', 'no', 'pending' ) ) ) {
            self::$errors[] = __( 'Invalid peer review status.', 'academic-bloggers-toolkit' );
        }

        return empty( self::$errors );
    }

    /**
     * Validate import data
     *
     * @since 1.0.0
     * @param array $data Import data to validate
     * @param string $format Import format (ris, bibtex, csv)
     * @return bool True if valid, false otherwise
     */
    public static function validate_import_data( $data, $format ) {
        self::$errors = array();

        if ( empty( $data ) || ! is_array( $data ) ) {
            self::$errors[] = __( 'No valid data found for import.', 'academic-bloggers-toolkit' );
            return false;
        }

        switch ( $format ) {
            case 'ris':
                return self::validate_ris_data( $data );
            case 'bibtex':
                return self::validate_bibtex_data( $data );
            case 'csv':
                return self::validate_csv_data( $data );
            default:
                self::$errors[] = __( 'Unsupported import format.', 'academic-bloggers-toolkit' );
                return false;
        }
    }

    /**
     * Check if reference type is valid
     *
     * @since 1.0.0
     * @param string $type Reference type
     * @return bool True if valid
     */
    public static function is_valid_reference_type( $type ) {
        $valid_types = array(
            'journal', 'book', 'chapter', 'conference', 'thesis', 
            'report', 'website', 'newspaper', 'magazine', 'other'
        );
        return in_array( $type, $valid_types );
    }

    /**
     * Check if year is valid
     *
     * @since 1.0.0
     * @param mixed $year Year to validate
     * @return bool True if valid
     */
    public static function is_valid_year( $year ) {
        if ( ! is_numeric( $year ) ) {
            return false;
        }

        $year = intval( $year );
        $current_year = intval( date( 'Y' ) );
        
        return $year >= 1000 && $year <= ( $current_year + 5 );
    }

    /**
     * Check if DOI is valid
     *
     * @since 1.0.0
     * @param string $doi DOI to validate
     * @return bool True if valid
     */
    public static function is_valid_doi( $doi ) {
        // DOI pattern: 10.xxxx/xxxxx
        return preg_match( '/^10\.\d{4,}\/[^\s]+$/', trim( $doi ) );
    }

    /**
     * Check if PMID is valid
     *
     * @since 1.0.0
     * @param string $pmid PMID to validate
     * @return bool True if valid
     */
    public static function is_valid_pmid( $pmid ) {
        return preg_match( '/^\d{7,8}$/', trim( $pmid ) );
    }

    /**
     * Check if ISBN is valid
     *
     * @since 1.0.0
     * @param string $isbn ISBN to validate
     * @return bool True if valid
     */
    public static function is_valid_isbn( $isbn ) {
        // Remove hyphens and spaces
        $isbn = preg_replace( '/[^0-9X]/i', '', $isbn );
        
        // Check length
        if ( ! in_array( strlen( $isbn ), array( 10, 13 ) ) ) {
            return false;
        }

        if ( strlen( $isbn ) === 10 ) {
            return self::validate_isbn10( $isbn );
        } else {
            return self::validate_isbn13( $isbn );
        }
    }

    /**
     * Validate ISBN-10 checksum
     *
     * @since 1.0.0
     * @param string $isbn ISBN-10 to validate
     * @return bool True if valid
     */
    private static function validate_isbn10( $isbn ) {
        $sum = 0;
        for ( $i = 0; $i < 9; $i++ ) {
            if ( ! is_numeric( $isbn[ $i ] ) ) {
                return false;
            }
            $sum += intval( $isbn[ $i ] ) * ( 10 - $i );
        }

        $checksum = ( 11 - ( $sum % 11 ) ) % 11;
        $last_char = strtoupper( $isbn[9] );
        
        return ( $checksum === 10 && $last_char === 'X' ) || 
               ( $checksum < 10 && intval( $last_char ) === $checksum );
    }

    /**
     * Validate ISBN-13 checksum
     *
     * @since 1.0.0
     * @param string $isbn ISBN-13 to validate
     * @return bool True if valid
     */
    private static function validate_isbn13( $isbn ) {
        $sum = 0;
        for ( $i = 0; $i < 12; $i++ ) {
            if ( ! is_numeric( $isbn[ $i ] ) ) {
                return false;
            }
            $multiplier = ( $i % 2 === 0 ) ? 1 : 3;
            $sum += intval( $isbn[ $i ] ) * $multiplier;
        }

        $checksum = ( 10 - ( $sum % 10 ) ) % 10;
        return intval( $isbn[12] ) === $checksum;
    }

    /**
     * Check if URL is valid
     *
     * @since 1.0.0
     * @param string $url URL to validate
     * @return bool True if valid
     */
    public static function is_valid_url( $url ) {
        return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
    }

    /**
     * Check if pages format is valid
     *
     * @since 1.0.0
     * @param string $pages Pages to validate
     * @return bool True if valid
     */
    public static function is_valid_pages( $pages ) {
        // Valid formats: "123", "123-145", "123,145,167", "123-145,150-160"
        $pattern = '/^\d+(?:-\d+)?(?:,\s*\d+(?:-\d+)?)*$/';
        return preg_match( $pattern, trim( $pages ) );
    }

    /**
     * Check if citation style is valid
     *
     * @since 1.0.0
     * @param string $style Citation style
     * @return bool True if valid
     */
    public static function is_valid_citation_style( $style ) {
        $valid_styles = array( 'apa', 'mla', 'chicago', 'harvard', 'ieee', 'vancouver' );
        return in_array( $style, $valid_styles );
    }

    /**
     * Check if academic level is valid
     *
     * @since 1.0.0
     * @param string $level Academic level
     * @return bool True if valid
     */
    public static function is_valid_academic_level( $level ) {
        $valid_levels = array( 'undergraduate', 'graduate', 'postgraduate', 'professional', 'general' );
        return in_array( $level, $valid_levels );
    }

    /**
     * Validate RIS format data
     *
     * @since 1.0.0
     * @param array $data RIS data
     * @return bool True if valid
     */
    private static function validate_ris_data( $data ) {
        foreach ( $data as $record ) {
            if ( ! isset( $record['TY'] ) ) {
                self::$errors[] = __( 'RIS record missing type field (TY).', 'academic-bloggers-toolkit' );
            }
            
            if ( ! isset( $record['TI'] ) && ! isset( $record['T1'] ) ) {
                self::$errors[] = __( 'RIS record missing title field.', 'academic-bloggers-toolkit' );
            }
        }
        
        return empty( self::$errors );
    }

    /**
     * Validate BibTeX format data
     *
     * @since 1.0.0
     * @param array $data BibTeX data
     * @return bool True if valid
     */
    private static function validate_bibtex_data( $data ) {
        foreach ( $data as $record ) {
            if ( ! isset( $record['type'] ) ) {
                self::$errors[] = __( 'BibTeX record missing entry type.', 'academic-bloggers-toolkit' );
            }
            
            if ( ! isset( $record['title'] ) ) {
                self::$errors[] = __( 'BibTeX record missing title field.', 'academic-bloggers-toolkit' );
            }
        }
        
        return empty( self::$errors );
    }

    /**
     * Validate CSV format data
     *
     * @since 1.0.0
     * @param array $data CSV data
     * @return bool True if valid
     */
    private static function validate_csv_data( $data ) {
        if ( empty( $data[0] ) ) {
            self::$errors[] = __( 'CSV file appears to be empty.', 'academic-bloggers-toolkit' );
            return false;
        }

        // Check for required columns
        $required_columns = array( 'title', 'type' );
        $headers = array_keys( $data[0] );
        
        foreach ( $required_columns as $column ) {
            if ( ! in_array( $column, $headers ) ) {
                self::$errors[] = sprintf( 
                    __( 'CSV file missing required column: %s', 'academic-bloggers-toolkit' ), 
                    $column 
                );
            }
        }
        
        return empty( self::$errors );
    }

    /**
     * Get validation errors
     *
     * @since 1.0.0
     * @return array Array of error messages
     */
    public static function get_errors() {
        return self::$errors;
    }

    /**
     * Get last validation error
     *
     * @since 1.0.0
     * @return string|null Last error message
     */
    public static function get_last_error() {
        return ! empty( self::$errors ) ? end( self::$errors ) : null;
    }

    /**
     * Clear validation errors
     *
     * @since 1.0.0
     */
    public static function clear_errors() {
        self::$errors = array();
    }

    /**
     * Check if there are validation errors
     *
     * @since 1.0.0
     * @return bool True if errors exist
     */
    public static function has_errors() {
        return ! empty( self::$errors );
    }

    /**
     * Validate file upload
     *
     * @since 1.0.0
     * @param array $file $_FILES array element
     * @param array $allowed_types Allowed file types
     * @param int $max_size Maximum file size in bytes
     * @return bool True if valid
     */
    public static function validate_file_upload( $file, $allowed_types = array(), $max_size = 5242880 ) {
        self::$errors = array();

        // Check for upload errors
        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            switch ( $file['error'] ) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    self::$errors[] = __( 'File is too large.', 'academic-bloggers-toolkit' );
                    break;
                case UPLOAD_ERR_PARTIAL:
                    self::$errors[] = __( 'File upload was incomplete.', 'academic-bloggers-toolkit' );
                    break;
                case UPLOAD_ERR_NO_FILE:
                    self::$errors[] = __( 'No file was uploaded.', 'academic-bloggers-toolkit' );
                    break;
                default:
                    self::$errors[] = __( 'File upload failed.', 'academic-bloggers-toolkit' );
            }
            return false;
        }

        // Check file size
        if ( $file['size'] > $max_size ) {
            self::$errors[] = sprintf( 
                __( 'File size exceeds maximum allowed size of %s.', 'academic-bloggers-toolkit' ),
                ABT_Utils::format_file_size( $max_size )
            );
        }

        // Check file type
        if ( ! empty( $allowed_types ) ) {
            $file_extension = ABT_Utils::get_file_extension( $file['name'] );
            if ( ! in_array( $file_extension, $allowed_types ) ) {
                self::$errors[] = sprintf( 
                    __( 'File type not allowed. Allowed types: %s', 'academic-bloggers-toolkit' ),
                    implode( ', ', $allowed_types )
                );
            }
        }

        return empty( self::$errors );
    }

    /**
     * Validate nonce
     *
     * @since 1.0.0
     * @param string $nonce Nonce value
     * @param string $action Nonce action
     * @return bool True if valid
     */
    public static function validate_nonce( $nonce, $action ) {
        return wp_verify_nonce( $nonce, $action );
    }

    /**
     * Validate user permissions
     *
     * @since 1.0.0
     * @param string $capability Required capability
     * @param int $user_id User ID (optional, defaults to current user)
     * @return bool True if user has permission
     */
    public static function validate_user_permission( $capability, $user_id = null ) {
        if ( $user_id ) {
            return user_can( $user_id, $capability );
        } else {
            return current_user_can( $capability );
        }
    }
}