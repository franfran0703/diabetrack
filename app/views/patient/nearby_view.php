<?php
$pageTitle  = 'Nearby Services';
$activeMenu = 'nearby';
ob_start();
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link href="<?= BASE_URL ?>/assets/css/nearby.css?v=<?= time() ?>" rel="stylesheet">


<!-- ══ PAGE HEADER ═══════════════════════════════════════ -->
<div class="nb-page-header">
    <div class="nb-page-header-left">
        <div class="nb-page-eyebrow">
            <i class="ti ti-map-pin"></i> Discover
        </div>
        <h1 class="nb-page-title">Nearby <span>Services</span></h1>
        <p class="nb-page-sub">Find clinics, hospitals, pharmacies and labs near you.</p>
    </div>
    <div class="nb-page-header-right">
        <div class="nb-radius-badge">
            <i class="ti ti-circle-dotted"></i> 5 km radius
        </div>
        <button class="nb-locate-btn" id="locateBtn" onclick="getLocation()">
            <span class="nb-locate-btn-icon" id="locateBtnIcon">
                <i class="ti ti-current-location"></i>
            </span>
            <span id="locateBtnText">Use My Location</span>
        </button>
    </div>
</div>


<!-- ══ SEARCH + FILTER ROW ═══════════════════════════════ -->
<div class="nb-search-row">
    <div class="nb-search-wrap">
        <i class="ti ti-search nb-search-icon"></i>
        <input type="text" class="nb-search" id="placeSearch"
               placeholder="Search by city or address… then press Enter" autocomplete="off"/>
        <button class="nb-search-submit" onclick="geocodeAndSearch(document.getElementById('placeSearch').value.trim())" title="Search">
            <i class="ti ti-arrow-right"></i>
        </button>
    </div>
</div>

<div class="nb-filter-row">
    <div class="nb-filter-tabs" id="filterTabs" role="tablist">
        <button class="nb-filter active" data-type="clinic" role="tab">
            <i class="ti ti-building-hospital"></i> Clinics
        </button>
        <button class="nb-filter" data-type="hospital" role="tab">
            <i class="ti ti-building-plus"></i> Hospitals
        </button>
        <button class="nb-filter" data-type="pharmacy" role="tab">
            <i class="ti ti-pill"></i> Pharmacies
        </button>
        <button class="nb-filter" data-type="lab" role="tab">
            <i class="ti ti-microscope"></i> Labs
        </button>
    </div>
    <div class="nb-result-counter" id="nbResultCounter" style="display:none;">
        <i class="ti ti-map-pin"></i>
        <span id="nbResultCount">0</span> found
    </div>
</div>


<!-- ══ IDLE / STATUS STATE ═══════════════════════════════ -->
<div class="nb-status" id="nbStatus">

    <!-- Idle: service type preview cards -->
    <div class="nb-idle-grid" id="nbIdleGrid">
        <?php foreach ([
            ['ti-building-hospital', '#c04a20', '#FDE8DC', 'Clinics',     'Primary care & diabetes specialists'],
            ['ti-building-plus',     '#b91c1c', '#fde8e8', 'Hospitals',   'Emergency care & full-service facilities'],
            ['ti-pill',              '#0e7490', '#cffafe', 'Pharmacies',  'Fill prescriptions & pick up supplies'],
            ['ti-microscope',        '#6d28d9', '#ede9fe', 'Labs',        'Blood glucose & HbA1c testing'],
        ] as [$icon, $color, $bg, $title, $desc]): ?>
        <div class="nb-idle-card" style="--ic:<?= $color ?>;--ib:<?= $bg ?>;">
            <div class="nb-idle-icon"><i class="ti <?= $icon ?>"></i></div>
            <div class="nb-idle-title"><?= $title ?></div>
            <div class="nb-idle-desc"><?= $desc ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Status message row (loading / error / empty) -->
    <div class="nb-status-msg" id="nbStatusMsg" style="display:none;">
        <div class="nb-status-icon-wrap" id="nbStatusIconWrap">
            <i class="ti ti-map-pin" id="nbStatusIcon"></i>
        </div>
        <div class="nb-status-text" id="nbStatusText">
            Tap "Use My Location" to find nearby health services, or type a location above.
        </div>
    </div>

</div>


<!-- ══ RESULTS GRID ══════════════════════════════════════ -->
<div class="nb-results-grid" id="nbResultsGrid" style="display:none;"></div>


<!-- ══ MAP SECTION ═══════════════════════════════════════ -->
<div class="nb-map-section" id="nbMapSection" style="display:none;">
    <div class="nb-map-header">
        <div class="nb-section-label">
            <i class="ti ti-map-2"></i> Map View
        </div>
        <button class="nb-map-expand-btn" onclick="openMapFullscreen()">
            <i class="ti ti-maximize"></i> Expand Map
        </button>
    </div>
    <div class="nb-map-wrap">
        <div id="nbLeafletMap"></div>
    </div>
