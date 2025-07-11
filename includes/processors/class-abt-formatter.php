<?php
/**
 * Formatter - Citation and Bibliography Formatting
 *
 * Handles the actual formatting of citations and bibliography entries
 *
 * @package Academic_Bloggers_Toolkit
 * @subpackage Processors
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Citation and Bibliography Formatter Class
 *
 * Provides formatting functionality for citations and bibliographies
 */
class ABT_Formatter {

    /**
     * Citation counter for numbered styles
     *
     * @var array
     */
    private $citation_numbers = [];

    /**
     * Bibliography counter
     *
     * @var int
     */
    private $bibliography_counter = 0;

    /**
     * Format an inline citation
     *
     * @param array $reference_data Reference data
     * @param array $citation Citation metadata
     * @param array $style_config Style configuration
     * @return string Formatted citation
     */
    public function format_citation($reference_data, $citation, $style_config) {
        $style_rules = $this->get_style_rules($style_config);
        
        // Handle numbered styles
        if (isset($style_rules['inline_citation']['numbered']) && $style_rules['inline_citation']['numbered']) {
            return $this->format_numbered_citation($reference_data, $citation, $style_rules);
        }

        // Handle author-date and author-page styles
        return $this->format_author_citation($reference_data, $citation, $style_rules);
    }

    /**
     * Format a bibliography entry
     *
     * @param array $reference_data Reference data
     * @param array $style_config Style configuration
     * @return string Formatted bibliography entry
     */
    public function format_bibliography_entry($reference_data, $style_config) {
        $style_rules = $this->get_style_rules($style_config);
        
        $type = $reference_data['type'] ?? 'article';
        
        switch ($type) {
            case 'book':
                return $this->format_book_entry($reference_data, $style_rules);
            case 'chapter':
                return $this->format_chapter_entry($reference_data, $style_rules);
            case 'article-journal':
                return $this->format_journal_entry($reference_data, $style_rules);
            case 'article-newspaper':
                return $this->format_newspaper_entry($reference_data, $style_rules);
            case 'webpage':
                return $this->format_webpage_entry($reference_data, $style_rules);
            case 'thesis':
                return $this->format_thesis_entry($reference_data, $style_rules);
            default:
                return $this->format_generic_entry($reference_data, $style_rules);
        }
    }

    /**
     * Get style formatting rules
     *
     * @param array $style_config Style configuration
     * @return array Formatting rules
     */
    private function get_style_rules($style_config) {
        $style_manager = new ABT_Style_Manager();
        return $style_manager->get_builtin_formatter($style_config['id'] ?? 'apa');
    }

    /**
     * Format numbered citation
     *
     * @param array $reference_data Reference data
     * @param array $citation Citation metadata
     * @param array $style_rules Style rules
     * @return string Formatted citation
     */
    private function format_numbered_citation($reference_data, $citation, $style_rules) {
        $reference_id = $citation['reference_id'];
        
        // Get or assign citation number
        if (!isset($this->citation_numbers[$reference_id])) {
            $this->citation_numbers[$reference_id] = count($this->citation_numbers) + 1;
        }
        
        $number = $this->citation_numbers[$reference_id];
        $format = $style_rules['inline_citation']['format'] ?? '[{number}]';
        
        // Add page numbers if specified
        $pages = $citation['pages'] ?? '';
        if (!empty($pages)) {
            $page_format = $style_rules['inline_citation']['page_format'] ?? ', p. {page}';
            $format .= str_replace('{page}', $pages, $page_format);
        }
        
        return str_replace('{number}', $number, $format);
    }

