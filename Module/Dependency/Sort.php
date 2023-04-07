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
    function sortModules($modules) {
        $sortedModules = array();
        $visitedModules = array();

        foreach ($modules as $module => $module_data) {
            $this->sortModuleDFS($module, $module_data['dependencies'], $modules, $sortedModules, $visitedModules);
        }

        return $sortedModules;
    }

    function sortModuleDFS($module, $dependencies, $modules, &$sortedModules, &$visitedModules) {
        if (isset($visitedModules[$module])) {
            return;
        }

        $visitedModules[$module] = true;
        foreach ($dependencies as $dependency) {
            if (isset($modules[$dependency])) {
                $this->sortModuleDFS($dependency, $modules[$dependency]['dependencies'], $modules, $sortedModules, $visitedModules);
            }
        }

        $sortedModules[$module] = $modules[$module];
    }
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
    public function dependenciesSort(array $dependencies, string $entity_id = 'name', string $parent_key = 'dependencies'): array
    {
        return $this->sortModules($dependencies);
    }
}
