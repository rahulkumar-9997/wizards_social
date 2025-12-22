// backend/assets/js/pages/instagram/ajax-interceptor.js
class AjaxProgressInterceptor {
    constructor() {
        this.totalRequests = 0;
        this.completedRequests = 0;
        this.requestMap = new Map(); // Maps request URLs to component names
        this.initializeRequestMap();
        this.setupInterceptor();
    }

    initializeRequestMap() {
        // Map your API endpoints to component names
        this.requestMap.set(window.insta_reach_pdf_url, 'reach');
        this.requestMap.set(window.insta_view_pdf_url, 'view');
        this.requestMap.set(window.insta_profile_reach_graphs_pdf_url, 'reachGraph');
        this.requestMap.set(window.insta_profile_follow_unfollow_pdf_url, 'followers');
        this.requestMap.set(window.insta_view_graphs_media_type_pdf_url, 'viewGraph');
        this.requestMap.set(window.insta_post_reel_pdf_url, 'postReels');
        this.requestMap.set(window.insta_total_interactions_pdf_url, 'totalInteractions');
        this.requestMap.set(window.insta_total_interactions_like_comment_pdf_url, 'interactionLikeComment');
        this.requestMap.set(window.insta_total_interactions_media_type_pdf_url, 'interactionMediaType');
        this.requestMap.set(window.insta_profile_visit_pdf_url, 'profileVisit');
        this.requestMap.set(window.insta_engagement_pdf_url, 'engagement');
        this.requestMap.set(window.insta_city_audience_pdf_url, 'cityAudience');
        this.requestMap.set(window.insta_audience_by_age_group_pdf_url, 'ageGroup');
        this.requestMap.set(window.insta_post_data_pdf_url, 'postData');
    }

    setupInterceptor() {
        // Store original $.ajax
        const originalAjax = $.ajax;
        
        // Override $.ajax
        const self = this;
        $.ajax = function(options) {
            // Check if this is one of our Instagram API calls
            const url = options.url || '';
            const componentName = self.requestMap.get(url);
            
            if (componentName) {
                // Increment total requests
                self.totalRequests++;
                
                // Store the original success and error callbacks
                const originalSuccess = options.success;
                const originalError = options.error;
                
                // Wrap success callback
                options.success = function(data, textStatus, jqXHR) {
                    // Call original success if it exists
                    if (originalSuccess) {
                        originalSuccess.call(this, data, textStatus, jqXHR);
                    }
                    
                    // Update progress
                    self.completedRequests++;
                    if (window.globalLoader) {
                        window.globalLoader.updateProgress(componentName);
                    }
                };
                
                // Wrap error callback
                options.error = function(jqXHR, textStatus, errorThrown) {
                    // Call original error if it exists
                    if (originalError) {
                        originalError.call(this, jqXHR, textStatus, errorThrown);
                    }
                    
                    // Still update progress (even on error)
                    self.completedRequests++;
                    if (window.globalLoader) {
                        window.globalLoader.updateProgress(componentName);
                    }
                };
            }
            
            // Call original $.ajax
            return originalAjax.call(this, options);
        };
    }

    reset() {
        this.totalRequests = 0;
        this.completedRequests = 0;
    }
}

// Initialize interceptor
window.ajaxInterceptor = new AjaxProgressInterceptor();