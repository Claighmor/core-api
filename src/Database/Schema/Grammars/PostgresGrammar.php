<?php

namespace Fleetbase\Database\Schema\Grammars;

use Illuminate\Database\Schema\Blueprint;
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
    public function typeUuid(Fluent $column): string
    {
        return 'char(36)';
    }

    /*
     * Spatial types -> PostGIS geometry with SRID 4326, matching the WKT that
     * Fleetbase writes (POINT(lng lat), etc.) and the app-side casts read.
     */
    public function typeGeometry(Fluent $column): string
    {
        return 'geometry(Geometry,4326)';
    }

    public function typePoint(Fluent $column): string
    {
        return 'geometry(Point,4326)';
    }

    public function typeLineString(Fluent $column): string
    {
        return 'geometry(LineString,4326)';
    }

    public function typePolygon(Fluent $column): string
    {
        return 'geometry(Polygon,4326)';
    }

    public function typeMultiPoint(Fluent $column): string
    {
        return 'geometry(MultiPoint,4326)';
    }

    public function typeMultiLineString(Fluent $column): string
    {
        return 'geometry(MultiLineString,4326)';
    }

    public function typeMultiPolygon(Fluent $column): string
    {
        return 'geometry(MultiPolygon,4326)';
    }

    public function typeGeometryCollection(Fluent $column): string
    {
        return 'geometry(GeometryCollection,4326)';
    }

    /**
     * Compile a spatial index as a GiST index with a schema-unique name.
     *
     * Fleetbase reuses index names like "location" across many tables (fine per
     * table on MySQL, but Postgres index names are schema-unique). Prefix the
     * given name with the table to disambiguate.
     */
    public function compileSpatialIndex(Blueprint $blueprint, Fluent $command): string
    {
        $name = $blueprint->getTable() . '_' . $command->index;

        return sprintf(
            'create index %s on %s using gist (%s)',
            $this->wrap($name),
            $this->wrapTable($blueprint),
            $this->columnize($command->columns)
        );
    }
}
