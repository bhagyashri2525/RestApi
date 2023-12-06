<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class EngagementPageSections extends Model
{
    use HasFactory;
    
    protected $table = "engagement_page_sections";

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
        'event_id',
        'section_title',
        'section_types',
        'status',
        'created_at',
        'is_deleted'
    ];

    protected $attributes = [
        'is_active' => self::STATUS_ACTIVE,
        'is_deleted' => self::STATUS_INACTIVE,
    ];
    
}
?>