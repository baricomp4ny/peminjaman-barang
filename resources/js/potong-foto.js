import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

/**
 * Potong & atur foto sebelum diunggah (form Jenis Barang).
 * Foto dipotong di browser lalu hasilnya menggantikan file di input,
 * sehingga server tetap menerima satu file gambar biasa.
 */
export default function potongFoto() {
    return {
        cropper: null,
        rasio: 1, // 1 = persegi (sesuai kartu katalog), 4/3, atau NaN = bebas
        zoom: 0,

        pilih(event) {
            const file = event.target.files[0];
            this.hapusCropper();
            if (!file || !file.type.startsWith('image/')) return;

            this.$refs.gambar.src = URL.createObjectURL(file);
            this.$refs.gambar.onload = () => {
                this.cropper = new Cropper(this.$refs.gambar, {
                    aspectRatio: this.rasio,
                    viewMode: 1,
                    autoCropArea: 1,
                    background: false,
                    zoom: (e) => { this.zoom = e.detail.ratio; },
                });
            };
        },

        aturRasio(nilai) {
            this.rasio = nilai;
            this.cropper?.setAspectRatio(nilai);
        },

        putar(derajat) { this.cropper?.rotate(derajat); },
        perbesar(langkah) { this.cropper?.zoom(langkah); },
        ulang() { this.cropper?.reset(); },

        hapusCropper() {
            this.cropper?.destroy();
            this.cropper = null;
        },

        // Saat form dikirim: ganti file asli dengan hasil potongan (maks 1200 px, JPEG kualitas 0.9)
        kirim(event) {
            if (!this.cropper) return;
            event.preventDefault();
            const form = event.target;
            const kanvas = this.cropper.getCroppedCanvas({ maxWidth: 1200, maxHeight: 1200, fillColor: '#fff', imageSmoothingQuality: 'high' });

            kanvas.toBlob((blob) => {
                const berkas = new File([blob], 'foto.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(berkas);
                this.$refs.input.files = dt.files;
                this.hapusCropper();
                form.submit();
            }, 'image/jpeg', 0.9);
        },
    };
}
