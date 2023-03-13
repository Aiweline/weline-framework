<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Session\Driver;

class File implements DriverInterface, SessionDriverHandlerInterface
{
    private function clone()
    {
    }

    private string $sessionPath;
    private array $config;

    /**
     * File 初始函数...
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config      = $config;
        $this->sessionPath = isset($this->config['path']) ? BP . str_replace('/', DS, $this->config['path']) : BP . 'var' . DS . 'session' . DS;
        if (!is_dir($this->sessionPath)) {
            mkdir($this->sessionPath, 0700);
        }
        # 会话启用 但是不存在时 新建会话
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_save_path($this->sessionPath);
//            session_set_cookie_params(3600, '/', '127.0.0.1', false, TRUE);
            ini_set('session.save_handler', 'files');
            ini_set('session.auto_start', '0');
            session_start();
        }
    }

    public function set($name, $value): bool
    {
        $_SESSION[$name] = $value;
        if ($_SESSION[$name]) {
            return true;
        }
        return false;
    }

    public function get($name = null): mixed
    {
        if ($name) {
            return $_SESSION[$name] ?? null;
        }
        return $_SESSION;
    }

    public function delete($name): bool
    {
        unset($_SESSION[$name]);
        return true;
    }

    public function destroy(): bool
    {
        $_SESSION = [];
        return session_destroy();
    }

    public function getSessionId(): string
    {
        return session_id();
    }
}
