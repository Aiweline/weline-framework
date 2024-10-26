<?php

namespace Weline\Framework\Console\Console\Server;

use Weline\Framework\App\Env;
use Weline\Framework\App\System;
use Weline\Framework\Console\CommandInterface;
use Weline\Framework\Console\Console\Deploy\Mode\Set;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Output\Cli\Printing;

class Start implements CommandInterface
{
    function __construct(
       private Set $set,
        private System $system,
        private Printing $printer
    )
    {

    }

    /**
     * @inheritDoc
     */
    public function execute(array $args = [], array $data = [])
    {
        # 咨询，WEB服务器会将部署模式设置为DEV
        $this->printer->note('启用PHP内资本地WebServer服务...');
        # 调用静态文件部署
        if(Env::get('deploy') !== 'dev'){
            $this->printer->setup(__('启用PHP内置服务器需要将部署模式设置为DEV，当前部署模式为 %1，是否继续?',Env::get('deploy')));
            $input = $this->system->input();
            if (strtolower(chop($input)) !== 'y') {
                $this->printer->setup('已为您取消操作！');
                return;
            }
            $this->set->deploy('dev');
        }
        Server::instance();
    }

    /**
     * @inheritDoc
     */
    public function tip(): string
    {
        return '启用PHP内资本地WebServer服务。';
    }
}