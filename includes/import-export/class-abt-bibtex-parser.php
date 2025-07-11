<?php
/**
 * BibTeX Parser - LaTeX Bibliography Format
 *
 * Handles parsing and generating BibTeX format files
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
 * BibTeX Parser Class
 *
 * Handles BibTeX format import and export operations
 */
class ABT_BibTeX_Parser {

    /**
     * BibTeX entry types
     *
     * @var array
     */
    private $bibtex_types = [
        'article' => 'Journal article',
        'book' => 'Book',
        'booklet' => 'Booklet',
        'inbook' => 'Part of a book',
        'incollection' => 'Part of a book with its own title',
        'inproceedings' => 'Conference paper',
        'conference' => 'Conference paper (alias)',
        'manual' => 'Technical manual',
        'mastersthesis' => 'Master\'s thesis',
        'phdthesis' => 'PhD thesis',
        'proceedings' => 'Conference proceedings',
        'techreport' => 'Technical report',
        'unpublished' => 'Unpublished work',
        'misc' => 'Miscellaneous'
    ];

    /**
     * Required fields for each entry type
     *
     * @var array
     */
    private $required_fields = [
        'article' => ['author', 'title', 'journal', 'year'],
        'book' => ['title', 'publisher', 'year'],
        'booklet' => ['title'],
        'inbook' => ['title', 'publisher', 'year'],
        'incollection' => ['author', 'title', 'booktitle', 'publisher', 'year'],
        'inproceedings' => ['author', 'title', 'booktitle', 'year'],
        'conference' => ['author', 'title', 'booktitle', 'year'],
        'manual' => ['title'],
        'mastersthesis' => ['author', 'title', 'school', 'year'],
        'phdthesis' => ['author', 'title', 'school', 'year'],
        'proceedings' => ['title', 'year'],
        'techreport' => ['author', 'title', 'institution', 'year'],
        'unpublished' => ['author', 'title', 'note'],
        'misc' => []
    ];

    /**
     * Field mappings from ABT to BibTeX
     *
     * @var array
     */
    private $field_mappings = [
        'title' => 'title',
        'author' => 'author',
        'editor' => 'editor',
        'year' => 'year',
        'journal' => 'journal',
        'publication' => 'journal',
        'book_title' => 'booktitle',
        'publisher' => 'publisher',
        'volume' => 'volume',
        'number' => 'number',
        'issue' => 'number',
        'pages' => 'pages',
        'doi' => 'doi',
        'isbn' => 'isbn',
        'issn' => 'issn',
        'url' => 'url',
        'note' => 'note',
        'notes' => 'note',
        'abstract' => 'abstract',
        'keywords' => 'keywords',
        'address' => 'address',
        'location' => 'address',
        'edition' => 'edition',
        'series' => 'series',
        'organization' => 'organization',
        'institution' => 'institution',
        'school' => 'school',
        'chapter' => 'chapter',
        'month' => 'month',
        'howpublished' => 'howpublished',
        'type' => 'type'
    ];

    /**
     * Parse BibTeX format data
     *
     * @param string $bibtex_content BibTeX content
     * @return array|WP_Error Parsed references or error
     */
    public function parse($bibtex_content) {
        if (empty($bibtex_content)) {
            return new WP_Error('empty_content', __('BibTeX content is empty', 'academic-bloggers-toolkit'));
        }

        // Clean the content
        $bibtex_content = $this->clean_bibtex_content($bibtex_content);

        $references = [];
        $entries = $this->extract_entries($bibtex_content);

        foreach ($entries as $entry) {
            $parsed_entry = $this->parse_entry($entry);
            if ($parsed_entry && !is_wp_error($parsed_entry)) {
                $references[] = $this->normalize_bibtex_reference($parsed_entry);
            }
        }

        if (empty($references)) {
            return new WP_Error('no_references', __('No valid references found in BibTeX data', 'academic-bloggers-toolkit'));
        }

        return $references;
    }

