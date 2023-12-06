<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class UserRole extends Model
{
    use HasFactory;

    protected $table = "users_roles";

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
        'user_id',
        'role_id',
        'event_id',
        'is_active',
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
