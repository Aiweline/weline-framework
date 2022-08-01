<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Module\Helper;

use Weline\Framework\App\Env;
use Weline\Framework\Http\Request;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Register\RegisterDataInterface;
use Weline\Framework\System\File\App\Scanner;
use Weline\Framework\System\File\Io\File;
use Weline\Framework\Helper\AbstractHelper;
use Weline\Framework\Http\RequestInterface;
use Weline\Framework\Module\Handle;
use Weline\Framework\Register\Register;
use Weline\Framework\Register\Router\Data\DataInterface;

class Data extends AbstractHelper
{
    private array $parent_class_arr = [];
    private File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function getClassNamespace(\Weline\Framework\System\File\Data\File $controllerFile)
    {
        $namespace_arr = explode('\\', $controllerFile->getNamespace());
        $namespace_arr = array_slice($namespace_arr, 2);
//        foreach ($namespace_arr as &$item) {
//            if (is_int(strpos($item, '-'))) {
//                $item = explode('-', $item);
//                foreach ($item as &$i) {
//                    $i = ucfirst($i);
//                }
//                $item = implode('', $item);
//            }
//            $item = ucfirst($item);
//        }
        return implode('\\', $namespace_arr);
    }

    /**
     * @DESC         |注册模块路由
     *
     * 参数区：
     *
     * @param array  $modules
     * @param string $name
     * @param string $router
     *
     * @throws \Weline\Framework\App\Exception
     * @throws \Weline\Framework\Console\ConsoleException
     * @throws \ReflectionException
     */
    public function registerModuleRouter(array &$modules, string $path, string $name, string $router)
    {
        if (!$this->isDisabled($modules, $name)) {
            // 禁用则不进行注册
            /**@var Scanner $appScanner */
            $appScanner = ObjectManager::getInstance(Scanner::class);
            // 扫描模块
            $appScanner->__init();

            $moduleDir = $appScanner->scanDirTree($path);
            /**@var Register $routerRegister */
            $routerRegister = ObjectManager::getInstance(Register::class);
            /** @var $Files \Weline\Framework\System\File\Data\File[] */

            foreach ($moduleDir as $dir => $Files) {
                // Api路由
                if (!is_bool(strpos($dir, Handle::api_DIR))) {
                    foreach ($Files as $apiFile) {
                        $apiDirArray = explode(Handle::api_DIR, $dir . DS . $apiFile->getFilename());

                        $baseRouter = str_replace('\\', '/', strtolower(array_pop($apiDirArray)));
                        $baseRouter = trim($router . $baseRouter, '/');

                        $apiClassName = Register::moduleNameToNamespacePath($name).'\\'.$this->getClassNamespace($apiFile) . '\\' . $apiFile->getFilename();
                        $apiClassName = str_replace("\\\\", "\\", $apiClassName);
                        // 删除父类方法：注册控制器方法
                        $this->parent_class_arr = [];// 清空父类信息
                        $ctl_data               = $this->parserController($apiClassName);
                        if (empty($ctl_data)) {
                            continue;
                        }
                        $ctl_methods = $ctl_data['methods'];
                        $ctl_area    = $ctl_data['area'];
                        foreach ($ctl_methods as $method) {
                            // 分析请求方法

                            // 分析请求方法
                            $request_method             = null;
                            $rule_method                = $method;
                            $request_method_split_array = preg_split('/(?=[A-Z])/', $method);
                            if (1 === count($request_method_split_array)) {
                                $request_method_split_array[1] = $request_method_split_array[0];
                                $request_method_split_array[0] = 'get';
                            }
                            $first_value = $request_method_split_array[array_key_first($request_method_split_array)];
                            if (in_array(strtoupper($first_value), RequestInterface::METHODS)) {
                                $request_method = strtoupper($first_value);
                                array_shift($request_method_split_array);
                                $rule_method = implode('', $request_method_split_array);
                            }
                            # 如果没有解析到请求方法就使用方法名
                            if(!$request_method&&in_array(strtoupper($rule_method), Request::METHODS)){
                                $request_method = strtoupper($rule_method);
                                $rule_method = '';
                            }
                            # 删除index后缀
                            $rule_router     = strtolower($baseRouter . '/' . $rule_method);
                            $rule_rule_arr   = explode('/', $rule_router);
                            $last_rule_value = $rule_rule_arr[array_key_last($rule_rule_arr)] ?? '';
                            while ('index' === array_pop($rule_rule_arr)) {
                                $last_rule_value = $rule_rule_arr[array_key_last($rule_rule_arr)] ?? '';
                                continue;
                            }
                            $rule_router    = implode('/', $rule_rule_arr) . (('index' !== $last_rule_value) ? '/' . $last_rule_value : '');
                            $rule_router    = trim($rule_router, '/');
                            $request_method = $request_method ?? RequestInterface::GET;
                            # 模块路由解析
                            $routers = is_string($router) ? [$router] : $router;
                            foreach ($routers as $router_) {
                                // 路由注册+
                                Register::register(RegisterDataInterface::ROUTER, $name, [
                                    'type'           => DataInterface::type_API,
                                    'area'           => $ctl_area,
                                    'module'         => $name,
                                    'base_router'    => $router_,
                                    'router'         => $rule_router . '::' . $request_method,
                                    'class'          => $apiClassName,
                                    'module_path'    => $path,
                                    'method'         => $method,
                                    'request_method' => $request_method,
                                ]);
                            }
                        }
                    }
                } // PC路由
                elseif (!is_bool(strpos($dir, Handle::pc_DIR))) {
                    foreach ($Files as $controllerFile) {
                        $controllerDirArray = explode($modules[$name]['path'] . Handle::pc_DIR, $dir . DS . $controllerFile->getFilename());

                        $baseRouter = str_replace('\\', '/', strtolower(array_pop($controllerDirArray)));

                        $baseRouter = trim($router . $baseRouter, '/');

                        $controllerClassName = Register::moduleNameToNamespacePath($name) . '\\' . $this->getClassNamespace($controllerFile) . '\\' . $controllerFile->getFilename();
                        $controllerClassName = str_replace("\\\\", "\\", $controllerClassName);
                        // 删除父类方法：注册控制器方法
                        $this->parent_class_arr = [];// 清空父类信息
                        $ctl_data               = $this->parserController($controllerClassName);
                        if (empty($ctl_data)) {
                            continue;
                        }

                        $ctl_methods = $ctl_data['methods'];
                        $ctl_area    = $ctl_data['area'];
                        foreach ($ctl_methods as $method) {
                            // 分析请求方法
                            $request_method             = '';
                            $rule_method                = $method;
                            $request_method_split_array = preg_split('/(?=[A-Z])/', $method);
                            if (1 === count($request_method_split_array)) {
                                $request_method_split_array[1] = $request_method_split_array[0];
                                $request_method_split_array[0] = '';
                            }
                            $first_value = $request_method_split_array[array_key_first($request_method_split_array)];
                            if (in_array(strtoupper($first_value), RequestInterface::METHODS)) {
                                $request_method = strtoupper($first_value);
                                array_shift($request_method_split_array);
                                $rule_method = implode('', $request_method_split_array);
                            }
                            # 如果没有解析到请求方法就使用方法名
                            if(!$request_method&&in_array(strtoupper($rule_method), Request::METHODS)){
                                $request_method = strtoupper($rule_method);
                                $rule_method = '';
                            }
                            # 删除index后缀
                            $rule_router     = strtolower($baseRouter . '/' . $rule_method);
                            $rule_rule_arr   = explode('/', $rule_router);
                            $last_rule_value = $rule_rule_arr[array_key_last($rule_rule_arr)] ?? '';
                            while ('index' === array_pop($rule_rule_arr)) {
                                $last_rule_value = $rule_rule_arr[array_key_last($rule_rule_arr)] ?? '';
                                continue;
                            }

                            $rule_router = implode('/', $rule_rule_arr) . (('index' !== $last_rule_value) ? '/' . $last_rule_value : '');
                            $rule_router = trim($rule_router, '/');
                            # 模块路由解析
                            $routers = is_string($router) ? [$router] : $router;
                            foreach ($routers as $router_) {
                                // 路由注册+
                                Register::register(RegisterDataInterface::ROUTER, $name, [
                                    'type'           => DataInterface::type_PC,
                                    'area'           => $ctl_area,
                                    'module'         => $name,
                                    'base_router'    => $router_,
                                    'router'         => $rule_router . ($request_method ? '::' . $request_method : ''),
                                    'class'          => $controllerClassName,
                                    'method'         => $method,
                                    'module_path'    => $path,
                                    'request_method' => $request_method,
                                ]);
                            }

                        }
                    }
                }
            }
        }
    }

