/**
 * Citation Tooltips for Academic Blogger's Toolkit
 * 
 * Handles interactive citation tooltips and reference previews
 * 
 * @package Academic_Bloggers_Toolkit
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Citation Tooltips Object
    const ABT_CitationTooltips = {
        
        // Configuration
        config: {
            showDelay: 300,
            hideDelay: 200,
            maxWidth: 400,
            maxHeight: 300,
            fadeSpeed: 200,
            cacheTimeout: 300000, // 5 minutes
            retryAttempts: 3
        },

        // Cache for tooltip content
        cache: new Map(),
        
        // Active tooltips
        activeTooltips: new Map(),
        
        // Loading states
        loadingStates: new Set(),

        /**
         * Initialize citation tooltips
         */
        init: function() {
            this.bindEvents();
            this.setupAccessibility();
            this.preloadCriticalCitations();
        },

        /**
         * Bind tooltip events
         */
        bindEvents: function() {
            const self = this;

            // Hover events for citations
            $(document).on('mouseenter', '.abt-citation', function() {
                const $citation = $(this);
                self.showTooltip($citation);
            });

            $(document).on('mouseleave', '.abt-citation', function() {
                const $citation = $(this);
                self.hideTooltip($citation);
            });

            // Click events for mobile
            $(document).on('click', '.abt-citation', function(e) {
                if (self.isMobile()) {
                    e.preventDefault();
                    const $citation = $(this);
                    self.toggleMobileTooltip($citation);
                }
            });

            // Close tooltips when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.abt-citation, .abt-tooltip').length) {
                    self.hideAllTooltips();
                }
            });

            // Keyboard navigation
            $(document).on('keydown', '.abt-citation', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const $citation = $(this);
                    self.toggleTooltip($citation);
                }
            });

            // Escape key to close tooltips
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.hideAllTooltips();
                }
            });

            // Window resize handler
            $(window).on('resize', function() {
                self.repositionTooltips();
            });

            // Scroll handler to hide tooltips
            $(window).on('scroll', function() {
                if (self.activeTooltips.size > 0) {
                    self.hideAllTooltips();
                }
            });
        },

        /**
         * Show tooltip for citation
         */
        showTooltip: function($citation) {
            const self = this;
            const citationId = $citation.data('citation-id');
            const referenceId = $citation.data('reference-id');
            
            if (!citationId && !referenceId) return;

            // Clear any existing hide timeout
            const hideTimeout = $citation.data('hide-timeout');
            if (hideTimeout) {
                clearTimeout(hideTimeout);
                $citation.removeData('hide-timeout');
            }

            // Show with delay
            const showTimeout = setTimeout(() => {
                self.createTooltip($citation, citationId || referenceId);
            }, this.config.showDelay);

            $citation.data('show-timeout', showTimeout);
        },

        /**
         * Hide tooltip for citation
         */
        hideTooltip: function($citation) {
            const self = this;
            
            // Clear show timeout
            const showTimeout = $citation.data('show-timeout');
            if (showTimeout) {
                clearTimeout(showTimeout);
                $citation.removeData('show-timeout');
            }

            // Hide with delay
            const hideTimeout = setTimeout(() => {
                self.removeTooltip($citation);
            }, this.config.hideDelay);

            $citation.data('hide-timeout', hideTimeout);
        },

        /**
         * Create and position tooltip
         */
        createTooltip: function($citation, referenceId) {
            const self = this;
            const tooltipId = 'abt-tooltip-' + referenceId;
            
            // Don't create if already exists
            if (this.activeTooltips.has(tooltipId)) {
                return;
            }

            // Create tooltip container
            const $tooltip = $('<div>', {
                id: tooltipId,
                class: 'abt-tooltip abt-citation-tooltip',
                role: 'tooltip',
                'aria-hidden': 'false',
                html: '<div class="abt-tooltip-content abt-loading">Loading citation...</div>'
            });

            // Add to DOM
            $('body').append($tooltip);
            this.activeTooltips.set(tooltipId, $tooltip);

            // Position tooltip
            this.positionTooltip($tooltip, $citation);

            // Load content
            this.loadTooltipContent($tooltip, referenceId);

            // Show with animation
            $tooltip.css('opacity', 0).animate({ opacity: 1 }, this.config.fadeSpeed);

            // Track for analytics
            this.trackTooltipShow(referenceId);
        },

        /**
         * Load tooltip content
         */
        loadTooltipContent: function($tooltip, referenceId) {
            const self = this;
            const cacheKey = 'ref-' + referenceId;

            // Check cache first
            if (this.cache.has(cacheKey)) {
                const cached = this.cache.get(cacheKey);
                if (Date.now() - cached.timestamp < this.config.cacheTimeout) {
                    this.displayTooltipContent($tooltip, cached.content);
                    return;
                }
            }

            // Prevent duplicate requests
            if (this.loadingStates.has(referenceId)) {
                return;
            }

            this.loadingStates.add(referenceId);

            // AJAX request for content
            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_get_citation_tooltip',
                    reference_id: referenceId,
                    nonce: abt_frontend_ajax.nonce
                },
                timeout: 10000,
                success: function(response) {
                    if (response.success && response.data) {
                        // Cache the content
                        self.cache.set(cacheKey, {
                            content: response.data,
                            timestamp: Date.now()
                        });

                        self.displayTooltipContent($tooltip, response.data);
                    } else {
                        self.displayTooltipError($tooltip, 'Unable to load citation details');
                    }
                },
                error: function() {
                    self.displayTooltipError($tooltip, 'Error loading citation');
                },
                complete: function() {
                    self.loadingStates.delete(referenceId);
                }
            });
        },

        /**
         * Display tooltip content
         */
        displayTooltipContent: function($tooltip, content) {
            const $content = $tooltip.find('.abt-tooltip-content');
            
            $content.removeClass('abt-loading abt-error')
                   .html(content)
                   .addClass('abt-loaded');

            // Reposition after content load
            this.repositionTooltip($tooltip);

            // Add interaction handlers
            this.setupTooltipInteractions($tooltip);
        },

        /**
         * Display tooltip error
         */
        displayTooltipError: function($tooltip, message) {
            const $content = $tooltip.find('.abt-tooltip-content');
            
            $content.removeClass('abt-loading')
                   .addClass('abt-error')
                   .html('<span class="abt-error-message">' + message + '</span>');
        },

        /**
         * Position tooltip relative to citation
         */
        positionTooltip: function($tooltip, $citation) {
            const citationOffset = $citation.offset();
            const citationWidth = $citation.outerWidth();
            const citationHeight = $citation.outerHeight();
            const tooltipWidth = $tooltip.outerWidth();
            const tooltipHeight = $tooltip.outerHeight();
            const windowWidth = $(window).width();
            const windowHeight = $(window).height();
            const scrollTop = $(window).scrollTop();
            const scrollLeft = $(window).scrollLeft();

            let top, left;

            // Default position: above the citation
            top = citationOffset.top - tooltipHeight - 10;
            left = citationOffset.left + (citationWidth / 2) - (tooltipWidth / 2);

            // Adjust if tooltip goes above viewport
            if (top < scrollTop + 10) {
                top = citationOffset.top + citationHeight + 10;
                $tooltip.addClass('abt-tooltip-below');
            } else {
                $tooltip.removeClass('abt-tooltip-below');
            }

            // Adjust horizontal position if needed
            if (left < scrollLeft + 10) {
                left = scrollLeft + 10;
                $tooltip.addClass('abt-tooltip-left');
            } else if (left + tooltipWidth > windowWidth + scrollLeft - 10) {
                left = windowWidth + scrollLeft - tooltipWidth - 10;
                $tooltip.addClass('abt-tooltip-right');
            } else {
                $tooltip.removeClass('abt-tooltip-left abt-tooltip-right');
            }

            // Apply position
            $tooltip.css({
                top: top,
                left: left,
                maxWidth: this.config.maxWidth,
                maxHeight: this.config.maxHeight
            });
        },

        /**
         * Reposition single tooltip
         */
        repositionTooltip: function($tooltip) {
            // Find associated citation and reposition
            const tooltipId = $tooltip.attr('id');
            const referenceId = tooltipId.replace('abt-tooltip-', '');
            const $citation = $('.abt-citation[data-reference-id="' + referenceId + '"]').first();
            
            if ($citation.length) {
                this.positionTooltip($tooltip, $citation);
            }
        },

        /**
         * Reposition all active tooltips
         */
        repositionTooltips: function() {
            const self = this;
            this.activeTooltips.forEach(function($tooltip) {
                self.repositionTooltip($tooltip);
            });
        },

        /**
         * Remove tooltip
         */
        removeTooltip: function($citation) {
            const referenceId = $citation.data('reference-id') || $citation.data('citation-id');
            const tooltipId = 'abt-tooltip-' + referenceId;
            const $tooltip = this.activeTooltips.get(tooltipId);

            if ($tooltip) {
                $tooltip.animate({ opacity: 0 }, this.config.fadeSpeed, function() {
                    $tooltip.remove();
                });
                
                this.activeTooltips.delete(tooltipId);
            }
        },

        /**
         * Hide all tooltips
         */
        hideAllTooltips: function() {
            const self = this;
            this.activeTooltips.forEach(function($tooltip, tooltipId) {
                $tooltip.animate({ opacity: 0 }, self.config.fadeSpeed, function() {
                    $tooltip.remove();
                });
            });
            this.activeTooltips.clear();
        },

        /**
         * Toggle tooltip (for keyboard/mobile)
         */
        toggleTooltip: function($citation) {
            const referenceId = $citation.data('reference-id') || $citation.data('citation-id');
            const tooltipId = 'abt-tooltip-' + referenceId;
            
            if (this.activeTooltips.has(tooltipId)) {
                this.removeTooltip($citation);
            } else {
                this.createTooltip($citation, referenceId);
            }
        },

        /**
         * Mobile-specific tooltip handling
         */
        toggleMobileTooltip: function($citation) {
            const referenceId = $citation.data('reference-id') || $citation.data('citation-id');
            const tooltipId = 'abt-tooltip-' + referenceId;
            
            // Close other tooltips first
            this.hideAllTooltips();
            
            if (!this.activeTooltips.has(tooltipId)) {
                this.createTooltip($citation, referenceId);
            }
        },

        /**
         * Setup tooltip interactions
         */
        setupTooltipInteractions: function($tooltip) {
            const self = this;

            // Prevent tooltip from hiding when hovering over it
            $tooltip.on('mouseenter', function() {
                const citationId = $(this).attr('id').replace('abt-tooltip-', '');
                const $citation = $('.abt-citation[data-reference-id="' + citationId + '"]');
                
                // Clear hide timeout
                const hideTimeout = $citation.data('hide-timeout');
                if (hideTimeout) {
                    clearTimeout(hideTimeout);
                    $citation.removeData('hide-timeout');
                }
            });

            $tooltip.on('mouseleave', function() {
                const citationId = $(this).attr('id').replace('abt-tooltip-', '');
                const $citation = $('.abt-citation[data-reference-id="' + citationId + '"]');
                self.hideTooltip($citation);
            });

            // Copy citation button
            $tooltip.find('.abt-copy-citation').on('click', function(e) {
                e.preventDefault();
                const citation = $(this).data('citation-text');
                self.copyCitationToClipboard(citation);
            });

            // View full reference button
            $tooltip.find('.abt-view-reference').on('click', function(e) {
                e.preventDefault();
                const referenceId = $(this).data('reference-id');
                self.navigateToReference(referenceId);
            });
        },

        /**
         * Setup accessibility features
         */
        setupAccessibility: function() {
            // Add ARIA attributes to citations
            $('.abt-citation').each(function() {
                const $citation = $(this);
                const referenceId = $citation.data('reference-id') || $citation.data('citation-id');
                
                $citation.attr({
                    'role': 'button',
                    'tabindex': '0',
                    'aria-describedby': 'abt-tooltip-' + referenceId,
                    'aria-label': 'Show citation details'
                });
            });
        },

        /**
         * Preload critical citations
         */
        preloadCriticalCitations: function() {
            // Preload first few citations that are visible
            $('.abt-citation:visible').slice(0, 3).each((index, element) => {
                const $citation = $(element);
                const referenceId = $citation.data('reference-id') || $citation.data('citation-id');
                
                if (referenceId) {
                    // Delay preloading to not block initial page load
                    setTimeout(() => {
                        this.loadTooltipContent($('<div>'), referenceId);
                    }, 2000 + (index * 500));
                }
            });
        },

        /**
         * Copy citation to clipboard
         */
        copyCitationToClipboard: function(citationText) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(citationText).then(() => {
                    this.showNotification('Citation copied to clipboard', 'success');
                }).catch(() => {
                    this.fallbackCopy(citationText);
                });
            } else {
                this.fallbackCopy(citationText);
            }
        },

        /**
         * Fallback copy method
         */
        fallbackCopy: function(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                document.execCommand('copy');
                this.showNotification('Citation copied to clipboard', 'success');
            } catch (err) {
                this.showNotification('Unable to copy citation', 'error');
            }

            document.body.removeChild(textArea);
        },

        /**
         * Navigate to full reference
         */
        navigateToReference: function(referenceId) {
            const $reference = $('#reference-' + referenceId);
            if ($reference.length) {
                $('html, body').animate({
                    scrollTop: $reference.offset().top - 100
                }, 500);
                $reference.addClass('abt-highlight');
                setTimeout(() => $reference.removeClass('abt-highlight'), 2000);
            }
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            // Use existing notification system or create simple one
            if (window.ABT_Frontend && window.ABT_Frontend.showNotification) {
                window.ABT_Frontend.showNotification(message, type);
            } else {
                // Fallback notification
                const $notification = $('<div class="abt-notification abt-notification-' + type + '">' + message + '</div>');
                $('body').append($notification);
                setTimeout(() => $notification.fadeOut(() => $notification.remove()), 3000);
            }
        },

        /**
         * Track tooltip show event
         */
        trackTooltipShow: function(referenceId) {
            // Send analytics event
            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_track_tooltip_view',
                    reference_id: referenceId,
                    nonce: abt_frontend_ajax.nonce
                }
            });
        },

        /**
         * Check if device is mobile
         */
        isMobile: function() {
            return window.innerWidth <= 768 || /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
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
        ABT_CitationTooltips.init();
    });

    // Export to global scope
    window.ABT_CitationTooltips = ABT_CitationTooltips;

})(jQuery);