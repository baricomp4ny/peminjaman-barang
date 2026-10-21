/**
 * Grafik Dashboard admin (Chart.js, lewat Vite — bukan CDN).
 * Chart.js dimuat terpisah (dynamic import) hanya saat Dashboard dibuka, supaya halaman lain tetap ringan.
 * Satu seri per grafik → satu warna Marian, tanpa legenda; angka lengkap ada di tooltip & tabel.
 */
const BIRU = '#263C92'; // marian-800
const GRID = '#EEF1FB'; // marian-50, garis bantu samar
const TEKS = '#4863C4'; // marian-500

function opsi(horizontal, satuan) {
    const sumbuNilai = { beginAtZero: true, ticks: { precision: 0, color: TEKS }, grid: { color: GRID }, border: { display: false } };
    const sumbuLabel = { ticks: { color: TEKS }, grid: { display: false }, border: { display: false } };

    return {
        indexAxis: horizontal ? 'y' : 'x',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#131E4A',
                padding: 10,
                displayColors: false,
                callbacks: { label: (ctx) => `${horizontal ? ctx.parsed.x : ctx.parsed.y} ${satuan}` },
            },
        },
        scales: horizontal ? { x: sumbuNilai, y: sumbuLabel } : { x: sumbuLabel, y: sumbuNilai },
    };
}

function batang(nilai) {
    // Batang tipis dengan ujung data membulat 4px
    return [{ data: nilai, backgroundColor: BIRU, hoverBackgroundColor: '#1E2F72', borderRadius: 4, borderSkipped: 'start', maxBarThickness: 28 }];
}

export default (data) => ({
    async init() {
        const { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip } = await import('chart.js');
        Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);

        new Chart(this.$refs.bulan, {
            type: 'bar',
            data: { labels: data.bulan.label, datasets: batang(data.bulan.nilai) },
            options: opsi(false, 'transaksi'),
        });

        if (this.$refs.populer) {
            new Chart(this.$refs.populer, {
                type: 'bar',
                data: { labels: data.populer.label, datasets: batang(data.populer.nilai) },
                options: opsi(true, 'unit'),
            });
        }
    },
});
