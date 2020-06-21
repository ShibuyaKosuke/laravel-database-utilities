<?php

namespace ShibuyaKosuke\LaravelDatabaseUtilities\Models;

use Illuminate\Database\Eloquent\Model;
use ShibuyaKosuke\LaravelDatabaseUtilities\Scopes\OwnDatabaseScope;

/**
 * Class InformationSchema
 * @package ShibuyaKosuke\LaravelDatabaseUtilities\Models
 */
abstract class InformationSchema extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = null;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OwnDatabaseScope());
    }
}
