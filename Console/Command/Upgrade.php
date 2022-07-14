<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Console\Command;

use Weline\Framework\App\System;
use Weline\Framework\App\Env;
use Weline\Framework\Console\Command;
use Weline\Framework\Console\CommandAbstract;
use Weline\Framework\Console\CommandInterface;
use Weline\Framework\System\File\Data\File;
use Weline\Framework\System\File\Scan;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Output\Cli\Printing;

class Upgrade extends CommandAbstract
{
    /**
     * @var System
     */
    private System $system;

    /**
     * @var Command
     */
    private Command $command;

    public function __construct(
        Printing $printer,
        Command  $command,
        System   $system
    ) {
        $this->printer = $printer;
        $this->system = $system;
        $this->command = $command;
    }

    /**
     * @DESC         |命令描述
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @return string
     */
    public function getTip(): string
    {
        return '更新命令';
    }

    /**
     * @DESC         |执行
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
     * @throws \ReflectionException
     * @throws \Weline\Framework\App\Exception
     */
    public function execute(array $args = [])
    {
        // 删除命令文件
        if (is_file(Env::path_COMMANDS_FILE)) {
            list($out, $var) = $this->system->exec('rm ' . Env::path_COMMANDS_FILE);
            $this->printer->printList($out);
        }

        $commands = $this->scan();
        /**@var $file \Weline\Framework\System\File\Io\File */
        $file = ObjectManager::getInstance(\Weline\Framework\System\File\Io\File::class);
        $file->open(Env::path_COMMANDS_FILE, $file::mode_w_add);
        $text = '<?php return ' . var_export($commands, true) . ';';
        $file->write($text);
        $file->close();
        $this->printer->printList($commands);
        $this->printer->success(__('所有命令已更新！'));
    }

    /**
     * @DESC         |扫描命令
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @return array
     */
    public function scan(): array
    {
        return $this->getDirFileCommand();
    }

    /**
     * @DESC         |文件转换命令
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @return array
     * @throws \ReflectionException
     */
    private function getDirFileCommand(): array
    {
        $commands = [];
        /**@var $scanner Scan */
        $scanner = ObjectManager::getInstance(Scan::class);

        // 扫描核心命令
        $scanner->__init();
        $core = $scanner->scanDirTree(Env::vendor_path);
        $scanner->__init();
        $custom = $scanner->scanDirTree(APP_CODE_PATH);

        // 合并
        $command_dir_files = array_merge($core, $custom);

        /** @var $command_files File[] */
        foreach ($command_dir_files as $dir => $command_files) {
            if (is_string($dir) && is_int(strpos($dir, self::dir))) {
                if (IS_WIN) {
                    $dir = str_replace(DS, DS, $dir);
                }
                $dir_command_array = explode(self::dir, $dir);
                $command_dir = trim(array_pop($dir_command_array), DS);
                $module_dir_arr = explode(DS, trim(array_pop($dir_command_array), DS));
                $vendor = array_shift($module_dir_arr);
                $module = implode('\\', $module_dir_arr);
                $module_name = $vendor . '\\' . $module;
                if ($command_dir) {
                    foreach ($command_files as $file) {
                        $command_dir_file = $file->getNamespace() . '\\' . $file->getFilename();
                        $command_dir_file_arr = explode(self::dir, $command_dir_file);
                        $command_dir_file = trim(array_pop($command_dir_file_arr), DS);
                        $command = str_replace('\\', ':', strtolower($command_dir_file));
                        $command = trim($command, ':');
                        if ($command) {
                            $command_tip = str_replace(DS, ':', strtolower($command_dir)) . '#' . $module_name;
                            $command_class_path = $this->command->getCommandPath($module_name, $command);
                            // 排除非框架系统命令类
                            if (class_exists($command_class_path)) {
                                try {
                                    $command_class = ObjectManager::getInstance($command_class_path);
                                    if ($command_class instanceof CommandInterface) {
                                        $commands[$command_tip][$command] = $command_class->getTip();
                                    } else {
                                        if (DEV && CLI) {
                                            $this->printer->warning(__('命令类：%1 必须继承：%2', [$command_class_path, CommandInterface::class]));
                                        }
                                    }
                                } catch (\Exception $exception) {
                                    // 异常的类不加入命令
                                }
                            }
                        }
                    }
                }
            }
        }

        return $commands;
    }
}
