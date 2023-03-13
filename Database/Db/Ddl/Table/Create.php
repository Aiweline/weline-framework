<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Database\Db\Ddl\Table;

use Weline\Framework\App\Exception;
use Weline\Framework\Database\Api\Db\Ddl\Table\CreateInterface;
use Weline\Framework\Database\Api\Db\TableInterface;
use Weline\Framework\Database\Api\Connection\QueryInterface;
use Weline\Framework\Database\Db\Ddl\TableAbstract;

class Create extends TableAbstract implements CreateInterface
{
    public function createTable(string $table, string $comment = ''): CreateInterface
    {
        # 开始表操作
        $this->startTable($table, $comment);
        return $this;
    }

    public function addColumn(string $field_name, string $type, ?int $length, string $options, string $comment): CreateInterface
    {
        # 数字字段
        if ($type === TableInterface::column_type_INTEGER || $type === TableInterface::column_type_SMALLINT) {
            if (!$length) {
                $length = 11;
            }
            if (is_int($length)) {
                if ($length <= 2) {
                    $type = 'smallint';
                } elseif ($length <= 11) {
                    $type = 'int';
                } else {
                    $type = 'bigint';
                }
            } else {
                $type = 'int';
            }
        }
        $type_length    = $length ? "{$type}({$length})" : $type;
        $this->fields[$field_name] = "`{$field_name}` {$type_length} {$options} COMMENT '{$comment}'";

        return $this;
    }


    public function addIndex(string $type, string $name, array|string $column, string $comment = '', string $index_method = ''): CreateInterface
    {
        $comment      = $comment ? "COMMENT '{$comment}'" : '';
        $index_method = $index_method ? "USING {$index_method}" : '';
        $type         = strtoupper($type);
        switch ($type) {
            case self::index_type_DEFAULT:
                $this->indexes[] = "INDEX `{$name}`(`{$column}`) {$index_method} {$comment}" . PHP_EOL;

                break;
            case self::index_type_FULLTEXT:
                $this->indexes[] = "FULLTEXT INDEX `{$name}`(`{$column}`) {$index_method} {$comment}" . PHP_EOL;

                break;
            case self::index_type_UNIQUE:
                $this->indexes[] = "UNIQUE INDEX `{$name}`(`{$column}`) {$index_method} {$comment}" . PHP_EOL;

                break;
            case self::index_type_SPATIAL:
                $this->indexes[] = "SPATIAL INDEX `{$name}`(`{$column}`) {$index_method} {$comment}" . PHP_EOL;

                break;
            case self::index_type_KEY:
                $this->indexes[] = "KEY IDX `{$name}`(`{$column}`) {$index_method} {$comment}" . PHP_EOL;

                break;
            case self::index_type_MULTI:
                $type_of_column = getType($column);
                if (!is_array($column)) {
                    new Exception(self::index_type_MULTI . __('：此索引的column需要array类型,当前类型') . "{$type_of_column}" . ' 例如：[ID,NAME(19),AGE]');
                }
                $column          = implode(',', $column);
                $this->indexes[] = "INDEX `{$name}`(`$column`) {$index_method} {$comment}," . PHP_EOL;

                break;
            default:
                new Exception(__('未知的索引类型：') . $type);
        }

        return $this;
    }


    public function addAdditional(string $additional_sql = 'ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;'): CreateInterface
    {
        $this->additional = $additional_sql;

        return $this;
    }

    public function addConstraints(string $constraints = ''): CreateInterface
    {
        $this->constraints = $constraints;

        return $this;
    }


    public function addForeignKey(string $FK_Name, string $FK_Field, string $references_table, string $references_field, bool $on_delete = false, bool $on_update = false): CreateInterface
    {
        $on_delete_str        = $on_delete ? 'on delete cascade' : '';
        $on_update_str        = $on_update ? 'on update cascade' : '';
        $this->foreign_keys[] = "constraint {$FK_Name} foreign key ({$FK_Field}) references {$references_table}({$references_field}) {$on_delete_str} {$on_update_str}";
        return $this;
    }

    public function create(): QueryInterface
    {
        // 字段
        if(!array_key_exists('`create_time`', $this->fields)&&!array_key_exists('create_time', $this->fields)){
            $create_time_comment_words = __('创建时间');
            $this->fields['`create_time`'] = "`create_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '{$create_time_comment_words}'";
        }
        if(!array_key_exists('`update_time`', $this->fields)&&!array_key_exists('update_time', $this->fields)){
            $update_time_comment_words = __('更新时间');
            $this->fields['`update_time`'] = "`update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '{$update_time_comment_words}'";
        }
        $fields_str = implode(',' . PHP_EOL, $this->fields);
        $fields_str = rtrim($fields_str, PHP_EOL);
        // 索引
        $indexes_str = implode(',', $this->indexes);
        $indexes_str = rtrim($indexes_str, PHP_EOL);
        // 外键
        $foreign_key_str = implode(','.PHP_EOL, $this->foreign_keys);
        $foreign_key_str = rtrim($foreign_key_str, PHP_EOL);
        // 组装结尾逗号
        if ($this->indexes) {
            $fields_str .= ',';
        }
        if ($this->foreign_keys) {
            $indexes_str .= ',';
        }
        if ($this->constraints) {
            $foreign_key_str .= ',';
        }
        $comment = $this->comment ? "COMMENT '{$this->comment}'" : '';
        $sql     = <<<createSQL
CREATE TABLE {$this->table}(
 {$fields_str}
 {$indexes_str}
 {$foreign_key_str}
 {$this->constraints}                 
) {$comment} {$this->additional}
createSQL;
        try {
            $result = $this->query($sql);
        } catch (\Exception $exception) {
            throw new Exception(__('创建表失败，' . PHP_EOL . PHP_EOL . 'SQL：%1 ' . PHP_EOL . PHP_EOL . 'ERROR：%2', [$sql, $exception->getMessage()]));
        }
        return $result;
    }
}
