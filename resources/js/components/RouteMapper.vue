<template>
    <div class="route-mapper">
        <!-- Header -->
        <div class="mapper-header">
            <h3 class="mapper-title">🗺️ Planificador de Rutas</h3>
            <p class="mapper-subtitle">Selecciona tu punto de inicio y destino en Google Maps</p>
        </div>

        <!-- Mapa Container -->
        <div class="map-container">
            <div ref="googleMap" class="google-map"></div>

            <!-- Controles sobre el mapa -->
            <div class="map-controls">
                <div class="control-group">
                    <button
                        @click="toggleStationsVisibility"
                        class="control-btn stations-btn"
                        :class="{ active: showStations }"
                    >
                        🏢 {{ showStations ? 'Ocultar' : 'Mostrar' }} Estaciones
                    </button>
                    <button
                        @click="clearRoute"
                        class="control-btn clear-btn"
                        :disabled="!startPoint && !endPoint"
                    >
                        🗑️ Limpiar
                    </button>
                    <button
                        @click="getCurrentLocation"
                        class="control-btn location-btn"
                    >
                        📍 Mi Ubicación
                    </button>
                </div>
            </div>

            <!-- Indicadores de puntos -->
            <div class="points-indicator">
                <div class="point-status" :class="{ active: startPoint }">
                    <span class="point-icon start">🟢</span>
                    <span class="point-text">{{ startPoint ? 'Inicio seleccionado' : 'Clic para inicio' }}</span>
                </div>
                <div class="point-status" :class="{ active: endPoint }">
                    <span class="point-icon end">🔴</span>
                    <span class="point-text">{{ endPoint ? 'Destino seleccionado' : 'Clic para destino' }}</span>
                </div>
                <div class="point-status" :class="{ active: showStations }">
                    <span class="point-icon stations">🏢</span>
                    <span class="point-text">{{ stations.length }} estaciones {{ showStations ? 'visibles' : 'disponibles' }}</span>
                </div>
            </div>
        </div>

        <!-- Información de estaciones cercanas -->
        <div v-if="nearbyStations.length > 0" class="nearby-stations-card">
            <h4 class="info-title">🏢 Estaciones Cercanas a tu Ruta</h4>
            <div class="stations-list">
                <div
                    v-for="station in nearbyStations"
                    :key="station.id"
                    class="station-item"
                    @click="highlightStation(station)"
                >
                    <div class="station-info">
                        <div class="station-name">{{ station.name }}</div>
                        <div class="station-details">
                            <span class="station-type" :class="getStationTypeClass(station.type)">
                                {{ getStationTypeIcon(station.type) }} {{ getStationTypeText(station.type) }}
                            </span>
                            <span class="station-distance">{{ station.distanceFromRoute }} km de la ruta</span>
                        </div>
                    </div>
                    <div class="station-capacity">
                        <div class="capacity-info">
                            {{ getAvailableBikes(station) }}/{{ station.capacity }}
                        </div>
                        <div class="capacity-label">disponibles</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de la ruta -->
        <div v-if="routeInfo.distance" class="route-info-card">
            <h4 class="info-title">📊 Información de la Ruta</h4>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Distancia:</span>
                    <span class="info-value">{{ routeInfo.distance }} km</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tiempo estimado:</span>
                    <span class="info-value">{{ routeInfo.estimatedTime }} min</span>
                </div>
                <div class="info-item">
                    <span class="info-label">CO₂ que ahorrarás:</span>
                    <span class="info-value co2">{{ routeInfo.co2Saved }} kg</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Puntos verdes:</span>
                    <span class="info-value points">{{ routeInfo.greenPoints }}</span>
                </div>
            </div>
        </div>

        <!-- Formulario de creación -->
        <div v-if="startPoint && endPoint" class="route-form-card">
            <h4 class="form-title">📝 Detalles de la Ruta</h4>
            <form @submit.prevent="createRoute" class="route-creation-form">
                <div class="form-group">
                    <label for="routeName">Nombre de la ruta:</label>
                    <input
                        v-model="routeData.name"
                        type="text"
                        id="routeName"
                        placeholder="Ej: Casa al trabajo"
                        required
                    />
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="startPointName">Punto de inicio:</label>
                        <input
                            v-model="routeData.start_point"
                            type="text"
                            id="startPointName"
                            :placeholder="startPointAddress || 'Ej: Mi casa, Oficina'"
                            required
                        />
                    </div>
                    <div class="form-group">
                        <label for="endPointName">Punto de destino:</label>
                        <input
                            v-model="routeData.end_point"
                            type="text"
                            id="endPointName"
                            :placeholder="endPointAddress || 'Ej: Trabajo, Universidad'"
                            required
                        />
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Descripción (opcional):</label>
                    <textarea
                        v-model="routeData.route_description"
                        id="description"
                        placeholder="Describe tu ruta..."
                        rows="3"
                    ></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" @click="cancelCreation" class="btn-cancel">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-create" :disabled="loading">
                        {{ loading ? 'Creando...' : '✨ Crear Ruta' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Loading Google Maps -->
        <div v-if="!mapLoaded" class="loading-maps">
            <div class="loading-spinner"></div>
            <p>Cargando Google Maps...</p>
        </div>
    </div>
</template>

<script>
export default {
    name: 'RouteMapper',
    emits: ['route-created'],

    data() {
        return {
            map: null,
            directionsService: null,
            directionsRenderer: null,
            startPoint: null,
            endPoint: null,
            startMarker: null,
            endMarker: null,
            startPointAddress: '',
            endPointAddress: '',
            routeData: {
                name: '',
                start_point: '',
                end_point: '',
                route_description: ''
            },
            routeInfo: {
                distance: 0,
                estimatedTime: 0,
                co2Saved: 0,
                greenPoints: 0
            },
            loading: false,
            mapLoaded: false,
            // Estaciones
            stations: [],
            stationMarkers: [],
            showStations: true,
            nearbyStations: [],
            highlightedStationMarker: null,
            // Coordenadas de Puerto Barrios, Izabal, Guatemala
            defaultCenter: {
                lat: 15.7281,
                lng: -88.5955
            }
        }
    },

    mounted() {
        this.loadGoogleMaps();
        this.loadStations();
    },

    methods: {
        async loadStations() {
            try {
                const response = await axios.get('/stations', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                this.stations = response.data.stations || [];

                // Si el mapa ya está cargado, mostrar las estaciones
                if (this.mapLoaded) {
                    this.displayStations();
                }
            } catch (error) {
                console.error('Error cargando estaciones:', error);
            }
        },

        loadGoogleMaps() {
            // Verificar si Google Maps ya está cargado
            if (window.google && window.google.maps) {
                this.initializeMap();
                return;
            }

            // Crear script tag para cargar Google Maps
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=AIzaSyDbXwkL4Vti_Wevnnr4jY44m8D8fCpqUUY&libraries=places&callback=initGoogleMaps`;
            script.async = true;
            script.defer = true;

            // Callback global para cuando se cargue Google Maps
            window.initGoogleMaps = () => {
                this.initializeMap();
            };

            // Si no tienes API key, usar versión de desarrollo (limitada)
            script.onerror = () => {
                console.warn('No se pudo cargar Google Maps con API key. Usando versión de desarrollo...');
                this.initializeMapFallback();
            };

            document.head.appendChild(script);

            // Fallback para desarrollo sin API key
            setTimeout(() => {
                if (!this.mapLoaded) {
                    this.initializeMapFallback();
                }
            }, 3000);
        },

        initializeMap() {
            if (!this.$refs.googleMap) return;

            try {
                this.map = new google.maps.Map(this.$refs.googleMap, {
                    center: this.defaultCenter,
                    zoom: 13,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    styles: [
                        {
                            featureType: 'poi.business',
                            stylers: [{ visibility: 'off' }]
                        }
                    ]
                });

                this.directionsService = new google.maps.DirectionsService();
                this.directionsRenderer = new google.maps.DirectionsRenderer({
                    draggable: false,
                    suppressMarkers: true
                });
                this.directionsRenderer.setMap(this.map);

                // Event listener para clics en el mapa
                this.map.addListener('click', this.handleMapClick);

                this.mapLoaded = true;

                // Mostrar estaciones si ya están cargadas
                if (this.stations.length > 0) {
                    this.displayStations();
                }
            } catch (error) {
                console.error('Error inicializando Google Maps:', error);
                this.initializeMapFallback();
            }
        },

        initializeMapFallback() {
            // Usar el mapa CSS anterior como fallback
            this.mapLoaded = true;
            this.$refs.googleMap.innerHTML = `
                <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f0f9ff 100%);
                     display: flex; align-items: center; justify-content: center; color: #6b7280; flex-direction: column; gap: 10px;">
                    <div style="font-size: 2rem;">🗺️</div>
                    <div>Google Maps no disponible</div>
                    <div style="font-size: 0.8rem;">Haz clic para seleccionar puntos</div>
                    <div style="font-size: 0.8rem;">${this.stations.length} estaciones disponibles</div>
                </div>
            `;

            this.$refs.googleMap.style.cursor = 'crosshair';
            this.$refs.googleMap.addEventListener('click', this.handleMapClickFallback);
        },

        displayStations() {
            if (!this.showStations || !window.google || !this.map) return;

            // Limpiar marcadores existentes
            this.clearStationMarkers();

            this.stations.forEach(station => {
                if (!station.is_active) return; // Solo mostrar estaciones activas

                const position = {
                    lat: parseFloat(station.latitude),
                    lng: parseFloat(station.longitude)
                };

                const marker = new google.maps.Marker({
                    position: position,
                    map: this.map,
                    title: `${station.name} - ${station.code}`,
                    icon: {
                        path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                        scale: 6,
                        fillColor: this.getStationColor(station),
                        fillOpacity: 0.8,
                        strokeWeight: 2,
                        strokeColor: '#ffffff',
                        rotation: 0
                    }
                });

                // InfoWindow para mostrar información de la estación
                const infoWindow = new google.maps.InfoWindow({
                    content: this.createStationInfoContent(station)
                });

                marker.addListener('click', () => {
                    // Cerrar otros InfoWindows
                    this.stationMarkers.forEach(m => {
                        if (m.infoWindow) {
                            m.infoWindow.close();
                        }
                    });

                    infoWindow.open(this.map, marker);
                });

                this.stationMarkers.push({
                    marker: marker,
                    infoWindow: infoWindow,
                    station: station
                });
            });
        },

        clearStationMarkers() {
            this.stationMarkers.forEach(item => {
                if (item.infoWindow) {
                    item.infoWindow.close();
                }
                item.marker.setMap(null);
            });
            this.stationMarkers = [];
        },

        toggleStationsVisibility() {
            this.showStations = !this.showStations;

            if (this.showStations) {
                this.displayStations();
            } else {
                this.clearStationMarkers();
            }
        },

        createStationInfoContent(station) {
            const availableBikes = this.getAvailableBikes(station);
            const totalBikes = this.getTotalBikes(station);

            return `
                <div style="padding: 10px; max-width: 250px;">
                    <h4 style="margin: 0 0 8px 0; color: #1f2937; font-size: 1rem;">
                        ${this.getStationTypeIcon(station.type)} ${station.name}
                    </h4>
                    <p style="margin: 0 0 8px 0; color: #6b7280; font-size: 0.8rem;">
                        <strong>Código:</strong> ${station.code}
                    </p>
                    <div style="margin-bottom: 8px;">
                        <span style="background: ${this.getStationColor(station)}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem;">
                            ${this.getStationTypeText(station.type)}
                        </span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                        <div style="text-align: center; background: #f0f9ff; padding: 8px; border-radius: 6px;">
                            <div style="font-weight: bold; color: #1e40af;">${availableBikes}</div>
                            <div style="font-size: 0.7rem; color: #6b7280;">Disponibles</div>
                        </div>
                        <div style="text-align: center; background: #f0fdf4; padding: 8px; border-radius: 6px;">
                            <div style="font-weight: bold; color: #16a34a;">${totalBikes}/${station.capacity}</div>
                            <div style="font-size: 0.7rem; color: #6b7280;">Ocupación</div>
                        </div>
                    </div>
                    ${station.address ? `<p style="margin: 0; color: #6b7280; font-size: 0.8rem;">📍 ${station.address}</p>` : ''}
                </div>
            `;
        },

        getStationColor(station) {
            const colors = {
                'carga': '#10b981',      // Verde
                'descanso': '#f59e0b',   // Naranja
                'seleccion': '#8b5cf6'   // Púrpura
            };
            return colors[station.type] || '#6b7280';
        },

        getStationTypeIcon(type) {
            const icons = {
                'carga': '🔋',
                'descanso': '☕',
                'seleccion': '🎯'
            };
            return icons[type] || '🏢';
        },

        getStationTypeText(type) {
            const types = {
                'carga': 'Carga',
                'descanso': 'Descanso',
                'seleccion': 'Selección'
            };
            return types[type] || type;
        },

        getStationTypeClass(type) {
            return `type-${type}`;
        },

        getAvailableBikes(station) {
            if (!station.bikes) return 0;
            return station.bikes.filter(bike => bike.is_active && bike.status === 'disponible').length;
        },

        getTotalBikes(station) {
            if (!station.bikes) return 0;
            return station.bikes.filter(bike => bike.is_active).length;
        },

        findNearbyStations() {
            if (!this.startPoint || !this.endPoint) {
                this.nearbyStations = [];
                return;
            }

            const nearbyStations = [];

            this.stations.forEach(station => {
                if (!station.is_active) return;

                const stationPos = {
                    lat: parseFloat(station.latitude),
                    lng: parseFloat(station.longitude)
                };

                // Calcular distancia al punto de inicio y destino
                const distanceToStart = this.getDistanceFromLatLng(
                    this.startPoint.lat, this.startPoint.lng,
                    stationPos.lat, stationPos.lng
                );

                const distanceToEnd = this.getDistanceFromLatLng(
                    this.endPoint.lat, this.endPoint.lng,
                    stationPos.lat, stationPos.lng
                );

                // Tomar la menor distancia como referencia
                const distanceFromRoute = Math.min(distanceToStart, distanceToEnd);

                // Si está a menos de 2km de algún punto de la ruta
                if (distanceFromRoute <= 2) {
                    nearbyStations.push({
                        ...station,
                        distanceFromRoute: Math.round(distanceFromRoute * 100) / 100
                    });
                }
            });

            // Ordenar por distancia
            this.nearbyStations = nearbyStations.sort((a, b) => a.distanceFromRoute - b.distanceFromRoute);
        },

        highlightStation(station) {
            if (!window.google || !this.map) return;

            // Remover marcador anterior
            if (this.highlightedStationMarker) {
                this.highlightedStationMarker.setMap(null);
            }

            // Crear marcador destacado
            const position = {
                lat: parseFloat(station.latitude),
                lng: parseFloat(station.longitude)
            };

            this.highlightedStationMarker = new google.maps.Marker({
                position: position,
                map: this.map,
                title: station.name,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 15,
                    fillColor: '#ef4444',
                    fillOpacity: 0.8,
                    strokeWeight: 3,
                    strokeColor: '#ffffff'
                },
                animation: google.maps.Animation.BOUNCE
            });

            // Centrar el mapa en la estación
            this.map.setCenter(position);
            this.map.setZoom(16);

            // Quitar la animación después de 2 segundos
            setTimeout(() => {
                if (this.highlightedStationMarker) {
                    this.highlightedStationMarker.setAnimation(null);
                }
            }, 2000);
        },

        handleMapClick(event) {
            const clickPoint = {
                lat: event.latLng.lat(),
                lng: event.latLng.lng()
            };

            if (!this.startPoint) {
                this.setStartPoint(clickPoint);
            } else if (!this.endPoint) {
                this.setEndPoint(clickPoint);
            } else {
                this.clearRoute();
                this.setStartPoint(clickPoint);
            }
        },

        handleMapClickFallback(event) {
            const rect = event.currentTarget.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            // Convertir coordenadas de píxeles a lat/lng simuladas
            const lat = this.defaultCenter.lat + ((y - rect.height/2) / rect.height) * -0.02;
            const lng = this.defaultCenter.lng + ((x - rect.width/2) / rect.width) * 0.03;

            const clickPoint = { lat, lng };

            if (!this.startPoint) {
                this.setStartPoint(clickPoint);
            } else if (!this.endPoint) {
                this.setEndPoint(clickPoint);
            } else {
                this.clearRoute();
                this.setStartPoint(clickPoint);
            }
        },

        setStartPoint(point) {
            this.startPoint = point;
            this.createMarker(point, 'start');
            this.getAddressFromCoordinates(point, 'start');
            this.updateRouteData();
        },

        setEndPoint(point) {
            this.endPoint = point;
            this.createMarker(point, 'end');
            this.getAddressFromCoordinates(point, 'end');
            this.calculateRoute();
            this.updateRouteData();
            this.findNearbyStations();
        },

        createMarker(point, type) {
            if (!window.google || !this.map) return;

            const marker = new google.maps.Marker({
                position: point,
                map: this.map,
                title: type === 'start' ? 'Punto de Inicio' : 'Punto de Destino',
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10,
                    fillColor: type === 'start' ? '#10b981' : '#ef4444',
                    fillOpacity: 1,
                    strokeWeight: 2,
                    strokeColor: '#ffffff'
                }
            });

            if (type === 'start') {
                if (this.startMarker) this.startMarker.setMap(null);
                this.startMarker = marker;
            } else {
                if (this.endMarker) this.endMarker.setMap(null);
                this.endMarker = marker;
            }
        },

        calculateRoute() {
            if (!this.startPoint || !this.endPoint || !this.directionsService) {
                this.calculateRouteSimple();
                return;
            }

            const request = {
                origin: this.startPoint,
                destination: this.endPoint,
                travelMode: google.maps.TravelMode.BICYCLING,
                unitSystem: google.maps.UnitSystem.METRIC
            };

            this.directionsService.route(request, (result, status) => {
                if (status === google.maps.DirectionsStatus.OK) {
                    this.directionsRenderer.setDirections(result);

                    const route = result.routes[0];
                    const leg = route.legs[0];

                    const distanceKm = leg.distance.value / 1000;

                    this.routeInfo = {
                        distance: Math.round(distanceKm * 100) / 100,
                        estimatedTime: Math.round((distanceKm / 15) * 60),
                        co2Saved: Math.round(distanceKm * 0.21 * 100) / 100,
                        greenPoints: Math.round(distanceKm * 10)
                    };
                } else {
                    console.warn('No se pudo calcular la ruta:', status);
                    this.calculateRouteSimple();
                }
            });
        },

        calculateRouteSimple() {
            if (!this.startPoint || !this.endPoint) return;

            const distance = this.getDistanceFromLatLng(
                this.startPoint.lat,
                this.startPoint.lng,
                this.endPoint.lat,
                this.endPoint.lng
            );

            this.routeInfo = {
                distance: Math.round(distance * 100) / 100,
                estimatedTime: Math.round((distance / 15) * 60),
                co2Saved: Math.round(distance * 0.21 * 100) / 100,
                greenPoints: Math.round(distance * 10)
            };
        },

        getDistanceFromLatLng(lat1, lng1, lat2, lng2) {
            const R = 6371; // Radio de la Tierra en km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a =
                Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLng/2) * Math.sin(dLng/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        },

        getAddressFromCoordinates(point, type) {
            if (!window.google) return;

            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: point }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    const address = results[0].formatted_address;
                    if (type === 'start') {
                        this.startPointAddress = address;
                        if (!this.routeData.start_point) {
                            this.routeData.start_point = address;
                        }
                    } else {
                        this.endPointAddress = address;
                        if (!this.routeData.end_point) {
                            this.routeData.end_point = address;
                        }
                    }
                }
            });
        },

        getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };

                        if (this.map) {
                            this.map.setCenter(userLocation);
                            this.map.setZoom(15);
                        }

                        if (!this.startPoint) {
                            this.setStartPoint(userLocation);
                        } else if (!this.endPoint) {
                            this.setEndPoint(userLocation);
                        }

                        alert('📍 Ubicación agregada al mapa');
                    },
                    (error) => {
                        console.warn('Error obteniendo ubicación:', error);
                        alert('No se pudo obtener tu ubicación. Puedes hacer clic en el mapa manualmente.');
                    },
                    {
                        timeout: 10000,
                        enableHighAccuracy: true
                    }
                );
            } else {
                alert('Tu navegador no soporta geolocalización');
            }
        },

        updateRouteData() {
            if (this.startPoint) {
                this.routeData.start_latitude = this.startPoint.lat;
                this.routeData.start_longitude = this.startPoint.lng;
            }

            if (this.endPoint) {
                this.routeData.end_latitude = this.endPoint.lat;
                this.routeData.end_longitude = this.endPoint.lng;
            }
        },

        clearRoute() {
            this.startPoint = null;
            this.endPoint = null;
            this.startPointAddress = '';
            this.endPointAddress = '';
            this.nearbyStations = [];

            if (this.startMarker) {
                this.startMarker.setMap(null);
                this.startMarker = null;
            }

            if (this.endMarker) {
                this.endMarker.setMap(null);
                this.endMarker = null;
            }

            if (this.highlightedStationMarker) {
                this.highlightedStationMarker.setMap(null);
                this.highlightedStationMarker = null;
            }

            if (this.directionsRenderer) {
                this.directionsRenderer.set('directions', null);
            }

            this.routeInfo = {
                distance: 0,
                estimatedTime: 0,
                co2Saved: 0,
                greenPoints: 0
            };
        },

        cancelCreation() {
            this.clearRoute();
            this.routeData = {
                name: '',
                start_point: '',
                end_point: '',
                route_description: ''
            };
        },

        async createRoute() {
            if (!this.startPoint || !this.endPoint) {
                alert('Por favor selecciona tanto el punto de inicio como el de destino');
                return;
            }

            this.loading = true;

            try {
                const routePayload = {
                    ...this.routeData,
                    start_latitude: this.routeData.start_latitude,
                    start_longitude: this.routeData.start_longitude,
                    end_latitude: this.routeData.end_latitude,
                    end_longitude: this.routeData.end_longitude,
                    distance: this.routeInfo.distance,
                    route_points: [
                        { lat: this.routeData.start_latitude, lng: this.routeData.start_longitude },
                        { lat: this.routeData.end_latitude, lng: this.routeData.end_longitude }
                    ]
                };

                const response = await axios.post('/routes', routePayload, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.data.success) {
                    this.$emit('route-created', response.data.route);
                    alert('¡Ruta creada exitosamente!');
                    this.cancelCreation();
                }
            } catch (error) {
                console.error('Error creando ruta:', error);
                if (error.response?.data?.errors) {
                    const errors = Object.values(error.response.data.errors).flat();
                    alert('Errores: ' + errors.join(', '));
                } else {
                    alert('Error al crear la ruta');
                }
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>

<style scoped>
.route-mapper {
    max-width: 1000px;
    margin: 0 auto;
}

.mapper-header {
    text-align: center;
    margin-bottom: 20px;
}

.mapper-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 8px 0;
}

.mapper-subtitle {
    color: #6b7280;
    margin: 0;
}

.map-container {
    position: relative;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 20px;
}

.google-map {
    width: 100%;
    height: 400px;
    border-radius: 8px;
}

.map-controls {
    position: absolute;
    top: 15px;
    right: 15px;
    z-index: 10;
}

.control-group {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.control-btn {
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.2s;
    white-space: nowrap;
}

.control-btn:hover:not(:disabled) {
    background: #f3f4f6;
    transform: translateY(-1px);
}

.control-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.stations-btn.active {
    background: #10b981;
    color: white;
    border-color: #10b981;
}

.clear-btn:hover:not(:disabled) {
    background: #fee2e2;
    border-color: #fca5a5;
}

.location-btn:hover:not(:disabled) {
    background: #dbeafe;
    border-color: #93c5fd;
}

.points-indicator {
    position: absolute;
    bottom: 15px;
    left: 15px;
    right: 15px;
    display: flex;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: wrap;
}

.point-status {
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 6px 10px;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    transition: all 0.3s;
    backdrop-filter: blur(4px);
    flex: 1;
    min-width: 120px;
}

.point-status.active {
    border-color: #10b981;
    background: rgba(16, 185, 129, 0.1);
}

.point-icon {
    font-size: 0.9rem;
}

.point-text {
    font-weight: 500;
    color: #374151;
    text-align: center;
    flex: 1;
}

.nearby-stations-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin-bottom: 20px;
    border-left: 4px solid #10b981;
}

.stations-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.station-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s;
}

.station-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}

.station-info {
    flex: 1;
}

.station-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.station-details {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.station-type {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.station-type.type-carga {
    background: rgba(16, 185, 129, 0.1);
    color: #065f46;
}

.station-type.type-descanso {
    background: rgba(245, 158, 11, 0.1);
    color: #92400e;
}

.station-type.type-seleccion {
    background: rgba(139, 92, 246, 0.1);
    color: #5b21b6;
}

.station-distance {
    font-size: 0.75rem;
    color: #6b7280;
}

.station-capacity {
    text-align: center;
    min-width: 60px;
}

.capacity-info {
    font-weight: 700;
    color: #1f2937;
}

.capacity-label {
    font-size: 0.7rem;
    color: #6b7280;
}

.route-info-card, .route-form-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin-bottom: 20px;
}

.info-title, .form-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 15px 0;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 4px solid #e2e8f0;
}

.info-label {
    font-weight: 500;
    color: #374151;
}

.info-value {
    font-weight: 700;
    color: #1f2937;
}

.info-value.co2 {
    color: #059669;
}

.info-value.points {
    color: #7c3aed;
}

.route-creation-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}

.form-group input,
.form-group textarea {
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1rem;
    transition: all 0.3s;
    resize: vertical;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    padding-top: 10px;
}

.btn-cancel {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-cancel:hover {
    background: #e5e7eb;
}

.btn-create {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-create:hover:not(:disabled) {
    background: #2563eb;
    transform: translateY(-1px);
}

.btn-create:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    transform: none;
}

.loading-maps {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 200px;
    gap: 15px;
    color: #6b7280;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e5e7eb;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .control-group {
        flex-direction: column;
        align-items: stretch;
        gap: 4px;
    }

    .points-indicator {
        flex-direction: column;
        gap: 8px;
        left: 10px;
        right: 10px;
        bottom: 10px;
    }

    .point-status {
        min-width: auto;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
    }

    .google-map {
        height: 300px;
    }

    .station-details {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }

    .nearby-stations-card {
        padding: 15px;
    }
}

@media (max-width: 480px) {
    .mapper-title {
        font-size: 1.3rem;
    }

    .station-item {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }

    .station-capacity {
        align-self: flex-end;
    }
}
</style>
