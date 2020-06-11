<?php
$cli = new Swoole\Http\Client('127.0.0.1', 9999);
$cli->setHeaders(['User-Agent' => "swoole"]);

$cli->post('/dump.php', array("test" => 'abc'), function ($cli) {
    echo $cli->body;
});