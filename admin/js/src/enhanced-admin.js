/**
 * Enhanced Admin JavaScript for Academic Blogger's Toolkit
 * 
 * Provides advanced AJAX interactions and real-time features:
 * - Auto-cite functionality with live preview
 * - Real-time citation management
 * - Advanced search with autocomplete
 * - Drag-and-drop citation reordering
 * - Live bibliography updates
 * 
 * @package Academic_Bloggers_Toolkit
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Main ABT Admin object
     */
    var ABT_Admin = {
        
        /**
         * Initialize all admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initAutoCite();
            this.initCitationManager();
            this.initSearchFunctionality();
            this.initBulkOperations();
            this.initTooltips();
            this.initDragDrop();
            this.loadSavedPreferences();
        },

        /**
         * Bind general events
         */
        bindEvents: function() {
            $(document).ready(this.onDocumentReady.bind(this));
            $(window).on('beforeunload', this.onBeforeUnload.bind(this));
            
            // Global AJAX error handling
            $(document).ajaxError(this.handleAjaxError.bind(this));
            $(document).ajaxSuccess(this.handleAjaxSuccess.bind(this));
        },

        /**
         * Document ready handler
         */
        onDocumentReady: function() {
            this.showWelcomeMessage();
            this.checkForUpdates();
            this.initKeyboardShortcuts();
        },

        /**
         * Initialize auto-cite functionality
         */
        initAutoCite: function() {
            var self = this;

            // DOI auto-cite
            $('#abt-auto-cite-doi').on('click', function(e) {
                e.preventDefault();
                var doi = $('#abt-doi-input').val().trim();
                if (doi) {
                    self.performAutoCite('doi', doi);
                } else {
                    self.showNotice('Please enter a valid DOI', 'error');
                }
            });

            // PubMed auto-cite
            $('#abt-auto-cite-pmid').on('click', function(e) {
                e.preventDefault();
                var pmid = $('#abt-pmid-input').val().trim();
                if (pmid) {
                    self.performAutoCite('pmid', pmid);
                } else {
                    self.showNotice('Please enter a valid PubMed ID', 'error');
                }
            });

            // ISBN auto-cite
            $('#abt-auto-cite-isbn').on('click', function(e) {
                e.preventDefault();
                var isbn = $('#abt-isbn-input').val().trim();
                if (isbn) {
                    self.performAutoCite('isbn', isbn);
                } else {
                    self.showNotice('Please enter a valid ISBN', 'error');
                }
            });

            // URL auto-cite
            $('#abt-auto-cite-url').on('click', function(e) {
                e.preventDefault();
                var url = $('#abt-url-input').val().trim();
                if (url && self.isValidUrl(url)) {
                    self.performAutoCite('url', url);
                } else {
                    self.showNotice('Please enter a valid URL', 'error');
                }
            });

            // Enter key support for auto-cite inputs
            $('.abt-auto-cite-input').on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    $(this).siblings('.abt-auto-cite-btn').click();
                }
            });
        },

        /**
         * Perform auto-cite operation
         */
        performAutoCite: function(type, identifier) {
            var self = this;
            var $button = $('#abt-auto-cite-' + type);
            var originalText = $button.text();

            // Show loading state
            $button.text('Fetching...').prop('disabled', true);
            this.showLoadingOverlay('Fetching reference data...');

            var data = {
                action: 'abt_auto_cite_' + type,
                nonce: abt_admin_ajax.auto_cite_nonce
            };
            data[type] = identifier;

            $.ajax({
                url: abt_admin_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        self.showNotice(response.data.message, 'success');
                        self.addReferenceToList(response.data.reference_data, response.data.reference_id);
                        self.clearAutoCiteInputs();
                        self.refreshReferenceCount();
                    } else {
                        self.showNotice(response.data || 'Failed to fetch reference data', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    self.showNotice('Error: ' + error, 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                    self.hideLoadingOverlay();
                }
            });
        },

        /**
         * Initialize citation manager
         */
        initCitationManager: function() {
            var self = this;

            // Add citation button
            $(document).on('click', '.abt-add-citation-btn', function(e) {
                e.preventDefault();
                var referenceId = $(this).data('reference-id');
                self.showCitationDialog(referenceId);
            });

            // Remove citation button
            $(document).on('click', '.abt-remove-citation-btn', function(e) {
                e.preventDefault();
                var citationIndex = $(this).data('citation-index');
                self.removeCitation(citationIndex);
            });

            // Edit citation button
            $(document).on('click', '.abt-edit-citation-btn', function(e) {
                e.preventDefault();
                var citationIndex = $(this).data('citation-index');
                self.editCitation(citationIndex);
            });

            // Citation style change
            $('#abt-citation-style-select').on('change', function() {
                var newStyle = $(this).val();
                self.switchCitationStyle(newStyle);
            });

            // Generate bibliography button
            $('#abt-generate-bibliography').on('click', function(e) {
                e.preventDefault();
                self.generateBibliography();
            });

            // Real-time citation preview
            $(document).on('input', '.abt-citation-field', function() {
                self.updateCitationPreview();
            });
        },

        /**
         * Show citation dialog
         */
        showCitationDialog: function(referenceId) {
            var self = this;
            
            // Create modal content
            var modalContent = `
                <div class="abt-citation-modal">
                    <div class="abt-modal-header">
                        <h2>Add Citation</h2>
                        <button class="abt-modal-close">&times;</button>
                    </div>
                    <div class="abt-modal-body">
                        <form id="abt-citation-form">
                            <input type="hidden" id="citation-reference-id" value="${referenceId}">
                            
                            <div class="abt-form-group">
                                <label for="citation-type">Citation Type:</label>
                                <select id="citation-type" class="abt-citation-field">
                                    <option value="in-text">In-text Citation</option>
                                    <option value="footnote">Footnote</option>
                                    <option value="bibliography">Bibliography Only</option>
                                </select>
                            </div>
                            
                            <div class="abt-form-group">
                                <label for="citation-page-numbers">Page Numbers:</label>
                                <input type="text" id="citation-page-numbers" class="abt-citation-field" placeholder="e.g., 123-145">
                            </div>
                            
                            <div class="abt-form-group">
                                <label for="citation-prefix">Prefix:</label>
                                <input type="text" id="citation-prefix" class="abt-citation-field" placeholder="e.g., see, cf.">
                            </div>
                            
                            <div class="abt-form-group">
                                <label for="citation-suffix">Suffix:</label>
                                <input type="text" id="citation-suffix" class="abt-citation-field" placeholder="e.g., emphasis added">
                            </div>
                            
                            <div class="abt-citation-preview">
                                <h4>Preview:</h4>
                                <div id="citation-preview-text">Loading preview...</div>
                            </div>
                        </form>
                    </div>
                    <div class="abt-modal-footer">
                        <button type="button" class="button" id="abt-cancel-citation">Cancel</button>
                        <button type="button" class="button-primary" id="abt-insert-citation">Insert Citation</button>
                    </div>
                </div>
            `;

            // Show modal
            this.showModal(modalContent);

            // Bind modal events
            $('#abt-cancel-citation, .abt-modal-close').on('click', function() {
                self.hideModal();
            });

            $('#abt-insert-citation').on('click', function() {
                self.insertCitation();
            });

            // Load initial preview
            this.updateCitationPreview();
        },

        /**
         * Insert citation
         */
        insertCitation: function() {
            var self = this;
            var postId = $('#post_ID').val();
            
            var citationData = {
                action: 'abt_insert_citation',
                nonce: abt_admin_ajax.citation_nonce,
                post_id: postId,
                reference_id: $('#citation-reference-id').val(),
                citation_type: $('#citation-type').val(),
                page_numbers: $('#citation-page-numbers').val(),
                prefix: $('#citation-prefix').val(),
                suffix: $('#citation-suffix').val()
            };

            $.ajax({
                url: abt_admin_ajax.ajax_url,
                type: 'POST',
                data: citationData,
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Citation inserted successfully', 'success');
                        self.hideModal();
                        self.refreshCitationList();
                        self.updateCitationCount(response.data.citation_count);
                        
                        // Insert citation text at cursor position if in editor
                        if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                            tinymce.activeEditor.insertContent(response.data.citation_text);
                        }
                    } else {
                        self.showNotice(response.data || 'Failed to insert citation', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    self.showNotice('Error inserting citation: ' + error, 'error');
                }
            });
        },

        /**
         * Initialize search functionality
         */
        initSearchFunctionality: function() {
            var self = this;

            // Reference search with autocomplete
            $('#abt-reference-search').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: abt_admin_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'abt_search_references_autocomplete',
                            nonce: abt_admin_ajax.autocomplete_nonce,
                            search_term: request.term,
                            limit: 10
                        },
                        success: function(data) {
                            if (data.success) {
                                response(data.data.suggestions);
                            }
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    self.selectReference(ui.item.value);
                }
            });

            // Advanced search form
            $('#abt-advanced-search-form').on('submit', function(e) {
                e.preventDefault();
                self.performAdvancedSearch();
            });

            // Quick filter buttons
            $('.abt-quick-filter').on('click', function(e) {
                e.preventDefault();
                var filterType = $(this).data('filter-type');
                var filterValue = $(this).data('filter-value');
                self.applyQuickFilter(filterType, filterValue);
            });

            // Search suggestions
            $('#abt-search-input').on('input', debounce(function() {
                var searchTerm = $(this).val();
                if (searchTerm.length >= 2) {
                    self.showSearchSuggestions(searchTerm);
                } else {
                    self.hideSearchSuggestions();
                }
            }, 300));
        },

        /**
         * Initialize bulk operations
         */
        initBulkOperations: function() {
            var self = this;

            // Select all checkbox
            $('#abt-select-all').on('change', function() {
                var isChecked = $(this).is(':checked');
                $('.abt-reference-checkbox').prop('checked', isChecked);
                self.updateBulkActionsState();
            });

            // Individual checkboxes
            $(document).on('change', '.abt-reference-checkbox', function() {
                self.updateBulkActionsState();
            });

            // Bulk action buttons
            $('#abt-bulk-delete').on('click', function(e) {
                e.preventDefault();
                self.performBulkAction('delete');
            });

            $('#abt-bulk-export').on('click', function(e) {
                e.preventDefault();
                self.performBulkAction('export');
            });

            $('#abt-bulk-categorize').on('click', function(e) {
                e.preventDefault();
                self.performBulkAction('categorize');
            });
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Initialize tooltips for all elements with data-tooltip attribute
            $(document).on('mouseenter', '[data-tooltip]', function() {
                var $this = $(this);
                var tooltipText = $this.data('tooltip');
                
                if (tooltipText && !$this.data('tooltip-shown')) {
                    var $tooltip = $('<div class="abt-tooltip">' + tooltipText + '</div>');
                    $('body').append($tooltip);
                    
                    var offset = $this.offset();
                    var tooltipWidth = $tooltip.outerWidth();
                    var tooltipHeight = $tooltip.outerHeight();
                    
                    $tooltip.css({
                        top: offset.top - tooltipHeight - 10,
                        left: offset.left + ($this.outerWidth() / 2) - (tooltipWidth / 2)
                    }).fadeIn(200);
                    
                    $this.data('tooltip-shown', true);
                    $this.data('tooltip-element', $tooltip);
                }
            });

            $(document).on('mouseleave', '[data-tooltip]', function() {
                var $this = $(this);
                var $tooltip = $this.data('tooltip-element');
                
                if ($tooltip) {
                    $tooltip.fadeOut(200, function() {
                        $tooltip.remove();
                    });
                    $this.removeData('tooltip-shown tooltip-element');
                }
            });
        },

        /**
         * Initialize drag and drop
         */
        initDragDrop: function() {
            var self = this;

            // Make citation list sortable
            if ($.fn.sortable) {
                $('#abt-citation-list').sortable({
                    handle: '.abt-citation-handle',
                    axis: 'y',
                    opacity: 0.7,
                    update: function(event, ui) {
                        self.updateCitationOrder();
                    }
                });
            }

            // File drop zone for imports
            $('#abt-import-dropzone').on({
                'dragover dragenter': function(e) {
                    e.preventDefault();
                    $(this).addClass('abt-dropzone-active');
                },
                'dragleave': function(e) {
                    e.preventDefault();
                    $(this).removeClass('abt-dropzone-active');
                },
                'drop': function(e) {
                    e.preventDefault();
                    $(this).removeClass('abt-dropzone-active');
                    
                    var files = e.originalEvent.dataTransfer.files;
                    if (files.length > 0) {
                        self.handleFileImport(files[0]);
                    }
                }
            });
        },

        /**
         * Initialize keyboard shortcuts
         */
        initKeyboardShortcuts: function() {
            var self = this;

            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + Shift + C: Quick citation
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.which === 67) {
                    e.preventDefault();
                    self.showQuickCitationDialog();
                }

                // Ctrl/Cmd + Shift + S: Quick search
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.which === 83) {
                    e.preventDefault();
                    $('#abt-search-input').focus();
                }

                // Escape: Close modals
                if (e.which === 27) {
                    self.hideModal();
                    self.hideSearchSuggestions();
                }
            });
        },

        /**
         * Update citation preview
         */
        updateCitationPreview: function() {
            var referenceId = $('#citation-reference-id').val();
            var citationType = $('#citation-type').val();
            var citationStyle = $('#abt-citation-style-select').val() || 'apa';

            if (!referenceId) return;

            $.ajax({
                url: abt_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_get_citation_preview',
                    nonce: abt_admin_ajax.citation_nonce,
                    reference_id: referenceId,
                    citation_type: citationType,
                    citation_style: citationStyle
                },
                success: function(response) {
                    if (response.success) {
                        $('#citation-preview-text').html(response.data.citation_preview);
                    }
                }
            });
        },

        /**
         * Switch citation style
         */
        switchCitationStyle: function(newStyle) {
            var self = this;
            var postId = $('#post_ID').val();

            if (!postId) return;

            $.ajax({
                url: abt_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_switch_citation_style',
                    nonce: abt_admin_ajax.citation_style_nonce,
                    post_id: postId,
                    citation_style: newStyle
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice(response.data.message, 'success');
                        self.refreshBibliography();
                        self.refreshCitationList();
                    } else {
                        self.showNotice(response.data || 'Failed to switch citation style', 'error');
                    }
                }
            });
        },

        /**
         * Generate bibliography
         */
        generateBibliography: function() {
            var self = this;
            var postId = $('#post_ID').val();
            var citationStyle = $('#abt-citation-style-select').val() || 'apa';

            if (!postId) {
                self.showNotice('Please save the post first', 'error');
                return;
            }

            $.ajax({
                url: abt_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_generate_bibliography',
                    nonce: abt_admin_ajax.bibliography_nonce,
                    post_id: postId,
                    citation_style: citationStyle
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice(response.data.message, 'success');
                        $('#abt-bibliography-preview').html(response.data.bibliography);
                    } else {
                        self.showNotice(response.data || 'Failed to generate bibliography', 'error');
                    }
                }
            });
        },

        /**
         * Utility functions
         */

        /**
         * Show modal dialog
         */
        showModal: function(content) {
            var modalHtml = `
                <div class="abt-modal-overlay">
                    <div class="abt-modal-container">
                        ${content}
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            $('.abt-modal-overlay').fadeIn(200);
        },

        /**
         * Hide modal dialog
         */
        hideModal: function() {
            $('.abt-modal-overlay').fadeOut(200, function() {
                $(this).remove();
            });
        },

        /**
         * Show loading overlay
         */
        showLoadingOverlay: function(message) {
            message = message || 'Loading...';
            var overlayHtml = `
                <div class="abt-loading-overlay">
                    <div class="abt-loading-content">
                        <div class="abt-spinner"></div>
                        <p>${message}</p>
                    </div>
                </div>
            `;
            
            $('body').append(overlayHtml);
        },

        /**
         * Hide loading overlay
         */
        hideLoadingOverlay: function() {
            $('.abt-loading-overlay').fadeOut(200, function() {
                $(this).remove();
            });
        },

        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            type = type || 'info';
            var noticeClass = 'notice-' + type;
            
            var notice = `
                <div class="notice ${noticeClass} is-dismissible abt-dynamic-notice">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `;
            
            $('.wrap h1').first().after(notice);
            
            // Auto-dismiss success messages
            if (type === 'success') {
                setTimeout(function() {
                    $('.abt-dynamic-notice').fadeOut();
                }, 5000);
            }
        },

        /**
         * Validate URL
         */
        isValidUrl: function(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        },

        /**
         * Clear auto-cite inputs
         */
        clearAutoCiteInputs: function() {
            $('.abt-auto-cite-input').val('');
        },

        /**
         * Handle AJAX errors
         */
        handleAjaxError: function(event, xhr, settings, error) {
            if (xhr.status === 0) return; // Aborted requests
            
            console.error('AJAX Error:', error);
            this.showNotice('An error occurred. Please try again.', 'error');
        },

        /**
         * Handle AJAX success (global)
         */
        handleAjaxSuccess: function(event, xhr, settings) {
            // Global success handler for any additional processing
        },

        /**
         * Save user preferences
         */
        savePreferences: function() {
            var preferences = {
                citation_style: $('#abt-citation-style-select').val(),
                view_mode: $('.abt-view-toggle.active').data('view'),
                items_per_page: $('#abt-items-per-page').val()
            };

            localStorage.setItem('abt_admin_preferences', JSON.stringify(preferences));
        },

        /**
         * Load saved preferences
         */
        loadSavedPreferences: function() {
            var saved = localStorage.getItem('abt_admin_preferences');
            if (saved) {
                try {
                    var preferences = JSON.parse(saved);
                    
                    if (preferences.citation_style) {
                        $('#abt-citation-style-select').val(preferences.citation_style);
                    }
                    
                    if (preferences.view_mode) {
                        $('.abt-view-toggle').removeClass('active');
                        $('.abt-view-toggle[data-view="' + preferences.view_mode + '"]').addClass('active');
                    }
                    
                    if (preferences.items_per_page) {
                        $('#abt-items-per-page').val(preferences.items_per_page);
                    }
                } catch (e) {
                    console.warn('Failed to load saved preferences:', e);
                }
            }
        },

        /**
         * Before unload handler
         */
        onBeforeUnload: function() {
            this.savePreferences();
        }
    };

    /**
     * Debounce function for search input
     */
    function debounce(func, wait, immediate) {
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
    }

    // Initialize when document is ready
    $(document).ready(function() {
        ABT_Admin.init();
    });

    // Export to global scope for external access
    window.ABT_Admin = ABT_Admin;

})(jQuery);