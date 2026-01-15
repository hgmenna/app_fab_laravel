<?php

namespace App\Imports;

use App\Models\Player;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\Club;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PlayersImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        Log::info("Row keys:", array_keys($row));
   
        // Normalizar textos
        $clubName = trim($row['club']);
        $categoryName = trim($row['category']);
        
        // Buscar club por nombre
        $club = Club::where('name', 'LIKE', "%{$clubName}%")->first();
        //$club = Club::where('name', 'LIKE', $clubName)->first();
        
        // Buscar categoría por nombre
        $category = Category::where('name', 'LIKE', "%{$categoryName}%")->first();
        //$category = Category::where('name', 'LIKE', $categoryName)->first();
        

        if (! $club) {
            Log::warning("Club no encontrado: {$clubName}");
        }
        if (! $category) {
            Log::warning("Categoría no encontrada: {$categoryName}");
        }

        // Si no existe club o categoría, no creamos nada
        if (!$club || !$category) {
            return null;
        }

        return new Player([
            'first_name'  => $row['first_name'],
            'last_name'   => $row['last_name'],
            'club_id'     => $club->id,
            'category_id' => $category->id,
            'is_active'   => true,
        ]);
    }

    public function rules(): array
    {
        return [
            '*.first_name' => ['required'],
            '*.last_name'  => ['required'],
            '*.club'       => ['required'],
            '*.category'   => ['required'],
        ];
    }


}
