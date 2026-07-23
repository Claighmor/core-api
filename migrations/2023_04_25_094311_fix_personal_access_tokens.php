<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('database.default') === config('fleetbase.connection.sandbox')) {
            return;
        }

        // Postgres needs an explicit USING cast to convert bigint -> char(36);
        // doctrine's ->change() does not emit one.
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE char(36) USING tokenable_id::text');

            return;
        }

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->uuid('tokenable_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('database.default') === config('fleetbase.connection.sandbox')) {
            return;
        }

        PersonalAccessToken::truncate();
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->bigInteger('tokenable_id')->change();
        });
    }
};
