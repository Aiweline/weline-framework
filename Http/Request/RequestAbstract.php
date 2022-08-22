<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Http\Request;

use Weline\Framework\App\Env;
use Weline\Framework\App\State;
use Weline\Framework\Controller\Data\DataInterface;
use Weline\Framework\DataObject\DataObject;
use Weline\Framework\Event\EventsManager;
use Weline\Framework\Http\Response;
use Weline\Framework\Manager\ObjectManager;

abstract class RequestAbstract extends DataObject
{
    public const HEADER = 'header';

    public const MOBILE_DEVICE_HEADERS = [
        'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc',
        'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic',
        'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry',
        'meizu', 'android', 'netfront', 'symbian', 'ucweb',
        'windowsce', 'palm', 'operamini', 'operamobi', 'openwave',
        'nexusone', 'cldc', 'midp', 'wap', 'mobile',
    ];

    private string $area_router = State::area_frontend;

    /**
     * @var RequestFilter
     */
    public RequestFilter $_filter;

    private array $parse_url = [];

    /**
     * @var \Weline\Framework\Http\Response
     */
    public ?\Weline\Framework\Http\Response $_response = null;

    public function __init()
    {
        $url_arr           = explode('/', trim($this->getModuleUrlPath(), '/'));
        $this->area_router = array_shift($url_arr);
        if (empty($this->_filter)) {
            $this->_filter = RequestFilter::getInstance();
        }
        if (empty($this->_response)) {
            $this->_response = $this->getResponse();
        }
    }

    public function parse_url(): bool|int|array|string|null
    {
        if (empty($this->parse_url)) {
            $this->parse_url = parse_url($this->getUri());
        }
        return $this->parse_url;
    }

    /**
     * @DESC         |设置原始路由
     *
     * 参数区：
     *
     * @param array $router
     */
    public function setRouter(array $router): RequestAbstract
    {
        return $this->setData('router', $router);
    }

    /**
     * @DESC         |获取原始路由
     *
     * 参数区：
     *
     * @return array
     */
    public function getRouter(): array
    {
        return $this->getData('router')??[];
    }

    /**
     * @DESC         |获取原始路由
     *
     * 参数区：
     *
     * @return string|null
     */
    public function getRouterData(string $key): mixed
    {
        return $this->getData('router/' . $key);
    }

    /**
     * @DESC         |获取模块名
     *
     * 参数区：
     *
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->router['name'] ?? '';
    }

    /**
     * @DESC         |获取请求区域
     *
     * 参数区：
     *
     * @return string
     */
    public function getRequestArea(): string
    {
        switch ($this->area_router) {
            case Env::getInstance()->getConfig('admin', 'admin'):
                $area = DataInterface::type_pc_BACKEND;
                $this->setBackend();
                break;
            case Env::getInstance()->getConfig('api_admin', 'api_admin'):
                $area = DataInterface::type_api_BACKEND;
                $this->setBackend();
                $this->setApiBackend();
                break;
            default:
                $area = DataInterface::type_pc_FRONTEND;
                break;
        }
        /**@var EventsManager $eventManager */
        $eventManager = ObjectManager::getInstance(EventsManager::class);
        $eventManager->dispatch('WelineFramework_Http::process_area', ['area' => $area, 'path' => $this->area_router]);
        return $area;
    }

    /**
     * @DESC         |获取
     *
     * 参数区：
     *
     * @return mixed|string
     */
    public function getAreaRouter(): mixed
    {
        return $this->area_router;
    }

    /**
     * @DESC          # 是否后端请求
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/14 23:20
     * 参数区：
     * @return bool
     */
    public function setBackend(): static
    {
        return $this->setData('backend', true);
    }

    /**
     * @DESC          # 是否后端请求
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/14 23:20
     * 参数区：
     * @return bool
     */
    public function isBackend(): bool
    {
        return $this->getData('backend') ?: false;
    }


    public function setApiBackend(): static
    {
        return $this->setData('api_backend', true);
    }

    public function isApiBackend(): bool
    {
        return $this->getData('api_backend') ?: false;
    }

    /**
     * @DESC         |方法描述
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param string|null $key
     *
     * @return string|array
     */
    public function getServer(string $key = null): string|array
    {
        $filter = RequestFilter::getInstance();
        $filter->init();
        if ($key) {
            switch ($key) {
                case self::HEADER:
                    $params = [];
                    foreach ($_SERVER as $name => $value) {
                        if (substr($name, 0, 5) === 'HTTP_') {
                            $params[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                        }
                    }

                    break;
                default:
                    $params = $_SERVER[$key] ?? '';
            }
        } else {
            $params = $_SERVER;
        }

        return $params;
    }

    /**
     * @DESC         |请求方法
     *
     * 参数区：
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @DESC         |是否手机设备
     *
     * 参数区：
     *
     * @return bool
     */
    public function isMobile(): bool
    {
        //如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        //如via信息有wap一定是移动设备，但是部分服务商会屏蔽该信息
        if (isset($_SERVER['HTTP_VIA'])) {
            //找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
        }
        //判断手机发送的客户端标志,兼容性有待提高
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            //从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match(
                '/(' . implode('|', self::MOBILE_DEVICE_HEADERS) . ')/i',
                strtolower($_SERVER['HTTP_USER_AGENT'])
            )) {
                return true;
            }
        }
        //协议法，因为有可能不准确，放到最后判断
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            //如果只支持wml并且不支持html那一定是app
            //如果支持wml和html但是wml在html之前则是app
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false)
                && (
                    strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false ||
                    (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml')
                        < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))
                )) {
                return true;
            }
        }

        return false;
    }

    public function getUri(): string
    {
        return $this->getServer('REQUEST_URI');
    }

    /**
     * @DESC          # 获取请求的module路由路径
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/9/22 20:24
     * 参数区：
     * @return string
     */
    public function getModuleUrlPath(): string
    {
        $url_exp = parse_url($this->getUri());
        return array_shift($url_exp);
    }

    public function getBaseUrl(): string
    {
        $uri     = $this->getUri();
        $url_exp = explode('?', rtrim($uri, '/'));
        return $this->getBaseHost() . array_shift($url_exp);
    }

    public function getBaseUri(): string
    {
        $uri     = $this->getUri();
        $url_exp = explode('?', $uri);
        return $this->getBaseHost() . array_shift($url_exp);
    }

    public function getFirstUrlPath(): string
    {
        $uri     = $this->getUri();
        $url_exp = explode('?', $uri);
        return trim(array_shift($url_exp), '/');
    }

    public function getBaseHost(): string
    {
        $port = $this->getServer('SERVER_PORT');
        return $this->getServer('REQUEST_SCHEME') . '://' . $this->getServer('SERVER_NAME') . (($port !== '80' && $port !== '443') ? ':' . $port : '');
    }

    public function getPrePath(): string
    {
        return $this->getBaseHost() . '/' . $this->getAreaRouter() . '/';
    }

    /**
     * @DESC         |返回响应类
     *
     * 参数区：
     *
     * @return Response
     * @throws \Weline\Framework\App\Exception
     * @throws \ReflectionException
     */
    public function getResponse(): Response
    {
        return $this->_response = ObjectManager::getInstance(\Weline\Framework\Http\Response::class);
    }
}
