<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\App\Controller;

use Weline\Framework\App\Session\FrontendSession;
use Weline\Framework\Controller\PcController;
use Weline\Framework\Manager\ObjectManager;

class FrontendController extends PcController
{
    private ?FrontendSession $session = null;

    public function __init()
    {
        parent::__init();
        $this->getSession(FrontendSession::class);
    }
}
