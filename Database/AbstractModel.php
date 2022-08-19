<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Database;

use Weline\Framework\App\Exception;
use Weline\Framework\Cache\CacheInterface;
use Weline\Framework\Database\Api\Connection\QueryInterface;
use Weline\Framework\Database\Cache\DbModelCache;
use Weline\Framework\Database\DbManager\ConfigProvider;
use Weline\Framework\Database\Exception\DbException;
use Weline\Framework\Database\Exception\ModelException;
use Weline\Framework\DataObject\DataObject;
use Weline\Framework\Event\EventsManager;
use Weline\Framework\Exception\Core;
use Weline\Framework\Http\Request;
use Weline\Framework\Manager\ObjectManager;

/**
 * Class AbstractModel
 * @method AbstractModel|QueryInterface identity(string $field)
 * @method AbstractModel|QueryInterface table(string $table_name)
 * @method AbstractModel|QueryInterface update(array $data, string $condition_field = 'id')
 * @method AbstractModel|QueryInterface fields(string $fields)
 * @method AbstractModel|QueryInterface join(string $table, string $condition, string $type = 'left')
 * @method AbstractModel|QueryInterface where(array|string $field, mixed $value = null, string $con = '=', string $logic = 'AND')
 * @method AbstractModel|QueryInterface limit(int $size, int $offset = 0)
 * @method AbstractModel|QueryInterface page(int $page = 1, int $pageSize = 20)
 * @method AbstractModel|QueryInterface order(string $fields, string $sort = 'ASC')
 * @method AbstractModel|QueryInterface find()
 * @method int total()
 * @method AbstractModel|QueryInterface select()
 * @method AbstractModel|QueryInterface insert(array $data)
 * @method AbstractModel|QueryInterface query(string $sql)
 * @method AbstractModel|QueryInterface getIndexFields()
 * @method AbstractModel|QueryInterface reindex(string $table, array $fields = [])
 * @method AbstractModel|QueryInterface additional(string $additional_sql)
 * @method AbstractModel|QueryInterface clear(string $type = '')
 * @method AbstractModel|QueryInterface clearQuery(string $type = '')
 *
 * @method AbstractModel|QueryInterface fetch()
 * @method AbstractModel|QueryInterface reset()
 * @method AbstractModel|QueryInterface beginTransaction()
 * @method AbstractModel|QueryInterface rollBack()
 * @method AbstractModel|QueryInterface commit()
 * @method AbstractModel|QueryInterface getLastSql()
 * @method AbstractModel|QueryInterface getPrepareSql()
 * @package Weline\Framework\Database
 */
abstract class AbstractModel extends DataObject
{
    # 主数据库连接使用标志
    public const framework_db_master = false;
    public const table               = '';
    public const primary_key         = '';
    # 索引名
    public const indexer = '';
    /**
     * 对象属性
     *
     * @var array
     */
    public const fields_ID          = 'id';
    public const fields_CREATE_TIME = 'create_time';

    public const fields_UPDATE_TIME = 'update_time';

    # 模块名称
    public string $module_name = '';
    public string $table = '';
    public string $alias = '';
    public string $origin_table_name = '';
    private ?ConnectionFactory $connection = null;
    public string $_suffix = '';
    public string $_primary_key = '';
    public string $_primary_key_default = 'id';
    public array $_fields = [];
    public array $_joins = [];
    private array $_model_fields = [];
    private array $_model_fields_data = [];

    private bool $force_check_flag = false;

    private DbManager|null $dbManager = null;
    private ?QueryInterface $_bind_query = null;
    private ?QueryInterface $current_query = null;
    public ?CacheInterface $_cache = null;
    private array $_fetch_data = [];
    public array $items = [];
    private mixed $_query_data = null;
    private array $pagination = ['page' => 1, 'pageSize' => 20, 'totalSize' => 0, 'lastPage' => 0];

    # Flag
    private bool $use_cache = false;

//    public function __sleep()
//    {
//        return array('table', 'origin_table_name', 'items');
//    }

    public function __wakeup()
    {
        $this->__init();
    }

