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
    static function set(string $key, string $value, int $expire = 3600 * 24 * 7, array $options = [])
    {
        $_options['path'] = '/';
        $_options['domain'] = getenv('HTTP_HOST');
        if($options)$_options = array_merge($_options, $options);
        setcookie($key, $value, time() + $expire, ...$_options);
    }

    static function get(string $key, $default = null)
    {
        return $_COOKIE[$key] ?? $default;
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
            $lang = self::get('WELINE-WEBSITE-LANG', 'zh_Hans_CN');
            if (!CLI) {
                self::set('WELINE-WEBSITE-LANG', $lang);
                self::set('WELINE-USER-LANG', $lang);
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
