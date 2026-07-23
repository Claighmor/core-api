<?php

namespace Fleetbase\Database;

use Fleetbase\Database\Schema\Blueprint;
use Fleetbase\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\PostgresConnection as BasePostgresConnection;

/**
 * Postgres connection that uses Fleetbase's schema grammar (uuid -> char(36))
 * and blueprint (auto-unique on the `uuid` public-identifier column).
 * Registered via Connection::resolverFor('pgsql', ...) in CoreServiceProvider.
 */
class PostgresConnection extends BasePostgresConnection
{
    /**
     * Get the default schema grammar instance.
     */
    protected function getDefaultSchemaGrammar()
    {
        ($grammar = new PostgresGrammar())->setConnection($this);

        return $this->withTablePrefix($grammar);
    }

    /**
     * Get a schema builder that resolves Fleetbase's blueprint.
     */
    public function getSchemaBuilder()
    {
        $builder = parent::getSchemaBuilder();

        $builder->blueprintResolver(function ($table, $callback, $prefix) {
            return new Blueprint($table, $callback, $prefix);
        });

        return $builder;
    }
}
