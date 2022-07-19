<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Database;

use Weline\Backend\Model\Menu;
use Weline\Framework\Manager\ObjectManager;

abstract class Model extends AbstractModel implements ModelInterface
{
    public function columns(): array
    {
        $cache_key = $this->getTable() . '_columns';
        if ($columns = $this->_cache->get($cache_key)) {
            p($columns);
            return $columns;
        }
        $columns = $this->query("SHOW FULL COLUMNS FROM {$this->getTable()} ")->fetchOrigin();
        $this->_cache->set($cache_key, $columns);
        return $columns;
    }

    /**
     * @DESC          # 获取菜单树
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/7/3 8:49
     * 参数区：
     *
     * @param string $main_field
     * @param string $parent_id_field
     * @param string $order_field
     * @param string $order_sort
     *
     * @return array
     */
    public function getTree(string $main_field='',string $parent_id_field='parent_id',string $order_field='position',string $order_sort='ASC'): array
    {
        $main_field = $main_field?:$this::fields_ID;
        $top_menus = $this->clearData()->where($parent_id_field, 0)->order($order_field, $order_sort)->select()->fetch()->getItems();
        foreach ($top_menus as &$top_menu) {
            $top_menu = $this->getSubs($top_menu,$main_field,$parent_id_field,$order_field,$order_sort);
        }
        return $top_menus;
    }

    /**
     * @DESC          # 方法描述
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/2/20 23:18
     * 参数区：
     * @return \Weline\Backend\Model\Menu[]
     */
    public function getSub(): array
    {
        return $this->getData('sub') ?? [];
    }

    /**
     * @DESC          # 方法描述
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/7/3 8:57
     * 参数区：
     *
     * @param Model  $model
     * @param string $main_field
     * @param string $parent_id_field
     * @param string $order_field
     * @param string $order_sort
     *
     * @return Model
     */
    public function getSubs(Model &$model,string $main_field='',string $parent_id_field='parent_id',string $order_field='position',string $order_sort='ASC'): Model
    {
        $main_field = $main_field?:$this::fields_ID;
        if ($subs = $this->clearData()->where($parent_id_field, $model->getData($main_field))->order($order_field, $order_sort)->select()->fetch()->getItems()) {
            foreach ($subs as &$sub) {
                $has_sub_menu = $this->clearData()->where($parent_id_field, $sub->getData($main_field))->find()->fetch();
                if ($has_sub_menu->getData($main_field)) {
                    $sub = $this->getSubs($sub);
                }
            }
            $model = $model->setData('sub', $subs);
        } else {
            $model = $model->setData('sub', []);
        }
        return $model;
    }
}
