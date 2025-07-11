/**
 * Reading Progress for Academic Blogger's Toolkit
 * 
 * Tracks reading progress, time estimates, and engagement analytics
 * 
 * @package Academic_Bloggers_Toolkit
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Reading Progress Object
    const ABT_ReadingProgress = {
        
        // Configuration
        config: {
            updateInterval: 250,
            saveInterval: 5000,
            wordsPerMinute: 200,
            scrollThreshold: 5,
            idleThreshold: 30000, // 30 seconds
            visibilityThreshold: 0.5
        },

        // State tracking
        state: {
            isActive: false,
            startTime: null,
            lastScrollTime: null,
            totalReadingTime: 0,
            currentProgress: 0,
            maxProgress: 0,
            isVisible: true,
            isScrolling: false,
            wordCount: 0,
            estimatedReadingTime: 0
        },

        // Elements
        elements: {
            $progressBar: null,
            $progressIndicator: null,
            $timeEstimate: null,
            $article: null
        },

        // Analytics data
        analytics: {
            startTime: null,
            endTime: null,
            totalTime: 0,
            activeReadingTime: 0,
            scrollEvents: 0,
            maxScrollDepth: 0,
            bounceRate: 0,
            engagementScore: 0
        },

        /**
         * Initialize reading progress tracker
         */
        init: function() {
            this.findElements();
            this.calculateReadingTime();
            this.bindEvents();
            this.setupProgressBar();
            this.startTracking();
            this.setupVisibilityTracking();
        },

        /**
         * Find DOM elements
         */
        findElements: function() {
            this.elements.$progressBar = $('.abt-reading-progress-bar');
            this.elements.$progressIndicator = $('.abt-progress-indicator');
            this.elements.$timeEstimate = $('.abt-reading-time');
            this.elements.$article = $('.abt-article-content, .entry-content, .post-content').first();

            // Create progress bar if it doesn't exist
            if (!this.elements.$progressBar.length) {
                this.createProgressBar();
            }
        },

        /**
         * Create progress bar
         */
        createProgressBar: function() {
            const $progressContainer = $('<div class="abt-reading-progress" role="progressbar" aria-label="Reading progress"></div>');
            const $progressBar = $('<div class="abt-reading-progress-bar"></div>');
            
            $progressContainer.append($progressBar);
            $('body').prepend($progressContainer);
            
            this.elements.$progressBar = $progressBar;
        },

        /**
         * Setup progress bar
         */
        setupProgressBar: function() {
            if (!this.elements.$progressBar.length) return;

            this.elements.$progressBar.css({
                width: '0%',
                transition: 'width 0.2s ease'
            });

            // Add ARIA attributes
            this.elements.$progressBar.closest('.abt-reading-progress').attr({
                'aria-valuemin': '0',
                'aria-valuemax': '100',
                'aria-valuenow': '0'
            });
        },

        /**
         * Calculate reading time
         */
        calculateReadingTime: function() {
            if (!this.elements.$article.length) return;

            const text = this.elements.$article.text();
            this.state.wordCount = text.split(/\s+/).filter(word => word.length > 0).length;
            this.state.estimatedReadingTime = Math.ceil(this.state.wordCount / this.config.wordsPerMinute);

            // Update reading time display
            if (this.elements.$timeEstimate.length) {
                this.elements.$timeEstimate.text(this.state.estimatedReadingTime + ' min read');
            }
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;

            // Scroll events
            $(window).on('scroll', this.throttle(function() {
                self.handleScroll();
            }, this.config.updateInterval));

            // Visibility change
            $(document).on('visibilitychange', function() {
                self.handleVisibilityChange();
            });

            // Page unload
            $(window).on('beforeunload', function() {
                self.saveProgress();
                self.trackSession();
            });

            // Mouse movement and clicks (activity tracking)
            $(document).on('mousemove click keydown', function() {
                self.updateActivity();
            });

            // Focus and blur events
            $(window).on('focus', function() {
                self.handleFocus();
            }).on('blur', function() {
                self.handleBlur();
            });

            // Scroll end detection
            let scrollTimer = null;
            $(window).on('scroll', function() {
                self.state.isScrolling = true;
                clearTimeout(scrollTimer);
                scrollTimer = setTimeout(() => {
                    self.state.isScrolling = false;
                }, 150);
            });
        },

        /**
         * Handle scroll events
         */
        handleScroll: function() {
            if (!this.elements.$article.length) return;

            const windowTop = $(window).scrollTop();
            const windowHeight = $(window).height();
            const articleTop = this.elements.$article.offset().top;
            const articleHeight = this.elements.$article.outerHeight();

            // Calculate reading progress
            const progress = this.calculateScrollProgress(windowTop, windowHeight, articleTop, articleHeight);
            
            // Update progress
            this.updateProgress(progress);

            // Track scroll depth
            this.trackScrollDepth(progress);

            // Update activity
            this.updateActivity();

            // Save progress periodically
            this.saveProgressPeriodically();
        },

        /**
         * Calculate scroll progress
         */
        calculateScrollProgress: function(windowTop, windowHeight, articleTop, articleHeight) {
            // Reading starts when article comes into view
            const readingStart = Math.max(0, articleTop - windowHeight * 0.2);
            const readingEnd = articleTop + articleHeight;
            const currentPosition = windowTop + windowHeight * 0.8;

            if (currentPosition <= readingStart) {
                return 0;
            } else if (currentPosition >= readingEnd) {
                return 100;
            } else {
                const readableHeight = readingEnd - readingStart;
                const readPosition = currentPosition - readingStart;
                return Math.min(100, Math.max(0, (readPosition / readableHeight) * 100));
            }
        },

        /**
         * Update progress
         */
        updateProgress: function(progress) {
            this.state.currentProgress = progress;
            
            // Track maximum progress reached
            if (progress > this.state.maxProgress) {
                this.state.maxProgress = progress;
            }

            // Update progress bar
            if (this.elements.$progressBar.length) {
                this.elements.$progressBar.css('width', progress + '%');
                
                // Update ARIA attributes
                this.elements.$progressBar.closest('.abt-reading-progress').attr('aria-valuenow', Math.round(progress));
            }

            // Update progress indicator
            if (this.elements.$progressIndicator.length) {
                this.elements.$progressIndicator.text(Math.round(progress) + '%');
            }

            // Calculate remaining reading time
            this.updateRemainingTime(progress);
        },

        /**
         * Update remaining reading time
         */
        updateRemainingTime: function(progress) {
            if (!this.elements.$timeEstimate.length) return;

            const remainingProgress = Math.max(0, 100 - progress);
            const remainingTime = Math.ceil((remainingProgress / 100) * this.state.estimatedReadingTime);
            
            let timeText;
            if (progress < 5) {
                timeText = this.state.estimatedReadingTime + ' min read';
            } else if (remainingTime <= 1) {
                timeText = 'Less than 1 min left';
            } else {
                timeText = remainingTime + ' min left';
            }

            this.elements.$timeEstimate.text(timeText);
        },

        /**
         * Track scroll depth
         */
        trackScrollDepth: function(progress) {
            this.analytics.scrollEvents++;
            
            if (progress > this.analytics.maxScrollDepth) {
                this.analytics.maxScrollDepth = progress;
            }
        },

        /**
         * Update activity timestamp
         */
        updateActivity: function() {
            this.state.lastScrollTime = Date.now();
            
            if (!this.state.isActive) {
                this.state.isActive = true;
                this.resumeReading();
            }
        },

        /**
         * Start tracking
         */
        startTracking: function() {
            this.state.startTime = Date.now();
            this.analytics.startTime = Date.now();
            this.state.isActive = true;
            
            // Start activity monitoring
            this.startActivityMonitoring();
            
            // Track page view
            this.trackPageView();
        },

        /**
         * Start activity monitoring
         */
        startActivityMonitoring: function() {
            const self = this;
            
            setInterval(() => {
                if (self.state.isActive && self.state.isVisible) {
                    // Check if user is idle
                    const now = Date.now();
                    const timeSinceActivity = now - (self.state.lastScrollTime || self.state.startTime);
                    
                    if (timeSinceActivity > self.config.idleThreshold) {
                        self.pauseReading();
                    } else {
                        self.analytics.activeReadingTime += self.config.updateInterval;
                    }
                }
            }, this.config.updateInterval);
        },

        /**
         * Pause reading
         */
        pauseReading: function() {
            this.state.isActive = false;
        },

        /**
         * Resume reading
         */
        resumeReading: function() {
            this.state.isActive = true;
            this.state.lastScrollTime = Date.now();
        },

        /**
         * Handle visibility change
         */
        handleVisibilityChange: function() {
            if (document.hidden) {
                this.state.isVisible = false;
                this.pauseReading();
            } else {
                this.state.isVisible = true;
                this.updateActivity();
            }
        },

        /**
         * Handle focus
         */
        handleFocus: function() {
            this.state.isVisible = true;
            this.updateActivity();
        },

        /**
         * Handle blur
         */
        handleBlur: function() {
            this.state.isVisible = false;
            this.pauseReading();
        },

        /**
         * Setup visibility tracking
         */
        setupVisibilityTracking: function() {
            if (!('IntersectionObserver' in window)) return;

            const self = this;
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.target === self.elements.$article[0]) {
                        const visibility = entry.intersectionRatio;
                        
                        if (visibility > self.config.visibilityThreshold) {
                            self.updateActivity();
                        } else {
                            self.pauseReading();
                        }
                    }
                });
            }, {
                threshold: [0, 0.25, 0.5, 0.75, 1.0]
            });

            if (this.elements.$article.length) {
                observer.observe(this.elements.$article[0]);
            }
        },

        /**
         * Save progress periodically
         */
        saveProgressPeriodically: function() {
            const now = Date.now();
            
            if (!this.lastSaveTime || now - this.lastSaveTime > this.config.saveInterval) {
                this.saveProgress();
                this.lastSaveTime = now;
            }
        },

        /**
         * Save progress
         */
        saveProgress: function() {
            const postId = $('body').data('post-id');
            if (!postId) return;

            const progressData = {
                post_id: postId,
                progress: this.state.maxProgress,
                reading_time: this.analytics.activeReadingTime,
                word_count: this.state.wordCount,
                timestamp: Date.now()
            };

            // Save to localStorage
            localStorage.setItem('abt_reading_progress_' + postId, JSON.stringify(progressData));

            // Send to server
            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_save_reading_progress',
                    progress_data: progressData,
                    nonce: abt_frontend_ajax.nonce
                }
            });
        },

        /**
         * Load saved progress
         */
        loadProgress: function() {
            const postId = $('body').data('post-id');
            if (!postId) return null;

            const saved = localStorage.getItem('abt_reading_progress_' + postId);
            if (saved) {
                try {
                    return JSON.parse(saved);
                } catch (e) {
                    return null;
                }
            }
            return null;
        },

        /**
         * Track page view
         */
        trackPageView: function() {
            const postId = $('body').data('post-id');
            if (!postId) return;

            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_track_page_view',
                    post_id: postId,
                    reading_session_start: this.analytics.startTime,
                    nonce: abt_frontend_ajax.nonce
                }
            });
        },

        /**
         * Track session
         */
        trackSession: function() {
            const postId = $('body').data('post-id');
            if (!postId) return;

            this.analytics.endTime = Date.now();
            this.analytics.totalTime = this.analytics.endTime - this.analytics.startTime;
            this.analytics.engagementScore = this.calculateEngagementScore();

            $.ajax({
                url: abt_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abt_track_reading_session',
                    post_id: postId,
                    analytics: this.analytics,
                    progress: this.state.maxProgress,
                    nonce: abt_frontend_ajax.nonce
                }
            });
        },

        /**
         * Calculate engagement score
         */
        calculateEngagementScore: function() {
            let score = 0;
            
            // Progress score (0-40 points)
            score += Math.min(40, this.state.maxProgress * 0.4);
            
            // Time score (0-30 points)
            const timeRatio = this.analytics.activeReadingTime / (this.state.estimatedReadingTime * 60 * 1000);
            score += Math.min(30, timeRatio * 30);
            
            // Interaction score (0-20 points)
            const interactionRatio = Math.min(1, this.analytics.scrollEvents / 10);
            score += interactionRatio * 20;
            
            // Completion bonus (0-10 points)
            if (this.state.maxProgress >= 90) {
                score += 10;
            }
            
            return Math.round(score);
        },

        /**
         * Get reading statistics
         */
        getStatistics: function() {
            return {
                state: this.state,
                analytics: this.analytics,
                estimatedTime: this.state.estimatedReadingTime,
                actualTime: this.analytics.activeReadingTime / 60000, // Convert to minutes
                efficiency: (this.analytics.activeReadingTime / 60000) / this.state.estimatedReadingTime,
                engagement: this.analytics.engagementScore
            };
        },

        /**
         * Show reading summary
         */
        showReadingSummary: function() {
            const stats = this.getStatistics();
            const summary = `
                <div class="abt-reading-summary">
                    <h3>Reading Summary</h3>
                    <div class="abt-summary-stats">
                        <div class="abt-stat">
                            <label>Progress:</label>
                            <span>${Math.round(stats.state.maxProgress)}%</span>
                        </div>
                        <div class="abt-stat">
                            <label>Reading Time:</label>
                            <span>${Math.round(stats.actualTime)} min</span>
                        </div>
                        <div class="abt-stat">
                            <label>Engagement Score:</label>
                            <span>${stats.engagement}/100</span>
                        </div>
                    </div>
                </div>
            `;
            
            // Show in modal or notification
            this.showNotification(summary, 'info');
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            if (window.ABT_Frontend && window.ABT_Frontend.showNotification) {
                window.ABT_Frontend.showNotification(message, type);
            }
        },

        /**
         * Throttle function
         */
        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        /**
         * Reset progress
         */
        reset: function() {
            this.state.currentProgress = 0;
            this.state.maxProgress = 0;
            this.state.startTime = Date.now();
            this.analytics = {
                startTime: Date.now(),
                endTime: null,
                totalTime: 0,
                activeReadingTime: 0,
                scrollEvents: 0,
                maxScrollDepth: 0,
                bounceRate: 0,
                engagementScore: 0
            };
            
            if (this.elements.$progressBar.length) {
                this.elements.$progressBar.css('width', '0%');
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize on single academic blog posts
        if ($('body').hasClass('single-abt_blog') || $('.abt-article-content').length) {
            ABT_ReadingProgress.init();
            
            // Load any saved progress
            const savedProgress = ABT_ReadingProgress.loadProgress();
            if (savedProgress && savedProgress.progress > 10) {
                // Optionally show option to resume reading
                const resumeMessage = `You've previously read ${Math.round(savedProgress.progress)}% of this article. Would you like to continue from where you left off?`;
                if (confirm(resumeMessage)) {
                    // Scroll to saved position
                    const targetProgress = savedProgress.progress / 100;
                    const $article = ABT_ReadingProgress.elements.$article;
                    if ($article.length) {
                        const scrollTarget = $article.offset().top + ($article.outerHeight() * targetProgress);
                        $('html, body').animate({ scrollTop: scrollTarget }, 1000);
                    }
                }
            }
        }
    });

    // Export to global scope
    window.ABT_ReadingProgress = ABT_ReadingProgress;

})(jQuery);