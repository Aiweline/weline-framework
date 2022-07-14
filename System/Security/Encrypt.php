<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\System\Security;

class Encrypt
{
    /**
     * @DESC          # 加盐MD5加密
     *
     * @AUTH  秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/11/9 14:17
     * 参数区：
     * @param $password
     * @param $salt
     * @return string
     */
    public static function md5_salt($password, $salt): string
    {
        $str1 = mb_substr($password, 0, 5);
        $str2 = mb_substr($salt, 0, 2);
        $str3 = mb_substr($salt, -2);
        $str4 = mb_substr($password, -5);
        return crypt(md5($str1 . $str2 . $password . $str3 . $str4), $salt);
    }
}
