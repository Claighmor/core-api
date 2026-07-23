<?php

namespace Fleetbase\Database\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\PostgresGrammar as BasePostgresGrammar;
use Illuminate\Support\Fluent;

/**
 * Fleetbase Postgres schema grammar.
 *
 * Fleetbase stores UUID/public identifiers as char(36) strings (values are not
 * always canonical UUIDs, and char(36) primary keys are foreign-key referenced by
 * columns declared with `->uuid()`). On MySQL both are strings, so it works; on
 * Postgres a native `uuid` column type would datatype-mismatch every such FK.
 *
 * Compiling `->uuid()` as char(36) keeps the string semantics consistent across
 * all packages' migrations without editing each one.
 */
class PostgresGrammar extends BasePostgresGrammar
{
    /**
     * Create the column definition for a uuid type.
     */
    protected function typeUuid(Fluent $column): string
    {
        return 'char(36)';
    }
}
