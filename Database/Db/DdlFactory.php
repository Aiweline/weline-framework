<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Database\Db;

use Weline\Framework\Database\ConnectionFactory;
use Weline\Framework\Database\Db\Ddl\Table;
use Weline\Framework\Database\Db\Ddl\Table\Alter;
use Weline\Framework\Manager\Api\FactoryInterface;
use Weline\Framework\Manager\ObjectManager;

class DdlFactory implements FactoryInterface
{
    /**
     * @DESC          # 创建Table动作
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/5 21:07
     * 参数区：
     *
     * @param ConnectionFactory $connection
     *
     * @return Table
     * @throws \ReflectionException
     * @throws \Weline\Framework\App\Exception
     */
    public function create(ConnectionFactory $connection = null): Table
    {
        if (!$connection) {
            return ObjectManager::getInstance(Table::class);
        }
        return ObjectManager::getInstance(Table::class)->setConnection($connection);
    }

    /**
     * @DESC          # 修改表对象
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/5/29 21:45
     * 参数区：
     *
     * @param ConnectionFactory $connection
     *
     * @return Alter
     * @throws \ReflectionException
     * @throws \Weline\Framework\App\Exception
     */
    public function alter(ConnectionFactory $connection = null): Alter
    {
        if (!$connection) {
            return ObjectManager::getInstance(Alter::class);
        }
        return ObjectManager::getInstance(Alter::class)->setConnection($connection);
    }
}
