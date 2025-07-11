/**
 * Footnote Handler for Academic Blogger's Toolkit
 * 
 * Manages footnote interactions, navigation, and accessibility
 * 
 * @package Academic_Bloggers_Toolkit
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Footnote Handler Object
    const ABT_FootnoteHandler = {
        
        // Configuration
        config: {
            scrollDuration: 500,
            highlightDuration: 2000,
            scrollOffset: 100,
            returnScrollOffset: 50
        },

        // State tracking
        footnoteMap: new Map(),
        highlightTimeouts: new Map(),

        /**
         * Initialize footnote handler
         */
        init: function() {
            this.buildFootnoteMap();
            this.bindEvents();
            this.setupAccessibility();
            this.addReturnLinks();
            this.setupKeyboardNavigation();
        },

        /**
         * Build mapping between footnote links and targets
         */
        buildFootnoteMap: function() {
            const self = this;
            
            $('.abt-footnote').each(function() {
                const $footnote = $(this);
                const href = $footnote.attr('href');
                const $target = $(href);
                
                if ($target.length) {
                    const footnoteId = $footnote.attr('id') || 'fn-ref-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                    
                    // Ensure footnote has ID
                    if (!$footnote.attr('id')) {
                        $footnote.attr('id', footnoteId);
                    }
                    
                    self.footnoteMap.set(footnoteId, {
                        link: $footnote,
                        target: $target,
                        href: href
                    });
                }
            });
        },

        /**
         * Bind footnote events
         */
        bindEvents: function() {
            const self = this;

            // Footnote link clicks
            $(document).on('click', '.abt-footnote', function(e) {
                e.preventDefault();
                const $footnote = $(this);
                self.navigateToFootnote($footnote);
            });

            // Return link clicks
            $(document).on('click', '.abt-footnote-return', function(e) {
                e.preventDefault();
                const $returnLink = $(this);
                self.returnFromFootnote($returnLink);
            });

            // Handle hash changes for direct footnote links
            $(window).on('hashchange', function() {
                self.handleHashNavigation();
            });

            // Initial hash check
            if (window.location.hash) {
                this.handleHashNavigation();
            }
        },

        /**
         * Navigate to footnote
         */
        navigateToFootnote: function($footnote) {
            const href = $footnote.attr('href');
            const $target = $(href);
            
            if (!$target.length) return;

            // Clear any existing highlights
            this.clearHighlights();

            // Scroll to footnote
            this.scrollToElement($target, () => {
                // Highlight the footnote
                this.highlightElement($target);
                
                // Update URL hash
                this.updateHash(href);
                
                // Track interaction
                this.trackFootnoteView($footnote.attr('id'), href);
                
                // Focus for accessibility
                $target.attr('tabindex', '-1').focus();
            });
        },

        /**
         * Return from footnote to source
         */
        returnFromFootnote: function($returnLink) {
            const targetId = $returnLink.attr('href');
            const $target = $(targetId);
            
            if (!$target.length) return;

            // Clear highlights
            this.clearHighlights();

            // Scroll back to source
            this.scrollToElement($target, () => {
                // Highlight the source citation
                this.highlightElement($target);
                
                // Clear hash
                this.updateHash('');
                
                // Focus for accessibility
                $target.focus();
            }, this.config.returnScrollOffset);
        },

        /**
         * Handle hash navigation (direct links to footnotes)
         */
        handleHashNavigation: function() {
            const hash = window.location.hash;
            if (!hash) return;

            const $target = $(hash);
            if ($target.length && $target.hasClass('abt-footnote-item')) {
                // Delay to ensure page is loaded
                setTimeout(() => {
                    this.scrollToElement($target, () => {
                        this.highlightElement($target);
                        $target.attr('tabindex', '-1').focus();
                    });
                }, 100);
            }
        },

        /**
         * Scroll to element with animation
         */
        scrollToElement: function($element, callback, offset) {
            offset = offset || this.config.scrollOffset;
            
            $('html, body').animate({
                scrollTop: $element.offset().top - offset
            }, this.config.scrollDuration, 'swing', callback);
        },

        /**
         * Highlight element temporarily
         */
        highlightElement: function($element) {
            const elementId = $element.attr('id');
            
            // Clear existing timeout
            if (this.highlightTimeouts.has(elementId)) {
                clearTimeout(this.highlightTimeouts.get(elementId));
            }

            // Add highlight class
            $element.addClass('abt-footnote-highlight');

            // Remove highlight after duration
            const timeout = setTimeout(() => {
                $element.removeClass('abt-footnote-highlight');
                this.highlightTimeouts.delete(elementId);
            }, this.config.highlightDuration);

            this.highlightTimeouts.set(elementId, timeout);
        },

        /**
         * Clear all highlights
         */
        clearHighlights: function() {
            $('.abt-footnote-highlight').removeClass('abt-footnote-highlight');
            this.highlightTimeouts.forEach(timeout => clearTimeout(timeout));
            this.highlightTimeouts.clear();
        },

        /**
         * Update URL hash
         */
        updateHash: function(hash) {
            if (history.replaceState) {
                history.replaceState(null, null, hash || window.location.pathname);
            }
        },

        /**
         * Add return links to footnotes
         */
        addReturnLinks: function() {
            $('.abt-footnote-item').each(function() {
                const $footnote = $(this);
                const footnoteId = $footnote.attr('id');
                
                // Find corresponding footnote link
                const $footnoteLink = $('.abt-footnote[href="#' + footnoteId + '"]');
                
                if ($footnoteLink.length && !$footnote.find('.abt-footnote-return').length) {
                    const linkId = $footnoteLink.attr('id');
                    
                    const $returnLink = $('<a>', {
                        href: '#' + linkId,
                        class: 'abt-footnote-return',
                        'aria-label': 'Return to footnote reference in text',
                        title: 'Return to text',
                        html: ' ↩'
                    });
                    
                    $footnote.append($returnLink);
                }
            });
        },

        /**
         * Setup accessibility features
         */
        setupAccessibility: function() {
            // Add ARIA attributes to footnote links
            $('.abt-footnote').each(function() {
                const $footnote = $(this);
                const href = $footnote.attr('href');
                
                $footnote.attr({
                    'role': 'doc-noteref',
                    'aria-describedby': href.substring(1),
                    'aria-label': 'Footnote ' + $footnote.text()
                });
            });

            // Add ARIA attributes to footnote content
            $('.abt-footnote-item').each(function() {
                const $item = $(this);
                $item.attr({
                    'role': 'doc-footnote',
                    'aria-label': 'Footnote content'
                });
            });

            // Add skip link for screen readers
            this.addSkipToFootnotes();
        },

        /**
         * Add skip link to footnotes section
         */
        addSkipToFootnotes: function() {
            const $footnotesSection = $('.abt-footnotes');
            if ($footnotesSection.length && !$('#abt-skip-to-footnotes').length) {
                const $skipLink = $('<a>', {
                    id: 'abt-skip-to-footnotes',
                    href: '#' + $footnotesSection.attr('id'),
                    class: 'abt-skip-link screen-reader-text',
                    text: 'Skip to footnotes'
                });

                $skipLink.on('focus', function() {
                    $(this).removeClass('screen-reader-text');
                }).on('blur', function() {
                    $(this).addClass('screen-reader-text');
                });

                $('body').prepend($skipLink);
            }
        },

        /**
         * Setup keyboard navigation
         */
        setupKeyboardNavigation: function() {
            const self = this;

            // Arrow key navigation between footnotes
            $(document).on('keydown', '.abt-footnote', function(e) {
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    self.navigateFootnotes($(this), e.key === 'ArrowDown');
                }
            });

            // Enter/Space to activate footnote
            $(document).on('keydown', '.abt-footnote', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });

            // Escape to return from footnote
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    const $focusedFootnote = $('.abt-footnote-item:focus');
                    if ($focusedFootnote.length) {
                        const $returnLink = $focusedFootnote.find('.abt-footnote-return');
                        if ($returnLink.length) {
                            $returnLink.trigger('click');
                        }
                    }
                }
            });
        },

        /**
         * Navigate between footnotes with keyboard
         */
        navigateFootnotes: function($currentFootnote, isDown) {
            const $footnotes = $('.abt-footnote');
            const currentIndex = $footnotes.index($currentFootnote);
            let targetIndex;

            if (isDown) {
                targetIndex = currentIndex + 1;
                if (targetIndex >= $footnotes.length) {
                    targetIndex = 0; // Wrap to first
                }
            } else {
                targetIndex = currentIndex - 1;
                if (targetIndex < 0) {
                    targetIndex = $footnotes.length - 1; // Wrap to last
                }
            }

            $footnotes.eq(targetIndex).focus();
        },

        /**
         * Generate footnote popup for mobile
         */
        showFootnotePopup: function($footnote) {
            const href = $footnote.attr('href');
            const $target = $(href);
            
            if (!$target.length) return;

            // Create popup overlay
            const $overlay = $('<div>', {
                class: 'abt-footnote-popup-overlay',
                html: '<div class="abt-footnote-popup">' +
                      '<div class="abt-footnote-popup-header">' +
                      '<h3>Footnote</h3>' +
                      '<button class="abt-footnote-popup-close" aria-label="Close footnote">&times;</button>' +
                      '</div>' +
                      '<div class="abt-footnote-popup-content">' +
                      $target.html() +
                      '</div>' +
                      '</div>'
            });

            // Add to DOM
            $('body').append($overlay);

            // Bind close events
            $overlay.find('.abt-footnote-popup-close').on('click', () => {
                this.hideFootnotePopup($overlay);
            });

            $overlay.on('click', (e) => {
                if (e.target === $overlay[0]) {
                    this.hideFootnotePopup($overlay);
                }
            });

            // Prevent body scroll
            $('body').addClass('abt-footnote-popup-open');

            // Focus management
            $overlay.find('.abt-footnote-popup-close').focus();

            // Track popup view
            this.trackFootnotePopup($footnote.attr('id'));
        },

        /**
         * Hide footnote popup
         */
        hideFootnotePopup: function($overlay) {
            $overlay.fadeOut(200, () => {
                $overlay.remove();
                $('body').removeClass('abt-footnote-popup-open');
            });
        },

        /**
         * Auto-number footnotes
         */
        autoNumberFootnotes: function() {
            $('.abt-footnote').each(function(index) {
                const $footnote = $(this);
                if (!$footnote.text().trim()) {
                    $footnote.text(index + 1);
                }
            });
        },

        /**
         * Track footnote view
         */
        trackFootnoteView: function(footnoteId, target) {
            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_track_footnote_view',
                    footnote_id: footnoteId,
                    target: target,
                    nonce: abt_frontend_ajax.nonce
                }
            });
        },

        /**
         * Track footnote popup view
         */
        trackFootnotePopup: function(footnoteId) {
            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_track_footnote_popup',
                    footnote_id: footnoteId,
                    nonce: abt_frontend_ajax.nonce
                }
            });
        },

        /**
         * Validate footnote structure
         */
        validateFootnotes: function() {
            const issues = [];

            // Check for footnotes without targets
            $('.abt-footnote').each(function() {
                const $footnote = $(this);
                const href = $footnote.attr('href');
                if (!$(href).length) {
                    issues.push('Footnote link missing target: ' + href);
                }
            });

            // Check for footnote targets without links
            $('.abt-footnote-item').each(function() {
                const $item = $(this);
                const id = $item.attr('id');
                if (!$('.abt-footnote[href="#' + id + '"]').length) {
                    issues.push('Footnote target without link: #' + id);
                }
            });

            return issues;
        },

        /**
         * Export footnotes as text
         */
        exportFootnotes: function() {
            const footnotes = [];
            
            $('.abt-footnote-item').each(function() {
                const $item = $(this);
                footnotes.push({
                    id: $item.attr('id'),
                    content: $item.text().trim()
                });
            });

            return footnotes;
        },

        /**
         * Get footnote statistics
         */
        getStatistics: function() {
            return {
                totalFootnotes: $('.abt-footnote').length,
                totalFootnoteItems: $('.abt-footnote-item').length,
                mapped: this.footnoteMap.size,
                issues: this.validateFootnotes()
            };
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ABT_FootnoteHandler.init();
    });

    // Export to global scope
    window.ABT_FootnoteHandler = ABT_FootnoteHandler;

})(jQuery);