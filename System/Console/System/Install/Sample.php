<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\System\Console\System\Install;

use Weline\Framework\Console\CommandAbstract;

class Sample extends CommandAbstract
{
    public function execute(array $args = [])
    {
        $this->printer->note('安装命令示例：');
        $line_break = IS_WIN ? '^' : '\\';
        $this->printer->success('php bin/m system:install ' . $line_break . '
--db-type=mysql ' . $line_break . '
--db-hostname=127.0.0.1 ' . $line_break . '
--db-database=weline ' . $line_break . '
--db-username=weline ' . $line_break . '
--db-password=weline
--db-charset=utf8 ' . $line_break . '
--db-collate=utf8_general_ci' . $line_break . '
            ');
        $this->printer->note('如果你是Windows11：');
        $this->printer->success('php bin/m system:install  --db-type=mysql  --db-hostname=127.0.0.1  --db-database=weline  --db-username=weline  --db-password=weline --db-charset=utf8 --db-collate=utf8_general_ci');
        exit();
    }

    public function getTip(): string
    {
        return '安装脚本样例';
    }
}
