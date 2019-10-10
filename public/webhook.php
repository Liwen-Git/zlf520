<?php

// 获取push数据内容的方法
$request = file_get_contents("php://input");

// 只需这一行代码便可拉取
shell_exec('cd /root/www/zlf520 && git pull');