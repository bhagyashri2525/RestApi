<?php

namespace App\Services\Email;

use App\Jobs\MailGunEmailReportToDBJob;
use App\Mail\SendMail;
use App\Models\User;
use App\Services\Uploads\S3Service;
use App\Services\Utils\CommonService;
use BaoPham\DynamoDb\Facades\DynamoDb;
use Illuminate\Support\Facades\Mail;
use stdClass;
use GuzzleHttp\Client;


class EmailService
{
    public function generateVerificationToken($userId,$type,$companyId = ''){

        $user = User::select(['id','email','verifications'])->where(['id' => $userId])->first();
        $token = (new CommonService)->generateEmailVerificationToken();
        $timestamp = (string)now();
        
        $verificationObj =  ['token_slug' => $token,'verification_type' => $type,'created_at' => $timestamp,'verification_status' => 'unverified'];

        if($companyId){
            $verificationObj['company'] = $companyId;
        }

        if(!empty($user->verifications)){
            $index = count($user->verifications);
            $verificationObj['token_slug'] = $verificationObj['token_slug'].'==='.(string)$index;
            $queryData = $verificationObj;
            $updateExpression = "SET #col[{$index}] = :data";
        }else{
            $verificationObj['token_slug'] = $verificationObj['token_slug'].'===0';
            $queryData = [$verificationObj];
            $updateExpression = "SET #col = :data";
        }

        DynamoDb::table(User::TABLE)
        ->setKey(DynamoDb::marshalItem(['id' => $userId]))
        ->setUpdateExpression($updateExpression)
        ->setExpressionAttributeName('#col', 'verifications')
        ->setExpressionAttributeValue(':data', DynamoDb::marshalValue($queryData))
        ->prepare()
        ->updateItem(); 

        return $verificationObj;
    }

    public function sendVerificationLink($userId,$type,$token)
    {
        $user = User::select(['id','email','display_name','verifications'])->where(['id' => $userId])->first();

        $data = new stdClass;
        $data->email = $user->email; 
        $data->variables = ['email' => $user->email,'name' => $user->display_name, 'token' => $token];
        $data->subject = 'Email Verification Mail';

        switch($type){

            case User::EMAIL_VERIFICATION_TYPE['email']:
                $data->template = 'emails.emailVerificationEmail';
                break;
            case User::EMAIL_VERIFICATION_TYPE['password']:
                $data->template = 'emails.emailVerificationEmail';
                break;
            case User::EMAIL_VERIFICATION_TYPE['welcome']:
                $data->template = 'emails.welcomeEmailWithVerificationCode';
                break;
            default:
                $data->template = 'emails.emailVerificationEmail';
                break;
        }

        self::sendMail($data);
    }


    public function sendMail($data)
    {
        /**
         * $data->email -> to email address
         * $data->subject -> mail subject
         * $data->variables -> dynamic bariable required at template
         * $data->template -> template path
         */

        Mail::to($data->email)->send(new SendMail($data));
    }

    public function triggerEmail($id){
        
        $client = new Client();

        try {
            $url = env('NEST_JS_EMAIL_TRIGGER_URL').'?id=' . urlencode($id);
            $response = $client->get($url);
            $statusCode = $response->getStatusCode();
            $content = $response->getBody()->getContents();
            return true;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return false;
        }

    }

    public function saveMailLog($companyId, $eventId, $emailLogId, $log, $beginDate = "", $endDate = "") {
        try {
            $jsonFileContent = json_encode($log);
            if($companyId && $eventId && $emailLogId) {
                $response = (new S3Service)->uploadMailGunMailReport($companyId, $eventId, $emailLogId, $jsonFileContent);
                if($response) {
                    dispatch(new MailGunEmailReportToDBJob($companyId, $eventId, $emailLogId, $response['ObjectURL']));
                }
            }
            return false;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return $e->getMessage();
        }
    }
}