<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class EventParticipantsLog extends Model
{
    use HasFactory;

    const TABLE = "event_participants_logs";

    protected $table = "event_participants_logs";

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
        'company_id',
        'event_id',
        'email',
        'details',
    ];
}
