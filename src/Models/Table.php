<?php

namespace ShibuyaKosuke\LaravelDatabaseUtilities\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
 * @property-read Table[] belongs_to
 * @property-read Table[] belongs_to_many
 * @property-read Table[] has_many
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
     * @return HasMany KeyColumnUsage[]
     */
    public function keyColumnUsages(): HasMany
    {
        return $this->hasMany(KeyColumnUsage::class, 'TABLE_NAME', 'TABLE_NAME')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->whereNotNull('REFERENCED_COLUMN_NAME');
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
     * get belongs-to relation tables
     * @return Builder[]|Collection
     */
    public function getBelongsToAttribute()
    {
        return Table::query()
            ->whereIn('TABLE_NAME', $this->keyColumnUsages->pluck('REFERENCED_TABLE_NAME'))
            ->get();
    }

    /**
     * get belongs-to-many relation tables
     * @return Builder[]|Collection
     */
    public function getBelongsToManyAttribute()
    {
        if (!$this->TABLE_COMMENT) {
            return null;
        }
        $tables = Table::all()->reject(
            function (Table $table) {
                return $table->TABLE_COMMENT === '';
            }
        )->pluck('TABLE_NAME');

        $join = $tables->crossJoin($tables)->map(
            function ($join) {
                return implode(
                    '_',
                    array_map(
                        function ($join) {
                            return Str::singular($join);
                        },
                        $join
                    )
                );
            }
        );

        $belongsToMany = KeyColumnUsage::query()
            ->whereIn(
                'TABLE_NAME',
                function ($query) use ($join) {
                    $query->from('information_schema.KEY_COLUMN_USAGE')
                        ->select('TABLE_NAME')
                        ->whereNotNull('REFERENCED_TABLE_NAME')
                        ->where('REFERENCED_TABLE_NAME', '=', $this->TABLE_NAME)
                        ->whereIn('TABLE_NAME', $join);
                }
            )
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->where('REFERENCED_TABLE_NAME', '<>', $this->TABLE_NAME)
            ->get();

        $table_names = $belongsToMany->pluck('REFERENCED_TABLE_NAME') ?? [];
        return Table::query()->whereIn('TABLE_NAME', $table_names)->get();
    }

    /**
     * get has-many relation tables
     * @return Builder[]|Collection
     */
    public function getHasManyAttribute()
    {
        return Table::query()
            ->where('TABLE_COMMENT', '!=', '')
            ->whereIn(
                'TABLE_NAME',
                $this->hasMany(KeyColumnUsage::class, 'REFERENCED_TABLE_NAME', 'TABLE_NAME')
                    ->whereNotNull('REFERENCED_TABLE_NAME')
                    ->whereNotNull('REFERENCED_COLUMN_NAME')
                    ->get()
                    ->pluck('TABLE_NAME')
            )
            ->get();
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
}