    /**
     * Format author-based citation
     *
     * @param array $reference_data Reference data
     * @param array $citation Citation metadata
     * @param array $style_rules Style rules
     * @return string Formatted citation
     */
    private function format_author_citation($reference_data, $citation, $style_rules) {
        $format = $style_rules['inline_citation']['format'] ?? '({author}, {year})';
        
        // Handle suppressed author
        if ($citation['suppress_author'] ?? false) {
            $format = $style_rules['inline_citation']['suppress_author_format'] ?? '({year})';
        }
        
        // Get author
        $author = $this->format_citation_author($reference_data, $style_rules);
        
        // Get year
        $year = $this->extract_year($reference_data);
        
        // Get pages
        $pages = $citation['pages'] ?? '';
        
        // Replace placeholders
        $formatted = str_replace(['{author}', '{year}'], [$author, $year], $format);
        
        // Add pages if specified
        if (!empty($pages)) {
            $page_format = $style_rules['inline_citation']['page_format'] ?? ', p. {page}';
            $formatted = rtrim($formatted, ')') . str_replace('{page}', $pages, $page_format) . ')';
        }
        
        // Add prefix and suffix
        $prefix = $citation['prefix'] ?? '';
        $suffix = $citation['suffix'] ?? '';
        
        if (!empty($prefix)) {
            $formatted = $prefix . ' ' . $formatted;
        }
        
        if (!empty($suffix)) {
            $formatted = $formatted . ' ' . $suffix;
        }
        
        return $formatted;
    }

    /**
     * Format author for citation
     *
     * @param array $reference_data Reference data
     * @param array $style_rules Style rules
     * @return string Formatted author
     */
    private function format_citation_author($reference_data, $style_rules) {
        $authors = $reference_data['author'] ?? [];
        
        if (empty($authors)) {
            return __('Anonymous', 'academic-bloggers-toolkit');
        }
        
        $author_format = $style_rules['inline_citation']['author_format'] ?? 'last_name_only';
        
        // Get first author
        $first_author = is_array($authors) ? $authors[0] : $authors;
        
        if (is_array($first_author)) {
            $last_name = $first_author['family'] ?? '';
        } else {
            // Parse name string
            $parts = explode(' ', trim($first_author));
            $last_name = array_pop($parts);
        }
        
        // Handle multiple authors
        if (is_array($authors) && count($authors) > 1) {
            if (count($authors) == 2) {
                $second_author = $authors[1];
                $second_last = is_array($second_author) ? $second_author['family'] : explode(' ', $second_author)[0];
                return $last_name . ' & ' . $second_last;
            } else {
                return $last_name . ' et al.';
            }
        }
        
        return $last_name;
    }

    /**
     * Extract year from reference data
     *
     * @param array $reference_data Reference data
     * @return string Year
     */
    private function extract_year($reference_data) {
        $issued = $reference_data['issued'] ?? null;
        
        if (is_array($issued) && isset($issued['date-parts'][0][0])) {
            return (string) $issued['date-parts'][0][0];
        }
        
        if (is_string($issued)) {
            return date('Y', strtotime($issued));
        }
        
        // Fallback to other date fields
        $year_field = $reference_data['year'] ?? $reference_data['date'] ?? '';
        
        if (!empty($year_field)) {
            return date('Y', strtotime($year_field));
        }
        
        return __('n.d.', 'academic-bloggers-toolkit'); // No date
    }

    /**
     * Format book bibliography entry
     *
     * @param array $reference_data Reference data
     * @param array $style_rules Style rules
     * @return string Formatted entry
     */
    private function format_book_entry($reference_data, $style_rules) {
        $parts = [];
        
        // Author
        $author = $this->format_bibliography_author($reference_data, $style_rules);
        if ($author) $parts[] = $author;
        
        // Year
        $year = $this->extract_year($reference_data);
        $parts[] = "($year).";
        
        // Title
        $title = $this->format_title($reference_data['title'] ?? '', 'book', $style_rules);
        if ($title) $parts[] = $title;
        
        // Publisher
        $publisher = $reference_data['publisher'] ?? '';
        if ($publisher) $parts[] = $publisher . '.';
        
        return implode(' ', $parts);
    }

