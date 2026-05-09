<?php
$pageTitle  = 'Nearby Services';
$activeMenu = 'nearby';
ob_start();
?>

<link href="/diabetrack/public/assets/css/nearby.css?v=<?= time() ?>" rel="stylesheet">

<!-- HEADER -->
<div class="nb-header">
    <div>
        <div class="nb-eyebrow">📍 Discover</div>
        <h1 class="nb-title">Nearby <span>Services</span></h1>
        <p class="nb-sub">Find clinics, hospitals, and pharmacies near you.</p>
    </div>
    <div class="nb-header-right">
        <button class="nb-locate-btn" id="locateBtn" onclick="getLocation()">
            <span id="locateBtnIcon">📍</span>
            <span id="locateBtnText">Use My Location</span>
        </button>
    </div>
</div>

<!-- SEARCH BAR -->
<div class="nb-search-row">
    <div class="nb-search-wrap">
        <span class="nb-search-icon">🔍</span>
        <input type="text" class="nb-search" id="placeSearch" placeholder="Search by city or address..." />
    </div>
    <div class="nb-filter-tabs" id="filterTabs">
        <button class="nb-filter active" data-type="clinic">🏥 Clinics</button>
        <button class="nb-filter" data-type="hospital">🏨 Hospitals</button>
        <button class="nb-filter" data-type="pharmacy">💊 Pharmacies</button>
        <button class="nb-filter" data-type="lab">🔬 Labs</button>
    </div>
</div>

<!-- STATUS / LOADING -->
<div class="nb-status" id="nbStatus">
    <div class="nb-status-icon">📍</div>
    <div class="nb-status-text">Click "Use My Location" to find nearby health services, or type a location above.</div>
</div>

<!-- RESULTS GRID -->
<div class="nb-results-grid" id="nbResultsGrid" style="display:none;"></div>

<!-- MAP -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
/* ── Map expand button ── */
.nb-map-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.nb-map-expand-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 999px;
    border: 1.5px solid rgba(249,116,71,0.25);
    background: rgba(249,116,71,0.07);
    color: #F97447;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
}
.nb-map-expand-btn:hover {
    background: rgba(249,116,71,0.14);
    border-color: rgba(249,116,71,0.45);
}

/* ── Fullscreen overlay ── */
#nbMapOverlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0,0,0,0.82);
    backdrop-filter: blur(6px);
    animation: mapFadeIn 0.2s ease;
}
#nbMapOverlay.active { display: flex; flex-direction: column; }

@keyframes mapFadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}

.nb-overlay-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    background: rgba(20,8,2,0.9);
    border-bottom: 1px solid rgba(249,116,71,0.15);
    flex-shrink: 0;
}
.nb-overlay-title {
    font-family: 'Cabinet Grotesk', sans-serif;
    font-weight: 800;
    font-size: 1rem;
    color: #ffe8d6;
    display: flex;
    align-items: center;
    gap: 8px;
}
.nb-overlay-close {
    width: 36px; height: 36px;
    border-radius: 50%;
    border: 1.5px solid rgba(249,116,71,0.3);
    background: rgba(249,116,71,0.1);
    color: #F97447;
    font-size: 1.1rem;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.15s;
}
.nb-overlay-close:hover { background: rgba(249,116,71,0.25); }

#nbMapFull {
    flex: 1;
    width: 100%;
}

/* Inline map container */
#nbLeafletMap {
    width: 100%;
    height: 420px;
    border-radius: 16px;
    cursor: grab;
}
#nbLeafletMap:active { cursor: grabbing; }
</style>

<div class="nb-map-section" id="nbMapSection" style="display:none;">
    <div class="nb-map-header">
        <div class="rep-section-label nb-map-label" style="margin-bottom:0;">Map View</div>
        <button class="nb-map-expand-btn" onclick="openMapFullscreen()">
            ⛶ Expand Map
        </button>
    </div>
    <div class="nb-map-wrap">
        <div id="nbLeafletMap"></div>
    </div>
</div>

