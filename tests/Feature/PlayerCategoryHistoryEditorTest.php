<?php

use App\Models\Player;
use App\Models\PlayerCategoryHistory;
use App\Models\User;
use App\Policies\PlayerCategoryHistoryPolicy;
use App\Services\PlayerCategoryHistoryEditor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-18 12:00:00'));
    Schema::create('categories', function(Blueprint $t) {$t->id(); $t->string('name'); $t->string('code'); $t->unsignedBigInteger('discipline_id');});
    Schema::create('players', function(Blueprint $t) {$t->id(); $t->unsignedBigInteger('discipline_id'); $t->unsignedBigInteger('category_id'); $t->unsignedBigInteger('club_id')->nullable(); $t->string('first_name'); $t->string('last_name'); $t->timestamps();});
    Schema::create('player_category_histories', function(Blueprint $t) {
        $t->id(); $t->unsignedBigInteger('player_id'); $t->unsignedBigInteger('category_id'); $t->unsignedBigInteger('previous_category_id')->nullable();
        $t->string('source'); $t->string('change_type'); $t->integer('season')->nullable(); $t->date('effective_date')->nullable(); $t->dateTime('applied_at')->nullable();
        $t->unsignedBigInteger('tournament_id')->nullable(); $t->unsignedBigInteger('ranking_id')->nullable(); $t->string('reason')->nullable(); $t->text('notes')->nullable(); $t->timestamps();
    });
    foreach ([[1,'T',1],[2,'S',1],[3,'P',1],[4,'M',1],[5,'T',2]] as [$id,$code,$discipline]) DB::table('categories')->insert(['id'=>$id,'code'=>$code,'name'=>'Categoría '.$id,'discipline_id'=>$discipline]);
    DB::table('players')->insert(['id'=>1,'discipline_id'=>1,'category_id'=>1,'club_id'=>8,'first_name'=>'Ana','last_name'=>'Prueba']);
    $user = Mockery::mock(User::class)->makePartial(); $user->id = 10;
    $user->shouldReceive('canAccessClubInDiscipline')->andReturnTrue(); auth()->setUser($user);
    Gate::shouldReceive('authorize')->andReturn(\Illuminate\Auth\Access\Response::allow());
    $this->editor = app(PlayerCategoryHistoryEditor::class);
});

afterEach(function () {Schema::dropIfExists('player_category_histories'); Schema::dropIfExists('players'); Schema::dropIfExists('categories'); $this->travelBack();});

it('creates, edits and deletes a change while restoring the actual player category', function () {
    $history = $this->editor->create(Player::findOrFail(1), ['category_id'=>2,'effective_date'=>'2026-11-19','reason'=>'Ascenso']);
    expect(Player::find(1)->category_id)->toBe(1)->and($history->applied_at)->toBeNull();
    $history = $this->editor->update($history, ['category_id'=>3,'effective_date'=>'2026-11-18','reason'=>'Corrección']);
    expect(Player::find(1)->category_id)->toBe(3)->and($history->applied_at)->not->toBeNull();
    $this->editor->delete($history);
    expect(Player::find(1)->category_id)->toBe(1)->and(PlayerCategoryHistory::count())->toBe(0);
});

it('repairs subsequent category origins when an earlier applied change is corrected or deleted', function () {
    $one = $this->editor->create(Player::find(1), ['category_id'=>2,'effective_date'=>'2026-11-10','reason'=>'Primero']);
    $two = $this->editor->create(Player::find(1), ['category_id'=>3,'effective_date'=>'2026-11-15','reason'=>'Segundo']);
    $this->editor->update($one, ['category_id'=>3,'effective_date'=>'2026-11-10','reason'=>'Corregido']);
    expect((int)$two->fresh()->previous_category_id)->toBe(3)->and(Player::find(1)->category_id)->toBe(3);
    $this->editor->delete($one->fresh());
    expect((int)$two->fresh()->previous_category_id)->toBe(1)->and(Player::find(1)->category_id)->toBe(3);
    $this->editor->delete($two->fresh());
    expect(Player::find(1)->category_id)->toBe(1);
});

