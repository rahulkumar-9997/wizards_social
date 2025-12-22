$(document).ready(function () {
    let map = null;
    let markers = [];
    let currentHighlightedMarker = null;
    let originalMapView = null;
    
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
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '© OpenStreetMap, © CartoDB',
                maxZoom: 18,
            }).addTo(map);
            L.control.scale({imperial: false}).addTo(map);
            console.log('Map initialized successfully');
            
            // Store original map view
            originalMapView = {
                center: [20, 0],
                zoom: 2
            };
            
        } catch (error) {
            console.error('Error initializing map:', error);
            throw error;
        }
    }
    
    async function geocodeCity(cityName) {
        try {
            const cleanCityName = cityName.split(',')[0].trim();
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(cleanCityName)}&limit=1&accept-language=en&addressdetails=1&namedetails=1&countrycodes=in,us,gb,ca,au,pk,bd,lk,ae`
            );

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data && data.length > 0) {
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
    
    async function geocodeAllLocations(locations) {
        const geocodedLocations = [];
        let successCount = 0;
        console.log('Starting geocoding for:', locations.length, 'locations');
        for (let i = 0; i < locations.length; i++) {
            const location = locations[i];
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
            await new Promise(resolve => setTimeout(resolve, 1200));
        }

        console.log(`Geocoding completed: ${successCount} out of ${locations.length} locations successfully mapped`);
        return geocodedLocations;
    }

    function getColorForPercentage(percentage) {
        if (percentage > 20) return '#FF6B6B'; /* Red for high */
        if (percentage > 10) return '#4ECDC4'; /* Teal for medium-high */
        if (percentage > 5) return '#45B7D1';  /* Blue for medium */
        if (percentage > 2) return '#96CEB4';  /* Green for low-medium */
        return '#FFEAA7'; /* Yellow for low */
    }
    
    function resetToDefault() {
        // Remove highlight from table rows
        $('.location-row').removeClass('table-active');
        
        // Remove highlight from markers
        if (currentHighlightedMarker) {
            const originalColor = currentHighlightedMarker.originalColor;
            const originalSize = currentHighlightedMarker.originalSize;
            
            const icon = L.divIcon({
                className: 'custom-map-marker',
                html: `
                    <div class="map-marker" style="
                        width: ${originalSize}px;
                        height: ${originalSize}px;
                        background: ${originalColor};
                        border: 3px solid white;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                        font-weight: bold;
                        font-size: ${Math.max(10, originalSize - 12)}px;
                        cursor: pointer;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                        text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
                    ">
                        ${Math.round(currentHighlightedMarker.percentage)}%
                    </div>
                `,
                iconSize: [originalSize, originalSize],
                iconAnchor: [originalSize / 2, originalSize / 2]
            });
            
            currentHighlightedMarker.marker.setIcon(icon);
            currentHighlightedMarker.marker.closePopup();
            currentHighlightedMarker = null;
        }
        
        // Reset map to original view
        if (map && originalMapView) {
            map.setView(originalMapView.center, originalMapView.zoom);
        }
        
        // Show reset confirmation
        showResetMessage();
    }
    
    function showResetMessage() {
        // Create a temporary success message
        const resetMessage = $(`
            <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999;">
                <i class="fas fa-check-circle me-2"></i>
                Map reset to default view
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('body').append(resetMessage);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            resetMessage.alert('close');
        }, 3000);
    }
    
    function highlightMarker(locationName) {
        /* Remove previous highlight*/
        if (currentHighlightedMarker) {
            const originalColor = currentHighlightedMarker.originalColor;
            const originalSize = currentHighlightedMarker.originalSize;
            
            const icon = L.divIcon({
                className: 'custom-map-marker',
                html: `
                    <div class="map-marker" style="
                        width: ${originalSize}px;
                        height: ${originalSize}px;
                        background: ${originalColor};
                        border: 3px solid white;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                        font-weight: bold;
                        font-size: ${Math.max(10, originalSize - 12)}px;
                        cursor: pointer;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                        text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
                    ">
                        ${Math.round(currentHighlightedMarker.percentage)}%
                    </div>
                `,
                iconSize: [originalSize, originalSize],
                iconAnchor: [originalSize / 2, originalSize / 2]
            });
            
            currentHighlightedMarker.marker.setIcon(icon);
        }

        /* Find and highlight new marker*/
        const markerData = markers.find(m => m.locationName === locationName);
        if (markerData) {
            const highlightSize = markerData.originalSize + 15; 
            const highlightColor = '#FF0000';
            
            const highlightIcon = L.divIcon({
                className: 'custom-map-marker highlighted',
                html: `
                    <div class="map-marker" style="
                        width: ${highlightSize}px;
                        height: ${highlightSize}px;
                        background: ${highlightColor};
                        border: 4px solid yellow;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                        font-weight: bold;
                        font-size: ${Math.max(12, highlightSize - 15)}px;
                        cursor: pointer;
                        box-shadow: 0 6px 20px rgba(255,0,0,0.6);
                        text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
                        animation: pulse 1.5s infinite;
                    ">
                        ${Math.round(markerData.percentage)}%
                    </div>
                `,
                iconSize: [highlightSize, highlightSize],
                iconAnchor: [highlightSize / 2, highlightSize / 2]
            });
            
            markerData.marker.setIcon(highlightIcon);
            
            /* Center map on the highlighted marker*/
            map.setView(markerData.coordinates, Math.max(6, map.getZoom()));
            markerData.marker.openPopup();
            
            /* Store current highlighted marker*/
            currentHighlightedMarker = {
                marker: markerData.marker,
                originalColor: markerData.originalColor,
                originalSize: markerData.originalSize,
                percentage: markerData.percentage
            };
        }
    }

    /* Function to create table HTML with click events */
    function createLocationsTable(locations) {
        return `
            <div class="table-responsive">
                <table class="table table-sm table-hover table-bordered location-table">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th>City Name</th>
                            <th width="15%" class="text-end">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${locations.map((location, index) => `
                            <tr class="location-row" data-location="${location.name}" style="cursor: pointer;">
                                <td class="text-center">
                                    <span class="badge bg-secondary">${index + 1}</span>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                    ${location.name}
                                </td>
                                <td class="text-end">
                                    <span class="badge" style="background: ${getColorForPercentage(location.percentage)}; color: white;">
                                        ${location.percentage}%
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    async function loadGeolocationData(timeframe = 'this_month') {
        const container = $('#geolocationContainer');
        container.html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Loading audience data...</p>
                <small class="text-muted">Fetching locations from API</small>
            </div>
        `);

        try {
            const response = await $.ajax({
                url: window.insta_city_audience_pdf_url,
                data: { timeframe },
                timeout: 30000
            });
            const description = response.api_description || '';
            initTooltipCityName(description);

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
            container.html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Preparing map data...</p>
                    <small class="text-muted">Geocoding ${response.locations.length} cities</small>
                </div>
            `);
            const geocodedLocations = await geocodeAllLocations(response.locations);
            const locationsWithCoordinates = geocodedLocations.filter(loc => loc.coordinates);

            if (locationsWithCoordinates.length === 0) {
                container.html(`
                    <div class="alert alert-warning text-center py-4">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Could not map any locations. Showing table only.
                    </div>
                    ${createLocationsTable(response.locations)}
                `);
                return;
            }

            /* Create layout with map and table */
            container.html(`
                <div class="row">
                    <!-- Map Section -->
                    <div class="col-lg-12 mb-1">
                        <div class="card h-100">
                            <div class="card-header py-1 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-globe-americas me-2"></i>
                                    Geographic Distribution
                                </h6>
                                <button class="btn btn-sm btn-outline-secondary" id="resetMapBtn">
                                    <i class="fas fa-sync-alt me-1"></i> Reset View
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div id="worldMap" style="height: 350px; border-radius: 0 0 8px 8px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Table Section -->
                    <div class="col-lg-12 mb-1">
                        <div class="card h-100">
                            <div class="card-header py-1 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    Top Locations (${response.locations.length})
                                </h6>
                                <small class="text-muted">Click on any row to highlight on map</small>
                            </div>
                            <div class="card-body p-0">
                                ${createLocationsTable(response.locations)}
                            </div>
                        </div>
                    </div>
                </div>
            `);

            await new Promise(resolve => setTimeout(resolve, 100));
            initMap();
            clearMarkers();
            const maxPercentage = Math.max(...locationsWithCoordinates.map(loc => loc.percentage));
            const getMarkerSize = (percentage) => {
                const baseSize = 20;
                const scale = percentage / maxPercentage;
                return baseSize + (scale * 40);
            };

            /* Add markers for each location */
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
                markers.push({
                    marker: marker,
                    locationName: location.name,
                    coordinates: location.coordinates,
                    originalColor: color,
                    originalSize: size,
                    percentage: location.percentage
                });
            });
            
            if (markers.length > 0) {
                const group = new L.featureGroup(markers.map(m => m.marker));
                map.fitBounds(group.getBounds().pad(0.15));
                
                /* Update original map view to show all markers*/
                originalMapView = {
                    center: map.getCenter(),
                    zoom: map.getZoom()
                };
            }
            
            /* Add click event for table rows*/
            $('.location-row').on('click', function() {
                const locationName = $(this).data('location');
                $('.location-row').removeClass('table-active');
                $(this).addClass('table-active');
                highlightMarker(locationName);
            });
            
            /* Add click event for reset button*/
            $('#resetMapBtn').on('click', function() {
                resetToDefault();
            });

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
            markers.forEach(markerData => {
                map.removeLayer(markerData.marker);
            });
        }
        markers = [];
        currentHighlightedMarker = null;
    }
    
    loadGeolocationData();
    $('#timeframe').on('change', function () {
        loadGeolocationData($(this).val());
    });
});

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
    .progress {
        background-color: #e9ecef;
        border-radius: 10px;
    }
    .progress-bar {
        background: linear-gradient(45deg, #FF6B6B, #4ECDC4);
        border-radius: 10px;
        transition: width 0.3s ease;
    }
    .table th {
        border-top: none;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .location-row:hover {
        background-color: #f8f9fa !important;
    }
    .table-active {
        background-color: #e3f2fd !important;
        border-left: 4px solid #2196F3 !important;
    }
    @keyframes pulse {
        0% {
            box-shadow: 0 6px 20px rgba(255,0,0,0.6);
        }
        50% {
            box-shadow: 0 6px 30px rgba(255,0,0,0.9);
        }
        100% {
            box-shadow: 0 6px 20px rgba(255,0,0,0.6);
        }
    }
    #resetMapBtn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
`;
document.head.appendChild(style);

function initTooltipCityName(description) {
    const icon = $('#audienceByCitiesTitle');
    if (icon.length === 0) return;

    const safeDescription = description && description.trim() !== '' 
        ? description 
        : 'No description available';

    icon.attr('data-bs-title', safeDescription);
    icon.attr('data-bs-toggle', 'tooltip');
    new bootstrap.Tooltip(icon[0]);
}