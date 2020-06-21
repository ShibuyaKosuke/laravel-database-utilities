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
        $tableName = $model->getTable();
        $builder->where("{$tableName}.table_schema", app('db.connection')->getDatabaseName())
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
