import './bootstrap';

import Alpine from 'alpinejs';
import halamanScan from './scan-qr';
import potongFoto from './potong-foto';

window.Alpine = Alpine;

// Komponen halaman Scan QR harus terdaftar sebelum Alpine.start()
Alpine.data('halamanScan', halamanScan);
Alpine.data('potongFoto', potongFoto);

Alpine.start();
