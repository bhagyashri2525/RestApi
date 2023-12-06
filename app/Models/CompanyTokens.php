<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class CompanyTokens extends Model
{
    use HasFactory;
    
    protected $table = "company_tokens";

    const STATUS_ACTIVE = true;
    const STATUS_INACTIVE = false;

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
        'token',
        'status',
        'action',
        'company_id',
        'name',
        'expiry',
        'is_active',
        'created_at',
    ];

    protected $attributes = [
        'is_active' => self::STATUS_ACTIVE,
        'is_deleted' => self::STATUS_INACTIVE,
    ];
}