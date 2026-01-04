<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Discipline;

class DisciplineSeeder extends Seeder
{
    public function run(): void
    {
        // Si ya existe, solo actualiza el código
        Discipline::updateOrCreate(
            ['name' => '5 Quillas'],
            [
                'short_name' => '5Q',
                'description' => 'Disciplina oficial del circuito argentino de billar 5 Quillas.',
                'code' => 'five_quillas',
                'active' => true,
            ]
        );

        // Si querés agregar más disciplinas, podés hacerlo acá:
        // Discipline::updateOrCreate(
        //     ['name' => 'Pool Bola 8'],
        //     ['short_name' => 'B8', 'code' => 'pool_8', 'active' => true]
        // );
    }
}
