<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Manager;

class PluginManager implements ManagerInterface
{
    private static PluginManager $instance;

    private function __clone()
    {
    }

    private function __construct()
    {
    }

    public static function create(string $class, string $method, array $params)
    {
        // TODO: Implement create() method.
    }

    public static function getInstance($class = '')
    {
        if (empty($class)) {
            return isset(self::$instance) ? self::$instance : new self();
        }
    }

    public function register()
    {
    }
}
