/**
 * Enhanced Frontend JavaScript for Academic Blogger's Toolkit
 * 
 * Provides advanced frontend interactions:
 * - Real-time search with AJAX
 * - Advanced filtering and sorting
 * - Citation tooltips and interactions
 * - Reading progress tracking
 * - Smooth scrolling and navigation
 * 
 * @package Academic_Bloggers_Toolkit
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Main ABT Frontend object
     */
    var ABT_Frontend = {
        
        /**
         * Configuration
         */
        config: {
            searchDebounceTime: 300,
            animationSpeed: 300,
            scrollOffset: 100,
            readingProgressUpdate: 250
        },

        /**
         * Initialize all frontend functionality
         */
        init: function() {
            this.bindEvents();
            this.initSearch();
            this.initFiltering();
            this.initCitationTooltips();
            this.initReadingProgress();
            this.initSmoothScrolling();
            this.initViewToggle();
            this.initInfiniteScroll();
            this.initAccessibility();
            this.loadUserPreferences();
        },

        /**
         * Bind general events
         */
        bindEvents: function() {
            $(document).ready(this.onDocumentReady.bind(this));
            $(window).on('scroll', this.throttle(this.onScroll.bind(this), 100));
            $(window).on('resize', this.throttle(this.onResize.bind(this), 200));
            $(window).on('beforeunload', this.onBeforeUnload.bind(this));
        },

        /**
         * Document ready handler
         */
        onDocumentReady: function() {
            this.updateReadingProgress();
            this.initTableOfContents();
            this.setupKeyboardNavigation();
            this.trackPageView();
        },

        /**
         * Initialize search functionality
         */
        initSearch: function() {
            var self = this;

            // Real-time search
            $('#abt-search-input').on('input', this.debounce(function() {
                var searchTerm = $(this).val();
                if (searchTerm.length >= 2) {
                    self.performLiveSearch(searchTerm);
                } else if (searchTerm.length === 0) {
                    self.clearSearchResults();
                }
            }, this.config.searchDebounceTime));

            // Search form submission
            $('#abt-search-form').on('submit', function(e) {
                e.preventDefault();
                var searchTerm = $('#abt-search-input').val();
                if (searchTerm.trim()) {
                    self.performFullSearch(searchTerm);
                }
            });

            // Search suggestions
            $('#abt-search-input').on('focus', function() {
                self.showSearchSuggestions();
            });

            // Click outside to hide suggestions
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#abt-search-container').length) {
                    self.hideSearchSuggestions();
                }
            });

            // Search suggestion click
            $(document).on('click', '.abt-search-suggestion', function(e) {
                e.preventDefault();
                var suggestion = $(this).text();
                $('#abt-search-input').val(suggestion);
                self.performFullSearch(suggestion);
                self.hideSearchSuggestions();
            });

            // Clear search button
            $('#abt-clear-search').on('click', function(e) {
                e.preventDefault();
                self.clearSearch();
            });
        },

        /**
         * Perform live search with AJAX
         */
        performLiveSearch: function(searchTerm) {
            var self = this;

            // Show loading indicator
            this.showSearchLoading();

            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_frontend_search',
                    search_term: searchTerm,
                    post_type: this.getCurrentPostType(),
                    per_page: 5
                },
                success: function(response) {
                    if (response.success) {
                        self.displayLiveSearchResults(response.data.results);
                        self.trackSearch(searchTerm, response.data.total_found);
                    } else {
                        self.showSearchError('Search failed. Please try again.');
                    }
                },
                error: function() {
                    self.showSearchError('Search error. Please check your connection.');
                },
                complete: function() {
                    self.hideSearchLoading();
                }
            });
        },

        /**
         * Initialize filtering system
         */
        initFiltering: function() {
            var self = this;

            // Subject filter
            $('#abt-subject-filter').on('change', function() {
                var selectedSubject = $(this).val();
                self.applyFilter('subject', selectedSubject);
            });

            // Category filter
            $('#abt-category-filter').on('change', function() {
                var selectedCategory = $(this).val();
                self.applyFilter('category', selectedCategory);
            });

            // Date range filter
            $('#abt-date-from, #abt-date-to').on('change', function() {
                var dateFrom = $('#abt-date-from').val();
                var dateTo = $('#abt-date-to').val();
                self.applyDateFilter(dateFrom, dateTo);
            });

            // Sort order
            $('#abt-sort-select').on('change', function() {
                var sortBy = $(this).val();
                self.applySorting(sortBy);
            });

            // Quick filter buttons
            $('.abt-quick-filter-btn').on('click', function(e) {
                e.preventDefault();
                var filterType = $(this).data('filter-type');
                var filterValue = $(this).data('filter-value');
                self.applyQuickFilter(filterType, filterValue);
            });

            // Clear filters
            $('#abt-clear-filters').on('click', function(e) {
                e.preventDefault();
                self.clearAllFilters();
            });
        },

        /**
         * Apply filter with AJAX
         */
        applyFilter: function(filterType, filterValue) {
            var self = this;
            
            // Show loading state
            this.showFilterLoading();

            var currentFilters = this.getCurrentFilters();
            currentFilters[filterType] = filterValue;

            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_frontend_filter',
                    filters: currentFilters,
                    post_type: this.getCurrentPostType()
                },
                success: function(response) {
                    if (response.success) {
                        self.updateFilteredResults(response.data);
                        self.updateFilterCounts(response.data.facets);
                        self.updateUrl(currentFilters);
                    }
                },
                error: function() {
                    self.showFilterError('Filter error. Please try again.');
                },
                complete: function() {
                    self.hideFilterLoading();
                }
            });
        },

        /**
         * Initialize citation tooltips
         */
        initCitationTooltips: function() {
            var self = this;

            // Citation link hover
            $(document).on('mouseenter', '.abt-citation-link', function() {
                var $citation = $(this);
                var citationId = $citation.data('citation-id');
                
                if (citationId && !$citation.data('tooltip-loaded')) {
                    self.loadCitationTooltip($citation, citationId);
                }
            });

            // Citation link click for mobile
            $(document).on('click', '.abt-citation-link', function(e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    var citationId = $(this).data('citation-id');
                    self.showCitationModal(citationId);
                }
            });

            // Footnote links
            $(document).on('click', '.abt-footnote-link', function(e) {
                e.preventDefault();
                var footnoteId = $(this).attr('href');
                self.scrollToFootnote(footnoteId);
            });

            // Back to text links
            $(document).on('click', '.abt-footnote-backlink', function(e) {
                e.preventDefault();
                var backId = $(this).attr('href');
                self.scrollToElement(backId);
            });
        },

        /**
         * Load citation tooltip content
         */
        loadCitationTooltip: function($citation, citationId) {
            var self = this;

            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_get_citation_tooltip',
                    citation_id: citationId
                },
                success: function(response) {
                    if (response.success) {
                        self.showCitationTooltip($citation, response.data.content);
                        $citation.data('tooltip-loaded', true);
                    }
                }
            });
        },

        /**
         * Show citation tooltip
         */
        showCitationTooltip: function($citation, content) {
            var $tooltip = $('<div class="abt-citation-tooltip">' + content + '</div>');
            $('body').append($tooltip);

            var offset = $citation.offset();
            var tooltipWidth = $tooltip.outerWidth();
            var tooltipHeight = $tooltip.outerHeight();
            var windowWidth = $(window).width();
            var windowHeight = $(window).height();
            var scrollTop = $(window).scrollTop();

            // Calculate position
            var left = offset.left + ($citation.outerWidth() / 2) - (tooltipWidth / 2);
            var top = offset.top - tooltipHeight - 10;

            // Adjust if tooltip goes off screen
            if (left < 10) left = 10;
            if (left + tooltipWidth > windowWidth - 10) left = windowWidth - tooltipWidth - 10;
            if (top < scrollTop + 10) top = offset.top + $citation.outerHeight() + 10;

            $tooltip.css({
                left: left,
                top: top,
                opacity: 0
            }).animate({
                opacity: 1
            }, 200);

            // Store tooltip reference
            $citation.data('tooltip-element', $tooltip);

            // Hide on mouse leave
            $citation.on('mouseleave.tooltip', function() {
                $tooltip.animate({
                    opacity: 0
                }, 200, function() {
                    $tooltip.remove();
                });
                $citation.off('mouseleave.tooltip');
                $citation.removeData('tooltip-element');
            });
        },

        /**
         * Initialize reading progress
         */
        initReadingProgress: function() {
            var self = this;
            
            if ($('.abt-reading-progress').length) {
                $(window).on('scroll', this.throttle(function() {
                    self.updateReadingProgress();
                }, this.config.readingProgressUpdate));

                // Initialize progress bar
                this.updateReadingProgress();
            }
        },

        /**
         * Update reading progress
         */
        updateReadingProgress: function() {
            var $progressBar = $('.abt-reading-progress-bar');
            var $article = $('.abt-blog-content, .entry-content').first();
            
            if (!$progressBar.length || !$article.length) return;

            var articleTop = $article.offset().top;
            var articleHeight = $article.outerHeight();
            var windowTop = $(window).scrollTop();
            var windowHeight = $(window).height();

            var progress = Math.max(0, Math.min(100, 
                ((windowTop + windowHeight - articleTop) / articleHeight) * 100
            ));

            $progressBar.css('width', progress + '%');

            // Update reading time estimate
            this.updateReadingTimeEstimate();
        },

        /**
         * Initialize view toggle
         */
        initViewToggle: function() {
            var self = this;

            $('.abt-view-toggle').on('click', function(e) {
                e.preventDefault();
                var viewType = $(this).data('view');
                self.switchView(viewType);
            });
        },

        /**
         * Switch between list and grid view
         */
        switchView: function(viewType) {
            var $container = $('.abt-blog-list');
            var $toggles = $('.abt-view-toggle');

            // Update active toggle
            $toggles.removeClass('active');
            $('.abt-view-toggle[data-view="' + viewType + '"]').addClass('active');

            // Switch view classes
            $container.removeClass('abt-list-view abt-grid-view')
                      .addClass('abt-' + viewType + '-view');

            // Save preference
            this.saveUserPreference('view_type', viewType);

            // Animate transition
            $container.css('opacity', 0.7);
            setTimeout(function() {
                $container.animate({opacity: 1}, 200);
            }, 100);
        },

        /**
         * Initialize infinite scroll
         */
        initInfiniteScroll: function() {
            var self = this;
            var $loadMoreBtn = $('#abt-load-more');
            var currentPage = 1;
            var loading = false;

            // Load more button click
            $loadMoreBtn.on('click', function(e) {
                e.preventDefault();
                if (!loading) {
                    self.loadMorePosts(++currentPage);
                }
            });

            // Auto-load on scroll (optional)
            if ($('.abt-blog-list').data('infinite-scroll') === true) {
                $(window).on('scroll', this.throttle(function() {
                    if (!loading && self.isNearBottom()) {
                        self.loadMorePosts(++currentPage);
                    }
                }, 500));
            }
        },

        /**
         * Load more posts via AJAX
         */
        loadMorePosts: function(page) {
            var self = this;
            var $loadMoreBtn = $('#abt-load-more');
            var $container = $('.abt-blog-list');
            
            // Set loading state
            loading = true;
            $loadMoreBtn.text('Loading...').prop('disabled', true);

            var currentFilters = this.getCurrentFilters();
            
            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_load_more_posts',
                    page: page,
                    filters: currentFilters,
                    post_type: this.getCurrentPostType()
                },
                success: function(response) {
                    if (response.success && response.data.posts.length > 0) {
                        var $newPosts = $(response.data.html);
                        $newPosts.css('opacity', 0);
                        $container.append($newPosts);
                        
                        // Animate new posts
                        $newPosts.animate({opacity: 1}, 300);
                        
                        // Update load more button
                        if (response.data.has_more) {
                            $loadMoreBtn.text('Load More').prop('disabled', false);
                        } else {
                            $loadMoreBtn.text('No More Posts').prop('disabled', true);
                        }
                    } else {
                        $loadMoreBtn.text('No More Posts').prop('disabled', true);
                    }
                },
                error: function() {
                    $loadMoreBtn.text('Error - Try Again').prop('disabled', false);
                },
                complete: function() {
                    loading = false;
                }
            });
        },

        /**
         * Initialize accessibility features
         */
        initAccessibility: function() {
            var self = this;

            // Skip links
            $('.abt-skip-link').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                $(target).focus();
                self.scrollToElement(target);
            });

            // Keyboard navigation for filters
            $('.abt-filter-select').on('keydown', function(e) {
                if (e.which === 13) { // Enter key
                    $(this).trigger('change');
                }
            });

            // ARIA live regions for dynamic content
            this.setupAriaLiveRegions();

            // Focus management for modals
            $(document).on('keydown', '.abt-modal', function(e) {
                if (e.which === 27) { // Escape key
                    self.closeModal();
                }
            });
        },

        /**
         * Setup ARIA live regions
         */
        setupAriaLiveRegions: function() {
            if (!$('#abt-live-region').length) {
                $('body').append('<div id="abt-live-region" aria-live="polite" aria-atomic="true" class="screen-reader-text"></div>');
            }
        },

        /**
         * Announce to screen readers
         */
        announceToScreenReader: function(message) {
            $('#abt-live-region').text(message);
        },

        /**
         * Initialize table of contents
         */
        initTableOfContents: function() {
            var $article = $('.abt-blog-content, .entry-content').first();
            var $tocContainer = $('#abt-table-of-contents');

            if (!$article.length || !$tocContainer.length) return;

            var headings = $article.find('h2, h3, h4, h5, h6');
            
            if (headings.length < 3) {
                $tocContainer.hide();
                return;
            }

            var tocHtml = '<ul class="abt-toc-list">';
            var currentLevel = 2;

            headings.each(function(index) {
                var $heading = $(this);
                var level = parseInt($heading.prop('tagName').substring(1));
                var id = $heading.attr('id') || 'heading-' + index;
                var text = $heading.text();

                // Ensure heading has an ID
                if (!$heading.attr('id')) {
                    $heading.attr('id', id);
                }

                // Adjust nesting based on heading level
                if (level > currentLevel) {
                    tocHtml += '<ul>';
                } else if (level < currentLevel) {
                    tocHtml += '</ul>';
                }

                tocHtml += '<li><a href="#' + id + '" class="abt-toc-link">' + text + '</a></li>';
                currentLevel = level;
            });

            tocHtml += '</ul>';
            $tocContainer.find('.abt-toc-content').html(tocHtml);

            // Bind TOC link clicks
            $('.abt-toc-link').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                this.scrollToElement(target, true);
            }.bind(this));

            // Highlight current section
            this.updateTocHighlight();
            $(window).on('scroll', this.throttle(this.updateTocHighlight.bind(this), 200));
        },

        /**
         * Update table of contents highlighting
         */
        updateTocHighlight: function() {
            var scrollTop = $(window).scrollTop();
            var $currentHeading = null;

            $('.abt-blog-content h2, .abt-blog-content h3, .abt-blog-content h4, .abt-blog-content h5, .abt-blog-content h6').each(function() {
                var $heading = $(this);
                if ($heading.offset().top <= scrollTop + 100) {
                    $currentHeading = $heading;
                }
            });

            $('.abt-toc-link').removeClass('current');
            if ($currentHeading) {
                var currentId = $currentHeading.attr('id');
                $('.abt-toc-link[href="#' + currentId + '"]').addClass('current');
            }
        },

        /**
         * Initialize smooth scrolling
         */
        initSmoothScrolling: function() {
            var self = this;

            // Smooth scroll for anchor links
            $(document).on('click', 'a[href^="#"]', function(e) {
                var target = $(this).attr('href');
                if (target && target !== '#' && $(target).length) {
                    e.preventDefault();
                    self.scrollToElement(target);
                }
            });

            // Back to top button
            var $backToTop = $('#abt-back-to-top');
            
            if ($backToTop.length) {
                $(window).on('scroll', this.throttle(function() {
                    if ($(window).scrollTop() > 500) {
                        $backToTop.fadeIn();
                    } else {
                        $backToTop.fadeOut();
                    }
                }, 100));

                $backToTop.on('click', function(e) {
                    e.preventDefault();
                    $('html, body').animate({scrollTop: 0}, 500);
                });
            }
        },

        /**
         * Scroll to element with smooth animation
         */
        scrollToElement: function(target, updateHash) {
            var $target = $(target);
            if (!$target.length) return;

            var offset = $target.offset().top - this.config.scrollOffset;
            
            $('html, body').animate({
                scrollTop: offset
            }, 500, function() {
                if (updateHash) {
                    history.pushState(null, null, target);
                }
                
                // Focus the target for accessibility
                $target.focus();
            });
        },

        /**
         * Track search for analytics
         */
        trackSearch: function(searchTerm, resultCount) {
            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_track_search',
                    search_term: searchTerm,
                    result_count: resultCount,
                    search_type: 'frontend'
                }
            });
        },

        /**
         * Track page view
         */
        trackPageView: function() {
            var postId = $('body').data('post-id');
            var postType = $('body').data('post-type');

            if (postId && postType === 'abt_blog') {
                $.ajax({
                    url: abt_frontend_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'abt_track_page_view',
                        post_id: postId
                    }
                });
            }
        },

        /**
         * Utility functions
         */

        /**
         * Get current post type
         */
        getCurrentPostType: function() {
            return $('body').data('post-type') || 'abt_blog';
        },

        /**
         * Get current filters
         */
        getCurrentFilters: function() {
            return {
                subject: $('#abt-subject-filter').val(),
                category: $('#abt-category-filter').val(),
                date_from: $('#abt-date-from').val(),
                date_to: $('#abt-date-to').val(),
                sort: $('#abt-sort-select').val()
            };
        },

        /**
         * Check if user is near bottom of page
         */
        isNearBottom: function() {
            var threshold = 200;
            var scrollTop = $(window).scrollTop();
            var windowHeight = $(window).height();
            var documentHeight = $(document).height();

            return (scrollTop + windowHeight + threshold >= documentHeight);
        },

        /**
         * Update reading time estimate
         */
        updateReadingTimeEstimate: function() {
            var $article = $('.abt-blog-content, .entry-content').first();
            var $timeEstimate = $('.abt-reading-time');

            if (!$article.length || !$timeEstimate.length) return;

            var text = $article.text();
            var wordsPerMinute = 200;
            var wordCount = text.split(/\s+/).length;
            var readingTime = Math.ceil(wordCount / wordsPerMinute);

            $timeEstimate.text(readingTime + ' min read');
        },

        /**
         * Display live search results
         */
        displayLiveSearchResults: function(results) {
            var $container = $('#abt-search-results');
            var html = '';

            if (results.length === 0) {
                html = '<div class="abt-no-results">No results found</div>';
            } else {
                html = '<ul class="abt-search-results-list">';
                results.forEach(function(result) {
                    html += '<li class="abt-search-result-item">';
                    html += '<a href="' + (result.permalink || '#') + '">';
                    html += '<h4>' + result.title + '</h4>';
                    if (result.excerpt) {
                        html += '<p>' + result.excerpt + '</p>';
                    }
                    html += '</a></li>';
                });
                html += '</ul>';
            }

            $container.html(html).show();
            this.announceToScreenReader(results.length + ' search results found');
        },

        /**
         * Clear search results
         */
        clearSearchResults: function() {
            $('#abt-search-results').hide().empty();
        },

        /**
         * Show/hide search loading
         */
        showSearchLoading: function() {
            $('#abt-search-loading').show();
        },

        hideSearchLoading: function() {
            $('#abt-search-loading').hide();
        },

        /**
         * Save user preference
         */
        saveUserPreference: function(key, value) {
            var preferences = JSON.parse(localStorage.getItem('abt_frontend_preferences') || '{}');
            preferences[key] = value;
            localStorage.setItem('abt_frontend_preferences', JSON.stringify(preferences));
        },

        /**
         * Load user preferences
         */
        loadUserPreferences: function() {
            var preferences = JSON.parse(localStorage.getItem('abt_frontend_preferences') || '{}');
            
            // Apply saved view type
            if (preferences.view_type) {
                this.switchView(preferences.view_type);
            }

            // Apply other preferences...
        },

        /**
         * Setup keyboard navigation
         */
        setupKeyboardNavigation: function() {
            var self = this;

            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + K: Focus search
                if ((e.ctrlKey || e.metaKey) && e.which === 75) {
                    e.preventDefault();
                    $('#abt-search-input').focus();
                }

                // Arrow keys for result navigation
                if (e.which === 38 || e.which === 40) { // Up or Down arrow
                    var $results = $('.abt-search-result-item');
                    var $current = $('.abt-search-result-item.highlighted');
                    
                    if ($results.length > 0) {
                        e.preventDefault();
                        
                        if ($current.length === 0) {
                            $results.first().addClass('highlighted');
                        } else {
                            var index = $results.index($current);
                            $current.removeClass('highlighted');
                            
                            if (e.which === 38 && index > 0) { // Up
                                $results.eq(index - 1).addClass('highlighted');
                            } else if (e.which === 40 && index < $results.length - 1) { // Down
                                $results.eq(index + 1).addClass('highlighted');
                            }
                        }
                    }
                }

                // Enter to navigate to highlighted result
                if (e.which === 13) { // Enter
                    var $highlighted = $('.abt-search-result-item.highlighted a');
                    if ($highlighted.length) {
                        window.location.href = $highlighted.attr('href');
                    }
                }
            });
        },

        /**
         * Throttle function
         */
        throttle: function(func, limit) {
            var inThrottle;
            return function() {
                var args = arguments;
                var context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(function() {
                        inThrottle = false;
                    }, limit);
                }
            };
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },

        /**
         * Before unload handler
         */
        onBeforeUnload: function() {
            // Save any pending preferences or state
        },

        /**
         * Window scroll handler
         */
        onScroll: function() {
            // Handled by individual scroll listeners
        },

        /**
         * Window resize handler
         */
        onResize: function() {
            // Recalculate tooltip positions, etc.
            $('.abt-citation-tooltip').remove();
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        ABT_Frontend.init();
    });

    /**
     * Export to global scope for external access
     */
    window.ABT_Frontend = ABT_Frontend;

})(jQuery);