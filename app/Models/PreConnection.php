<?php

namespace App\Models;

use \BaoPham\DynamoDb\DynamoDbModel as Model;

class PreConnection extends Model
{
    protected $table = "pre_connections";

    const TABLE = "pre_connections";

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
        'company_id',
        'email'
    ];
}