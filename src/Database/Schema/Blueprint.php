<?php

namespace Fleetbase\Database\Schema;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use Illuminate\Database\Schema\Grammars\Grammar;

/**
 * Fleetbase schema blueprint.
 *
 * Fleetbase's `uuid` column is a globally-unique public identifier that many
 * foreign keys reference. MySQL tolerates FKs referencing a merely-indexed
 * column, but Postgres requires the referenced column to carry a UNIQUE/PK
 * constraint. Rather than edit every migration, ensure any created table with a
 * `uuid` column gets a unique constraint on it (unless one is already declared).
 */
class Blueprint extends BaseBlueprint
{
    protected function addImpliedCommands(Connection $connection, Grammar $grammar)
    {
        $this->ensureUuidUnique();

        parent::addImpliedCommands($connection, $grammar);
    }

    protected function ensureUuidUnique(): void
    {
        if (!$this->creating()) {
            return;
        }

        $uuidColumn = null;
        foreach ($this->columns as $column) {
            if ($column->name === 'uuid') {
                $uuidColumn = $column;
                break;
            }
        }

        if ($uuidColumn === null) {
            return;
        }

        // Already keyed at the column level (->unique()/->primary()).
        if (!empty($uuidColumn->unique) || !empty($uuidColumn->primary)) {
            return;
        }

        // Already keyed via a table-level unique/primary command on uuid.
        foreach ($this->commands as $command) {
            if (in_array($command->name, ['primary', 'unique'], true)
                && in_array('uuid', (array) ($command->columns ?? []), true)) {
                return;
            }
        }

        $this->unique('uuid');
    }
}
