<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Session\Driver;

interface DriverInterface
{
    public const driver_FILE = 'files';

    public const driver_REDIS = 'redis';

    public function __construct(array $config);
}