<!-- Fullscreen map overlay -->
<div id="nbMapOverlay">
    <div class="nb-overlay-bar">
        <div class="nb-overlay-title">🗺 Map View</div>
        <button class="nb-overlay-close" onclick="closeMapFullscreen()" title="Close">✕</button>
    </div>
    <div id="nbMapFull"></div>
</div>

<!-- TIPS PANEL -->
<div class="nb-tips-panel">
    <div class="nb-tips-title">🩺 Visit Preparation Checklist</div>
    <div class="nb-tips-grid">
        <div class="nb-tip-item">
            <div class="nb-tip-icon">🩸</div>
            <div class="nb-tip-text">Bring your last 7-day blood sugar log (printable from Doctor Reports)</div>
        </div>
        <div class="nb-tip-item">
            <div class="nb-tip-icon">💊</div>
            <div class="nb-tip-text">List all medications with dosages — your Medication page has this info</div>
        </div>
        <div class="nb-tip-item">
            <div class="nb-tip-icon">📋</div>
            <div class="nb-tip-text">Note any symptoms, unusual readings, or questions for the doctor</div>
        </div>
        <div class="nb-tip-item">
            <div class="nb-tip-icon">🆔</div>
            <div class="nb-tip-text">Bring a valid ID and your health insurance card</div>
        </div>
        <div class="nb-tip-item">
            <div class="nb-tip-icon">🥗</div>
            <div class="nb-tip-text">Fast for 8–10 hours if a fasting blood glucose test is needed</div>
        </div>
        <div class="nb-tip-item">
            <div class="nb-tip-icon">📅</div>
            <div class="nb-tip-text">Log your visit in Appointments after booking to get a reminder</div>
        </div>
    </div>
</div>

<script>
let userLat = null;
let userLng = null;
let activeType = 'clinic';

// ── FILTER TABS ──────────────────────────────────────────────────────
document.querySelectorAll('.nb-filter').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.nb-filter').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        activeType = this.dataset.type;
        if (userLat && userLng) searchNearby(userLat, userLng);
    });
});

// ── GEOLOCATION ──────────────────────────────────────────────────────
function getLocation() {
    const btn  = document.getElementById('locateBtn');
    const icon = document.getElementById('locateBtnIcon');
    const text = document.getElementById('locateBtnText');

    icon.textContent = '⏳';
    text.textContent = 'Locating...';
    btn.disabled = true;

    if (!navigator.geolocation) {
        showStatus('❌', 'Geolocation is not supported by your browser.');
        resetBtn();
        return;
    }

    navigator.geolocation.getCurrentPosition(
        pos => {
            userLat = pos.coords.latitude;
            userLng = pos.coords.longitude;
            icon.textContent = '✅';
            text.textContent = 'Location Found';
            btn.disabled = false;
            searchNearby(userLat, userLng);
        },
        err => {
            showStatus('❌', 'Could not get your location. Please allow location access or type a city name above.');
            resetBtn();
        }
    );
}

function resetBtn() {
    document.getElementById('locateBtnIcon').textContent = '📍';
    document.getElementById('locateBtnText').textContent = 'Use My Location';
    document.getElementById('locateBtn').disabled = false;
}

// ── MANUAL SEARCH ────────────────────────────────────────────────────
document.getElementById('placeSearch').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const query = this.value.trim();
        if (query) geocodeAndSearch(query);
    }
});

function geocodeAndSearch(query) {
    showStatus('⏳', 'Searching for "' + query + '"...');
    // Use a public geocoding service
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
        .then(r => r.json())
        .then(data => {
            if (data && data.length > 0) {
                userLat = parseFloat(data[0].lat);
                userLng = parseFloat(data[0].lon);
                searchNearby(userLat, userLng);
            } else {
                showStatus('❌', 'Location not found. Try a different search term.');
            }
        })
        .catch(() => showStatus('❌', 'Search failed. Please try again.'));
}

