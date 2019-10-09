<?php


namespace App\Http\Controllers;


use App\Http\Services\WeChatService;

class WeChatController extends Controller
{
    public function serve()
    {
        $app = app('wechat.official_account');

        // 创建菜单
        $button = WeChatService::getMenu();
        $app->menu->create($button);

        // 自动回复消息
        $app->server->push(function ($message) {
            switch ($message['MsgType']) {
                case 'text':
                    return "很开心认识你~~~";
                    break;
                case 'event':
                    if ($message['Event'] == 'subscribe') {
                        return '欢迎关注多乐多乐!!!';
                    } else {
                        return "ヽ(￣▽￣)و";
                    }
                    break;
                default:
                    return "^_^";
                    break;
            }
        });

        return $app->server->serve();
    }
}