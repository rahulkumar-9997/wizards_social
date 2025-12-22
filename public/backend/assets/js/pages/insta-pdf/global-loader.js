// backend/assets/js/pages/instagram/global-loader.js - UPDATED
class GlobalLoader {
    constructor() {
        this.totalComponents = 14;
        this.loadedComponents = 0;
        this.componentStatus = {};
        this.initializeComponents();
    }

    initializeComponents() {
        // Define all components
        const components = [
            'reach', 'view', 'reachGraph', 'followers', 'viewGraph',
            'postReels', 'totalInteractions', 'interactionLikeComment',
            'interactionMediaType', 'profileVisit', 'engagement',
            'cityAudience', 'ageGroup', 'postData'
        ];
        
        components.forEach(component => {
            this.componentStatus[component] = 'pending';
        });
    }

    show() {
        document.getElementById('fullPageLoader').style.display = 'flex';
        document.getElementById('mainContainer').style.display = 'none';
        document.getElementById('mainContent').classList.remove('loaded');
        
        // Reset counts
        this.loadedComponents = 0;
        Object.keys(this.componentStatus).forEach(key => {
            this.componentStatus[key] = 'pending';
        });
        
        // Reset UI
        document.getElementById('loaderProgressBar').style.width = '0%';
        document.getElementById('loadedCount').textContent = '0';
        document.getElementById('percentage').textContent = '0%';
        document.getElementById('currentLoading').textContent = 'Initializing components...';
    }

    hide() {
        setTimeout(() => {
            document.getElementById('fullPageLoader').style.display = 'none';
            document.getElementById('mainContainer').style.display = 'block';
            document.getElementById('mainContent').classList.add('loaded');
        }, 500);
    }

    updateProgress(componentName) {
        if (this.componentStatus[componentName] === 'pending') {
            this.componentStatus[componentName] = 'loaded';
            this.loadedComponents++;
            
            const percentage = Math.round((this.loadedComponents / this.totalComponents) * 100);
            
            // Update UI
            document.getElementById('loaderProgressBar').style.width = percentage + '%';
            document.getElementById('loadedCount').textContent = this.loadedComponents;
            document.getElementById('percentage').textContent = percentage + '%';
            
            // Update current loading text
            const pending = Object.keys(this.componentStatus).filter(c => this.componentStatus[c] === 'pending');
            if (pending.length > 0) {
                const currentComp = this.getComponentDisplayName(pending[0]);
                document.getElementById('currentLoading').textContent = `Loading: ${currentComp}...`;
            }
            
            // Check if all loaded
            if (this.loadedComponents === this.totalComponents) {
                this.hide();
            }
        }
    }

    getComponentDisplayName(key) {
        const names = {
            'reach': 'Reach Analysis',
            'view': 'Views Analysis',
            'reachGraph': 'Reach Graphs',
            'followers': 'Followers Data',
            'viewGraph': 'Views Graphs',
            'postReels': 'Posts & Reels',
            'totalInteractions': 'Total Interactions',
            'interactionLikeComment': 'Likes & Comments',
            'interactionMediaType': 'Media Type Analysis',
            'profileVisit': 'Profile Visits',
            'engagement': 'Engagement Rate',
            'cityAudience': 'City Audience',
            'ageGroup': 'Age Group Analysis',
            'postData': 'Post Data'
        };
        return names[key] || key;
    }
}

// Initialize global loader
window.globalLoader = new GlobalLoader();