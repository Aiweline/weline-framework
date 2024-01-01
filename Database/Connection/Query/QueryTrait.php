<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Database\Connection\Query;

use Weline\Framework\Database\Exception\ModelException;
use Weline\Framework\Database\Exception\QueryException;
use Weline\Framework\Database\Exception\SqlParserException;
use Weline\Framework\Database\ConnectionFactory;
use Weline\Framework\Cache\CacheInterface;
use Weline\Framework\Database\Cache\DbCache;
use Weline\Framework\Database\Exception\DbException;
use Weline\Framework\Database\Model;
use Weline\Framework\Exception\Core;
use Weline\Framework\Manager\ObjectManager;

trait QueryTrait
{
    public array $conditions = [
        '>',
        '<',
        '>=',
        '!=',
        '=<',
        '<>',
        'like',
        'in',
        'find_in_set',
        '=',
    ];
    public ConnectionFactory $connection;
    public string $db_name = 'default';

    public function __construct(ConnectionFactory $connection)
    {
        $this->connection = $connection;
        $this->db_name    = $this->connection->getConfigProvider()->getDatabase();
    }

    public function __sleep()
    {
        return ['db_name', 'connection'];
    }


    public function getTable($table_name): string
    {
        return "`{$this->db_name}`.`{$table_name}`";
    }

    /**
     * @DESC          | 获取链接
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/16 21:10
     *
     * @return ConnectionFactory
     */
    public function getConnection(): ConnectionFactory
    {
        return $this->connection;
    }

    /**
     * @DESC          | 设置链接
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/16 21:10
     *
     * @param ConnectionFactory $connection
     */
    public function setConnection(ConnectionFactory $connection): void
    {
        $this->connection = $connection;
    }


    /**
     * @DESC          |  # 检测条件数组 下角标 必须为数字
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/16 22:39
     * 参数区：
     *
     * @param array $where_array
     * @param mixed $f_key
     *
     * @throws null
     */
    private function checkWhereArray(array $where_array, mixed $f_key): void
    {
        foreach ($where_array as $f_item_key => $f_item_value) {
            if (!is_numeric($f_item_key)) {
                $this->$this->exceptionHandle(__('Where查询异常：%1,%2,%3', ["第{$f_key}个条件数组错误", '出错的数组：["' . implode('","', $where_array) . '"]', "示例：where([['name','like','%张三%','or'],['name','like','%李四%']])"]));
            }
        }
    }

    /**
     * @DESC          | 检测条件参数是否正确
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/16 22:30
     * 参数区：
     *
     * @param array $where_array
     *
     * @return string
     * @throws null
     */
    private function checkConditionString(array $where_array): string
    {
        if (in_array(strtolower($where_array[1]), $this->conditions)) {
            return $where_array[1];
        } else {
            $this->exceptionHandle(__('当前错误的条件操作符：%1 ,当前的条件数组：%2, 允许的条件符：%3', [$where_array[1], '["' . implode('","', $where_array) . '"]', '["' . implode('","', $this->conditions) . '"]']));
        }
    }

