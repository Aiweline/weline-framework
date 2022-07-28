<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Http;

use Weline\Framework\Manager\ObjectManager;
use Weline\I18n\Model\I18n;

class Cookie
{
    public function setCookie(string $key, string $value)
    {
        setcookie($key, $value);
    }

    /**
     * @DESC          # 获取语言
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/6/24 22:47
     * 参数区：
     * @return string
     */
    static public function getLang(): string
    {
        // 用户语言优先
        $lang = $_COOKIE['WELINE-USER-LANG'] ?? null;
        // 默认网站语言
        if (empty($lang)) {
            $lang = $_COOKIE['WELINE-WEBSITE-LANG'] ?? 'zh_Hans_CN';
            if(!CLI){
                setcookie('WELINE-WEBSITE-LANG',$lang);
                setcookie('WELINE-USER-LANG',$lang);
            }
        }
        return $lang;
    }

    /**
     * @DESC          # 获取语言
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/6/24 22:47
     * 参数区：
     * @return string
     * @throws \ReflectionException
     * @throws \Weline\Framework\App\Exception
     */
    static public function getLangLocal(): string
    {
        return ObjectManager::getInstance(I18n::class)->getLocalByCode(self::getLang());
    }
}
