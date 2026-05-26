<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * RolesAndPermissionsSeeder roda sempre (necessário em produção também).
     * DevelopmentSeeder roda apenas em ambiente local.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        if (app()->isLocal()) {
            $this->call(DevelopmentSeeder::class);
        }
    }
}