</div>


<!-- ══ FULLSCREEN MAP OVERLAY ════════════════════════════ -->
<div id="nbMapOverlay">
    <div class="nb-overlay-bar">
        <div class="nb-overlay-title">
            <i class="ti ti-map-2"></i> Map View
        </div>
        <button class="nb-overlay-close" onclick="closeMapFullscreen()" aria-label="Close map">
            <i class="ti ti-x"></i>
        </button>
    </div>
    <div id="nbMapFull"></div>
</div>


<!-- ══ VISIT PREP CHECKLIST ══════════════════════════════ -->
<div class="nb-checklist-panel">
    <div class="nb-checklist-header">
        <div class="nb-section-label">
            <i class="ti ti-clipboard-check"></i> Visit Preparation
        </div>
    </div>
    <div class="nb-checklist-grid">
        <?php foreach ([
            ['ti-droplet-half-2', '#c04a20', '#FDE8DC', 'Bring your last 7-day blood sugar log', 'Printable from Doctor Reports'],
            ['ti-pill',           '#0e7490', '#cffafe', 'List all medications with dosages',       'Your Medication page has this'],
            ['ti-notes',          '#6d28d9', '#ede9fe', 'Note symptoms, unusual readings & questions', 'Write them down beforehand'],
            ['ti-id-badge',       '#d97706', '#fef3c7', 'Bring valid ID & health insurance card',  'Required at most facilities'],
            ['ti-salad',          '#0f7a45', '#d4f7e8', 'Fast 8–10 hrs if a glucose test is needed', 'Water is fine to drink'],
            ['ti-calendar-plus',  '#c04a20', '#FDE8DC', 'Log your visit in Appointments after booking', 'Get a reminder set up'],
        ] as [$icon, $color, $bg, $main, $sub]): ?>
        <div class="nb-checklist-item">
            <div class="nb-checklist-icon" style="background:<?= $bg ?>;color:<?= $color ?>;">
                <i class="ti <?= $icon ?>"></i>
            </div>
            <div class="nb-checklist-text">
                <div class="nb-checklist-main"><?= $main ?></div>
                <div class="nb-checklist-sub"><?= $sub ?></div>
            </div>
            <div class="nb-checklist-check"><i class="ti ti-circle-check"></i></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>


<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
/* ── State ──────────────────────────────────────────────── */
let userLat    = null;
let userLng    = null;
let activeType = 'clinic';

/* ── Service type config (Tabler icons in JS) ──────────── */
const typeMap = {
    clinic:   { osm: 'clinic',      icon: 'ti-building-hospital', color: '#c04a20', bg: '#FDE8DC', label: 'Clinic'    },
    hospital: { osm: 'hospital',    icon: 'ti-building-plus',     color: '#b91c1c', bg: '#fde8e8', label: 'Hospital'  },
    pharmacy: { osm: 'pharmacy',    icon: 'ti-pill',              color: '#0e7490', bg: '#cffafe', label: 'Pharmacy'  },
    lab:      { osm: 'laboratory',  icon: 'ti-microscope',        color: '#6d28d9', bg: '#ede9fe', label: 'Laboratory'},
};

/* ── Filter tabs ─────────────────────────────────────────── */
document.querySelectorAll('.nb-filter').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.nb-filter').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        activeType = this.dataset.type;
        if (userLat && userLng) searchNearby(userLat, userLng);
    });
});

/* ── Geolocation ─────────────────────────────────────────── */
function getLocation() {
    const btn  = document.getElementById('locateBtn');
    const icon = document.getElementById('locateBtnIcon');
    const text = document.getElementById('locateBtnText');

    icon.innerHTML = '<i class="ti ti-loader-2 nb-spin"></i>';
    text.textContent = 'Locating…';
    btn.disabled = true;

    if (!navigator.geolocation) {
        showStatusMsg('ti-x', 'Geolocation is not supported by your browser.');
        resetBtn(); return;
    }

    navigator.geolocation.getCurrentPosition(
        pos => {
            userLat = pos.coords.latitude;
            userLng = pos.coords.longitude;
            icon.innerHTML = '<i class="ti ti-circle-check"></i>';
            text.textContent = 'Location Found';
            btn.disabled = false;
            searchNearby(userLat, userLng);
        },
        () => {
            showStatusMsg('ti-map-pin-off', 'Could not get your location. Allow location access or type a city above.');
            resetBtn();
        }
    );
}

function resetBtn() {
    document.getElementById('locateBtnIcon').innerHTML = '<i class="ti ti-current-location"></i>';
    document.getElementById('locateBtnText').textContent = 'Use My Location';
    document.getElementById('locateBtn').disabled = false;
}

