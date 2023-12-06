<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class EventEmailSentLog extends Model
{
    use HasFactory;
    
    protected $table = "event_email_sent_logs";

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
        'company_id',
        'email_log_id',
        'email',
        'timestamp',
        'status',
        'data'
    ];
}