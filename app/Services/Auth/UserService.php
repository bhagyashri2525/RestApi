<?php

namespace App\Services\Auth;

use App\Models\Role;
use App\Models\User;
use App\Models\Company;
use App\Models\PreConnection;
use App\Data\ParticularUserData;
use App\Services\Utils\CommonService as UtilsCommonService;
use BaoPham\DynamoDb\Facades\DynamoDb;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
class UserService
{
    public function registerUser($data)
    {
        $data['password'] = Hash::make($data['password']);
        $data['is_email_verified'] = false;
        $data['is_active'] = true;
        $data['identity_key'] = (new UtilsCommonService)->generateUserIdentityKey('usr');
        $data['id'] = (new UtilsCommonService)->generatePrimaryKey();
        $model = new User($data);
        $model->saveAsync()->wait();
        return $model->refresh();
    }

    public function savePanelUser($data,$roleId)
    {
        $data['is_email_verified'] = false;
        $data['is_active'] = true;
        $data['identity_key'] = (new UtilsCommonService)->generateUserIdentityKey('usr');
        $data['id'] = (new UtilsCommonService)->generatePrimaryKey();
        $data['roles'] = [$roleId];
        $model = new User($data);
        $model->saveAsync()->wait();
        return $model->refresh();
    }

    public function saveCompanyUser($data,$roleId,$companyId)
    {
        $data['is_email_verified'] = false;
        $data['is_active'] = true;
        $data['identity_key'] = (new UtilsCommonService)->generateUserIdentityKey('usr');
        $data['id'] = (new UtilsCommonService)->generatePrimaryKey();
        $data['roles'] = [$roleId];
        $data['companies'] = [$companyId];
        $model = new User($data);
        $model->saveAsync()->wait();
        return $model->refresh();
    }

    public function setPassword($userId,$password){
        $user = User::where(['id' => $userId])->first();
        if(!empty($user)){
            $password = Hash::make($password);
            $user->update(['password' => $password]);
            $user->save();
            return true;
        }
        return false;
    }

    public function getUsers($all = false)
    {
        if ($all) {
            return User::all();
        } else {
            return User::where("is_active", true)->get();
        }
    }

    public function getRolesUserList($roleUser,$all = true){
        if ($all) {
            return User::where(['creator_id' => $roleUser->id])->get();
            
        } else {
            return User::where("is_active", true)->get();
        }
    }


    public function store($data): User
    {
        $data = array_merge(['id' => (new UtilsCommonService)->generatePrimaryKey(),'is_email_verified' => false], $data);
        $model = new User($data); // insert
        $model->identity_key = (new UtilsCommonService)->generateUserIdentityKey('usr');
        $model->save();
        return $model->refresh(); # re-hydrate the existing model using fresh data from the database
    }

    public function update($id, $data): User
    {
        $user = User::where("id", $id)->first();
        if ($user) {
            $user->update($data);
            return $user->refresh();
        }
        return null;
    }

    public function delete($userId, $companyId): User
    {
        $user = User::where("id", $userId)->first();
        if ($user && $companyId) {
            $companies = array_values(array_filter(
                $user->companies,
                function($comp) use ($companyId) {
                    return $comp != $companyId;
                }
            ));
            $companyMapp = array_values(array_filter(
                $user->company_mapping,
                function($compMapp) use ($companyId) {
                    return $compMapp['id'] != $companyId;
                }
            ));
            $user->companies = $companies;
            $user->company_mapping = $companyMapp;
            $user->save();
            return $user->refresh();
        }
        return null;
    }

    public function details($id, $whereData = []): User
    {
        $user = User::where("id", $id)
            ->when(!empty($whereData), function ($query) use ($whereData) {
                $query->where($whereData);
            })
            ->active()->first();

        return $user;
    }

    public function login($credentials)
    {
        return Auth::attempt($credentials);
    }

    public function loginUsingUser(User $user)
    {
        return Auth::login($user);
    }
        
