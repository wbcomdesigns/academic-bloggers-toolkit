<?php
/**
 * RIS Parser - Research Information Systems Format
 *
 * Handles parsing and generating RIS format files
 *
 * @package Academic_Bloggers_Toolkit
 * @subpackage Import_Export
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RIS Parser Class
 *
 * Handles RIS format import and export operations
 */
class ABT_RIS_Parser {

    /**
     * RIS type mappings
     *
     * @var array
     */
    private $ris_types = [
        'ABST' => 'Abstract',
        'ADVS' => 'Audiovisual material',
        'AGGR' => 'Aggregated Database',
        'ANCIENT' => 'Ancient Text',
        'ART' => 'Art Work',
        'BILL' => 'Bill',
        'BLOG' => 'Blog',
        'BOOK' => 'Book, Whole',
        'CASE' => 'Case',
        'CHAP' => 'Book chapter',
        'CHART' => 'Chart',
        'CLSWK' => 'Classical Work',
        'COMP' => 'Computer program',
        'CONF' => 'Conference proceeding',
        'CPAPER' => 'Conference paper',
        'CTLG' => 'Catalog',
        'DATA' => 'Data file',
        'DBASE' => 'Online Database',
        'DICT' => 'Dictionary',
        'EBOOK' => 'Electronic Book',
        'ECHAP' => 'Electronic Book Section',
        'EDBOOK' => 'Edited Book',
        'EJOUR' => 'Electronic Article',
        'ELEC' => 'Web Page',
        'ENCYC' => 'Encyclopedia',
        'EQUA' => 'Equation',
        'FIGURE' => 'Figure',
        'GEN' => 'Generic',
        'GOVDOC' => 'Government Document',
        'GRANT' => 'Grant',
        'HEAR' => 'Hearing',
        'ICOMM' => 'Internet Communication',
        'INPR' => 'In Press',
        'JFULL' => 'Journal (full)',
        'JOUR' => 'Journal',
        'LEGAL' => 'Legal Rule or Regulation',
        'MANSCPT' => 'Manuscript',
        'MAP' => 'Map',
        'MGZN' => 'Magazine article',
        'MPCT' => 'Motion picture',
        'MULTI' => 'Online Multimedia',
        'MUSIC' => 'Music score',
        'NEWS' => 'Newspaper',
        'PAMP' => 'Pamphlet',
        'PAT' => 'Patent',
        'PCOMM' => 'Personal communication',
        'RPRT' => 'Report',
        'SER' => 'Serial publication',
        'SLIDE' => 'Slide',
        'SOUND' => 'Sound recording',
        'STAND' => 'Standard',
        'STAT' => 'Statute',
        'THES' => 'Thesis/Dissertation',
        'UNBILL' => 'Unenacted Bill',
        'UNPB' => 'Unpublished work',
        'VIDEO' => 'Video recording'
    ];

