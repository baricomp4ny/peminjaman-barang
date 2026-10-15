<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_awal_adalah_katalog_publik(): void
    {
        $this->get('/')->assertOk()->assertSee('Katalog Alat')->assertSee(config('peminjaman.mitra_singkat'))->assertSee('Login Admin');
    }
}
