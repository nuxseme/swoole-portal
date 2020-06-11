<?php
$serv = new Swoole\Server("0.0.0.0", 9501, SWOOLE_PROCESS);
$port1 = $serv->listen("127.0.0.1", 9502, SWOOLE_SOCK_TCP);
$serv->set(array(
    'worker_num' => 1,
    'max_wait_time' => 60,
    'reload_async' => true,
));

$serv->on('WorkerStart',function ($server, $worker_id){
    echo 'workStart';
    print_r(get_included_files());
});
$serv->on('receive', function (Swoole\Server $serv, $fd, $reactor_id, $data) {

    echo "[#" . $serv->worker_id . "]\tClient[$fd] receive data: $data\n";

    /*Swoole\Timer::tick(5000, function () {
        echo 'tick';
    });*/
});

$port1->on('receive', function (Swoole\Server $serv, $fd, $reactor_id, $data) {
    $data = explode("\r\n", $data);
    if($data[0] == 'reload') {
        $serv->send($fd, 'Swoole-reload');
        $serv->reload();
    }else{
        $serv->send($fd, 'Swoole: '.$data);
    }


});

$serv->start();