    /**
     * RIS field mappings
     *
     * @var array
     */
    private $field_mappings = [
        'TY' => 'type',
        'A1' => 'author',
        'A2' => 'editor',
        'A3' => 'series_editor',
        'A4' => 'translator',
        'AB' => 'abstract',
        'AD' => 'author_address',
        'AN' => 'accession_number',
        'AU' => 'author',
        'AV' => 'availability',
        'BT' => 'book_title',
        'C1' => 'custom1',
        'C2' => 'custom2',
        'C3' => 'custom3',
        'C4' => 'custom4',
        'C5' => 'custom5',
        'C6' => 'custom6',
        'C7' => 'custom7',
        'C8' => 'custom8',
        'CA' => 'caption',
        'CN' => 'call_number',
        'CP' => 'issue',
        'CT' => 'title_of_unpublished',
        'CY' => 'place_published',
        'DA' => 'date',
        'DB' => 'name_of_database',
        'DO' => 'doi',
        'DP' => 'database_provider',
        'ED' => 'editor',
        'EP' => 'end_page',
        'ET' => 'edition',
        'ID' => 'reference_id',
        'IS' => 'issue',
        'J1' => 'journal_abbreviation',
        'J2' => 'alternate_journal',
        'JA' => 'journal_abbreviation',
        'JF' => 'journal_full',
        'JO' => 'journal',
        'KW' => 'keywords',
        'L1' => 'file_attachments',
        'L2' => 'full_text_links',
        'L3' => 'related_records',
        'L4' => 'images',
        'LA' => 'language',
        'LB' => 'label',
        'LK' => 'website_link',
        'M1' => 'number',
        'M2' => 'miscellaneous2',
        'M3' => 'type_of_work',
        'N1' => 'notes',
        'N2' => 'abstract',
        'NV' => 'number_of_volumes',
        'OP' => 'original_publication',
        'PB' => 'publisher',
        'PP' => 'place_published',
        'PY' => 'year',
        'RI' => 'reviewed_item',
        'RN' => 'research_notes',
        'RP' => 'reprint_edition',
        'SE' => 'section',
        'SN' => 'isbn_issn',
        'SP' => 'start_page',
        'ST' => 'short_title',
        'T1' => 'primary_title',
        'T2' => 'secondary_title',
        'T3' => 'tertiary_title',
        'TA' => 'translated_author',
        'TI' => 'title',
        'TT' => 'translated_title',
        'U1' => 'user_definable1',
        'U2' => 'user_definable2',
        'U3' => 'user_definable3',
        'U4' => 'user_definable4',
        'U5' => 'user_definable5',
        'UR' => 'url',
        'VL' => 'volume',
        'VO' => 'published_standard_number',
        'Y1' => 'primary_date',
        'Y2' => 'access_date'
    ];

    /**
     * Parse RIS format data
     *
     * @param string $ris_content RIS content
     * @return array|WP_Error Parsed references or error
     */
    public function parse($ris_content) {
        if (empty($ris_content)) {
            return new WP_Error('empty_content', __('RIS content is empty', 'academic-bloggers-toolkit'));
        }

        $references = [];
        $current_reference = [];
        
        // Split content into lines
        $lines = preg_split('/\r\n|\r|\n/', $ris_content);
        
        foreach ($lines as $line_number => $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            // Parse RIS line format: TAG  - VALUE
            if (preg_match('/^([A-Z0-9]{2,4})\s*-\s*(.*)$/', $line, $matches)) {
                $tag = trim($matches[1]);
                $value = trim($matches[2]);

                if ($tag === 'TY') {
                    // Start of new reference
                    if (!empty($current_reference)) {
                        $references[] = $this->normalize_ris_reference($current_reference);
                    }
                    $current_reference = ['TY' => $value];
                } elseif ($tag === 'ER') {
                    // End of reference
                    if (!empty($current_reference)) {
                        $references[] = $this->normalize_ris_reference($current_reference);
                        $current_reference = [];
                    }
                } else {
                    // Regular field
                    if (isset($current_reference[$tag])) {
                        // Handle multiple values for same tag
                        if (!is_array($current_reference[$tag])) {
                            $current_reference[$tag] = [$current_reference[$tag]];
                        }
                        $current_reference[$tag][] = $value;
                    } else {
                        $current_reference[$tag] = $value;
                    }
                }
            } else {
                // Handle continuation lines (values that span multiple lines)
                if (!empty($current_reference)) {
                    $last_key = array_key_last($current_reference);
                    if ($last_key && is_string($current_reference[$last_key])) {
                        $current_reference[$last_key] .= ' ' . $line;
                    }
                }
            }
        }

        // Don't forget the last reference if there's no ER tag
        if (!empty($current_reference)) {
            $references[] = $this->normalize_ris_reference($current_reference);
        }

        if (empty($references)) {
            return new WP_Error('no_references', __('No valid references found in RIS data', 'academic-bloggers-toolkit'));
        }

        return $references;
    }

