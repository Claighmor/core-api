<?php

namespace Fleetbase\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;

/**
 * Doctrine guid type that declares as char(36).
 *
 * Laravel's `->change()` uses doctrine/dbal, and `$table->uuid()->change()` maps
 * to doctrine's `guid`, which the Postgres platform declares as native `UUID`.
 * Fleetbase stores uuids as char(36) (see the schema grammar), so re-declaring a
 * uuid column via `->change()` would try to cast char(36) -> uuid and fail. This
 * makes guid declare as char(36) so such `->change()` calls (typically just
 * toggling nullability) become no-op type changes.
 */
class CharGuidType extends GuidType
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'char(36)';
    }
}
