<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Plugin\Console\Plugin\Di;

use Weline\Framework\App\Env;
use Weline\Framework\App\Exception;
use Weline\Framework\App\System;
use Weline\Framework\Exception\Core;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Output\Cli\Printing;
use Weline\Framework\Plugin\Console\Plugin\Cache\Clear;
use Weline\Framework\Plugin\PluginsManager;

class Compile implements \Weline\Framework\Console\CommandInterface
{
    /**
     * @var PluginsManager
     */
    private PluginsManager $pluginsManager;

    /**
     * @var Printing
     */
    private Printing $printing;

    /**
     * @var System
     */
    private System $system;

    public function __construct(
        PluginsManager $pluginsManager,
        System $system,
        Printing $printing
    ) {
        $this->pluginsManager = $pluginsManager;
        $this->printing       = $printing;
        $this->system         = $system;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $args = [])
    {
        $this->printing->printing(__('编译开始...'));
        $this->printing->printing(__('清除旧编译内容...'));
        $this->system->exec('rm ' . Env::path_framework_generated_code . ' -rf');
        $this->printing->printing(__('清除编译缓存...'));
        /**@var Clear $clear*/
        $clear = ObjectManager::getInstance(Clear::class);
        $clear->execute();
        $generator = $this->pluginsManager->generatorInterceptor('', false);
        $printer_list = [];
        foreach ($generator::getClassProxyMap() as $key=>$item) {
            unset($item['body']);
            $printer_list[$key]=$item;
        }
        $this->printing->printList($printer_list);
        $this->printing->printing(__('编译结束...'));
    }

    /**
     * @inheritDoc
     */
    public function getTip(): string
    {
        return '【插件】系统依赖编译';
    }
}
