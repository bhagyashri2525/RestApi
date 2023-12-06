<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class DefaultEmailUserGroup extends Model
{
    use HasFactory;
    
    const TABLE = "default_email_user_groups";

    const DEFAULT_GROUPS = ["Registering Users", "Attendee Users", "Non-Attendee Users"];

    protected $table = "default_email_user_groups";

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
        'group_name'
    ];
}
?>