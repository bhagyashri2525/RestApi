<?php

namespace App\Models;

use App\Data\Company\CompanyData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class Company extends Model
{
    use HasFactory;

    protected $table = "company";

    const TABLE = "company";
    const MAX_DUMMY_DEPARTMENTS_COUNT = 2;
    const MAX_DUMMY_USERS_COUNT = 2;
    const COMPANY_TYPES = [ 'trial' =>  'trial','genuine' => 'genuine'];


    protected $dynamoDbIndexKeys = [
        'listing_index' => [
            'hash' => 'id',
        ],
    ];

    protected $primaryKey = 'id';

    protected $guarded = ['id'];


    protected $fillable = [
        'id',
        'display_name',
        'slug',
        'description',
        'code',
        'departments',
        'website',
        'zoom_emails',
        'zoom_integrations',
        'is_active',
        'is_deleted',
        'type',
        'is_zoom_pull_emails',
    ];
    public static function details(String $id) {
        $company =  Company::where('id',$id)->first();
        if(!empty($company) && $company->is_active == true && (!isset($company->is_deleted) || $company->is_deleted == false)){
            return $company;
        }
        return null;
    }
}