    /**
     * Export references to RIS format
     *
     * @param array $references Array of reference data
     * @param array $options Export options
     * @return string RIS formatted content
     */
    public function export($references, $options = []) {
        if (empty($references)) {
            return '';
        }

        $ris_content = [];
        
        // Add header comment
        $ris_content[] = sprintf(
            '# RIS file exported by Academic Bloggers Toolkit on %s',
            date('Y-m-d H:i:s')
        );
        $ris_content[] = '# Total references: ' . count($references);
        $ris_content[] = '';

        foreach ($references as $reference) {
            $ris_reference = $this->format_reference_to_ris($reference, $options);
            $ris_content[] = $ris_reference;
            $ris_content[] = ''; // Empty line between references
        }

        return implode("\n", $ris_content);
    }

    /**
     * Normalize RIS reference to standard format
     *
     * @param array $ris_reference Raw RIS reference
     * @return array Normalized reference
     */
    private function normalize_ris_reference($ris_reference) {
        $normalized = [];

        foreach ($ris_reference as $tag => $value) {
            $field_name = $this->field_mappings[$tag] ?? $tag;
            
            // Handle special cases
            switch ($tag) {
                case 'TY':
                    $normalized['type'] = $this->map_ris_type_to_abt_type($value);
                    $normalized['ris_type'] = $value;
                    break;
                    
                case 'TI':
                case 'T1':
                    $normalized['title'] = $this->clean_text($value);
                    break;
                    
                case 'AU':
                case 'A1':
                    $normalized['author'] = $this->format_authors($value);
                    break;
                    
                case 'ED':
                case 'A2':
                    $normalized['editor'] = $this->format_authors($value);
                    break;
                    
                case 'JO':
                case 'JF':
                case 'T2':
                    $normalized['journal'] = $this->clean_text($value);
                    break;
                    
                case 'BT':
                    $normalized['book_title'] = $this->clean_text($value);
                    break;
                    
                case 'PY':
                case 'Y1':
                    $normalized['year'] = $this->extract_year($value);
                    break;
                    
                case 'VL':
                    $normalized['volume'] = $this->clean_text($value);
                    break;
                    
                case 'IS':
                case 'CP':
                    $normalized['issue'] = $this->clean_text($value);
                    break;
                    
                case 'SP':
                    $normalized['start_page'] = $this->clean_text($value);
                    break;
                    
                case 'EP':
                    $normalized['end_page'] = $this->clean_text($value);
                    break;
                    
                case 'PB':
                    $normalized['publisher'] = $this->clean_text($value);
                    break;
                    
                case 'PP':
                case 'CY':
                    $normalized['location'] = $this->clean_text($value);
                    break;
                    
                case 'DO':
                    $normalized['doi'] = $this->clean_text($value);
                    break;
                    
                case 'SN':
                    $normalized['isbn_issn'] = $this->clean_text($value);
                    break;
                    
                case 'UR':
                case 'LK':
                    $normalized['url'] = $this->clean_text($value);
                    break;
                    
                case 'AB':
                case 'N2':
                    $normalized['abstract'] = $this->clean_text($value);
                    break;
                    
                case 'KW':
                    $normalized['keywords'] = $this->format_keywords($value);
                    break;
                    
                case 'LA':
                    $normalized['language'] = $this->clean_text($value);
                    break;
                    
                case 'N1':
                    $normalized['notes'] = $this->clean_text($value);
                    break;
                    
                default:
                    $normalized[$field_name] = is_array($value) ? $value : $this->clean_text($value);
                    break;
            }
        }

        // Combine start and end pages
        if (isset($normalized['start_page']) || isset($normalized['end_page'])) {
            $pages = '';
            if (isset($normalized['start_page'])) {
                $pages = $normalized['start_page'];
                if (isset($normalized['end_page']) && $normalized['end_page'] !== $normalized['start_page']) {
                    $pages .= '-' . $normalized['end_page'];
                }
            } elseif (isset($normalized['end_page'])) {
                $pages = $normalized['end_page'];
            }
            $normalized['pages'] = $pages;
            unset($normalized['start_page'], $normalized['end_page']);
        }

        return $normalized;
    }

