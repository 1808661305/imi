<?php

declare(strict_types=1);

namespace Imi\Swoole\Test\TCPServer\MainServer\Controller;

use Imi\ConnectContext;
use Imi\RequestContext;
use Imi\Swoole\Server\TcpServer\Route\Annotation\TcpAction;
use Imi\Swoole\Server\TcpServer\Route\Annotation\TcpController;
use Imi\Swoole\Server\TcpServer\Route\Annotation\TcpRoute;

/**
 * 数据收发测试.
 *
 * @TcpController
 */
class TestController extends \Imi\Controller\TcpController
{
    /**
     * 登录.
     *
     * @TcpAction
     * @TcpRoute({"action"="login"})
     *
     * @return void
     */
    public function login($data)
    {
        ConnectContext::set('username', $data->username);
        $this->server->joinGroup('g1', $this->data->getFd());

        return ['action' => 'login', 'success' => true, 'middlewareData' => RequestContext::get('middlewareData')];
    }

    /**
     * 发送消息.
     *
     * @TcpAction
     * @TcpRoute({"action"="send"})
     *
     * @param
     *
     * @return void
     */
    public function send($data)
    {
        $message = [
            'action'     => 'send',
            'message'    => ConnectContext::get('username') . ':' . $data->message,
        ];
        $this->server->groupCall('g1', 'send', $this->server->getBean(\Imi\Server\DataParser\DataParser::class)->encode($message));
    }

    /**
     * 测试重复路由警告.
     *
     * @TcpAction
     * @TcpRoute({"duplicated"="1"})
     *
     * @param
     *
     * @return void
     */
    public function duplicated1($data)
    {
    }

    /**
     * 测试重复路由警告.
     *
     * @TcpAction
     * @TcpRoute({"duplicated"="1"})
     *
     * @param
     *
     * @return void
     */
    public function duplicated2($data)
    {
    }
}
