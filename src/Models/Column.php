<?php

namespace ShibuyaKosuke\LaravelDatabaseUtilities\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Column
 * @package ShibuyaKosuke\LaravelLanguageMysqlComment\Models
 *
 * @property string TABLE_CATALOG
 * @property string TABLE_SCHEMA
 * @property string TABLE_NAME
 * @property string COLUMN_NAME
 * @property int ORDINAL_POSITION
 * @property string COLUMN_DEFAULT
 * @property string IS_NULLABLE
 * @property string DATA_TYPE
 * @property int CHARACTER_MAXIMUM_LENGTH
 * @property int CHARACTER_OCTET_LENGTH
 * @property int NUMERIC_PRECISION
 * @property int NUMERIC_SCALE
 * @property int DATETIME_PRECISION
 * @property string CHARACTER_SET_NAME
 * @property string COLLATION_NAME
 * @property string COLUMN_TYPE
 * @property string COLUMN_KEY
 * @property string EXTRA
 * @property string PRIVILEGES
 * @property string COLUMN_COMMENT
 * @property string GENERATION_EXPRESSION
 * @property-read Table table
 * @property-read Column belongs_to
 * @property-read Column has_many
 */
class Column extends InformationSchema
{
    protected $table = 'information_schema.columns';

    /**
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (config('database.default') === 'pgsql') {
            $key = strtolower($key);
        }
        return $this->getAttribute($key);
    }

    /**
     * @return BelongsTo
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'TABLE_NAME', 'TABLE_NAME');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|BelongsTo|object|null
     */
    public function getBelongsToAttribute()
    {
        /** @var KeyColumnUsage $keyColumnUsage */
        $keyColumnUsage = $this->belongsTo(KeyColumnUsage::class, 'COLUMN_NAME', 'COLUMN_NAME')
            ->where('key_column_usage.TABLE_NAME', $this->TABLE_NAME)
            ->where('key_column_usage.TABLE_SCHEMA', $this->TABLE_SCHEMA)
            ->where('key_column_usage.REFERENCED_TABLE_NAME', '!=', '')
            ->where('key_column_usage.REFERENCED_COLUMN_NAME', '!=', '')
            ->first();

        if ($keyColumnUsage) {
            return $keyColumnUsage->getBelongsToAttribute()
                ->first();
        }
        return null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|BelongsTo|object|null
     */
    public function getHasManyAttribute()
    {
        /** @var KeyColumnUsage $keyColumnUsage */
        $keyColumnUsage = $this->belongsTo(KeyColumnUsage::class, 'COLUMN_NAME', 'REFERENCED_COLUMN_NAME')
            ->where('key_column_usage.REFERENCED_TABLE_NAME', $this->TABLE_NAME)
            ->where('key_column_usage.REFERENCED_TABLE_SCHEMA', $this->TABLE_SCHEMA)
            ->firstOrNew();
        return $keyColumnUsage->getHasManyAttribute()
            ->first();
    }
}
