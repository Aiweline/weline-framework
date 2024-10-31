<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Database\Connection\Api\Sql;

use PDO;
use PDOStatement;
use Weline\Framework\App\Env;
use Weline\Framework\App\Exception;
use Weline\Framework\Database\Exception\DbException;
use Weline\Framework\Manager\ObjectManager;

abstract class Query implements QueryInterface
{
    use SqlTrait;

    // 联合主键 设置联合主键可以提升查询效率
    public array $_unit_primary_keys = [];
    // 联合索引最左原则，提升查询效率
    public array $_index_sort_keys = [];

    public string $identity_field = 'id';
    public string $table = '';
    public string $table_alias = 'main_table';
    public array $insert = [];
    public array $insert_update_fields = [];
    public array $insert_need_fields = [];
    public array $insert_update_where_fields = [];
    public array $joins = [];
    public string $fields = '*';
    public array $single_updates = [];
    public array $updates = [];
    public array $wheres = [];
    public array $bound_values = [];
    public string $limit = '';
    public array $order = [];
    public string $group_by = '';
    public string $having = '';

    protected ?PDOStatement $PDOStatement = null;
    public string $sql = '';
    public string $additional_sql = '';

    public string $fetch_type = '';

    public array $pagination = ['page' => 1, 'pageSize' => 20, 'totalSize' => 0, 'lastPage' => 0];

    public string $backup_file = '';
    public bool $batch = false;


    public function identity(string $field): QueryInterface
    {
        $this->identity_field = $field;
        return $this;
    }

    public function table(string $table_name): QueryInterface
    {
        $this->table = $this->getTable($table_name);
        return $this;
    }

