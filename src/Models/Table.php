<?php

namespace ShibuyaKosuke\LaravelDatabaseUtilities\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Class Table
 * @package ShibuyaKosuke\LaravelDatabaseUtilities\Models
 *
 * @property string TABLE_CATALOG varchar
 * @property string TABLE_SCHEMA varchar
 * @property string TABLE_NAME varchar
 * @property string TABLE_TYPE varchar
 * @property string ENGINE varchar
 * @property int VERSION bigint
 * @property string ROW_FORMAT varchar
 * @property int TABLE_ROWS bigint
 * @property int AVG_ROW_LENGTH bigint
 * @property int DATA_LENGTH bigint
 * @property int MAX_DATA_LENGTH bigint
 * @property int INDEX_LENGTH bigint
 * @property int DATA_FREE bigint
 * @property int AUTO_INCREMENT bigint
 * @property string CREATE_TIME datetime
 * @property string UPDATE_TIME datetime
 * @property string CHECK_TIME datetime
 * @property string TABLE_COLLATION varchar
 * @property int CHECKSUM bigint
 * @property string CREATE_OPTIONS varchar
 * @property string TABLE_COMMENT varchar
 *
 * @property-read Column[] columns
 *
 * @property-read string model_name
 * @property-read string controller_name
 * @property-read string request_name
 * @property-read string policy_name
 * @property-read string view_composer_name
 */
class Table extends InformationSchema
{
    protected $table = 'information_schema.tables';

    /**
     * @var string[]
     */
    protected $appends = [
        'columns',
        'primary_key',
        'key_column_usages',
        'model_name',
        'controller_name',
        'request_name',
        'policy_name',
        'view_composer_name',
        'belongs_to',
        'belongs_to_many',
        'has_many',
    ];

    /**
     * get columns
     * @return HasMany|Column[]
     */
    public function columns(): HasMany
    {
        return $this->hasMany(Column::class, 'TABLE_NAME', 'TABLE_NAME');
    }

    /**
     * get primary key name
     * @return Column[]|null
     */
    public function getPrimaryKeyAttribute()
    {
        return $this->columns->filter(
            function (Column $column) {
                return $column->COLUMN_KEY === 'PRI';
            }
        );
    }

    /**
     * get model name
     * @return string
     */
    public function getModelNameAttribute(): string
    {
        return Str::studly(Str::singular($this->TABLE_NAME));
    }

    /**
     * get controller name
     * @return string
     */
    public function getControllerNameAttribute(): string
    {
        return sprintf('%sController', $this->model_name);
    }

    /**
     * get request name
     * @return string
     */
    public function getRequestNameAttribute(): string
    {
        return sprintf('%sFormRequest', $this->model_name);
    }

    /**
     * get policy name
     * @return string
     */
    public function getPolicyNameAttribute(): string
    {
        return sprintf('%sPolicy', $this->model_name);
    }

