<?php

namespace ShibuyaKosuke\LaravelDatabaseUtilities\Models;

use Illuminate\Database\Eloquent\Model;
use ShibuyaKosuke\LaravelDatabaseUtilities\Scopes\OwnDatabaseScope;

abstract class InformationSchema extends Model
{
    protected $primaryKey = null;
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OwnDatabaseScope());
    }
}