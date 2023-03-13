<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Session\Driver;

class Redis extends AbstractSessionDriverHandlerDriverHandle implements DriverInterface
{
    public function close()
    {
        // TODO: Implement close() method.
    }

    public function destroy($session_id)
    {
        // TODO: Implement destroy() method.
    }

    public function gc($maxlifetime)
    {
        // TODO: Implement gc() method.
    }

    public function open($save_path, $name)
    {
        // TODO: Implement open() method.
    }

    public function read($session_id)
    {
        // TODO: Implement read() method.
    }

    public function write($session_id, $session_data)
    {
        // TODO: Implement write() method.
    }
}