// ── NEARBY SEARCH (Overpass/OSM) ─────────────────────────────────────
const typeMap = {
    clinic:   { osm: 'clinic', emoji: '🏥', color: '#F97447', label: 'Clinic' },
    hospital: { osm: 'hospital', emoji: '🏨', color: '#e74c3c', label: 'Hospital' },
    pharmacy: { osm: 'pharmacy', emoji: '💊', color: '#2ec4b6', label: 'Pharmacy' },
    lab:      { osm: 'laboratory', emoji: '🔬', color: '#9b59b6', label: 'Laboratory' }
};

function searchNearby(lat, lng) {
    const { osm, emoji, color, label } = typeMap[activeType];
    showStatus('⏳', `Finding nearby ${label.toLowerCase()}s...`);
    document.getElementById('nbResultsGrid').style.display = 'none';

    const radius = 5000; // 5km
    const query = `
        [out:json][timeout:15];
        (
            node["amenity"="${osm}"](around:${radius},${lat},${lng});
            way["amenity"="${osm}"](around:${radius},${lat},${lng});
        );
        out body 20;
    `;

    fetch('https://overpass-api.de/api/interpreter', {
        method: 'POST',
        body: query
    })
    .then(r => r.json())
    .then(data => {
        const elements = data.elements || [];
        if (elements.length === 0) {
            showStatus('😔', `No ${label.toLowerCase()}s found within 5km. Try a different location.`);
            return;
        }

        hideStatus();
        renderResults(elements, lat, lng, emoji, color, label);
        updateMap(lat, lng, elements, emoji, color);
    })
    .catch(() => {
        showStatus('❌', 'Could not load nearby services. Please check your connection.');
    });
}

// ── RENDER RESULTS ───────────────────────────────────────────────────
function renderResults(items, userLat, userLng, emoji, color, label) {
    const grid = document.getElementById('nbResultsGrid');
    grid.innerHTML = '';
    grid.style.display = 'grid';

    items.forEach(item => {
        const name = item.tags?.name || `Unnamed ${label}`;
        const address = buildAddress(item.tags);
        const phone = item.tags?.phone || item.tags?.['contact:phone'] || null;
        const website = item.tags?.website || item.tags?.['contact:website'] || null;
        const opening = item.tags?.opening_hours || null;

        // Distance (approx)
        const iLat = item.lat || item.center?.lat;
        const iLng = item.lon || item.center?.lon;
        let distStr = '';
        if (iLat && iLng) {
            const d = haversine(userLat, userLng, iLat, iLng);
            distStr = d < 1 ? Math.round(d * 1000) + 'm away' : d.toFixed(1) + 'km away';
        }

        const mapsUrl = iLat && iLng
            ? `https://www.google.com/maps/search/?api=1&query=${iLat},${iLng}`
            : `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(name)}`;

        const card = document.createElement('div');
        card.className = 'nb-card';
        card.innerHTML = `
            <div class="nb-card-top">
                <div class="nb-card-icon" style="background:${color}1a;color:${color};">${emoji}</div>
                <div class="nb-card-badge" style="background:${color}1a;color:${color};">${label}</div>
            </div>
            <h3 class="nb-card-name">${escHtml(name)}</h3>
            ${address ? `<div class="nb-card-addr">📍 ${escHtml(address)}</div>` : ''}
            ${distStr ? `<div class="nb-card-dist">🚶 ${distStr}</div>` : ''}
            ${opening ? `<div class="nb-card-hours">🕐 ${escHtml(opening)}</div>` : ''}
            <div class="nb-card-actions">
                <a href="${mapsUrl}" target="_blank" rel="noopener" class="nb-card-btn nb-btn-map">🗺 Directions</a>
                ${phone ? `<a href="tel:${escHtml(phone)}" class="nb-card-btn nb-btn-call">📞 Call</a>` : ''}
                ${website ? `<a href="${escHtml(website)}" target="_blank" rel="noopener" class="nb-card-btn nb-btn-web">🌐 Website</a>` : ''}
            </div>
        `;
        grid.appendChild(card);
    });
}

function buildAddress(tags) {
    if (!tags) return '';
    const parts = [
        tags['addr:housenumber'],
        tags['addr:street'],
        tags['addr:city'],
        tags['addr:state']
    ].filter(Boolean);
    return parts.join(', ');
}

