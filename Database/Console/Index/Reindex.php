<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Database\Console\Index;

use Weline\Framework\Event\EventsManager;
use Weline\Framework\Manager\ObjectManager;

class Reindex implements \Weline\Framework\Console\CommandInterface
{
    /**
     * @inheritDoc
     */
    public function execute(array $args = [])
    {
        /**@var EventsManager $eventManager*/
        $eventManager = ObjectManager::getInstance(EventsManager::class);
        $eventManager->dispatch('Framework_Database::indexer', ['args'=>$args]);
    }

    /**
     * @inheritDoc
     */
    public function getTip(): string
    {
        return '重建数据库表索引。示例：index:reindex weline_indexer （其中weline_indexer是模型索引器名，可以多个Model使用同一个索引器）';
    }
}
