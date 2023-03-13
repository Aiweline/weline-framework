<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Database\Console\Index;

use Weline\Framework\Output\Cli\Printing;
use Weline\Indexer\Model\Indexer;

class Listing implements \Weline\Framework\Console\CommandInterface
{
    private Indexer $indexer;
    private Printing $printing;

    public function __construct(
        Indexer  $indexer,
        Printing $printing
    )
    {
        $this->indexer  = $indexer;
        $this->printing = $printing;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $args = [], array $data = [])
    {
        $indexer_list = $this->indexer->select()->fetch();
        /**@var Indexer $indexer */
        foreach ($indexer_list as $indexer) {
            $msg = str_pad($this->printing->colorize($indexer->getName(), $this->printing::SUCCESS), 35, ' ', STR_PAD_RIGHT);
            $msg .= $this->printing->colorize($indexer->getTable(), $this->printing::NOTE);
            $this->printing->printing($msg);
        }
    }

    /**
     * @inheritDoc
     */
    public function tip(): string
    {
        return __('索引器列表');
    }
}