    /**
     * Export references to BibTeX format
     *
     * @param array $references Array of reference data
     * @param array $options Export options
     * @return string BibTeX formatted content
     */
    public function export($references, $options = []) {
        if (empty($references)) {
            return '';
        }

        $bibtex_content = [];
        
        // Add header comment
        $bibtex_content[] = sprintf(
            '%% BibTeX file exported by Academic Bloggers Toolkit on %s',
            date('Y-m-d H:i:s')
        );
        $bibtex_content[] = '% Total references: ' . count($references);
        $bibtex_content[] = '';

        foreach ($references as $reference) {
            $bibtex_entry = $this->format_reference_to_bibtex($reference, $options);
            $bibtex_content[] = $bibtex_entry;
            $bibtex_content[] = ''; // Empty line between entries
        }

        return implode("\n", $bibtex_content);
    }

    /**
     * Clean BibTeX content
     *
     * @param string $content BibTeX content
     * @return string Cleaned content
     */
    private function clean_bibtex_content($content) {
        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // Normalize line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        
        // Remove multiple consecutive blank lines
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        return trim($content);
    }

    /**
     * Extract BibTeX entries from content
     *
     * @param string $content BibTeX content
     * @return array Array of entry strings
     */
    private function extract_entries($content) {
        $entries = [];
        $entry_pattern = '/@\s*(\w+)\s*\{([^@]*)\}/is';
        
        if (preg_match_all($entry_pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $entries[] = [
                    'type' => strtolower(trim($match[1])),
                    'content' => trim($match[2])
                ];
            }
        }
        
        return $entries;
    }

    /**
     * Parse individual BibTeX entry
     *
     * @param array $entry_data Entry data with type and content
     * @return array|WP_Error Parsed entry or error
     */
    private function parse_entry($entry_data) {
        $type = $entry_data['type'];
        $content = $entry_data['content'];
        
        // Extract citation key (first part before comma)
        $first_comma = strpos($content, ',');
        if ($first_comma === false) {
            return new WP_Error('invalid_entry', __('Invalid BibTeX entry format', 'academic-bloggers-toolkit'));
        }
        
        $citation_key = trim(substr($content, 0, $first_comma));
        $fields_content = trim(substr($content, $first_comma + 1));
        
        // Parse fields
        $fields = $this->parse_fields($fields_content);
        
        return [
            'type' => $type,
            'citation_key' => $citation_key,
            'fields' => $fields
        ];
    }

    /**
     * Parse BibTeX fields
     *
     * @param string $fields_content Fields content
     * @return array Parsed fields
     */
    private function parse_fields($fields_content) {
        $fields = [];
        
        // Remove trailing comma and closing brace
        $fields_content = rtrim($fields_content, ',}');
        
        // Pattern to match field = {value} or field = "value"
        $field_pattern = '/(\w+)\s*=\s*([{"])((?:[^{}"]|{[^}]*})*)\2/';
        
        if (preg_match_all($field_pattern, $fields_content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $field_name = strtolower(trim($match[1]));
                $field_value = $this->clean_field_value($match[3]);
                $fields[$field_name] = $field_value;
            }
        }
        
        return $fields;
    }

    /**
     * Clean BibTeX field value
     *
     * @param string $value Field value
     * @return string Cleaned value
     */
    private function clean_field_value($value) {
        // Remove extra braces
        $value = preg_replace('/^\{(.*)\}$/', '$1', $value);
        
        // Handle LaTeX commands and special characters
        $latex_replacements = [
            '\\textbf{' => '<strong>',
            '\\textit{' => '<em>',
            '\\emph{' => '<em>',
            '}' => '',
            '\\"a' => 'ä', '\\"o' => 'ö', '\\"u' => 'ü',
            '\\"A' => 'Ä', '\\"O' => 'Ö', '\\"U' => 'Ü',
            '\\\'a' => 'á', '\\\'e' => 'é', '\\\'i' => 'í', '\\\'o' => 'ó', '\\\'u' => 'ú',
            '\\`a' => 'à', '\\`e' => 'è', '\\`i' => 'ì', '\\`o' => 'ò', '\\`u' => 'ù',
            '\\^a' => 'â', '\\^e' => 'ê', '\\^i' => 'î', '\\^o' => 'ô', '\\^u' => 'û',
            '\\~a' => 'ã', '\\~n' => 'ñ', '\\~o' => 'õ',
            '\\c{c}' => 'ç', '\\c{C}' => 'Ç',
            '\\&' => '&',
            '\\$' => '$',
            '\\%' => '%',
            '\\#' => '#',
            '\\_' => '_',
            '\\{' => '{',
            '\\}' => '}',
            '\\\\' => ' ',
            '---' => '—',
            '--' => '–',
            '``' => '"',
            "''" => '"'
        ];
        
        $value = str_replace(array_keys($latex_replacements), array_values($latex_replacements), $value);
        
        // Clean up multiple spaces
        $value = preg_replace('/\s+/', ' ', $value);
        
        return trim($value);
    }

