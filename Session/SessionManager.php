<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Session;

use Weline\Framework\App\Env;
use Weline\Framework\Session\Driver\SessionDriverHandlerInterface;

class SessionManager
{
    public const driver_NAMESPACE = Env::framework_name . '\\Framework\\Session\\Driver\\';

    private static SessionManager $instance;

    private array $config;
    private ?SessionDriverHandlerInterface $_session = null;

    private function __clone()
    {
    }

    private function __construct()
    {
        $this->config = (array)Env::getInstance()->getConfig('session');
    }

    /**
     * @DESC         |获取实例
     *
     * 参数区：
     *
     * @return SessionManager
     */
    public static function getInstance(): SessionManager
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @DESC         |创建session
     *
     * 参数区：
     * @param string $driver
     * @param string $area
     * @return SessionDriverHandlerInterface
     */
    public function create(string $driver = ''): SessionDriverHandlerInterface
    {
        if (empty($this->_session)) {
            if (empty($driver) && isset($this->config['default'])) {
                $driver = $this->config['default'];
            }
            $driver_class = self::driver_NAMESPACE . ucfirst($driver);
            $driver_config = $this->config['drivers'][$driver];
            $this->_session = new $driver_class($driver_config);
        }
        return $this->_session;
    }
}
