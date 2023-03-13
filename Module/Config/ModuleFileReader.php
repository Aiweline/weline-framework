<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Module\Config;

use Weline\Framework\App\Env;
use Weline\Framework\Register\Register;
use Weline\Framework\System\File\Scan;

class ModuleFileReader extends Scan
{
    public function read(string $module_name, string $dir = ''): array
    {
        $base_path = Env::getInstance()->getModuleInfo($module_name)['base_path'] ?? '';
        if ($base_path) {
            return $this->scanDirTree($base_path . $dir);
        } else {
            # 如果没有模块可能是第一次安装模块
            # app 内部的模块
            $base_path = APP_PATH . str_replace('_', DS, $module_name) . DS;
            $app_data  = $this->scanDirTree($base_path . $dir);
            # vendor 内部的模块
            $vendor_data = $this->scanDirTree(VENDOR_PATH . Register::convertToComposerName($module_name) . DS . $dir);
            return array_merge($app_data, $vendor_data);
        }
    }

    public function readClass(string $base_path, string $dir = ''): array
    {
        $files   = [];
        $explode = explode(DS, $base_path);
        array_pop($explode);
        array_pop($explode);
        array_pop($explode);
        return $this->globFile($base_path . $dir, $files, '.php', implode(DS, $explode) . DS, '', true, true);
    }
}
