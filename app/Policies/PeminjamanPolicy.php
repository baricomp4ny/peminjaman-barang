<?php

namespace App\Policies;

use App\Models\Peminjaman;
use App\Models\User;

class PeminjamanPolicy
{
    /** Admin melihat semua; peminjam hanya miliknya sendiri. */
    public function view(User $user, Peminjaman $peminjaman): bool
    {
        return $user->isAdmin() || $peminjaman->user_id === $user->id;
    }

    public function batalkan(User $user, Peminjaman $peminjaman): bool
    {
        return $peminjaman->user_id === $user->id;
    }
}
