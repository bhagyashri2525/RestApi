<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class DefaultEmailTemplate extends Model
{
    use HasFactory;
    
    const TABLE = "default_email_templates";

    protected $table = "default_email_templates";

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
        'template_name',
        'template_thumbnail',
        'template_url',
    ];
}
?>