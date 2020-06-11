<?php
$serv = new Swoole\Websocket\Server("0.0.0.0", 9999);

$serv->on('Open', function($server, $req) {
    echo "connection open: ".$req->fd;
});

$serv->on('Message', function($server, $frame) {
    echo "message: ".$frame->data;
    $server->push($frame->fd, json_encode(["hello", "world"]));
});

$serv->on('Close', function($server, $fd) {
    echo "connection close: ".$fd;
});

$serv->start();