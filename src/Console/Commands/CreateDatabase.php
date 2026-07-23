<?php

namespace Fleetbase\Console\Commands;

use Fleetbase\Support\Utils;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mysql:createdb {--schemaName=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new mysql database schema based on the database config file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // On PostgreSQL there is one physical database (already provisioned, e.g.
        // Supabase). "Databases" here become SCHEMAS scoped by search_path, so we
        // CREATE SCHEMA instead of CREATE DATABASE and never detach the database.
        if (config('database.connections.mysql.driver') === 'pgsql') {
            $this->createPostgresSchemas();

            return;
        }

        $_schemaName        = $this->option('schemaName');
        $connections        = ['mysql', 'sandbox'];
        $packageConnections = Utils::fromFleetbaseExtensions('create-database');

        if (is_array($packageConnections) && !empty($packageConnections)) {
            $connections = array_merge($connections, $packageConnections);
        }

        foreach ($connections as $connection) {
            $schemaName = config("database.connections.$connection.database");

            if ($_schemaName) {
                $schemaName = $connection === 'mysql' ? $_schemaName : $_schemaName . '_' . $connection;
            }

            $charset   = config("database.connections.$connection.charset", 'utf8mb4');
            $collation = config("database.connections.$connection.collation", 'utf8mb4_unicode_ci');

            config(['database.connections.mysql.database' => null]);

            $query = "CREATE DATABASE IF NOT EXISTS $schemaName CHARACTER SET \"$charset\" COLLATE \"$collation\";";
            DB::statement($query);

            config(['database.connections.mysql.database' => $schemaName]);
        }
    }

    /**
     * PostgreSQL: ensure the schemas each named connection resolves against
     * exist (public is present by default; sandbox is created), and that PostGIS
     * is available for the FleetOps spatial columns. Idempotent.
     */
    protected function createPostgresSchemas(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');

        foreach (['mysql', 'sandbox'] as $connection) {
            $schema = config("database.connections.$connection.search_path");
            if (!empty($schema) && $schema !== 'public') {
                DB::statement('CREATE SCHEMA IF NOT EXISTS "' . $schema . '";');
                $this->info("Ensured schema '{$schema}' exists.");
            }
        }
    }
}
