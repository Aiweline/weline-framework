<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Module\Console\Module;

use Weline\Framework\App\Env;
use Weline\Framework\App\System;
use Weline\Framework\Console\CommandAbstract;
use Weline\Framework\Event\EventsManager;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Module\Helper\Data;
use Weline\Framework\Output\Cli\Printing;
use Weline\Framework\System\File\App\Scanner as AppScanner;

class Upgrade extends CommandAbstract
{
    /**
     * @var System
     */
    private System $system;

    /**
     * @var AppScanner
     */
    private AppScanner $scanner;

    /**
     * @var Data
     */
    private Data $data;

    public function __construct(
        Printing   $printer,
        AppScanner $scanner,
        Data       $data,
        System     $system
    ) {
        $this->printer = $printer;
        $this->system = $system;
        $this->scanner = $scanner;
        $this->data = $data;
    }

    /**
     * @DESC         |方法描述
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param array $args
     *
     * @return mixed|void
     */
    public function execute(array $args = [])
    {
        $i = 1;
        // 删除路由文件
        $this->printer->warning($i . '、路由更新...', '系统');
        $this->printer->warning('清除文件：');
        foreach (Env::router_files_PATH as $path) {
            $this->printer->warning($path);
            if (is_file($path)) {
                list($out, $var) = $this->system->exec('rm -f ' . $path);
                if ($out) {
                    $this->printer->printList($out);
                }
            }
        }
        $i += 1;
        $this->printer->warning($i . '、generated生成目录代码code清理...', '系统');
        $this->system->exec('rm -rf ' . Env::path_framework_generated_code);
        $i += 1;
        $this->printer->note($i . '、收集模块信息', '系统');
        # 加载module中的助手函数
        $modules        = Env::getInstance()->getActiveModules();
        $function_files_content = '';
        foreach ($modules as $module) {
            $common_file = $module['base_path'] .'Global'.DS.'functions.php';
            if (file_exists($common_file)) {
                # 读取文件内容 去除注释以及每个文件末尾的 '\?\>'结束符
                $function_files_content .= str_replace('?>', '', file_get_contents($common_file)) . '\n';
            }
        }
        # 写入文件
        $this->printer->warning('写入文件：');
        $this->printer->warning(Env::path_FUNCTIONS_FILE);
        file_put_contents(Env::path_FUNCTIONS_FILE, $function_files_content);

        $i += 1;
        $this->printer->note($i . '、事件清理...');
        /**@var $cacheManagerConsole \Weline\CacheManager\Console\Cache\Clear */
        $cacheManagerConsole = ObjectManager::getInstance(\Weline\Framework\Event\Console\Event\Cache\Clear::class);
        $cacheManagerConsole->execute();
        $i += 1;
        $this->printer->note($i . '、插件编译...');
        /**@var $cacheManagerConsole \Weline\CacheManager\Console\Cache\Clear */
        $cacheManagerConsole = ObjectManager::getInstance(\Weline\Framework\Plugin\Console\Plugin\Di\Compile::class);
        $cacheManagerConsole->execute();
        $i += 1;
        $this->printer->note($i . '、module模块更新...');
        // 注册模块
        $all_modules = [];
        // 扫描代码
        list($vendor, $dependencies) = $this->scanner->scanAppModules();
        foreach ($dependencies as $module_name => $module) {
            $register = $module['register']??"";
            if (is_file($register)) {
                require $register;
            }
        }
        $this->printer->note('模块更新完毕！');
        $i += 1;

        // 清理其他
        $this->printer->note($i . '、清理缓存...');
        /**@var $cacheManagerConsole \Weline\CacheManager\Console\Cache\Clear */
        $cacheManagerConsole = ObjectManager::getInstance(\Weline\CacheManager\Console\Cache\Clear::class);
        $cacheManagerConsole->execute();

        /**@var EventsManager $eventsManager */
        $eventsManager = ObjectManager::getInstance(EventsManager::class);
        $eventsManager->dispatch('Framework_Module::module_upgrade');
    }

    /**
     * @inheritDoc
     */
    public function getTip(): string
    {
        return '升级模块';
    }

    /**
     * ----------辅助函数--------------
     */
}
