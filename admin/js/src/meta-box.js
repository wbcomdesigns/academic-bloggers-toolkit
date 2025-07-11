/**
 * Academic Blogger's Toolkit - Meta Box JavaScript
 * 
 * Handles all meta box interactions including citations, footnotes,
 * bibliography generation, and auto-cite functionality.
 * 
 * @package Academic_Bloggers_Toolkit
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Main Meta Box object
     */
    const ABT_MetaBox = {
        
        // Configuration
        config: {
            ajaxUrl: abt_metabox_ajax.ajax_url,
            nonce: abt_metabox_ajax.nonce,
            postId: abt_metabox_ajax.post_id,
            strings: abt_metabox_ajax.strings
        },

        // Current editing states
        state: {
            editingCitation: null,
            editingFootnote: null,
            citationsModified: false,
            footnotesModified: false
        },

        /**
         * Initialize meta box functionality
         */
        init: function() {
            this.bindEvents();
            this.initSortable();
            this.initTooltips();
            
            console.log('ABT Meta Box initialized');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Citation management
            $(document).on('click', '#abt-add-citation', this.openCitationModal.bind(this));
            $(document).on('click', '.abt-edit-citation', this.editCitation.bind(this));
            $(document).on('click', '.abt-delete-citation', this.deleteCitation.bind(this));
            $(document).on('submit', '#abt-citation-form', this.saveCitation.bind(this));

            // Footnote management
            $(document).on('click', '#abt-add-footnote', this.openFootnoteModal.bind(this));
            $(document).on('click', '.abt-edit-footnote', this.editFootnote.bind(this));
            $(document).on('click', '.abt-delete-footnote', this.deleteFootnote.bind(this));

            // Bibliography actions
            $(document).on('click', '#abt-refresh-bibliography', this.refreshBibliography.bind(this));
            $(document).on('click', '#abt-copy-bibliography', this.copyBibliography.bind(this));
            $(document).on('click', '#abt-export-bibliography', this.exportBibliography.bind(this));

            // Modal controls
            $(document).on('click', '.abt-modal-close', this.closeModal.bind(this));
            $(document).on('click', '.abt-citation-modal', function(e) {
                if (e.target === this) {
                    ABT_MetaBox.closeModal();
                }
            });

            // Auto-cite functionality
            $(document).on('click', '#abt-import-citation', this.openAutoCiteModal.bind(this));
            $(document).on('click', '#abt-fetch-doi', this.fetchFromDOI.bind(this));
            $(document).on('click', '#abt-fetch-url', this.fetchFromURL.bind(this));

            // Reference search
            $(document).on('input', '#abt_citation_reference_search', this.searchReferences.bind(this));
            $(document).on('click', '.abt-reference-result', this.selectReference.bind(this));

            // Settings changes
            $(document).on('change', '#abt_citation_style', this.onStyleChange.bind(this));
            $(document).on('change', '#abt_footnote_style', this.onFootnoteStyleChange.bind(this));

            // Save detection
            $(document).on('click', '#publish, #save-post', this.beforeSave.bind(this));

            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts.bind(this));
        },

        /**
         * Initialize sortable functionality
         */
        initSortable: function() {
            if ($.fn.sortable) {
                $('.abt-citations-table tbody').sortable({
                    handle: '.abt-citation-handle',
                    placeholder: 'abt-sort-placeholder',
                    update: this.updateCitationOrder.bind(this)
                });

                $('.abt-footnotes-table tbody').sortable({
                    handle: '.abt-footnote-handle',
                    placeholder: 'abt-sort-placeholder',
                    update: this.updateFootnoteOrder.bind(this)
                });
            }
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            if ($.fn.tooltip) {
                $('.abt-tooltip').tooltip({
                    position: { my: "center bottom-20", at: "center top" }
                });
            }
        },

        /**
         * Open citation modal
         */
        openCitationModal: function(e) {
            e.preventDefault();
            
            this.state.editingCitation = null;
            this.resetCitationForm();
            $('#abt-citation-modal').show();
            $('#abt_citation_reference_search').focus();
        },

        /**
         * Edit existing citation
         */
        editCitation: function(e) {
            e.preventDefault();
            
            const $row = $(e.target).closest('tr');
            const citationId = $row.data('citation-id');
            
            this.state.editingCitation = citationId;
            
            // Load citation data via AJAX
            this.ajaxRequest('abt_get_citation', {
                citation_id: citationId
            })
            .done((response) => {
                if (response.success) {
                    this.populateCitationForm(response.data.citation);
                    $('#abt-citation-modal').show();
                } else {
                    this.showNotice('error', response.data.message);
                }
            });
        },

        /**
         * Delete citation
         */
        deleteCitation: function(e) {
            e.preventDefault();
            
            if (!confirm(this.config.strings.confirm_delete)) {
                return;
            }
            
            const $row = $(e.target).closest('tr');
            const citationId = $row.data('citation-id');
            
            this.ajaxRequest('abt_delete_citation', {
                citation_id: citationId
            })
            .done((response) => {
                if (response.success) {
                    $row.fadeOut(() => $row.remove());
                    this.updateCitationNumbers();
                    this.refreshBibliography();
                    this.showNotice('success', response.data.message);
                    this.state.citationsModified = true;
                } else {
                    this.showNotice('error', response.data.message);
                }
            });
        },

        /**
         * Save citation
         */
        saveCitation: function(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const formData = this.serializeFormData($form);
            
            // Add citation ID if editing
            if (this.state.editingCitation) {
                formData.citation_id = this.state.editingCitation;
            }
            
            formData.post_id = this.config.postId;
            
            this.ajaxRequest('abt_save_citation', formData)
            .done((response) => {
                if (response.success) {
                    this.closeModal();
                    this.updateCitationRow(response.data.citation);
                    this.refreshBibliography();
                    this.showNotice('success', response.data.message);
                    this.state.citationsModified = true;
                } else {
                    this.showNotice('error', response.data.message);
                }
            });
        },

        /**
         * Open footnote modal
         */
        openFootnoteModal: function(e) {
            e.preventDefault();
            
            this.state.editingFootnote = null;
            this.resetFootnoteForm();
            $('#abt-footnote-modal').show();
            $('#abt_footnote_content').focus();
        },

        /**
         * Edit existing footnote
         */
        editFootnote: function(e) {
            e.preventDefault();
            
            const $row = $(e.target).closest('tr');
            const footnoteId = $row.data('footnote-id');
            
            this.state.editingFootnote = footnoteId;
            
            // Load footnote data via AJAX
            this.ajaxRequest('abt_get_footnote', {
                footnote_id: footnoteId
            })
            .done((response) => {
                if (response.success) {
                    this.populateFootnoteForm(response.data.footnote);
                    $('#abt-footnote-modal').show();
                } else {
                    this.showNotice('error', response.data.message);
                }
            });
        },

        /**
         * Delete footnote
         */
        deleteFootnote: function(e) {
            e.preventDefault();
            
            if (!confirm(this.config.strings.confirm_delete_footnote)) {
                return;
            }
            
            const $row = $(e.target).closest('tr');
            const footnoteId = $row.data('footnote-id');
            
            this.ajaxRequest('abt_delete_footnote', {
                footnote_id: footnoteId
            })
            .done((response) => {
                if (response.success) {
                    $row.fadeOut(() => $row.remove());
                    this.updateFootnoteNumbers();
                    this.showNotice('success', response.data.message);
                    this.state.footnotesModified = true;
                } else {
                    this.showNotice('error', response.data.message);
                }
            });
        },

        /**
         * Refresh bibliography
         */
        refreshBibliography: function(e) {
            if (e) e.preventDefault();
            
            const style = $('#abt_citation_style').val() || 'apa';
            
            this.ajaxRequest('abt_generate_bibliography', {
                post_id: this.config.postId,
                style: style
            })
            .done((response) => {
                if (response.success) {
                    this.updateBibliographyPreview(response.data.bibliography, response.data.style);
                } else {
                    this.showNotice('error', response.data.message);
                }
            });
        },

        /**
         * Copy bibliography to clipboard
         */
        copyBibliography: function(e) {
            e.preventDefault();
            
            const bibliographyText = $('.abt-bibliography-content').text();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(bibliographyText).then(() => {
                    this.showNotice('success', 'Bibliography copied to clipboard');
                });
            } else {
                // Fallback for older browsers
                const $temp = $('<textarea>').val(bibliographyText).appendTo('body').select();
                document.execCommand('copy');
                $temp.remove();
                this.showNotice('success', 'Bibliography copied to clipboard');
            }
        },

        /**
         * Export bibliography
         */
        exportBibliography: function(e) {
            e.preventDefault();
            
            const style = $('#abt_citation_style').val() || 'apa';
            
            this.ajaxRequest('abt_export_bibliography', {
                post_id: this.config.postId,
                style: style,
                format: 'rtf'
            })
            .done((response) => {
                if (response.success) {
                    // Create download link
                    const blob = new Blob([response.data.content], { type: 'application/rtf' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'bibliography.rtf';
                    a.click();
                    window.URL.revokeObjectURL(url);
                } else {
                    this.showNotice('error', response.data.message);
                }
            });
        },

        /**
         * Search references
         */
        searchReferences: function(e) {
            const searchTerm = $(e.target).val();
            
            if (searchTerm.length < 2) {
                $('#abt-reference-results').hide();
                return;
            }
            
            // Debounce search
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.performReferenceSearch(searchTerm);
            }, 300);
        },

        /**
         * Perform reference search
         */
        performReferenceSearch: function(searchTerm) {
            this.ajaxRequest('abt_search_references', {
                search_term: searchTerm,
                limit: 10
            })
            .done((response) => {
                if (response.success) {
                    this.displayReferenceResults(response.data.references);
                }
            });
        },

        /**
         * Display reference search results
         */
        displayReferenceResults: function(references) {
            const $results = $('#abt-reference-results');
            
            if (references.length === 0) {
                $results.html('<div class="abt-no-results">No references found</div>').show();
                return;
            }
            
            let html = '<div class="abt-reference-list">';
            references.forEach(ref => {
                html += `
                    <div class="abt-reference-result" data-ref-id="${ref.id}">
                        <div class="abt-ref-title">${ref.title}</div>
                        <div class="abt-ref-meta">${ref.formatted}</div>
                    </div>
                `;
            });
            html += '</div>';
            
            $results.html(html).show();
        },

        /**
         * Select reference from search results
         */
        selectReference: function(e) {
            const $result = $(e.target).closest('.abt-reference-result');
            const refId = $result.data('ref-id');
            const title = $result.find('.abt-ref-title').text();
            
            $('#abt_citation_reference').val(refId);
            $('#abt_citation_reference_display').val(title);
            $('#abt-reference-results').hide();
        },

        /**
         * Fetch reference data from DOI
         */
        fetchFromDOI: function(e) {
            e.preventDefault();
            
            const doi = $('#abt-doi-input').val().trim();
            
            if (!doi) {
                this.showNotice('error', 'Please enter a DOI');
                return;
            }
            
            this.showLoader('#abt-fetch-doi');
            
            this.ajaxRequest('abt_fetch_from_doi', {
                doi: doi
            })
            .done((response) => {
                this.hideLoader('#abt-fetch-doi');
                
                if (response.success) {
                    this.populateReferenceForm(response.data.reference_data);
                    this.showNotice('success', response.data.message);
                } else {
                    this.showNotice('error', response.data.message);
                }
            })
            .fail(() => {
                this.hideLoader('#abt-fetch-doi');
                this.showNotice('error', 'Failed to fetch DOI data');
            });
        },

        /**
         * Fetch reference data from URL
         */
        fetchFromURL: function(e) {
            e.preventDefault();
            
            const url = $('#abt-url-input').val().trim();
            
            if (!url) {
                this.showNotice('error', 'Please enter a URL');
                return;
            }
            
            this.showLoader('#abt-fetch-url');
            
            this.ajaxRequest('abt_fetch_from_url', {
                url: url
            })
            .done((response) => {
                this.hideLoader('#abt-fetch-url');
                
                if (response.success) {
                    this.populateReferenceForm(response.data.reference_data);
                    this.showNotice('success', response.data.message);
                } else {
                    this.showNotice('error', response.data.message);
                }
            })
            .fail(() => {
                this.hideLoader('#abt-fetch-url');
                this.showNotice('error', 'Failed to fetch URL data');
            });
        },

        /**
         * Handle citation style change
         */
        onStyleChange: function(e) {
            const newStyle = $(e.target).val();
            this.refreshBibliography();
            this.showNotice('info', `Citation style changed to ${newStyle.toUpperCase()}`);
        },

        /**
         * Handle footnote style change
         */
        onFootnoteStyleChange: function(e) {
            const newStyle = $(e.target).val();
            this.updateFootnoteNumbers();
            this.showNotice('info', `Footnote style changed to ${newStyle}`);
        },

        /**
         * Update citation order after sorting
         */
        updateCitationOrder: function(event, ui) {
            const citationIds = [];
            $('.abt-citations-table tbody tr').each(function() {
                const citationId = $(this).data('citation-id');
                if (citationId) {
                    citationIds.push(citationId);
                }
            });
            
            this.ajaxRequest('abt_update_citation_order', {
                citation_ids: citationIds,
                post_id: this.config.postId
            })
            .done((response) => {
                if (response.success) {
                    this.updateCitationNumbers();
                    this.refreshBibliography();
                    this.state.citationsModified = true;
                }
            });
        },

        /**
         * Update citation numbers in the table
         */
        updateCitationNumbers: function() {
            $('.abt-citations-table tbody tr').each(function(index) {
                $(this).find('.abt-citation-position').text(index + 1);
            });
        },

        /**
         * Update footnote numbers
         */
        updateFootnoteNumbers: function() {
            const style = $('#abt_footnote_style').val() || 'numeric';
            
            $('.abt-footnotes-table tbody tr').each(function(index) {
                const number = ABT_MetaBox.formatFootnoteNumber(index + 1, style);
                $(this).find('.abt-footnote-number').text(number);
            });
        },

        /**
         * Format footnote number based on style
         */
        formatFootnoteNumber: function(number, style) {
            switch (style) {
                case 'roman':
                    const romanNumerals = ['', 'i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x'];
                    return romanNumerals[number] || number;
                case 'alpha':
                    return String.fromCharCode(96 + number); // a, b, c, etc.
                case 'symbols':
                    const symbols = ['', '*', '†', '‡', '§', '‖', '¶'];
                    return symbols[number] || number;
                case 'numeric':
                default:
                    return number;
            }
        },

        /**
         * Close modal
         */
        closeModal: function() {
            $('.abt-citation-modal, .abt-footnote-modal, .abt-autocite-modal').hide();
            this.state.editingCitation = null;
            this.state.editingFootnote = null;
        },

        /**
         * Reset citation form
         */
        resetCitationForm: function() {
            $('#abt-citation-form')[0].reset();
            $('#abt-reference-results').hide();
        },

        /**
         * Reset footnote form
         */
        resetFootnoteForm: function() {
            $('#abt-footnote-form')[0].reset();
        },

        /**
         * Populate citation form with data
         */
        populateCitationForm: function(citation) {
            $('#abt_citation_reference').val(citation.reference_id);
            $('#abt_citation_reference_display').val(citation.reference_title);
            $('#abt_citation_position').val(citation.position);
            $('#abt_citation_prefix').val(citation.prefix);
            $('#abt_citation_suffix').val(citation.suffix);
            $('#abt_citation_suppress_author').prop('checked', citation.suppress_author);
        },

        /**
         * Populate footnote form with data
         */
        populateFootnoteForm: function(footnote) {
            $('#abt_footnote_content').val(footnote.content);
            $('#abt_footnote_position').val(footnote.position);
        },

        /**
         * Update citation row in table
         */
        updateCitationRow: function(citation) {
            let $row = $(`.abt-citations-table tr[data-citation-id="${citation.id}"]`);
            
            if ($row.length === 0) {
                // Add new row
                const rowHtml = this.buildCitationRowHtml(citation);
                $('.abt-citations-table tbody').append(rowHtml);
                $('.abt-no-citations').hide();
            } else {
                // Update existing row
                $row.find('.abt-reference-title').text(citation.reference_title);
                $row.find('.abt-citation-position').text(citation.position);
                // Update other fields as needed
            }
        },

        /**
         * Build citation row HTML
         */
        buildCitationRowHtml: function(citation) {
            return `
                <tr data-citation-id="${citation.id}">
                    <td>
                        <strong class="abt-reference-title">${citation.reference_title}</strong>
                        <div class="row-actions">
                            <span class="edit">
                                <a href="#" class="abt-edit-citation">Edit</a> |
                            </span>
                            <span class="delete">
                                <a href="#" class="abt-delete-citation" style="color: #a00;">Remove</a>
                            </span>
                        </div>
                    </td>
                    <td><span class="abt-citation-position">${citation.position}</span></td>
                    <td>
                        <span class="abt-citation-format">
                            ${citation.prefix ? citation.prefix + ' ' : ''}Citation${citation.suffix ? ' ' + citation.suffix : ''}
                        </span>
                    </td>
                    <td>
                        <button type="button" class="button-link abt-edit-citation">Edit</button>
                        <button type="button" class="button-link abt-delete-citation" style="color: #a00;">Remove</button>
                    </td>
                </tr>
            `;
        },

        /**
         * Update bibliography preview
         */
        updateBibliographyPreview: function(bibliography, style) {
            const $content = $('.abt-bibliography-content');
            
            if (bibliography.length === 0) {
                $content.html('<div class="abt-no-bibliography"><p>No citations to display</p></div>');
                return;
            }
            
            let html = '<ol class="abt-bibliography-list">';
            bibliography.forEach(entry => {
                html += `<li class="abt-bibliography-item">${entry.formatted}</li>`;
            });
            html += '</ol>';
            
            $content.html(html);
            $('.abt-style-indicator').text(`(${style.toUpperCase()})`);
        },

        /**
         * Handle keyboard shortcuts
         */
        handleKeyboardShortcuts: function(e) {
            // Ctrl/Cmd + M: Add citation
            if ((e.ctrlKey || e.metaKey) && e.key === 'm') {
                e.preventDefault();
                this.openCitationModal(e);
            }
            
            // Ctrl/Cmd + F: Add footnote
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                this.openFootnoteModal(e);
            }
            
            // Escape: Close modal
            if (e.key === 'Escape') {
                this.closeModal();
            }
        },

        /**
         * Before save handler
         */
        beforeSave: function() {
            if (this.state.citationsModified || this.state.footnotesModified) {
                // Show saving indicator
                this.showNotice('info', 'Saving citations and footnotes...');
            }
        },

        /**
         * Serialize form data
         */
        serializeFormData: function($form) {
            const formArray = $form.serializeArray();
            const formData = {};
            
            $.each(formArray, function(i, field) {
                formData[field.name] = field.value;
            });
            
            return formData;
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
            $(selector).prop('disabled', true).append(' <span class="abt-loader">...</span>');
        },

        /**
         * Hide loader
         */
        hideLoader: function(selector) {
            $(selector).prop('disabled', false).find('.abt-loader').remove();
        },

        /**
         * Show notice
         */
        showNotice: function(type, message) {
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible abt-notice">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.abt-citations-manager').prepend($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 5000);
            
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
        // Only initialize on academic blog post edit screens
        if ($('#post_type').val() === 'abt_blog' || $('body').hasClass('post-type-abt_blog')) {
            ABT_MetaBox.init();
        }
    });

    // Expose to global scope for debugging
    window.ABT_MetaBox = ABT_MetaBox;

})(jQuery);