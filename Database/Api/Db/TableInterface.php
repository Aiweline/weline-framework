<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Database\Api\Db;

use Weline\Framework\Database\ConnectionFactory;

interface TableInterface
{
    /**
     * 字段类型
     */
    public const column_type_BOOLEAN = 'boolean';

    public const column_type_VARCHAR = 'varchar';

    public const column_type_SMALLINT = 'smallint';

    public const column_type_INTEGER = 'integer';

    public const column_type_BIGINT = 'bigint';

    public const column_type_FLOAT = 'float';

    public const column_type_NUMERIC = 'numeric';

    public const column_type_DECIMAL = 'decimal';

    public const column_type_DATE = 'date';

    public const column_type_TIMESTAMP = 'timestamp';

    // 能够支持从1970年开始的日期时间+在一些关系数据库中的自动触发器
    public const column_type_DATETIME = 'datetime';

    // 能够支持1970年以前的长时间数据
    public const column_type_TEXT = 'text';

    // 一个真正的blob，以二进制形式存储在DB中
    public const column_type_BLOB = 'blob';

    // 当查询参数不能使用语句选项时，用于向后兼容
    public const column_type_VARBINARY = 'varbinary';

    /**
     * 索引类型
     */
    public const index_type_DEFAULT = 'DEFAULT';

    public const index_type_FULLTEXT = 'FULLTEXT';//-- FullText 全文索引，需指定存储引擎为MyISAM，MySQL默认存储引擎为InnoDB

    public const index_type_SPATIAL = 'SPATIAL';//-- SPATIAL 创建空间索引，需指定存储引擎为MyISAM，MySQL默认存储引擎为InnoDB

    public const index_type_UNIQUE = 'UNIQUE';//-- 创建唯一索引

    public const index_type_MULTI = 'MULTI';//-- 创建组合索引

    public const index_type_KEY     = 'KEY';  //--用KEY创建普通索引
    public const index_method_HASH  = 'HASH'; //--索引方法-HASH
    public const index_method_BTREE = 'BTREE';//--索引方法-BTREE

    public const table_TABLE         = 'table';
    public const table_COMMENT       = 'comment';
    public const table_FIELDS        = 'fields';
    public const table_ALERT_FIELDS  = 'alter_fields';
    public const table_DELETE_FIELDS = 'delete_fields';
    public const table_INDEXS        = 'indexes';
    public const table_FOREIGN_KEYS  = 'foreign_keys';
    public const table_TYPE          = 'type';
    public const table_CONSTRAINTS   = 'constraints';
    public const table_ADDITIONAL    = 'additional';

    public const init_vars = [
        self::table_TABLE         => '',
        self::table_COMMENT       => '',
        self::table_FIELDS        => [],
        self::table_ALERT_FIELDS  => [],
        self::table_DELETE_FIELDS => [],
        self::table_INDEXS        => [],
        self::table_FOREIGN_KEYS  => [],
        self::table_CONSTRAINTS   => '',
        self::table_ADDITIONAL    => 'ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;',
    ];

    /**
     * @DESC          # 设置链接
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/5/29 20:28
     * 参数区：
     *
     * @param ConnectionFactory $connection
     *
     * @return mixed
     */
    public function setConnection(ConnectionFactory $connection);

    /**
     * @DESC          # 创建表
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/5 21:03
     * 参数区：
     * @return \Weline\Framework\Database\Api\Db\Ddl\Table\CreateInterface
     */
    public function createTable(): \Weline\Framework\Database\Api\Db\Ddl\Table\CreateInterface;

    /**
     * @DESC          # 修改表
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/5 21:04
     * 参数区：
     * @return \Weline\Framework\Database\Api\Db\Ddl\Table\AlterInterface
     */
    public function alterTable(): \Weline\Framework\Database\Api\Db\Ddl\Table\AlterInterface;
}
