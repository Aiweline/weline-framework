<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\System\File\App;

use Weline\Framework\App\Env;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Module\Dependency\Sort;
use Weline\Framework\Register\Register;
use Weline\Framework\System\File\Scan;
use Weline\Framework\Register\RegisterInterface;

class Scanner extends Scan
{
    /**
     * @DESC          # 扫描模块
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/6/6 23:13
     * 参数区：
     * @return array
     */
    function scanModules(): array
    {
        # 扫描app模块
        $app_modules = glob(APP_CODE_PATH . '*' . DS . '*' . DS . RegisterInterface::register_file, GLOB_NOSORT);
        # 扫描vendor模块
        $vendor_modules = glob(VENDOR_PATH . '*' . DS . '*' . DS . RegisterInterface::register_file, GLOB_NOSORT);
        # 合并
        return array_merge($vendor_modules, $app_modules);
    }

    /**
     * @DESC         |扫描vendor供应商目录 模块
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     * @return array|false
     * @throws \ReflectionException
     * @throws \Weline\Framework\App\Exception
     */
    public function scanAppModules(): array
    {
        # 合并
        $modules = $this->scanModules();
        # 解析模组
        $modules = $this->parseModules($modules);
        # 依赖排序
        $module_dependencies = [];
        foreach ($modules as $vendor => $modules_list) {
            foreach ($modules_list as $module) {
                $module_dependencies[] = $module;
            }
        }
        /**@var Sort $sort */
        $sort                = ObjectManager::getInstance(Sort::class);
        $module_dependencies = $sort->dependenciesSort($module_dependencies);
        Env::write(Env::path_MODULE_DEPENDENCIES_FILE, '<?php return ' . var_export($module_dependencies, true) . ';?>');
        return [$modules, $module_dependencies];
    }

    /**
     * @DESC         |扫描所有注册文件
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @return array|false
     */
    public function scanRegisters()
    {
        $registers = $this->scanAllAppVendors();
        foreach ($registers as $key => $vendor) {
            unset($registers[$key]);
            if ($vendor_files = $this->scanVendorModules($vendor)) {
                $registers[$vendor] = $vendor_files;
            }
        }

        return $registers;
    }

    /**
     * @DESC         |扫描应用供应商
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     * @return array|false
     */
    public function scanAllAppVendors()
    {
        $registers = [];
        foreach (Env::register_FILE_PATHS as $register_type => $register_FILE_PATH) {
            $registers = array_merge($registers, $this->scanDir($register_FILE_PATH));
        }

        return $registers;
    }

    /**
     * @DESC         |扫描供应商模块
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param $vendor
     */
    public function scanVendorModules($vendor)
    {
//        $app_modules = $this->scanDir(APP_CODE_PATH . $vendor);
//        $core_modules = $this->scanDir(Env::path_VENDOR_CODE . $vendor);
//        $theme_modules = $this->scanDir(Env::path_CODE_DESIGN. $vendor);
//        $modules = array_merge($core_modules, $app_modules);
//        $modules = array_merge($modules, $theme_modules);
        $modules = [];
        foreach (Env::register_FILE_PATHS as $register_FILE_PATH) {
            if (is_dir($register_FILE_PATH . $vendor)) {
                $modules = array_merge($modules, $this->scanDir($register_FILE_PATH . $vendor));
            }
        }
        foreach ($modules as $key => $module) {
            unset($modules[$key]);
//            // app下的代码
//            if (file_exists(APP_CODE_PATH . $vendor . DS . $module . DS . RegisterInterface::register_file)) {
//                $modules[$module] = $vendor . DS . $module . DS . RegisterInterface::register_file;
//            }
//            // app下的代码优先度更高 这里
//            if (!isset($modules[$module])) {
//                if (file_exists(Env::vendor_path . $vendor . DS . $module . DS . RegisterInterface::register_file)) {
//                    $modules[$module] = $vendor . DS . $module . DS . RegisterInterface::register_file;
//                }
//            }
//            // 扫描 主题
//            if (!isset($modules[$module])) {
//                if (file_exists(BP . 'vendor' . DS . $vendor . DS . $module . DS . RegisterInterface::register_file)) {
//                    $modules[$module] = $vendor . DS . $module . DS . RegisterInterface::register_file;
//                }
//            }
            foreach (Env::register_FILE_PATHS as $type => $register_FILE_PATH) {
                $app_module_path = $register_FILE_PATH . Register::parserModuleVendor($vendor) . DS . Register::parserModuleName($module);
                if (is_dir($app_module_path)) {
                    $register = $app_module_path . DS . RegisterInterface::register_file;
                    if (is_file($register)) {
                        $env_file                                     = $app_module_path . DS . 'etc' . DS . 'env.php';
                        $env_data                                     = is_file($env_file) ? require $env_file : [];
                        $modules[Register::parserModuleName($module)] = ['register' => $register, 'env' => $env_data, 'env_file' => $env_file, 'base_path' => $app_module_path];
                    }
                }
            }
        }
        return $modules;
    }

    protected function parseModules(array $modules): array
    {
        # 解析模块数据
        foreach ($modules as $key => $app_module_register) {
            $register      = str_replace(APP_CODE_PATH, '', $app_module_register);
            $register      = str_replace(VENDOR_PATH, '', $register);
            $register_dirs = explode(DS, $register);
            $origin_vendor = array_shift($register_dirs);
            $vendor        = Register::parserModuleVendor($origin_vendor);
            $origin_module = array_shift($register_dirs);
            $module        = Register::parserModuleName($origin_module);
            $base_path     = str_replace(Register::register_file, '', $app_module_register);
            $env_file      = $base_path . 'etc' . DS . 'env.php';
            $env           = [];
            if (file_exists($env_file)) {
                $env = (array)include $env_file;
            }
            $modules[$vendor][] = [
                'vendor'    => $vendor,
                'name'      => $module,
                'path'      => $origin_vendor . DS . $origin_module,
                'register'  => $app_module_register,
                'id'        => $vendor . '_' . $module,
                'parent'    => $env['dependencies'] ?? [],
                'env_file'  => $env_file,
                'base_path' => $base_path,
                'env'       => $env
            ];
            unset($modules[$key]);
        }
        return $modules;
    }
}