    /**
     * @DESC         |模块名到路径转化
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param array  $modules
     * @param string $name
     *
     * @return string
     */
    public function moduleNameToPath(array &$modules, string $name): string
    {
        if ($this->isInstalled($modules, $name)) {
            return trim(str_replace('\\', DS, $modules[$name]['path']), DS);
        }

        return str_replace('_', DS, $name);
    }

    /**
     * @DESC         |模块名到路径转化
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param string $name
     *
     * @return string
     */
    public function getModulePath(string $name): string
    {
        return APP_CODE_PATH . str_replace('_', DS, $name);
    }

    /**
     * @DESC         |利用反射去除父类方法
     *
     * 参数区：
     *
     * @param string $class
     *
     * @return array
     * @throws \ReflectionException
     */
    private function parserController(string $class)
    {
        // 默认前端控制器
//        $ctl_area = \Weline\Framework\Controller\Data\DataInterface::type_pc_FRONTEND;
        if (class_exists($class)) {
            $reflect            = new \ReflectionClass($class);
            $controller_methods = [];
            foreach ($reflect->getMethods() as $method) {
                if (is_int(strpos($method->getName(), '__'))) {
                    continue;
                }
                if ($method->isPublic()) $controller_methods[] = $method->getName();
            }
            // 存在父类则过滤父类方法
            if ($parent_class = $reflect->getParentClass()) {
                $controller_class = [];
                foreach (explode('\\', $parent_class->getName()) as $item) {
                    if (is_int(strpos($item, 'Controller'))) {
                        $controller_class[] = $item;
                    }
                }
                $this->parent_class_arr = array_merge($this->parent_class_arr, $controller_class);
                $parent_methods         = [];
                foreach ($parent_class->getMethods() as $method) {
                    if (is_int(strpos($method->getName(), '__'))) {
                        continue;
                    }
                    if ($method->isPublic()) $parent_methods[] = $method->getName();
                }
                $controller_methods = array_diff($controller_methods, $parent_methods);
                // 实例化类
                if (!$parent_class->isAbstract()) {
                    $this->parent_class_arr = array_merge($this->parent_class_arr, $this->parserController($parent_class->getName())['area']);
                }
            }

            return ['area' => array_unique($this->parent_class_arr), 'methods' => $controller_methods];
        } else {
            return [];
        }
    }

