<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            // Peminjam tidak punya akun login → tanpa password
            'password' => null,
            'role' => User::ROLE_PEMINJAM,
            'nomor_identitas' => fake()->unique()->numerify('##########'),
            'unit' => fake()->randomElement(['Teknologi Industri Benih', 'Manajemen Agribisnis', 'Analisis Kimia', 'Teknologi Produksi dan Pengembangan Masyarakat Pertanian']),
            'no_hp' => fake()->numerify('08##########'),
            'status' => User::STATUS_AKTIF,
            'remember_token' => null,
        ];
    }

    /** Admin: satu-satunya peran yang login. Password: "password". */
    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_ADMIN,
            // Hash dibuat sekali sesuai konfigurasi aktif (cast 'hashed' menolak cost yang berbeda)
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'nomor_identitas' => null,
            'unit' => 'Pengelola',
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['status' => User::STATUS_NONAKTIF]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return $this
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