    public function insert(array $data, array|string $update_where_fields = [], string $update_fields = '', bool $ignore_primary_key = false): QueryInterface
    {
        if (empty($data)) {
            throw new DbException('插入数据不能为空！');
        }
        # 要更新的字段
        if ($update_fields) {
            if (is_string($update_fields)) {
                $this->insert_update_fields = explode(',', $update_fields);
            } else {
                $this->insert_update_fields = $update_fields;
            }
        }
        # 更新依据条件
        if(is_string($update_where_fields) and $update_where_fields){
            $update_where_fields = explode(',', $update_where_fields);
        }
        if(is_array($update_where_fields)){
            $this->insert_update_where_fields = $update_where_fields;
        }
        # 如果没有忽略主键，则需要添加主键
        if(!$ignore_primary_key){
            if(!in_array($this->identity_field,$this->insert_update_where_fields)){
                $this->insert_update_where_fields[] = $this->identity_field;
            }
            foreach ($this->_unit_primary_keys as $unit_primary_key) {
                if(!in_array($unit_primary_key,$this->insert_update_where_fields)){
                    $this->insert_update_where_fields[] = $unit_primary_key;
                }
            }
            # 倒序
            $this->insert_update_where_fields = array_reverse($this->insert_update_where_fields);
        }
        # 如果要更新的字段为空
        # 插入数据
        if (is_string(array_key_first($data))) {
            $this->insert['origin'][] = $data;
        } else {
            $this->insert['origin'] = $data;
        }
        # 计算是否存在多条语句
        if(count($this->insert['origin']) > 1){
            $this->batch = true;
        }

        if (count($this->insert)) {
            # 除去主键的联合查询字段
            $insert_have_not_identity_fields = $this->insert_update_where_fields;
            foreach ($insert_have_not_identity_fields as $insert_have_not_identity_field_key=>$insert_have_not_identity_field) {
                if($insert_have_not_identity_field == $this->identity_field){
                    unset($insert_have_not_identity_fields[$insert_have_not_identity_field_key]);
                    break;
                }
            }
            # 总需要的字段
            $insert_need_fields =  array_merge($this->_unit_primary_keys,$this->insert_update_where_fields);
            foreach ($insert_need_fields as $insert_need_field_key=>$insert_need_field) {
                if($insert_need_field == $this->identity_field){
                    unset($insert_need_fields[$insert_need_field_key]);
                    break;
                }
            }
            $this->insert_need_fields = $insert_need_fields;
            # 区分更新或者插入
            foreach ($this->insert['origin'] as $item) {
                # 检测个数据是否有需要更新的字段以及更新依据字段的字段数据
                $item_fields = array_keys($item);
                foreach ($insert_need_fields as $insert_need_field) {
                    if(!in_array($insert_need_field,$item_fields)){
                        throw new Exception(__('插入数据和更新依据字段不匹配，请检查! 所需字段：%1，实际字段: %2',[implode(',',$insert_need_fields),implode(',',$item_fields)]));
                    }
                }
                # 检测要更新的字段数据格式是否正确
                if(!empty($this->insert_update_fields)){
                    foreach ($this->insert_update_fields as $insert_update_field) {
                        if(!in_array($insert_update_field,$item_fields)){
                            throw new Exception(__('检测打算要更新依据字段不匹配，请检查! 打算更新字段：%1，实际字段: %2',[implode(',',$this->insert_update_fields),implode(',',$item_fields)]));
                        }
                    }
                }
                # 如果主键字段为空,并且没有更新条件时，为单纯的插入数据
                if(!$insert_have_not_identity_fields){
                    if(empty($item[$this->identity_field])){
                        $this->insert['insert'][] = $item;
                        continue;
                    }
                }
                $this->insert['i_o_u'][] = $item;
            }
        }

        # 获取字段
        $fields = '(';
        $first_insert = $this->insert['insert'][0]??[];
        if(empty($first_insert)){
            $first_insert = $this->insert['origin'][0]??[];
        }
        $special_fields = [
            'order',
            'key',
            'table',
            'fields',
        ];
        if(!empty($first_insert)){
            $fields_keys = array_keys($first_insert);
            foreach ($fields_keys as &$fields_key) {
                if(in_array($fields_key,$special_fields)){
                    $fields_key = '`' . $fields_key . '`';
                }
            }
            $fields .= implode(',', $fields_keys);
        }else{
            foreach ($this->insert_need_fields as &$fields_key) {
                if(in_array($fields_key,$special_fields)){
                    $fields_key = '`' . $fields_key . '`';
                }
            }
            $fields .= implode(',', $this->insert_need_fields);
        }
        $fields           = rtrim($fields, ',') . ')';
        $origin_fields    = $this->fields;
        $this->fields     = $fields;
        $this->fetch_type = __FUNCTION__;
        $this->prepareSql(__FUNCTION__);
        $this->fields = $origin_fields;
        return $this;
    }

    public function update(array|string $field, int|string $value_or_condition_field = 'id'): QueryInterface
    {
        if (empty($field)) {
            throw new DbException(__('更新异常，不可更新空数据！'));
        }
        # 单条记录更新
        if (is_string($field)) {
            $this->single_updates[$field] = $value_or_condition_field;
        } else {
            // 设置数据更新依赖条件主键
            if ($this->identity_field !== $value_or_condition_field) {
                $this->identity_field = $value_or_condition_field;
            }
            if (is_string(array_key_first($field))) {
                $this->updates[] = $field;
            } else {
                $this->updates = $field;
            }
        }
        $this->fetch_type = __FUNCTION__;
        $this->prepareSql(__FUNCTION__);
        return $this;
    }

    public function alias(string $table_alias_name): QueryInterface
    {
        $this->table_alias = $table_alias_name;
        return $this;
    }

    public function join(string $table, string $condition, string $type = 'left'): QueryInterface
    {
        if (1 === count(func_get_args())) {
            $type = 'inner';
        }
        $this->joins[] = [$table, $condition, $type];
        return $this;
    }

