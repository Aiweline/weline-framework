<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Http;

use Weline\Framework\App\Env;
use Weline\Framework\Http\Request\RequestFilter;
use Weline\Framework\Manager\ObjectManager;

class Request extends Request\RequestAbstract implements RequestInterface
{
    private static Request $instance;

    private string $module_name = '';

    public function __init()
    {
        parent::__init();
        if (is_array($this->getBodyParams())) {
            $this->setData(array_merge($this->getParams(), $this->getBodyParams()));
        }
    }

    public function getUrlPath(): string
    {
        return $this->parse_url()['path'];
    }

    /**
     * @return string
     */
    public function getModulePath(): string
    {
        return $this->getRouterData('module_path');
    }

    public function getHeader(string $key = null)
    {
        if (empty($key)) {
            return $this->getServer(self::HEADER);
        }

        return $this->getServer('HTTP_' . strtoupper($key));
    }

    public function getParam(string $key, mixed $default = null, string $filter = null)
    {
        if ($result = $this->getData($key)) {
            return $result;
        }
        parse_str($this->getServer('QUERY_STRING'), $params);
        array_shift($params);
        $params = array_merge($params, $_POST);
        $params = array_merge($params, $_GET);
        $data   = $params[$key] ?? $default;
        # 如果设置了过滤器，则进行过滤，否则直接使用默认值的类型进行过滤
        if ($filter) {
            $data = RequestFilter::filter($filter, $data);
        } else {
            $data = RequestFilter::filter(gettype($default), $data);
        }
        return $data;
    }

    public function getParams()
    {
        if ($params = $this->getData('params')) {
            return $params;
        }
        parse_str($this->getServer('QUERY_STRING'), $params);
        array_shift($params);
        $params = array_merge($params, $_POST);
        $params = array_merge($params, $_GET);
        $this->setData('params', $params);
        return $params;
    }

    public function getBodyParam($key, mixed $default = null)
    {
        $params = $this->getBodyParams();
        return $params[$key] ?? $default;
    }

    public function getBodyParams()
    {
        if ($params = $this->getData('body_params')) {
            return $params;
        }
        $params = file_get_contents('php://input');
        if (is_int(strpos($this->getContentType(), self::CONTENT_TYPE['json']))) {
            $params = json_decode($params, true);
        }
        if (is_string($params)) {
            $params_ = [];
            foreach (explode('&', $params) as $key => $value) {
                $value = explode('=', $value);
                if (isset($value[0])) $params_[$value[0]] = $value[1] ?? '';
            }
            $params = $params_;
        }
        $this->setData('body_params',$params);
        return $params;
    }

    public function getPost(string $key = '', mixed $default = null)
    {
        if ('' === $key) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    public function getGet(string $key = '', mixed $default = null)
    {
        if ('' === $key) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    public function isPost(): bool
    {
        return $this->getMethod() === self::POST;
    }

    public function isGet(): bool
    {
        return $this->getMethod() === self::GET;
    }

    public function isPut(): bool
    {
        return $this->getMethod() === self::PUT;
    }

    public function isDelete(): bool
    {
        return $this->getMethod() === self::DELETE;
    }

    public function getContentType(): string
    {
        return $this->getServer('CONTENT_TYPE');
    }

    public function getReferer(): string
    {
        return $this->getServer('HTTP_REFERER');
    }

    public function getAuth(string $auth_type = 'bearer')
    {
        switch ($auth_type) {
            case self::auth_TYPE_BEARER:
                return str_replace('Bearer ', '', $this->getHeader('Authorization'));
            case self::auth_TYPE_BASIC_AUTH:
                return ['USER' => $this->getServer('PHP_AUTH_USER'), 'PW' => $this->getServer('PHP_AUTH_PW')];
            default:
                return null;
        }
    }

    public function getApiKey(string $key): string
    {
        return $this->getHeader($key);
    }

    public function getModuleName(): string
    {
        if ($module_name = parent::getModuleName()) {
            return $module_name;
        } else {
            return $this->getRouter()['module'] ?? '';
        }
    }

    public function clientIP()
    {
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $realip = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            } elseif (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }
        return $realip;
    }

    public function getUrl(string $path = '', array $params = [], bool $merge_params = false): string
    {
        if ($path) {
            $url = $this->getBaseHost() . '/' . ltrim($path, '/');
        } else {
            $url = $this->getBaseUrl();
        }
        return $this->extractedUrl($params, $merge_params, $url);
    }

    public function getAdminUrl(string $path = '', array $params = [], bool $merge_params = true): string
    {
        if ($path) {
            $url = $this->getBaseHost() . '/' . Env::getInstance()->getConfig('admin') . (('/' === $path) ? '' : '/' . $path);
        } else {
            $url = $this->getBaseUrl();
        }
        return $this->extractedUrl($params, $merge_params, $url);
    }

    public function getApiAdminUrl(string $path = '', array $params = [], bool $merge_params = true): string
    {
        if ($path) {
            $url = $this->getBaseHost() . '/' . Env::getInstance()->getConfig('api_admin') . '/' . $path;
        } else {
            $url = $this->getBaseUrl();
        }
        return $this->extractedUrl($params, $merge_params, $url);
    }

    /**
     * @DESC          # 提取Url
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/2/8 23:27
     * 参数区：
     *
     * @param array  $params
     * @param bool   $merge_params
     * @param string $url
     *
     * @return string
     */
    public function extractedUrl(array $params, bool $merge_params, string $url): string
    {
        if ($params) {
            if ($merge_params) {
                $url .= '?' . http_build_query(array_merge($this->getGet(), $params));
            } else {
                $url .= '?' . http_build_query($params);
            }
        } else {
            $url .= ($this->getGet()&&$merge_params) ? '?' . http_build_query($this->getGet()) : '';
        }
        return $url;
    }

    /**
     * @DESC          # 设置规则数据
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/4/28 19:30
     * 参数区：
     *
     * @param string|array $key
     * @param mixed        $value
     *
     * @return $this
     */
    public function setRule(string|array $key, mixed $value = null): static
    {
        if (is_array($key)) {
            $this->setData('rule', $key);
        } else {
            $rule       = $this->getRule();
            $rule[$key] = $value;
            $this->setData('rule', $rule);
        }
        return $this;
    }

    /**
     * @DESC          # 获取规则数据
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/4/28 19:30
     * 参数区：
     *
     * @param string $key
     *
     * @return array|null|string
     */
    public function getRule(string $key = ''): array|null|string
    {
        if ($key) {
            return $this->getData('rule')[$key] ?? null;
        } else {
            return $this->getData('rule');
        }
    }
}
