# Academic Blogger's Toolkit

A complete academic citation management system for WordPress. Create academic blog posts with proper citations, footnotes, and bibliographies. Features auto-cite from DOI, PMID, ISBN, and URL sources.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)

## 🎓 Features

### Academic Blog System
- **Custom Post Type**: Dedicated `abt_blog` post type for academic articles
- **Academic Metadata**: Abstract, keywords, DOI, publication date, and more
- **Subject Classification**: Organize content by academic disciplines
- **Author Profiles**: Extended author information with affiliations and ORCID

### Citation Management
- **Auto-Cite**: Automatically fetch citations from DOI, PMID, ISBN, and URLs
- **Multiple Styles**: Support for APA, MLA, Chicago, Harvard, IEEE, and more
- **Reference Library**: Centralized reference management system
- **Bibliography Generation**: Automatic bibliography creation from citations

### Import/Export
- **BibTeX Support**: Import and export BibTeX files
- **RIS Format**: Support for Reference Information Systems format
- **CSV Handling**: Bulk import/export via CSV files
- **Cross-Platform**: Compatible with Zotero, Mendeley, EndNote

### Frontend Features
- **Citation Tooltips**: Interactive citation previews
- **Footnote System**: Academic-style footnotes with back-links
- **Search & Filter**: Advanced search with academic metadata
- **Responsive Design**: Mobile-friendly academic layouts

## 📦 Installation

### From WordPress Admin
1. Download the latest release from [GitHub Releases](https://github.com/academic-bloggers-toolkit/academic-bloggers-toolkit/releases)
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the downloaded ZIP file and click **Install Now**
4. Activate the plugin

### Manual Installation
1. Upload the plugin files to `/wp-content/plugins/academic-bloggers-toolkit/`
2. Activate the plugin through the **Plugins** menu in WordPress

### Via Composer
```bash
composer require academic-bloggers-toolkit/academic-bloggers-toolkit
```

## 🚀 Quick Start

### 1. Create Your First Academic Post
1. Go to **Academic Blog > Add New**
2. Fill in the academic metadata (abstract, keywords, DOI)
3. Add citations using the citation editor
4. Publish your academic article

### 2. Manage References
1. Navigate to **Academic Blog > References**
2. Add references manually or import from DOI/PMID
3. Organize references by categories
4. Use references in your blog posts

### 3. Configure Citation Style
1. Go to **Academic Blog > Settings**
2. Choose your preferred citation style (APA, MLA, etc.)
3. Customize bibliography formatting
4. Set up auto-cite preferences

## 📚 Usage

### Adding Citations
```html
<!-- In your post content -->
This research shows significant results [abt_cite id="123" page="45"].

<!-- Will render as -->
This research shows significant results (Smith, 2023, p. 45).
```

### Creating Footnotes
```html
<!-- In your post content -->
This is an important point[abt_footnote]Additional explanation here[/abt_footnote].
```

### Displaying Bibliography
```php
// Shortcode
[abt_bibliography style="apa" title="References"]

// Template function
<?php echo do_shortcode('[abt_bibliography]'); ?>
```

### Listing Academic Posts
```php
// Show recent academic posts
[abt_blog_list posts_per_page="10" orderby="date" show_excerpt="true"]

// Filter by subject
[abt_blog_list subject="computer-science" posts_per_page="5"]
```

## 🛠️ Development

### Requirements
- PHP 8.0 or higher
- WordPress 6.0 or higher
- Node.js 16+ (for development)
- Composer (for PHP dependencies)

### Setup Development Environment
```bash
# Clone the repository
git clone https://github.com/academic-bloggers-toolkit/academic-bloggers-toolkit.git
cd academic-bloggers-toolkit

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Build assets
npm run build

# Watch for changes during development
npm run watch
```

### File Structure
```
academic-bloggers-toolkit/
├── admin/                    # Admin interface
│   ├── css/                 # Admin styles
│   ├── js/                  # Admin JavaScript
│   ├── pages/               # Admin pages
│   └── partials/            # Admin templates
├── includes/                # Core PHP classes
│   ├── post-types/          # Custom post types
│   ├── processors/          # Citation processing
│   ├── fetchers/            # Auto-cite APIs
│   └── import-export/       # File handling
├── public/                  # Frontend functionality
│   ├── css/                 # Frontend styles
│   ├── js/                  # Frontend JavaScript
│   └── partials/            # Frontend templates
├── templates/               # Page templates
├── assets/                  # Citation styles & assets
└── languages/               # Translation files
```

### Available Commands
```bash
# Development
npm run dev          # Build for development
npm run watch        # Watch files for changes
npm run build        # Build for production

# Testing
npm run test         # Run JavaScript tests
composer test        # Run PHP tests
composer lint        # Check coding standards

# Validation
npm run validate     # Run all validation checks
composer validate    # Run PHP validation
```

## 🎨 Customization

### Custom Citation Styles
Create custom CSL (Citation Style Language) files in `/assets/citation-styles/`:

```xml
<?xml version="1.0" encoding="utf-8"?>
<style xmlns="http://purl.org/net/xbiblio/csl" version="1.0">
  <!-- Your custom citation style -->
</style>
```

### Template Overrides
Override plugin templates in your theme:

```
your-theme/
└── academic-bloggers-toolkit/
    ├── single-abt_blog.php
    ├── archive-abt_blog.php
    └── shortcodes/
        └── blog-list.php
```

### Hooks & Filters
```php
// Modify citation format
add_filter( 'abt_format_citation', function( $citation, $style, $data ) {
    // Custom citation formatting
    return $citation;
}, 10, 3 );

// Add custom metadata fields
add_action( 'abt_save_post_meta', function( $post_id, $meta_data ) {
    // Save custom metadata
} );
```

## 🌐 Internationalization

The plugin is translation-ready. To contribute translations:

1. Copy `/languages/academic-bloggers-toolkit.pot`
2. Translate using tools like Poedit
3. Save as `academic-bloggers-toolkit-{locale}.po`
4. Submit via pull request

Currently supported languages:
- English (default)
- Spanish (es_ES)
- French (fr_FR)  
- German (de_DE)

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Development Workflow
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests (`npm run validate && composer validate`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Reporting Issues
- Use the [GitHub Issues](https://github.com/academic-bloggers-toolkit/academic-bloggers-toolkit/issues) page
- Include WordPress version, PHP version, and plugin version
- Provide steps to reproduce the issue
- Include relevant error messages or screenshots

## 📄 License

This project is licensed under the GPL-2.0-or-later License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [Citation Style Language](https://citationstyles.org/) for citation formatting
- [CrossRef](https://www.crossref.org/) for DOI resolution
- [PubMed](https://pubmed.ncbi.nlm.nih.gov/) for biomedical literature
- [WordPress](https://wordpress.org/) for the excellent platform

## 📞 Support

- **Documentation**: [https://academic-bloggers-toolkit.com/docs/](https://academic-bloggers-toolkit.com/docs/)
- **Support Forum**: [https://academic-bloggers-toolkit.com/support/](https://academic-bloggers-toolkit.com/support/)
- **Email**: support@academic-bloggers-toolkit.com

## 🗺️ Roadmap

### Version 1.1 (Planned)
- [ ] Advanced search with filters
- [ ] Citation analytics and metrics
- [ ] Collaboration features
- [ ] REST API endpoints

### Version 1.2 (Planned)
- [ ] LaTeX export support
- [ ] Integration with external databases
- [ ] Multi-site network support
- [ ] Advanced template system

---

Made with ❤️ for the academic community