    /**
     * verifyToken
     *
     * @param  mixed $token
     * @param  mixed $type
     * @return User
     */
    public function verifyToken(String $token,String $type,bool $isCheckOnly = false) : User | null
    {
        
        if($token){
            
            $tokenArr = explode("===",$token);
    
            if(count($tokenArr) >= 2){

                $index = (int)$tokenArr[1];
                $whereTokenSlug = "verifications[{$index}].token_slug";
                $whereTokenVerified = "verifications[{$index}].verification_status";
                //$whereType = "verifications[{$index}].verification_type";

                if($isCheckOnly){

                    $user = User::select(['id','email','password'])
                    ->where($whereTokenSlug,$token)
                    ->first(); 
                
                }else{

                    $user = User::select(['id','email','password'])
                    ->where($whereTokenSlug,$token)
                    ->where($whereTokenVerified,'unverified')
                    //->where($whereType,$type)
                    ->first();

                    if(!empty($user)){
                        $user->update(['is_email_verified' => true]);
                        $user->save();
                    }
                }


                if(!empty($user)){

                    $user->update(['is_active' => true]);
                    $user->save();

                    $tokenObj = $user->verifications[$index];
                    $tokenObj['verification_status'] = 'verified';
                    $tokenObj['verified_at'] = (string)now();
                    $updateExpression = "SET #col[{$index}] = :data";

                    DynamoDb::table(User::TABLE)
                    ->setKey(DynamoDb::marshalItem(['id' => $user->id]))
                    ->setUpdateExpression($updateExpression)
                    ->setExpressionAttributeName('#col', 'verifications')
                    ->setExpressionAttributeValue(':data', DynamoDb::marshalValue($tokenObj))
                    ->prepare()
                    ->updateItem();

                    if(isset($tokenObj['company']) && !empty($tokenObj['company'])){
                       self::changeCompanyMappingStatus($user->id,$tokenObj['company'],true);
                    }
                    
                    return $user;
                }
            }
        }
        return null;
    }

    public function getAuthExpiry(){
      return  Carbon::now()->addHour();
      //return  Carbon::now()->addMinute();
    }

    public function saveCompanyMapping($userId,$companyId,$departments,$roles,$status){
        
        $user = User::where(['id' => $userId])->first();

        if(empty($user->companies)){

            $user->update(['companies' => [$companyId]]);
            $user->save();

        }else if(!in_array($companyId,$user->companies)){

            $companies = array_merge([$companyId],$user->companies);
            $user->update(['companies' => $companies]);
            $user->save();

            // keep for reference
            // $userTableUpdateExp = "SET #col = list_append(#col, :data)";
            // DynamoDb::table(User::TABLE)
            // ->setKey(DynamoDb::marshalItem(['id' => $userId]))
            // ->setUpdateExpression($userTableUpdateExp)
            // ->setExpressionAttributeName('#col', 'companies')
            // ->setExpressionAttributeValue(':data', DynamoDb::marshalValue($companyId))
            // ->prepare()
            // ->updateItem();
        }

        $departmentMapp = [];

        if(!empty($departments)){
            foreach($departments as $departmentId){
                $departmentMapp[] = ['id' => $departmentId,'roles' => ['slug' => Role::DEPARTMENT_ADMIN]];
            }
        }

        $roleMapp = [];
        if(!empty($roles)){
            foreach($roles as $role){
               $roleMapp[] = ['slug' => $role];
            }
        }

        $itemIndex = NULL;
        $company = ['id' => $companyId,'is_active' => $status ? true : false,'roles' => $roleMapp, 'departments' => $departmentMapp];
        if(!empty($user->company_mapping)){
            foreach($user->company_mapping as $index => $comp) {
                if($comp['id'] == $companyId) {
                    $itemIndex = $index;
                    break;
                }
            }
            if($itemIndex === NULL) {
                $itemIndex = count($user->company_mapping);
            }
            $queryData = $company;
            $updateExpression = "SET #col[{$itemIndex}] = :data";
        }else{
            $queryData = [$company];
            $updateExpression = "SET #col = :data";
        }
       
        DynamoDb::table(User::TABLE)
        ->setKey(DynamoDb::marshalItem(['id' => $userId]))
        ->setUpdateExpression($updateExpression)
        ->setExpressionAttributeName('#col', 'company_mapping')
        ->setExpressionAttributeValue(':data', DynamoDb::marshalValue($queryData))
        ->prepare()
        ->updateItem();

        return true;
    }  
    
    public function changeCompanyMappingStatus($userId,$companyId,$status){

        $user = User::where(['id' => $userId])->first();
    
        $companyObj = [];
        $companyIndex = NULL;

        if(!empty($user->companies)){
            if(in_array($companyId,$user->companies)){
                $mapping = $user->company_mapping;
                foreach($mapping as $index => $company){
                    if($company['id'] == $companyId){
                        $company['is_active'] = $status;
                        $companyObj = $company;
                        $companyIndex = $index;
                    }
                }
            }
        }

        if(!empty($companyObj)){

            $updateExpression = "SET #col[{$companyIndex}] = :data";

            DynamoDb::table(User::TABLE)
            ->setKey(DynamoDb::marshalItem(['id' => $user->id]))
            ->setUpdateExpression($updateExpression)
            ->setExpressionAttributeName('#col', 'company_mapping')
            ->setExpressionAttributeValue(':data', DynamoDb::marshalValue($companyObj))
            ->prepare()
            ->updateItem();  
            return true;   
        }

        return false;
    }

