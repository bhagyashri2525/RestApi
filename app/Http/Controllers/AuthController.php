<?php

namespace App\Http\Controllers;

// use App\Models\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\Utils\ApiService as API;
use App\Services\Auth\UserService;
use App\Services\Validation\ValidationService;
use App\Services\Validation\ValidationService as VS;
use App\Services\Company\CompanyService;
use App\Data\UserData;
use App\Models\Company;
use App\Models\Role;
use App\Services\Email\EmailService;
use Exception;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            if ($validator->fails()) {
                return API::response(API::FAIL, [], $validator->messages()->first());
            }

            $user = User::where('email', $request->email)->first();
    
            if ($user) {
                
                $credentials = $request->only('email', 'password');
                $token = (new UserService)->login($credentials);
                $user = null;
                $expiry = null;


                if ($token) {
                    $user = Auth::user();
                    $user = UserData::from($user);
                    $expiry = (new UserService)->getAuthExpiry();
                }
                
                return API::response(!empty($user) ? API::SUCCESS : API::FAIL, ['token' => $token, 'user' => $user, 'expiry' => $expiry]);
            }
            return API::response(API::FAIL);

        } catch (Exception $e) {

            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                // Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }

    public function register(Request $request)
    {
        try {
           
            $validator = Validator::make($request->all(), [
                'display_name' => ['required'],
                'email' => ['required', 'email'],
                'password' => ['required'],
                'company' => ['required'],
                // 'g-recaptcha-response' => ['required', new ReCaptcha]
            ]);

            if ($validator->fails()) {
                return API::response(API::FAIL, [], $validator->messages()->first());
            }

            if ((new ValidationService)->isEmailExist($request->email)) {
                return API::response(API::FAIL, [], VS::MESSSAGES[VS::EMAIL_ALREADY_REGISTERED]);
            }

            // $request->request->remove('g-recaptcha-response');
            $details = ['company' => $request->company];
            $request->request->add(['details' => $details]);
            $companyName = $request->company;
            
            $user = (new UserService)->registerUser($request->all());
            $slug = (new CompanyService)->createDummyCompanySlug($companyName);

            //Adding type to company data

            $companyData = ['slug' => $slug,'name' => $companyName, 'type' => Company::COMPANY_TYPES['trial'], 'is_active' => true];
            $company = (new CompanyService)->store( $companyData);
            $role = [Role::COMPANY_MASTER_ADMIN];
            (new UserService)->saveCompanyMapping($user->id,$company->id,[],$role,true);
            $tokenObj = (new EmailService)->generateVerificationToken($user->id,User::EMAIL_VERIFICATION_TYPE['email']);
            // (new EmailService)->sendVerificationLink($user->id,'email',$tokenObj['token_slug']);
            
            return API::response(!empty($user) ? API::SUCCESS : API::ERROR, ['user' => $user]);

        } catch (Exception $e) {

            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                echo "Line no: ". $e->getLine();
            }
            return API::response(API::ERROR);
        }
    }

    public function verifyAccount(Request $request)
    {

        try {

            $alertMessage = ['code' => '','content' => ''];

            $validator = Validator::make($request->all(), [
                'token' => ['required'],
                // 'g-recaptcha-response' => ['required', new ReCaptcha]
            ]);

            if ($validator->fails()) {
                $alertMessage = ['code' => 'alert-warning','content' => $validator->messages()->first(),'type' => 'page'];
                return API::response(API::FAIL, ['alert' => $alertMessage], $validator->messages()->first());
            }

            $user = (new UserService)->verifyToken($request->token,'email');

            if (!is_null($user)) {
               
                if(empty($user->password)){
                    $tokenObj = (new EmailService)->generateVerificationToken($user->id,'password');
                    $redirectUrl = route('user.update-password', $tokenObj['token_slug']);
                    return API::response(API::SUCCESS,['alert' => $alertMessage,'action' => 'redirect','redirect_url' => $redirectUrl,'message' => 'Success']);
                }

                $token = (new UserService)->loginUsingUser($user);
                $user = null;
                if ($token) {
                    $user = UserData::from(Auth::user());
                    $expiry = (new UserService)->getAuthExpiry();

                    $alertMessage = ['code' => '','content' => '','type' => 'global'];

                    return API::response(API::SUCCESS,['alert' => $alertMessage,'token' => $token,'action' => 'login','user' => $user,'message' => 'Success','expiry' => $expiry]);   
                }else{
                    $alertMessage = ['code' => 'alert-warning','content' => 'Something went wrong.','type' => 'page'];
                    return API::response(API::FAIL,['message' => 'Fail','alert' => $alertMessage]); 
                }

            }else{
                $alertMessage = ['code' => 'alert-danger','content' => 'Sorry your email cannot be identified.'];
                return API::response(API::FAIL,['message' => 'Fail','alert' => $alertMessage]);
            }
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                // Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }
      /**
     * welcomeAccount
     *
     * @param  mixed $request
     * @return void
     */
    public function welcomeAccount(Request $request){
        try {

            $alertMessage = ['code' => '','content' => ''];

            $validator = Validator::make($request->all(), [
                'token' => ['required'],
                // 'g-recaptcha-response' => ['required', new ReCaptcha]
            ]);

            if ($validator->fails()) {
                $alertMessage = ['code' => 'alert-warning','content' => $validator->messages()->first(),'type' => 'page'];
                return API::response(API::FAIL, ['alert' => $alertMessage], $validator->messages()->first());
            }

            $user = (new UserService)->verifyToken($request->token,'email');

            if (!is_null($user)) {

                $redirectUrl = '';
               
                if(!empty($user->password)){
                    $redirectUrl = route('user.verify', $request->token);
                }else{
                    $redirectUrl = route('user.update-password', $request->token);
                }

                return API::response(API::SUCCESS,['redirectUrl' => $redirectUrl]);
                
            }else{
                $alertMessage = ['code' => 'alert-danger','content' => 'Something went wrong.'];
                return API::response(API::FAIL,['message' => 'Fail','alert' => $alertMessage]);
            }
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                // Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }
    
    /**
     * sendResetPasswordLink
     *
     * @param  mixed $request
     * @return void
     */
    public function sendResetPasswordLink(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'email' => ['required','email'],
                // 'g-recaptcha-response' => ['required', new ReCaptcha]
            ]);

            if ($validator->fails()) {
                $alertMessage = ['code' => 'alert-warning','content' => $validator->messages()->first(),'type' => 'page'];
                return API::response(API::FAIL, ['alert' => $alertMessage], $validator->messages()->first());
            }

            $user = User::where('email', $request->email)->first();

            if (!is_null($user)) {
                $user->password = NULL;
                $user->save();
                $tokenObj = (new EmailService)->generateVerificationToken($user->id,User::EMAIL_VERIFICATION_TYPE['password']);
                // (new EmailService)->sendVerificationLink($user->id, User::EMAIL_VERIFICATION_TYPE['password'],$tokenObj['token_slug']);
                return API::response(API::SUCCESS);
            }
            return API::response(API::ERROR);

        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                // Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }
    
    /**
     * updatePassword
     *
     * @param  mixed $request
     * @return void
     */
    public function updatePassword(Request $request)
    {
        try {
            $token = $request->token;
            $user = (new UserService)->verifyToken($token,'password', true) ;
            
            if (!is_null($user) && (new UserService())->setPassword($user->id,$request->password) ) {
                return API::response(API::SUCCESS);
            }
            return API::response(API::FAIL);
        } catch (Exception $e) {

            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                // Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }
    
    /**
     * refreshToken
     *
     * @param  mixed $request
     * @return void
     */
    public function refreshToken(Request $request){
        $expiry = (new UserService)->getAuthExpiry();
        return API::response(API::SUCCESS,['token' => Auth::refresh(),'expiry' => $expiry]);
    }
}
