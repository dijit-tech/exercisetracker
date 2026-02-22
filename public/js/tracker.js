/**
 * Goal Tracker Clickstream Analytics (v3)
 * Lightweight, privacy-first analytics library.
 */
(function(window, document) {
    'use strict';

    const CONFIG = {
        endpoint: '/api/ingest.php',
        bufferTimeout: 2000, // Send events every 2 seconds if not immediate
    };

    let eventBuffer = [];
    let bufferTimer = null;

    // Get user preferences from global window object or default
    const getPrefs = () => window.currentUserPrefs || { analytics_enabled: true };

    const Tracker = {
        init: function() {
            console.log('Goals Tracker Initialized');
            this.attachClickListeners();
            this.trackPageView();
            
            // Handle page visibility changes (flush on hide)
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    this.flushBuffer();
                }
            });
        },

        attachClickListeners: function() {
            document.addEventListener('click', (e) => {
                const target = e.target.closest('[data-track-event]');
                if (target) {
                    const eventName = target.getAttribute('data-track-event');
                    let properties = {};
                    
                    // Parse optional properties
                    try {
                        const propsAttr = target.getAttribute('data-track-props');
                        if (propsAttr) {
                            properties = JSON.parse(propsAttr);
                        }
                    } catch (err) {
                        console.warn('Invalid track props JSON', err);
                    }

                    this.track(eventName, properties);
                }
            });
        },

        track: function(eventName, properties = {}) {
            const prefs = getPrefs();
            
            // Basic event payload
            const event = {
                event_name: eventName,
                client_timestamp: new Date().toISOString(),
                client_timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                url: window.location.href,
                screen_width: window.innerWidth,
                screen_height: window.innerHeight,
                referrer: document.referrer,
                properties: properties
            };

            // If privacy is enabled (user opted out), strip sensitive data client-side too
            if (prefs.analytics_enabled === false) {
                delete event.url;
                delete event.referrer;
                delete event.screen_width;
                delete event.screen_height;
                event.privacy_mode = true;
            }

            this.send(event);
        },

        trackPageView: function() {
            this.track('page_view', {
                title: document.title,
                path: window.location.pathname
            });
        },

        send: function(eventData) {
            // Use sendBeacon for reliability during unload, fetch for normal use
            if (navigator.sendBeacon) {
                const blob = new Blob([JSON.stringify(eventData)], { type: 'application/json' });
                navigator.sendBeacon(CONFIG.endpoint, blob);
            } else {
                // Fallback for older browsers
                fetch(CONFIG.endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(eventData),
                    keepalive: true
                }).catch(console.error);
            }
        },

        flushBuffer: function() {
            // Future optimization: Batch events if needed
            // Currently sending one by one as they happen for real-time responsiveness
        }
    };

    // Expose to window
    window.GoalTracker = Tracker;

    // Auto-start
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => Tracker.init());
    } else {
        Tracker.init();
    }

})(window, document);
