<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Hapus banyak data sekaligus dari checkbox "pilih banyak".
 * Aturan boleh-tidaknya dihapus memakai alasanTidakBisaDihapus() di model (sama dengan hapus satuan);
 * data yang tidak boleh dihapus dilewati dan disebutkan di pesan hasil.
 */
trait HapusBanyak
{
    /**
     * @param  Builder  $query  data yang boleh dipilih (mis. hanya role peminjam)
     * @param  string  $sebutan  kata benda untuk pesan, mis. "unit", "kategori"
     * @param  Closure(Model): string  $nama  label tiap data di pesan
     * @param  Closure(Model): ?string  $alasan  alasan tidak bisa dihapus, null = boleh
     * @param  Closure(Model): void|null  $sebelumHapus  mis. hapus file foto
     */
    protected function hapusBanyak(Request $request, Builder $query, string $sebutan, Closure $nama, Closure $alasan, ?Closure $sebelumHapus = null): RedirectResponse
    {
        $id = $request->validate([
            'id' => ['required', 'array', 'min:1', 'max:100'],
            'id.*' => ['required', 'ulid'],
        ], [
            'id.required' => 'Pilih minimal satu data yang akan dihapus.',
        ])['id'];

        [$dihapus, $dilewati] = DB::transaction(function () use ($query, $id, $nama, $alasan, $sebelumHapus) {
            $dihapus = 0;
            $dilewati = [];

            foreach ($query->whereKey(array_unique($id))->lockForUpdate()->get() as $model) {
                // Dicek satu per satu karena hasil hapus sebelumnya bisa memengaruhi (mis. admin aktif terakhir)
                if ($kenapa = $alasan($model)) {
                    $dilewati[] = $nama($model).' ('.$kenapa.')';

                    continue;
                }

                $sebelumHapus?->__invoke($model);
                $model->delete();
                $dihapus++;
            }

            return [$dihapus, $dilewati];
        });

        $pesan = $dihapus ? "{$dihapus} {$sebutan} berhasil dihapus." : "Tidak ada {$sebutan} yang dihapus.";
        if ($dilewati) {
            $pesan .= ' '.count($dilewati).' dilewati: '.implode('; ', $dilewati).'.';
        }

        return back()->with($dihapus ? 'success' : 'error', $pesan);
    }
}
