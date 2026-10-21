/**
 * Checkbox "pilih banyak" di tabel admin.
 * ids = id baris yang boleh dipilih di halaman ini (untuk "pilih semua").
 */
export default (ids = []) => ({
    semua: ids,
    dipilih: [],

    get semuaDipilih() {
        return this.semua.length > 0 && this.dipilih.length === this.semua.length;
    },

    get sebagianDipilih() {
        return this.dipilih.length > 0 && ! this.semuaDipilih;
    },

    pilihSemua(centang) {
        this.dipilih = centang ? [...this.semua] : [];
    },

    batal() {
        this.dipilih = [];
    },
});