    /**
     * Normalize BibTeX reference to standard format
     *
     * @param array $bibtex_entry Parsed BibTeX entry
     * @return array Normalized reference
     */
    private function normalize_bibtex_reference($bibtex_entry) {
        $normalized = [
            'type' => $this->map_bibtex_type_to_abt_type($bibtex_entry['type']),
            'bibtex_type' => $bibtex_entry['type'],
            'citation_key' => $bibtex_entry['citation_key']
        ];

        $fields = $bibtex_entry['fields'];

        // Map standard fields
        foreach ($this->field_mappings as $abt_field => $bibtex_field) {
            if (isset($fields[$bibtex_field])) {
                $normalized[$abt_field] = $fields[$bibtex_field];
            }
        }

        // Handle special cases
        if (isset($fields['title'])) {
            $normalized['title'] = $this->clean_title($fields['title']);
        }

        if (isset($fields['author'])) {
            $normalized['author'] = $this->format_authors($fields['author']);
        }

        if (isset($fields['editor'])) {
            $normalized['editor'] = $this->format_authors($fields['editor']);
        }

        if (isset($fields['pages'])) {
            $normalized['pages'] = $this->format_pages($fields['pages']);
        }

        if (isset($fields['month']) && isset($fields['year'])) {
            $normalized['date'] = $this->format_date($fields['month'], $fields['year']);
        } elseif (isset($fields['year'])) {
            $normalized['year'] = $fields['year'];
        }

        // Handle journal vs booktitle
        if (isset($fields['journal'])) {
            $normalized['journal'] = $fields['journal'];
        } elseif (isset($fields['booktitle'])) {
            $normalized['publication'] = $fields['booktitle'];
        }

        return $normalized;
    }

    /**
     * Format reference to BibTeX format
     *
     * @param array $reference Reference data
     * @param array $options Export options
     * @return string BibTeX formatted entry
     */
    private function format_reference_to_bibtex($reference, $options = []) {
        $bibtex_type = $this->map_abt_type_to_bibtex_type($reference['type'] ?? 'other');
        $citation_key = $this->generate_citation_key($reference);
        
        $bibtex_lines = [];
        $bibtex_lines[] = "@{$bibtex_type}{{$citation_key},";
        
        // Required and optional fields based on type
        $field_order = $this->get_field_order($bibtex_type);
        
        foreach ($field_order as $field) {
            $value = $this->get_field_value($reference, $field);
            if (!empty($value)) {
                $formatted_value = $this->format_bibtex_value($value);
                $bibtex_lines[] = "  {$field} = {{$formatted_value}},";
            }
        }
        
        // Remove trailing comma from last field
        if (count($bibtex_lines) > 1) {
            $last_line = rtrim(end($bibtex_lines), ',');
            $bibtex_lines[count($bibtex_lines) - 1] = $last_line;
        }
        
        $bibtex_lines[] = "}";
        
        return implode("\n", $bibtex_lines);
    }

    /**
     * Map BibTeX type to ABT type
     *
     * @param string $bibtex_type BibTeX type
     * @return string ABT type
     */
    private function map_bibtex_type_to_abt_type($bibtex_type) {
        $mapping = [
            'article' => 'journal',
            'book' => 'book',
            'inbook' => 'chapter',
            'incollection' => 'chapter',
            'inproceedings' => 'conference',
            'conference' => 'conference',
            'phdthesis' => 'thesis',
            'mastersthesis' => 'thesis',
            'techreport' => 'report',
            'manual' => 'report',
            'booklet' => 'other',
            'proceedings' => 'conference',
            'unpublished' => 'other',
            'misc' => 'other'
        ];

        return $mapping[$bibtex_type] ?? 'other';
    }

