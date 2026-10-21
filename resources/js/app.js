import './bootstrap';

import Alpine from 'alpinejs';
import halamanScan from './scan-qr';
import potongFoto from './potong-foto';
import grafikDashboard from './grafik-dashboard';

window.Alpine = Alpine;

// Komponen halaman Scan QR harus terdaftar sebelum Alpine.start()
Alpine.data('halamanScan', halamanScan);
Alpine.data('potongFoto', potongFoto);
Alpine.data('grafikDashboard', grafikDashboard);

Alpine.start();
