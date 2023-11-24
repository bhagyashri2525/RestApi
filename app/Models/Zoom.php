<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class Zoom extends Model
{
    use HasFactory;
    protected $table = "zoom";

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
        'email',
        'start_time',
        'url',
    ];

}
