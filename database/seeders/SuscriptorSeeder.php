<?php

namespace Database\Seeders;

use App\Models\Suscriptor;
use Illuminate\Database\Seeder;

class SuscriptorSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['ana@ejemplo.com', 'carlos@ejemplo.com', 'diana@ejemplo.com'] as $email) {
            Suscriptor::updateOrCreate(['email' => $email], ['baja_en' => null]);
        }
    }
}
