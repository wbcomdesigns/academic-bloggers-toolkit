<?php
/**
 * CSV Handler - CSV Format Import/Export
 *
 * Handles parsing and generating CSV format files for references
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
 * CSV Handler Class
 *
 * Handles CSV format import and export operations for references
 */
class ABT_CSV_Handler {

    /**
     * CSV field headers mapping
     *
     * @var array
     */
    private $field_headers = [
        'id' => 'ID',
        'type' => 'Type',
        'title' => 'Title',
        'author' => 'Author',
        'editor' => 'Editor',
        'year' => 'Year',
        'publication' => 'Publication',
        'journal' => 'Journal',
        'publisher' => 'Publisher',
        'volume' => 'Volume',
        'issue' => 'Issue',
        'pages' => 'Pages',
        'doi' => 'DOI',
        'pmid' => 'PMID',
        'isbn' => 'ISBN',
        'issn' => 'ISSN',
        'url' => 'URL',
        'abstract' => 'Abstract',
        'keywords' => 'Keywords',
        'language' => 'Language',
        'location' => 'Location',
        'edition' => 'Edition',
        'notes' => 'Notes'
    ];

    /**
     * Parse CSV format data
     *
     * @param string $csv_content CSV content
     * @return array|WP_Error Parsed references or error
     */
    public function parse($csv_content) {
        if (empty($csv_content)) {
            return new WP_Error('empty_content', __('CSV content is empty', 'academic-bloggers-toolkit'));
        }

        // Parse CSV data
        $csv_data = $this->parse_csv_string($csv_content);
        
        if (empty($csv_data)) {
            return new WP_Error('no_data', __('No valid data found in CSV', 'academic-bloggers-toolkit'));
        }

        // Get headers from first row
        $headers = array_shift($csv_data);
        $headers = array_map('trim', $headers);
        
        if (empty($headers)) {
            return new WP_Error('no_headers', __('No headers found in CSV', 'academic-bloggers-toolkit'));
        }

        // Validate required headers
        $validation = $this->validate_headers($headers);
        if (is_wp_error($validation)) {
            return $validation;
        }

        $references = [];
        
        foreach ($csv_data as $row_index => $row) {
            if (count($row) !== count($headers)) {
                // Skip rows with mismatched column count
                continue;
            }

            // Combine headers with row data
            $reference_data = array_combine($headers, $row);
            
            // Clean and normalize the data
            $normalized_data = $this->normalize_csv_reference($reference_data);
            
            if (!empty($normalized_data['title'])) {
                $references[] = $normalized_data;
            }
        }

        if (empty($references)) {
            return new WP_Error('no_references', __('No valid references found in CSV data', 'academic-bloggers-toolkit'));
        }

        return $references;
    }

    /**
     * Export references to CSV format
     *
     * @param array $references Array of reference data
     * @param array $options Export options
     * @return string CSV formatted content
     */
    public function export($references, $options = []) {
        if (empty($references)) {
            return '';
        }

        $options = wp_parse_args($options, [
            'include_headers' => true,
            'delimiter' => ',',
            'enclosure' => '"',
            'escape_char' => '\\',
            'include_empty_fields' => false
        ]);

        $csv_data = [];
        
        // Add headers if requested
        if ($options['include_headers']) {
            $csv_data[] = array_values($this->field_headers);
        }

        foreach ($references as $reference) {
            $csv_row = $this->format_reference_for_csv($reference, $options);
            $csv_data[] = $csv_row;
        }

        return $this->generate_csv_string($csv_data, $options);
    }

    /**
     * Parse CSV string into array
     *
     * @param string $csv_content CSV content
     * @return array Parsed CSV data
     */
    private function parse_csv_string($csv_content) {
        $csv_data = [];
        $lines = str_getcsv($csv_content, "\n");
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            $row = str_getcsv($line);
            $csv_data[] = $row;
        }

