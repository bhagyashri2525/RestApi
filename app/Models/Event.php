<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class Event extends Model
{
    use HasFactory;
    protected $table = "event";

    protected $dynamoDbIndexKeys = [
        'listing_index' => [
            'hash' => 'id',
        ],
    ];

    protected $primaryKey = 'id';

    protected $guarded = ['id'];


    protected $fillable = [
        'id',
        'name',
        'slug',
        'category',
        'timezone',
        'type',
        'start_time',
    ];

}

