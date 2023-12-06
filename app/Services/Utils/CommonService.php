<?php

namespace App\Services\Utils;

use App\Models\Event;
use App\Models\Role;
use DateTime;
use DatePeriod;
use DateInterval;
use Illuminate\Support\Str;
use stdClass;

class CommonService
{
    public function generateUuid($limit ='')
    {
        return (string) Str::uuid($limit);
    }

    public function generatePrimaryKey()
    {
        return (string) Str::orderedUuid();
    }

    public function generateUniqueString($range = 32){
        return strtolower((string)Str::random($range));
    }

    public function generateUserIdentityKey($str)
    {
        return (string) $str . '-' . Str::orderedUuid();
    }

    public function generateUuidForEvent($companyId)
    {
        $slug = (string) strtolower(Str::random(12));
        $exist = Event::where(['slug' => $slug])->first();
        if(!empty($exist)){
            self::generateUuidForEvent($companyId);
        }else{
            return $slug;
        }
    }

    public function generateEmailVerificationToken()
    {
        return (string) Str::random(64);
    }

    public function generateForgetPasswordVerifictionToken()
    {
        return (string) Str::random(64);
    }

    public function proccessSlug($slug){
        $object = new stdClass();
        $arr = explode("==",$slug);
        $object->id = $arr[0];
        $object->index = $arr[1];
        return (object)['id' => $arr[0],'index' => $arr[1]];
        //return (object)['id' => $arr[0],'index' => $arr[1]];
    }