    /**
     * @DESC         |初始化连接、缓存、表前缀 读取模型自身表名字等
     *
     * 参数区：
     *
     * @throws \ReflectionException
     * @throws \Weline\Framework\App\Exception
     */
    public function __init()
    {
        # 如果初始化有数据
        if ($this->getData()) {
            $this->fetch_after();
        }
        # 重置查询
        if (!isset($this->_cache)) {
            $this->_cache = ObjectManager::getInstance(DbModelCache::class . 'Factory');
        }
        # dbManager
        if (empty($this->dbManager)) {
            $this->dbManager = ObjectManager::getInstance(DbManager::class);
        }
        # 类属性
        if (empty($this->connection)) {
            $this->connection = $this->getConnection();
        }
        if (empty($this->_suffix)) {
            $this->_suffix = $this->getConnection()->getConfigProvider()->getPrefix() ?: '';
        }
        # 模型属性
        if (!empty($this::table)) {
            $this->table = $this::table;
        }
        if (empty($this->table)) {
            $this->table = $this->processTable();
        }
        if (!empty($this::primary_key)) {
            $this->_primary_key = $this::primary_key;
        }
        if (empty($this->_primary_key)) {
            if ($this::fields_ID !== $this->_primary_key_default) {
                $this->_primary_key = $this::fields_ID;
            } else {
                $this->_primary_key = $this->_primary_key_default;
            }
        }
        # 字段解析
        if (empty($this->_fields)) {
            $this->_fields = $this->getModelFields();
        }
//        # 动态属性字段
//        if ($this->getData()) {
//            foreach ($this->getData() as $key => $data) {
//                if (is_string($key)) {
//                    $field_name = 'model_field_'.$key;
//                    $this->$field_name = $data;
//                }
//            }
//        }else{
//            foreach ($this->_fields as $key => $filed) {
//                if (is_string($filed)) {
//                    $field_name = 'model_field_'.$filed;
//                    $this->$field_name = '';
//                }
//            }
//        }
    }

    public function getConnection()
    {
        # 如果已经有链接直接返回
        if (!empty($this->connection)) {
            return $this->connection;
        }
        # 使用主数据库
        if ($this::framework_db_master) {
            $this->connection = ObjectManager::getInstance(DbManager::class . 'Factory');
        } else {
            # 检测app应用级别的数据库配置信息 读链接 和  写链接
            $filename = ObjectManager::getReflectionInstance($this)->getFileName();
            # 去除相对的模组路径
            if (str_starts_with($filename, APP_CODE_PATH)) {
                $filename = substr($filename, strlen(APP_CODE_PATH));
                $this->processModelDbConnection($filename);
            } elseif (str_starts_with($filename, VENDOR_PATH)) {
                $filename = substr($filename, strlen(VENDOR_PATH));
                $this->processModelDbConnection($filename);
            } else {
                $this->connection = ObjectManager::getInstance(DbManager::class . 'Factory');
            }
        }
        return $this->connection;
    }

    private function processModelDbConnection(string $filename)
    {
        # 读取模组名称
        $file_name_arr = explode(DS, $filename);
        # 模组目录
        $module_name_path  = $file_name_arr[0] . DS . $file_name_arr[1];
        $this->module_name = str_replace(DS, '_', $module_name_path);
        # 应用数据库配置
        $db_config_file = APP_CODE_PATH . $module_name_path . DS . 'etc' . DS . 'db.php';
        if (is_file($db_config_file)) {
            $db_config = include $db_config_file;
            # 确认是否有主库配置
            if (!isset($db_config['master'])) {
                throw new DbException(__('请配置主数据库配置信息,或者主数据库配置信息设置错误') . (DEV ? '(' . $db_config_file . ')' : ''));
            }
            # TODO 子应用数据库配置
            $this->connection = $this->dbManager->create(
                $this->module_name,
                new ConfigProvider($db_config)
            );
        } else {
            $this->connection = ObjectManager::getInstance(DbManager::class . 'Factory');
        }
    }

    public function getIdField(): string
    {
        return $this->_primary_key;
    }

