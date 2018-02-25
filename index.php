<?php


require "Bot.php";

$bangGame = new Bot();


if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
    header('HTTP/1.1 204 No Content');
}
header("Content-Type: application/json");
//记录整体执行时间
$bangGame->log->markStart('all_t');
$ret = $bangGame->run();
$bangGame->log->markEnd('all_t');

//打印日志
//or 在register_shutdown_function增加一个执行函数
$bangGame->log->notice($bangGame->log->getField('url_t'));
$bangGame->log->notice();

print $ret;
