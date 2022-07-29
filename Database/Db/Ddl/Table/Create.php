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
        if ($type === TableInterface::column_type_INTEGER) {
            if ($length === 0) {
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
        $this->fields[] = "`{$field_name}` {$type_length} {$options} COMMENT '{$comment}'," . PHP_EOL;

        return $this;
    }


    public function addIndex(string $type, string $name, array|string $column, string $comment='', string $index_method = 'BTREE'): CreateInterface
    {
        $comment = $comment?"COMMENT '{$comment}'": '';
        switch ($type) {
            case self::index_type_DEFAULT:
                $this->indexes[] = "INDEX `{$name}`(`{$column}`) USING {$index_method} {$comment}," . PHP_EOL;

                break;
            case self::index_type_FULLTEXT:
                $this->indexes[] = "FULLTEXT INDEX `{$name}`(`{$column}`) USING {$index_method} {$comment}," . PHP_EOL;

                break;
            case self::index_type_UNIQUE:
                $this->indexes[] = "UNIQUE INDEX `{$name}`(`{$column}`) USING {$index_method} {$comment}," . PHP_EOL;

                break;
            case self::index_type_SPATIAL:
                $this->indexes[] = "SPATIAL INDEX `{$name}`(`{$column}`) USING {$index_method} {$comment}," . PHP_EOL;

                break;
            case self::index_type_KEY:
                $this->indexes[] = "KEY IDX `{$name}`(`{$column}`) USING {$index_method} {$comment}," . PHP_EOL;

                break;
            case self::index_type_MULTI:
                $type_of_column = getType($column);
                if (!is_array($column)) {
                    new Exception(self::index_type_MULTI . __('：此索引的column需要array类型,当前类型') . "{$type_of_column}" . ' 例如：[ID,NAME(19),AGE]');
                }
                $column          = implode(',', $column);
                $this->indexes[] = "INDEX `{$name}`(`$column`) USING {$index_method} {$comment}," . PHP_EOL;

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
        $fields_str = '';
        foreach ($this->fields as $field) {
            if (end($this->fields) === $field) {
                $field = trim($field, PHP_EOL);
                if (empty($this->indexes)) {
                    $field = trim($field, ',');
                }// 如果没有设置索引
            }
            $fields_str .= $field;
        }
        $fields_str = trim($fields_str, ',');
        if (!is_int(strpos($fields_str, '`create_time`'))) {
            $fields_str                .= ',' . PHP_EOL;
            $create_time_comment_words = __('创建时间');
            $fields_str                .= "`create_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '{$create_time_comment_words}'," . PHP_EOL;
        }
        if (!is_int(strpos($fields_str, '`update_time`'))) {
            if (is_int(strpos($fields_str, ',' . PHP_EOL))) {
                $fields_str = rtrim($fields_str, ',' . PHP_EOL);
            }
            $fields_str                = rtrim($fields_str, ',');
            $fields_str                .= ',' . PHP_EOL;
            $update_time_comment_words = __('更新时间');
            $fields_str                .= "`update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '{$update_time_comment_words}'," . PHP_EOL;
        }
        if (is_int(strpos($fields_str, ',' . PHP_EOL))) {
            $fields_str = rtrim($fields_str, ',' . PHP_EOL);
        }
        // 索引
        $indexes_str = '';
        foreach ($this->indexes as $index) {
            if (end($this->indexes) === $index) {
                if (empty($this->constraints)) {
                    $index = trim(trim($index, PHP_EOL), ',');
                }
            }
            $indexes_str .= $index;
        }
        if ($indexes_str) {
            $fields_str .= ',';
        }
        $indexes_str = rtrim($indexes_str, PHP_EOL);
        $indexes_str = rtrim($indexes_str, '\n\r');
        // 外键
        $foreign_key_str = '';
        foreach ($this->foreign_keys as $foreign_key) {
            if (end($this->foreign_keys) === $foreign_key) {
                if (empty($this->constraints)) {
                    $foreign_key = trim(trim($foreign_key, PHP_EOL), ',');
                }
            }
            $foreign_key_str .= $foreign_key;
        }
        if ($foreign_key_str) {
            $indexes_str .= ',';
        }
        $foreign_key_str = rtrim($foreign_key_str, PHP_EOL);
        $foreign_key_str = rtrim($foreign_key_str, '\n\r');

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