    /**
     * @DESC          # 处理表名 存在表名则不处理
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/26 20:32
     * 参数区：
     * @return string
     */
    protected function processTable(): string
    {
        if (!$this->table) {
            $class_file_name_arr = explode('\\', $this::class);
//            $module_table_pre     = array_shift($class_file_name_arr).'_'.array_shift($class_file_name_arr);
            $class_file_name = array_pop($class_file_name_arr);
            if (str_ends_with($class_file_name, 'Model')) {
                $class_file_name = substr($class_file_name, 0, strpos($class_file_name, 'Model'));
            }
            $table_name              = $class_file_name;
            $this->origin_table_name = $this->_suffix . strtolower(implode('_', w_split_by_capital(lcfirst($table_name))));
            $this->table             = "`{$this->getConnection()->getConfigProvider()->getDatabase()}`.`{$this->origin_table_name}`";
        }
        $this->origin_table_name = empty($this->origin_table_name) ? $this->table : $this->origin_table_name;
        return $this->table;
    }

    public function getData(string $key = '', $index = null): mixed
    {
        if (empty($key)) {
            if ($data = parent::getData($key, $index)) {
                return $data;
            }
            return $this->getFetchData();
        }
        return parent::getData($key, $index);
    }

    public function getOriginData(string $key = '', $index = null): mixed
    {
        if (empty($key)) {
            $dataObjectData = $this->getData();
            $data           = [];
            if (is_int(array_key_first($dataObjectData)) && ($dataObjectData[0] instanceof DataObject)) {
                foreach ($dataObjectData as $datum) {
                    $data[] = $datum->getData();
                }
                return $data;
            }
            if (empty($data)) {
                if (is_int(array_key_first($this->getFetchData())) && ($this->getFetchData()[0] instanceof DataObject)) {
                    foreach ($this->getFetchData() as $datum) {
                        $data[] = $datum->getData();
                    }
                }
                return $data;
            }
        }
        return parent::getData($key, $index);
    }

    /**
     * @DESC          # 读取表名
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/3 20:14
     * 参数区：
     *
     * @param string $table
     *
     * @return string
     */
    public function getTable(string $table = ''): string
    {
        if (empty($table)) {
            return $this->processTable();
        }
        return $this->getConnection()->getConfigProvider()->getPrefix() . $table;
    }