    /**
     * Map ABT type to BibTeX type
     *
     * @param string $abt_type ABT type
     * @return string BibTeX type
     */
    private function map_abt_type_to_bibtex_type($abt_type) {
        $mapping = [
            'journal' => 'article',
            'book' => 'book',
            'chapter' => 'incollection',
            'conference' => 'inproceedings',
            'thesis' => 'phdthesis',
            'report' => 'techreport',
            'website' => 'misc',
            'newspaper' => 'article',
            'magazine' => 'article',
            'other' => 'misc'
        ];

        return $mapping[$abt_type] ?? 'misc';
    }

    /**
     * Clean title (remove extra braces)
     *
     * @param string $title Title
     * @return string Cleaned title
     */
    private function clean_title($title) {
        // Remove unnecessary braces around entire title
        $title = preg_replace('/^\{(.*)\}$/', '$1', $title);
        return $title;
    }

    /**
     * Format authors for BibTeX
     *
     * @param string $authors Authors string
     * @return string Formatted authors
     */
    private function format_authors($authors) {
        // BibTeX uses " and " to separate authors
        return str_replace(['; ', ', and ', ' & '], ' and ', $authors);
    }

    /**
     * Format pages for BibTeX
     *
     * @param string $pages Pages string
     * @return string Formatted pages
     */
    private function format_pages($pages) {
        // BibTeX uses -- for page ranges
        return str_replace('-', '--', $pages);
    }

    /**
     * Format date from month and year
     *
     * @param string $month Month
     * @param string $year Year
     * @return string Formatted date
     */
    private function format_date($month, $year) {
        $months = [
            'jan' => 'January', 'feb' => 'February', 'mar' => 'March',
            'apr' => 'April', 'may' => 'May', 'jun' => 'June',
            'jul' => 'July', 'aug' => 'August', 'sep' => 'September',
            'oct' => 'October', 'nov' => 'November', 'dec' => 'December'
        ];
        
        $month_name = $months[strtolower($month)] ?? $month;
        return $month_name . ' ' . $year;
    }

    /**
     * Generate citation key
     *
     * @param array $reference Reference data
     * @return string Citation key
     */
    private function generate_citation_key($reference) {
        $key_parts = [];
        
        // Get first author's last name
        if (!empty($reference['author'])) {
            $authors = explode(' and ', $reference['author']);
            $first_author = trim($authors[0]);
            
            // Extract last name
            if (strpos($first_author, ',') !== false) {
                $name_parts = explode(',', $first_author);
                $last_name = trim($name_parts[0]);
            } else {
                $name_parts = explode(' ', $first_author);
                $last_name = end($name_parts);
            }
            
            $key_parts[] = strtolower(preg_replace('/[^a-zA-Z]/', '', $last_name));
        }
        
        // Add year
        if (!empty($reference['year'])) {
            $key_parts[] = $reference['year'];
        }
        
        // Add first word of title
        if (!empty($reference['title'])) {
            $title_words = explode(' ', $reference['title']);
            $first_word = strtolower(preg_replace('/[^a-zA-Z]/', '', $title_words[0]));
            if (strlen($first_word) > 2) {
                $key_parts[] = $first_word;
            }
        }
        
        if (empty($key_parts)) {
            $key_parts[] = 'ref' . time();
        }
        
        return implode('', $key_parts);
    }

    /**
     * Get field order for BibTeX type
     *
     * @param string $bibtex_type BibTeX type
     * @return array Field order
     */
    private function get_field_order($bibtex_type) {
        $orders = [
            'article' => ['author', 'title', 'journal', 'year', 'volume', 'number', 'pages', 'doi', 'url'],
            'book' => ['author', 'editor', 'title', 'publisher', 'address', 'year', 'isbn', 'url'],
            'incollection' => ['author', 'title', 'booktitle', 'editor', 'publisher', 'address', 'year', 'pages'],
            'inproceedings' => ['author', 'title', 'booktitle', 'year', 'pages', 'organization', 'address'],
            'phdthesis' => ['author', 'title', 'school', 'year', 'type', 'address'],
            'techreport' => ['author', 'title', 'institution', 'year', 'number', 'type', 'address']
        ];
        
        return $orders[$bibtex_type] ?? ['author', 'title', 'year', 'note'];
    }

