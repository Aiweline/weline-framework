<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\App;

use Weline\Framework\App\Env\Modules;
use Weline\Framework\DataObject\DataObject;
use Weline\Framework\System\File\Io\File;

class Env extends DataObject
{
    public const vendor_path = BP . 'vendor' . DS;

    public const framework_name = 'Weline';

    public const path_framework_generated = BP . 'generated' . DS;

    public const path_framework_generated_code = self::path_framework_generated . 'code' . DS;

    # 框架模板文件位置
    public const path_framework_generated_complicate = self::path_framework_generated . 'complicate' . DS;

    // -----------------路径--------------------
    public const path_ENV_FILE = APP_ETC_PATH . 'env.php';

    public const path_SYSTEM_META_DATA = self::path_framework_generated . 'configs.php'; //FIXME 元数据等待开发

    public const path_MODULES_FILE = APP_ETC_PATH . 'modules.php';

    public const path_MODULE_DEPENDENCIES_FILE = APP_ETC_PATH . 'module_dependencies.php';

    public const path_COMMANDS_FILE = self::path_framework_generated . 'commands.php';

    // 注册register路径

    public const path_VENDOR_CODE = self::vendor_path;

    public const path_CODE_DESIGN = BP . 'app' . DS . 'design' . DS;

    public const path_LANGUAGE_PACK = BP . 'app' . DS . 'i18n' . DS;

    public const register_FILE_PATHS = [
        'app_code'      => APP_CODE_PATH,
        'vendor_code'   => self::path_VENDOR_CODE,
        'theme_design'  => self::path_CODE_DESIGN,
        'language_pack' => self::path_LANGUAGE_PACK,
    ];

    public const default_theme_DATA = [
        'id'          => 0,
        'name'        => 'default',
        'path'        => 'Weline' . DS . 'default',
        'parent_id'   => null,
        'is_active'   => 1,
        'create_time' => '2021-04-05 16:49:58',
    ];

    # 助手函数文件位置
    public const path_FUNCTIONS_FILE = self::path_framework_generated . 'functions.php';
    // 路由
    public const path_ROUTERS_DIR = self::path_framework_generated . 'routers' . DS;

    public const path_BACKEND_REST_API_ROUTER_FILE = self::path_ROUTERS_DIR . 'backend_rest_api.php';

    public const path_BACKEND_PC_ROUTER_FILE = self::path_ROUTERS_DIR . 'backend_pc.php';

    public const path_FRONTEND_REST_API_ROUTER_FILE = self::path_ROUTERS_DIR . 'frontend_rest_api.php';

    public const path_FRONTEND_PC_ROUTER_FILE = self::path_ROUTERS_DIR . 'frontend_pc.php';

    public const router_files_PATH = [
        self::path_BACKEND_REST_API_ROUTER_FILE,
        self::path_FRONTEND_REST_API_ROUTER_FILE,
        self::path_BACKEND_PC_ROUTER_FILE,
        self::path_FRONTEND_PC_ROUTER_FILE,
    ];

    // 生成文件的目录

    public const GENERATED_DIR = BP . 'generated';

    // 编译生成文件目录
    public const path_COMPLICATE_GENERATED_DIR = self::GENERATED_DIR . DS . 'complicate' . DS;

    // 翻译词典 目录
    public const path_TRANSLATE_FILES_PATH = self::GENERATED_DIR . DS . 'language' . DS;

    public const path_TRANSLATE_DEFAULT_FILE = self::GENERATED_DIR . DS . 'language' . DS . 'zh_Hans_CN.php';

    public const path_TRANSLATE_ALL_COLLECTIONS_WORDS_FILE = self::GENERATED_DIR . DS . 'language' . DS . 'words.php';

    // 日志
    public const log_path_ERROR = 'error';

    public const log_path_EXCEPTION = 'exception';

    public const log_path_NOTICE = 'notice';

    public const log_path_WARNING = 'warning';

    public const log_path_DEBUG = 'debug';

    // 拓展目录
    public const extend_dir = BP . 'extend' . DS;

    // 主题设计
    public const path_THEME_DESIGN_DIR = BP . 'app' . DS . 'design' . DS;
    // 主题设计
    public const path_UPLOAD_DIR = PUB . 'upload' . DS;

    // 变量

    /**
     * @var Env
     */
    private static Env $instance;

    public const default_CONFIG = [
        'cache'   => self::default_CACHE,
        'session' => self::default_SESSION,
        'log'     => self::default_LOG,
        'php-cs'  => true,
    ];

    // 日志
    public const default_LOG = [
        'error'     => 'var' . DS . 'log' . DS . 'error.log',
        'exception' => 'var' . DS . 'log' . DS . 'exception.log',
        'notice'    => 'var' . DS . 'log' . DS . 'notice.log',
        'warning'   => 'var' . DS . 'log' . DS . 'warning.log',
        'debug'     => 'var' . DS . 'log' . DS . 'debug.log',
    ];