    public function timezoneList(){
        return array(
            'Pacific/Midway'       => "(GMT-11:00) Midway Island",
            'US/Samoa'             => "(GMT-11:00) Samoa",
            'US/Hawaii'            => "(GMT-10:00) Hawaii",
            'US/Alaska'            => "(GMT-09:00) Alaska",
            'US/Pacific'           => "(GMT-08:00) Pacific Time (US &amp; Canada)",
            'America/Tijuana'      => "(GMT-08:00) Tijuana",
            'US/Arizona'           => "(GMT-07:00) Arizona",
            'US/Mountain'          => "(GMT-07:00) Mountain Time (US &amp; Canada)",
            'America/Chihuahua'    => "(GMT-07:00) Chihuahua",
            'America/Mazatlan'     => "(GMT-07:00) Mazatlan",
            'America/Mexico_City'  => "(GMT-06:00) Mexico City",
            'America/Monterrey'    => "(GMT-06:00) Monterrey",
            'Canada/Saskatchewan'  => "(GMT-06:00) Saskatchewan",
            'US/Central'           => "(GMT-06:00) Central Time (US &amp; Canada)",
            'US/Eastern'           => "(GMT-05:00) Eastern Time (US &amp; Canada)",
            'US/East-Indiana'      => "(GMT-05:00) Indiana (East)",
            'America/Bogota'       => "(GMT-05:00) Bogota",
            'America/Lima'         => "(GMT-05:00) Lima",
            'America/Caracas'      => "(GMT-04:30) Caracas",
            'Canada/Atlantic'      => "(GMT-04:00) Atlantic Time (Canada)",
            'America/La_Paz'       => "(GMT-04:00) La Paz",
            'America/Santiago'     => "(GMT-04:00) Santiago",
            'Canada/Newfoundland'  => "(GMT-03:30) Newfoundland",
            'America/Buenos_Aires' => "(GMT-03:00) Buenos Aires",
            'Greenland'            => "(GMT-03:00) Greenland",
            'Atlantic/Stanley'     => "(GMT-02:00) Stanley",
            'Atlantic/Azores'      => "(GMT-01:00) Azores",
            'Atlantic/Cape_Verde'  => "(GMT-01:00) Cape Verde Is.",
            'Africa/Casablanca'    => "(GMT) Casablanca",
            'Europe/Dublin'        => "(GMT) Dublin",
            'Europe/Lisbon'        => "(GMT) Lisbon",
            'Europe/London'        => "(GMT) London",
            'Africa/Monrovia'      => "(GMT) Monrovia",
            'Europe/Amsterdam'     => "(GMT+01:00) Amsterdam",
            'Europe/Belgrade'      => "(GMT+01:00) Belgrade",
            'Europe/Berlin'        => "(GMT+01:00) Berlin",
            'Europe/Bratislava'    => "(GMT+01:00) Bratislava",
            'Europe/Brussels'      => "(GMT+01:00) Brussels",
            'Europe/Budapest'      => "(GMT+01:00) Budapest",
            'Europe/Copenhagen'    => "(GMT+01:00) Copenhagen",
            'Europe/Ljubljana'     => "(GMT+01:00) Ljubljana",
            'Europe/Madrid'        => "(GMT+01:00) Madrid",
            'Europe/Paris'         => "(GMT+01:00) Paris",
            'Europe/Prague'        => "(GMT+01:00) Prague",
            'Europe/Rome'          => "(GMT+01:00) Rome",
            'Europe/Sarajevo'      => "(GMT+01:00) Sarajevo",
            'Europe/Skopje'        => "(GMT+01:00) Skopje",
            'Europe/Stockholm'     => "(GMT+01:00) Stockholm",
            'Europe/Vienna'        => "(GMT+01:00) Vienna",
            'Europe/Warsaw'        => "(GMT+01:00) Warsaw",
            'Europe/Zagreb'        => "(GMT+01:00) Zagreb",
            'Europe/Athens'        => "(GMT+02:00) Athens",
            'Europe/Bucharest'     => "(GMT+02:00) Bucharest",
            'Africa/Cairo'         => "(GMT+02:00) Cairo",
            'Africa/Harare'        => "(GMT+02:00) Harare",
            'Europe/Helsinki'      => "(GMT+02:00) Helsinki",
            'Europe/Istanbul'      => "(GMT+02:00) Istanbul",
            'Asia/Jerusalem'       => "(GMT+02:00) Jerusalem",
            'Europe/Kiev'          => "(GMT+02:00) Kyiv",
            'Europe/Minsk'         => "(GMT+02:00) Minsk",
            'Europe/Riga'          => "(GMT+02:00) Riga",
            'Europe/Sofia'         => "(GMT+02:00) Sofia",
            'Europe/Tallinn'       => "(GMT+02:00) Tallinn",
            'Europe/Vilnius'       => "(GMT+02:00) Vilnius",
            'Asia/Baghdad'         => "(GMT+03:00) Baghdad",
            'Asia/Kuwait'          => "(GMT+03:00) Kuwait",
            'Africa/Nairobi'       => "(GMT+03:00) Nairobi",
            'Asia/Riyadh'          => "(GMT+03:00) Riyadh",
            'Europe/Moscow'        => "(GMT+03:00) Moscow",
            'Asia/Tehran'          => "(GMT+03:30) Tehran",
            'Asia/Baku'            => "(GMT+04:00) Baku",
            'Europe/Volgograd'     => "(GMT+04:00) Volgograd",
            'Asia/Muscat'          => "(GMT+04:00) Muscat",
            'Asia/Tbilisi'         => "(GMT+04:00) Tbilisi",
            'Asia/Yerevan'         => "(GMT+04:00) Yerevan",
            'Asia/Kabul'           => "(GMT+04:30) Kabul",
            'Asia/Karachi'         => "(GMT+05:00) Karachi",
            'Asia/Tashkent'        => "(GMT+05:00) Tashkent",
            'Asia/Kolkata'         => "(GMT+05:30) Kolkata",
            'Asia/Kathmandu'       => "(GMT+05:45) Kathmandu",
            'Asia/Yekaterinburg'   => "(GMT+06:00) Ekaterinburg",
            'Asia/Almaty'          => "(GMT+06:00) Almaty",
            'Asia/Dhaka'           => "(GMT+06:00) Dhaka",
            'Asia/Novosibirsk'     => "(GMT+07:00) Novosibirsk",
            'Asia/Bangkok'         => "(GMT+07:00) Bangkok",
            'Asia/Jakarta'         => "(GMT+07:00) Jakarta",
            'Asia/Krasnoyarsk'     => "(GMT+08:00) Krasnoyarsk",
            'Asia/Chongqing'       => "(GMT+08:00) Chongqing",
            'Asia/Hong_Kong'       => "(GMT+08:00) Hong Kong",
            'Asia/Kuala_Lumpur'    => "(GMT+08:00) Kuala Lumpur",
            'Australia/Perth'      => "(GMT+08:00) Perth",
            'Asia/Singapore'       => "(GMT+08:00) Singapore",
            'Asia/Taipei'          => "(GMT+08:00) Taipei",
            'Asia/Ulaanbaatar'     => "(GMT+08:00) Ulaan Bataar",
            'Asia/Urumqi'          => "(GMT+08:00) Urumqi",
            'Asia/Irkutsk'         => "(GMT+09:00) Irkutsk",
            'Asia/Seoul'           => "(GMT+09:00) Seoul",
            'Asia/Tokyo'           => "(GMT+09:00) Tokyo",
            'Australia/Adelaide'   => "(GMT+09:30) Adelaide",
            'Australia/Darwin'     => "(GMT+09:30) Darwin",
            'Asia/Yakutsk'         => "(GMT+10:00) Yakutsk",
            'Australia/Brisbane'   => "(GMT+10:00) Brisbane",
            'Australia/Canberra'   => "(GMT+10:00) Canberra",
            'Pacific/Guam'         => "(GMT+10:00) Guam",
            'Australia/Hobart'     => "(GMT+10:00) Hobart",
            'Australia/Melbourne'  => "(GMT+10:00) Melbourne",
            'Pacific/Port_Moresby' => "(GMT+10:00) Port Moresby",
            'Australia/Sydney'     => "(GMT+10:00) Sydney",
            'Asia/Vladivostok'     => "(GMT+11:00) Vladivostok",
            'Asia/Magadan'         => "(GMT+12:00) Magadan",
            'Pacific/Auckland'     => "(GMT+12:00) Auckland",
            'Pacific/Fiji'         => "(GMT+12:00) Fiji",
        );
    }

