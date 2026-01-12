import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';
import collapse from '@alpinejs/collapse';
Alpine.plugin(intersect);
Alpine.plugin(collapse);
window.Alpine = Alpine;

// ===== Auto-Generated Imports =====
import '@components/customers/customers.js';
import '@components/dashboardTiles/dashboard_tiles.js';
import '@components/leveranciers/leveranciers.js';
import '@components/leveringen/leveringen.js';
import '@components/login/login.js';
import '@components/pakket-samenstellen/pakket-samenstellen.js';
// ===== End Auto-Generated Imports =====

Alpine.start();