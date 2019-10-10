<?php

// 获取push数据内容的方法
$requestBody = file_get_contents("php://input");
if (empty($requestBody)) {
    die('send fail');
}
$content = json_decode($requestBody, true);

// 只需这一行代码便可拉取
$res = shell_exec('cd /root/www/zlf520/ && git pull 2>&1');
// 日志
$res_log = '-------------------------'.PHP_EOL;
$res_log .= ' 在' . date('Y-m-d H:i:s') . '向' . $content['repository']['name'] . '项目的' . $content['ref'] . '分支push'.$res;
file_put_contents("git-webhook.txt", $res_log, FILE_APPEND);