<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Database\Db\Ddl;

use Weline\Framework\App\Exception;
use Weline\Framework\Database\Api\Db\Ddl\TableInterface;
use Weline\Framework\Database\Api\Connection\QueryInterface;
use Weline\Framework\Database\DbManager;
use Weline\Framework\Database\ConnectionFactory;
use Weline\Framework\Manager\ObjectManager;

abstract class TableAbstract implements TableInterface
{
    // 数据字段
    protected string $table;

    protected string $primary_key = 'id';
    protected string $new_table_name = '';

    protected string $comment;

    protected array $fields = [];
    protected array $alter_fields = [];
    protected array $delete_fields = [];
    protected array $indexes = [];

    protected array $foreign_keys = [];

    protected string $constraints = '';

    protected string $additional = 'ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;';

    protected ?ConnectionFactory $connection = null;
    protected ?QueryInterface $query = null;

    /**
     * @DESC          # 设置链接
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/5 18:11
     * 参数区：
     * @throws Exception
     * @throws \ReflectionException
     */
    public function setConnection(ConnectionFactory $connection): static
    {
        $this->connection = $connection;
        $this->query      = $connection->getQuery();
        return $this;
    }

    /**
     * @DESC          # 数据库链接
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/31 20:43
     * 参数区：
     * @return ConnectionFactory
     * @throws Exception
     */
    public function getConnection(): ConnectionFactory
    {
        # 如果没有链接，则抛出异常
        if (!$this->connection) {
            throw new Exception('请先设置数据库链接：setConnection()');
        }
        return $this->connection;
    }

    /**
     * @DESC          # 数据库链接
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/31 20:43
     * 参数区：
     * @return QueryInterface
     * @throws Exception
     */
    public function getQuery(): QueryInterface
    {
        # 如果没有链接，则抛出异常
        if (!$this->query) {
            throw new Exception('请先设置数据库链接：setConnection()');
        }
        return $this->query;
    }

    /**
     * @DESC          # 开始表操作
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/5 18:23
     * 参数区：
     *
     * @param string $table
     * @param string $primary_key
     * @param string $comment
     * @param string $new_table_name
     */
    protected function startTable(string $table, string $comment = '', string $primary_key = '', string $new_table_name = '')
    {
        # 清空所有表操作属性
        $this->init_vars();
        # 重新赋予新表的值
        if ($primary_key) {
            $this->primary_key = $primary_key;
        }
        $this->table          = $table;
        $this->new_table_name = $new_table_name ? '`' . $this->connection->getConfigProvider()->getDatabase() . '`.`' . $new_table_name . '`' : '';
        $this->comment        = $comment;
    }

    /**
     * @DESC          # 清空所有表操作属性
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/5 18:22
     * 参数区：
     */
    protected function init_vars()
    {
        foreach (self::init_vars as $attr => $init_var) {
            $this->$attr = $init_var;
        }
    }

    /**
     * @DESC          # 查询
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/31 20:34
     * 参数区：
     *
     * @param string $sql
     *
     * @return \Weline\Framework\Database\Api\Connection\QueryInterface
     * @throws Exception
     */
    public function query(string $sql): \Weline\Framework\Database\Api\Connection\QueryInterface
    {
        return $this->getQuery()->query($sql);
    }

    /**
     * @DESC          # 数据库类型
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/31 20:36
     * 参数区：
     * @return string
     * @throws Exception
     */
    public function getType(): string
    {
        return $this->getConnection()->getConfigProvider()->getDbType();
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getPrefix(): string
    {
        return $this->getConnection()->getConfigProvider()->getPrefix();
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @DESC          # 读取表字段
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/5 17:32
     * 参数区：
     *
     * @param string $table_name 【1、如果存在表名就读取对应表的字段；2、不存在则读取Table类设置的表名】
     *
     * @return mixed
     */
    public function getTableColumns(string $table_name = ''): mixed
    {
        $table_name = $table_name ?: $this->table;
        return $this->query("SHOW FULL COLUMNS FROM {$table_name}")->fetch();
    }

    /**
     * @DESC          # 读取创建表SQL
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/5 22:08
     * 参数区：
     *
     * @param string $table_name
     *
     * @return mixed
     */
    public function getCreateTableSql(string $table_name = ''): mixed
    {
        $table_name = $table_name ?: $this->table;
        return $this->query("SHOW CREATE TABLE {$table_name}")->fetch()[0]["Create Table"];
    }
}
