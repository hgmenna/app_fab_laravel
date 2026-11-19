<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Discipline;
use App\Models\Federation;
use App\Models\TournamentType;
use Illuminate\Http\JsonResponse;

/** Actualiza navegación, permisos visibles y opciones de búsqueda sin volver a cargar formularios. */
class MobileAppStateController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'header' => view('partials.mobile-header')->render(),
            'filters' => [
                'disciplina' => Discipline::query()->orderBy('name')->get(['id', 'name']),
                'tipo' => TournamentType::query()->orderBy('name')->get(['id', 'name']),
                'categoria' => Category::query()->orderBy('name')->get(['id', 'name', 'discipline_id']),
                'federacion' => Federation::query()->orderBy('name')->get(['id', 'name']),
            ],
        ])->header('Cache-Control', 'private, no-store');
    }
}
