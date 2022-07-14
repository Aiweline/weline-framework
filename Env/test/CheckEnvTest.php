<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Env\test;

use Weline\Framework\Env\CheckEnv;
use PHPUnit\Framework\TestCase;
use Weline\Framework\Manager\ObjectManager;

require __DIR__ . '/../../../../setup/bootstrap.php';
class CheckEnvTest extends TestCase
{
    public function testSetNeed()
    {
        $envCheck = ObjectManager::getInstance(CheckEnv::class);
        $result   = $envCheck->setNeed('module1', 'json');
        p($result);
    }
}