    public function fields(string $fields): QueryInterface
    {
        if ($this->fields === '*' || $this->fields === $this->table_alias . '.*' || 'main_table.*' === $this->fields) {
            $this->fields = $fields;
        } else {
            $this->fields = $fields . ',' . $this->fields;
            $fields       = explode(',', $this->fields);
            $fields       = array_unique($fields);
            foreach ($fields as &$field) {
                $field = self::parserFiled($field);
            }
            $this->fields = implode(',', $fields);
        }
        return $this;
    }

    public function where(array|string $field, mixed $value = null, string $condition = '=', string $where_logic = 'AND', string $array_where_logic_type = 'AND'): QueryInterface
    {
        $where_logic            = trim(strtoupper($where_logic));
        $condition              = trim(strtoupper($condition));
        $array_where_logic_type = trim(strtoupper($array_where_logic_type));
        if (is_array($field)) {
            foreach ($field as $f_key => $where_array) {
                # 处理字段，都要加`
                $f_key = self::parserFiled($f_key);
                if (!is_array($where_array)) {
                    $value          = $where_array;
                    $where_array    = [];
                    $where_array[0] = $f_key;
                    $where_array[1] = '=';
                    $where_array[2] = $value;
                    $where_array[3] = $array_where_logic_type;
                } elseif (2 === count($where_array)) {# 处理两个元素数组
                    $where_array[2] = $where_array[1];
                    $where_array[1] = '=';
                }

                # 检测条件数组 下角标 必须为数字
                $this->checkWhereArray($where_array, $f_key);
                # 检测条件数组 检测第二个元素必须是限定的 条件操作符
                $this->checkConditionString($where_array);
                $this->wheres[] = $where_array;
            }
        } else {
            if (is_array($value)) {
                if ($condition === 'IN' || $condition === 'NOT IN') {
                    if (empty($value)) {
                        throw new Exception(__('IN 条件无法匹配空值数组。数组值：[]'));
                    }
                    $where_array = [$field, $condition, $value, $where_logic];
                    # 检测条件数组 下角标 必须为数字
                    $this->checkWhereArray($where_array, 0);
                    # 检测条件数组 检测第二个元素必须是限定的 条件操作符
                    $this->checkConditionString($where_array);
                    $this->wheres[] = $where_array;
                } else {
                    $last_key = array_key_last($value);
                    foreach ($value as $kv => $item) {
                        if ($last_key === $kv) {
                            $array_where_logic_type = $where_logic;
                        }
                        # 判断字段是否为同一个
                        $where_array = [$field, $condition, $item, $array_where_logic_type];
                        # 检测条件数组 下角标 必须为数字
                        $this->checkWhereArray($where_array, 0);
                        # 检测条件数组 检测第二个元素必须是限定的 条件操作符
                        $this->checkConditionString($where_array);
                        $this->wheres[] = $where_array;
                    }
                }
            } else {
                $where_array = [self::parserFiled($field), $condition, $value, $where_logic];
                # 检测条件数组 下角标 必须为数字
                $this->checkWhereArray($where_array, 0);
                # 检测条件数组 检测第二个元素必须是限定的 条件操作符
                $this->checkConditionString($where_array);
                $this->wheres[] = $where_array;
            }
        }
        return $this;
    }

    public function limit($size, $offset = 0): QueryInterface
    {
        $this->limit = " LIMIT $offset,$size";
        return $this;
    }

    public function page(int $page = 1, int $pageSize = 20): QueryInterface
    {
        $offset = 0;
        if (1 < $page) {
            $offset = $pageSize * ($page - 1) /*+ 1*/
            ;
        }
        $this->limit              = " LIMIT $offset,$pageSize";
        $this->pagination['page'] = $page;
        return $this;
    }

    public function pagination(int $page = 1, int $pageSize = 20, array $params = []): QueryInterface
    {
        $this->pagination['page']     = $page;
        $this->pagination['pageSize'] = $pageSize;
        if ($params) {
            $this->pagination = array_merge($this->pagination, $params);
        }
        $this->pagination['params'] = $params;
        $this->page(intval($this->pagination['page']), $pageSize);
        $query                         = clone $this;
        $total                         = $query->total();
        $this->pagination['totalSize'] = $total;
        $lastPage                      = intval($total / $pageSize);
        if ($total % $pageSize) {
            $lastPage += 1;
        }
        $this->pagination['lastPage'] = $lastPage;
        return $this;
    }

