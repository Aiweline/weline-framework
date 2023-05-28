<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\View;

use Weline\Framework\App\Exception;
use Weline\Framework\Cache\CacheInterface;
use Weline\Framework\Database\AbstractModel;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\View\Cache\ViewCache;

class Block extends Template implements BlockInterface
{
    public ?CacheInterface $_cache = null;
    protected string $_template = '';
    protected bool $is_init = false;
    protected array $action_params = [];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    public function __init()
    {
        parent::__init();
        if (empty($this->_cache)) {
            $this->_cache = ObjectManager::getInstance(ViewCache::class . 'Factory');
        }
        $this->is_init = true;
    }

    public static function getInstance(): Block|Template
    {
        return parent::getInstance();
    }

    /**设置模板文件
     * @throws Exception
     */
    public function setTemplate(string $template = ''): static
    {
        if (empty($template) && isset($this->_template)) {
            $template = $this->_template;
        }
        if (is_bool(strpos($template, '::'))) {
            throw new Exception(__('模板文件设置错误：%1,正确示例：Weline_System::demo.phtml'));
        }
        $template_arr         = explode('::', $template);
        $template_module_name = array_shift($template_arr);
        # 设置模板位置
        $this->setData('template', $template);
        return $this;
    }

    public function getTemplate(): string
    {
        $template = $this->getData('template');
        # 如果未指定模板，并且模板已经设置到Block，则返回已经设置的模板，如果已经指定则返回指定的模板
        if (empty($template) && isset($this->_template)) {
            $template = $this->_template;
        }
        return $template;
    }

    /**
     * @throws \Exception
     */
    public function render(): string
    {
        if (!$this->is_init) {
            throw new Exception(__('检测到Block类未调用父__init()方法：请在当前类（%1）中的__init()函数中添加parent::__init()初始化模板。', $this::class));
        }
        return $this->fetchHtml($this->getTemplate(), ['block' => $this]);
    }

    /**
     * @DESC         |调用模板显示 FIXME 等待抽象出模板引擎的基础类，并继承，解决block模板中使用$this指向block本身类的问题，此方法和模板类效果一样，属于代码冗余
     *
     * 参数区：
     *
     * @param string $fileName   获取的模板名
     * @param array  $dictionary 参数绑定
     *
     * @return bool|void
     * @throws \Exception
     */
    public function fetchHtml(string $fileName, array $dictionary = []): string
    {
        $comFileName = $this->fetchTagSource('blocks', $fileName);
        ob_start();
        try {
            if ($dictionary) {
                $this->addData($dictionary);
            }
            # 将数组存储的变量散列到当前页内存中，使得变量可在页面中暴露出来（可直接使用）
            if ($this->getData()) {
                extract($this->getData(), EXTR_SKIP);
            }
            include $comFileName;
        } catch (\Exception $exception) {
            ob_end_clean();
            throw $exception;
        }
        /** Get output buffer. */
        # FIXME 是否显示模板路径
        return ob_get_clean();
    }

    public function __toString()
    {
        return $this->render();
    }

    public function _getModel(string $model_class, array $data = []): ?AbstractModel
    {
        return ObjectManager::getInstance($model_class, $data);
    }

    /**
     * @DESC          # 获取vars中指定名称的变量系列值 示例：从<block vars='attribute,env' action-params={code:attribute.code,title:env.title}
     * />中获得参数action-params=['code'=>$attribute['code'],'title'=>$env['title']]
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2023/5/15 22:29
     * 参数区：
     * @return array
     * @throws \Weline\Framework\App\Exception
     */
    protected function getParseVarsParams(string $attribute_param_key): array
    {
        if ($action_params = $this->action_params) {
            return $action_params;
        }
        $vars                = $this->getData('vars');
        $attribute_param_key = $this->getData($attribute_param_key);
        if (empty($vars) || empty($attribute_param_key)) {
            return [];
        }
        $action_params_template     = trim($this->getData($attribute_param_key), '{}');
        $action_params_template_arr = explode(',', $action_params_template);
        $action_params              = [];
        foreach ($action_params_template_arr as $action_param) {
            $action_param_arr = explode(':', $action_param);
            if (empty($action_param_arr) || (count($action_param_arr) != 2) || !isset($action_param_arr[1])) {
                throw new \Weline\Framework\App\Exception(__('错误的%1参数格式，正确格式应该是:%2', [$attribute_param_key, $this->doc()]));
            }
            $action_param_name      = trim($action_param_arr[0]);
            $action_param_value_arr = explode('.', $action_param_arr[1]);
            $first_var              = trim(array_shift($action_param_value_arr));
            if (empty($vars[$first_var])) {
                throw new \Weline\Framework\App\Exception(__('参数链%1没有%2参数，确保参数调用链正常！正确格式应该是:%3', [$action_param_arr[1], $first_var,
                                                                                                                         $this->doc()]));
            }
            $action_param_name_var = $vars[$first_var];
            foreach ($action_param_value_arr as $action_param) {
                if (empty($action_param_name_var[$action_param])) {
                    throw new \Weline\Framework\App\Exception(__('参数链%1没有%2参数，确保参数调用链正常！正确格式应该是:%3', [$action_param_arr[1], $action_param,
                                                                                                                             $this->doc()]));
                }
                $action_param_name_var = $action_param_name_var[$action_param];
            }
            $action_params[$action_param_name] = $action_param_name_var;
        }
        return $action_params;
    }
}
