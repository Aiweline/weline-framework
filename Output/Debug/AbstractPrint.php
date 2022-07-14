<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Output\Debug;

abstract class AbstractPrint extends \Weline\Framework\Output\AbstractPrint implements PrintInterface
{
    /**
     * @DESC         |错误
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param array|string $data
     * @param string $message
     * @param string $color
     * @param int $pad_length
     * @return mixed|void
     */
    public function error($data = 'Error!', string $message = '', string $color = self::ERROR, int $pad_length = 25): mixed
    {
        $this->doPrint($data, $message, self::ERROR);
    }

    /**
     * @DESC         |成功
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param string $data
     * @param string $message
     * @param string $color
     * @param int $pad_length
     * @return mixed|void
     */
    public function success(string $data = 'Success!', string $message = '', string $color = self::ERROR, int $pad_length = 25): mixed
    {
        $this->doPrint($data, $message, self::SUCCESS);
    }

    /**
     * @DESC         |警告
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param string $data
     * @param string $message
     * @param string $color
     * @param int $pad_length
     * @return mixed|void
     */
    public function warning(string $data = 'Warning!', string $message = '', string $color = self::WARNING, int $pad_length = 25): mixed
    {
        $this->doPrint($data, $message, self::WARNING);
    }

    /**
     * @DESC         |提示
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param string $data
     * @param string $message
     * @param string $color
     * @param int $pad_length
     * @return mixed|void
     */
    public function note(string $data = 'Note!', string $message = '', string $color = self::NOTE, int $pad_length = 25): mixed
    {
        $this->doPrint($data, $message, self::NOTE);
    }

    /**
     * ----------------辅助方法-------------------
     * @param mixed $data
     * @param mixed $message
     * @param mixed $color
     * @param mixed $pad_length
     */

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
     * @param array|string $data
     * @param string $message
     * @param string $color
     * @param int $pad_length
     */
    public function doPrint(array|string $data, string $message, string $color, int $pad_length = 0): mixed
    {
        if (DEV) {
            error_log();
        }
        if (is_array($data)) {
            foreach ($data as $msg) {
                $this->printing($msg, $message, $color, $pad_length);
            }
        }
        $this->printing($data, $message, $color, $pad_length);
    }

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
     * @param string $data
     * @param string $message
     * @param string $color
     * @param int $pad_length
     */
    public function printing(string $data = 'Printing!', string $message = '', string $color = self::NOTE, int $pad_length = 0): void
    {
        $doc_tmp = '【' . $message . '】：' . $this->colorize(($pad_length ? str_pad($data, $pad_length) : $data), $color);
        $doc     = <<<COMMAND_LIST

$doc_tmp

COMMAND_LIST;
        exit($doc);
    }

    /**
     * @DESC         |终端输出颜色字体
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param string $text
     * @param string $status
     * @return string
     */
    public function colorize($text, $status): string
    {
        switch ($status) {
            case self::SUCCESS:
            case 'Green':
                $this->out = '[32m'; //Green

                break;
            case self::ERROR:
            case self::FAILURE:
            case 'Red':
                $this->out = '[31m'; //Red

                break;
            case self::WARNING:
            case 'Yellow':
                $this->out = '[33m'; //Yellow

                break;
            case self::NOTE:
            case 'Blue':
                $this->out = '[34m'; //Blue

                break;
            default:
                $this->out = '[31m'; //默认错误信息

                break;
        }

        return chr(27) . "{$this->out}" . "{$text}" . chr(27) . '[0m';
    }
}