    public function alias(string $alias): static
    {
        $this->alias = $alias;
        return $this;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @DESC          # 读取原始表名
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/3 20:14
     * 参数区：
     * @return string
     */
    public function getOriginTableName(): string
    {
        $this->processTable();
        return $this->origin_table_name;
    }

    /**
     * @DESC         |获取数据库基类
     *
     * 参数区：
     *
     * @param bool $keep_condition
     *
     * @return QueryInterface
     * @throws Exception
     * @throws \ReflectionException
     */
    public function getQuery(bool $keep_condition = true): QueryInterface
    {
        # 如果绑定了查询
        if ($this->_bind_query) {
            if (!$keep_condition) {
                $this->_bind_query->clearQuery()->table($this->getOriginTableName())->identity($this->_primary_key);
            } else {
                $this->_bind_query->table($this->getOriginTableName())->identity($this->_primary_key);
            }
            $this->_bind_query->joins = $this->_joins;
            return $this->_bind_query;
        }
        if ($this->current_query) {
            if (!$keep_condition) {
                $this->current_query->clearQuery()->table($this->getOriginTableName())->identity($this->_primary_key);
            } else {
                $this->current_query->table($this->getOriginTableName())->identity($this->_primary_key);
            }
            return $this->current_query;
        }
        # 区分是否保持查询
        $this->current_query = $this->getConnection()->getQuery()->clearQuery()->table($this->getOriginTableName())->identity($this->_primary_key);
        return $this->current_query;
    }

    /**
     * @DESC         |获取数据库基类
     *
     * 参数区：
     *
     * @param QueryInterface $query
     *
     * @return AbstractModel
     */
    public function setQuery(QueryInterface $query): static
    {
        # 如果绑定了查询
        if ($this->_bind_query) {
            $this->_bind_query = $query;
        } elseif ($this->current_query) {
            $this->current_query = $query;
        }
        return $this;
    }

    /**
     * @DESC         |读取当前模型的数据
     * 如果只给一个参数则当做读取主键的值
     * 如果给定一个字段，那么必填第二个参数，当做这个字段的值
     *
     * 参数区：
     *
     * @param int|string $field_or_pk_value 字段或者主键的值
     * @param null       $value             字段的值，只读取主键就不填
     *
     * @return mixed
     * @throws \ReflectionException
     * @throws \Weline\Framework\Exception\Core
     */
    public function load(int|string $field_or_pk_value, $value = null): AbstractModel
    {
        // 加载之前
        $this->load_before();
        // 清空之前的数据
        $this->clearDataObject();
        // load之前事件
        $this->getEvenManager()->dispatch($this->getOriginTableName() . '_model_load_before', ['model' => $this]);
        if (is_null($value)) {
            $data = $this->getQuery()->where($this->_primary_key, $field_or_pk_value)->find()->fetch();
        } else {
            $data = $this->getQuery()->where($field_or_pk_value, $value)->find()->fetch();
        }
        if (is_array($data)) {
            $this->setModelData($data);
        }
        // load之之后事件
        $this->getEvenManager()->dispatch($this->getOriginTableName() . '_model_load_after', ['model' => $this]);
        // 加载之后
        $this->load_after();
        # 触发fetch_after
        $this->fetch_after();
        $this->clearQuery();
        return $this;
    }
    /***************便捷查询 开始 *********************/
//    function all(): mixed
//    {
//        return $this->where(1)->select()->fetch();
//    }
    /***************便捷查询 结束 *********************/
    /**
     * @DESC         |载入前
     *
     * 参数区：
     */
    public function load_before()
    {
    }

    /**
     * @DESC         |载入后
     *
     * 参数区：
     */
    public function load_after()
    {
    }

    /**
     * @DESC         |保存方法
     *
     * 参数区：
     *
     * @param array       $data
     * @param string|null $sequence
     *
     * @return bool
     * @throws \Weline\Framework\Exception\Core
     * @throws \ReflectionException
     */
    public function save(array|bool|AbstractModel $data = [], string $sequence = null): bool
    {
        if (is_object($data)) {
            $data = $data->getModelData();
        }
        if (is_bool($data)) {
            $this->force_check_flag = $data;
        }
        if (is_array($data)) {
            $this->setModelData($data);
        }
        // 保存前
        $this->save_before();
        // save之前事件
        $this->getEvenManager()->dispatch($this->processTable() . '_model_save_before', ['model' => $this]);
        $this->getQuery()->beginTransaction();
        try {
            if ($this->getId()) {
                # 暂时解决主键已经存在且是字符串，无法新增的问题，强制检测主键是否存在
                if (!is_numeric($this->getId())) {
                    $this->force_check_flag = true;
                }
                # 是否强制检查
                if ($this->force_check_flag) {
                    $save_result = $this->getQuery()->insert($this->getModelData(), $this->getModelFields(true))->fetch();
                } else {
                    $save_result = $this->getQuery()->where($this->_primary_key, $this->getId())->update($this->getModelData())->fetch();
                }
            } else {
                $insert_data = $this->getModelData();
                unset($insert_data[$this->_primary_key]);
                if ($this->force_check_flag) {
                    $save_result = $this->getQuery()->insert($this->getModelData(), $this->getModelFields(true))->fetch();
                } else {
                    $save_result = $this->getQuery()->insert($insert_data)->fetch();
                }
                if (!$this->getId()) {
                    $this->setData($this->_primary_key, $save_result);
                }
            }
            $this->getQuery()->commit();
        } catch (\Exception $exception) {
            $this->getQuery()->rollBack();
            $msg = __('保存数据出错! ');
            $msg .= __('消息: %1', $exception->getMessage()) . PHP_EOL . __('预编译SQL: %1', $this->getQuery()->getPrepareSql()) . PHP_EOL . __('执行SQL: %1', $this->getQuery()->getLastSql());
            throw new ModelException($msg);
        }

        // save之后事件
        $this->getEvenManager()->dispatch($this->processTable() . '_model_save_after', ['model' => $this]);
        // 保存后
        $this->save_after();
        return $save_result;
    }

    public function save_before()
    {
    }

    public function save_after()
    {
    }

    /**
     * @DESC          # 【强制检测】 true:强制查询数据库，检测数据是否存在 不存在则插入记录 false:检测当前模型是否存在主键，存在则更新，不存在则插入
     *                # 【原因】 如果主键非ID自增键时，因为主键就是数据，无法检测，只能先查询后操作，遇到此类情况时使用此函数
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/14 22:49
     * 参数区：
     *
     * @param bool $force_check_flag
     *
     * @return AbstractModel
     */
    public function forceCheck(bool $force_check_flag = true): AbstractModel
    {
        $this->force_check_flag = $force_check_flag;
        return $this;
    }

    /**
     * @DESC          # 获取事件管理器
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/30 21:12
     * 参数区：
     * @return EventsManager
     * @throws Exception
     * @throws \ReflectionException
     */
    protected function getEvenManager(): EventsManager
    {
        return ObjectManager::getInstance(EventsManager::class);
    }

    /**
     * @DESC          # 删除 模型中 对应主键值的条目
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/30 21:08
     * 参数区：
     * @return $this
     * @throws Core
     * @throws Exception
     * @throws \ReflectionException
     */
    public function delete(): AbstractModel
    {
        // 加载之前
        $this->delete_before();
        // load之前事件
        $this->getEvenManager()->dispatch($this->processTable() . '_model_delete_before', ['model' => $this]);
        $this->getQuery()->where($this->_primary_key, $this->getId())->delete()->fetch();
        $this->clearData();
        // load之之后事件
        $this->getEvenManager()->dispatch($this->processTable() . '_model_delete_after', ['model' => $this]);
        // 加载之后
        $this->delete_after();
        return $this;
    }

    public function delete_before()
    {
    }

    public function delete_after()
    {
    }

    public function clearData(): static
    {
        $this->_fields     = [];
        $this->_joins      = [];
        $this->items       = [];
        $this->_bind_query = null;
        $this->clearQuery();
        $this->clearDataObject();
        $this->setFetchData([]);
        return $this;
    }

    /**
     * @DESC         |访问不存在的方法时，默认为查询
     *
     * 参数区：
     *
     * @param $method
     * @param $args
     *
     * @return array|bool|mixed|string|AbstractModel|null
     * @throws \Weline\Framework\Exception\Core
     * @throws \ReflectionException
     */
    public function __call($method, $args)
    {
        // 模型查询
        if (in_array($method, get_class_methods(QueryInterface::class))) {
            # 某些函数是不需要保持查询的
            if ($method == 'clearQuery') {
                $query = $this->getQuery(false);
            } else {
                $query = $this->getQuery();
            }
            if ($method == 'total') {
                return $query->$method(... $args);
            }
            # 注入选择的模型字段
            if ($method == 'fields') {
                $fields = explode(',', ...$args);
                foreach ($fields as &$field) {
                    if (is_int(strpos($field, '.'))) {
                        $fields_array = explode('.', $field);
                        $field        = array_pop($fields_array);
                    }
                    # 别名
                    if (is_int(strpos($field, 'as'))) {
                        $fields_array = explode('as', $field);
                        $field        = array_pop($fields_array);
                    }
                }
                $this->bindModelFields($fields);
            }
            # 非链式操作的Fetch
            $is_fetch = false;
            # 拦截fetch操作 注入返回的模型
            if ('fetch' === $method) {
                $args[]   = $this::class;
                $is_fetch = true;
            }

            $query_data = $query->$method(... $args);
            $this->setQueryData($query_data);
            if ('fetchOrigin' === $method) {
                return $query_data;
            }
            # 拦截fetch返回的数据注入模型
            if ($is_fetch) {
                $this->fetch_before();
                if (is_array($query_data)) {
                    $this->setFetchData($query_data);
                    $this->setData($query_data);
                } elseif (is_object($query_data)) {
                    /**@var AbstractModel $query_data */
                    $this->setFetchData($query_data->getData());
                    $this->setData($query_data->getData());
                } else {
                    $this->setFetchData([]);
                    $this->setData([]);
                }
                $this->fetch_after();
                $this->clearQuery();
                # 清除当前查询
                return $this;
            }
            $query_methods = [
                'getPrepareSql',
                'getLastSql',
            ];
            if (in_array($method, $query_methods)) {
                return $query_data;
            }
            if ($query_data instanceof QueryInterface) {
                $this->setQuery($query_data);
            }
            return $this;
        }
        /**
         * 重载方法
         */
        return parent::__call($method, $args);
    }

    /**
     * 归档数据
     *
     * @param string $period ['all'=>'全部','today'=>'今天','yesterday'=>'昨天','current_week'=>'这周','near_week'=>'最近一周','last_week'=>'上周','near_month'=>'近三十天','current_month'=>'本月','last_month'=>'上一月','quarter'=>'本季度','last_quarter'=>'上个季度','current_year'=>'今年','last_year'=>'上一年']
     * @param string $field
     *
     * @return $this
     */
    public function period(string $period, string $field = 'main_table.create_time'): static
    {
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

    protected function setQueryData($query_data)
    {
        $this->_query_data = $query_data;
        return $this;
    }

    public function getQueryData()
    {
        return $this->_query_data;
    }

    public function fetch_before()
    {
    }

    public function fetch_after()
    {
    }

    /**
     * @DESC          # 设置取得的数据
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/22 19:32
     * 参数区：
     *
     * @param AbstractModel[] $value
     */
    public function setFetchData(array $value): self
    {
        $this->_fetch_data = $value;
        $this->setItems($value);
        return $this;
    }

    /**
     * @DESC          # 获取查询数据
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/22 19:32
     * 参数区：
     */
    public function getFetchData(): mixed
    {
        return $this->_fetch_data;
    }

    function getItems(): array
    {
        return $this->items;
    }

    function setItems(array $items): self
    {
        $this->items = [];
        foreach ($items as $item) {
            if ($item instanceof AbstractModel) {
                $this->addItem($item);
            } else {
                if (is_array($item)) {
                    $model = $this::class;
                    $this->addItem(new $model($item));
                }
            }
        }
        return $this;
    }

    public function addItem(AbstractModel $item): self
    {
//        $classRef= ObjectManager::getReflectionInstance($item::class);
//        $properties         = isset($property_scope) ? $classRef->getProperties($property_scope) : $classRef->getProperties();
//        # 卸载模型属性
//        foreach ($properties as $property) {
//            // 为了兼容反射私有属性
//            $property->setAccessible(true);
//            // 当不想获取静态属性时
//            if ($property->isStatic()) {
//                continue;
//            }
//            if ($property->isPublic()) {
//                $attr = $property->getName();
//                unset($item->$attr);
//            }
//        }
//        p((object)$item);
        $this->items[] = $item;
        return $this;
    }

    public function setData($key, $value = null): static
    {
        $this->set_data_before($key, $value);
        if (is_array($key)) {
            $this->_model_fields_data = $key;
        } else {
            $this->_model_fields_data[$key] = $value;
        }
        parent::setData($key, $value);
        $this->set_data_after($key, $value);
        return $this;
    }

    public function set_data_before(string|array $key, mixed $value = null)
    {
    }

    public function set_data_after(string|array $key, mixed $value = null)
    {
    }


    /**----------参数获取---------------*/

    /**
     * @DESC          # 读取模型的主键字段值
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/26 21:54
     * 参数区：
     */
    public function getId()
    {
        return $this->getData($this->_primary_key);
    }

    /**
     * @DESC          # 设置模型的主键字段值
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/8/26 21:54
     * 参数区：
     */
    public function setId($primary_id): AbstractModel
    {
        return $this->setData($this->_primary_key, $primary_id);
    }

    public function getCreateTime()
    {
        return $this->getData(self::fields_CREATE_TIME);
    }

    public function setCreateTime(string $create_time): static
    {
        return $this->setData(self::fields_CREATE_TIME, $create_time);
    }

    public function getUpdateTime()
    {
        return $this->getData(self::fields_UPDATE_TIME);
    }

    public function setUpdateTime(string $update_time): static
    {
        return $this->setData(self::fields_CREATE_TIME, $update_time);
    }

    public function getModelFields(bool $remove_primary_key = false): array
    {
        if ($_model_fields = $this->_model_fields) {
            return $_model_fields;
        }
        $module__fields_cache_key = $this::class . '_module__fields_cache_key';
        if (PROD && $_model_fields = $this->_cache->get($module__fields_cache_key)) {
            return $_model_fields;
        }
        $objClass = new \ReflectionClass($this::class);
        $arrConst = $objClass->getConstants();
//        if ($this::class === \Weline\Theme\Model\WelineTheme::class) p($arrConst,1);
        $_fields = [];
        foreach ($arrConst as $key => $val) {
            if ($val && $key !== 'fields_ID' && str_starts_with($key, "fields_")) {
                $_fields[] = $val;
            }
        }
        if (!$remove_primary_key) $_fields[] = $this->_primary_key;
        $_fields             = array_unique($_fields);
        $this->_model_fields = $_fields;
        if (PROD) {
            $this->_cache->set($module__fields_cache_key, $_fields);
        }
        return $_fields;
    }

    public function bindModelFields(array $fields): static
    {
        foreach ($fields as $key => $bind_field) {
            if (in_array($bind_field, $this->_model_fields)) {
                unset($fields[$key]);
            }
        }
        $this->_model_fields = array_merge($fields, $this->_model_fields);
        return $this;
    }

    /**
     * @DESC          # 返回模型数据
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/11/16 17:09
     * 参数区：
     *
     * @param string $field
     *
     * @return array|string
     */
    public function getModelData(string $field = ''): array|string
    {
        if (empty($this->_model_fields_data) && $data = $this->getData()) {
            $need_fill_fields = [];
            foreach ($this->getModelFields() as $key => $val) {
                $field_data = $data[$val] ?? null;
                # 设置沿用主模型的数据
//                if ($val !== $this->_primary_key && $field_data && $datas = $this->getId()) {
//                    if (!isset($datas[0][$val])) {
//                        foreach ($datas as &$data_val) {
//                            $data_val[$val] = $field_data;
//                        }
//                        $this->setData($this->_primary_key, $datas);
//                    }
//                }
                if (($val === $this::fields_CREATE_TIME || $val === $this::fields_UPDATE_TIME) && empty($field_data)) {
                    $field_data = date('Y-m-d H:i:s');
                }
                $this->_model_fields_data[$val] = $field_data;

            }
        }
        if ($field && isset($this->_model_fields_data[$field]) && $field_data = $this->_model_fields_data[$field]) {
            return $field_data;
        }
        if ($field) {
            return '';
        }
        return $this->_model_fields_data;
    }

    /**
     * @DESC          # 设置模型数据
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/11/16 17:09
     * 参数区：
     *
     * @param array|string $key
     * @param mixed        $value
     *
     * @return AbstractModel
     */
    public function setModelData(array|string $key, mixed $value = ''): static
    {
        if (is_array($key)) {
            # 如果Key是批量数据 将主数据对应的 键值对 添加到批量数据中
            if (isset($key[0])) {
                foreach ($key as &$fv) {
                    $common_datas = $this->getData();
                    foreach ($common_datas as $common_key => $common_data) {
                        if (!isset($common_data[$common_key])) {
                            $fv[$common_key] = $common_data;
                        }
                    }
                }
            }
//            $this->clearDataObject();# 清空数据
            $this->setData($key);
            $this->_model_fields_data = $this->getModelData();
        } else {
            $this->_model_fields_data[$key] = $value;
            $this->setData($key, $value);
        }
        return $this;
    }

    /**
     * @DESC          # 处理分页
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/7/7 22:22
     * 参数区：
     *
     * @param int   $page     页码
     * @param int   $pageSize 页大小
     * @param array $params   请求参数
     *
     * @return AbstractModel|$this
     * @throws Exception
     * @throws \ReflectionException
     *
     */
    public function pagination(int $page = 1, int $pageSize = 20, array $params = []): AbstractModel|static
    {
        if (empty($params)) {
            $params = ObjectManager::getInstance(Request::class)->getGet();
        }
        $this->setQuery($this->getQuery()->pagination($page, $pageSize, $params));
        $this->pagination = $this->getQuery()->pagination;
        $this->setData('pagination', $this->getPagination());
        return $this;
    }

    public function getPaginationData(string $url_path = ''): array
    {
        # 分页数据存在
        if (isset($this->pagination['html'])) {
            return $this->pagination;
        }
        $this->pagination['path'] = $url_path;
        # 上一页
        if (($this->pagination['page'] - 1) > 0) {
            $this->pagination['prePage'] = $this->pagination['page'] - 1;
        } else {
            $this->pagination['prePage'] = 0;
        }
        # 下一页
        if ($this->pagination['page'] < $this->pagination['lastPage']) {
            $this->pagination['nextPage'] = $this->pagination['page'] + 1;
        } else {
            $this->pagination['nextPage'] = $this->pagination['lastPage'];
        }
        $hasPrePage                      = intval($this->pagination['prePage']) !== 0;
        $this->pagination['hasPrePage']  = $hasPrePage;
        $hasNextPage                     = (intval($this->pagination['page']) < intval($this->pagination['lastPage']));
        $this->pagination['hasNextPage'] = $hasNextPage;
        /**@var Request $request */
        $request                  = ObjectManager::getInstance(Request::class);
        $this->pagination['lang'] = $request->getHeader('WELINE-USER-LANG');

        # 页码缓存
        $cache_key = md5(json_encode($this->pagination));
        if ($data = $this->_cache->get($cache_key)) {
            $this->pagination = $data;
            return $data;
        }
        $currentUrl     = $request->isBackend() ? $request->getAdminUrl($url_path) : $request->getUrl($url_path);
        $currentUrlPath = substr($currentUrl, 0, strpos($currentUrl, '?'));

        $prePageName = __('上一页');

        $prePageClassStatus = $hasPrePage ? '' : 'disabled';
        $prePageUrl         = $hasPrePage ? "{$currentUrlPath}?page={$this->pagination['prePage']}&pageSize={$this->pagination['pageSize']}" : '#';

        $page_list_html = '';
        for ($i = 1; $i <= intval($this->pagination['lastPage']); $i++) {
            $pageActiveStatus = ($this->pagination['page'] === $i) ? 'active' : '';
            $pageUrl          = "{$currentUrlPath}?page={$i}&pageSize={$this->pagination['pageSize']}";
            $page_list_html   .= <<<PAGELISTHTML
<li class='page-item {$pageActiveStatus}'><a
                    class='page-link'
                    href='{$pageUrl}'>{$i}</a>
            </li>
PAGELISTHTML;
        }

        $nextPageName = __('下一页');

        $nextPageClassStatus      = $hasNextPage ? '' : 'disabled';
        $nextPageUrl              = $hasNextPage ? "{$currentUrlPath}?page={$this->pagination['nextPage']}&pageSize={$this->pagination['pageSize']}" : '#';
        $this->pagination['html'] = <<<PAGINATION
<nav aria-label='...'>
                            <ul class='pagination pagination-lg'>
                                <li class='page-item {$prePageClassStatus}'>
                                    <a class='page-link'
                                       href='{$prePageUrl}'
                                       tabindex='-1'>{$prePageName}</a>
                                </li>
                                {$page_list_html}
                                <li class='page-item {$nextPageClassStatus}'>
                                    <a class='page-link'
                                       href='{$nextPageUrl}'>{$nextPageName}</a>
                                </li>
                            </ul>
                        </nav>
PAGINATION;
        # 页码缓存
        $this->_cache->set($cache_key, $this->pagination);
        return $this->pagination;
    }

    public function getPagination(): string
    {
        return $this->getPaginationData()['html'] ?? '';
    }

    /**----------链接查询--------------*/

    public function bindQuery(QueryInterface $query): static
    {
        $this->_bind_query = $query;
        return $this;
    }

    public function joinModel(AbstractModel|string $model, string $alias = '', $condition = '', $type = 'LEFT', string $fields = '*'): AbstractModel
    {
        $query = $this->getQuery(true);
        if (is_string($model)) {
            /**@var Model $model */
            $model = ObjectManager::getInstance($model);
            $model->setQuery($query);
            $model->alias($alias);
        }
        # 自动设置条件
        $model_table = $model->getTable();
        if (empty($condition)) {
            $condition = "`main_table`.`{$this->getIdField()}`={$alias}.`{$model->getIdField()}`";
        }
        if ($fields === '*') {
            $model_fields = '';
            foreach ($model->getModelFields() as $modelField) {
                $model_fields .= $modelField . ',';
            }
            $model_fields = rtrim($model_fields, ',');
            $this->bindModelFields(explode(',', $model_fields));
        } else {
            $this->bindModelFields(explode(',', $fields));
        }
        $this->_joins[] = [$model_table . ($alias ? ' `' . $alias . '`' : ''), $condition, $type];
        return $this->bindQuery($query);
    }


    /**缓存控制*/

    function useCache(bool $cache = true)
    {
        $this->use_cache = $cache;
        return $this;
    }

    function getCache(string $key)
    {
        if ($this->use_cache) {
            return $this->_cache->get($key);
        }
        return null;
    }

    function setCache(string $key, mixed $value, $duration = 1800)
    {
        return $this->_cache->set($key, $value, $duration);
    }
}
