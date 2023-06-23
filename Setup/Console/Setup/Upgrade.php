<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 作者：Admin
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 * 日期：2022/11/26 15:02:25
 */

namespace Weline\Framework\Setup\Console\Setup;

use \Weline\Framework\App\Env;
use \Weline\Framework\App\System;
use \Weline\Framework\Event\EventsManager;
use \Weline\Framework\Manager\ObjectManager;
use \Weline\Framework\Module\Dependency\Sort;
use \Weline\Framework\Module\Handle;
use \Weline\Framework\Module\Helper\Data;
use \Weline\Framework\Module\Model\Module;
use \Weline\Framework\Output\Cli\Printing;
use \Weline\Framework\System\File\App\Scanner as AppScanner;

class Upgrade implements \Weline\Framework\Console\CommandInterface
{
    /**
     * @inheritDoc
     */
    public function execute(array $args = [], array $data = [])
    {
        /**@var \Weline\Framework\Module\Console\Module\Upgrade $moduleUpdate*/
        $moduleUpdate = ObjectManager::getInstance(\Weline\Framework\Module\Console\Module\Upgrade::class);
        $moduleUpdate->execute($args, $data);
    }

    /**
     * @inheritDoc
     */
    public function tip(): string
    {
        return '框架代码刷新。';
    }
}
