<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Register;

use Weline\Framework\App;
use Weline\Framework\Console\ConsoleException;
use Weline\Framework\DataObject\DataObject;
use Weline\Framework\Event\EventsManager;
use Weline\Framework\Manager\ObjectManager;

class Register implements RegisterDataInterface
{
    /**
     * @DESC         |注册
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param string       $type         注册类型
     * @param string       $module_name  模组名
     * @param array|string $param        参数[模组类型:此处传输目录__DIR__,主题类型：['name' => 'demo','path' => __DIR__,]]
     * @param array        $dependencies 依赖定义【例如:['Weline_Theme','Weline_Backend']】
     * @param string       $version      版本
     * @param string       $description  描述
     *
     * @return mixed
     * @throws App\Exception
     * @throws \ReflectionException
     */
    public static function register(string $type, string $module_name, array|string $param, string $version = '', string $description = '', array $dependencies = []): mixed
    {
        $install_params = func_get_args();
        switch ($type) {
            // 模块安装
            case self::MODULE:
                $appPathArray    = explode(DS, $param);
                $module_name_dir = array_pop($appPathArray);
                $vendor_dir      = array_pop($appPathArray);
                // 安装数据
                $install_params = [$type, $module_name, ['dir_path' => $vendor_dir . DS . $module_name_dir . DS, 'base_path' => $param . DS, 'module_name' => $module_name], $version, $description];
                break;
            // 路由注册
            case self::ROUTER:
            default:
        }
        /*
         * 采用观察者模式 是的其余类型的安装可自定义注册
         */
        /**@var DataObject $installerPathData */
        $installerPathData = ObjectManager::getInstance(DataObject::class);
        $installerPathData
            ->setData('installer', self::NAMESPACE . ucfirst($type) . '\Handle')
            ->setData('register_arguments', $install_params);
        /**@var EventsManager $eventsManager */
        $eventsManager = ObjectManager::getInstance(EventsManager::class);
        $eventsManager->dispatch('Framework_Register::register_installer', ['data' => $installerPathData]);
        $installer_class = $installerPathData->getData('installer');
        /**@var RegisterInterface $installer */
        $installer = ObjectManager::getInstance($installer_class);
        if ($installer instanceof RegisterInterface) {
            $register_arguments = $installerPathData->getData('register_arguments');
            return $installer->register(...$register_arguments);
        } else {
            throw new ConsoleException($installer_class . __('安装器必须继承：') . RegisterInterface::class);
        }
    }

    public static function moduleName(string $vendor, string $module_name): string
    {
        return self::parserModuleVendor($vendor) . '_' . self::parserModuleName($module_name);
    }

    public static function parserModuleVendor(string $vendor): string
    {
        return ucfirst($vendor);
    }

    public static function moduleNameToNamespacePath(string $module_name): string
    {
        return str_replace('_', '\\', $module_name) . '\\';
    }

    public static function parserModuleName(string $module_name): string
    {
        $module_rename = '';
        if (is_int(strpos($module_name, '-'))) {
            $module_arr = explode('-', $module_name);
            foreach ($module_arr as $item) {
                $module_rename .= ucfirst($item);
            }
        } else {
            $module_rename = $module_name;
        }
        return ucfirst($module_rename);
    }

    public static function convertToComposerName(string $name): string
    {
        # 转化为composer名称
        $name            = lcfirst($name);
        $vendor_name_arr = w_split_by_capital($name);
        return strtolower(implode('-', $vendor_name_arr));
    }

    public static function composerNameConvertToNamespace(string $name): string
    {
        # composer名称转化为命名空间
        $name = explode('-', $name);
        foreach ($name as &$item) {
            $item = ucfirst($item);
        }
        $name     = implode('\\', $name);
        $name_arr = explode(DS, $name);
        foreach ($name_arr as &$item_name) {
            $item_name = ucfirst($item_name);
        }
        $name = implode('\\', $name_arr);
        return str_replace(DS, '\\', $name);
    }
}
