# webSocket
This is a service class that implements websocket (这是一个websocket的服务类，可以快速的帮助你实现websocket的一些基本功能)

[![Latest Stable Version](https://poser.pugx.org/huaweichenai/web-socket/v/stable)](https://packagist.org/packages/huaweichenai/web-socket) 
[![Total Downloads](https://poser.pugx.org/huaweichenai/web-socket/downloads)](https://packagist.org/packages/huaweichenai/web-socket) 
[![Latest Unstable Version](https://poser.pugx.org/huaweichenai/web-socket/v/unstable)](https://packagist.org/packages/huaweichenai/web-socket) 
[![License](https://poser.pugx.org/huaweichenai/web-socket/license)](https://packagist.org/packages/huaweichenai/web-socket)

## Installation<br>
```
composer require huaweichenai/web-socket
```

## Usage<br>

### Configuration<br>
```
$host = '0.0.0.0';
$port = '8888';
$configs = [
    // 日志文件路径
  'log_file' => dirname(__DIR__) . '/logs/swoole.log',
  // 进程的PID存储文件
  'pid_file' => dirname(__DIR__) . '/logs/swoole.server.pid'
]
```
其他的详细运行参数可与参考：https://www.wj0511.com/site/detail.html?id=423

### use<br>

#### websocket启动<br>
```
$server = new WebSocketServer($host, $port, $configs);
$server->handshake = true;//设置自定义握手配置
$server->run();
```

#### websocket停止<br>
```
$server = new WebSocketServer($host, $port, $configs);
$server->stop();
```

根据如上就可是简单的实现websocket运行,如果你需要在websocket运行期间：启动,握手,连接,接收消息,http响应,客户端关闭连接,服务端关闭连接自己设置自己的自定义方法的话
**1**
自己创建一个类，专门用户继承websocket，并进行复写各个阶段的事件
```
class Swoole extends WebSocketServer
{
    /**
     * @var bool 
     * 开启自定义握手处理
     */
    public $handshake = true;

    /**
     * @param \swoole_websocket_server $server
     * 
     * 自定义websocket服务启动处理
     */
    public function socketStart($server)
    {
        //业务代码
    }

    /**
     * @param \swoole_websocket_server $server
     * @param \swoole_http_request $request
     * 
     * 自定义websocket建立连接处理
     */
    public function socketOpen($server, $request)
    {
        //业务代码
    }

    /**
     * @param \swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame
     * 
     * 自定义websocket 接受客户端消息处理
     */
    public function socketMessage($server, $frame)
    {
        //业务代码
    }

    /**
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     * 
     * 自定义websocket握手处理
     */
    public function socketHandshake($request, $response)
    {
        //业务代码
        //业务代码
    }

    /**
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     * 
     * 自定义websocket http响应处理
     */
    public function socketRequest($request, $response)
    {
        //业务代码
    }

    /**
     * @param \swoole_websocket_server $server
     * @param $fd
     * 
     * 自定义websocket 客户端连接关闭处理
     */
    public function socketClose($server, $fd)
    {
        //业务代码
    }

    /**
     * @param \swoole_websocket_server $server
     * 
     * 自定义websocket 服务端正常关闭处理
     */
    public function socketShutdown($server)
    {
        //业务代码
    }


}
```
在上面的业务代码中我们可以使用下面方法向指定客户端发送信息
```
$this->sendMessage($request->fd, $server,$data);
```
还可以使用如下方法：
```
$this->getParams($request) //获取客户端连接路由
$this->getRoute($request) //获取客户端传参
```
***2***
自定义类创建好了之后
```
$server = new Swoole($host, $port, $configs);
$server->run();
```