    public function order(string $field, string $sort = 'DESC'): QueryInterface
    {
        $field = $this->parserFiled($field);
        if('key'==strtolower($field) || 'value'==strtolower($field) || 'key'==strtolower($sort)){
            $field = '`'.$field.'`';
        }
        $this->order[$field] = $sort;
        return $this;
    }

    public function group(string $fields): QueryInterface
    {
        $this->group_by = 'group by ' . $fields;
        return $this;
    }

    public function having(string $having): QueryInterface
    {
        $this->having = 'having ' . $having;
        return $this;
    }

    public function find(): QueryInterface
    {
        $this->limit(1, 0);
        $this->fetch_type = __FUNCTION__;
        $this->prepareSql(__FUNCTION__);
        return $this;
    }

    public function total(string $field = '*', string $alias = 'total'): int
    {
        $this->limit(1, 0);
        $this->fetch_type = 'find';
        $this->fields     = "count({$field}) as `{$alias}`";
        $this->prepareSql('find');
        //        p($this->getLastSql());
        $result = $this->fetch();
        if (isset($result[$alias])) {
            $result = $result[$alias];
        }
        return intval($result);
    }

    public function select(string $fields = ''): QueryInterface
    {
        if ($fields) {
            $this->fields($fields);
        }
        $this->fetch_type = __FUNCTION__;
        $this->prepareSql(__FUNCTION__);
        return $this;
    }

    public function delete(): QueryInterface
    {
        $this->fetch_type = __FUNCTION__;
        $this->prepareSql(__FUNCTION__);
        return $this;
    }

    public function query(string $sql): QueryInterface
    {
        $sql = self::formatSql($sql);
        $this->reset();
        $this->sql          = $sql;
        $this->fetch_type   = __FUNCTION__;
        $this->PDOStatement = $this->getLink()->prepare($sql);
        return $this;
    }

    public function additional(string $additional_sql): QueryInterface
    {
        $this->additional_sql = $additional_sql;
        return $this;
    }

    public function fetch(string $model_class = ''): mixed
    {
        if($this->batch and $this->fetch_type == 'insert'){
            $origin_data      = $this->getLink()->exec($this->getSql());
            if($origin_data === false){
                $result = false;
            }else{
                $result = $this->getLink()->lastInsertId();
            }
            $origin_data = [];
        }else{
            $result      = $this->PDOStatement->execute($this->bound_values);
            $origin_data = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
        }
        $this->batch = false;
        $data = [];
        if ($model_class) {
            foreach ($origin_data as $origin_datum) {
                $data[] = ObjectManager::make($model_class, ['data' => $origin_datum], '__construct');
            }
        } else {
            $data = $origin_data;
        }
        switch ($this->fetch_type) {
            case 'find':
                $result = array_shift($data);
                if ($model_class && empty($result)) {
                    $result = ObjectManager::make($model_class, ['data' => []], '__construct');
                }
                break;
            case 'insert':
                $result = $this->getLink()->lastInsertId();
                break;
            case 'query':
            case 'select':
                $result = $data;
                break;
            case 'delete':
            case 'update':
                break;
            default:
                throw new Exception(__('错误的获取类型。fetch之前必须有操作函数，操作函数包含（find,update,delete,select,query,insert,find）函数。'));
                break;
        }
        $this->fetch_type = '';
        if (Env::get('db_log.enabled') or DEBUG) {
            $file = Env::get('db_log.file');
            $data = [
                'prepare_sql' => $this->getPrepareSql(false),
                'sql' => $this->getLastSql(false),
                'data' => $this->bound_values,
                'result' => $origin_data
            ];
            Env::log($file, json_encode($data));
        }
        //        $this->clear();
        $this->clearQuery();
        //        $this->reset();
        return $result;
    }

