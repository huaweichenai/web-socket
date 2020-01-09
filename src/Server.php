<?php

namespace huaweichenai\webSocket;

/**
 * websocket server基类
 */
abstract class Server
{
    /**
     * @var string 监听IP
     */
    public $host = '0.0.0.0';

    /**
     * @var string 监听端口
     */
    public $port = '8888';

    /**
     * @var \swoole_websocket_server
     */
    public $server;

    /**
     * @var bool 是否加载swoole拓展
     */
    public $swoole_expand = false;


    public $configs = [];

    /**
     * @var boolean 是否开启自定义握手处理
     */
    public $handshake = false;

    public function __construct($host = null, $port = null, $config = [])
    {
        $this->configs = [
            // 日志文件路径
            'log_file' =>  dirname(__DIR__) . '/logs/swoole.log',
            // 进程的PID存储文件
            'pid_file' => dirname(__DIR__) . '/logs/swoole.server.pid',
        ];

        $this->configs = array_merge($this->configs, $config);
        
        if (!is_dir(dirname($this->configs['log_file']))) {
            mkdir(dirname($this->configs['log_file']));
        }

        if (!is_dir(dirname($this->configs['pid_file']))) {
            mkdir(dirname($this->configs['pid_file']));
        }

        $this->host = $host ? $host: $this->host;
        $this->port = $host ? $port: $this->port;
        $this->swoole_expand = extension_loaded('swoole');
    }

    /**
     * 启动server
     */
    public function run()
    {
        //判断是否加载swoole拓展
        if (!$this->swoole_expand) {
            $this->stdout('当前环境无swoole拓展,无法运行websocket');
            return false;
        }

        $pidFile = $this->configs['pid_file'];
        $pid = self::getPid($pidFile);
        if ($pid !== false) {
            $this->stdout("WebSocket服务已开启!\n");
            return false;
        }

        $this->server = new \swoole_websocket_server($this->host, $this->port);
        //Server配置选项
        $this->server->set($this->configs);

        // Server启动在主进程的主线程回调此函数
        $this->server->on('start', [$this, 'onStart']);
        //WebSocket建立连接后进行握手处理
        $this->handshake && $this->server->on('handshake', [$this, 'onHandshake']);
        //监听WebSocket成功并完成握手回调事件
        $this->server->on('open', [$this, 'onOpen']);
        //监听WebSocket消息事件
        $this->server->on('message', [$this, 'onMessage']);
        //使用http请求时执行,及直接在浏览器上输入websocket地址
        $this->server->on('request', [$this, 'onRequest']);
        //监听WebSocket连接关闭事件
        $this->server->on('close', [$this, 'onClose']);
        //此事件在Server正常结束时发生
        $this->server->on('shutdown', [$this, 'onShutdown']);

        //启动server,监听所有TCP/UDP端口
        $this->server->start();
    }

    /**
     * 停止server
     */
    public function stop()
    {
        $pidFile = $this->configs['pid_file'];
        $pid = self::getPid($pidFile);
        if ($pid === false) {
            $this->stdout("WebSocket服务已停止!\n");
            return false;
        }

        \swoole_process::kill($pid);

    }

    /**
     * @param \swoole_websocket_server $server
     * websocket 启动事件
     */
    abstract public function onStart($server);

    /**
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     * @return boolean 若返回`false`，则握手失败
     *
     * WebSocket客户端与服务器建立连接时的握手回调事件处理
     */
    abstract public function onHandshake($request, $response);

    /**
     * @param \swoole_websocket_server $server
     * @param \swoole_http_request $request
     *
     * WebSocket客户端与服务器建立连接并完成握手后的回调事件处理
     */
    abstract public function onOpen($server, $request);

    /**
     * @param \swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame 对象，包含了客户端发来的数据信息
     *
     * 当服务器收到来自客户端的数据时的回调事件处理
     */
    abstract public function onMessage($server, $frame);

    /**
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     *
     * 当服务器收到来自客户端的HTTP请求时的回调事件处理
     */
    abstract public function onRequest($request, $response);

    /**
     * @param \swoole_websocket_server $server
     * @integer $fd
     *
     * WebSocket客户端与服务器断开连接后的回调事件处理
     */
    abstract public function onClose($server, $fd);


    /**
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     * @return bool
     *
     * 通用的websocket握手处理
     */
    public function handshake($request, $response) {
        // websocket握手连接算法验证
        $secWebSocketKey = $request->header['sec-websocket-key'];
        $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
        if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
            $response->end();
            return false;
        }
        $key = base64_encode(sha1($request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        $headers = [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => $key,
            'Sec-WebSocket-Version' => '13',
        ];

        // WebSocket connection to 'ws://[host]:[port]/'
        // failed: Error during WebSocket handshake:
        // Response must not include 'Sec-WebSocket-Protocol' header if not present in request: websocket
        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }

        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }

        $response->status(101);
        $response->end();
    }


    /**
     * @param \swoole_websocket_server $server
     *
     * Server正常结束时的回调事件处理
     */
    abstract public function onShutdown($server);



    /**
     * 获取请求路由
     *
     * @param swoole_http_request $request
     */
    protected function getRoute($request)
    {
        return ltrim($request->server['request_uri'], '/');
    }

    /**
     * 获取请求的GET参数
     *
     * @param swoole_http_request $request
     */
    protected function getParams($request)
    {
        return $request->get;
    }

    /**
     * 日志信息输出函数
     */
    protected function stdout($string)
    {
        fwrite(\STDOUT, $string . "\n");
    }

    /**
     * Before Exec
     */
    protected function startTime()
    {
        $this->stdout(date('Y-m-d H:i:s'));
    }

    /**
     * @param $pidFile
     * @return bool|false|int|string
     *
     *
     * 获取进程PID
     */
    public static function getPid($pidFile)
    {

        if (!file_exists($pidFile)) {
            return false;
        }

        $pid = file_get_contents($pidFile);
        if (empty($pid)) {
            return false;
        }

        $pid = intval($pid);
        if (\swoole_process::kill($pid, 0)) {
            return $pid;
        } else {
            self::unlink($pidFile);
            return false;
        }
    }


    /**
     * Removes a file or symlink in a cross-platform way
     *
     * @param string $path
     * @return bool
     *
     * @since 2.0.14
     */
    public static function unlink($path)
    {
        $isWindows = DIRECTORY_SEPARATOR === '\\';

        if (!$isWindows) {
            return unlink($path);
        }

        if (is_link($path) && is_dir($path)) {
            return rmdir($path);
        }

        try {
            return unlink($path);
        } catch (\ErrorException $e) {
            // last resort measure for Windows
            if (is_dir($path)) {
                return false;
            }
            if (function_exists('exec') && file_exists($path)) {
                exec('DEL /F/Q ' . escapeshellarg($path));

                return !file_exists($path);
            }

            return false;
        }
    }

}
