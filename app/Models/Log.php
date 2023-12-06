<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class Log extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = true;
    const STATUS_INACTIVE = false;

    protected $table = "logs";

    protected $dynamoDbIndexKeys = [
        'listing_index' => [
            'hash' => 'id',
        ],
    ];

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'actor',
        'event',
        'data',
        'old_data',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => self::STATUS_ACTIVE,
    ];

    public function scopeActive($query)
    {
        return $query->where(['is_active' => true])->where(function ($query) {
            $query->whereNull('is_deleted')->orWhere(['is_deleted' => false]);
        });
    }

    public function scopeIsDeleted($query)
    {
        return $query->where(['is_deleted  ' => true]);
    }
}
