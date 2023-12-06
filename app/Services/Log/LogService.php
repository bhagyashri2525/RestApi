<?php

namespace App\Services\Log;


use App\Services\Utils\CommonService;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class LogService
{
    const TYPE_NONE = 'none';
    const TYPE_CREATE = 'create';
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';

    // public function create($primaryKey, $createdBy, $action, $type, $oldData = [], $newData = [])
    // {

    //     $oldData = !empty($oldData) ? json_encode($oldData) : '';
    //     $newData = !empty($newData) ? json_encode($newData) : '';

    //     $data = [
    //         'id' => (new CommonService)->generatePrimaryKey(),
    //         'primary_key' => $primaryKey,
    //         'created_by' => $createdBy,
    //         'action' => $action,
    //         'type' => $type,
    //         'old_data' => $oldData,
    //         'new_data' => $newData,
    //         'is_active' => true,
    //     ];
    //     $log = Log::create($data);
    //     return true;
    // }

    public function add($userId,$createdBy,$action,$table,$oldData,$newData){
        $data = ['user_id' => $userId,'created_by' => $createdBy,'table' => $table,'action' => $action, 'old_data' => $oldData, 'new_data' => $newData];
        $log = json_encode($data);
        //print_r($log);
        Log::channel('activity')->info($log);
        return true;
    }

    public function addEventLog($userId,$createdBy,$eventId,$action,$table,$oldData,$newData){
        $data = ['user_id' => $userId,'created_by' => $createdBy,'table' => $table,'event_id' => $eventId,'action' => $action, 'old_data' => $oldData, 'new_data' => $newData];
        $log = json_encode($data);
        //print_r($log);
        Log::channel('activity')->info($log);
        return true;
    }

    public function show($date = null){

        if(empty($date)){
            $date =  date('Y-m-d');
        }

        $filePath = storage_path("logs/activity_".$date."."."log");
        $data = [];

        if(File::exists($filePath)){

            $content = fopen($filePath,'r');

            while(!feof($content)){

                $line = fgets($content);

                if($line){

                    $dateTime = self::getStringBetween($line,'[',']');
                    $line = str_replace('['.$dateTime.']',"", $line);
                    $dateTime = Carbon::parse($dateTime)->format('Y-m-d H:i:s');
                    $json = str_replace('local.INFO:',"",$line);
                    $json = stripslashes(trim($json));
                    $logData = json_decode($json,true);
                    $data[] = ['timestamp' => $dateTime,'data' => $logData];
                }
            }
    
            fclose($content);
        }
        return $data;
    }

    function getStringBetween($str, $start, $end) {
        $pos1 = strpos($str, $start);
        $pos2 = strpos($str, $end);
        return substr($str, $pos1+1, $pos2-($pos1+1));
    }

    function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
     }
}
