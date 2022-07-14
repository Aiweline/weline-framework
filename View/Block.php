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
use Weline\Framework\DataObject\DataObject;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\View\Cache\ViewCache;

class Block extends Template implements BlockInterface
{
    public ?CacheInterface $_cache = null;
    protected string $_template = '';
    protected bool $is_init = false;

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

    static function getInstance(): Block|Template
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
    public function render()
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
    public function fetchHtml(string $fileName, array $dictionary = [])
    {
        $comFileName = $this->fetchTagSource('templates', $fileName);
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
}