    /**
     * @DESC          # 准备sql
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/17 22:52
     * 参数区：
     * @throws null
     */
    private function prepareSql($action): void
    {
        if ($this->table == '') {
            $this->exceptionHandle(__('没有指定table表名！'));
        }
        # 处理 joins
        $joins = '';
        foreach ($this->joins as $join) {
            $joins .= " {$join[2]} JOIN {$join[0]} ON {$join[1]} ";
        }
        # 处理 Where 条件
        $wheres = '';
        // 如果有联合主键，把条件按照联合主键的顺序依次添加到sql语句中，提升查询速度
        if (!empty($this->_index_sort_keys)) {
            $_index_sort_keys_wheres = [];
            foreach ($this->wheres as $where_key => $where) {
                $where_cond  = $where[1];
                $where_field = $where[0];
                if (str_contains($where_field, '.')) {
                    $where_field_arr = explode('.', $where_field);
                    $where_field     = array_pop($where_field_arr);
                }
                if (in_array($where_field, $this->_index_sort_keys)) {
                    $_index_sort_keys_wheres[$where_field][] = $where;
                    unset($this->wheres[$where_key]);
                }
            }
            if($_index_sort_keys_wheres){
                foreach (array_reverse($this->_index_sort_keys) as $filed_key) {
                    if (isset($_index_sort_keys_wheres[$filed_key])) {
                        array_unshift($this->wheres, ...$_index_sort_keys_wheres[$filed_key]);
                    }
                }
            }
        }
        if ($this->wheres) {
            $wheres .= ' WHERE ';
            $logic  = 'AND ';
            foreach ($this->wheres as $key => $where) {
                $key += 1;
                # 如果自己设置了where 逻辑连接符 就修改默认的连接符 AND
                if (isset($where[3])) {
                    $logic = array_pop($where) . ' ';
                }
                switch (count($where)) {
                    # 字段等于sql
                    case 1:
                        $wheres .= $where[0] . " {$logic} ";
                        break;
                    # 默认where逻辑连接符为AND
                    default:
                        $param = ':' . trim($where[0], '`');
                        # 是sql的字段不添加字段引号(没有值则是sql)
                        if (null === $where[2]) {
                            $wheres .= '(' . $where[0] . ') ' . $logic;
                        } else {
                            $quote = '`';
                            # 复杂参数转化
                            if (str_contains($where[0], '(')) {
                                $quote = '';
                                $param = str_replace('(', '_', $param);
                            }
                            if (str_contains($where[0], ')')) {
                                $quote = '';
                                $param = str_replace(')', '_', $param);
                            }
                            if (str_contains($where[0], ',')) {
                                $quote = '';
                                $param = str_replace(',', '_', $param);
                            }
                            $where[0] = $quote . (($quote === '`') ? str_replace('.', '`.`', $where[0]) : $where[0]) . $quote;
                            # 处理带别名的参数键
                            $param                      = str_replace('.', '__', $param) . $key;
                            $this->bound_values[$param] = (string)$where[2];
                            $where[2]                   = match (strtolower($where[1])) {
                                'in', 'find_in_set' => '(' . $param . ')',
                                default => $param,
                            };
                            $wheres                     .= '(' . implode(' ', $where) . ') ' . $logic;
                        }
                }
            }
            $wheres = rtrim($wheres, $logic);
        }
        # 排序
        $order = '';
        foreach ($this->order as $field => $dir) {
            $order .= "$field $dir,";
        }
        $order = rtrim($order, ',');
        if ($order) {
            $order = 'ORDER BY ' . $order;
        }

        # 匹配sql
        switch ($action) {
            case 'insert':
                $values = '';
                foreach ($this->insert as $insert_key => $insert) {
                    $insert_key += 1;
                    $values     .= '(';
                    foreach ($insert as $insert_field => $insert_value) {
                        $insert_bound_key                      = ':' . md5("{$insert_field}_field_{$insert_key}");
                        $this->bound_values[$insert_bound_key] = $insert_value;
                        $values                                .= "$insert_bound_key , ";
                    }
                    $values = rtrim($values, ', ');
                    $values .= '),';
                }
                $values = rtrim($values, ',');
                $sql    = "INSERT INTO {$this->table} {$this->fields} VALUES {$values} {$this->exist_update_sql}";
                break;
            case 'delete':
                $sql = "DELETE FROM {$this->table} {$wheres} {$this->additional_sql}";
                break;
            case 'update':
                # 设置where条件
                $identity_values = array_column($this->updates, $this->identity_field);
                if ($identity_values) {
                    $identity_values_key                      = ':' . md5('identity_values_key');
                    $identity_values_str                      = implode('\',\'', $identity_values);
                    $this->bound_values[$identity_values_key] = $identity_values_str;
                    $wheres                                   .= ($wheres ? ' AND ' : 'WHERE ') . "$this->identity_field IN ( $identity_values_key )";
                }

                # 排除没有条件值的更新
                if (empty($wheres)) {
                    throw new DbException(__('请设置更新条件：第一种方式，->where($condition)设置，第二种方式，更新数据中包含条件值（默认为字段id,可自行设置->update($arg1,$arg2)第二参数指定根据数组中的某个字段值作为依赖条件更新。）'));
                }

                # 配置更新语句
                $updates = '';
                # 多条更新
                if ($this->updates) {
                    # 存在$identity_values 表示多维数组更新
                    if ($identity_values) {
                        $keys = array_keys(current($this->updates));
                        foreach ($keys as $column) {
                            $updates .= sprintf("`%s` = CASE `%s` \n", $column, $this->identity_field);
                            foreach ($this->updates as $update_key => $line) {
                                # 主键值
                                $update_key                                     += 1;
                                $identity_field_column_key                      = ':' . md5("{$this->identity_field}_{$column}_key_{$update_key}");
                                $this->bound_values[$identity_field_column_key] = $line[$this->identity_field];

                                # 更新键值
                                $identity_field_column_value                      = ':' . md5("update_{$column}_value_{$update_key}");
                                $this->bound_values[$identity_field_column_value] = $line[$column];
                                # 组装
                                $updates .= sprintf('WHEN %s THEN %s ', $identity_field_column_key, $identity_field_column_value);
//                            $updates .= sprintf("WHEN '%s' THEN '%s' \n", $line[$this->identity_field], $identity_field_column_value);
                            }
                            $updates .= 'END,';
                        }
                    } else { # 普通单条更新
                        if (1 < count($this->updates)) {
                            throw new SqlParserException(__('更新条数大于一条时请使用示例更新：$query->table("demo")->identity("id")->update(["id"=>1,"name"=>"测试1"])->update(["id"=>2,"name"=>"测试2"])或者update中指定条件字段id：$query->table("demo")->update([["id"=>1,"name"=>"测试1"],["id"=>2,"name"=>"测试2"]],"id")'));
                        }
                        foreach ($this->updates[0] as $update_field => $field_value) {
                            $update_key                      = ':' . md5($update_field);
                            $update_field                    = $this->parserFiled($update_field);
                            $this->bound_values[$update_key] = $field_value;
                            $updates                         .= "$update_field = $update_key,";
                        }
                    }
                } elseif ($this->single_updates) {
                    foreach ($this->single_updates as $update_field => $update_value) {
                        $update_field = $this->parserFiled($update_field);
                        $updates      .= "$update_field=$update_value,";
                    }
                } else {
                    throw new QueryException(__('无法解析更新数据！多记录更新数据：%1，单记录更新数据：%2', [var_export($this->updates, true), var_export($this->single_updates, true)]));
                }
                $updates = rtrim($updates, ',');

                $sql = "UPDATE {$this->table} {$this->table_alias} SET {$updates} {$wheres} {$this->additional_sql} ";
                break;
            case 'find':
            case 'select':
            default:
                $sql = "SELECT {$this->fields} FROM {$this->table} {$this->table_alias} {$joins} {$wheres} {$this->group_by} {$this->having} {$order} {$this->additional_sql} {$this->limit}";
                break;
        };
        # 预置sql
        $this->PDOStatement = $this->connection->getLink()->prepare($sql);
        $this->sql          = $sql;
    }

    /**
     * @DESC          # 解析数组键
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/25 22:34
     * 参数区：
     *
     * @param string|array $field 解析数据：一维数组值 或者 二维数组值
     *
     * @return string|array
     */
    public function parserFiled(string|array &$field): string|array
    {
        if (is_array($field)) {
            foreach ($field as $field_key => $value) {
                unset($field[$field_key]);
                $field_key         = '`' . str_replace('.', '`.`', $field_key) . '`';
                $field[$field_key] = $value;
            }
        } else {
            if (is_string($field)) {
                $field = '`' . str_replace('.', '`.`', $field) . '`';
            }
        }
        return $field;
    }

    /**
     * @DESC          # 异常函数
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/23 21:28
     * 参数区：
     *
     * @param $words
     *
     * @throws DbException
     */
    protected function exceptionHandle($words)
    {
        if (DEV && DEBUG) {
            echo '<pre>';
            var_dump(debug_backtrace());
        }
        throw new DbException($words);
    }
}
