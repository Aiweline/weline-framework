<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Module\Dependency;

class Sort
{
    /**
     * @DESC          # 依赖排序
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/4/22 20:10
     * 参数区：
     *
     * @param array  $dependencies
     * @param string $entity_id
     * @param string $parent_key
     *
     * @return array
     */
    public function dependenciesSort(array $dependencies, string $entity_id = 'id', string $parent_key = 'parent'): array
    {
        $dependencies_sort = [];

        while (count($dependencies) > 0) {
            foreach ($dependencies as $k => $d) {
                $add = true;
                foreach ($d[$parent_key] as $parent) {
                    if (!isset($dependencies_sort[$parent])) {
                        $add = false;
                    }
                }
                if ($add||end($dependencies)) {
                    $dependencies_sort[$d[$entity_id]] = $d;
                    unset($dependencies[$k]);
                }
            }
        }
        return $dependencies_sort;
    }
}
