<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use App\Services\Utils\ApiService as API;
use App\Services\Utils\CommonService;

class SiteController extends Controller
{
    public function getCountries(Request $request){
        $countries = Country::where(['is_active' => true])->get();
        $status = !empty($countries) ? API::SUCCESS : API::ERROR;
        $sortedObjects = collect($countries)->sortBy('name')->values();
        // print_r($sortedObjects);exit();
        return API::response($status, ['countries' => $sortedObjects]); 

    }

    public function timezoneList(){
        $timezones = (new CommonService)->timezoneList();
        return API::response('success',['list' => $timezones]);
    }

    public function getCountryList() {
        $path = public_path("plugin/formBuilder/finalCountries.json");
        $data = json_decode(file_get_contents($path), true);
        return $data;
    }

    public function getTimeZoneList() {
        $path = public_path("plugin/formBuilder/timeZoneList.json");
        $data = json_decode(file_get_contents($path), true);
        return $data;
    }
}
