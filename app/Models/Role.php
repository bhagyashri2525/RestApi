<?php

namespace App\Models;

use App\Data\Role\RoleData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use \BaoPham\DynamoDb\DynamoDbModel as Model;

class Role extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = true;
    const STATUS_INACTIVE = false;

    protected $table = "roles";

    const STREAMON_ADMIN = 'stream-on-admin';
    const STREAMON_OPERATION = 'stream-on-operation';
    const STREAMON_SALES = 'stream-on-sales';
    const ADMIN_ROLES = [self::STREAMON_ADMIN,self::STREAMON_OPERATION,self::STREAMON_SALES];


    const COMPANY_MASTER_ADMIN = 'company-master-admin';
    const DEPARTMENT_ADMIN = 'company-department-admin';
    const COMPANY_EMPLOYEE = 'company-department-employee';
    const COMPANY_ROLES = [self::COMPANY_MASTER_ADMIN,self::DEPARTMENT_ADMIN,self::COMPANY_EMPLOYEE];
    const COMPANY_ROLE_OPTIONS = [self::COMPANY_MASTER_ADMIN => 'Company Admin', self::DEPARTMENT_ADMIN => 'Department Admin', self::COMPANY_EMPLOYEE => 'Employee'];

    const EVENT_ADMIN = 'event-admin';
    const EVENT_MODERATOR = 'event-moderator';
    const EVENT_REPORT_MANAGER = 'event-report-manager';
    const EVENT_ATTENDEE = 'event-attendee';
    const EVENT_ROLES = [self::EVENT_ADMIN,self::EVENT_MODERATOR,self::EVENT_REPORT_MANAGER];

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
        'type',
        'slug',
        'order',
        'is_deleted',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => self::STATUS_ACTIVE,
    ];

    public static function details(String $id): RoleData | null{
        $role =  Role::where('id',$id)->first();
        if($role->is_active == true && (!isset($role->is_deleted) || $role->is_deleted == false) ){
            return RoleData::from($role);
        }
        return null;
    }

    public static function getAdminRole(Array $roles){
        foreach($roles as $r){
            if(in_array($r,self::ADMIN_ROLES)){
                return $r;
            }
        }
        return null;
    }

    public static function allAdminRoles($type = 'string'){
        if($type == 'array'){
            return self::ADMIN_ROLES;
        }else if($type == 'string'){
            $roles = self::ADMIN_ROLES;
            $str = implode(',', $roles);
            return $str;
        }
    }

    public static function allCompanyRoles($type = 'string'){
        if($type == 'array'){
            return self::COMPANY_ROLES;
        }else if($type == 'string'){
            $roles = self::COMPANY_ROLES;
            $str = implode(',', $roles);
            return $str;
        }
    }
    public static function allEventRoles($type = 'string'){
        if($type == 'array'){
            return self::EVENT_ROLES;
        }else if($type == 'string'){
            $roles = self::EVENT_ROLES;
            $str = implode(',', $roles);
            return $str;
        }
    }
}