    /**
     * Format reference to RIS format
     *
     * @param array $reference Reference data
     * @param array $options Export options
     * @return string RIS formatted reference
     */
    private function format_reference_to_ris($reference, $options = []) {
        $ris_lines = [];

        // Start with type
        $ris_type = $this->map_abt_type_to_ris_type($reference['type'] ?? 'other');
        $ris_lines[] = "TY  - {$ris_type}";

        // Title
        if (!empty($reference['title'])) {
            $ris_lines[] = "TI  - {$reference['title']}";
        }

        // Authors
        if (!empty($reference['author'])) {
            $authors = $this->split_authors($reference['author']);
            foreach ($authors as $author) {
                $ris_lines[] = "AU  - {$author}";
            }
        }

        // Editors
        if (!empty($reference['editor'])) {
            $editors = $this->split_authors($reference['editor']);
            foreach ($editors as $editor) {
                $ris_lines[] = "ED  - {$editor}";
            }
        }

        // Journal/Publication
        if (!empty($reference['journal'])) {
            $ris_lines[] = "JO  - {$reference['journal']}";
        } elseif (!empty($reference['publication'])) {
            $ris_lines[] = "T2  - {$reference['publication']}";
        }

        // Year
        if (!empty($reference['year'])) {
            $ris_lines[] = "PY  - {$reference['year']}";
        }

        // Volume
        if (!empty($reference['volume'])) {
            $ris_lines[] = "VL  - {$reference['volume']}";
        }

        // Issue
        if (!empty($reference['issue'])) {
            $ris_lines[] = "IS  - {$reference['issue']}";
        }

        // Pages
        if (!empty($reference['pages'])) {
            $page_parts = $this->split_pages($reference['pages']);
            if (isset($page_parts['start'])) {
                $ris_lines[] = "SP  - {$page_parts['start']}";
            }
            if (isset($page_parts['end'])) {
                $ris_lines[] = "EP  - {$page_parts['end']}";
            }
        }

        // Publisher
        if (!empty($reference['publisher'])) {
            $ris_lines[] = "PB  - {$reference['publisher']}";
        }

        // Location
        if (!empty($reference['location'])) {
            $ris_lines[] = "PP  - {$reference['location']}";
        }

        // DOI
        if (!empty($reference['doi'])) {
            $ris_lines[] = "DO  - {$reference['doi']}";
        }

        // ISBN/ISSN
        if (!empty($reference['isbn'])) {
            $ris_lines[] = "SN  - {$reference['isbn']}";
        } elseif (!empty($reference['issn'])) {
            $ris_lines[] = "SN  - {$reference['issn']}";
        }

        // URL
        if (!empty($reference['url'])) {
            $ris_lines[] = "UR  - {$reference['url']}";
        }

        // Abstract
        if (!empty($reference['abstract']) && !empty($options['include_abstracts'])) {
            $abstract = $this->wrap_long_text($reference['abstract']);
            $ris_lines[] = "AB  - {$abstract}";
        }

        // Keywords
        if (!empty($reference['keywords']) && !empty($options['include_keywords'])) {
            $keywords = is_array($reference['keywords']) ? implode(', ', $reference['keywords']) : $reference['keywords'];
            $ris_lines[] = "KW  - {$keywords}";
        }

        // Language
        if (!empty($reference['language'])) {
            $ris_lines[] = "LA  - {$reference['language']}";
        }

        // Notes
        if (!empty($reference['notes'])) {
            $ris_lines[] = "N1  - {$reference['notes']}";
        }

        // End reference
        $ris_lines[] = "ER  - ";

        return implode("\n", $ris_lines);
    }

