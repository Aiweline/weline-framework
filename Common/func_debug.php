<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */
if (!function_exists('p')) {
    /**
     * @DESC         |打印调试
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param      $data
     * @param bool $pass
     * @param int  $trace_deep
     */
    function p($data = null, $pass = false, int $trace_deep = 1): void
    {
        // 执行时间
        $exe_time = microtime(true) - START_TIME;
        $isCli    = (PHP_SAPI === 'cli');
        if(!$isCli){
            // 响应500
            http_response_code(500);
        }
        $echo_pre = ($isCli ? PHP_EOL : '<pre>');
        echo $echo_pre;
        $parent_call_info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $trace_deep);
        $parent_call_info = array_reverse($parent_call_info);
        foreach ($parent_call_info as $key => $item) {
            if (is_array($item)) {
                foreach ($item as $k => $i) {
                    $end_line = '';
                    if (isset($item['line']) && 'file' === $k) {
                        $end_line = ':' . $item['line'];
                    }
                    $k     = "【{$k}】";
                    $i_str = is_string($i) ? $i . $end_line : json_encode($i) . $end_line;
                    print_r("{$k} " . $i_str . ($isCli ? PHP_EOL : '<br>'));
                }
                echo '---------------------------------------------------------' . ($isCli ? PHP_EOL : '<br>');
            } else {
                $key      = str_pad($key, 12, '-', STR_PAD_BOTH);
                $item_str = is_string($item) ? $item : json_encode($item);
                print_r("{$key}");
                echo '---------------------------------------------------------' . ($isCli ? PHP_EOL : '<br>');
            }
        }
        if (is_object($data)) {
            if (method_exists($data, 'toArray')) {
                $subIsObject = 0;
                foreach ($data->toArray() as $item) {
                    if (is_object($item)) {
                        $subIsObject = 1;
                    }
                }
                if (!$subIsObject) {
                    var_dump(get_class($data));
                    echo $isCli ? PHP_EOL : '<br><pre>';
                    var_dump($data->toArray());
                    echo $isCli ? PHP_EOL : '</div><br><div>调试时间：<br>--' . ($exe_time * 1000) . '(ms/毫秒)<br>--' . $exe_time . '(s/秒)<br></div></div></pre>';
                    echo $isCli ? PHP_EOL : '</div>';
                    echo $isCli ? PHP_EOL : '</div>';
                    if (DEV) {
                        echo $isCli ? PHP_EOL : '<b>源数据：</b>';
                        echo $isCli ? PHP_EOL : '<br>';
                        var_dump($data);
                        echo $isCli ? PHP_EOL : '</pre>';
                    }
                    if (!$pass) {
                        die;
                    }
                }
            }
            echo $isCli ? PHP_EOL : '<br><pre>';
            var_dump($data);
            var_dump(get_class($data));
            var_dump(get_class_methods($data));
            echo $isCli ? PHP_EOL : '</div><br><div>调试时间：<br>--' . ($exe_time * 1000) . '(ms/毫秒)<br>--' . $exe_time . '(s/秒)<br></div></div></pre>';
            echo $isCli ? PHP_EOL : '</div></div></pre>';
            if (!$pass) {
                die;
            }
        }

        var_dump($data);
        echo $isCli ? PHP_EOL : '</div><br><div>调试时间：<br>--' . ($exe_time * 1000) . '(ms/毫秒)<br>--' . $exe_time . '(s/秒)<br></div></div></pre>';
        if (!$pass) {
            die;
        }
    }
}
if (!function_exists('pp')) {
    /**
     * 打印并跳过
     *
     * @param $data
     */
    function pp($data): void
    {
        p($data, 1);
    }
}
//if (!function_exists('d')) {
//    /**
//     * @DESC         |打印调试
//     *
//     * @Author       秋枫雁飞
//     * @Email        aiweline@qq.com
//     * @Forum        https://bbs.aiweline.com
//     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
//     *
//     * 参数区：
//     *
//     * @param      $data
//     * @param bool $pass
//     * @param int  $trace_deep
//     */
//    function d($data = null, bool $pass = false, int $trace_deep = 2): void
//    {
//        // 执行时间
//        $exe_time = microtime(true) - START_TIME;
//
//        $parent_call_info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $trace_deep);
//        $isCli            = (PHP_SAPI === 'cli');
//        if (!$isCli) {
//            echo '<div style="color: #180808;padding: 2% 5%;border: 2px gray dashed "><pre style="font-size: 20px"><div>';
//            print_r("<h3 style=\"color: chocolate\">调试位置：（深度：{$trace_deep}）</h3>");
//            echo '<style>body{background-color:#9e9e9e42 }</style>';
//        }
//        $parent_call_info = array_reverse($parent_call_info);
//        foreach ($parent_call_info as $key => $item) {
//            if (is_array($item)) {
//                foreach ($item as $k => $i) {
//                    $k     = str_pad($k, 12, '-', STR_PAD_BOTH);
//                    $i_str = is_string($i) ? $i : json_encode($i);
//                    !$isCli ? print_r("<b style='color: dodgerblue'>{$k}</b>  :  <b style='color: darkred'>{$i_str}</b>" . PHP_EOL) : print_r("{$k}   {$i}" . PHP_EOL);
//                }
//                echo !$isCli ? '---------------------------------------------------------<br>' : print_r('---------------------------------------------------------' . PHP_EOL);
//            } else {
//                $key      = str_pad($key, 12, '-', STR_PAD_BOTH);
//                $item_str = is_string($item) ? $item : json_encode($item);
//                !$isCli ? print_r("<b style='color: dodgerblue'>{$key}</b>  :  <b style='color: darkred'>{$item_str}</b>" . PHP_EOL) : print_r("{$key}   {$item}" . PHP_EOL);
//            }
//        }
//        !$isCli ? print_r('<h3 style="color: chocolate">调试信息：</h3><div style="border: #0a464e solid 1px;padding: 2% 2%">') : print_r('调试信息：');
//        if (is_object($data)) {
//            if (method_exists($data, 'toArray')) {
//                $subIsObject = 0;
//                foreach ($data->toArray() as $item) {
//                    if (is_object($item)) {
//                        $subIsObject = 1;
//                    }
//                }
//                if (!$subIsObject) {
//                    var_dump(get_class($data));
//                    echo $isCli ? PHP_EOL : '<br>';
//                    var_dump($data->toArray());
//                    echo $isCli ? PHP_EOL : '</div><br><div>调试时间：<br>--' . ($exe_time * 1000) . '(ms/毫秒)<br>--' . $exe_time . '(s/秒)<br></div></div></pre>';
//                    echo $isCli ? PHP_EOL : '</div></pre>';
//                    if (!$pass) {
//                        die;
//                    }
//                }
//            }
//            var_dump(get_class($data));
//            echo $isCli ? PHP_EOL : '<br>';
//            var_dump(get_class_methods($data));
//            echo $isCli ? PHP_EOL : '</div><br><div>调试时间：<br>--' . ($exe_time * 1000) . '(ms/毫秒)<br>--' . $exe_time . '(s/秒)<br></div></div></pre>';
//            echo $isCli ? PHP_EOL : '</div></div></pre>';
//            if (!$pass) {
//                die;
//            }
//        }
//        var_dump($data);
//        echo $isCli ? PHP_EOL : '</div><br><div>调试时间：<br>--' . ($exe_time * 1000) . '(ms/毫秒)<br>--' . $exe_time . '(s/秒)<br></div></div></pre>';
//        if (!$pass) {
//            die;
//        }
//    }
//}
if (function_exists('dump') && !function_exists('d')) {
    function d($data, $trace_deep = 2): void
    {
        // 执行时间
        $exe_time                 = microtime(true) - START_TIME;
        $parent_call_info         = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $trace_deep);
        $parent_call_info         = array_reverse($parent_call_info);
        $parent_call_info['time'] = $exe_time;
//        if (DEV) {
//            dump($parent_call_info);
//        }
        dump($data);
    }
}
if (!function_exists('d')) {
    /**
     * @DESC         |打印调试
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param      $data
     *
     * @return string|string[]|null
     */
    function d($data)
    {
        if (is_null($data)) {
            $str = '<i>NULL</i>';
        } elseif ($data == '') {
            $str = '<i>Empty</i>';
        } elseif (is_array($data)) {
            if (count($data) == 0) {
                $str = '<i>Empty array.</i>';
            } else {
                $str = "<table style=\"border-bottom:0px solid #000;\" cellpadding=\"0\" cellspacing=\"0\">";
                foreach ($data as $key => $value) {
                    $str .= "<tr><td style=\"background-color:#008B8B; color:#FFF;border:1px solid #000;\">" . $key . "</td><td style=\"border:1px solid #000;\">" . d($value) . '</td></tr>';
                }
                $str .= '</table>';
            }
        } elseif (is_resource($data)) {
            while ($arr = \mysql_fetch_array($data)) {
                $data_array[] = $arr;
            }
            $str = d($data_array);
        } elseif (is_object($data)) {
            $str = d(get_object_vars($data));
        } elseif (is_bool($data)) {
            $str = '<i>' . ($data ? 'True' : 'False') . '</i>';
        } else {
            $str = $data;
            $str = preg_replace("/\n/", "<br>\n", $str);
        }
        return $str;
    }
}
if (!function_exists('dd')) {
    function dnl($data)
    {
        return d($data) . "<br>\n";
    }

    function dd($data)
    {
        echo dnl($data);
        // 执行时间
        $exe_time = microtime(true) - START_TIME;
        $isCli    = (PHP_SAPI === 'cli');
        $echo_pre = ($isCli ? PHP_EOL : '<pre>');
        echo $echo_pre;
        $parent_call_info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $parent_call_info = array_reverse($parent_call_info);
        foreach ($parent_call_info as $key => $item) {
            if (is_array($item)) {
                foreach ($item as $k => $i) {
                    $end_line = '';
                    if (isset($item['line']) && 'file' === $k) {
                        $end_line = ':' . $item['line'];
                    }
                    $k     = "【{$k}】";
                    $i_str = is_string($i) ? $i . $end_line : json_encode($i) . $end_line;
                    print_r("{$k} " . $i_str . ($isCli ? PHP_EOL : '<br>'));
                }
                echo '---------------------------------------------------------' . ($isCli ? PHP_EOL : '<br>');
            } else {
                $key      = str_pad($key, 12, '-', STR_PAD_BOTH);
                $item_str = is_string($item) ? $item : json_encode($item);
                print_r("{$key}");
                echo '---------------------------------------------------------' . ($isCli ? PHP_EOL : '<br>');
            }
        }
        echo $exe_time.PHP_EOL;
        exit;
    }

    function ddt($data = '')
    {
        echo '[' . date('Y/m/d H:i:s') . ']' . dnl($data) . "<br>\n";
        exit();
    }
}