    /**
     * Get field value from reference
     *
     * @param array $reference Reference data
     * @param string $field Field name
     * @return string Field value
     */
    private function get_field_value($reference, $field) {
        $mappings = [
            'author' => 'author',
            'editor' => 'editor',
            'title' => 'title',
            'journal' => ['journal', 'publication'],
            'booktitle' => ['book_title', 'publication'],
            'publisher' => 'publisher',
            'year' => 'year',
            'volume' => 'volume',
            'number' => ['issue', 'number'],
            'pages' => 'pages',
            'doi' => 'doi',
            'isbn' => 'isbn',
            'url' => 'url',
            'address' => ['location', 'address'],
            'school' => ['institution', 'school'],
            'institution' => 'institution',
            'organization' => 'organization',
            'note' => ['notes', 'note']
        ];
        
        $source_fields = $mappings[$field] ?? $field;
        
        if (is_array($source_fields)) {
            foreach ($source_fields as $source_field) {
                if (!empty($reference[$source_field])) {
                    return $reference[$source_field];
                }
            }
        } else {
            return $reference[$source_fields] ?? '';
        }
        
        return '';
    }

    /**
     * Format value for BibTeX output
     *
     * @param string $value Value to format
     * @return string Formatted value
     */
    private function format_bibtex_value($value) {
        // Escape special characters
        $value = str_replace(['&', '$', '%', '#', '_', '{', '}'], ['\\&', '\\$', '\\%', '\\#', '\\_', '\\{', '\\}'], $value);
        
        // Handle page ranges
        $value = str_replace('–', '--', $value);
        $value = str_replace('—', '---', $value);
        
        return $value;
    }

    /**
     * Validate BibTeX content
     *
     * @param string $bibtex_content BibTeX content
     * @return bool|WP_Error True if valid, error if not
     */
    public function validate($bibtex_content) {
        if (empty($bibtex_content)) {
            return new WP_Error('empty_content', __('BibTeX content is empty', 'academic-bloggers-toolkit'));
        }

        // Check for BibTeX entry pattern
        if (!preg_match('/@\s*\w+\s*\{/', $bibtex_content)) {
            return new WP_Error('invalid_format', __('Content does not appear to be in BibTeX format', 'academic-bloggers-toolkit'));
        }

        // Check for balanced braces
        $open_braces = substr_count($bibtex_content, '{');
        $close_braces = substr_count($bibtex_content, '}');
        
        if ($open_braces !== $close_braces) {
            return new WP_Error('unbalanced_braces', __('Unbalanced braces in BibTeX content', 'academic-bloggers-toolkit'));
        }

        return true;
    }

    /**
     * Count entries in BibTeX content
     *
     * @param string $bibtex_content BibTeX content
     * @return int Number of entries
     */
    public function count_entries($bibtex_content) {
        return preg_match_all('/@\s*\w+\s*\{/', $bibtex_content);
    }

    /**
     * Get BibTeX types
     *
     * @return array BibTeX types
     */
    public function get_bibtex_types() {
        return $this->bibtex_types;
    }

    /**
     * Generate BibTeX template
     *
     * @param string $entry_type Entry type
     * @return string BibTeX template
     */
    public function generate_template($entry_type = 'article') {
        $templates = [
            'article' => [
                '@article{key,',
                '  author = {Author Name},',
                '  title = {Article Title},',
                '  journal = {Journal Name},',
                '  year = {Year},',
                '  volume = {Volume},',
                '  number = {Number},',
                '  pages = {Start--End},',
                '  doi = {DOI}',
                '}'
            ],
            'book' => [
                '@book{key,',
                '  author = {Author Name},',
                '  title = {Book Title},',
                '  publisher = {Publisher},',
                '  year = {Year},',
                '  address = {City},',
                '  isbn = {ISBN}',
                '}'
            ]
        ];
        
        return implode("\n", $templates[$entry_type] ?? $templates['article']);
    }
}