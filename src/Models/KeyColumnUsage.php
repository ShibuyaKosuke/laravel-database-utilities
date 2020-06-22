<?php

namespace ShibuyaKosuke\LaravelDatabaseUtilities\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class KeyColumnUsage
 * @package ShibuyaKosuke\LaravelDatabaseUtilities\Models
 *
 * @property string CONSTRAINT_CATALOG
 * @property string CONSTRAINT_SCHEMA
 * @property string CONSTRAINT_NAME
 * @property string TABLE_CATALOG
 * @property string TABLE_SCHEMA
 * @property string TABLE_NAME
 * @property string COLUMN_NAME
 * @property int ORDINAL_POSITION
 * @property int POSITION_IN_UNIQUE_CONSTRAINT
 * @property string REFERENCED_TABLE_SCHEMA
 * @property string REFERENCED_TABLE_NAME
 * @property string REFERENCED_COLUMN_NAME
 */
class KeyColumnUsage extends InformationSchema
{
    protected $table = 'information_schema.key_column_usage';

    /**
     * @return BelongsTo
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'TABLE_NAME', 'TABLE_NAME')->withDefault();
    }

    public function getBelongsToAttribute()
    {
        return $this->belongsTo(Column::class, 'REFERENCED_COLUMN_NAME', 'COLUMN_NAME')
            ->where('columns.TABLE_NAME', $this->REFERENCED_TABLE_NAME)
            ->where('columns.TABLE_SCHEMA', $this->REFERENCED_TABLE_SCHEMA)
            ->withDefault();
    }
}
