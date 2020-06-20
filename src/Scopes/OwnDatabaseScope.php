<?php

namespace ShibuyaKosuke\LaravelDatabaseUtilities\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OwnDatabaseScope implements Scope
{
    /**
     * @inheritDoc
     */
    public function apply(Builder $builder, Model $model)
    {
        $table_name = $model->getTable();
        $builder->where("{$table_name}.table_schema", \app('db.connection')->getDatabaseName())
            ->where("{$table_name}.table_name", '<>', 'migrations');
    }
}