    /**
     * Map RIS type to ABT type
     *
     * @param string $ris_type RIS type
     * @return string ABT type
     */
    private function map_ris_type_to_abt_type($ris_type) {
        $mapping = [
            'JOUR' => 'journal',
            'EJOUR' => 'journal',
            'BOOK' => 'book',
            'EBOOK' => 'book',
            'CHAP' => 'chapter',
            'ECHAP' => 'chapter',
            'CONF' => 'conference',
            'CPAPER' => 'conference',
            'THES' => 'thesis',
            'RPRT' => 'report',
            'ELEC' => 'website',
            'NEWS' => 'newspaper',
            'MGZN' => 'magazine',
            'GEN' => 'other',
            'UNPB' => 'other'
        ];

        return $mapping[$ris_type] ?? 'other';
    }

    /**
     * Map ABT type to RIS type
     *
     * @param string $abt_type ABT type
     * @return string RIS type
     */
    private function map_abt_type_to_ris_type($abt_type) {
        $mapping = [
            'journal' => 'JOUR',
            'book' => 'BOOK',
            'chapter' => 'CHAP',
            'conference' => 'CONF',
            'thesis' => 'THES',
            'report' => 'RPRT',
            'website' => 'ELEC',
            'newspaper' => 'NEWS',
            'magazine' => 'MGZN',
            'other' => 'GEN'
        ];

        return $mapping[$abt_type] ?? 'GEN';
    }

    /**
     * Format authors for RIS
     *
     * @param mixed $authors Authors data
     * @return string Formatted authors
     */
    private function format_authors($authors) {
        if (is_array($authors)) {
            return implode('; ', $authors);
        }
        return $this->clean_text($authors);
    }

    /**
     * Split authors string into array
     *
     * @param string $authors_string Authors string
     * @return array Authors array
     */
    private function split_authors($authors_string) {
        return array_map('trim', preg_split('/[;,]|\band\b|\&/', $authors_string));
    }

    /**
     * Format keywords for RIS
     *
     * @param mixed $keywords Keywords data
     * @return string Formatted keywords
     */
    private function format_keywords($keywords) {
        if (is_array($keywords)) {
            return implode(', ', $keywords);
        }
        return $this->clean_text($keywords);
    }

    /**
     * Split pages into start and end
     *
     * @param string $pages Pages string
     * @return array Pages array with start and end
     */
    private function split_pages($pages) {
        $result = [];
        
        if (strpos($pages, '-') !== false) {
            $parts = explode('-', $pages, 2);
            $result['start'] = trim($parts[0]);
            $result['end'] = trim($parts[1]);
        } else {
            $result['start'] = trim($pages);
        }
        
        return $result;
    }

    /**
     * Extract year from date string
     *
     * @param string $date_string Date string
     * @return string Year
     */
    private function extract_year($date_string) {
        if (preg_match('/(\d{4})/', $date_string, $matches)) {
            return $matches[1];
        }
        return $date_string;
    }

    /**
     * Clean text for RIS output
     *
     * @param string $text Text to clean
     * @return string Cleaned text
     */
    private function clean_text($text) {
        if (is_array($text)) {
            $text = implode('; ', $text);
        }
        
        // Remove extra whitespace and normalize line endings
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        return $text;
    }

    /**
     * Wrap long text for RIS format
     *
     * @param string $text Long text
     * @param int $max_length Maximum line length
     * @return string Wrapped text
     */
    private function wrap_long_text($text, $max_length = 255) {
        $text = $this->clean_text($text);
        
        if (strlen($text) <= $max_length) {
            return $text;
        }
        
        // Split into chunks at word boundaries
        $words = explode(' ', $text);
        $lines = [];
        $current_line = '';
        
        foreach ($words as $word) {
            if (strlen($current_line . ' ' . $word) <= $max_length) {
                $current_line .= ($current_line ? ' ' : '') . $word;
            } else {
                if ($current_line) {
                    $lines[] = $current_line;
                }
                $current_line = $word;
            }
        }
        
        if ($current_line) {
            $lines[] = $current_line;
        }
        
        return implode("\n", $lines);
    }

