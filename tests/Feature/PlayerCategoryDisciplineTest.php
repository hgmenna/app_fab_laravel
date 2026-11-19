<?php

use App\Models\Category;
use App\Models\PlayerCategoryHistory;
use App\Models\PlayerCategoryPromotion;
use App\Services\PlayerCategoryDiscipline;
use App\Services\PlayerCategoryHistoryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Schema::create('disciplines', function(Blueprint $t) {$t->id(); $t->string('code'); $t->string('name');});
    Schema::create('categories', function(Blueprint $t) {$t->id(); $t->string('code'); $t->string('name'); $t->unsignedBigInteger('discipline_id');});
    Schema::create('players', function(Blueprint $t) {$t->id(); $t->string('first_name'); $t->string('last_name'); $t->unsignedBigInteger('discipline_id'); $t->unsignedBigInteger('category_id');});
    Schema::create('player_category_histories', function(Blueprint $t) {
        $t->id(); $t->unsignedBigInteger('player_id'); $t->unsignedBigInteger('category_id'); $t->unsignedBigInteger('previous_category_id')->nullable();
        $t->string('source'); $t->string('change_type')->default('ranking'); $t->integer('season')->nullable(); $t->date('effective_date'); $t->dateTime('applied_at')->nullable(); $t->string('reason')->nullable(); $t->text('notes')->nullable(); $t->timestamps();
    });
    Schema::create('player_category_promotions', function(Blueprint $t) {$t->id(); $t->unsignedBigInteger('player_id'); $t->unsignedBigInteger('previous_category_id'); $t->unsignedBigInteger('new_category_id'); $t->timestamps();});
    DB::table('disciplines')->insert([['id'=>1,'code'=>'five_quillas','name'=>'5 Quillas'],['id'=>2,'code'=>'three_cushion','name'=>'3 Bandas']]);
    DB::table('categories')->insert([
        ['id'=>1,'code'=>'P','name'=>'Primera','discipline_id'=>1], ['id'=>2,'code'=>'M','name'=>'Master','discipline_id'=>1],
        ['id'=>3,'code'=>'N','name'=>'Nacional','discipline_id'=>1], ['id'=>4,'code'=>'M','name'=>'Máxima','discipline_id'=>2],
    ]);
    DB::table('players')->insert([['id'=>1,'first_name'=>'Ana','last_name'=>'Quillas','discipline_id'=>1,'category_id'=>1],['id'=>2,'first_name'=>'Beto','last_name'=>'Bandas','discipline_id'=>2,'category_id'=>4]]);
    Storage::fake('local');
});

afterEach(function () {foreach(['player_category_promotions','player_category_histories','players','categories','disciplines'] as $table) Schema::dropIfExists($table);});

function insertCategoryHistory(int $id, int $previous, int $category, string $source = 'ranking'): void {
    DB::table('player_category_histories')->insert(['id'=>$id,'player_id'=>1,'previous_category_id'=>$previous,'category_id'=>$category,'source'=>$source,'change_type'=>'ranking','season'=>2026,'effective_date'=>'2026-11-'.str_pad((string)$id,2,'0',STR_PAD_LEFT),'applied_at'=>'2026-11-18 10:00:00']);
}

it('resolves repeated category codes only within the requested discipline', function () {
    expect(PlayerCategoryDiscipline::categories(1)->get('M')->name)->toBe('Master')
        ->and(PlayerCategoryDiscipline::categories(2)->get('M')->name)->toBe('Máxima');
});

it('blocks cross-discipline histories and pending promotions at the model boundary', function () {
    expect(fn() => PlayerCategoryHistory::create(['player_id'=>1,'previous_category_id'=>2,'category_id'=>4,'source'=>'ranking','effective_date'=>'2026-11-18']))->toThrow(ValidationException::class);
    expect(fn() => PlayerCategoryPromotion::create(['player_id'=>1,'previous_category_id'=>1,'new_category_id'=>4]))->toThrow(ValidationException::class);
    expect(PlayerCategoryHistory::count())->toBe(0)->and(PlayerCategoryPromotion::count())->toBe(0);
});

it('ignores invalid legacy categories when resolving the effective ranking state', function () {
    insertCategoryHistory(1,1,2); insertCategoryHistory(2,2,4);
    $service = app(PlayerCategoryHistoryService::class);
    expect($service->getEffectiveCategory(\App\Models\Player::find(1))->name)->toBe('Master');
    PlayerCategoryHistory::create(['player_id'=>2,'category_id'=>4,'source'=>'affiliation','change_type'=>'affiliation','effective_date'=>'2026-11-18','applied_at'=>now()]);
    expect($service->getPlayersWithEffectiveTemporaryRankingCategory()->all())->toBe([1]);
});

it('audits without modifying data and repairs unambiguous ranking errors with a recoverable backup', function () {
    insertCategoryHistory(1,1,2); insertCategoryHistory(2,2,4); insertCategoryHistory(3,4,3);
    $this->artisan('player-categories:audit-disciplines')->assertSuccessful();
    expect(PlayerCategoryHistory::count())->toBe(3)->and(Storage::disk('local')->allFiles())->toBe([]);
    $this->artisan('player-categories:audit-disciplines', ['--repair-ranking'=>true])->assertSuccessful();
    expect(PlayerCategoryHistory::find(2))->toBeNull()->and((int)PlayerCategoryHistory::find(3)->previous_category_id)->toBe(2)
        ->and((int)PlayerCategoryHistory::find(3)->category_id)->toBe(3)->and((int)\App\Models\Player::find(1)->category_id)->toBe(1);
    $files = Storage::disk('local')->allFiles('repairs');
    expect($files)->toHaveCount(1);
    $backup = json_decode(Storage::disk('local')->get($files[0]), true);
    expect($backup['records'])->toHaveCount(2)->and($backup['records'][0]['before']['category_id'])->toBe(4);
});

it('leaves manual histories and ambiguous mappings unchanged during automatic repair', function () {
    insertCategoryHistory(1,2,4,'manual');
    $this->artisan('player-categories:audit-disciplines', ['--repair-ranking'=>true])->assertSuccessful();
    expect((int)PlayerCategoryHistory::find(1)->category_id)->toBe(4)->and(Storage::disk('local')->allFiles())->toBe([]);
});
