<?php

declare(strict_types=1);
/**
 * 文件信息
 * 作者：邹万才
 * 网名：秋风雁飞(Aiweline)
 * 网站：www.aiweline.com/bbs.aiweline.com
 * 工具：PhpStorm
 * 日期：2021/6/15
 * 时间：16:43
 * 描述：此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
 */

namespace Weline\Framework\Database;

use PDO;
use PDOException;
use PDOStatement;
use Weline\Framework\Database\Api\Connection\AlterInterface;
use Weline\Framework\Database\Api\Connection\QueryInterface;

use Weline\Framework\Database\DbManager\ConfigProvider;
use Weline\Framework\Database\Exception\LinkException;
use Weline\Framework\Manager\ObjectManager;

class ConnectionFactory
{
    protected ?PDO $connection = null;
    protected ConfigProvider $configProvider;
    protected ?QueryInterface $query = null;
    protected ?AlterInterface $alter = null;
    protected array $queries = [];

    /**
     * Connection 初始函数...
     *
     * @param ConfigProvider $configProvider
     *
     * @throws LinkException
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
        $this->create();
    }

    /**
     * @DESC         |连接配置
     *
     * 参数区：
     *
     * @return ConfigProvider
     */
    public function getConfigProvider(): ConfigProvider
    {
        return $this->configProvider;
    }

    /**
     * @DESC         |休眠时执行函数： 保存配置信息，以及模型数据
     *
     * 参数区：
     *
     * @return string[]
     */
    public function __sleep()
    {
        return ['configProvider', 'query'];
    }

    /**
     * @DESC         |唤醒时执行函数
     *
     * 参数区：
     *
     * @throws LinkException
     * @throws \Weline\Framework\App\Exception
     */
    public function __wakeup()
    {
        $this->create();
    }

    /**
     * @DESC          # 获得数据库PDO链接
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/18 21:10
     * 参数区：
     * @throws LinkException
     */
    public function create(): static
    {
        $db_type = $this->configProvider->getDbType();
        $dsn     = "{$db_type}:host={$this->configProvider->getHostName()}:{$this->configProvider->getHostPort()};dbname={$this->configProvider->getDatabase()};charset={$this->configProvider->getCharset()};collate={$this->configProvider->getCollate()}";
        if (!in_array($db_type, PDO::getAvailableDrivers())) {
            throw new LinkException(__('驱动不存在：%1,可用驱动列表：%2，更多驱动配置请转到php.ini中开启。', [$db_type, implode(',', PDO::getAvailableDrivers())]));
        }
        try {
            //初始化一个Connection对象
            $this->connection = new PDO($dsn, $this->configProvider->getUsername(), $this->configProvider->getPassword(), $this->configProvider->getOptions());
//            $this->connection->exec("set names {$this->configProvider->getCharset()} COLLATE {$this->configProvider->getCollate()}");
        } catch (PDOException $e) {
            throw new LinkException($e->getMessage());
        }
        return $this;
    }

    public function close(): void
    {
        $this->connection = null;
    }

    /**
     * @DESC          # 获取连接
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/18 21:06
     * 参数区：
     * @return PDO
     * @throws LinkException
     */
    public function getLink(): PDO
    {
        return $this->connection;
    }

    /**
     * @DESC          # 获取查询类
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/18 21:07
     * 参数区：
     * @return QueryInterface
     * @throws \ReflectionException
     * @throws \Weline\Framework\App\Exception
     */
    public function getQuery(): QueryInterface
    {
        if (is_null($this->query)) {
            $adapter                 = $this->getAdapter();
            $this->queries['master'] = new $adapter($this);
            $this->query             = $this->queries['master'];
        }
        return $this->query;
    }

    /**
     * @DESC          # 查询
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/5 22:40
     * 参数区：
     *
     * @param string $sql
     *
     * @return QueryInterface
     * @throws \ReflectionException
     * @throws \Weline\Framework\App\Exception
     */
    public function query(string $sql): PDOStatement
    {
        # 非写操作，用均衡算法从从库中选择一个
        $write_flags = [
            'insert',
            'update',
            'delete',
            'replace',
            'alter',
            'create',
            'drop',
            'truncate',
            'desc',
            'describe',
            'explain',
            'grant',
            'revoke',
        ];
        $sql_type    = strtolower(substr(trim($sql), 0, strpos($sql, ' ')));
        if (!in_array($sql_type, $write_flags)) {
            # 检测从库配置，如果有从库，则从库中查询
            if ($slaves_configs = $this->configProvider->getSalvesConfig()) {
                # 如果有从库直接读取从库，一个请求只能读取一个从库
                # TODO 均衡算法（先随机选一个）
                $slave_config = $slaves_configs[array_rand($slaves_configs)];
                $config_key   = md5($slave_config['host'] . $slave_config['port'] . $slave_config['database']);
                if (!isset($this->queries[$config_key])) {
                    $adapter                    = $this->getAdapter($slave_config->getType());
                    $this->queries[$config_key] = new $adapter(new ConnectionFactory($slave_config));
                }
                $this->query = $this->queries[$config_key];
            } else {
                $this->query = $this->getQuery();
            }
        }
        if (is_null($this->query)) {
            $this->getQuery();
        }
        // 如果是drop开头的语句，则不进行缓存
//        if (strpos(strtolower($sql), 'drop') === 0) {
//            p($sql);
//        }
//
//        // 如果是drop开头的语句，则不进行缓存
//        if (strpos(strtolower($sql), 'm_aiweline_hello_world')) {
//            p($sql);
//        }
        return $this->query->getConnection()->getLink()->query($sql);
    }

    /**
     * @DESC          # 获取修改者
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/5 21:11
     * 参数区：
     * @return AlterInterface
     * @throws \ReflectionException
     * @throws \Weline\Framework\App\Exception
     */
    public function getAlter(): AlterInterface
    {
        if (is_null($this->alter)) {
            $this->alter = ObjectManager::getInstance($this->getAdapter('alert'));
        }
        return $this->alter;
    }

    /**
     * 获取适配器
     *
     * @param string $driver_type
     *
     * @return string
     */
    public function getAdapter(string $driver_type = 'mysql'): string
    {
        $driver_type = $this->configProvider->getDbType() ?: $driver_type;
        return "Weline\\Framework\\Database\\Connection\\Query\\Adapter\\" . ucfirst($driver_type);
    }
}
