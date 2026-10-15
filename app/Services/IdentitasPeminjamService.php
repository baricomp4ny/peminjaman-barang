<?php

namespace App\Services;

use App\Enums\Peran;
use App\Enums\StatusAkun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Peminjam tidak punya akun login. Identitasnya diisi di form ajuan publik
 * dan dicocokkan dengan data tersimpan berdasarkan NOMOR IDENTITAS (NIM/NIP).
 *
 * Aturan keamanan:
 *  - NIM sudah terdaftar → email harus sama dengan yang tersimpan. Ini mencegah orang lain
 *    mengajukan atas nama NIM tertentu lalu mengalihkan email notifikasinya.
 *  - NIM baru → email tidak boleh dipakai orang lain.
 *  - Peminjam yang dinonaktifkan admin tidak bisa mengajukan.
 * Verifikasi identitas akhir tetap oleh admin saat serah-terima (cek kartu identitas asli).
 */
class IdentitasPeminjamService
{
    /** Cari peminjam berdasarkan NIM (tanpa membuat). */
    public function cari(string $nomorIdentitas): ?User
    {
        return User::where('nomor_identitas', trim($nomorIdentitas))->first();
    }

    /**
     * Periksa identitas yang diisi di form.
     *
     * @param  array{name:string, nomor_identitas:string, email:string, unit?:?string, no_hp?:?string}  $data
     * @return array<string, string> field => pesan error (kosong bila valid)
     */
    public function periksa(array $data): array
    {
        $email = Str::lower(trim($data['email']));
        $user = $this->cari($data['nomor_identitas']);

        if ($user) {
            if (! $user->isPeminjam()) {
                return ['nomor_identitas' => 'Nomor identitas ini tidak bisa dipakai untuk mengajukan peminjaman.'];
            }
            if (! $user->isAktif()) {
                return ['nomor_identitas' => 'Data peminjam dengan nomor identitas ini dinonaktifkan. Hubungi admin.'];
            }
            if (Str::lower($user->email) !== $email) {
                return ['email' => 'Nomor identitas ini sudah terdaftar dengan email lain ('.$this->samarkan($user->email).'). Gunakan email tersebut atau hubungi admin untuk mengubahnya.'];
            }

            return [];
        }

        if (User::where('email', $email)->exists()) {
            return ['email' => 'Email ini sudah dipakai oleh peminjam dengan nomor identitas lain.'];
        }

        return [];
    }

    /**
     * Ambil peminjam lama (memperbarui unit & no. HP) atau buat data peminjam baru.
     * Panggil setelah periksa() lolos.
     */
    public function cariAtauBuat(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::where('nomor_identitas', trim($data['nomor_identitas']))->lockForUpdate()->first();

            if ($user) {
                // Nama & email tidak diubah dari form publik; kontak lain boleh diperbarui
                $user->fill(array_filter([
                    'unit' => $data['unit'] ?? null,
                    'no_hp' => $data['no_hp'] ?? null,
                ]))->save();

                return $user;
            }

            $user = new User([
                'name' => trim($data['name']),
                'email' => Str::lower(trim($data['email'])),
                'nomor_identitas' => trim($data['nomor_identitas']),
                'unit' => $data['unit'] ?? null,
                'no_hp' => $data['no_hp'] ?? null,
            ]);
            // role & status tidak ada di $fillable; peminjam tanpa password (tidak bisa login)
            $user->forceFill(['role' => Peran::Peminjam, 'status' => StatusAkun::Aktif, 'password' => null])->save();

            return $user;
        });
    }

    /** a***i@gmail.com — supaya email tidak terbuka penuh di halaman publik. */
    public function samarkan(string $email): string
    {
        [$nama, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $tampil = mb_strlen($nama) <= 2 ? mb_substr($nama, 0, 1).'*' : mb_substr($nama, 0, 1).str_repeat('*', 3).mb_substr($nama, -1);

        return $tampil.'@'.$domain;
    }
}
