<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Controller;

use Weline\Framework\App\Env;
use Weline\Framework\App\Exception;
use Weline\Framework\Cache\CacheInterface;
use Weline\Framework\Controller\Cache\ControllerCache;
use Weline\Framework\DataObject\DataObject;
use Weline\Framework\Event\EventsManager;
use Weline\Framework\Http\Request;
use Weline\Framework\Manager\MessageManager;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\View\Data\DataInterface;
use Weline\Framework\View\Template;
use ReflectionObject;

class PcController extends Core
{
    private Template $_template;
    private EventsManager $_eventManager;

    private CacheInterface $controllerCache;

    public function __init()
    {
        parent::__init();
        $this->isAllowed();
        $this->assign($this->_request->getParams());
        if (empty($this->controllerCache)) {
            $this->controllerCache = $this->getControllerCache();
        }
        if (empty($this->_eventManager)) {
            $this->_eventManager = ObjectManager::getInstance(EventsManager::class);
        }
    }

    /**
     * @param string|int $url url或者http状态码
     *
     * @return void
     * @throws Exception
     * @throws \ReflectionException
     */
    public function redirect(string|int $url)
    {
        if (is_string($url)) {
            $this->getRequest()->getResponse()->redirect($url);
        } elseif ($url = 404) {
            $this->getRequest()->getResponse()->responseHttpCode($url);
        }
    }

    public function isAllowed(): void
    {
        # FIXME 存储需要验证的URL才合理 放入SESSION不合理
        if (!empty($form_key_paths_str = $this->getSession()->getData('form_key_paths')) && !empty($form_key = $this->getSession()->getData('form_key'))) {
            $form_key_paths = explode(',', $form_key_paths_str);
            if (in_array($this->getRequest()->getUrl(), $form_key_paths) && ($form_key !== $this->getRequest()->getParam('form_key'))) {
                $this->noRouter();
            }
        }
    }

    public function getControllerCache(): CacheInterface
    {
        if (!isset($this->controllerCache)) {
            $this->controllerCache = ObjectManager::getInstance(ControllerCache::class)->create();
        }
        return $this->controllerCache;
    }

    /**
     * 设置
     *
     * @param Template $template
     *
     * @return PcController
     */
    public function setTemplate(Template $template): static
    {
        $this->_template = $template;

        return $this;
    }

    /**
     * @DESC         |获取模板数据
     *
     * 参数区：
     *
     * @param string|null $key
     *
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException
     */
    public function getData(string $key = null): mixed
    {
        return $this->getTemplate()->getData($key);
    }

    /**
     * @DESC         |方法描述
     *
     * 参数区：
     *
     * @return Template
     * @throws Exception
     * @throws \ReflectionException
     */
    public function getTemplate(): Template
    {
        if (!isset($this->_template)) {
            $this->_template = Template::getInstance()->init();
        }
        return $this->_template;
    }

    /**
     * @DESC         |方法描述
     *
     * 参数区：
     *
     * @param array|string      $tpl_var
     * @param array|string|null $value
     *
     * @return PcController
     */
    protected function assign(array|string $tpl_var, mixed $value = null): static
    {
        if (is_string($tpl_var)) {
            $this->getTemplate()->assign($tpl_var, $value);
        }
        if (is_array($tpl_var)) {
            foreach ($tpl_var as $key => $item) {
                $this->getTemplate()->assign($key, $item);
            }
        }

        return $this;
    }

    /**
     * @DESC         |获取模板渲染
     *
     * 参数区：
     *
     * @param string|null $fileName
     *
     * @return mixed
     */
    protected function fetch(string $fileName = null): mixed
    {
        # 如果指定了模板就直接读取
        if ($fileName && is_int(strpos($fileName, '::'))) {
            return $this->getTemplate()->fetch($fileName);
        }
        $controller_class_name = $this->_request->getRouterData('class/controller_name');
        if ($fileName === null) {
            if (in_array(strtoupper($this->_request->getRouterData('class/method')), $this->_request::METHODS)) {
                $fileName = $controller_class_name;
            } else {
                $fileName = $controller_class_name . '/' . $this->_request->getRouterData('class/method');
            }
        } elseif (is_bool(strpos($fileName, '/')) || is_bool(strpos($fileName, '\\'))) {
            $fileName = $controller_class_name . DS . $fileName;
        }
        return $this->getTemplate()->fetch('templates' . DS . $fileName);
    }

    /**
     * 返回JSON
     *
     * @param string $data
     *
     * @return string
     */
    protected function fetchJson(array|bool $data): string
    {
        if (is_bool($data)) {
            if ($data) {
                $data = [
                    'data' => $data,
                    'msg'  => 'sucess',
                    'code' => 200
                ];
            } else {
                $data = [
                    'data' => $data,
                    'msg'  => 'error',
                    'code' => 400
                ];
            }
        }
        return $this->_request->getResponse()->renderJson($data);
    }

    /**
     * @DESC         |按照类型获取view目录
     *
     * 参数区：
     *
     * @return string
     */
    public function getViewBaseDir(): string
    {
        $cache_key = 'module_of_' . $this::class;
        // 设置缓存，以免每次都去反射解析控制器的模块基础目录
        if ($module_dir = $this->getControllerCache()->get($cache_key)) {
            return $module_dir;
        }
        $reflect             = new ReflectionObject($this);
        $filename            = $reflect->getFileName();
        $filename            = str_replace(Env::GENERATED_DIR, 'app', $filename);
        $ctl_dir_reflect_arr = explode(self::dir, $filename);
        $module_dir          = array_shift($ctl_dir_reflect_arr);
        $module_dir          = $module_dir . DataInterface::dir . DS;
        if (!is_dir($module_dir)) {
            mkdir($module_dir, 0775, true);
        }
        $this->getControllerCache()->set($cache_key, $module_dir);

        return $module_dir;
    }

    public function getMessageManager(): MessageManager
    {
        return $this->_objectManager::getInstance(MessageManager::class);
    }


//    #[\JetBrains\PhpStorm\ArrayShape(['msg' => 'string', 'data' => 'mixed|string', 'code' => 'int'])]
//    public function success(string $msg = '请求成功！', mixed $data = '', int $code = 200,string $url=''): array
//    {
//
//        return ['msg' => __($msg), 'data' => $data, 'code' => $code];
//    }
//
//    #[\JetBrains\PhpStorm\ArrayShape(['msg' => 'string', 'data' => 'mixed|string', 'code' => 'int'])]
//    public function error(string $msg = '请求失败！', mixed $data = '', int $code = 404): array
//    {
//        return ['msg' => __($msg), 'data' => $data, 'code' => $code];
//    }
//
//
    public function exception(\Exception $exception, string $msg = '请求异常！', mixed $data = '', int $code = 403): mixed
    {
        if (PROD) {
            return $this->getMessageManager()->addException($exception);
        } else {
            $return_data['data']      = DEV ? $data : '';
            $return_data['exception'] = DEV ? $exception : $exception->getMessage();
            $return_data              = DEV ? json_encode($return_data) : '';
            $msg                      = DEV ? $exception->getMessage() : __($msg);
            $msg_title                = __('消息');
            $data_title               = __('数据');
            $html                     = <<<HTML
$msg_title:$msg,
$data_title:$return_data,
HTML;
            exit($html);
        }
    }
}
