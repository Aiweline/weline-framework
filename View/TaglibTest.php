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
    private Taglib $taglib;
    function setUp(): void
    {
        parent::setUp();
        $this->taglib = ObjectManager::getInstance(Taglib::class);
    }

    public function testVarParser()
    {
        $parse_str = $this->taglib->varParser('Request.param.c_id');
        self::assertTrue($parse_str==="(\$Request['param']['c_id']??'') ",'解析变量');
    }
    public function testTagIf()
    {
        $template = new Template();
//        $content = '@if{req.type==="progress-select-entity"=>"active"}';
        $content = "@if{req.type==='progress-select-entity'=>'active'}";
        $this->taglib->tagReplace($template, $content);
        dd($content);
        self::assertTrue($parse_str==="(\$Request['param']['c_id']??'') ",'解析变量');
    }
    public function testVarParserEmptyString()
    {
        $parse_str = $this->taglib->varParser('Request.param.c_id');
        self::assertTrue($parse_str==="(\$Request['param']['c_id']??'') ",'解析变量');
    }
}