/* ── Manual search ───────────────────────────────────────── */
document.getElementById('placeSearch').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        const q = this.value.trim();
        if (q) geocodeAndSearch(q);
    }
});

function geocodeAndSearch(query) {
    if (!query) return;
    hideIdleGrid();
    showStatusMsg('ti-loader-2 nb-spin', `Searching for "${query}"…`);
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
        .then(r => r.json())
        .then(data => {
            if (data && data.length > 0) {
                userLat = parseFloat(data[0].lat);
                userLng = parseFloat(data[0].lon);
                searchNearby(userLat, userLng);
            } else {
                showStatusMsg('ti-map-search', 'Location not found. Try a different search term.');
            }
        })
        .catch(() => showStatusMsg('ti-wifi-off', 'Search failed. Please check your connection.'));
}

/* ── Nearby search (Overpass API / OSM) ──────────────────── */
function searchNearby(lat, lng) {
    const { osm, label } = typeMap[activeType];
    hideIdleGrid();
    showStatusMsg('ti-loader-2 nb-spin', `Finding nearby ${label.toLowerCase()}s…`);
    document.getElementById('nbResultsGrid').style.display = 'none';
    document.getElementById('nbResultCounter').style.display = 'none';

    const query = `
        [out:json][timeout:15];
        (
            node["amenity"="${osm}"](around:5000,${lat},${lng});
            way["amenity"="${osm}"](around:5000,${lat},${lng});
        );
        out body 20;
    `;

    fetch('https://overpass-api.de/api/interpreter', { method: 'POST', body: query })
        .then(r => r.json())
        .then(data => {
            const items = data.elements || [];
            if (items.length === 0) {
                showStatusMsg('ti-map-pin-off', `No ${label.toLowerCase()}s found within 5 km. Try a different location.`);
                return;
            }
            hideStatusMsg();
            renderResults(items, lat, lng);
            updateMap(lat, lng, items);
        })
        .catch(() => showStatusMsg('ti-wifi-off', 'Could not load nearby services. Please check your connection.'));
}

/* ── Render result cards ─────────────────────────────────── */
function renderResults(items, uLat, uLng) {
    const { icon, color, bg, label } = typeMap[activeType];
    const grid = document.getElementById('nbResultsGrid');
    grid.innerHTML = '';
    grid.style.display = 'grid';

    const counter = document.getElementById('nbResultCounter');
    document.getElementById('nbResultCount').textContent = items.length;
    counter.style.display = 'inline-flex';

    items.forEach((item, idx) => {
        const name    = item.tags?.name || `Unnamed ${label}`;
        const address = buildAddress(item.tags);
        const phone   = item.tags?.phone || item.tags?.['contact:phone'] || null;
        const website = item.tags?.website || item.tags?.['contact:website'] || null;
        const hours   = item.tags?.opening_hours || null;
        const iLat    = item.lat   || item.center?.lat;
        const iLng    = item.lon   || item.center?.lon;
        const mapsUrl = iLat && iLng
            ? `https://www.google.com/maps/search/?api=1&query=${iLat},${iLng}`
            : `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(name)}`;

        let distStr = '';
        if (iLat && iLng) {
            const d = haversine(uLat, uLng, iLat, iLng);
            distStr = d < 1 ? Math.round(d * 1000) + ' m' : d.toFixed(1) + ' km';
        }

        const card = document.createElement('div');
        card.className = 'nb-card';
        card.style.animationDelay = (idx * 0.05) + 's';
        card.innerHTML = `
            <div class="nb-card-top">
                <div class="nb-card-icon" style="background:${bg};color:${color};">
                    <i class="ti ${icon}"></i>
                </div>
                <span class="nb-card-badge" style="background:${bg};color:${color};">${label}</span>
            </div>
            <h3 class="nb-card-name">${escHtml(name)}</h3>
            ${address ? `<div class="nb-card-addr"><i class="ti ti-map-pin"></i>${escHtml(address)}</div>` : ''}
            ${distStr ? `<div class="nb-card-dist"><i class="ti ti-walk"></i>${distStr} away</div>` : ''}
            ${hours   ? `<div class="nb-card-hours"><i class="ti ti-clock"></i>${escHtml(hours)}</div>` : ''}
            <div class="nb-card-actions">
                <a href="${mapsUrl}" target="_blank" rel="noopener" class="nb-card-btn nb-btn-map">
                    <i class="ti ti-navigation"></i> Directions
                </a>
                ${phone   ? `<a href="tel:${escHtml(phone)}" class="nb-card-btn nb-btn-call"><i class="ti ti-phone"></i> Call</a>` : ''}
                ${website ? `<a href="${escHtml(website)}" target="_blank" rel="noopener" class="nb-card-btn nb-btn-web"><i class="ti ti-world"></i> Website</a>` : ''}
            </div>
        `;
        grid.appendChild(card);
    });
}

