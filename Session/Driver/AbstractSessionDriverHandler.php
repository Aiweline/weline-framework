<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Session\Driver;

use Weline\Framework\DataObject\DataObject;

abstract class AbstractSessionDriverHandler extends DataObject implements SessionDriverHandlerInterface
{
    private function __clone()
    {
    }

    public function __construct(array $config)
    {
        parent::__construct($config);
        session_set_save_handler($this, true);
    }

    public function set($name, $value): bool
    {
        $_SESSION[$name] = $value;
        if ($_SESSION[$name]) {
            return true;
        }
        $this->setData($_SESSION);
        return false;
    }

    public function get($name = null): mixed
    {
        if ($name) {
            return $_SESSION[$name] ?? $this->getData($name);
        }
        return $_SESSION;
    }

    public function delete($name): bool
    {
        unset($_SESSION[$name]);
        $this->unsetData($name);
        return true;
    }


    public function getSessionId(): string
    {
        $this->setData('session_id', session_id());
        return $this->getData('session_id');
    }
}
