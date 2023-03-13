<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\View;

use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\UnitTest\TestCore;

class TaglibTest extends TestCore
{
    public function testVarParser()
    {
        /**@var Taglib $taglib */
        $taglib    = ObjectManager::getInstance(Taglib::class);
        $parse_str = $taglib->varParser('Request.param.c_id');
        p($parse_str);
    }
}
