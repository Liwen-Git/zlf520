<?php


namespace App\Exceptions;


use App\ResultCode;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseResponseException extends HttpResponseException
{
    public function __construct($message = '未知错误', $code = ResultCode::UNKNOWN)
    {
        $response = response([
            'code' => $code,
            'message' => $message,
            'timestamp' => time(),
        ]);
        parent::__construct($response);
    }
}