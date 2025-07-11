/**
 * Academic Blogger's Toolkit - Main Admin JavaScript
 * 
 * Handles general admin functionality including reference management,
 * bulk operations, import/export, and admin page interactions.
 * 
 * @package Academic_Bloggers_Toolkit
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Main Admin object
     */
    const ABT_Admin = {
        
        // Configuration
        config: {
            ajaxUrl: abt_admin_ajax.ajax_url,
            nonce: abt_admin_ajax.nonce,
            strings: abt_admin_ajax.strings || {}
        },

        // State management
        state: {
            selectedReferences: [],
            currentView: 'list',
            filters: {},
            sortOrder: {}
        },

        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initDataTables();
            this.initBulkActions();
            this.initFilters();
            
            console.log('ABT Admin initialized');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Reference management
            $(document).on('click', '.abt-preview-reference', this.previewReference.bind(this));
            $(document).on('click', '.abt-duplicate-reference', this.duplicateReference.bind(this));
            $(document).on('click', '.abt-delete-reference', this.deleteReference.bind(this));

            // Bulk operations
            $(document).on('click', '#cb-select-all-1, #cb-select-all-2', this.toggleSelectAll.bind(this));
            $(document).on('change', 'input[name="reference_ids[]"]', this.updateBulkActions.bind(this));
            $(document).on('submit', '#abt-bulk-form', this.handleBulkAction.bind(this));

            // Import/Export
            $(document).on('click', '#abt-import-references', this.openImportModal.bind(this));
            $(document).on('click', '#abt-export-all', this.exportAll.bind(this));
            $(document).on('click', '#abt-import-bibtex', this.importBibTeX.bind(this));
            $(document).on('click', '#abt-import-ris', this.importRIS.bind(this));
            $(document).on('click', '#abt-import-csv', this.importCSV.bind(this));

            // Search and filters
            $(document).on('input', '.abt-search-input', this.debounceSearch.bind(this));
            $(document).on('change', '.abt-filter-select', this.applyFilters.bind(this));
            $(document).on('click', '.abt-clear-filters', this.clearFilters.bind(this));

            // Statistics refresh
            $(document).on('click', '#abt-refresh-stats', this.refreshStatistics.bind(this));

            // Modal controls
            $(document).on('click', '.abt-modal-close', this.closeModal.bind(this));
            $(document).on('click', '.abt-modal-overlay', function(e) {
                if (e.target === this) {
                    ABT_Admin.closeModal();
                }
            });

            // Auto-save draft
            $(document).on('input', '.abt-auto-save', this.autoSaveDraft.bind(this));

            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts.bind(this));

            // Page-specific events
            this.bindPageSpecificEvents();
        },

        /**
         * Bind page-specific events
         */
        bindPageSpecificEvents: function() {
            const currentPage = this.getCurrentPage();
            
            switch (currentPage) {
                case 'references':
                    this.bindReferencesPageEvents();
                    break;
                case 'statistics':
                    this.bindStatisticsPageEvents();
                    break;
                case 'settings':
                    this.bindSettingsPageEvents();
                    break;
                case 'import-export':
                    this.bindImportExportPageEvents();
                    break;
            }
        },

        /**
         * Get current admin page
         */
        getCurrentPage: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const page = urlParams.get('page');
            
            if (page === 'academic-bloggers-toolkit') return 'references';
            if (page === 'abt-statistics') return 'statistics';
            if (page === 'abt-settings') return 'settings';
            if (page === 'abt-import-export') return 'import-export';
            
            return 'unknown';
        },

        /**
         * Initialize DataTables
         */
        initDataTables: function() {
            if ($.fn.DataTable && $('.abt-references-table').length) {
                $('.abt-references-table').DataTable({
                    responsive: true,
                    pageLength: 20,
                    order: [[7, 'desc']], // Sort by date added
                    columnDefs: [
                        { orderable: false, targets: [0, 6] }, // Checkbox and actions columns
                        { searchable: false, targets: [0, 6] }
                    ],
                    language: {
                        search: "Search references:",
                        lengthMenu: "Show _MENU_ references per page",
                        info: "Showing _START_ to _END_ of _TOTAL_ references",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    }
                });
            }
        },

        /**
         * Initialize bulk actions
         */
        initBulkActions: function() {
            this.updateBulkActions();
        },

        /**
         * Initialize filters
         */
        initFilters: function() {
            // Restore filters from URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            
            ['ref_type', 'orderby', 'order'].forEach(param => {
                const value = urlParams.get(param);
                if (value) {
                    $(`[name="${param}"]`).val(value);
                }
            });
        },

        /**
         * Preview reference
         */
        previewReference: function(e) {
            e.preventDefault();
            
            const refId = $(e.target).data('ref-id');
            
            this.ajaxRequest('abt_get_reference_preview', {
                reference_id: refId
            })
            .done((response) => {
                if (response.success) {
                    this.displayReferencePreview(response.data.preview);
                } else {
                    this.showNotice('error', response.data.message);
                }
            });
        },

        /**
         * Display reference preview modal
         */
        displayReferencePreview: function(preview) {
            const modalHtml = `
                <div class="abt-modal-overlay abt-preview-modal">
                    <div class="abt-modal-content">
                        <div class="abt-modal-header">
                            <h3>Reference Preview</h3>
                            <button class="abt-modal-close">&times;</button>
                        </div>
                        <div class="abt-modal-body">
                            <div class="abt-preview-content">
                                <h4>${preview.title}</h4>
                                
                                <div class="abt-preview-metadata">
                                    ${Object.entries(preview.metadata).map(([key, value]) => 
                                        `<div class="abt-meta-item">
                                            <strong>${this.formatMetaLabel(key)}:</strong> ${value}
                                        </div>`
                                    ).join('')}
                                </div>
                                
                                <div class="abt-preview-citations">
                                    <h5>Formatted Citations</h5>
                                    <div class="abt-citation-preview">
                                        <strong>APA:</strong> ${preview.formatted_apa}
                                    </div>
                                    <div class="abt-citation-preview">
                                        <strong>MLA:</strong> ${preview.formatted_mla}
                                    </div>
                                </div>
                                
                                <div class="abt-preview-usage">
                                    <p><strong>Usage:</strong> Cited ${preview.usage_count} time(s)</p>
                                </div>
                            </div>
                        </div>
                        <div class="abt-modal-footer">
                            <a href="/wp-admin/post.php?post=${preview.id}&action=edit" class="button button-primary">Edit Reference</a>
                            <button class="button abt-modal-close">Close</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
        },

        /**
         * Duplicate reference
         */
        duplicateReference: function(e) {
            e.preventDefault();
            
            const refId = $(e.target).data('ref-id');
            
            if (!confirm('Are you sure you want to duplicate this reference?')) {
                return;
            }
            
            this.ajaxRequest('abt_duplicate_reference', {
                reference_id: refId
            })
            .done((response) => {
                if (response.success) {
                    this.showNotice('success', response.data.message);
                    // Redirect to edit the new reference
                    window.location.href = response.data.edit_url;
                } else {
                    this.showNotice('error', response.data.message);
                }
            });
        },

        /**
         * Delete reference
         */
        deleteReference: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this reference? This action cannot be undone.')) {
                return;
            }
            
            // Handle deletion via standard WordPress mechanism
            return true;
        },

        /**
         * Toggle select all checkboxes
         */
        toggleSelectAll: function(e) {
            const isChecked = $(e.target).prop('checked');
            $('input[name="reference_ids[]"]').prop('checked', isChecked);
            this.updateBulkActions();
        },

        /**
         * Update bulk actions state
         */
        updateBulkActions: function() {
            const selectedCount = $('input[name="reference_ids[]"]:checked').length;
            const $bulkActions = $('.abt-bulk-actions');
            
            if (selectedCount > 0) {
                $bulkActions.removeClass('disabled');
                $('.abt-selection-count').text(`${selectedCount} selected`);
            } else {
                $bulkActions.addClass('disabled');
                $('.abt-selection-count').text('');
            }
            
            this.state.selectedReferences = $('input[name="reference_ids[]"]:checked').map(function() {
                return this.value;
            }).get();
        },

        /**
         * Handle bulk action
         */
        handleBulkAction: function(e) {
            e.preventDefault();
            
            const action = $('#bulk-action-selector-top').val();
            const selectedIds = this.state.selectedReferences;
            
            if (!action || selectedIds.length === 0) {
                this.showNotice('warning', 'Please select an action and at least one reference.');
                return;
            }
            
            switch (action) {
                case 'delete':
                    this.bulkDelete(selectedIds);
                    break;
                case 'export':
                    this.bulkExport(selectedIds);
                    break;
                default:
                    this.showNotice('error', 'Invalid action selected.');
            }
        },

        /**
         * Bulk delete references
         */
        bulkDelete: function(referenceIds) {
            if (!confirm(`Are you sure you want to delete ${referenceIds.length} reference(s)? This action cannot be undone.`)) {
                return;
            }
            
            this.showLoader('.abt-bulk-actions');
            
            this.ajaxRequest('abt_bulk_delete_references', {
                reference_ids: referenceIds
            })
            .done((response) => {
                this.hideLoader('.abt-bulk-actions');
                
                if (response.success) {
                    // Remove rows from table
                    referenceIds.forEach(id => {
                        $(`.abt-references-table tr[data-ref-id="${id}"]`).fadeOut();
                    });
                    
                    this.showNotice('success', response.data.message);
                    this.updateBulkActions();
                } else {
                    this.showNotice('error', response.data.message);
                }
            })
            .fail(() => {
                this.hideLoader('.abt-bulk-actions');
                this.showNotice('error', 'Failed to delete references. Please try again.');
            });
        },

        /**
         * Bulk export references
         */
        bulkExport: function(referenceIds) {
            this.showLoader('.abt-bulk-actions');
            
            this.ajaxRequest('abt_bulk_export_references', {
                reference_ids: referenceIds,
                format: 'bibtex'
            })
            .done((response) => {
                this.hideLoader('.abt-bulk-actions');
                
                if (response.success) {
                    this.downloadFile(response.data.content, response.data.filename, response.data.mime_type);
                    this.showNotice('success', 'References exported successfully.');
                } else {
                    this.showNotice('error', response.data.message);
                }
            })
            .fail(() => {
                this.hideLoader('.abt-bulk-actions');
                this.showNotice('error', 'Failed to export references. Please try again.');
            });
        },

        /**
         * Open import modal
         */
        openImportModal: function(e) {
            e.preventDefault();
            
            const modalHtml = `
                <div class="abt-modal-overlay abt-import-modal">
                    <div class="abt-modal-content">
                        <div class="abt-modal-header">
                            <h3>Import References</h3>
                            <button class="abt-modal-close">&times;</button>
                        </div>
                        <div class="abt-modal-body">
                            <div class="abt-import-options">
                                <div class="abt-import-method">
                                    <h4>Select Import Method</h4>
                                    <div class="abt-import-buttons">
                                        <button class="button button-large" id="abt-import-file">
                                            <span class="dashicons dashicons-upload"></span>
                                            Upload File
                                        </button>
                                        <button class="button button-large" id="abt-import-paste">
                                            <span class="dashicons dashicons-clipboard"></span>
                                            Paste Content
                                        </button>
                                        <button class="button button-large" id="abt-import-url">
                                            <span class="dashicons dashicons-admin-links"></span>
                                            From URL
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="abt-import-form" id="abt-import-form" style="display: none;">
                                    <!-- Import form will be populated based on method -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            // Bind import method events
            $('#abt-import-file').on('click', () => this.showFileImport());
            $('#abt-import-paste').on('click', () => this.showPasteImport());
            $('#abt-import-url').on('click', () => this.showUrlImport());
        },

        /**
         * Show file import interface
         */
        showFileImport: function() {
            const formHtml = `
                <form id="abt-file-import-form" enctype="multipart/form-data">
                    <table class="form-table">
                        <tr>
                            <th><label for="abt-import-file-input">Select File</label></th>
                            <td>
                                <input type="file" id="abt-import-file-input" name="import_file" 
                                       accept=".bib,.ris,.csv,.txt" required />
                                <p class="description">
                                    Supported formats: BibTeX (.bib), RIS (.ris), CSV (.csv), Plain text (.txt)
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="abt-import-format">Format</label></th>
                            <td>
                                <select id="abt-import-format" name="format" required>
                                    <option value="">Auto-detect</option>
                                    <option value="bibtex">BibTeX</option>
                                    <option value="ris">RIS</option>
                                    <option value="csv">CSV</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="abt-import-options">Options</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="abt-skip-duplicates" name="skip_duplicates" checked />
                                    Skip duplicate references
                                </label><br />
                                <label>
                                    <input type="checkbox" id="abt-validate-data" name="validate_data" checked />
                                    Validate reference data
                                </label>
                            </td>
                        </tr>
                    </table>
                    <div class="abt-form-actions">
                        <button type="submit" class="button button-primary">Import References</button>
                        <button type="button" class="button abt-modal-close">Cancel</button>
                    </div>
                </form>
            `;
            
            $('#abt-import-form').html(formHtml).show();
            
            $('#abt-file-import-form').on('submit', this.handleFileImport.bind(this));
        },

        /**
         * Handle file import
         */
        handleFileImport: function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'abt_import_references');
            formData.append('nonce', this.config.nonce);
            
            this.showLoader('#abt-file-import-form .button-primary');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            })
            .done((response) => {
                this.hideLoader('#abt-file-import-form .button-primary');
                
                if (response.success) {
                    this.closeModal();
                    this.showNotice('success', response.data.message);
                    // Refresh the page to show imported references
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    this.showNotice('error', response.data.message);
                }
            })
            .fail(() => {
                this.hideLoader('#abt-file-import-form .button-primary');
                this.showNotice('error', 'Import failed. Please try again.');
            });
        },

        /**
         * Export all references
         */
        exportAll: function(e) {
            e.preventDefault();
            
            const format = prompt('Export format (bibtex, ris, csv):', 'bibtex');
            
            if (!format) return;
            
            this.showLoader('#abt-export-all');
            
            this.ajaxRequest('abt_export_all_references', {
                format: format
            })
            .done((response) => {
                this.hideLoader('#abt-export-all');
                
                if (response.success) {
                    this.downloadFile(response.data.content, response.data.filename, response.data.mime_type);
                    this.showNotice('success', 'All references exported successfully.');
                } else {
                    this.showNotice('error', response.data.message);
                }
            })
            .fail(() => {
                this.hideLoader('#abt-export-all');
                this.showNotice('error', 'Export failed. Please try again.');
            });
        },

        /**
         * Debounced search
         */
        debounceSearch: function(e) {
            clearTimeout(this.searchTimeout);
            
            this.searchTimeout = setTimeout(() => {
                this.performSearch($(e.target).val());
            }, 500);
        },

        /**
         * Perform search
         */
        performSearch: function(searchTerm) {
            const currentUrl = new URL(window.location.href);
            
            if (searchTerm.trim()) {
                currentUrl.searchParams.set('s', searchTerm);
            } else {
                currentUrl.searchParams.delete('s');
            }
            
            window.location.href = currentUrl.toString();
        },

        /**
         * Apply filters
         */
        applyFilters: function() {
            const form = $('.abt-search-form')[0];
            if (form) {
                form.submit();
            }
        },

        /**
         * Clear filters
         */
        clearFilters: function(e) {
            e.preventDefault();
            
            const baseUrl = window.location.pathname + '?page=' + this.getCurrentPageParam();
            window.location.href = baseUrl;
        },

        /**
         * Get current page parameter
         */
        getCurrentPageParam: function() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('page') || 'academic-bloggers-toolkit';
        },

        /**
         * Refresh statistics
         */
        refreshStatistics: function(e) {
            e.preventDefault();
            
            this.showLoader('#abt-refresh-stats');
            
            this.ajaxRequest('abt_refresh_statistics', {})
            .done((response) => {
                this.hideLoader('#abt-refresh-stats');
                
                if (response.success) {
                    this.updateStatistics(response.data.statistics);
                    this.showNotice('success', 'Statistics refreshed.');
                } else {
                    this.showNotice('error', response.data.message);
                }
            })
            .fail(() => {
                this.hideLoader('#abt-refresh-stats');
                this.showNotice('error', 'Failed to refresh statistics.');
            });
        },

        /**
         * Auto-save draft
         */
        autoSaveDraft: function(e) {
            const $field = $(e.target);
            const fieldName = $field.attr('name');
            const fieldValue = $field.val();
            
            // Debounce auto-save
            clearTimeout(this.autoSaveTimeout);
            
            this.autoSaveTimeout = setTimeout(() => {
                this.saveDraft(fieldName, fieldValue);
            }, 2000);
        },

        /**
         * Save draft
         */
        saveDraft: function(fieldName, fieldValue) {
            this.ajaxRequest('abt_save_draft', {
                field_name: fieldName,
                field_value: fieldValue,
                post_id: this.getPostId()
            })
            .done((response) => {
                if (response.success) {
                    this.showNotice('info', 'Draft saved.', 2000);
                }
            });
        },

        /**
         * Handle keyboard shortcuts
         */
        handleKeyboardShortcuts: function(e) {
            // Ctrl/Cmd + I: Import
            if ((e.ctrlKey || e.metaKey) && e.key === 'i') {
                e.preventDefault();
                this.openImportModal(e);
            }
            
            // Ctrl/Cmd + E: Export
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                this.exportAll(e);
            }
            
            // Ctrl/Cmd + A: Select all (in tables)
            if ((e.ctrlKey || e.metaKey) && e.key === 'a' && $('.abt-references-table').length) {
                e.preventDefault();
                $('#cb-select-all-1').prop('checked', true).trigger('change');
            }
        },

        /**
         * Bind references page events
         */
        bindReferencesPageEvents: function() {
            // Reference-specific functionality
            $(document).on('click', '.abt-quick-edit', this.quickEditReference.bind(this));
            $(document).on('click', '.abt-view-citations', this.viewCitations.bind(this));
        },

        /**
         * Bind statistics page events
         */
        bindStatisticsPageEvents: function() {
            // Initialize charts if Chart.js is available
            if (typeof Chart !== 'undefined') {
                this.initCharts();
            }
        },

        /**
         * Bind settings page events
         */
        bindSettingsPageEvents: function() {
            // Settings-specific functionality
            $(document).on('change', '.abt-setting-field', this.saveSetting.bind(this));
            $(document).on('click', '.abt-reset-settings', this.resetSettings.bind(this));
        },

        /**
         * Bind import/export page events
         */
        bindImportExportPageEvents: function() {
            // Import/export specific functionality
            $(document).on('change', '#abt-export-format', this.updateExportOptions.bind(this));
            $(document).on('click', '.abt-download-sample', this.downloadSample.bind(this));
        },

        /**
         * Close modal
         */
        closeModal: function() {
            $('.abt-modal-overlay').fadeOut(() => {
                $('.abt-modal-overlay').remove();
            });
        },

        /**
         * Format metadata label
         */
        formatMetaLabel: function(key) {
            const labels = {
                'type': 'Type',
                'authors': 'Authors',
                'year': 'Year',
                'journal': 'Journal',
                'volume': 'Volume',
                'issue': 'Issue',
                'pages': 'Pages',
                'doi': 'DOI',
                'url': 'URL',
                'pmid': 'PMID',
                'isbn': 'ISBN'
            };
            
            return labels[key] || key.charAt(0).toUpperCase() + key.slice(1);
        },

        /**
         * Download file
         */
        downloadFile: function(content, filename, mimeType) {
            const blob = new Blob([content], { type: mimeType });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        },

        /**
         * Get post ID
         */
        getPostId: function() {
            return $('#post_ID').val() || 0;
        },

        /**
         * Make AJAX request
         */
        ajaxRequest: function(action, data) {
            const requestData = $.extend({
                action: action,
                nonce: this.config.nonce
            }, data);
            
            return $.post(this.config.ajaxUrl, requestData);
        },

        /**
         * Show loader
         */
        showLoader: function(selector) {
            $(selector).prop('disabled', true).addClass('abt-loading');
        },

        /**
         * Hide loader
         */
        hideLoader: function(selector) {
            $(selector).prop('disabled', false).removeClass('abt-loading');
        },

        /**
         * Show notice
         */
        showNotice: function(type, message, duration = 5000) {
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible abt-notice">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.wrap').prepend($notice);
            
            // Auto-dismiss
            if (duration > 0) {
                setTimeout(() => {
                    $notice.fadeOut(() => $notice.remove());
                }, duration);
            }
            
            // Manual dismiss
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(() => $notice.remove());
            });
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Only initialize on ABT admin pages
        if ($('body').hasClass('toplevel_page_academic-bloggers-toolkit') ||
            $('body').hasClass('academic-bloggers-toolkit_page_abt-statistics') ||
            $('body').hasClass('academic-bloggers-toolkit_page_abt-settings') ||
            $('body').hasClass('academic-bloggers-toolkit_page_abt-import-export')) {
            ABT_Admin.init();
        }
    });

    // Expose to global scope for debugging
    window.ABT_Admin = ABT_Admin;

})(jQuery);