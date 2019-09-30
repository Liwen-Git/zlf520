<?php


namespace App\Http\Controllers;


class WeChatController extends Controller
{
    public function serve()
    {
        $app = app('wechat.official_account');
        $app->server->push(function () {
            return "欢迎关注WEN";
        });

        return $app->server->serve();
    }
}