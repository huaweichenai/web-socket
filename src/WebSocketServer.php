<?php

namespace huaweichenai\webSocket;

/**
 * WebSocketServer 通用类
 */
class WebSocketServer extends Server
{

    /**
     * @param \swoole_websocket_server $server
     *
     * 服务启动事件
     */
    public function onStart($server){
        $this->startTime();
        $this->stdout("**websocket 服务启动 **");
        $this->stdout("主进程的PID为: " . "{$server->master_pid}" . "\n");

        //websocket服务启动自定义处理
        $this->socketStart($server);
    }

    /**
     * @param \swoole_websocket_server $server
     *
     * 自定义websocket服务启动处理
     */
    public function socketStart($server) {}

    /**
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     * @return bool|void
     *
     * 握手处理事件
     */
    public function onHandshake($request, $response)
    {
        $this->startTime();
        $this->stdout("**websocket 建立握手**");
        $this->stdout("客户端连接ID为: " . "{$request->fd}" . "\n");

        //websocket握手自定义处理
        $this->socketHandshake($request, $response);

        //默认握手处理
        $this->handshake($request, $response);

        //触发客户端连接完成事件
        $this->server->defer(function() use ($request) {
            $this->onOpen($this->server, $request);
        });
    }

    /**
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     *
     * 自定义websocket握手处理
     */
    public function socketHandshake($request, $response) {}

    /**
     * @param \swoole_websocket_server $server
     * @param \swoole_http_request $request
     * 建立连接完成
     */
    public function onOpen($server, $request)
    {
        $this->startTime();

        $this->stdout("**websocket客户端 连接完成**");
        $this->stdout("客户端连接ID为: " . "{$request->fd}");

        $route = $this->getRoute($request);

        $this->stdout("websocket客户端 连接路由为: " . $route);
        $params = $this->getParams($request);
        $params = json_encode($params);
        $this->stdout("websocket客户端 get传参为: " . $params . "\n");

        //websocket建立连接自定义处理
        $this->socketOpen($server, $request);
    }

    /**
     * @param \swoole_websocket_server $server
     * @param \swoole_http_request $request
     *
     * 自定义websocket建立连接处理
     */
    public function socketOpen($server, $request) {}

    /**
     * @param \swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame
     * 接受客户端消息
     */
    public function onMessage($server, $frame)
    {
        $this->startTime();
        $this->stdout("**websocket 接收客户端消息**");
        $this->stdout('客户端连接ID为' . $frame->fd . '的客户端发送的消息为' . $frame->data . "\n");

        //websocket接受客户端信息自定义处理
        $this->socketMessage($server, $frame);
    }

    /**
     * @param \swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame
     *
     * 自定义websocket 接受客户端消息处理
     */
    public function socketMessage($server, $frame) {}

    /**
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     * http响应
     */
    public function onRequest($request, $response)
    {
        $this->startTime();

        $this->stdout("**websocket 路由响应**");

        $this->stdout("响应的客户端连接ID: " . $request->fd . "\n");
        $response->status(200);
        $response->end('success');

        //websockethttp响应自定义处理
        $this->socketRequest($request, $response);
    }

    /**
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     * 自定义websocket http响应处理
     */
    public function socketRequest($request, $response) {}

    /**
     * @param \swoole_websocket_server $server
     * @param $fd
     * 连接关闭
     */
    public function onClose($server, $fd)
    {
        $this->startTime();

        $this->stdout('**websocket客户端 连接关闭**');
        $this->stdout('关闭的客户端连接ID:' . $fd . "\n");

        //websocket客户端关闭自定义处理
        $this->socketClose($server, $fd);
    }

    /**
     * @param \swoole_websocket_server $server
     * @param $fd
     * 自定义websocket 客户端连接关闭处理
     */
    public function socketClose($server, $fd) {}

    /**
     * @param \swoole_websocket_server $server
     *
     * 正常关闭连接事件
     */
    public function onShutdown($server)
    {
        $this->startTime();

        $this->stdout("**websocket服务端 关闭**");
        $this->stdout("关闭主进程的PID为: " . $server->master_pid . "\n");

        //websocket服务正常关闭自定义处理
        $this->socketShutdown($server);

    }

    /**
     * @param \swoole_websocket_server $server
     *
     * 自定义websocket 服务端正常关闭处理
     */
    public function socketShutdown($server) {}

    /**
     *
     *
     * 给指定客户端发送消息
     */
    /**
     * @param $fd 客户端连接ID
     * @param \swoole_websocket_server $server
     * @param $data string|array 需要发送的消息
     */
    public function sendMessage($fd, $server, $data)
    {
        $data = is_array($data) ? json_encode($data) : $data;
        //发送消息
        $server->push($fd, $data);
    }


}