    public function fetchOrigin(): mixed
    {
        return $this->fetch();
    }


    public function clear(string $type = ''): QueryInterface
    {
        if ($type) {
            $attr_var_name = $type;
            if (DEV && !isset(self::init_vars[$attr_var_name])) {
                $this->exceptionHandle(__('不支持的清理类型：%1 支持的初始化类型：%2', [$attr_var_name, var_export(self::init_vars, true)]));
            }
            $this->$attr_var_name = self::init_vars[$attr_var_name];
        } else {
            $this->reset();
        }
        $this->_unit_primary_keys = [];
        $this->batch = false;
        return $this;
    }


    public function clearQuery(string $type = ''): QueryInterface
    {
        if ($type) {
            $attr_var_name = $type;
            if (DEV && !isset(self::init_vars[$attr_var_name])) {
                $this->exceptionHandle(__('不支持的清理类型：%1 支持的初始化类型：%2', [$attr_var_name, var_export(self::init_vars, true)]));
            }
            $this->$attr_var_name = self::init_vars[$attr_var_name];
        } else {
            foreach (self::query_vars as $query_field => $query_var) {
                $this->$query_field = $query_var;
            }
        }
        return $this;
    }

    public function reset(): QueryInterface
    {
        foreach (self::init_vars as $init_field => $init_var) {
            $this->$init_field = $init_var;
        }
        $this->PDOStatement = null;
        $this->batch = false;
        return $this;
    }

    public function beginTransaction(): void
    {
        $this->getLink()->beginTransaction();
    }

    public function rollBack(): void
    {
        $this->getLink()->rollBack();
    }

    public function commit(): void
    {
        $this->getLink()->commit();
    }

    /**
     * 归档数据
     *
     * @param string $period ['all'=>'全部','today'=>'今天','yesterday'=>'昨天','current_week'=>'这周','near_week'=>'最近一周','last_week'=>'上周','near_month'=>'近三十天','current_month'=>'本月','last_month'=>'上一月','quarter'=>'本季度','last_quarter'=>'上个季度','current_year'=>'今年','last_year'=>'上一年']
     * @param string $field [默认按照'create_time'字段归档，可指定归档字段]
     *
     * @return $this
     * @throws Exception
     */
    public function period(string $period, string $field = 'create_time'): static
    {
        if (!is_int(strpos($field, '.'))) {
            $field = $this->table_alias . '.' . $field;
        }
        switch ($period) {
            case 'all':
                break;
            case 'today':
                #今天
                $this->where("TO_DAYS({$field})=TO_DAYS(NOW())");
                break;
            case 'yesterday':
                #昨天
                $this->where("DATE({$field}) = DATE(CURDATE()-1)");
                break;
            case 'current_week':
                #查询当前这周的数据
                $this->where("YEARWEEK(DATE_FORMAT({$field},'%Y-%m-%d')) = YEARWEEK(NOW())");
                break;
            case 'near_week':
                #近7天
                $this->where("DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= DATE({$field})");
                break;
            case 'last_week':
                #查询上周的数据
                $this->where("YEARWEEK(DATE_FORMAT({$field},'%Y-%m-%d')) =YEARWEEK(NOW())-1");
                break;
            case 'near_month':
                #近30天
                $this->where("DATE_SUB(CURDATE(), INTERVAL 30 DAY) <= DATE({$field})");
                break;
            case 'current_month':
                # 本月
                $this->where("DATE_FORMAT({$field},'%Y%m') =DATE_FORMAT(CURDATE(),'%Y%m')");
                break;
            case 'last_month':
                #上一月
                $this->where("PERIOD_DIFF(DATE_FORMAT( NOW(),'%Y%m'),DATE_FORMAT({$field},'%Y%m')) =1");
                break;
            case 'quarter':
                #查询本季度数据
                $this->where("QUARTER({$field})=QUARTER(NOW())");
                break;
            case 'last_quarter':
                #查询上季度数据
                $this->where("QUARTER({$field})=QUARTER(DATE_SUB(NOW(),INTERVAL 1 QUARTER))");
                break;
            case 'current_year':
                #查询本年数据
                $this->where("YEAR({$field})=YEAR(NOW())");
                break;
            case 'last_year':
                #查询上年数据
                $this->where("YEAR({$field})=YEAR(DATE_SUB(NOW(),INTERVAL 1 YEAR))");
                break;
            default:
        }
        return $this;
    }

