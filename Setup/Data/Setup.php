<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Setup\Data;

use Weline\Framework\Output\Cli\Printing;
use Weline\Framework\Setup\Db\Setup as DbSetup;

class Setup
{
    protected DbSetup $setup_db;

    /**
     * @var Printing
     */
    private Printing $printing;

    /**
     * Setup 初始函数...
     * @param DbSetup $setup_db
     * @param Printing $printing
     */
    public function __construct(
        DbSetup $setup_db,
        Printing $printing
    ) {
        $this->setup_db = $setup_db;
        $this->printing = $printing;
    }

    /**
     * @DESC          # 获取数据库链接
     *
     * @AUTH  秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/31 20:47
     * 参数区：
     * @deprecated
     * @return DbSetup
     */
    public function getDb(): DbSetup
    {
        return $this->setup_db;
    }

    /**
     * @DESC          # 打印
     *
     * @AUTH  秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/31 20:48
     * 参数区：
     * @return Printing
     */
    public function getPrinter(): Printing
    {
        return $this->printing;
    }
}