/* ── Map (Leaflet) ───────────────────────────────────────── */
let leafletMap     = null, leafletMapFull = null;
let markersLayer   = null, markersLayerFull = null;
let lastItems = [], lastMapLat = 0, lastMapLng = 0;

function updateMap(lat, lng, items) {
    lastItems = items; lastMapLat = lat; lastMapLng = lng;

    document.getElementById('nbMapSection').style.display = 'block';

    if (!leafletMap) {
        leafletMap = L.map('nbLeafletMap', { scrollWheelZoom: true }).setView([lat, lng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(leafletMap);
    } else {
        leafletMap.setView([lat, lng], 14);
        if (markersLayer) markersLayer.clearLayers();
    }

    markersLayer = L.layerGroup().addTo(leafletMap);
    addMarkers(markersLayer, lat, lng, items);
    setTimeout(() => leafletMap.invalidateSize(), 100);
}

function addMarkers(layer, lat, lng, items) {
    const { color, icon: tiIcon } = typeMap[activeType];

    /* User pin */
    L.circleMarker([lat, lng], {
        radius: 9, color: '#F97447', fillColor: '#F97447',
        fillOpacity: 0.9, weight: 3
    }).bindPopup('<strong>📍 You are here</strong>').addTo(layer);

    items.forEach(item => {
        const iLat = item.lat || item.center?.lat;
        const iLng = item.lon || item.center?.lon;
        if (!iLat || !iLng) return;

        const name    = item.tags?.name || 'Unnamed';
        const address = buildAddress(item.tags);
        const phone   = item.tags?.phone || item.tags?.['contact:phone'] || null;
        const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${iLat},${iLng}`;

        const markerIcon = L.divIcon({
            className: '',
            html: `<div style="background:${color};width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(0,0,0,0.22);border:2px solid #fff;">
                       <i class="ti ${tiIcon}" style="font-size:16px;color:#fff;"></i>
                   </div>`,
            iconSize: [34, 34], iconAnchor: [17, 17]
        });

        L.marker([iLat, iLng], { icon: markerIcon })
            .bindPopup(`
                <strong style="font-size:13px;">${escHtml(name)}</strong><br>
                ${address ? `<span style="color:#888;font-size:11px;">${escHtml(address)}</span><br>` : ''}
                ${phone   ? `<span style="font-size:11px;"><i class="ti ti-phone"></i> ${escHtml(phone)}</span><br>` : ''}
                <a href="${mapsUrl}" target="_blank" style="font-size:11px;color:#F97447;font-weight:700;">
                    <i class="ti ti-navigation"></i> Directions
                </a>
            `)
            .addTo(layer);
    });
}

function openMapFullscreen() {
    const overlay = document.getElementById('nbMapOverlay');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';

    if (!leafletMapFull) {
        leafletMapFull = L.map('nbMapFull', { scrollWheelZoom: true });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(leafletMapFull);
    }

    setTimeout(() => {
        leafletMapFull.invalidateSize();
        if (leafletMap) leafletMapFull.setView(leafletMap.getCenter(), leafletMap.getZoom());
        if (markersLayerFull) markersLayerFull.clearLayers();
        markersLayerFull = L.layerGroup().addTo(leafletMapFull);
        addMarkers(markersLayerFull, lastMapLat, lastMapLng, lastItems);
    }, 60);
}

function closeMapFullscreen() {
    document.getElementById('nbMapOverlay').classList.remove('active');
    document.body.style.overflow = '';
    setTimeout(() => leafletMap && leafletMap.invalidateSize(), 60);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMapFullscreen(); });

/* ── Helpers ─────────────────────────────────────────────── */
function buildAddress(tags) {
    if (!tags) return '';
    return [tags['addr:housenumber'], tags['addr:street'], tags['addr:city'], tags['addr:state']]
        .filter(Boolean).join(', ');
}
function haversine(lat1, lng1, lat2, lng2) {
    const R = 6371, dLat = (lat2-lat1)*Math.PI/180, dLng = (lng2-lng1)*Math.PI/180;
    const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Status display ──────────────────────────────────────── */
function hideIdleGrid() {
    document.getElementById('nbIdleGrid').style.display = 'none';
}
function showStatusMsg(iconClass, msg) {
    document.getElementById('nbStatus').style.display = 'flex';
    document.getElementById('nbStatusMsg').style.display = 'flex';
    document.getElementById('nbStatusIcon').className = 'ti ' + iconClass;
    document.getElementById('nbStatusText').textContent = msg;
}
function hideStatusMsg() {
    document.getElementById('nbStatus').style.display = 'none';
    document.getElementById('nbStatusMsg').style.display = 'none';
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>