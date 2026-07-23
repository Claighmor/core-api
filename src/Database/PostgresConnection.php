<?php

namespace Fleetbase\Database;

use Fleetbase\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\PostgresConnection as BasePostgresConnection;

/**
 * Postgres connection that uses Fleetbase's schema grammar (uuid -> char(36)).
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
}
