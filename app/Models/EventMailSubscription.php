<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class EventMailSubscription extends Model
{
    use HasFactory;

    const TABLE = "event_mail_subscriptions";

    protected $table = "event_mail_subscriptions";

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
        'reason',
        'subscribed',
    ];
}
