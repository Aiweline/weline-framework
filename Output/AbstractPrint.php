<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Output;

use Weline\Framework\System\File\Io\File;

abstract class AbstractPrint implements PrintInterface
{
    protected $out;

    private File $file;

    public function error(mixed $data = 'Error!', string $message = '', string $color = self::ERROR, int $pad_length = 25)
    {
        return $this->printing('Error!');
    }

    public function success(string $data = 'Success!', string $message = '', string $color = self::ERROR, int $pad_length = 25)
    {
    }

    public function warning(string $data = 'Warning!', string $message = '', string $color = self::ERROR, int $pad_length = 25)
    {
    }

    public function note(string $data = 'Note!', string $message = '', string $color = self::ERROR, int $pad_length = 25)
    {
    }

    /**
     * ----------------辅助方法-------------------
     */

    /**
     * @DESC         |打印消息
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param string $message
     *
     * @return mixed|string
     */
    public function printing(string $message = 'Printing!')
    {
        return $message;
    }

    /**
     * @DESC         |方法描述
     *
     * 参数区：
     *
     * @param string $log_path
     * @param string $content
     * @param int    $type
     *
     * @throws \Weline\Framework\App\Exception
     */
    protected function write(string $log_path, string $content, int $type)
    {
        if (!isset($this->file)) {
            $this->file = new File();
        }
        $this->file->open($log_path);
        $this->file->write("【{$type}】" . $content . PHP_EOL);
        $this->file->close();
    }
}