    public function strDateDifference(String $dateOne,String $dateTwo){
        return strtotime($dateOne) - strtotime($dateTwo);
    }

    public function generateZoomPassword(){
        return (string) Str::random(10);
    }

    public function dateTimeIntoZoomDateTimeFormat(String $dateTime){
        $dateTimeObject = new DateTime($dateTime); 
        return $dateTimeObject->format('Y-m-d\TH:i:s');
    }

    public function generateRoleMiddlewareString(String $adminRoles = '',String $companyRoles = '',String $eventRoles = ''){
        $roleStr = "";
        if(!empty($adminRoles) && !empty($companyRoles) && !empty($eventRoles)){
            $roleStr = "role:{$adminRoles},{$companyRoles},{$eventRoles}";
        }else if(!empty($adminRoles) && !empty($companyRoles)){
            $roleStr = "role:{$adminRoles},{$companyRoles}";
        }else if(!empty($adminRoles)){
            $roleStr = "role:{$adminRoles}";
        }else if(!empty($companyRoles)){
            $roleStr = "role:{$companyRoles}";
        }else if(!empty($eventRoles)){
            $roleStr = "role:{$eventRoles}";
        }
        return $roleStr;
    }

    public function roleStr($label = 'ADMIN_ALL_ROLES'){
        
        $roleStr = '';
        
        if($label == 'ADMIN_COMPANY_EVENT_ALL'){
            $roleStr = self::generateRoleMiddlewareString(Role::allAdminRoles(),Role::allCompanyRoles(),Role::allEventRoles());
        }else if($label == 'ADMIN_COMPANY_ALL'){
            $roleStr = self::generateRoleMiddlewareString(Role::allAdminRoles(),Role::allCompanyRoles());
        }else if($label == 'COMPANY_ALL_ROLES'){
            $roleStr = self::generateRoleMiddlewareString('',Role::allCompanyRoles());
        }else if($label == 'EVENT_ALL_ROLES'){
            $roleStr = self::generateRoleMiddlewareString(Role::allEventRoles());
        }
        

        return $roleStr;
    }

