<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsuarioSeeder::class,
            EscenarioSeeder::class,
            EventoSeeder::class,
            ReservaSeeder::class,
            SuscriptorSeeder::class,
        ]);
    }
}