    /**
     * Validate RIS content
     *
     * @param string $ris_content RIS content to validate
     * @return bool|WP_Error True if valid, error if not
     */
    public function validate($ris_content) {
        if (empty($ris_content)) {
            return new WP_Error('empty_content', __('RIS content is empty', 'academic-bloggers-toolkit'));
        }

        // Check if content has RIS format markers
        if (!preg_match('/^[A-Z0-9]{2,4}\s*-\s*/m', $ris_content)) {
            return new WP_Error('invalid_format', __('Content does not appear to be in RIS format', 'academic-bloggers-toolkit'));
        }

        // Check for at least one TY (type) field
        if (!preg_match('/^TY\s*-\s*/m', $ris_content)) {
            return new WP_Error('missing_type', __('No reference type (TY) field found', 'academic-bloggers-toolkit'));
        }

        return true;
    }

    /**
     * Get RIS type descriptions
     *
     * @return array RIS type descriptions
     */
    public function get_ris_types() {
        return $this->ris_types;
    }

    /**
     * Get field mappings
     *
     * @return array Field mappings
     */
    public function get_field_mappings() {
        return $this->field_mappings;
    }

    /**
     * Count references in RIS content
     *
     * @param string $ris_content RIS content
     * @return int Number of references
     */
    public function count_references($ris_content) {
        return preg_match_all('/^TY\s*-\s*/m', $ris_content);
    }

    /**
     * Extract metadata from RIS content
     *
     * @param string $ris_content RIS content
     * @return array Metadata information
     */
    public function extract_metadata($ris_content) {
        $metadata = [
            'format' => 'ris',
            'reference_count' => $this->count_references($ris_content),
            'file_size' => strlen($ris_content),
            'line_count' => substr_count($ris_content, "\n") + 1,
            'estimated_source' => $this->guess_source($ris_content)
        ];

        return $metadata;
    }

    /**
     * Guess the source of RIS file based on content patterns
     *
     * @param string $ris_content RIS content
     * @return string Estimated source
     */
    private function guess_source($ris_content) {
        // Look for patterns that indicate specific sources
        if (strpos($ris_content, 'EndNote') !== false) {
            return 'EndNote';
        }
        
        if (strpos($ris_content, 'Zotero') !== false) {
            return 'Zotero';
        }
        
        if (strpos($ris_content, 'Mendeley') !== false) {
            return 'Mendeley';
        }
        
        if (strpos($ris_content, 'RefWorks') !== false) {
            return 'RefWorks';
        }
        
        if (strpos($ris_content, 'PubMed') !== false) {
            return 'PubMed';
        }
        
        if (strpos($ris_content, 'Web of Science') !== false) {
            return 'Web of Science';
        }
        
        return 'Unknown';
    }

    /**
     * Clean and optimize RIS content
     *
     * @param string $ris_content RIS content
     * @return string Cleaned RIS content
     */
    public function clean_ris_content($ris_content) {
        // Normalize line endings
        $ris_content = str_replace(["\r\n", "\r"], "\n", $ris_content);
        
        // Remove BOM if present
        $ris_content = preg_replace('/^\xEF\xBB\xBF/', '', $ris_content);
        
        // Remove extra blank lines
        $ris_content = preg_replace('/\n{3,}/', "\n\n", $ris_content);
        
        // Ensure proper spacing around dashes
        $ris_content = preg_replace('/^([A-Z0-9]{2,4})\s*-\s*/m', '$1  - ', $ris_content);
        
        return trim($ris_content);
    }

    /**
     * Merge RIS files
     *
     * @param array $ris_contents Array of RIS content strings
     * @return string Merged RIS content
     */
    public function merge_ris_files($ris_contents) {
        $merged_content = [];
        
        // Add header
        $merged_content[] = sprintf(
            '# Merged RIS file created by Academic Bloggers Toolkit on %s',
            date('Y-m-d H:i:s')
        );
        $merged_content[] = '# Total files merged: ' . count($ris_contents);
        $merged_content[] = '';
        
        $total_references = 0;
        
        foreach ($ris_contents as $index => $content) {
            $content = $this->clean_ris_content($content);
            
            if (!empty($content)) {
                $ref_count = $this->count_references($content);
                $total_references += $ref_count;
                
                $merged_content[] = "# References from file " . ($index + 1) . " ({$ref_count} references)";
                $merged_content[] = $content;
                $merged_content[] = '';
            }
        }
        
        // Update header with total count
        $merged_content[2] = '# Total references: ' . $total_references;
        
        return implode("\n", $merged_content);
    }

