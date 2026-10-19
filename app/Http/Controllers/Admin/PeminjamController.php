<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Peran;
use App\Enums\StatusAkun;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PeminjamRequest;
use App\Models\User;
use App\Services\KeterlambatanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Data peminjam. Parameter {peminjam} hanya cocok dengan user ber-role peminjam (lihat RouteServiceProvider). */
class PeminjamController extends Controller
{
    public function index(Request $request): View
    {
        $peminjam = User::peminjam()
            ->withCount('peminjaman')
            ->withJumlahTerlambat()
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->q.'%')
                    ->orWhere('email', 'like', '%'.$request->q.'%')
                    ->orWhere('nomor_identitas', 'like', '%'.$request->q.'%');
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.peminjam.index', compact('peminjam'));
    }

    public function create(): View
    {
        return view('admin.peminjam.form', ['peminjam' => (new User)->forceFill(['status' => StatusAkun::Aktif])]);
    }

    public function store(PeminjamRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $peminjam = new User(collect($data)->except('status')->all());
        // role & status tidak ada di $fillable; peminjam tanpa password (tidak login)
        $peminjam->forceFill([
            'password' => null,
            'role' => Peran::Peminjam,
            'status' => $data['status'],
        ])->save();

        return redirect()->route('admin.peminjam.index')
            ->with('success', "Peminjam \"{$peminjam->name}\" berhasil ditambahkan.");
    }

    public function show(User $peminjam, KeterlambatanService $keterlambatan): View
    {
        $riwayat = $peminjam->peminjaman()
            ->with('detail.barang')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.peminjam.show', [
            'peminjam' => $peminjam,
            'riwayat' => $riwayat,
            'jumlahTerlambat' => User::whereKey($peminjam->id)->withJumlahTerlambat()->value('jumlah_terlambat') ?? 0,
            'penangguhanBerakhir' => $keterlambatan->penangguhanBerakhir($peminjam),
        ]);
    }

    public function edit(User $peminjam): View
    {
        return view('admin.peminjam.form', compact('peminjam'));
    }

    public function update(PeminjamRequest $request, User $peminjam): RedirectResponse
    {
        $data = $request->validated();

        $peminjam->fill(collect($data)->except('status')->all());
        $peminjam->forceFill(['status' => $data['status']])->save();

        return redirect()->route('admin.peminjam.index')
            ->with('success', "Data peminjam \"{$peminjam->name}\" berhasil diperbarui.");
    }

    public function destroy(User $peminjam): RedirectResponse
    {
        // Peminjam dengan riwayat tidak dihapus agar laporan tetap utuh; cukup dinonaktifkan
        if ($peminjam->peminjaman()->exists()) {
            return back()->with('error', "Peminjam \"{$peminjam->name}\" tidak bisa dihapus karena memiliki riwayat peminjaman. Nonaktifkan akunnya saja.");
        }

        $peminjam->delete();

        return redirect()->route('admin.peminjam.index')
            ->with('success', "Peminjam \"{$peminjam->name}\" berhasil dihapus.");
    }
}
