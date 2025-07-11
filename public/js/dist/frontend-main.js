/**
 * Academic Blogger's Toolkit - Frontend JavaScript
 * 
 * Handles interactive features for academic blog posts
 */

(function($) {
    'use strict';

    // Main ABT Frontend Object
    const ABT_Frontend = {
        
        // Initialize all features
        init: function() {
            this.initReadingProgress();
            this.initBackToTop();
            this.initCitationTooltips();
            this.initFootnoteHandlers();
            this.initArchiveFilters();
            this.initViewToggle();
            this.initSharingButtons();
            this.initSmoothScrolling();
            this.initAccessibility();
        },

        // Reading Progress Bar
        initReadingProgress: function() {
            if (!$('.abt-reading-progress').length) return;

            const $progressBar = $('.abt-progress-bar');
            const $article = $('.abt-article-content');
            
            if (!$article.length) return;

            const updateProgress = () => {
                const articleTop = $article.offset().top;
                const articleHeight = $article.outerHeight();
                const scrollTop = $(window).scrollTop();
                const windowHeight = $(window).height();
                
                const progress = Math.max(0, Math.min(100, 
                    ((scrollTop - articleTop + windowHeight * 0.3) / articleHeight) * 100
                ));
                
                $progressBar.css('width', progress + '%');
            };

            $(window).on('scroll', updateProgress);
            updateProgress(); // Initial call
        },

        // Back to Top Button
        initBackToTop: function() {
            const $backToTop = $('.abt-back-to-top');
            if (!$backToTop.length) return;

            const toggleVisibility = () => {
                if ($(window).scrollTop() > 300) {
                    $backToTop.addClass('visible');
                } else {
                    $backToTop.removeClass('visible');
                }
            };

            $(window).on('scroll', toggleVisibility);
            
            $backToTop.on('click', function(e) {
                e.preventDefault();
                $('html, body').animate({ scrollTop: 0 }, 600);
            });

            toggleVisibility(); // Initial call
        },

        // Citation Tooltips
        initCitationTooltips: function() {
            const $citations = $('.abt-citation');
            if (!$citations.length) return;

            $citations.each(function() {
                const $citation = $(this);
                const citationId = $citation.data('citation-id');
                
                if (!citationId) return;

                $citation.on('mouseenter', function() {
                    // Create tooltip with citation preview
                    const tooltipContent = $citation.attr('title') || 'Loading citation...';
                    const $tooltip = $('<div class="abt-citation-tooltip">' + tooltipContent + '</div>');
                    
                    $citation.append($tooltip);
                    
                    // Fetch full citation details via AJAX if needed
                    if (tooltipContent === 'Loading citation...') {
                        ABT_Frontend.loadCitationDetails(citationId, $tooltip);
                    }
                });

                $citation.on('mouseleave', function() {
                    $citation.find('.abt-citation-tooltip').remove();
                });

                // Click handler for mobile devices
                $citation.on('click', function(e) {
                    e.preventDefault();
                    const referenceId = $citation.attr('href').replace('#', '');
                    const $reference = $('#' + referenceId);
                    
                    if ($reference.length) {
                        $('html, body').animate({
                            scrollTop: $reference.offset().top - 100
                        }, 500);
                        
                        // Highlight the reference briefly
                        $reference.addClass('highlighted');
                        setTimeout(() => $reference.removeClass('highlighted'), 2000);
                    }
                });
            });
        },

        // Load citation details via AJAX
        loadCitationDetails: function(citationId, $tooltip) {
            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_get_citation_preview',
                    citation_id: citationId,
                    nonce: abt_frontend_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        $tooltip.html(response.data);
                    }
                },
                error: function() {
                    $tooltip.html('Unable to load citation details');
                }
            });
        },

        // Footnote Handlers
        initFootnoteHandlers: function() {
            const $footnotes = $('.abt-footnote');
            if (!$footnotes.length) return;

            $footnotes.on('click', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                const $target = $(href);
                
                if ($target.length) {
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 100
                    }, 500);
                    
                    // Highlight footnote
                    $target.addClass('highlighted');
                    setTimeout(() => $target.removeClass('highlighted'), 2000);
                }
            });

            // Back to content links in footnotes
            $(document).on('click', '.abt-footnote-back', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                const $target = $(href);
                
                if ($target.length) {
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 100
                    }, 500);
                }
            });
        },

        // Archive Filters
        initArchiveFilters: function() {
            const $filters = $('.abt-filter-select');
            if (!$filters.length) return;

            $filters.on('change', function() {
                ABT_Frontend.applyFilters();
            });
        },

        // Apply archive filters
        applyFilters: function() {
            const subject = $('#abt-subject-filter').val();
            const category = $('#abt-category-filter').val();
            const sort = $('#abt-sort-filter').val();
            
            // Build URL with filters
            const url = new URL(window.location);
            const params = new URLSearchParams();
            
            if (subject) params.set('subject', subject);
            if (category) params.set('category', category);
            if (sort) params.set('orderby', sort);
            
            // Update URL and reload
            const newUrl = url.pathname + (params.toString() ? '?' + params.toString() : '');
            window.location.href = newUrl;
        },

        // View Toggle (List/Grid)
        initViewToggle: function() {
            const $toggleBtns = $('.abt-view-btn');
            const $container = $('#abt-posts-container');
            
            if (!$toggleBtns.length || !$container.length) return;

            $toggleBtns.on('click', function() {
                const view = $(this).data('view');
                
                // Update active state
                $toggleBtns.removeClass('active');
                $(this).addClass('active');
                
                // Update container class
                $container.removeClass('abt-view-list abt-view-grid');
                $container.addClass('abt-view-' + view);
                
                // Save preference
                localStorage.setItem('abt_view_preference', view);
            });

            // Load saved preference
            const savedView = localStorage.getItem('abt_view_preference');
            if (savedView) {
                $(`.abt-view-btn[data-view="${savedView}"]`).click();
            }
        },

        // Sharing Buttons
        initSharingButtons: function() {
            // Copy link functionality
            $('.abt-share-copy').on('click', function() {
                const url = $(this).data('url');
                
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(url).then(() => {
                        ABT_Frontend.showNotification('Link copied to clipboard!', 'success');
                    }).catch(() => {
                        ABT_Frontend.fallbackCopyToClipboard(url);
                    });
                } else {
                    ABT_Frontend.fallbackCopyToClipboard(url);
                }
            });

            // Social sharing tracking
            $('.abt-share-twitter, .abt-share-linkedin').on('click', function() {
                const platform = $(this).hasClass('abt-share-twitter') ? 'twitter' : 'linkedin';
                ABT_Frontend.trackSharing(platform);
            });
        },

        // Fallback copy to clipboard
        fallbackCopyToClipboard: function(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                ABT_Frontend.showNotification('Link copied to clipboard!', 'success');
            } catch (err) {
                ABT_Frontend.showNotification('Unable to copy link', 'error');
            }
            
            document.body.removeChild(textArea);
        },

        // Track sharing events
        trackSharing: function(platform) {
            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_track_sharing',
                    platform: platform,
                    post_id: $('body').data('post-id') || 0,
                    nonce: abt_frontend_ajax.nonce
                }
            });
        },

        // Smooth Scrolling for internal links
        initSmoothScrolling: function() {
            $('a[href^="#"]').on('click', function(e) {
                const target = $(this.getAttribute('href'));
                
                if (target.length) {
                    e.preventDefault();
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 500);
                }
            });
        },

        // Accessibility Features
        initAccessibility: function() {
            // Skip to content link
            this.addSkipLink();
            
            // Keyboard navigation for tooltips
            $('.abt-citation').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });

            // Focus management for modals/tooltips
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.abt-citation-tooltip').remove();
                }
            });

            // Announce dynamic content changes
            this.setupAriaLive();
        },

        // Add skip to content link
        addSkipLink: function() {
            if ($('#abt-skip-link').length) return;
            
            const skipLink = $('<a href="#main" id="abt-skip-link" class="screen-reader-text">Skip to content</a>');
            $('body').prepend(skipLink);
            
            skipLink.on('focus', function() {
                $(this).removeClass('screen-reader-text');
            }).on('blur', function() {
                $(this).addClass('screen-reader-text');
            });
        },

        // Setup ARIA live region for announcements
        setupAriaLive: function() {
            if ($('#abt-aria-live').length) return;
            
            const liveRegion = $('<div id="abt-aria-live" aria-live="polite" aria-atomic="true" class="screen-reader-text"></div>');
            $('body').append(liveRegion);
        },

        // Show notification to users
        showNotification: function(message, type = 'info') {
            // Update ARIA live region
            $('#abt-aria-live').text(message);
            
            // Visual notification
            const notification = $(`
                <div class="abt-notification abt-notification-${type}">
                    <span class="abt-notification-message">${message}</span>
                    <button class="abt-notification-close" aria-label="Close notification">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 3000);
            
            // Manual close
            notification.find('.abt-notification-close').on('click', function() {
                notification.fadeOut(() => notification.remove());
            });
        },

        // Search Enhancement
        initSearchEnhancement: function() {
            const $searchForm = $('.abt-search-form');
            if (!$searchForm.length) return;

            const $searchField = $searchForm.find('.abt-search-field');
            
            // Add search suggestions
            $searchField.on('input', debounce(function() {
                const query = $(this).val();
                if (query.length >= 3) {
                    ABT_Frontend.getSearchSuggestions(query);
                } else {
                    $('.abt-search-suggestions').remove();
                }
            }, 300));

            // Hide suggestions when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.abt-search-form').length) {
                    $('.abt-search-suggestions').remove();
                }
            });
        },

        // Get search suggestions
        getSearchSuggestions: function(query) {
            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_search_suggestions',
                    query: query,
                    nonce: abt_frontend_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length) {
                        ABT_Frontend.displaySearchSuggestions(response.data);
                    }
                }
            });
        },

        // Display search suggestions
        displaySearchSuggestions: function(suggestions) {
            $('.abt-search-suggestions').remove();
            
            const $suggestions = $('<div class="abt-search-suggestions"></div>');
            
            suggestions.forEach(suggestion => {
                const $item = $(`
                    <div class="abt-suggestion-item">
                        <a href="${suggestion.url}">${suggestion.title}</a>
                        <span class="abt-suggestion-type">${suggestion.type}</span>
                    </div>
                `);
                $suggestions.append($item);
            });
            
            $('.abt-search-form').append($suggestions);
        },

        // Initialize lazy loading for images
        initLazyLoading: function() {
            const images = document.querySelectorAll('img[data-src]');
            
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                images.forEach(img => imageObserver.observe(img));
            } else {
                // Fallback for older browsers
                images.forEach(img => {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                });
            }
        },

        // Table of Contents Generator
        generateTableOfContents: function() {
            const $content = $('.abt-article-content');
            const $headings = $content.find('h2, h3, h4, h5, h6');
            
            if ($headings.length < 3) return; // Only generate TOC if there are enough headings
            
            const $toc = $('<div class="abt-table-of-contents"><h3>Table of Contents</h3><ul class="abt-toc-list"></ul></div>');
            const $tocList = $toc.find('.abt-toc-list');
            
            $headings.each(function(index) {
                const $heading = $(this);
                const id = 'heading-' + index;
                const level = parseInt(this.tagName.charAt(1));
                const text = $heading.text();
                
                // Add ID to heading for linking
                $heading.attr('id', id);
                
                // Create TOC item
                const $tocItem = $(`
                    <li class="abt-toc-item abt-toc-level-${level}">
                        <a href="#${id}" class="abt-toc-link">${text}</a>
                    </li>
                `);
                
                $tocList.append($tocItem);
            });
            
            // Insert TOC after the first paragraph or at the beginning
            const $firstParagraph = $content.find('p').first();
            if ($firstParagraph.length) {
                $firstParagraph.after($toc);
            } else {
                $content.prepend($toc);
            }
            
            // Add smooth scrolling to TOC links
            $toc.find('.abt-toc-link').on('click', function(e) {
                e.preventDefault();
                const target = $(this.getAttribute('href'));
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 500);
            });
        }
    };

    // Utility Functions
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Initialize when document is ready
    $(document).ready(function() {
        ABT_Frontend.init();
        ABT_Frontend.initSearchEnhancement();
        ABT_Frontend.initLazyLoading();
        ABT_Frontend.generateTableOfContents();
    });

    // Additional CSS for notifications and enhancements
    const additionalCSS = `
        <style>
        .abt-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 16px;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
        }
        
        .abt-notification-success {
            background: #059669;
            color: white;
        }
        
        .abt-notification-error {
            background: #dc2626;
            color: white;
        }
        
        .abt-notification-info {
            background: #1e40af;
            color: white;
        }
        
        .abt-notification-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 18px;
            padding: 0;
        }
        
        .abt-search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 4px 4px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .abt-suggestion-item {
            padding: 8px 12px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .abt-suggestion-item:hover {
            background: #f9fafb;
        }
        
        .abt-suggestion-item a {
            color: #1f2937;
            text-decoration: none;
            flex: 1;
        }
        
        .abt-suggestion-type {
            font-size: 12px;
            color: #6b7280;
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 2px;
        }
        
        .abt-table-of-contents {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 16px;
            margin: 24px 0;
            float: right;
            width: 250px;
            margin-left: 24px;
        }
        
        .abt-table-of-contents h3 {
            margin: 0 0 12px 0;
            font-size: 16px;
            color: #1f2937;
        }
        
        .abt-toc-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .abt-toc-item {
            margin: 4px 0;
        }
        
        .abt-toc-level-3 { padding-left: 12px; }
        .abt-toc-level-4 { padding-left: 24px; }
        .abt-toc-level-5 { padding-left: 36px; }
        .abt-toc-level-6 { padding-left: 48px; }
        
        .abt-toc-link {
            color: #1e40af;
            text-decoration: none;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .abt-toc-link:hover {
            text-decoration: underline;
        }
        
        .highlighted {
            animation: highlight 2s ease-out;
        }
        
        @keyframes highlight {
            0% { background-color: #fef3c7; }
            100% { background-color: transparent; }
        }
        
        .screen-reader-text {
            position: absolute !important;
            clip: rect(1px, 1px, 1px, 1px);
            padding: 0 !important;
            border: 0 !important;
            height: 1px !important;
            width: 1px !important;
            overflow: hidden;
        }
        
        #abt-skip-link:focus {
            position: fixed;
            top: 10px;
            left: 10px;
            background: #000;
            color: #fff;
            padding: 8px 16px;
            text-decoration: none;
            z-index: 10001;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            .abt-table-of-contents {
                float: none;
                width: 100%;
                margin-left: 0;
            }
            
            .abt-notification {
                left: 10px;
                right: 10px;
                top: 10px;
            }
        }
        </style>
    `;
    
    $('head').append(additionalCSS);

})(jQuery);