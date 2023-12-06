<?php

namespace App\Services\Utils;

class ApiService
{
    const SUCCESS = "success"; # 200
    const FAIL = "fail"; # 200
    const VALIDATION_ERROR = "validation_error"; # 200
    const ERROR = "error"; # 200
    const BAD_REQUEST = "badRequest"; # 400
    const UNAUTHORIZED = "Unauthorized"; # 401
    const NOT_FOUND = "notFound"; # 404
    const FORBIDDEN = "forbidden"; # 403
    const INTERNAL_SERVER_ERROR = "internalServerError"; # 500

    const STATUS_CODE = [
        self::SUCCESS => 200,
        self::FAIL => 200,
        self::ERROR => 200,
        self::BAD_REQUEST => 400,
        self::UNAUTHORIZED => 401,
        self::NOT_FOUND => 404,
        self::FORBIDDEN => 403,
        self::INTERNAL_SERVER_ERROR => 500,
    ];

    const STATUS_MESSAGE = [
        self::SUCCESS => "Success",
        self::FAIL => "Fail",
        self::ERROR => "Error Occurs",
        self::BAD_REQUEST => "Bad Request",
        self::UNAUTHORIZED => "Unauthorized",
        self::NOT_FOUND => "Not Found",
        self::FORBIDDEN => "Forbidden",
        self::INTERNAL_SERVER_ERROR => "Internal Server Error",
    ];

    public static function response($status, $data = null, $message = '')
    {
        return response()->json([
            "status" => $status, # success,failed,error
            "message" => !empty($message) ? $message : @self::STATUS_MESSAGE[$status], # user friendly message
            "data" => $data, # response data
        ], @self::STATUS_CODE[$status]); # status code: 200,404 etc
    }

    public static function alert($errorType,$messageBody){
        return [
            'type' => $errorType,
            'message' => $messageBody
        ];
    }
}
