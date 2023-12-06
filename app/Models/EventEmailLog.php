<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class EventEmailLog extends Model
{
    use HasFactory;
    
    protected $table = "event_email_logs";

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
        'event_id',
        'sub_event_id',
        'email_template_id',
        'group_id',
        'datetime',
        'timezone',
        'utc_datetime',
        'dynamic_vars',
        'subject',
        'group_type'
    ];
}