$(document).ready(function () {
    let map = null;
    let markers = [];

    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.error('Leaflet library not loaded!');
        $('#geolocationContainer').html(`
            <div class="alert alert-danger text-center py-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Map library not loaded. Please check your internet connection.
            </div>
        `);
        return;
    }

    // Initialize world map
    function initMap() {
        try {
            const mapContainer = document.getElementById('worldMap');
            if (!mapContainer) {
                throw new Error('Map container not found');
            }

            if (mapContainer._leaflet_id) {
                map.remove();
            }

            map = L.map('worldMap').setView([20, 0], 2);

            // Use a more colorful tile layer
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '© OpenStreetMap, © CartoDB',
                maxZoom: 18,
            }).addTo(map);
            L.control.scale({imperial: false}).addTo(map);
            console.log('Map initialized successfully');
        } catch (error) {
            console.error('Error initializing map:', error);
            throw error;
        }
    }

    // Enhanced geocoding function with better language handling
    async function geocodeCity(cityName) {
        try {
            const cleanCityName = cityName.split(',')[0].trim();

            // Enhanced API call with multiple parameters for English results
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(cleanCityName)}&limit=1&accept-language=en&addressdetails=1&namedetails=1&countrycodes=in,us,gb,ca,au,pk,bd,lk,ae`
            );

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data && data.length > 0) {
                // Prefer English name from namedetails if available
                let englishName = data[0].display_name;
                if (data[0].namedetails && data[0].namedetails['name:en']) {
                    englishName = data[0].namedetails['name:en'];
                }
                
                return {
                    lat: parseFloat(data[0].lat),
                    lng: parseFloat(data[0].lon),
                    found: true,
                    displayName: englishName
                };
            }
            return { found: false };
        } catch (error) {
            console.error('Geocoding error for:', cityName, error);
            return { found: false };
        }
    }

    // Batch geocode all locations
    async function geocodeAllLocations(locations) {
        const geocodedLocations = [];
        let successCount = 0;

        console.log('Starting geocoding for:', locations.length, 'locations');

        for (let i = 0; i < locations.length; i++) {
            const location = locations[i];

            // Update loading message
            $('#geolocationContainer').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Mapping cities... (${i + 1}/${locations.length})</p>
                    <small class="text-muted">${location.name}</small>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar" style="width: ${((i + 1) / locations.length) * 100}%"></div>
                    </div>
                </div>
            `);

            const coordinates = await geocodeCity(location.name);
            if (coordinates.found) {
                geocodedLocations.push({
                    ...location,
                    coordinates: [coordinates.lat, coordinates.lng],
                    fullLocation: coordinates.displayName || location.name
                });
                successCount++;
            } else {
                console.warn(`Could not geocode: ${location.name}`);
                geocodedLocations.push({
                    ...location,
                    coordinates: null,
                    fullLocation: location.name
                });
            }

            // Add delay to respect Nominatim usage policy
            await new Promise(resolve => setTimeout(resolve, 1200));
        }

        console.log(`Geocoding completed: ${successCount} out of ${locations.length} locations successfully mapped`);
        return geocodedLocations;
    }

    // Color palette for markers
    const colorPalette = [
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
        '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9',
        '#F8C471', '#82E0AA', '#F1948A', '#85C1E9', '#D7BDE2',
        '#F9E79F', '#A9DFBF', '#F5B7B1', '#AED6F1', '#D2B4DE'
    ];

    // Get color based on percentage
    function getColorForPercentage(percentage) {
        if (percentage > 20) return '#FF6B6B'; // Red for high
        if (percentage > 10) return '#4ECDC4'; // Teal for medium-high
        if (percentage > 5) return '#45B7D1';  // Blue for medium
        if (percentage > 2) return '#96CEB4';  // Green for low-medium
        return '#FFEAA7'; // Yellow for low
    }

    async function loadGeolocationData(timeframe = 'this_month') {
        const container = $('#geolocationContainer');

        // Show initial loading state
        container.html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Loading audience data...</p>
                <small class="text-muted">Fetching locations from API</small>
            </div>
        `);

        try {
            const response = await $.ajax({
                url: window.instagramTopLocationUrl,
                data: { timeframe },
                timeout: 30000
            });

            if (!response.success) {
                container.html(`
                    <div class="alert alert-danger text-center py-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${response.message}
                    </div>
                `);
                return;
            }

            if (!response.locations || response.locations.length === 0) {
                container.html(`
                    <div class="alert alert-info text-center py-4">
                        <i class="fas fa-info-circle me-2"></i>
                        No audience data available for the selected timeframe.
                    </div>
                `);
                return;
            }

            // Show geocoding progress
            container.html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Preparing map data...</p>
                    <small class="text-muted">Geocoding ${response.locations.length} cities</small>
                </div>
            `);

            // Geocode all locations
            const geocodedLocations = await geocodeAllLocations(response.locations);

            // Filter out locations without coordinates
            const locationsWithCoordinates = geocodedLocations.filter(loc => loc.coordinates);

            if (locationsWithCoordinates.length === 0) {
                container.html(`
                    <div class="alert alert-warning text-center py-4">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Could not map any locations. Please try again.
                    </div>
                `);
                return;
            }

            // Create map container
            container.html(`
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">
                        <i class="fas fa-globe-americas me-2"></i>
                        Audience Locations (${locationsWithCoordinates.length} cities)
                    </h6>
                </div>
                <div id="worldMap" style="height: 500px; border-radius: 8px; border: 1px solid #dee2e6;"></div>
            `);

            // Wait for DOM to update
            await new Promise(resolve => setTimeout(resolve, 100));

            // Initialize map
            initMap();

            // Clear previous markers
            clearMarkers();

            // Calculate marker sizes based on percentage
            const maxPercentage = Math.max(...locationsWithCoordinates.map(loc => loc.percentage));
            const getMarkerSize = (percentage) => {
                const baseSize = 20;
                const scale = percentage / maxPercentage;
                return baseSize + (scale * 40); // 20px to 60px range
            };

            // Add markers for each location
            locationsWithCoordinates.forEach((location, index) => {
                const size = getMarkerSize(location.percentage);
                const color = getColorForPercentage(location.percentage);
                const shadowColor = color.replace(')', ', 0.5)').replace('rgb', 'rgba');

                const customIcon = L.divIcon({
                    className: 'custom-map-marker',
                    html: `
                        <div class="map-marker" style="
                            width: ${size}px;
                            height: ${size}px;
                            background: ${color};
                            border: 3px solid white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: ${Math.max(10, size - 12)}px;
                            cursor: pointer;
                            box-shadow: 0 4px 12px ${shadowColor};
                            text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
                            transition: all 0.3s ease;
                        " title="${location.name}: ${location.percentage}%">
                            ${Math.round(location.percentage)}%
                        </div>
                    `,
                    iconSize: [size, size],
                    iconAnchor: [size / 2, size / 2]
                });

                const marker = L.marker(location.coordinates, { icon: customIcon })
                    .addTo(map)
                    .bindPopup(`
                        <div class="text-center" style="min-width: 160px;">
                            <h6 class="mb-2 text-dark fw-bold">${location.name}</h6>
                            <div class="mb-2">
                                <span class="badge" style="background: ${color}; font-size: 14px;">
                                    ${location.percentage}% Audience
                                </span>
                            </div>
                            
                        </div>
                    `)
                    .on('mouseover', function () {
                        this.openPopup();
                    });

                markers.push(marker);
            });

            // Fit map to show all markers
            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.15));
            }

        } catch (error) {
            console.error('Error loading geolocation data:', error);
            container.html(`
                <div class="alert alert-danger text-center py-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error: ${error.message}
                </div>
            `);
        }
    }

    function clearMarkers() {
        if (map) {
            markers.forEach(marker => {
                map.removeLayer(marker);
            });
        }
        markers = [];
    }

    // Initialize when page loads
    loadGeolocationData();

    // Handle timeframe change
    $('#timeframe').on('change', function () {
        loadGeolocationData($(this).val());
    });
});

// Enhanced CSS for colorful map
const style = document.createElement('style');
style.textContent = `
    .custom-map-marker {
        background: transparent !important;
        border: none !important;
    }
    .map-marker {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .map-marker:hover {
        transform: scale(1.3);
        box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        z-index: 1000 !important;
    }
    .leaflet-popup-content {
        margin: 16px 20px;
    }
    .leaflet-popup-content-wrapper {
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        border: 2px solid #f8f9fa;
    }
    .leaflet-popup-tip {
        background: white;
    }
    #worldMap {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .progress {
        background-color: #e9ecef;
        border-radius: 10px;
    }
    .progress-bar {
        background: linear-gradient(45deg, #FF6B6B, #4ECDC4);
        border-radius: 10px;
        transition: width 0.3s ease;
    }
`;
document.head.appendChild(style);