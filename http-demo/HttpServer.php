<?php
/*
 * swoole version 2.0.7
 */

//实例化内置的swoole http 服务器并指定服务器的监听的端口和ip
$serv = new Swoole\Http\Server("127.0.0.1", 9999);

//注册request 方法  解析请求时回调
$serv->on('Request', function($request, $response) {
    //$request  请求组件
    //$response 响应组件
    var_dump($request->get);
    var_dump($request->post);
    var_dump($request->cookie);
    var_dump($request->files);
    var_dump($request->header);
    var_dump($request->server);

    //设置响应cookie
    //设置响应header
    //结束响应并返回数据
    $response->cookie("User", "Swoole");
    $response->header("X-Server", "Swoole");
    $response->end("<h1>Hello Swoole!</h1>");
});
//启动swoole 服务器
$serv->start();