<?php

namespace ShibuyaKosuke\LaravelDatabaseUtilities\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

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
        $table_name = $model->getTable();
        $builder->where("{$table_name}.table_schema", \app('db.connection')->getDatabaseName())
            ->whereNotIn("{$table_name}.table_name", [
                'failed_jobs',
                'migrations',
                'password_resets'
            ]);
    }
}