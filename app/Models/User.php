<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class User extends Model implements AuthenticatableContract, JWTSubject
{
    use HasFactory, Notifiable, Authenticatable;

    protected $table = "users";

    const TABLE = "users";
    const STATUS_ACTIVE = true;
    const STATUS_INACTIVE = true;

    const EMAIL_VERIFICATION_TYPE = ['email' => 'email_verification','password' => 'password_update','welcome' => 'welcome_mail_from_company'];

    /* will use it later

    const COMPANY_MAPPING_DEFAULT_OBJECT = [
        "id" => "",
        "roles" => [],
        "departments" => [],
        "is_active" => false,
    ];
    const COMPANY_MAPPING_DEFAULT_ROLE_OBJECT =  ['slug' => ""];
    const COMPANY_MAPPING_DEFAULT_DEPARTMENT_OBJECT = ['id' => "",'roles' => []];
    
    */
    
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
        'identity_key',
        'display_name',
        'email',
        'password',
        'details',
        'country',
        'companies',
        'company_mapping',
        'verifications',
        'zoom_pre_connections',
        'roles',
        'is_email_verified',
        'is_active',
        'is_deleted',
        'deleted_at',
        'created_at',
        'updated_at',
        'display_profile_url',
        'zoom_integrations',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password'
    ];


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function scopeActive($query)
    {
        return $query->where(['is_active' => true])->where(function ($query) {
            $query->whereNull('is_deleted')->orWhere(['is_deleted' => false]);
        });
    }

    public function scopeIsDeleted($query)
    {
        return $query->where(['is_deleted  ' => true]);
    }

    public static function isCompanyRoleExist($userId,$companyId,$role){

        $isCompanyExist = false;
        $isRoleExist = false;
        $user = User::find($userId);
        $companyMapping = !empty($user) ?  $user->company_mapping : null;
        if(!empty($companyMapping) && count($companyMapping)){
            foreach( $companyMapping as $company){
                $isCompanyExist = false;
                $isRoleExist = false;
                if($company['id'] == $companyId){
                    $isCompanyExist = true;
                    if(!empty($company['roles']) && count($company['roles'])){
                        foreach($company['roles'] as $i => $roleObj){
                            if($roleObj['slug'] == $role){
                                $isRoleExist = true;
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

}