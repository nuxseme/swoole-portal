<?php
$serv = new Swoole\Server("127.0.0.1", 9501);
$serv->set(array(
    'worker_num' => 2,   //工作进程数量 ps 2+n  1*manager + 1*master  + n*work
    'daemonize' => 0, //是否作为守护进程
));
$serv->on('connect', function ($serv, $fd){
    echo "Client:Connect.\n";
});
$serv->on('receive', function ($serv, $fd, $from_id, $data) {
    $serv->send($fd, 'Swoole: '.$data.'from_id:'.$from_id.'worker_id:'.$serv->worker_id);
    //$serv->close($fd);//每次请求不关闭可以测试返回的workid
});
$serv->on('close', function ($serv, $fd) {
    echo "Client: Close.\n";
});
$serv->start();