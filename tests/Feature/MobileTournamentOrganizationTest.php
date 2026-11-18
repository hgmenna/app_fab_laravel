<?php

use App\Livewire\MobileTournamentOrganization;
use App\Models\FiveQuillasCompetition;
use App\Models\Tournament;
use App\Models\User;
use App\Services\FiveQuillasCompetitionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

/** Fixture de presentación: aísla las consultas estadísticas, conserva las acciones reales del organizador. */
class MobileOrganizationHarness extends MobileTournamentOrganization
{
    public static function authorizeResourceAccess(): void {}

    public function mount(int|string $record): void
    {
        $this->record = Tournament::findOrFail($record);
        $this->hydrate();
    }

    public function competition(): ?FiveQuillasCompetition { return null; }

    protected function getViewData(): array
    {
        return ['competition' => null, 'competitionViewRevision' => 'test', 'competitionLayoutVersion' => 'test'];
    }
}

beforeEach(function () {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
    Schema::create('tournaments', function (Blueprint $table) { $table->id(); $table->string('name'); });
    Schema::create('five_quillas_entries', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('competition_id'); $table->string('status'); });
    Schema::create('marker_tournament_links', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('tournament_id'); });
    Tournament::insert(['id' => 1, 'name' => 'Torneo móvil']);
    $this->operator = Mockery::mock(User::class)->makePartial();
    $this->operator->id = 10;
    $this->operator->email_verified_at = now();
    $this->operator->shouldReceive('canAccessTournament')->with('ManageTournamentCompetition', Mockery::on(fn($record) => $record->id === 1))->andReturnTrue();
    auth()->setUser($this->operator);
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    foreach (['five_quillas_matches', 'five_quillas_group_entries', 'five_quillas_groups', 'five_quillas_phases', 'five_quillas_competitions'] as $table) Schema::dropIfExists($table);
    Schema::dropIfExists('marker_tournament_links');
    Schema::dropIfExists('five_quillas_entries');
    Schema::dropIfExists('tournaments');
    Schema::enableForeignKeyConstraints();
});

it('renders mobile organization controls and executes the shared initialize action', function () {
    $competition = new FiveQuillasCompetition;
    $competition->id = 7;
    $service = Mockery::mock(FiveQuillasCompetitionService::class);
    $service->shouldReceive('initialize')->once()->with(Mockery::on(fn($record) => $record->id === 1))->andReturn($competition);
    app()->instance(FiveQuillasCompetitionService::class, $service);

    Livewire::test(MobileOrganizationHarness::class, ['record' => 1])
        ->assertSee('Configuración y publicación')
        ->assertSee('Iniciar organización')
        ->callAction('initialize')
        ->assertHasNoErrors();
});

it('rejects actions when the operator no longer has permission for the tournament', function () {
    $component = Livewire::test(MobileOrganizationHarness::class, ['record' => 1]);
    auth()->forgetUser();
    $component->call('refreshOrganizationForEveryone')->assertForbidden();
});

it('blocks unverified accounts before organization can be mounted', function () {
    $this->operator->email_verified_at = null;
    Livewire::test(MobileOrganizationHarness::class, ['record' => 1])->assertForbidden();
});

/** Competencia de presentación con relaciones precargadas; no modifica torneos reales. */
class MobileOrganizationConfiguredHarness extends MobileOrganizationHarness
{
    public static ?FiveQuillasCompetition $previewCompetition = null;
    public function competition(): ?FiveQuillasCompetition { return static::$previewCompetition; }
    protected function getViewData(): array
    {
        return ['competition' => $this->competition(), 'entriesByPhase' => collect(), 'competitionViewRevision' => 'test', 'competitionLayoutVersion' => \App\Helpers\CompetitionViewVersion::current(organization: true)];
    }
}