    /**
     * Format journal article bibliography entry
     *
     * @param array $reference_data Reference data
     * @param array $style_rules Style rules
     * @return string Formatted entry
     */
    private function format_journal_entry($reference_data, $style_rules) {
        $parts = [];
        
        // Author
        $author = $this->format_bibliography_author($reference_data, $style_rules);
        if ($author) $parts[] = $author;
        
        // Year
        $year = $this->extract_year($reference_data);
        $parts[] = "($year).";
        
        // Title
        $title = $this->format_title($reference_data['title'] ?? '', 'article', $style_rules);
        if ($title) $parts[] = $title;
        
        // Journal
        $journal = $reference_data['container-title'] ?? $reference_data['journal'] ?? '';
        if ($journal) {
            $journal_formatted = $this->format_journal_title($journal, $style_rules);
            
            // Volume and issue
            $volume = $reference_data['volume'] ?? '';
            $issue = $reference_data['issue'] ?? '';
            
            if ($volume) {
                $journal_formatted .= ", $volume";
                if ($issue) {
                    $journal_formatted .= "($issue)";
                }
            }
            
            // Pages
            $pages = $reference_data['page'] ?? '';
            if ($pages) {
                $journal_formatted .= ", $pages";
            }
            
            $parts[] = $journal_formatted . '.';
        }
        
        // DOI or URL
        $doi = $reference_data['DOI'] ?? '';
        $url = $reference_data['URL'] ?? '';
        
        if ($doi) {
            $parts[] = "https://doi.org/$doi";
        } elseif ($url) {
            $parts[] = $url;
        }
        
        return implode(' ', $parts);
    }

    /**
     * Format webpage bibliography entry
     *
     * @param array $reference_data Reference data
     * @param array $style_rules Style rules
     * @return string Formatted entry
     */
    private function format_webpage_entry($reference_data, $style_rules) {
        $parts = [];
        
        // Author (or organization)
        $author = $this->format_bibliography_author($reference_data, $style_rules);
        if ($author) $parts[] = $author;
        
        // Year
        $year = $this->extract_year($reference_data);
        $parts[] = "($year).";
        
        // Title
        $title = $this->format_title($reference_data['title'] ?? '', 'webpage', $style_rules);
        if ($title) $parts[] = $title;
        
        // Website name
        $container = $reference_data['container-title'] ?? '';
        if ($container) $parts[] = $container . '.';
        
        // URL
        $url = $reference_data['URL'] ?? '';
        if ($url) $parts[] = $url;
        
        // Accessed date
        $accessed = $reference_data['accessed'] ?? '';
        if ($accessed) {
            $accessed_formatted = $this->format_access_date($accessed);
            $parts[] = $accessed_formatted;
        }
        
        return implode(' ', $parts);
    }

    /**
     * Format generic bibliography entry
     *
     * @param array $reference_data Reference data
     * @param array $style_rules Style rules
     * @return string Formatted entry
     */
    private function format_generic_entry($reference_data, $style_rules) {
        $parts = [];
        
        // Author
        $author = $this->format_bibliography_author($reference_data, $style_rules);
        if ($author) $parts[] = $author;
        
        // Year
        $year = $this->extract_year($reference_data);
        $parts[] = "($year).";
        
        // Title
        $title = $reference_data['title'] ?? '';
        if ($title) $parts[] = $title . '.';
        
        // Container/Publication
        $container = $reference_data['container-title'] ?? '';
        if ($container) $parts[] = $container . '.';
        
        // URL if available
        $url = $reference_data['URL'] ?? '';
        if ($url) $parts[] = $url;
        
        return implode(' ', $parts);
    }

