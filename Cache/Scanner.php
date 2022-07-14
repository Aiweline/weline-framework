<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Cache;

use Weline\Framework\App\Env;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Module\Must\DataInterface;
use Weline\Framework\Register\Register;
use Weline\Framework\Register\RegisterInterface;
use Weline\Framework\System\File\Data\File;

class Scanner extends \Weline\Framework\System\File\App\Scanner
{
    public const dir = 'Cache';

    #[\JetBrains\PhpStorm\ArrayShape(['app_caches' => "array", 'framework_caches' => "array"])] public function getCaches(): array
    {
        $app_caches = $this->scanAppCaches();

        $framework_caches = $this->scanFrameworkCaches();
        return [
            'app_caches'       => $app_caches,
            'framework_caches' => $framework_caches,
        ];
    }

    /**
     * @DESC         |搜索App所有模块的缓存管理器
     *
     * 参数区：
     */
    public function scanAppCaches(): array
    {
        # 扫描app模块
        $apps       = glob(APP_CODE_PATH . '*' . DS . '*' . DS . Register::register_file, GLOB_NOSORT);
        $app_caches = [];
        foreach ($apps as $app_register) {
            # 查找缓存管理器
            $cache_files = glob(str_replace(Register::register_file, '', $app_register) . 'Cache' . DS . '*.php', GLOB_NOSORT);
            $app_caches  = array_merge($app_caches, $this->convertParser($cache_files));
        }
        return $app_caches;
    }

    /**
     * @DESC          # 缓存文件转为缓存管理器
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/6/6 21:35
     * 参数区：
     *
     * @param $cache_files
     * @param string $dir
     *
     * @return array
     */
    protected function convertParser($cache_files): array
    {
        $app_caches = [];
        foreach ($cache_files as $cache_file) {
            # 如果有Interface名，则跳过
            if (strpos($cache_file, 'Interface')) {
                continue;
            }
            $cache_class = str_replace('.php', '', $cache_file);
            $cache_class = str_replace(APP_CODE_PATH, '', $cache_class);
            $cache_class = str_replace(VENDOR_PATH, '', $cache_class);
            $cache_class = str_replace(DS, '\\', $cache_class);
            # 处理模块目录
            $cache_class_dirs = explode('\\', $cache_class);
            $vendor           = array_shift($cache_class_dirs);
            $module           = array_shift($cache_class_dirs);
            $class            = Register::parserModuleVendor($vendor) . '\\' . Register::parserModuleName($module) . '\\' . implode('\\', $cache_class_dirs);
            if (class_exists($class)) {
                try {
                    $obj = ObjectManager::getInstance(rtrim($class, 'Factory') . 'Factory');
                } catch (\Exception $e) {
                    $obj = null;
                }
                if ($obj instanceof CacheInterface) {
                    $app_caches[] = [
                        'class' => $class,
                        'file'  => $cache_file,
                    ];
                }
            }
        }
        return $app_caches;
    }

    /**
     * @DESC         |框架以及第三方Cache
     *
     * 参数区：
     *
     * @return array
     */
    public function scanFrameworkCaches(): array
    {
        // 扫描核心命令 兼容AppCode和composer
        $app_framework      = glob(APP_CODE_PATH . 'Weline' . DS . 'Framework' . DS.'*'.DS . 'Cache' . DS . '*.php', GLOB_NOSORT);
        $composer_framework = glob(VENDOR_PATH . 'Weline' . DS . 'Framework' . DS.'*'.DS . 'Cache' . DS . '*.php', GLOB_NOSORT);
        // 合并
        $cache_files = array_merge($composer_framework, $app_framework);
        # 查找缓存管理器
        return $this->convertParser($cache_files);
    }
}