    public function addMinutes(DateTime $date, int $minutes): DateTime {
        $interval = new DateInterval("PT{$minutes}M");
        $date->add($interval);
        return $date;
    }

    public function htmlDomDocumentToArray($element) {
        $data = array();
        $data['tag'] = $element->tagName;
        $data['attrs'] = array();
    
        foreach ($element->attributes as $attr) {
            $data['attrs'][$attr->name] = $attr->value;
        }
    
        $data['content'] = array();
    
        foreach ($element->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $data['content'][] = $child->nodeValue;
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                $data['content'][] = self::htmlDomDocumentToArray($child);
            }
        }
    
        return $data;
    }

    public function htmlArrayToDomDocument($data) {
        $doc = new \DOMDocument();
        $element = $doc->createElement($data['tag']);
    
        foreach ($data['attrs'] as $name => $value) {
            $element->setAttribute($name, $value);
        }
    
        if (!empty($data['content'])) {
            foreach ($data['content'] as $child) {
                if (is_string($child)) {
                    $element->appendChild($doc->createTextNode($child));
                } else {
                    $childElement = self::htmlArrayToDomDocument($child);
                    $element->appendChild($doc->importNode($childElement, true));
                }
            }
        }
    
        return $element;
    }

    function htmlStringToDomDocumentElement($htmlString)
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($htmlString, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_use_internal_errors(false);
        return $dom->documentElement;
    }
    
    public function getConnection() {
        $connection = env('DYNAMODB_CONNECTION');
        
        if($connection == "local") {
            return [
                'region' => 'stub',
                'version' => 'latest',
                'credentials' => [
                    'key' => 'dynamodb_local',
                    'secret' => 'secret',
                ],
                'endpoint' => env('DYNAMODB_LOCAL_ENDPOINT'),
            ];
        } else {
            return [
                'region' => env('DYNAMODB_REGION'),
                'version' => 'latest',
                'credentials' => [
                    'key' => env('DYNAMODB_KEY'),
                    'secret' => env('DYNAMODB_SECRET'),
                ],
            ];
        }
    }

    public function getDateAndTimeFromString($dateTimeStr){
        $datetime = new DateTime($dateTimeStr);
        $date = $datetime->format("M, j, Y");
        $time = $datetime->format("g:i A");
        $diff = $datetime->format("P");
        return [ $date, $time, $diff];
    }
    
    public function getStringRepresentationFormat($startDate, $endDate){
        $startDate = new DateTime($startDate);
        $endDate = new DateTime($endDate);
        return [ $startDate->format("Ymd\THis\Z"), $endDate->format("Ymd\THis\Z") ];
    }

    public function sanitizeStringForVariableName($input) {
        // Remove unsupported characters
        $sanitized = preg_replace('/[^a-zA-Z0-9_]/', '', $input);
        
        // Remove leading digits
        $sanitized = preg_replace('/^[0-9]+/', '', $sanitized);
        
        // Ensure the string is not empty
        if (empty($sanitized)) {
            $sanitized = 'add_to_calendar'; // Set a default name
        }
        
        return $sanitized;
    }

    function generateUniqueFileName($originalFileName) {
        $timestamp = now()->format('YmdHis');
        $randomString = Str::random(10); // You can adjust the length of the random string as needed.
        $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        
        return "{$timestamp}_{$randomString}.{$extension}";
    }
}