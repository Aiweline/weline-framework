<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Database\Connection\Adapter\SqLite;

use Weline\Framework\Database\Connection\Api\ConnectorInterface;
use Weline\Framework\Database\Exception\DbException;
use Weline\Framework\Database\Exception\QueryException;
use Weline\Framework\Database\Exception\SqlParserException;

trait SqlTrait
{
    use \Weline\Framework\Database\Connection\Api\Sql\SqlTrait;
    /**
     * @param string $sql
     * @return string|string[]
     */
    protected static function formatSql(string $sql): string|array
    {
        $sql = self::parserFiled($sql);
        if (str_contains(strtolower($sql), 'truncate')) {
            $truncate_check_sqls = explode(strtolower($sql), ';');
            foreach ($truncate_check_sqls as $truncate_check_sql_key => $truncate_check_sql) {
                if (str_contains($truncate_check_sql, 'truncate')) {
                    # 修改成sqlite支持的delete形式
                    $sql = str_replace('truncate', ' delete from ', $sql);
                    $truncate_check_sqls[$truncate_check_sql_key] = $sql;
                }
            }
            $sql = implode(';', $truncate_check_sqls);
        }
        if (str_contains(strtolower($sql), 'curdate()-1')) {
            $sql = strtolower($sql);
            $sql = str_replace('curdate()-1','\'now\', \'-1 day\'', $sql);
        }
        if (str_contains(strtolower($sql), 'to_days')) {
            $sql = strtolower($sql);
            $sql = str_replace('to_days','DATE', $sql);
        }
        if (str_contains(strtolower($sql), 'now()')) {
            $sql = strtolower($sql);
            $sql = str_replace('now()','\'now\'', $sql);
        }
        if (str_contains(strtolower($sql), 'order by order')) {
            $sql = strtolower($sql);
            $sql = str_replace('order by order','order by `order`', $sql);
        }
        return $sql;
    }
}
