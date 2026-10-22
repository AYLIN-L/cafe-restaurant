<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        $tables = [
            ['number' => 1, 'capacity' => 2, 'is_active' => true],
            ['number' => 2, 'capacity' => 2, 'is_active' => true],
            ['number' => 3, 'capacity' => 2, 'is_active' => true],
            ['number' => 4, 'capacity' => 2, 'is_active' => true],
            ['number' => 5, 'capacity' => 4, 'is_active' => true],
            ['number' => 6, 'capacity' => 4, 'is_active' => true],
            ['number' => 7, 'capacity' => 4, 'is_active' => true],
            ['number' => 8, 'capacity' => 4, 'is_active' => true],
            ['number' => 9, 'capacity' => 6, 'is_active' => true],
            ['number' => 10, 'capacity' => 6, 'is_active' => true],
        ];

        foreach ($tables as $table) {
            Table::firstOrCreate(
                ['number' => $table['number']],
                ['capacity' => $table['capacity'], 'is_active' => $table['is_active']]
            );
        }
    }
}
