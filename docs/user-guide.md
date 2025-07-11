# Academic Blogger's Toolkit - User Guide

## Table of Contents

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Getting Started](#getting-started)
4. [Academic Blog Posts](#academic-blog-posts)
5. [Reference Management](#reference-management)
6. [Citation System](#citation-system)
7. [Import & Export](#import--export)
8. [Shortcodes](#shortcodes)
9. [Templates & Themes](#templates--themes)
10. [Settings & Configuration](#settings--configuration)
11. [Troubleshooting](#troubleshooting)

## Introduction

The **Academic Blogger's Toolkit** is a comprehensive WordPress plugin designed specifically for academic writers, researchers, and institutions who want to create professional academic content with proper citation management, reference libraries, and scholarly formatting.

### Key Features

- **Academic Blog Post Type** - Specialized content type for scholarly articles
- **Reference Library** - Centralized management of academic sources
- **Automatic Citations** - Auto-generate citations from DOI, PMID, ISBN, or URL
- **Multiple Citation Styles** - APA, MLA, Chicago, Harvard, and custom styles
- **Bibliography Generation** - Automatic bibliography creation
- **Import/Export** - Support for RIS, BibTeX, and CSV formats
- **Footnote System** - Professional footnote management
- **Search & Analytics** - Advanced search and citation statistics
- **Responsive Design** - Mobile-friendly academic layouts

## Installation

### Requirements

- **WordPress:** 6.0 or higher
- **PHP:** 8.0 or higher
- **MySQL:** 5.7 or higher
- **Memory Limit:** 512MB recommended

### Installation Steps

1. **Download the Plugin**
   - Download the plugin ZIP file
   - Or install directly from WordPress admin

2. **Upload and Activate**
   ```
   WordPress Admin → Plugins → Add New → Upload Plugin
   Select the ZIP file → Install Now → Activate
   ```

3. **Initial Setup**
   - Go to `Academic Toolkit → Settings`
   - Configure your default citation style
   - Set up your institution information
   - Configure import/export preferences

## Getting Started

### First Steps

1. **Create Your First Reference**
   ```
   Academic Toolkit → References → Add New
   Fill in the reference details or use Auto-Cite
   ```

2. **Write Your First Academic Post**
   ```
   Academic Toolkit → Blog Posts → Add New
   Write your content and add citations
   ```

3. **Configure Citation Style**
   ```
   In the post editor → Academic Settings meta box
   Choose your preferred citation style
   ```

### Quick Start Checklist

- [ ] Install and activate the plugin
- [ ] Set default citation style in settings
- [ ] Add your first reference (try auto-cite with a DOI)
- [ ] Create your first academic blog post
- [ ] Add citations to your post
- [ ] Preview the generated bibliography
- [ ] Customize your theme templates (optional)

## Academic Blog Posts

### Creating Academic Content

Academic blog posts are a special content type designed for scholarly writing with enhanced features:

#### Post Types Available

1. **Research Article** - Full research papers
2. **Review Article** - Literature reviews
3. **Case Study** - Case study reports
4. **Opinion Piece** - Editorial content
5. **News** - Academic news and updates

#### Academic Metadata

Each academic post includes:

- **Abstract** - Brief summary of the content
- **Keywords** - Searchable terms
- **Subject Areas** - Academic disciplines
- **Reading Time** - Estimated reading duration
- **Peer Review Status** - Reviewed/not reviewed
- **Publication Date** - Original publication date

#### Writing Interface

The academic post editor includes:

**Citation Tools**
- Insert citations with `[cite:ref_id]`
- Quick reference search
- Auto-complete for existing references

**Academic Settings Meta Box**
- Citation style selection
- Enable/disable footnotes
- Bibliography placement
- Academic metadata fields

**Preview Features**
- Live bibliography preview
- Citation format preview
- Reading time calculation

### Managing Academic Content

#### Organizing Posts

**Categories and Tags**
```
Academic Categories: Research, Reviews, News, Opinions
Academic Tags: methodology, data-analysis, peer-review
Subject Areas: Computer Science, Psychology, Medicine
```

**Filtering and Search**
- Filter by publication date
- Search by keywords
- Filter by subject area
- Sort by citation count

#### Bulk Operations

- Export multiple posts
- Batch update citation styles
- Bulk categorization
- Mass reference updates

## Reference Management

### Adding References

#### Manual Entry

1. Go to `Academic Toolkit → References → Add New`
2. Select reference type (Article, Book, Website, etc.)
3. Fill in the required fields
4. Save the reference

#### Auto-Cite Features

**DOI Auto-Cite**
```
Enter DOI: 10.1038/nature12373
Click "Fetch from DOI"
Automatically populates all fields
```

**PubMed Auto-Cite**
```
Enter PMID: 23685631
Click "Fetch from PubMed"
Gets medical/scientific article data
```

**ISBN Auto-Cite**
```
Enter ISBN: 978-0123456789
Click "Fetch from ISBN"
Retrieves book information
```

**URL Scraping**
```
Enter URL: https://example.com/article
Click "Extract Metadata"
Attempts to extract title, author, date
```

### Reference Types

#### Journal Articles
Required fields:
- Title
- Authors
- Journal name
- Year
- Volume/Issue
- Page numbers
- DOI (recommended)

#### Books
Required fields:
- Title
- Authors/Editors
- Publisher
- Year
- Location
- ISBN (recommended)

#### Book Chapters
Required fields:
- Chapter title
- Book title
- Authors/Editors
- Publisher
- Year
- Page numbers

#### Websites
Required fields:
- Title
- Author (if available)
- Website name
- URL
- Access date

#### Conference Papers
Required fields:
- Title
- Authors
- Conference name
- Year
- Location
- Page numbers

### Managing Your Reference Library

#### Search and Filter

**Quick Search**
```
Search box: Enter title, author, or keyword
Instant results as you type
```

**Advanced Filters**
- Reference type
- Publication year range
- Author name
- Journal/Publisher
- Subject area

#### Bulk Operations

**Import References**
- Upload RIS files from reference managers
- Import BibTeX from LaTeX documents
- CSV import with custom mapping

**Export References**
- Export to RIS for Zotero/Mendeley
- Export to BibTeX for LaTeX
- CSV export for spreadsheets

**Bulk Edit**
- Update citation styles
- Batch categorization
- Mass field updates

## Citation System

### Citation Styles

#### Available Styles

**APA (American Psychological Association)**
- In-text: (Smith, 2023)
- Bibliography: Smith, J. (2023). Title. Journal, 42(3), 123-145.

**MLA (Modern Language Association)**
- In-text: (Smith 123)
- Bibliography: Smith, John. "Title." Journal, vol. 42, no. 3, 2023, pp. 123-145.

**Chicago (Chicago Manual of Style)**
- In-text: (Smith 2023, 123)
- Bibliography: Smith, John. "Title." Journal 42, no. 3 (2023): 123-145.

**Harvard**
- In-text: (Smith, 2023)
- Bibliography: Smith, J., 2023. Title. Journal, 42(3), pp.123-145.

**IEEE**
- In-text: [1]
- Bibliography: [1] J. Smith, "Title," Journal, vol. 42, no. 3, pp. 123-145, 2023.

#### Custom Styles

Create your own citation styles:

1. Go to `Academic Toolkit → Settings → Citation Styles`
2. Click "Add Custom Style"
3. Define in-text and bibliography formats
4. Use placeholders: `{author}`, `{year}`, `{title}`, etc.
5. Save and apply to posts

### Adding Citations to Posts

#### Using the Citation Button

1. In the post editor, place cursor where you want the citation
2. Click the "Insert Citation" button
3. Search for and select your reference
4. Add page numbers if needed
5. Click "Insert"

#### Manual Citation Codes

```
Basic citation: [cite:123]
With page number: [cite:123 page="45"]
Multiple pages: [cite:123 page="45-47"]
Specific type: [cite:123 type="footnote"]
```

#### Multiple Citations

```
Separate citations: [cite:123][cite:456]
Grouped citations: [cite:123,456,789]
```

### Bibliography Generation

#### Automatic Bibliography

The bibliography is automatically generated at the end of your post based on:
- All citations used in the post
- Selected citation style
- Sort order (alphabetical, by appearance, etc.)

#### Customizing Bibliography

**Bibliography Settings**
```
Title: "References", "Bibliography", "Works Cited"
Sort by: Author, Year, Title, Appearance
Show: All references or only cited ones
```

**Manual Placement**
```
Use shortcode: [abt_bibliography]
Disable auto-placement in settings
Control exact positioning
```

### Footnotes

#### Adding Footnotes

```
Text with footnote[footnote]This is the footnote text[/footnote]
```

#### Footnote Features

- Automatic numbering
- Click to jump between text and footnote
- Responsive design
- Print-friendly formatting

## Import & Export

### Importing References

#### From Reference Managers

**Zotero/Mendeley (RIS format)**
1. Export from your reference manager as RIS
2. Go to `Academic Toolkit → Import`
3. Select RIS file
4. Map fields if necessary
5. Import references

**EndNote (RIS format)**
1. Export from EndNote as RIS
2. Follow same import process

#### From LaTeX Documents

**BibTeX Import**
1. Export .bib file from your LaTeX document
2. Use BibTeX import option
3. Automatically converts to plugin format

#### From Spreadsheets

**CSV Import**
1. Export references from Excel/Google Sheets as CSV
2. Map columns to reference fields
3. Preview and import

### Exporting References

#### Export Formats

**RIS Format**
- Compatible with Zotero, Mendeley, EndNote
- Preserves all metadata
- Standard academic format

**BibTeX Format**
- For LaTeX documents
- Maintains citation keys
- Academic publishing standard

**CSV Format**
- For spreadsheets and databases
- Customizable field selection
- Easy data manipulation

#### Export Options

**Selective Export**
- Choose specific references
- Filter by type or date
- Export only cited references

**Bulk Export**
- Export entire library
- Include metadata
- Preserve categories

## Shortcodes

### Blog Display Shortcodes

#### Academic Blog List
```
[abt_blog_list]
[abt_blog_list posts_per_page="10"]
[abt_blog_list subject="Computer Science"]
[abt_blog_list show_excerpt="true"]
[abt_blog_list show_meta="true"]
```

#### Recent Academic Posts
```
[abt_recent_posts]
[abt_recent_posts count="5"]
[abt_recent_posts subject="Psychology"]
```

### Reference Display Shortcodes

#### Reference List
```
[abt_reference_list]
[abt_reference_list type="article"]
[abt_reference_list style="mla"]
[abt_reference_list limit="20"]
```

#### Popular References
```
[abt_popular_references]
[abt_popular_references count="10"]
[abt_popular_references show_count="true"]
```

### Citation Shortcodes

#### Single Citation
```
[abt_cite ref_id="123"]
[abt_cite ref_id="123" page="45"]
[abt_cite ref_id="123" style="mla"]
```

#### Bibliography Display
```
[abt_bibliography post_id="456"]
[abt_bibliography post_id="456" style="apa"]
[abt_bibliography post_id="456" sort_by="author"]
```

### Search Shortcodes

#### Search Form
```
[abt_search_form]
[abt_search_form show_filters="true"]
[abt_search_form ajax="true"]
[abt_search_form placeholder="Search academic content..."]
```

### Widget Shortcodes

#### Author Profile
```
[abt_author_profile user_id="123"]
[abt_author_profile user_id="123" show_orcid="true"]
[abt_author_profile user_id="123" show_institution="true"]
```

#### Citation Statistics
```
[abt_citation_stats]
[abt_citation_stats show="total_references,total_citations"]
[abt_citation_stats format="chart"]
```

#### Reading Time
```
[abt_reading_time post_id="456"]
[abt_reading_time post_id="456" format="Reading time: {time} minutes"]
```

## Templates & Themes

### Theme Integration

#### Template Hierarchy

The plugin follows WordPress template hierarchy:

```
single-abt_blog.php          → Single academic post
archive-abt_blog.php         → Academic blog archive
taxonomy-abt_subject.php     → Subject archive pages
taxonomy-abt_blog_category.php → Category archives
search-abt_blog.php          → Search results
```

#### Customizing Templates

**Override Plugin Templates**
1. Copy templates from plugin's `/templates/` folder
2. Place in your theme's `/abt-templates/` folder
3. Customize as needed

**Example: Custom Single Post Template**
```php
// In your theme: abt-templates/single-abt_blog.php
get_header(); ?>

<article class="academic-post">
    <header class="academic-header">
        <h1><?php the_title(); ?></h1>
        <div class="academic-meta">
            <?php echo abt_get_academic_meta(); ?>
        </div>
    </header>
    
    <div class="academic-content">
        <?php the_content(); ?>
    </div>
    
    <footer class="academic-footer">
        <?php echo abt_get_bibliography(); ?>
    </footer>
</article>

<?php get_footer();
```

### Styling Academic Content

#### CSS Classes

**Academic Posts**
```css
.abt-blog-post          → Academic post container
.abt-blog-meta          → Academic metadata
.abt-blog-abstract      → Abstract section
.abt-blog-keywords      → Keywords display
.abt-reading-time       → Reading time indicator
```

**Citations and Bibliography**
```css
.abt-citation           → Individual citations
.abt-citation-link      → Citation links
.abt-footnote           → Footnote markers
.abt-bibliography       → Bibliography container
.abt-reference-item     → Individual references
```

**Search and Navigation**
```css
.abt-search-form        → Search forms
.abt-search-filters     → Filter controls
.abt-pagination         → Pagination controls
.abt-subject-nav        → Subject navigation
```

#### Responsive Design

The plugin includes responsive CSS for:
- Mobile-friendly reading experience
- Touch-friendly citation interactions
- Collapsible reference lists
- Mobile search interfaces

## Settings & Configuration

### General Settings

#### Default Citation Style
```
Academic Toolkit → Settings → General
Choose default style: APA, MLA, Chicago, Harvard, IEEE
```

#### Institution Information
```
Institution Name: Your University
Department: Your Department
ORCID Integration: Enable/Disable
```

#### Content Settings
```
Auto-generate reading time: Yes/No
Show author bio on posts: Yes/No
Enable social sharing: Yes/No
```

### Citation Settings

#### Style Management
```
Default in-text format: (Author, Year)
Default bibliography format: APA
Allow per-post style override: Yes/No
```

#### Auto-Cite APIs
```
DOI API: Enable CrossRef integration
PubMed API: Enable medical article lookup
ISBN API: Enable book information lookup
URL Scraping: Enable metadata extraction
```

### Import/Export Settings

#### Default Formats
```
Preferred import format: RIS
Default export format: BibTeX
Field mapping preferences: Save custom mappings
```

#### Data Management
```
Auto-backup references: Weekly/Monthly
Duplicate detection: Enable/Disable
Data validation: Strict/Permissive
```

### Advanced Settings

#### Performance
```
Enable caching: Yes/No
Cache duration: 24 hours
Lazy load references: Yes/No
```

#### SEO and Analytics
```
Generate JSON-LD metadata: Yes/No
Track citation analytics: Yes/No
Google Scholar integration: Enable/Disable
```

## Troubleshooting

### Common Issues

#### Citations Not Displaying
**Problem:** Citations show as `[cite:123]` instead of formatted citations

**Solution:**
1. Check if citation style is set in post settings
2. Verify reference exists and is published
3. Clear any caching plugins
4. Check for JavaScript errors in browser console

#### Auto-Cite Not Working
**Problem:** DOI/PMID lookup fails

**Solution:**
1. Verify internet connection
2. Check if DOI/PMID is valid
3. Try again later (API may be temporarily down)
4. Add reference manually if auto-cite continues to fail

#### Bibliography Not Generating
**Problem:** Bibliography section is empty

**Solution:**
1. Ensure post contains citations
2. Check bibliography placement settings
3. Verify citation style supports bibliography
4. Clear post cache

#### Import Failures
**Problem:** Reference import fails or shows errors

**Solution:**
1. Check file format (RIS, BibTeX, CSV)
2. Verify file encoding (UTF-8 recommended)
3. Check for special characters in data
4. Try importing smaller batches

### Plugin Conflicts

#### Common Plugin Conflicts
- **Caching plugins:** May cache old citation formats
- **SEO plugins:** May interfere with metadata
- **Page builders:** May not process shortcodes correctly

#### Resolution Steps
1. Deactivate other plugins temporarily
2. Test academic toolkit functionality
3. Reactivate plugins one by one
4. Identify conflicting plugin
5. Check for plugin-specific compatibility settings

### Performance Issues

#### Slow Page Loading
**Causes:**
- Large reference libraries
- Complex bibliography generation
- Unoptimized database queries

**Solutions:**
1. Enable plugin caching
2. Optimize database tables
3. Use pagination for large reference lists
4. Consider CDN for static assets

#### High Memory Usage
**Causes:**
- Processing large import files
- Generating complex bibliographies
- Multiple concurrent auto-cite requests

**Solutions:**
1. Increase PHP memory limit
2. Process imports in smaller batches
3. Enable background processing for large operations

### Getting Help

#### Documentation Resources
- Plugin documentation: `/docs/` folder
- WordPress Codex: For general WordPress issues
- Citation Style Language: For custom styles

#### Support Channels
1. **Plugin Support Forum**
   - Post detailed error descriptions
   - Include WordPress and plugin versions
   - Provide relevant error logs

2. **GitHub Issues**
   - Report bugs with reproducible steps
   - Request new features
   - Contribute to development

3. **Email Support**
   - For sensitive issues
   - Enterprise support inquiries
   - Custom development requests

#### Before Contacting Support

**Information to Gather:**
- WordPress version
- Plugin version
- Active theme name
- List of active plugins
- Error messages (exact text)
- Steps to reproduce the issue
- Browser and device information

**Basic Troubleshooting:**
1. Update WordPress and all plugins
2. Switch to default theme temporarily
3. Deactivate other plugins
4. Clear all caches
5. Check error logs

---

*For the latest updates and advanced features, visit the [plugin website](https://wbcomdesigns.com) or check the [GitHub repository](https://github.com/wbcomdesigns/plugin).*