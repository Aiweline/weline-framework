<?php

declare(strict_types=1);
/**
 * 文件信息
 * 作者：邹万才
 * 网名：秋风雁飞(Aiweline)
 * 网站：www.aiweline.com/bbs.aiweline.com
 * 工具：PhpStorm
 * 日期：2021/6/21
 * 时间：11:45
 * 描述：此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
 */

namespace Weline\Framework\Database\Connection\Query\Adapter;

use Weline\Framework\Database\Api\Connection\QueryInterface;
use Weline\Framework\Database\Connection\Query;

class Mysql extends Query
{
    public function reindex(string $table): void
    {
        $this->query('REINDEX TABLE ' . $table);
        $this->query('OPTIMIZE TABLE ' . $table);
//        # 查看表存储引擎
//        $index_fields = $this->query('SHOW INDEX FROM ' . $table)->fetch()?? '';
//        foreach ($index_fields as $index_field) {
//            switch ($index_field['Key_name']) {
//
//            }
//            # 删除索引
//            $this->query('ALTER TABLE ' . $table . ' DROP INDEX ' . $index_field['Key_name']);
//            # 重建索引
//
//        }
    }

    public function getIndexFields(): QueryInterface
    {
        return $this->query('show index from ' . $this->table);
    }

    public function dev()
    {
        return "
        #拼接删除非主键索引的语法
-- SELECT
-- 	CONCAT( 'ALTER TABLE ', i.TABLE_NAME, ' DROP INDEX ', i.INDEX_NAME, ' ;' ) AS drop_sql,
-- CONCAT( i.TABLE_NAME ) AS table_name,
-- CONCAT( i.INDEX_NAME ) AS index_name,
-- CONCAT( i.COLUMN_NAME ) AS column_name
-- 
-- FROM
-- 	INFORMATION_SCHEMA.STATISTICS i #过滤主键索引
-- 	
-- WHERE
-- 	TABLE_SCHEMA = 'weline' 
-- 	AND i.INDEX_NAME <> 'PRIMARY';
	

#拼接删除主键索引的语法
-- SELECT
-- 	CONCAT( 'ALTER TABLE ', i.TABLE_NAME, ' DROP PRIMARY KEY;' ) AS drop_sql,
-- CONCAT( i.TABLE_NAME ) AS table_name,
-- CONCAT( i.INDEX_NAME ) AS index_name,
-- CONCAT( i.COLUMN_NAME ) AS column_name,
-- CONCAT( i.INDEX_TYPE ) AS idnex_type
-- 
-- FROM
-- 	INFORMATION_SCHEMA.STATISTICS i #过滤主键索引
-- 	
-- WHERE
-- 	TABLE_SCHEMA = 'weline' 
-- 	AND i.INDEX_NAME = 'PRIMARY';
        ";
    }
}
