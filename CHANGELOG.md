# Changelog

All notable changes to the Academic Blogger's Toolkit will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Advanced search with metadata filters
- Citation analytics dashboard
- Bulk citation operations
- Export to LaTeX format
- REST API endpoints for external integrations

### Changed
- Improved citation processing performance
- Enhanced mobile responsiveness
- Updated citation style templates

### Deprecated
- Legacy citation format functions (will be removed in v2.0)

### Security
- Enhanced data sanitization for citation imports
- Improved nonce verification for AJAX requests

## [1.0.0] - 2024-01-15

### Added
- **Core Features**
  - Custom post type `abt_blog` for academic articles
  - Custom post type `abt_reference` for reference management
  - Citation processing engine with multiple styles (APA, MLA, Chicago, Harvard, IEEE)
  - Auto-cite functionality from DOI, PMID, ISBN, and URLs
  - Bibliography generation system
  - Footnote management with back-links

- **Admin Interface**
  - Meta boxes for academic metadata (abstract, keywords, DOI)
  - Reference management interface
  - Citation editor with drag-and-drop functionality
  - Bulk operations for references
  - Import/export tools for BibTeX and RIS formats
  - Settings page for citation styles and preferences

- **Frontend Features**
  - Academic blog post templates
  - Citation tooltips with hover previews
  - Responsive design for mobile devices
  - Search functionality with academic metadata
  - Author profile pages with academic information
  - Subject-based taxonomy for content organization

- **Shortcodes**
  - `[abt_blog_list]` - Display academic blog posts
  - `[abt_bibliography]` - Show bibliography for a post
  - `[abt_reference_list]` - Display reference library
  - `[abt_search_form]` - Academic search form
  - `[abt_author_profile]` - Author profile display
  - `[abt_citation_stats]` - Citation statistics widget

- **Widgets**
  - Recent Academic Posts widget
  - Popular References widget
  - Citation Statistics widget

- **Import/Export**
  - BibTeX file import and export
  - RIS format support
  - CSV bulk operations
  - Zotero/Mendeley compatibility

- **API Integrations**
  - CrossRef API for DOI resolution
  - PubMed API for biomedical literature
  - Google Books API for ISBN lookups
  - URL metadata scraping for web resources

- **Templates**
  - `single-abt_blog.php` - Single academic post template
  - `archive-abt_blog.php` - Academic blog archive
  - `taxonomy-abt_subject.php` - Subject archive pages
  - `taxonomy-abt_blog_category.php` - Category archive pages
  - `taxonomy-abt_blog_tag.php` - Tag archive pages
  - `search-abt_blog.php` - Search results template

- **Developer Features**
  - Comprehensive hook system for customization
  - Template override support in themes
  - PSR-4 autoloading for classes
  - PHPUnit testing framework
  - JavaScript unit tests with Jest
  - Webpack build system for assets
  - SCSS preprocessing for styles
  - ESLint and PHP_CodeSniffer for code quality

- **Internationalization**
  - Translation-ready with textdomain
  - POT file for translators
  - Spanish, French, and German translations included

- **Security**
  - Capability-based access control
  - Nonce verification for all forms
  - Data sanitization and validation
  - SQL injection prevention
  - XSS protection

- **Performance**
  - Lazy loading for citation tooltips
  - Caching for bibliography generation
  - Optimized database queries
  - Minified and concatenated assets

### Requirements
- WordPress 6.0 or higher
- PHP 8.0 or higher
- Modern browser with JavaScript enabled

### Installation Notes
- Plugin creates necessary database tables on activation
- Default citation style set to APA
- Sample academic post created on first activation
- Settings accessible via WordPress admin menu

### Migration Notes
- No migration needed for new installation
- Future versions will include migration scripts

### Known Issues
- Large bibliography generation may timeout on shared hosting
- Citation tooltips may not display correctly with some themes
- Search functionality requires permalink structure enabled

### Credits
- Citation Style Language (CSL) community for style definitions
- CrossRef for DOI resolution services
- PubMed for biomedical literature access
- WordPress community for development best practices

---

**Full Changelog**: https://github.com/wbcomdesigns/academic-bloggers-toolkit/compare/v0.9.0...v1.0.0