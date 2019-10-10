<?php


namespace App\Http\Controllers;


use Illuminate\Support\Facades\Log;

class GitController extends Controller
{
    public function webHook()
    {
        // 获取push数据内容的方法
        $request = file_get_contents("php://input");
        $logArr = json_decode($request, true) ?: [];
        Log::info('git webhook:', $logArr);

        // 只需这一行代码便可拉取
        echo shell_exec('cd /root/www/zlf520 && git pull');
    }
}