    // Zoom
    public function saveUserZoomEmail($email,$userId,$companyId){
        
        $user = User::where(['id' => $userId])->first();

        if(!empty($user)){
            
            $connections = $user->zoom_pre_connections && count($user->zoom_pre_connections) ? $user->zoom_pre_connections : [];
            $connectionArray = [];
            if(empty($connections)){
                $connectionArray[] = ['company_id' => $companyId, 'email' => $email];
            }else{
                $existObject = null;
                foreach($connections as $conn){
                    if($conn['email'] == $email){
                        $existObject = $conn;
                    }
                }

                if(empty($existObject)){
                    array_push($connections,['company_id' => $companyId, 'email' => $email]);   
                    $connectionArray = $connections;
                }
            }
            
            $user->update(['zoom_pre_connections' =>  $connectionArray]);
            return true;
        }

        return false;
    }

    // public function removeUserZoomIntegration($companyId, $userId, $email) {
    //     $user = User::find($userId);
    //     $user = ParticularUserData::from($user);

    //     if(!empty($user)) {
    //         $preConnections = PreConnection::where('email', $email)->get();
    //         if (!empty($preConnections) && count($preConnections) > 0) {
    //             foreach($preConnections as $connection){
    //                 $con = PreConnection::find($connection->id);
    //                 $con->delete();
    //             }
    //         }

    //         if(!empty($user->company_mapping) && count($user->company_mapping) > 0) {
    //             $companyMapp = $user->company_mapping;
    //             foreach($companyMapp as $i => $comp) {
    //                 if($comp['id'] == $companyId) {
    //                     if( isset($comp["integrations"])) {
    //                         foreach($comp["integrations"] as $index=> $integration){
    //                             if(isset($integration['integration_obj']['email']) && $integration['integration_obj']['email'] == $email){
    //                                 if(count($comp["integrations"]) <= 1) {
    //                                     unset($comp["integrations"]);
    //                                 } else {
    //                                     unset($comp["integrations"][$index]);
    //                                 }
    //                                 $updateExpression = "SET #col[{$i}] = :data";
    //                                 DynamoDb::table(User::TABLE)
    //                                     ->setKey(DynamoDb::marshalItem(['id' => $userId]))
    //                                     ->setUpdateExpression($updateExpression)
    //                                     ->setExpressionAttributeName('#col', 'company_mapping')
    //                                     ->setExpressionAttributeValue(':data', DynamoDb::marshalValue($comp))
    //                                     ->prepare()
    //                                     ->updateItem();
    //                                     break;
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //         }
    //         return true;
    //     }
    //     return false;
    // }

    public function removeAdminZoomIntegration($userId, $email) {
        if($userId && $email) {
            $finalInt = [];
            $user = User::find($userId)->first();
            if($user) {
                $integrations = $user->zoom_integrations;
                dd($user);
                $finalInt = array_values(array_filter($integrations, function($int) use ($email) {
                    return ($int['email'] != $email);
                }));
                $user->update(['zoom_integrations'=> $finalInt]);
                return $user->refresh();
            }
            return false;
        }
        return false;
    }

    public function getSelfZoomEmails($userId,$companyId){
        $emails = [];
        $user = User::find($userId);
        $companyMapping = $user->company_mapping;
        if(!empty($companyMapping)){
            foreach($companyMapping as $mapCompany){
                if($mapCompany['id'] == $companyId){
                    $integrations = $mapCompany['integrations'];
                    if($integrations){
                        foreach($integrations as $integration){
                            if($integration['integration_type'] == 'zoom'){
                                $emails[] = $integration['integration_obj']['email'];
                            }
                        }
                    }
                }
            }
            return $emails;
        }

        return [];
    }

    public function getAdminZoomEmails($userId) {
        $emails = [];
        $user = User::find($userId);
        $zoom_integrations = $user->zoom_integrations;
        if(!empty($zoom_integrations)){
            foreach($zoom_integrations as $integration) {
                $emails[] = $integration['email'];
            }
            return $emails;
        }
        return [];
    }

}