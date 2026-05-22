<div class="w-full mt-4">
    <div 
        x-data="{
            lat: @entangle('data.latitude'),
            lng: @entangle('data.longitude'),
            map: null,
            marker: null,
            init() {
                let initMap = () => {
                    // Default coordinates if empty (Bandung)
                    let defaultLat = parseFloat(this.lat) || -6.9175;
                    let defaultLng = parseFloat(this.lng) || 107.6191;

                    // Create Map
                    this.map = L.map(this.$refs.mapContainer).setView([defaultLat, defaultLng], 13);
                    
                    // Add OpenStreetMap tile layer
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(this.map);

                    // Create draggable marker
                    this.marker = L.marker([defaultLat, defaultLng], {
                        draggable: true
                    }).addTo(this.map);

                    // Set initial inputs if not set
                    if (!this.lat || !this.lng) {
                        this.lat = defaultLat.toFixed(6);
                        this.lng = defaultLng.toFixed(6);
                    }

                    // Handle drag marker
                    this.marker.on('dragend', (e) => {
                        let position = this.marker.getLatLng();
                        this.lat = position.lat.toFixed(6);
                        this.lng = position.lng.toFixed(6);
                    });

                    // Listen to external coordinate changes
                    this.$watch('lat', (val) => {
                        if (val) {
                            let newLat = parseFloat(val);
                            let currentLatLng = this.marker.getLatLng();
                            if (Math.abs(currentLatLng.lat - newLat) > 0.00001) {
                                this.marker.setLatLng([newLat, currentLatLng.lng]);
                                this.map.panTo([newLat, currentLatLng.lng]);
                            }
                        }
                    });

                    this.$watch('lng', (val) => {
                        if (val) {
                            let newLng = parseFloat(val);
                            let currentLatLng = this.marker.getLatLng();
                            if (Math.abs(currentLatLng.lng - newLng) > 0.00001) {
                                this.marker.setLatLng([currentLatLng.lat, newLng]);
                                this.map.panTo([currentLatLng.lat, newLng]);
                            }
                        }
                    });

                    // Recalculate size after map container rendered
                    setTimeout(() => {
                        this.map.invalidateSize();
                    }, 200);
                };

                if (typeof window.L === 'undefined') {
                    // Check if script already injected
                    let script = document.querySelector('script[src*=\'leaflet.js\']');
                    if (!script) {
                        // Inject CSS
                        let link = document.createElement('link');
                        link.rel = 'stylesheet';
                        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                        document.head.appendChild(link);

                        // Inject JS
                        script = document.createElement('script');
                        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        script.onload = () => {
                            initMap();
                        };
                        document.head.appendChild(script);
                    } else {
                        // Script exists but not loaded yet
                        script.addEventListener('load', () => {
                            initMap();
                        });
                    }
                } else {
                    initMap();
                }
            }
        }"
        class="border border-slate-200 rounded-xl overflow-hidden shadow-sm dark:border-slate-700"
    >
        <div x-ref="mapContainer" class="w-full" style="z-index: 1; height: 320px; min-height: 320px;"></div>
    </div>
</div>
