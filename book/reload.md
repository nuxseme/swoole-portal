# reload



在我们修改了 PHP 代码后经常需要重启服务让代码生效，一台繁忙的后端服务器随时都在处理请求，如果管理员通过 `kill` 进程方式来终止 / 重启服务器程序，可能导致刚好代码执行到一半终止，没法保证整个业务逻辑的完整性。

`Swoole` 提供了柔性终止 / 重启的机制，管理员只需要向 `Server` 发送特定的信号或者调用 `reload` 方法，工作进程就可以结束，并重新拉起。[具体请参考 reload ()](https://wiki.swoole.com/#/server/methods?id=reload)

但有几点要注意：

首先要注意新修改的代码必须要在 `OnWorkerStart` 事件中重新载入才会生效，比如某个类在 `OnWorkerStart` 之前就通过 composer 的 autoload 载入了就是不可以的。

其次 `reload` 还要配合这两个参数 [max_wait_time](https://wiki.swoole.com/#/server/setting?id=max_wait_time) 和 [reload_async](https://wiki.swoole.com/#/server/setting?id=reload_async)，设置了这两个参数之后就能实现`异步安全重启`。

如果没有此特性，Worker 进程收到重启信号或达到 [max_request](https://wiki.swoole.com/#/server/setting?id=max_request) 时，会立即停止服务，这时 `Worker` 进程内可能仍然有事件监听，这些异步任务将会被丢弃。设置上述参数后会先创建新的 `Worker`，旧的 `Worker` 在完成所有事件之后自行退出，即 reload_async。

如果旧的 `Worker` 一直不退出，底层还增加了一个定时器，在约定的时间 ( [max_wait_time](https://wiki.swoole.com/#/server/setting?id=max_wait_time) 秒) 内旧的 `Worker` 没有退出，底层会强行终止。

示例：

```php
<?php
$serv = new Swoole\Server("0.0.0.0", 9501, SWOOLE_PROCESS);
$serv->set(array(
    'worker_num' => 1,
    'max_wait_time' => 60,
    'reload_async' => true,
));
$serv->on('receive', function (Swoole\Server $serv, $fd, $reactor_id, $data) {

    echo "[#" . $serv->worker_id . "]\tClient[$fd] receive data: $data\n";

    Swoole\Timer::tick(5000, function () {
        echo 'tick';
    });
});

$serv->start();
```

例如上面的代码 如果没有 reload_async 那么 onReceive 中创建的定时器将丢失，没有机会处理定时器中的回调函数。



停止

`SIGTERM`: 向主进程 / 管理进程发送此信号服务器将安全终止

在 PHP 代码中可以调用 `$serv->shutdown()` 完成此操作

`SIGUSR1`: 向主进程 / 管理进程发送 `SIGUSR1` 信号，将平稳地 `restart` 所有 `Worker` 进程

```bash
# 重启所有worker进程 
kill -USR1 主进程PID
```

`SIGUSR2`: 向主进程 / 管理进程发送 `SIGUSR2` 信号，将平稳地重启所有 `Task` 进程

```bash
# 仅重启task进程 
kill -USR2 主进程PID
```

在 PHP 代码中可以调用 `$serv->reload()` 完成此操作

```
bool $only_reload_taskworkrer
```

- 功能：是否仅重启 [Task 进程](https://wiki.swoole.com/#/learn?id=taskworker进程)
- 默认值：false
- 其它值：无



**Reload 有效范围**

`Reload` 操作只能重新载入 `Worker` 进程启动后加载的 PHP 文件，使用 `get_included_files` 函数来列出哪些文件是在 `WorkerStart` 之前就加载的 PHP 文件，在此列表中的 PHP 文件，即使进行了 `reload` 操作也无法重新载入。要关闭服务器重新启动才能生效。

```php
$serv->on('WorkerStart', function(Swoole\Server $server, int $workerId) {
    var_dump(get_included_files()); //此数组中的文件表示进程启动前就加载了，所以无法reload
});
```



**APC/OpCache**

如果 `PHP` 开启了 `APC/OpCache`，`reload` 重载入时会受到影响，有 `2` 种解决方案

- 打开 `APC/OpCache` 的 `stat` 检测，如果发现文件更新 `APC/OpCache` 会自动更新 `OpCode`
- 在 `onWorkerStart` 中加载文件（require、include 等函数）之前执行 `apc_clear_cache` 或 `opcache_reset` 刷新 `OpCode` 缓存

>  平滑重启只对 `onWorkerStart` 或 [onReceive](https://wiki.swoole.com/#/server/events?id=onreceive) 等在 `Worker` 进程中 `include/require` 的 PHP 文件有效
> -`Server` 启动前就已经 `include/require` 的 PHP 文件，不能通过平滑重启重新加载
> \- 对于 `Server` 的配置即 `$serv->set()` 中传入的参数设置，必须关闭 / 重启整个 `Server` 才可以重新加载
> -`Server` 可以监听一个内网端口，然后可以接收远程的控制命令，去重启所有 `Worker` 进程



命令模式可以grep出主进程id 对进程发起信号

php脚本模式可以使用reload,监听一个message端口，发送命令解析并执行对应的流程

OpCache  推荐开启自动更新检查配置项，每次reset可能导致大量的文件需要重建Opcache

onWorkStart的文件更新，单台机器只能停服更新

多台机器可以切换流量更新