    /**
     * @DESC         |模块是否已经安装
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param array  $modules
     * @param string $name
     *
     * @return bool
     */
    public function isInstalled(array &$modules, string $name): bool
    {
        return array_key_exists($name, $modules);
    }

    /**
     * @DESC         |模块是否已经安装
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param array  $modules
     * @param string $name
     *
     * @return bool
     */
    public function isDisabled(array &$modules, string $name): bool
    {
        if ($this->isInstalled($modules, $name) && isset($modules[$name]['status']) && $modules[$name]['status']) {
            return false;
        }

        return true;
    }

    /**
     * @DESC         |是否模块更新
     *
     * 参数区：
     *
     * @param array  $modules
     * @param string $name
     * @param string $version
     *
     * @return bool
     */
    public function isUpgrade(array &$modules, string $name, string $version): bool
    {
        if (version_compare($version, $modules[$name]['version'], '>')) {
            $modules[$name]['version'] = $version;

            return true;
        }

        return false;
    }

    /**
     * @DESC         |更新模块数据
     *
     * 参数区：
     *
     * @param array $modules
     */
    public function updateModules(array &$modules)
    {
        $this->file->open(Env::path_MODULES_FILE, $this->file::mode_w_add);
        $text = '<?php return ' . w_var_export($modules, true) . ';';
        $this->file->write($text);
        $this->file->close();
        Env::getInstance()->reload();
    }

    /**
     * @DESC         |更新路由
     *
     * 参数区：
     *
     * @param array $routers
     *
     * @throws \Weline\Framework\App\Exception
     */
    public function updateRouters(array &$routers)
    {
        $this->file->open(Env::path_MODULES_FILE, $this->file::mode_w_add);
        $text = '<?php return ' . var_export($routers, true) . ';';
        $this->file->write($text);
        $this->file->close();
    }
}