    // 缓存
    public const default_CACHE = [
        'default' => 'file',
        'drivers' => [
            'file'  => [
                'path' => 'var/cache/',
            ],
            'redis' => [
                'tip'      => '开发中...',
                'server'   => '127.0.0.1',
                'port'     => 6379,
                'database' => 1,
            ],
        ],
        'status'  => [
            'config'               => 1,
            'framework_controller' => 1,
            'database'             => 1,
            'database_model'       => 1,
            'framework_event'      => 1,
            'framework_object'     => 1,
            'framework_phrase'     => 1,
            'framework_plugin'     => 1,
            'router_cache'         => 1,
            'framework_view'       => 1,
            'frontend_cache'       => 1,
        ]
    ];

    // Session
    public const default_SESSION = [
        'default' => 'file',
        'drivers' => [
            'file'  => [
                'path' => 'var/session/',
            ],
            'mysql' => [
                'tip' => '开发中...',
            ],
            'redis' => [
                'tip' => '开发中...',
            ],
        ],
    ];

    private array $config = [];

    private array $module_list = [];
    private array $active_module_list = [];

    private array $hasGetConfig;

    /**
     * @DESC         |私有化克隆函数
     *
     * 参数区：
     */
    private function __clone()
    {
    }

    /**
     * Env 私有化 初始函数...
     */
    private function __construct()
    {
        parent::__construct();
        try {
            $this->reload();
        } catch (Exception $e) {
            throw new Exception(__('系统加载错误：%1', $e->getMessage()));
        }
    }

    public function reload(): static
    {
        if (!is_file(self::path_ENV_FILE)) {
            $file = new File();
            $file->open(self::path_ENV_FILE, $file::mode_w_add);
            $text = '<?php return ' . w_var_export([], true) . ';?>';

            try {
                $file->write($text);
            } catch (Exception $e) {
                throw new Exception(__('错误：' . $e->getMessage()));
            }
            $file->close();
        }
        // 覆盖默认配置
        $this->config = array_merge(self::default_CONFIG, (array)include self::path_ENV_FILE);
        $this->setData($this->config);
        return $this;
    }

    /**
     * @DESC         |获得实例
     *
     * 参数区：
     *
     * @return Env
     */
    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @DESC         |获取环境参数
     *
     * 参数区：
     *
     * @param string $name
     * @param        $default
     *
     * @return mixed
     */
    public function getConfig(string $name = '', $default = null): mixed
    {
        if (isset($this->hasGetConfig[$name])) {
            return $this->hasGetConfig[$name];
        }
        if ('' === $name) {
            return $this->config;
        }

        return isset($this->config[$name]) ? $this->config[$name] : $default;
    }

    /**
     * @DESC         |设置环境参数
     *
     * 参数区：
     *
     * @param string $key
     * @param        $value
     *
     * @return bool
     */
    public function setConfig(string $key, $value = null): bool
    {
        $this->hasGetConfig[$key] = $value;
        $config                   = $this->getConfig();
        $config[$key]             = $value;

        try {
            $file = new File();
            $file->open(self::path_ENV_FILE, $file::mode_w);
            $text = '<?php return ' . w_var_export($config, true) . ';';
            $file->write($text);
            $file->close();
            // 重置环境参数
            $this->reload();
            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @DESC         |读取log路径
     *
     * 参数区：
     *
     * @param string $type
     *
     * @return string
     */
    public function getLogPath(string $type): string
    {
        return BP . $this->config['log'][$type];
    }

    /**
     * @DESC         |获取数据库配置
     *
     * 参数区：
     *
     * @return array
     */
    public function getDbConfig(): array
    {
        return isset($this->config['db']) ? $this->config['db'] : [];
    }

    /**
     * @DESC         |读取模块列表
     *
     * 参数区：
     *
     * @param bool $reget
     *
     * @return array
     */
    public function getModuleList(bool $reget = false): array
    {
        if (!$reget && $this->module_list) {
            return $this->module_list;
        }
        $this->module_list = (new Modules())->getList();

        return $this->module_list;
    }

    function getActiveModules(): array
    {
        if($this->active_module_list){
            return $this->active_module_list;
        }
        $modules        = $this->getModuleList();
        $active_modules = [];
        foreach ($modules as $module) {
            if ($module['status']) {
                $active_modules[$module['name']] = $module;
            }
        }
        $this->active_module_list = $active_modules;
        return $active_modules;
    }

    /**
     * @DESC          # 获取模块信息
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/17 9:13
     * 参数区：
     *
     * @param string $module_name
     *
     * @return mixed
     */
    public function getModuleInfo(string $module_name): mixed
    {
        if ($modules = $this->getModuleList()) {
            if (isset($modules[$module_name])) {
                return $modules[$module_name];
            }
        }
        return null;
    }

    public static function getCommands(): array
    {
        $commands = [];
        if (file_exists(Env::path_COMMANDS_FILE)) {
            $commands = (array)require self::path_COMMANDS_FILE;
        }
        return $commands;
    }

    /**
     * @throws Exception
     */
    public static function write(string $filename, string $content): bool
    {
        try {
            $file = new File();
            $file->open($filename, $file::mode_w);
            $file->write($content);
            $file->close();
            return true;
        } catch (Exception $exception) {
            if (DEV) {
                throw $exception;
            }
            return false;
        }
    }

    public static function open(string $filename, string $content): bool
    {
        try {
            $file = new File();
            $file->open($filename, $file::mode_w);
            $file->write($content);
            $file->close();
            return true;
        } catch (Exception $exception) {
            return false;
        }
    }
}
