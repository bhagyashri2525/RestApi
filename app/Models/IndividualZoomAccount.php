<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;


class IndividualZoomAccount extends Model
{
    use HasFactory;

    const TABLE = "individual_zoom_account";

    const STATUS_ACTIVE = true;
    const STATUS_INACTIVE = false;

    protected $table = "individual_zoom_account";

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
        'email',
        'creator_id',
        'company_id',
        'is_verified',
        'is_active',
        'is_deleted',
    ];

    protected $attributes = [
        'is_active' => self::STATUS_INACTIVE,
        'is_deleted' => false,
        'is_verified' => self::STATUS_INACTIVE,
    ];
}
?>