    /**
     * get view composer name
     * @return string
     */
    public function getViewComposerNameAttribute(): string
    {
        return sprintf('%sComposer', $this->model_name);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getRelationsAttribute()
    {
        $relations = [];

        // belongs to
        $relations['belongs_to'] = $this->hasMany(KeyColumnUsage::class, 'TABLE_NAME', 'TABLE_NAME')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->whereNotNull('REFERENCED_COLUMN_NAME')
            ->get()
            ->map(function (KeyColumnUsage $keyColumnUsage) {
                $column = Column::query()
                    ->where('TABLE_NAME', $keyColumnUsage->TABLE_NAME)
                    ->where('COLUMN_NAME', $keyColumnUsage->COLUMN_NAME)
                    ->first();
                $nullable = $column->IS_NULLABLE == 'YES';

                return collect([
                    'comment' => $this->getTableComment($keyColumnUsage->REFERENCED_TABLE_NAME),
                    'relation_name' => str_replace('_id', '', $keyColumnUsage->COLUMN_NAME),
                    'related_model' => Str::studly(Str::singular($keyColumnUsage->REFERENCED_TABLE_NAME)),
                    'nullable' => $nullable,
                    'ownTable' => $keyColumnUsage->TABLE_NAME,
                    'ownColumn' => $keyColumnUsage->COLUMN_NAME,
                    'otherTable' => $keyColumnUsage->REFERENCED_TABLE_NAME,
                    'otherColumn' => $keyColumnUsage->REFERENCED_COLUMN_NAME
                ]);
            });

        $relations['has_many'] = $this->hasMany(KeyColumnUsage::class, 'REFERENCED_TABLE_NAME', 'TABLE_NAME')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->whereNotNull('REFERENCED_COLUMN_NAME')
            ->get()
            ->reject(function (KeyColumnUsage $keyColumnUsage) {
                return Table::query()
                    ->where('TABLE_NAME', $keyColumnUsage->TABLE_NAME)
                    ->where('TABLE_COMMENT', '=', '')
                    ->first();
            })
            ->map(function (KeyColumnUsage $keyColumnUsage) {
                return collect([
                    'comment' => $this->getTableComment($keyColumnUsage->TABLE_NAME),
                    'relation_name' => $keyColumnUsage->TABLE_NAME,
                    'related_model' => Str::studly(Str::singular($keyColumnUsage->TABLE_NAME)),
                    'ownTable' => $keyColumnUsage->REFERENCED_TABLE_NAME,
                    'ownColumn' => $keyColumnUsage->REFERENCED_COLUMN_NAME,
                    'otherTable' => $keyColumnUsage->TABLE_NAME,
                    'otherColumn' => $keyColumnUsage->COLUMN_NAME
                ]);
            });

        $belongsToMany = [];
        KeyColumnUsage::query()
            ->whereIn(
                'TABLE_NAME',
                function ($query) {
                    $query->from('information_schema.KEY_COLUMN_USAGE')
                        ->select('TABLE_NAME')
                        ->whereNotNull('REFERENCED_TABLE_NAME')
                        ->where('REFERENCED_TABLE_NAME', '=', $this->TABLE_NAME);
                }
            )
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->whereNotNull('REFERENCED_COLUMN_NAME')
            ->orderBy('CONSTRAINT_NAME')
            ->get()
            ->filter(function (KeyColumnUsage $keyColumnUsage) {
                return Table::query()
                    ->where('TABLE_NAME', $keyColumnUsage->TABLE_NAME)
                    ->where('TABLE_COMMENT', '=', '')
                    ->first();
            })
            ->each(function (KeyColumnUsage $keyColumnUsage) use (&$belongsToMany) {
                $belongsToMany[$keyColumnUsage->TABLE_NAME]['relation_table'] = $keyColumnUsage->TABLE_NAME;
                $belongsToMany[$keyColumnUsage->TABLE_NAME]['relation_name'] = $keyColumnUsage->REFERENCED_TABLE_NAME;

                if ($keyColumnUsage->REFERENCED_TABLE_NAME == $this->TABLE_NAME) {
                    $belongsToMany[$keyColumnUsage->TABLE_NAME]['ownTable'] = $keyColumnUsage->REFERENCED_TABLE_NAME;
                    $belongsToMany[$keyColumnUsage->TABLE_NAME]['ownColumn'] = $keyColumnUsage->REFERENCED_COLUMN_NAME;
                } else {
                    $belongsToMany[$keyColumnUsage->TABLE_NAME]['comment'] = $this->getTableComment($keyColumnUsage->REFERENCED_TABLE_NAME);
                    $belongsToMany[$keyColumnUsage->TABLE_NAME]['related_model'] = Str::studly(Str::singular($keyColumnUsage->REFERENCED_TABLE_NAME));
                    $belongsToMany[$keyColumnUsage->TABLE_NAME]['otherTable'] = $keyColumnUsage->REFERENCED_TABLE_NAME;
                    $belongsToMany[$keyColumnUsage->TABLE_NAME]['otherColumn'] = $keyColumnUsage->REFERENCED_COLUMN_NAME;
                }
            });
        $relations['belongs_to_many'] = collect(array_values($belongsToMany));

        return collect($relations);
    }

    protected function getTableComment($table_name)
    {
        return Table::query()->where('TABLE_NAME', $table_name)->first()->TABLE_COMMENT;
    }
}
