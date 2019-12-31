<?php


namespace App\Http\Controllers;


use App\Result;
use App\ResultCode;

class StoryController extends Controller
{
    public function checkPassword()
    {
        $password = request('password', '');
        if ($password == 'zlfshigexiaojingling') {
            return Result::success();
        } else {
            return Result::error(ResultCode::NO_PERMISSION, '密码错误');
        }
    }
}