function haversine(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2)**2 + Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) * Math.sin(dLng/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── MAP (Leaflet + OpenStreetMap — no API key) ───────────────────────
let leafletMap     = null;
let leafletMapFull = null;
let markersLayer     = null;
let markersLayerFull = null;
let lastItems = [], lastEmoji = '', lastColor = '';

function updateMap(lat, lng, items, emoji, color) {
    lastItems = items; lastEmoji = emoji; lastColor = color;

    const section = document.getElementById('nbMapSection');
    section.style.display = 'block';

    if (!leafletMap) {
        leafletMap = L.map('nbLeafletMap', { scrollWheelZoom: true }).setView([lat, lng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(leafletMap);
    } else {
        leafletMap.setView([lat, lng], 14);
        if (markersLayer) markersLayer.clearLayers();
    }

    markersLayer = L.layerGroup().addTo(leafletMap);
    addMarkers(markersLayer, lat, lng, items, emoji, color);
    setTimeout(() => leafletMap.invalidateSize(), 100);
}

function addMarkers(layer, lat, lng, items, emoji, color) {
    // User pin
    L.circleMarker([lat, lng], {
        radius: 10, color: '#F97447', fillColor: '#F97447',
        fillOpacity: 0.9, weight: 3
    }).bindPopup('<b>📍 You are here</b>').addTo(layer);

    // Place markers
    items.forEach(item => {
        const iLat = item.lat || item.center?.lat;
        const iLng = item.lon || item.center?.lon;
        if (!iLat || !iLng) return;

        const name    = item.tags?.name || 'Unnamed';
        const address = buildAddress(item.tags);
        const phone   = item.tags?.phone || item.tags?.['contact:phone'] || null;
        const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${iLat},${iLng}`;

        const icon = L.divIcon({
            className: '',
            html: `<div style="background:${color};width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;box-shadow:0 2px 8px rgba(0,0,0,0.25);border:2px solid #fff;">${emoji}</div>`,
            iconSize: [32, 32], iconAnchor: [16, 16]
        });

        L.marker([iLat, iLng], { icon })
            .bindPopup(`
                <b style="font-size:0.9rem;">${name}</b><br>
                ${address ? `<span style="color:#888;font-size:0.8rem;">📍 ${address}</span><br>` : ''}
                ${phone   ? `<span style="font-size:0.8rem;">📞 ${phone}</span><br>` : ''}
                <a href="${mapsUrl}" target="_blank" style="font-size:0.8rem;color:#F97447;">🗺 Open in Google Maps</a>
            `)
            .addTo(layer);
    });
}

// ── Fullscreen expand / close ─────────────────────────────────────────
function openMapFullscreen() {
    const overlay = document.getElementById('nbMapOverlay');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';

    if (!leafletMapFull) {
        leafletMapFull = L.map('nbMapFull', { scrollWheelZoom: true });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(leafletMapFull);
    }

    setTimeout(() => {
        leafletMapFull.invalidateSize();
        if (leafletMap) leafletMapFull.setView(leafletMap.getCenter(), leafletMap.getZoom());
        if (markersLayerFull) markersLayerFull.clearLayers();
        markersLayerFull = L.layerGroup().addTo(leafletMapFull);
        if (leafletMap) {
            const c = leafletMap.getCenter();
            addMarkers(markersLayerFull, c.lat, c.lng, lastItems, lastEmoji, lastColor);
        }
    }, 50);
}

function closeMapFullscreen() {
    document.getElementById('nbMapOverlay').classList.remove('active');
    document.body.style.overflow = '';
    setTimeout(() => leafletMap && leafletMap.invalidateSize(), 50);
}

// Close on Escape key
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMapFullscreen(); });

// ── STATUS HELPERS ───────────────────────────────────────────────────
function showStatus(icon, msg) {
    const el = document.getElementById('nbStatus');
    el.style.display = 'flex';
    el.querySelector('.nb-status-icon').textContent = icon;
    el.querySelector('.nb-status-text').textContent = msg;
}
function hideStatus() {
    document.getElementById('nbStatus').style.display = 'none';
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>