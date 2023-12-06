<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class EmailTemplate extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = true;
    const STATUS_INACTIVE = false;
    const TABLE = "event_email_templates";

    protected $table = "event_email_templates";

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
        'template_name',
        'template',
        'path',
        'params',
        'is_active',
        'is_delete',
        'creator_id',
        'creator_at',
        'template_filename',
        'sub_event_id',
        'type',
        'subject'
    ];

    protected $attributes = [
        'is_active' => self::STATUS_ACTIVE,
    ];
}
