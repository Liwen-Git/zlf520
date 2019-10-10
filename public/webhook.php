<?php

// 获取push数据内容的方法
$requestBody = file_get_contents("php://input");    //接收数据
if (empty($requestBody)) {              //判断数据是不是空
    die('send fail');
}
$content = json_decode($requestBody, true);     //数据转换

// 只需这一行代码便可拉取
$res = shell_exec('cd /root/www/zlf520/ && git pull 2>&1');
$res_log = '-------------------------'.PHP_EOL;
$res_log .= ' 在' . date('Y-m-d H:i:s') . '向' . $content['repository']['name'] . '项目的' . $content['ref'] . '分支push'.$res;
file_put_contents("git-webhook.txt", $res_log, FILE_APPEND);//将每次拉取信息追加写入到日志里