    public function getSql(bool $format = false): string
    {
        $real_sql = $this->sql;
        foreach ($this->bound_values as $where_key => $wheres_value) {
            $wheres_value = $this->getLink()->quote($wheres_value);
            $real_sql    = str_replace($where_key, $wheres_value, $real_sql);
        }
        if ($format) {
            return \SqlFormatter::format($real_sql);
        }
        return $real_sql;
    }

    public function truncate(string $backup_file = '', string $table = ''): static
    {
        if (empty($table)) {
            $table = $this->table;
        }
        if (empty($table)) {
            throw new Exception(__('请先指定要操作的表，表名不能为空!'));
        }
        $this->backup($backup_file, $table);
        # 清理表
        $PDOStatement = $this->getLink()->prepare("TRUNCATE TABLE $table");
        $PDOStatement->execute();
        return $this;
    }

    public function backup(string $backup_file = '', string $table = ''): static
    {
        if (empty($table)) {
            $table = $this->table;
        }
        if (empty($table)) {
            throw new Exception(__('请先指定要操作的表，表名不能为空!'));
        }
        // 获取表的创建语句
        $PDOStatement = $this->getLink()->prepare("SHOW CREATE TABLE $table");
        $PDOStatement->execute();
        $createTableResult = $PDOStatement->fetchAll(PDO::FETCH_ASSOC);
        $createTableSql    = $createTableResult[0]['Create Table'];
        $createTableSql    = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $createTableSql);
        // 定义备份文件路径和名称
        if (empty($backup_file)) {
            $originTable = str_replace('`', '', $table);
            $originTable = explode('.', $originTable) ?: [$table];
            $originTable = end($originTable);
            $backupFile  = Env::backup_dir . 'db' . DS . $table . DS . $originTable . '_' . date('Y-m-d H:i:s') . '.sql';
        } else {
            if (!str_starts_with($backup_file, BP)) {
                $backupFile = BP . $backup_file;
            } else {
                $backupFile = $backup_file;
            }
        }
        if (!is_dir(dirname($backupFile))) {
            mkdir(dirname($backupFile), 0777, true);
        }
        // 将表的创建语句写入备份文件
        $backupFile        = str_replace('\\', DS, $backupFile);
        $backupFile        = str_replace('/', DS, $backupFile);
        $backupFile        = str_replace('//', DS, $backupFile);
        $this->backup_file = $backupFile;
        $file              = fopen($backupFile, 'w');
        fwrite($file, "-- $table 建表语句" . PHP_EOL);
        fwrite($file, $createTableSql . ';' . PHP_EOL);
        // 获取表的数据并写入备份文件
        $PDOStatement = $this->getLink()->prepare("SELECT * FROM $table");
        $PDOStatement->execute();
        $results = $PDOStatement->fetchAll(PDO::FETCH_ASSOC);
        fwrite($file, PHP_EOL);
        fwrite($file, "-- $table 数据 " . PHP_EOL);
        foreach ($results as $result) {
            # 单引号转义
            foreach ($result as $key => $item) {
                if (is_string($item)) {
                    $result[$key] = str_replace("'", "\\'", $item);
                }
            }
            $values = implode("','", array_values($result));
            fwrite($file, "INSERT INTO $table VALUES ('$values');" . PHP_EOL);
        }
        // 关闭备份文件和数据库连接
        fclose($file);
        return $this;
    }
}