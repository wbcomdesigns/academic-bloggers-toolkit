/**
 * Search Widget for Academic Blogger's Toolkit
 * 
 * Enhanced search functionality with autocomplete, filters, and live results
 * 
 * @package Academic_Bloggers_Toolkit
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Search Widget Object
    const ABT_SearchWidget = {
        
        // Configuration
        config: {
            minSearchLength: 2,
            debounceDelay: 300,
            maxSuggestions: 8,
            maxResults: 20,
            suggestionTypes: ['posts', 'authors', 'subjects', 'keywords'],
            cacheTimeout: 300000 // 5 minutes
        },

        // State
        cache: new Map(),
        currentRequest: null,
        searchHistory: [],
        activeFilters: {},

        /**
         * Initialize search widget
         */
        init: function() {
            this.bindEvents();
            this.setupAutoComplete();
            this.initializeFilters();
            this.loadSearchHistory();
            this.setupKeyboardNavigation();
        },

        /**
         * Bind search events
         */
        bindEvents: function() {
            const self = this;

            // Search input events
            $(document).on('input', '.abt-search-field', function() {
                const $input = $(this);
                const query = $input.val().trim();
                self.handleSearchInput(query, $input);
            });

            // Search form submission
            $(document).on('submit', '.abt-search-form', function(e) {
                e.preventDefault();
                const $form = $(this);
                const query = $form.find('.abt-search-field').val().trim();
                self.performSearch(query, $form);
            });

            // Filter changes
            $(document).on('change', '.abt-search-filter', function() {
                const $filter = $(this);
                self.updateFilter($filter);
            });

            // Clear search
            $(document).on('click', '.abt-clear-search', function(e) {
                e.preventDefault();
                self.clearSearch();
            });

            // Suggestion clicks
            $(document).on('click', '.abt-search-suggestion', function(e) {
                e.preventDefault();
                const $suggestion = $(this);
                self.selectSuggestion($suggestion);
            });

            // Advanced search toggle
            $(document).on('click', '.abt-toggle-advanced', function(e) {
                e.preventDefault();
                self.toggleAdvancedSearch();
            });

            // Search history
            $(document).on('click', '.abt-search-history-item', function(e) {
                e.preventDefault();
                const query = $(this).data('query');
                self.loadSearchQuery(query);
            });

            // Click outside to hide suggestions
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.abt-search-container').length) {
                    self.hideSuggestions();
                }
            });
        },

        /**
         * Handle search input with debouncing
         */
        handleSearchInput: function(query, $input) {
            const self = this;
            
            // Clear previous timeout
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }

            // Hide suggestions if query too short
            if (query.length < this.config.minSearchLength) {
                this.hideSuggestions();
                return;
            }

            // Debounce search
            this.searchTimeout = setTimeout(() => {
                self.getSuggestions(query, $input);
            }, this.config.debounceDelay);
        },

        /**
         * Get search suggestions
         */
        getSuggestions: function(query, $input) {
            const self = this;
            const cacheKey = 'suggestions:' + query + ':' + JSON.stringify(this.activeFilters);

            // Check cache first
            if (this.cache.has(cacheKey)) {
                const cached = this.cache.get(cacheKey);
                if (Date.now() - cached.timestamp < this.config.cacheTimeout) {
                    this.displaySuggestions(cached.data, $input);
                    return;
                }
            }

            // Cancel previous request
            if (this.currentRequest) {
                this.currentRequest.abort();
            }

            // Show loading
            this.showSuggestionsLoading($input);

            // Make AJAX request
            this.currentRequest = $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_search_suggestions',
                    query: query,
                    filters: this.activeFilters,
                    types: this.config.suggestionTypes,
                    max_results: this.config.maxSuggestions,
                    nonce: abt_frontend_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Cache results
                        self.cache.set(cacheKey, {
                            data: response.data,
                            timestamp: Date.now()
                        });

                        self.displaySuggestions(response.data, $input);
                    } else {
                        self.hideSuggestions();
                    }
                },
                error: function(xhr) {
                    if (xhr.statusText !== 'abort') {
                        self.hideSuggestions();
                    }
                },
                complete: function() {
                    self.currentRequest = null;
                }
            });
        },

        /**
         * Display search suggestions
         */
        displaySuggestions: function(suggestions, $input) {
            const $container = $input.closest('.abt-search-container');
            let $suggestionsBox = $container.find('.abt-suggestions-box');

            // Create suggestions box if not exists
            if (!$suggestionsBox.length) {
                $suggestionsBox = $('<div class="abt-suggestions-box" role="listbox"></div>');
                $container.append($suggestionsBox);
            }

            if (!suggestions.length) {
                $suggestionsBox.html('<div class="abt-no-suggestions">No suggestions found</div>').show();
                return;
            }

            // Build suggestions HTML
            let html = '';
            suggestions.forEach((suggestion, index) => {
                html += `
                    <div class="abt-search-suggestion" role="option" data-value="${suggestion.value}" data-type="${suggestion.type}" data-index="${index}">
                        <div class="abt-suggestion-content">
                            <span class="abt-suggestion-text">${suggestion.text}</span>
                            <span class="abt-suggestion-type">${suggestion.type}</span>
                        </div>
                        ${suggestion.description ? `<div class="abt-suggestion-description">${suggestion.description}</div>` : ''}
                    </div>
                `;
            });

            $suggestionsBox.html(html).show();

            // Add search history if available
            this.addHistoryToSuggestions($suggestionsBox, $input.val());
        },

        /**
         * Add search history to suggestions
         */
        addHistoryToSuggestions: function($suggestionsBox, currentQuery) {
            const relevantHistory = this.searchHistory.filter(item => 
                item.toLowerCase().includes(currentQuery.toLowerCase()) && 
                item !== currentQuery
            ).slice(0, 3);

            if (relevantHistory.length) {
                const historyHtml = relevantHistory.map(query => `
                    <div class="abt-search-suggestion abt-history-suggestion" data-value="${query}" data-type="history">
                        <div class="abt-suggestion-content">
                            <span class="abt-suggestion-text">${query}</span>
                            <span class="abt-suggestion-type">recent</span>
                        </div>
                    </div>
                `).join('');

                $suggestionsBox.append('<div class="abt-suggestions-divider">Recent Searches</div>' + historyHtml);
            }
        },

        /**
         * Hide suggestions
         */
        hideSuggestions: function() {
            $('.abt-suggestions-box').hide();
        },

        /**
         * Show suggestions loading
         */
        showSuggestionsLoading: function($input) {
            const $container = $input.closest('.abt-search-container');
            let $suggestionsBox = $container.find('.abt-suggestions-box');

            if (!$suggestionsBox.length) {
                $suggestionsBox = $('<div class="abt-suggestions-box"></div>');
                $container.append($suggestionsBox);
            }

            $suggestionsBox.html('<div class="abt-suggestions-loading">Searching...</div>').show();
        },

        /**
         * Select suggestion
         */
        selectSuggestion: function($suggestion) {
            const value = $suggestion.data('value');
            const type = $suggestion.data('type');
            const $input = $suggestion.closest('.abt-search-container').find('.abt-search-field');

            $input.val(value);
            this.hideSuggestions();

            // Handle different suggestion types
            if (type === 'author') {
                this.searchByAuthor(value);
            } else if (type === 'subject') {
                this.searchBySubject(value);
            } else {
                this.performSearch(value);
            }

            // Add to search history
            this.addToSearchHistory(value);
        },

        /**
         * Perform search
         */
        performSearch: function(query, $form) {
            if (!query) return;

            const self = this;
            
            // Add to history
            this.addToSearchHistory(query);
            
            // Show loading state
            this.showSearchLoading();

            // Build search data
            const searchData = {
                action: 'abt_frontend_search',
                query: query,
                filters: this.activeFilters,
                max_results: this.config.maxResults,
                nonce: abt_frontend_ajax.nonce
            };

            // Perform AJAX search
            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: searchData,
                success: function(response) {
                    if (response.success) {
                        self.displaySearchResults(response.data, query);
                        self.trackSearch(query, response.data.total_found);
                    } else {
                        self.displaySearchError('Search failed. Please try again.');
                    }
                },
                error: function() {
                    self.displaySearchError('Search error. Please check your connection.');
                },
                complete: function() {
                    self.hideSearchLoading();
                }
            });
        },

        /**
         * Display search results
         */
        displaySearchResults: function(results, query) {
            const $resultsContainer = $('#abt-search-results');
            
            if (!$resultsContainer.length) return;

            let html = `<div class="abt-search-results-header">
                <h3>Search Results for "${query}"</h3>
                <span class="abt-results-count">${results.total_found} results found</span>
            </div>`;

            if (results.posts && results.posts.length) {
                html += '<div class="abt-search-results-list">';
                
                results.posts.forEach(post => {
                    html += `
                        <article class="abt-search-result-item">
                            <h4><a href="${post.permalink}">${post.title}</a></h4>
                            <div class="abt-result-meta">
                                <span class="abt-result-author">By ${post.author}</span>
                                <span class="abt-result-date">${post.date}</span>
                                ${post.subjects ? `<span class="abt-result-subjects">${post.subjects}</span>` : ''}
                            </div>
                            ${post.excerpt ? `<div class="abt-result-excerpt">${post.excerpt}</div>` : ''}
                        </article>
                    `;
                });
                
                html += '</div>';
            } else {
                html += '<div class="abt-no-results">No results found for your search.</div>';
            }

            // Add pagination if needed
            if (results.pagination) {
                html += this.buildPagination(results.pagination);
            }

            $resultsContainer.html(html).show();

            // Scroll to results
            $('html, body').animate({
                scrollTop: $resultsContainer.offset().top - 100
            }, 500);
        },

        /**
         * Display search error
         */
        displaySearchError: function(message) {
            const $resultsContainer = $('#abt-search-results');
            $resultsContainer.html(`<div class="abt-search-error">${message}</div>`).show();
        },

        /**
         * Show/hide search loading
         */
        showSearchLoading: function() {
            $('.abt-search-loading').show();
            $('.abt-search-submit').prop('disabled', true).text('Searching...');
        },

        hideSearchLoading: function() {
            $('.abt-search-loading').hide();
            $('.abt-search-submit').prop('disabled', false).text('Search');
        },

        /**
         * Update search filter
         */
        updateFilter: function($filter) {
            const filterName = $filter.attr('name');
            const filterValue = $filter.val();

            if (filterValue) {
                this.activeFilters[filterName] = filterValue;
            } else {
                delete this.activeFilters[filterName];
            }

            // Update filter display
            this.updateActiveFiltersDisplay();

            // Clear cache when filters change
            this.clearCache();

            // Re-run current search if there's a query
            const currentQuery = $('.abt-search-field').val().trim();
            if (currentQuery.length >= this.config.minSearchLength) {
                this.performSearch(currentQuery);
            }
        },

        /**
         * Update active filters display
         */
        updateActiveFiltersDisplay: function() {
            const $activeFilters = $('.abt-active-filters');
            
            if (Object.keys(this.activeFilters).length === 0) {
                $activeFilters.hide();
                return;
            }

            let html = '<div class="abt-active-filters-title">Active Filters:</div>';
            
            Object.entries(this.activeFilters).forEach(([key, value]) => {
                html += `<span class="abt-filter-tag" data-filter="${key}">
                    ${key}: ${value}
                    <button class="abt-remove-filter" data-filter="${key}">&times;</button>
                </span>`;
            });

            $activeFilters.html(html).show();

            // Bind remove filter events
            $activeFilters.find('.abt-remove-filter').on('click', function() {
                const filterKey = $(this).data('filter');
                delete ABT_SearchWidget.activeFilters[filterKey];
                $(`[name="${filterKey}"]`).val('');
                ABT_SearchWidget.updateActiveFiltersDisplay();
                ABT_SearchWidget.clearCache();
            });
        },

        /**
         * Clear search
         */
        clearSearch: function() {
            $('.abt-search-field').val('');
            $('#abt-search-results').hide();
            this.hideSuggestions();
            this.activeFilters = {};
            this.updateActiveFiltersDisplay();
            $('.abt-search-filter').val('');
        },

        /**
         * Toggle advanced search
         */
        toggleAdvancedSearch: function() {
            const $advanced = $('.abt-advanced-search');
            const $toggle = $('.abt-toggle-advanced');
            
            if ($advanced.is(':visible')) {
                $advanced.slideUp();
                $toggle.text('Show Advanced Search');
            } else {
                $advanced.slideDown();
                $toggle.text('Hide Advanced Search');
            }
        },

        /**
         * Search by author
         */
        searchByAuthor: function(author) {
            this.activeFilters.author = author;
            $('[name="author"]').val(author);
            this.updateActiveFiltersDisplay();
            this.performSearch('', null);
        },

        /**
         * Search by subject
         */
        searchBySubject: function(subject) {
            this.activeFilters.subject = subject;
            $('[name="subject"]').val(subject);
            this.updateActiveFiltersDisplay();
            this.performSearch('', null);
        },

        /**
         * Setup keyboard navigation
         */
        setupKeyboardNavigation: function() {
            const self = this;

            // Arrow key navigation in suggestions
            $(document).on('keydown', '.abt-search-field', function(e) {
                const $suggestions = $('.abt-search-suggestion:visible');
                const $current = $suggestions.filter('.abt-suggestion-selected');
                let $target;

                switch(e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        if ($current.length === 0) {
                            $target = $suggestions.first();
                        } else {
                            $target = $current.next('.abt-search-suggestion');
                            if ($target.length === 0) {
                                $target = $suggestions.first();
                            }
                        }
                        break;

                    case 'ArrowUp':
                        e.preventDefault();
                        if ($current.length === 0) {
                            $target = $suggestions.last();
                        } else {
                            $target = $current.prev('.abt-search-suggestion');
                            if ($target.length === 0) {
                                $target = $suggestions.last();
                            }
                        }
                        break;

                    case 'Enter':
                        if ($current.length) {
                            e.preventDefault();
                            self.selectSuggestion($current);
                        }
                        break;

                    case 'Escape':
                        self.hideSuggestions();
                        break;
                }

                if ($target) {
                    $suggestions.removeClass('abt-suggestion-selected');
                    $target.addClass('abt-suggestion-selected');
                }
            });
        },

        /**
         * Search history management
         */
        addToSearchHistory: function(query) {
            if (!query || this.searchHistory.includes(query)) return;

            this.searchHistory.unshift(query);
            this.searchHistory = this.searchHistory.slice(0, 10); // Keep last 10
            this.saveSearchHistory();
        },

        loadSearchHistory: function() {
            const saved = localStorage.getItem('abt_search_history');
            if (saved) {
                try {
                    this.searchHistory = JSON.parse(saved);
                } catch (e) {
                    this.searchHistory = [];
                }
            }
        },

        saveSearchHistory: function() {
            localStorage.setItem('abt_search_history', JSON.stringify(this.searchHistory));
        },

        /**
         * Load search query
         */
        loadSearchQuery: function(query) {
            $('.abt-search-field').val(query);
            this.performSearch(query);
        },

        /**
         * Track search
         */
        trackSearch: function(query, resultsCount) {
            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_track_search',
                    query: query,
                    results_count: resultsCount,
                    filters: this.activeFilters,
                    nonce: abt_frontend_ajax.nonce
                }
            });
        },

        /**
         * Build pagination
         */
        buildPagination: function(pagination) {
            if (!pagination.total_pages || pagination.total_pages <= 1) return '';

            let html = '<div class="abt-search-pagination">';
            
            // Previous button
            if (pagination.current_page > 1) {
                html += `<a href="#" class="abt-page-link" data-page="${pagination.current_page - 1}">« Previous</a>`;
            }

            // Page numbers
            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.current_page) {
                    html += `<span class="abt-page-current">${i}</span>`;
                } else {
                    html += `<a href="#" class="abt-page-link" data-page="${i}">${i}</a>`;
                }
            }

            // Next button
            if (pagination.current_page < pagination.total_pages) {
                html += `<a href="#" class="abt-page-link" data-page="${pagination.current_page + 1}">Next »</a>`;
            }

            html += '</div>';
            return html;
        },

        /**
         * Setup autocomplete
         */
        setupAutoComplete: function() {
            // Already handled in main search functionality
        },

        /**
         * Initialize filters
         */
        initializeFilters: function() {
            // Load any saved filter state
            const savedFilters = sessionStorage.getItem('abt_search_filters');
            if (savedFilters) {
                try {
                    this.activeFilters = JSON.parse(savedFilters);
                    // Apply saved filters to form elements
                    Object.entries(this.activeFilters).forEach(([key, value]) => {
                        $(`[name="${key}"]`).val(value);
                    });
                    this.updateActiveFiltersDisplay();
                } catch (e) {
                    this.activeFilters = {};
                }
            }
        },

        /**
         * Clear cache
         */
        clearCache: function() {
            this.cache.clear();
        },

        /**
         * Get cache statistics
         */
        getCacheStats: function() {
            return {
                size: this.cache.size,
                keys: Array.from(this.cache.keys())
            };
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ABT_SearchWidget.init();
    });

    // Export to global scope
    window.ABT_SearchWidget = ABT_SearchWidget;

})(jQuery);