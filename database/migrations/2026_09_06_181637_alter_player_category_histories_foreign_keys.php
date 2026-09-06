<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'player_category_histories';

        $this->dropForeignIfExists(
            $table,
            'player_category_histories_player_id_foreign'
        );

        $this->dropForeignIfExists(
            $table,
            'player_category_histories_category_id_foreign'
        );

        $this->dropForeignIfExists(
            $table,
            'player_category_histories_previous_category_id_foreign'
        );

        DB::statement("
            ALTER TABLE {$table}
            ADD CONSTRAINT player_category_histories_player_id_foreign
            FOREIGN KEY (player_id)
            REFERENCES players(id)
            ON DELETE RESTRICT
        ");

        DB::statement("
            ALTER TABLE {$table}
            ADD CONSTRAINT player_category_histories_category_id_foreign
            FOREIGN KEY (category_id)
            REFERENCES categories(id)
            ON DELETE RESTRICT
        ");

        DB::statement("
            ALTER TABLE {$table}
            ADD CONSTRAINT player_category_histories_previous_category_id_foreign
            FOREIGN KEY (previous_category_id)
            REFERENCES categories(id)
            ON DELETE RESTRICT
        ");
    }

    public function down(): void
    {
        $table = 'player_category_histories';

        $this->dropForeignIfExists(
            $table,
            'player_category_histories_player_id_foreign'
        );

        $this->dropForeignIfExists(
            $table,
            'player_category_histories_category_id_foreign'
        );

        $this->dropForeignIfExists(
            $table,
            'player_category_histories_previous_category_id_foreign'
        );

        DB::statement("
            ALTER TABLE {$table}
            ADD CONSTRAINT player_category_histories_player_id_foreign
            FOREIGN KEY (player_id)
            REFERENCES players(id)
            ON DELETE CASCADE
        ");

        DB::statement("
            ALTER TABLE {$table}
            ADD CONSTRAINT player_category_histories_category_id_foreign
            FOREIGN KEY (category_id)
            REFERENCES categories(id)
            ON DELETE CASCADE
        ");

        DB::statement("
            ALTER TABLE {$table}
            ADD CONSTRAINT player_category_histories_previous_category_id_foreign
            FOREIGN KEY (previous_category_id)
            REFERENCES categories(id)
            ON DELETE SET NULL
        ");
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists) {
            DB::statement(
                "ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}"
            );
        }
    }
};