    /**
     * Format bibliography author
     *
     * @param array $reference_data Reference data
     * @param array $style_rules Style rules
     * @return string Formatted author
     */
    private function format_bibliography_author($reference_data, $style_rules) {
        $authors = $reference_data['author'] ?? [];
        
        if (empty($authors)) {
            return '';
        }
        
        $author_format = $style_rules['bibliography']['author_format'] ?? 'last_first';
        $formatted_authors = [];
        
        if (!is_array($authors)) {
            $authors = [$authors];
        }
        
        foreach ($authors as $index => $author) {
            if (is_array($author)) {
                $given = $author['given'] ?? '';
                $family = $author['family'] ?? '';
                
                if ($index === 0) {
                    // First author: Last, First
                    $formatted_authors[] = trim("$family, $given");
                } else {
                    // Other authors: First Last
                    $formatted_authors[] = trim("$given $family");
                }
            } else {
                // String format - parse and format
                $parts = explode(' ', trim($author));
                $last = array_pop($parts);
                $first = implode(' ', $parts);
                
                if ($index === 0) {
                    $formatted_authors[] = trim("$last, $first");
                } else {
                    $formatted_authors[] = trim("$first $last");
                }
            }
        }
        
        // Join authors
        $count = count($formatted_authors);
        if ($count === 1) {
            return $formatted_authors[0] . '.';
        } elseif ($count === 2) {
            return $formatted_authors[0] . ', & ' . $formatted_authors[1] . '.';
        } else {
            $last_author = array_pop($formatted_authors);
            return implode(', ', $formatted_authors) . ', & ' . $last_author . '.';
        }
    }

    /**
     * Format title based on type and style
     *
     * @param string $title Title
     * @param string $type Reference type
     * @param array $style_rules Style rules
     * @return string Formatted title
     */
    private function format_title($title, $type, $style_rules) {
        if (empty($title)) return '';
        
        $title_format = $style_rules['bibliography']['title_format'] ?? 'sentence_case';
        
        // Apply case formatting
        switch ($title_format) {
            case 'title_case':
                $title = ucwords(strtolower($title));
                break;
            case 'sentence_case':
                $title = ucfirst(strtolower($title));
                break;
        }
        
        // Apply formatting based on type
        if (in_array($type, ['book', 'thesis'])) {
            return "<em>$title</em>.";
        } elseif ($type === 'article') {
            return "$title.";
        } else {
            return "$title.";
        }
    }

    /**
     * Format journal title
     *
     * @param string $journal Journal name
     * @param array $style_rules Style rules
     * @return string Formatted journal
     */
    private function format_journal_title($journal, $style_rules) {
        $journal_format = $style_rules['bibliography']['journal_format'] ?? 'italic';
        
        switch ($journal_format) {
            case 'italic':
                return "<em>$journal</em>";
            case 'abbreviated':
                return $this->abbreviate_journal($journal);
            default:
                return $journal;
        }
    }

    /**
     * Abbreviate journal name
     *
     * @param string $journal Journal name
     * @return string Abbreviated journal
     */
    private function abbreviate_journal($journal) {
        // Basic journal abbreviation rules
        $abbreviations = [
            'Journal' => 'J.',
            'International' => 'Int.',
            'American' => 'Am.',
            'British' => 'Br.',
            'European' => 'Eur.',
            'Proceedings' => 'Proc.',
            'Transactions' => 'Trans.',
            'Science' => 'Sci.',
            'Medicine' => 'Med.',
            'Research' => 'Res.'
        ];
        
        return str_replace(array_keys($abbreviations), array_values($abbreviations), $journal);
    }

    /**
     * Format access date
     *
     * @param mixed $accessed Access date data
     * @return string Formatted access date
     */
    private function format_access_date($accessed) {
        if (is_array($accessed) && isset($accessed['date-parts'][0])) {
            $date_parts = $accessed['date-parts'][0];
            $timestamp = mktime(0, 0, 0, $date_parts[1] ?? 1, $date_parts[2] ?? 1, $date_parts[0]);
            return 'Retrieved ' . date('F j, Y', $timestamp);
        }
        
        if (is_string($accessed)) {
            $timestamp = strtotime($accessed);
            if ($timestamp) {
                return 'Retrieved ' . date('F j, Y', $timestamp);
            }
        }
        
        return '';
    }

    /**
     * Reset citation numbering
     */
    public function reset_citation_numbers() {
        $this->citation_numbers = [];
        $this->bibliography_counter = 0;
    }

    /**
     * Get citation number for reference
     *
     * @param int $reference_id Reference ID
     * @return int Citation number
     */
    public function get_citation_number($reference_id) {
        return $this->citation_numbers[$reference_id] ?? 0;
    }
}