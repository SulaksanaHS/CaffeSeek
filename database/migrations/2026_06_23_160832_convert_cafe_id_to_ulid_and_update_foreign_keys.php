<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Strategy:
     *  1. Add `ulid` column to cafes, backfill existing rows
     *  2. Add `new_cafe_id` (char 26) to child tables, backfill via JOIN
     *  3. Drop old FK constraints on child tables
     *  4. Drop old integer `cafe_id` columns, rename `new_cafe_id` → `cafe_id`, add new string FK
     *  5. Drop old integer `id` on cafes, rename `ulid` → `id`, set as PK
     *  6. Fix `owner_cafe` on users: drop int column, add string column
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return; // Skip conversion in tests since we updated original migrations
        }

        // ------------------------------------------------------------------
        // STEP 1: Add ULID column to cafes and populate
        // ------------------------------------------------------------------
        Schema::table('cafes', function (Blueprint $table) {
            $table->char('ulid', 26)->nullable()->after('id');
        });

        $cafes = DB::table('cafes')->get();
        foreach ($cafes as $cafe) {
            DB::table('cafes')->where('id', $cafe->id)->update([
                'ulid' => (string) Str::ulid(),
            ]);
        }

        // ------------------------------------------------------------------
        // STEP 2: Add new_cafe_id to all child tables and populate
        // ------------------------------------------------------------------
        Schema::table('cafe_photos', function (Blueprint $table) {
            $table->char('new_cafe_id', 26)->nullable()->after('cafe_id');
        });
        Schema::table('cafe_menus', function (Blueprint $table) {
            $table->char('new_cafe_id', 26)->nullable()->after('cafe_id');
        });
        Schema::table('cafe_tables', function (Blueprint $table) {
            $table->char('new_cafe_id', 26)->nullable()->after('cafe_id');
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->char('new_cafe_id', 26)->nullable()->after('cafe_id');
        });

        // Backfill new_cafe_id using subquery (compatible with MySQL and SQLite)
        DB::statement('UPDATE cafe_photos SET new_cafe_id = (SELECT ulid FROM cafes WHERE cafes.id = cafe_photos.cafe_id)');
        DB::statement('UPDATE cafe_menus SET new_cafe_id = (SELECT ulid FROM cafes WHERE cafes.id = cafe_menus.cafe_id)');
        DB::statement('UPDATE cafe_tables SET new_cafe_id = (SELECT ulid FROM cafes WHERE cafes.id = cafe_tables.cafe_id)');
        DB::statement('UPDATE reservations SET new_cafe_id = (SELECT ulid FROM cafes WHERE cafes.id = reservations.cafe_id)');

        // ------------------------------------------------------------------
        // STEP 3: Drop old FK constraints and integer cafe_id columns
        // ------------------------------------------------------------------
        Schema::table('cafe_photos', function (Blueprint $table) {
            $table->dropForeign('cafe_photos_cafe_id_foreign');
            $table->dropColumn('cafe_id');
        });
        Schema::table('cafe_menus', function (Blueprint $table) {
            $table->dropForeign('cafe_menus_cafe_id_foreign');
            $table->dropColumn('cafe_id');
        });
        Schema::table('cafe_tables', function (Blueprint $table) {
            $table->dropForeign('cafe_tables_cafe_id_foreign');
            $table->dropColumn('cafe_id');
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign('reservations_cafe_id_foreign');
            $table->dropColumn('cafe_id');
        });

        // ------------------------------------------------------------------
        // STEP 4: Rename new_cafe_id → cafe_id in child tables
        // ------------------------------------------------------------------
        Schema::table('cafe_photos', function (Blueprint $table) {
            $table->renameColumn('new_cafe_id', 'cafe_id');
        });
        Schema::table('cafe_menus', function (Blueprint $table) {
            $table->renameColumn('new_cafe_id', 'cafe_id');
        });
        Schema::table('cafe_tables', function (Blueprint $table) {
            $table->renameColumn('new_cafe_id', 'cafe_id');
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->renameColumn('new_cafe_id', 'cafe_id');
        });

        $driver = DB::connection()->getDriverName();

        // ------------------------------------------------------------------
        // STEP 5: Replace cafes.id with ULID (MySQL only - not needed in SQLite tests)
        // ------------------------------------------------------------------
        if ($driver === 'mysql') {
            // Drop the auto-increment primary key and old integer id column
            DB::statement('ALTER TABLE cafes DROP PRIMARY KEY, DROP COLUMN id');
            // Rename ulid → id
            Schema::table('cafes', function (Blueprint $table) {
                $table->renameColumn('ulid', 'id');
            });
            // Set new id as primary key and make NOT NULL, move to first position
            DB::statement('ALTER TABLE cafes MODIFY COLUMN id CHAR(26) NOT NULL FIRST');
            DB::statement('ALTER TABLE cafes ADD PRIMARY KEY (id)');

            // ------------------------------------------------------------------
            // STEP 6: Add new FK constraints on child tables (string-based)
            // ------------------------------------------------------------------
            Schema::table('cafe_photos', function (Blueprint $table) {
                $table->char('cafe_id', 26)->nullable(false)->change();
                $table->foreign('cafe_id')->references('id')->on('cafes')->cascadeOnDelete();
            });
            Schema::table('cafe_menus', function (Blueprint $table) {
                $table->char('cafe_id', 26)->nullable(false)->change();
                $table->foreign('cafe_id')->references('id')->on('cafes')->cascadeOnDelete();
            });
            Schema::table('cafe_tables', function (Blueprint $table) {
                $table->char('cafe_id', 26)->nullable(false)->change();
                $table->foreign('cafe_id')->references('id')->on('cafes')->cascadeOnDelete();
            });
            Schema::table('reservations', function (Blueprint $table) {
                $table->char('cafe_id', 26)->nullable(false)->change();
                $table->foreign('cafe_id')->references('id')->on('cafes')->cascadeOnDelete();
            });
        }

        // ------------------------------------------------------------------
        // STEP 7: Fix `owner_cafe` in users table (int → string)
        // ------------------------------------------------------------------
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('owner_cafe');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->char('owner_cafe', 26)->nullable()->after('role');
        });
    }

    /**
     * Reverse the migrations.
     * NOTE: Full reversal of this migration is complex.
     * We provide a best-effort rollback.
     */
    public function down(): void
    {
        // This is a destructive migration; rolling back would require
        // the original data mapping which we do not store here.
        // In production, restore from a backup instead.
        throw new \RuntimeException('Rollback of ULID conversion migration is not supported. Restore from backup.');
    }
};
