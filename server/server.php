<?php
//server只能在cli环境下实例化
$server = new Swoole\Server('127.0.0.1', 9999, SWOOLE_BASE, SWOOLE_SOCK_TCP);
$server->set(array(
    'worker_num' => 4,
    'daemonize' => false,
    'backlog' => 128,
));
$server->on('Connect', 'onConnect');
$server->on('Receive', 'onReceive');
$server->on('Close', 'onClose');

$server->start();
function onConnect(Swoole\Server $server, int $fd, int $from_id) {
    echo '属性列表';
    echo 'manager_pid',$server->manager_pid;
    echo 'master_pid',$server->master_pid;
    echo 'manager_pid';print_r($server->connections);
    echo 'setting';print_r($server->setting);
    $server->send($fd,'hello world', $from_id);
}
function onReceive(Swoole\Server $server, int $fd, int $from_id, string $data) {
    //$server_error = new Swoole\Server('127.0.0.1',9998);//在web环境中实例化server会报错
   // $server->send($fd,'server:'.$data, $from_id);
    $server->tick(5000, function() use ($server, $fd) {
        $server->send($fd, "hello world");
    });

}
function onClose(swoole_server $server, int $fd, int $reactorId) {

    $server->send($fd,'bye', $reactorId);
}


//$http = new Swoole\Http\Server("systemd");
//
//$http->set([
//    'daemonize' => true,
//    'pid_file' => '/run/swoole.pid',
//]);
//
//$http->on('request', function ($request, $response) {
//    $response->header("Content-Type", "text/html; charset=utf-8");
//    $response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
//});
//
//$http->start();