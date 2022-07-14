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
    public function __construct(array $config)
    {
    }

    public function set($name, $value)
    {
        // TODO: Implement set() method.
    }

    public function get($name): mixed
    {
        // TODO: Implement get() method.
    }

    public function del($name)
    {
        // TODO: Implement del() method.
    }

    public function des()
    {
        // TODO: Implement des() method.
    }

    public function open()
    {
        // TODO: Implement open() method.
    }

    public function gc(int $sessMaxLifeTime)
    {
        // TODO: Implement gc() method.
    }
}