it('renders operational zones and the final bracket with scheduling and match controls', function () {
    Schema::dropIfExists('five_quillas_entries');
    (require database_path('migrations/2026_10_17_120000_create_five_quillas_competition_tables.php'))->up();
    $competition = new FiveQuillasCompetition;
    $competition->id = 1;
    $competition->setRelation('entries', new \Illuminate\Database\Eloquent\Collection);
    $phases = new \Illuminate\Database\Eloquent\Collection;
    foreach (['zones', 'bracket'] as $index => $type) {
        $phase = new \App\Models\FiveQuillasPhase(['name' => $type === 'zones' ? 'Zonas' : 'Etapa final', 'phase_type' => $type, 'is_enabled' => true, 'status' => 'ready', 'sort_order' => $index+1, 'points_per_set' => 60]);
        $phase->id = $index+1;
        $phase->setRelation('competition', $competition);
        $phase->setRelation('groups', new \Illuminate\Database\Eloquent\Collection);
        $match = new \App\Models\FiveQuillasMatch(['label' => 'Partido 1', 'round_number' => 1, 'bracket_order' => 1, 'status' => 'pending', 'player_one_entry_id' => 1, 'player_two_entry_id' => 2]);
        $match->id = $index+1;
        foreach (['playerOne', 'playerTwo'] as $number => $relation) {
            $entry = new \App\Models\FiveQuillasEntry;
            $entry->id = $number+1;
            $entry->setRelation('player', new \App\Models\Player(['first_name' => 'Jugador', 'last_name' => $number ? 'Dos' : 'Uno']));
            $match->setRelation($relation, $entry);
        }
        $match->setRelation('phase', $phase);
        $matches = new \Illuminate\Database\Eloquent\Collection([$match]);
        $phase->setRelation('matches', $matches);
        if ($type === 'zones') {
            $group = new \App\Models\FiveQuillasGroup(['name' => 'Zona 1']);
            $group->id = 1;
            $group->setRelation('slot', null);
            $group->setRelation('matches', $matches);
            $group->setRelation('groupEntries', new \Illuminate\Database\Eloquent\Collection);
            $phase->setRelation('groups', new \Illuminate\Database\Eloquent\Collection([$group]));
        }
        $phases->push($phase);
    }
    $competition->setRelation('phases', $phases);
    MobileOrganizationConfiguredHarness::$previewCompetition = $competition;
    $service = Mockery::mock(FiveQuillasCompetitionService::class);
    $service->shouldReceive('qualifiedParticipantRows')->andReturn([]);
    app()->instance(FiveQuillasCompetitionService::class, $service);
    $component = Livewire::test(MobileOrganizationConfiguredHarness::class, ['record' => 1])
        ->assertSee('Configurar formato')
        ->assertSee('Configurar zonas')
        ->assertSee('Zona 1')
        ->assertSee('Cargar o editar resultado')
        ->set('mobilePhase', '2')
        ->assertSee('Preparar / actualizar grilla')
        ->assertSee('Asignar horarios')
        ->assertSee('Cargar o editar resultado');
    $instance = $component->instance();
    $schema = $instance->createZonesAction()->livewire($instance)->getSchema(\Filament\Schemas\Schema::make($instance));
    $repeater = collect($schema->getComponents(withHidden: true))->first(fn($field) => $field instanceof \Filament\Forms\Components\Repeater);
    expect($repeater->getTableColumns())->toBeNull();
    Livewire::component('mobile-tournament-organization', MobileOrganizationConfiguredHarness::class);
    \Illuminate\Support\Facades\Route::get('/mobile-shell-test', fn() => view('mobile-tournament-organization', ['tournament' => Tournament::findOrFail(1), 'localPreview' => true]));
    $this->get('/mobile-shell-test')->assertOk()->assertSee('Organización del torneo');
});




it('blocks an open form when tournament organization permission is revoked', function () {
    $component = Livewire::test(MobileOrganizationHarness::class, ['record' => 1]);
    $restricted = Mockery::mock(User::class)->makePartial();
    $restricted->id = 11;
    $restricted->email_verified_at = now();
    $restricted->shouldReceive('canAccessTournament')->andReturnFalse();
    auth()->setUser($restricted);
    $component->call('mountAction', 'initialize')->assertForbidden();
});
