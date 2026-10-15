<?php

namespace App\Enums\Concerns;

/** Bantuan umum untuk backed enum: daftar nilai & label tampilan. */
trait DaftarNilai
{
    /** @return array<int, string> nilai mentah, mis. untuk aturan validasi Rule::in() */
    public static function nilai(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
