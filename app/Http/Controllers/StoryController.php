<?php


namespace App\Http\Controllers;


use App\Http\Services\StoryService;
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

    public function list()
    {
        $param = [
            'wx_user_id' => request('wx_user_id', ''),
            'pageSize' => request('pageSize', 10),
        ];

        $data = StoryService::getList($param);
        return Result::success([
            'list' => $data->items(),
            'total' => $data->total(),
        ]);
    }

    public function add() {
        $this->validate(request(), [
            'wx_user_id' => 'required',
        ]);
        $data = [
            'wx_user_id' => request('wx_user_id'),
            'content' => request('content', '')
        ];

        $story = StoryService::add($data);

        return Result::success($story);
    }
}