        return $csv_data;
    }

    /**
     * Generate CSV string from array
     *
     * @param array $data CSV data array
     * @param array $options Export options
     * @return string CSV string
     */
    private function generate_csv_string($data, $options) {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row, $options['delimiter'], $options['enclosure'], $options['escape_char']);
        }
        
        rewind($output);
        $csv_string = stream_get_contents($output);
        fclose($output);
        
        return $csv_string;
    }

    /**
     * Validate CSV headers
     *
     * @param array $headers CSV headers
     * @return bool|WP_Error True if valid, error if not
     */
    private function validate_headers($headers) {
        $required_headers = ['title', 'type'];
        $header_lower = array_map('strtolower', $headers);
        
        foreach ($required_headers as $required) {
            if (!in_array(strtolower($required), $header_lower)) {
                return new WP_Error('missing_header', sprintf(__('Required header "%s" not found in CSV', 'academic-bloggers-toolkit'), $required));
            }
        }

        return true;
    }

    /**
     * Normalize CSV reference data
     *
     * @param array $csv_data Raw CSV row data
     * @return array Normalized reference data
     */
    private function normalize_csv_reference($csv_data) {
        $normalized = [];

        // Map CSV headers to our field names
        $header_mapping = [
            'id' => 'id',
            'type' => 'type',
            'title' => 'title',
            'author' => 'author',
            'authors' => 'author',
            'editor' => 'editor',
            'editors' => 'editor',
            'year' => 'year',
            'publication_year' => 'year',
            'publication' => 'publication',
            'journal' => 'journal',
            'journal_name' => 'journal',
            'publisher' => 'publisher',
            'volume' => 'volume',
            'issue' => 'issue',
            'number' => 'issue',
            'pages' => 'pages',
            'page_range' => 'pages',
            'doi' => 'doi',
            'pmid' => 'pmid',
            'isbn' => 'isbn',
            'issn' => 'issn',
            'url' => 'url',
            'link' => 'url',
            'abstract' => 'abstract',
            'summary' => 'abstract',
            'keywords' => 'keywords',
            'tags' => 'keywords',
            'language' => 'language',
            'location' => 'location',
            'place' => 'location',
            'edition' => 'edition',
            'notes' => 'notes',
            'note' => 'notes'
        ];

        foreach ($csv_data as $csv_header => $value) {
            $header_key = strtolower(trim($csv_header));
            $value = trim($value);
            
            if (empty($value)) {
                continue;
            }

            // Map to our field name
            if (isset($header_mapping[$header_key])) {
                $field_name = $header_mapping[$header_key];
                $normalized[$field_name] = $this->clean_csv_value($value, $field_name);
            }
        }

        // Ensure we have a type
        if (empty($normalized['type'])) {
            $normalized['type'] = 'other';
        }

        return $normalized;
    }

    /**
     * Clean CSV value based on field type
     *
     * @param string $value Raw value
     * @param string $field_name Field name
     * @return mixed Cleaned value
     */
    private function clean_csv_value($value, $field_name) {
        $value = trim($value);
        
        switch ($field_name) {
            case 'year':
                // Extract year from various formats
                if (preg_match('/(\d{4})/', $value, $matches)) {
                    return intval($matches[1]);
                }
                return $value;
                
            case 'doi':
                // Clean DOI format
                $value = preg_replace('/^(https?:\/\/)?(dx\.)?doi\.org\//', '', $value);
                $value = preg_replace('/^doi:?\s*/i', '', $value);
                return $value;
                
            case 'pmid':
                // Extract PMID numbers
                if (preg_match('/(\d{7,8})/', $value, $matches)) {
                    return $matches[1];
                }
                return $value;
                
            case 'isbn':
                // Clean ISBN format
                return preg_replace('/[^0-9X]/i', '', $value);
                
            case 'url':
                // Validate URL format
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    return $value;
                }
                return '';
                
            case 'keywords':
                // Split keywords by common delimiters
                $keywords = preg_split('/[,;|]/', $value);
                $keywords = array_map('trim', $keywords);
                $keywords = array_filter($keywords);
                return implode(', ', $keywords);
                
            case 'type':
                // Normalize reference type
                return $this->normalize_reference_type($value);
                
            default:
                return $value;
        }
    }

    /**
     * Normalize reference type from CSV
     *
     * @param string $type Raw type value
     * @return string Normalized type
     */
    private function normalize_reference_type($type) {
        $type = strtolower(trim($type));
        
        $type_mapping = [
            'journal article' => 'journal',
            'article' => 'journal',
            'paper' => 'journal',
            'book' => 'book',
            'book chapter' => 'chapter',
            'chapter' => 'chapter',
            'conference paper' => 'conference',
            'conference' => 'conference',
            'proceedings' => 'conference',
            'thesis' => 'thesis',
            'dissertation' => 'thesis',
            'phd thesis' => 'thesis',
            'masters thesis' => 'thesis',
            'report' => 'report',
            'technical report' => 'report',
            'website' => 'website',
            'web page' => 'website',
            'online' => 'website',
            'newspaper' => 'newspaper',
            'news' => 'newspaper',
            'magazine' => 'magazine',
            'other' => 'other'
        ];

        return $type_mapping[$type] ?? 'other';
    }

    /**
     * Format reference for CSV export
     *
     * @param array $reference Reference data
     * @param array $options Export options
     * @return array CSV row data
     */
    private function format_reference_for_csv($reference, $options) {
        $csv_row = [];
        
        foreach ($this->field_headers as $field_key => $header_name) {
            $value = $reference[$field_key] ?? '';
            
            // Handle special formatting
            switch ($field_key) {
                case 'author':
                case 'editor':
                    if (is_array($value)) {
                        $value = implode('; ', $value);
                    }
                    break;
                    
                case 'keywords':
                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }
                    break;
                    
                case 'abstract':
                    // Clean HTML from abstract
                    $value = wp_strip_all_tags($value);
                    // Limit length for CSV
                    $value = $this->truncate_text($value, 500);
                    break;
                    
                case 'url':
                    // Validate URL
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                        $value = '';
                    }
                    break;
            }
            
            // Skip empty fields if option is set
            if (!$options['include_empty_fields'] && empty($value)) {
                $value = '';
            }
            
            $csv_row[] = (string) $value;
        }
        
        return $csv_row;
    }

    /**
     * Truncate text to specified length
     *
     * @param string $text Text to truncate
     * @param int $length Maximum length
     * @return string Truncated text
     */
    private function truncate_text($text, $length = 100) {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length - 3) . '...';
    }

    /**
     * Validate CSV content format
     *
     * @param string $csv_content CSV content
     * @return bool|WP_Error True if valid, error if not
     */
    public function validate($csv_content) {
        if (empty($csv_content)) {
            return new WP_Error('empty_content', __('CSV content is empty', 'academic-bloggers-toolkit'));
        }

        // Check if content looks like CSV
        $lines = explode("\n", $csv_content);
        $first_line = trim($lines[0] ?? '');
        
        if (empty($first_line)) {
            return new WP_Error('invalid_format', __('CSV appears to be empty or invalid', 'academic-bloggers-toolkit'));
        }

        // Check for CSV delimiters
        if (strpos($first_line, ',') === false && strpos($first_line, ';') === false && strpos($first_line, '\t') === false) {
            return new WP_Error('no_delimiters', __('No CSV delimiters found', 'academic-bloggers-toolkit'));
        }

        return true;
    }

    /**
     * Count references in CSV content
     *
     * @param string $csv_content CSV content
     * @return int Number of references (excluding header)
     */
    public function count_references($csv_content) {
        $lines = explode("\n", $csv_content);
        $line_count = 0;
        
        foreach ($lines as $line) {
            if (!empty(trim($line))) {
                $line_count++;
            }
        }
        
        // Subtract 1 for header row
        return max(0, $line_count - 1);
    }

    /**
     * Get CSV template
     *
     * @return string CSV template with headers
     */
    public function get_template() {
        $headers = array_values($this->field_headers);
        
        // Add example row
        $example_row = [
            '1', // ID
            'journal', // Type
            'Example Article Title', // Title
            'Smith, John; Doe, Jane', // Author
            '', // Editor
            '2023', // Year
            'Journal of Examples', // Publication
            'Journal of Examples', // Journal
            'Example Press', // Publisher
            '15', // Volume
            '3', // Issue
            '123-145', // Pages
            '10.1000/example.doi', // DOI
            '12345678', // PMID
            '978-0123456789', // ISBN
            '1234-5678', // ISSN
            'https://example.com', // URL
            'This is an example abstract...', // Abstract
            'example, research, academic', // Keywords
            'en', // Language
            'New York', // Location
            '1st', // Edition
            'Example notes' // Notes
        ];
        
        $csv_data = [$headers, $example_row];
        
        return $this->generate_csv_string($csv_data, [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape_char' => '\\'
        ]);
    }

    /**
     * Detect CSV delimiter
     *
     * @param string $csv_content CSV content
     * @return string Detected delimiter
     */
    public function detect_delimiter($csv_content) {
        $delimiters = [',', ';', '\t', '|'];
        $delimiter_counts = [];
        
        // Take first few lines for analysis
        $lines = array_slice(explode("\n", $csv_content), 0, 5);
        $sample = implode("\n", $lines);
        
        foreach ($delimiters as $delimiter) {
            $delimiter_counts[$delimiter] = substr_count($sample, $delimiter);
        }
        
        // Return delimiter with highest count
        return array_search(max($delimiter_counts), $delimiter_counts) ?: ',';
    }

    /**
     * Convert CSV to other formats
     *
     * @param string $csv_content CSV content
     * @param string $target_format Target format (ris, bibtex, json)
     * @return string|WP_Error Converted content or error
     */
    public function convert_to_format($csv_content, $target_format) {
        $references = $this->parse($csv_content);
        
        if (is_wp_error($references)) {
            return $references;
        }
        
        switch ($target_format) {
            case 'ris':
                $ris_parser = new ABT_RIS_Parser();
                return $ris_parser->export($references);
                
            case 'bibtex':
                $bibtex_parser = new ABT_BibTeX_Parser();
                return $bibtex_parser->export($references);
                
            case 'json':
                return wp_json_encode($references, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                
            default:
                return new WP_Error('unsupported_format', sprintf(__('Unsupported target format: %s', 'academic-bloggers-toolkit'), $target_format));
        }
    }

    /**
     * Get field headers mapping
     *
     * @return array Field headers mapping
     */
    public function get_field_headers() {
        return $this->field_headers;
    }

    /**
     * Update field headers mapping
     *
     * @param array $headers New headers mapping
     */
    public function set_field_headers($headers) {
        $this->field_headers = wp_parse_args($headers, $this->field_headers);
    }
}