    /**
     * Split RIS content into individual references
     *
     * @param string $ris_content RIS content
     * @return array Array of individual RIS references
     */
    public function split_references($ris_content) {
        $references = [];
        $current_reference = [];
        
        $lines = preg_split('/\r\n|\r|\n/', $ris_content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            if (preg_match('/^TY\s*-\s*/', $line)) {
                // Start of new reference
                if (!empty($current_reference)) {
                    $references[] = implode("\n", $current_reference) . "\nER  - ";
                }
                $current_reference = [$line];
            } elseif (preg_match('/^ER\s*-\s*/', $line)) {
                // End of reference
                if (!empty($current_reference)) {
                    $current_reference[] = $line;
                    $references[] = implode("\n", $current_reference);
                    $current_reference = [];
                }
            } else {
                // Part of current reference
                if (!empty($current_reference)) {
                    $current_reference[] = $line;
                }
            }
        }
        
        // Handle last reference if no ER tag
        if (!empty($current_reference)) {
            $current_reference[] = "ER  - ";
            $references[] = implode("\n", $current_reference);
        }
        
        return $references;
    }

    /**
     * Convert RIS to other formats
     *
     * @param string $ris_content RIS content
     * @param string $target_format Target format (bibtex, json, csv)
     * @return string|WP_Error Converted content or error
     */
    public function convert_to_format($ris_content, $target_format) {
        // Parse RIS first
        $references = $this->parse($ris_content);
        
        if (is_wp_error($references)) {
            return $references;
        }
        
        // Convert to target format
        switch ($target_format) {
            case 'bibtex':
                $bibtex_parser = new ABT_BibTeX_Parser();
                return $bibtex_parser->export($references);
                
            case 'csv':
                $csv_handler = new ABT_CSV_Handler();
                return $csv_handler->export($references);
                
            case 'json':
                return wp_json_encode($references, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                
            default:
                return new WP_Error('unsupported_format', sprintf(__('Unsupported target format: %s', 'academic-bloggers-toolkit'), $target_format));
        }
    }

    /**
     * Generate RIS template
     *
     * @param string $reference_type Reference type
     * @return string RIS template
     */
    public function generate_template($reference_type = 'JOUR') {
        $templates = [
            'JOUR' => [
                'TY  - JOUR',
                'AU  - [Author Name]',
                'TI  - [Article Title]',
                'JO  - [Journal Name]',
                'VL  - [Volume]',
                'IS  - [Issue]',
                'SP  - [Start Page]',
                'EP  - [End Page]',
                'PY  - [Year]',
                'DO  - [DOI]',
                'ER  - '
            ],
            'BOOK' => [
                'TY  - BOOK',
                'AU  - [Author Name]',
                'TI  - [Book Title]',
                'PB  - [Publisher]',
                'PP  - [Place of Publication]',
                'PY  - [Year]',
                'SN  - [ISBN]',
                'ER  - '
            ],
            'CHAP' => [
                'TY  - CHAP',
                'AU  - [Author Name]',
                'TI  - [Chapter Title]',
                'BT  - [Book Title]',
                'ED  - [Editor Name]',
                'PB  - [Publisher]',
                'PP  - [Place of Publication]',
                'SP  - [Start Page]',
                'EP  - [End Page]',
                'PY  - [Year]',
                'ER  - '
            ]
        ];
        
        $template = $templates[$reference_type] ?? $templates['JOUR'];
        
        return implode("\n", $template);
    }
}