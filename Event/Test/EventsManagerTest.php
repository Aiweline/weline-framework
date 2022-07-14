<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Event\Test;

use Weline\Framework\Event\EventsManager;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\UnitTest\TestCore;

class EventsManagerTest extends TestCore
{
    /**
     * @var mixed|EventsManager|ObjectManager
     */
    private EventsManager $eventsManager;

    public function setUp(): void
    {
        $this->eventsManager =  ObjectManager::getInstance(EventsManager::class);
    }

    public function testScanEvents()
    {
        p($this->eventsManager->scanEvents());
    }
}
