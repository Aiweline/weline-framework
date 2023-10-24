<?php
declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 作者：Administrator
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 * 日期：24/10/2023 13:20:05
 */

namespace Weline\Framework\Database\Helper;

class Tool
{
    static function sql2table($sql, string|array $exclude_expression = '')
    {
        $expression = array(
            'update' => '/UPDATE[\s`]+?(\w+[\.]?\w+)[\s`]+?/is',
            'insert' => '/INSERT[\s]{1,}INTO[\s]{1,}[`]?([A-Za-z0-9\_]{1,}[\.]?([A-Za-z0-9\_]{1,})?)[`]?/is',
            'delete' => '/DELETE\s+?FROM[\s`]+?(\w+[\.]?\w+)[\s`]+?/is',
            'select' => '/((SELECT.+?FROM)|(LEFT\\s+JOIN|JOIN|LEFT))[\\s`]+?(\\w+[\.]?\\w+)[\\s`]+?/is'
        );
        # 排除的类型
        if ($exclude_expression) {
            $expression = array_diff_key($expression, $exclude_expression);
        }
        foreach ($expression as $type => $e) {
            $ret = preg_match_all($e, $sql, $matches);
            if (is_int($ret) and $ret) {
                if ($type == 'insert') {
                    return array($type => array_unique($matches[1]));
                }
                return array($type => array_unique(array_pop($matches)));
            }
        }
        return array();
    }
}