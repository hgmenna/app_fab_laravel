<?php

use App\Filament\Resources\Tournaments\Pages\ListTournaments;
use App\Filament\Resources\Tournaments\Tables\TournamentsTable;
use App\Models\Tournament;
use Filament\Tables\Table;

it('filters tournament types together with their official condition', function () {
    $filters = TournamentsTable::configure(Table::make(new ListTournaments))->getFilters();

    // Verifica la consulta sin ejecutar lecturas ni modificar torneos de la base de datos.
    foreach (['official' => true, 'non_official' => false] as $value => $expected) {
        $query = $filters['official']->apply(Tournament::query(), ['value' => $value]);
        expect($query->toSql())->toContain('is_official')
            ->and($query->getBindings())->toBe([$expected]);
    }

    $query = $filters['type_id']->apply(Tournament::query(), ['values' => [2, 5]]);
    $query = $filters['official']->apply($query, ['value' => 'non_official']);
    expect($filters['type_id']->isMultiple())->toBeTrue()
        ->and($query->toSql())->toContain(' in ', 'is_official')
        ->and($query->getBindings())->toBe([2, 5, false]);

    $query = $filters['type_id']->apply(Tournament::query(), ['values' => []]);
    $query = $filters['official']->apply($query, ['value' => null]);
    expect($query->getBindings())->toBe([]);
});
