<?php

namespace ShibuyaKosuke\LaravelDatabaseUtilities\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

use function app;

/**
 * Class OwnDatabaseScope
 * @package ShibuyaKosuke\LaravelDatabaseUtilities\Scopes
 */
class OwnDatabaseScope implements Scope
{
    /**
     * @inheritDoc
     */
    public function apply(Builder $builder, Model $model)
    {
        $database = config('database.default');

        $table_catalog = ($database === 'pgsql') ? app('db.connection')->getDatabaseName() : 'def';
        $table_schema = ($database === 'pgsql') ? 'public' : app('db.connection')->getDatabaseName();

        $tableName = $model->getTable();
        $builder->where("{$tableName}.table_catalog", $table_catalog)
            ->where("{$tableName}.table_schema", $table_schema)
            ->whereNotIn(
                "{$tableName}.table_name",
                [
                    'failed_jobs',
                    'migrations',
                    'password_resets'
                ]
            );
    }
}