it('reverts an applied change moved to the future and applies it again on its due date', function () {
    $history = $this->editor->create(Player::find(1), ['category_id'=>2,'effective_date'=>'2026-11-18','reason'=>'Cambio']);
    $history = $this->editor->update($history, ['category_id'=>2,'effective_date'=>'2026-11-19','reason'=>'Fecha corregida']);
    expect(Player::find(1)->category_id)->toBe(1)->and($history->applied_at)->toBeNull();
    $this->travel(1)->days();
    expect(\App\Services\PlayerCategoryChangeService::applyDueChanges())->toBe(1)->and(Player::find(1)->category_id)->toBe(2);
});

it('rejects temporary and other-discipline categories without changing the player or history', function () {
    foreach ([4,5] as $category) expect(fn() => $this->editor->create(Player::find(1), ['category_id'=>$category,'effective_date'=>'2026-11-18','reason'=>'Erróneo']))->toThrow(ValidationException::class);
    expect(PlayerCategoryHistory::count())->toBe(0)->and(Player::find(1)->category_id)->toBe(1);
});

it('requires player update or delete permission together with EditField in the correct club and discipline', function () {
    $history = new PlayerCategoryHistory(['source'=>'manual']); $history->setRelation('player', Player::find(1));
    $user = Mockery::mock(User::class)->makePartial();
    $user->shouldReceive('canAccessClubInDiscipline')->with('EditField',1,8)->andReturnTrue();
    $user->shouldReceive('canAccessClubInDiscipline')->with('Update:Player',1,8)->andReturnTrue();
    $user->shouldReceive('canAccessClubInDiscipline')->with('Delete:Player',1,8)->andReturnFalse();
    $user->shouldReceive('can')->with('update', Mockery::type(Player::class))->andReturnTrue();
    $user->shouldReceive('can')->with('delete', Mockery::type(Player::class))->andReturnFalse();
    $policy = new PlayerCategoryHistoryPolicy;
    expect($policy->update($user,$history))->toBeTrue()->and($policy->delete($user,$history))->toBeFalse();
    $history->source = 'ranking'; expect($policy->update($user,$history))->toBeFalse();
});

/** Componente de prueba: abre el mismo formulario que se publica en el informe. */
class CategoryHistoryActionHarness extends \Livewire\Component implements \Filament\Actions\Contracts\HasActions, \Filament\Schemas\Contracts\HasSchemas
{
    use \Filament\Actions\Concerns\InteractsWithActions;
    use \Filament\Schemas\Concerns\InteractsWithSchemas;
    public function editCategoryChangeAction(): \Filament\Actions\Action {return \App\Filament\Actions\PlayerCategoryHistoryActions::edit()->record(PlayerCategoryHistory::first());}
    public function render() {return '<div>{{ $this->editCategoryChangeAction }}<x-filament-actions::modals /></div>';}
}

it('opens and saves the actual category correction modal', function () {
    config(['app.key'=>'base64:'.base64_encode(str_repeat('a',32))]);
    Schema::table('categories', fn(Blueprint $table) => $table->integer('order')->default(0));
    Gate::shouldReceive('allows')->andReturnTrue();
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
    \Filament\Facades\Filament::bootCurrentPanel();
    $history = $this->editor->create(Player::find(1), ['category_id'=>2,'effective_date'=>'2026-11-18','reason'=>'Original']);
    \Livewire\Livewire::test(CategoryHistoryActionHarness::class)
        ->mountAction('editCategoryChange')
        ->setActionData(['category_id'=>3,'effective_date'=>'2026-11-18','reason'=>'Corregido desde formulario','notes'=>''])
        ->callMountedAction()
        ->assertHasNoActionErrors();
    expect((int)$history->fresh()->category_id)->toBe(3)->and(Player::find(1)->category_id)->toBe(3);
});
