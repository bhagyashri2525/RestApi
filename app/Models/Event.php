<?php

namespace App\Models;

use App\Services\Utils\CommonService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class Event extends Model
{
    use HasFactory;

    const TABLE = "event";

    const STATUS_ACTIVE = true;
    const STATUS_INACTIVE = false;

    const TYPE_SINGLE = 'single';
    const TYPE_MULTIPLE = 'multiple';
    const TYPE_SERIES = 'series';

    const TYPES = [self::TYPE_SINGLE => 'Standalone',self::TYPE_MULTIPLE => 'Multiple',self::TYPE_SERIES => 'Series'];

    const CAT_SINGLE = 'single';
    const CAT_SERIES = 'series';

    const MAX_DUMMY_EVENTS_COUNT = 2;
    const MAX_DUMMY_SUBEVENTS_COUNT = 2;

    const STREAMON_CREATE_METHOD = 'stream-on';
    const SELF_CREATE_METHOD = 'self';
    const INDIVIDUAL_ZOOM_CREATE_METHOD = 'individual-zoom-acc';
    const CREATE_METHODS = [];

    protected $table = "event";

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
        'name',
        'description',
        'slug',
        'parent_id',
        'category',
        'type',
        'start_datetime',
        'end_datetime',
        'duration',
        'timezone',
        'timezone_start_datetime',
        'timezone_end_datetime',
        'company_id',
        'department_id',
        'creator_id',
        'integration_type', // zoom
        'integration', // zoom meeting response
        'integration_account', // zoom account 
        'is_active',
        'is_deleted',
        'publish',
        'event_count',
        'meeting_url',
        'page_draft_data',
        'page_publish_data',
        'short_urls',
        'total_visits',
        'create_method',
        'is_gdpr_compliance',
        'is_registartion_confrimation',
        'is_consent',
        'is_mute_notification',
        'form_data',
        'template_option_slug',
        'desc_event_timestamp',
        'asc_event_timestamp',
        'desc_creation_timestamp',
        'asc_creation_timestamp',
        'global_partition_key',
        'form_json_url',
        'form_html_url',
        'home_page',
    ];

    protected $attributes = [
        'is_active' => self::STATUS_ACTIVE,
        'is_deleted' => false,
    ];


}