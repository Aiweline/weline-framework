<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\System\File;

use JetBrains\PhpStorm\Pure;
use Weline\Framework\App\Env;
use Weline\Framework\App\Exception;
use Weline\Framework\Http\Request;
use Weline\Framework\Manager\ObjectManager;

class Uploader
{
    private string $media_dir = PUB . 'media' . DS;
    private string $uploader_dir = 'uploader' . DS;
    private string $module_dir = '';
    private string $module_name = '';
    private array $accepted_origins = ['http://localhost', 'http://192.168.1.1', 'http://127.0.0.1'];
    private array $ext = ['gif', 'jpg', 'png', 'jpeg'];

    function checkDomain()
    {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            // 验证来源是否在白名单内
            if (in_array($_SERVER['HTTP_ORIGIN'], $this->accepted_origins)) {
                header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            } else {
                header('HTTP/1.1 403 Origin Denied');
                throw new Exception('Origin Denied');
            }
        }
    }

    /**
     * @DESC          # 添加域名白名单
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/5/3 22:26
     * 参数区：
     *
     * @param array $origin_domains
     *
     * @return $this
     */
    public function addAcceptOriginDomain(array $origin_domains): static
    {
        $this->accepted_origins = array_merge($this->accepted_origins, $origin_domains);
        return $this;
    }

    /**
     * @DESC          # 添加拓展名
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/5/3 22:22
     * 参数区：
     *
     * @param array $extends
     *
     * @return $this
     */
    public function addExt(array $extends): static
    {
        $this->ext = array_merge($this->ext, $extends);
        return $this;
    }

    /**
     * @DESC          # 检测文件名
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2022/5/3 22:20
     * 参数区：
     *
     * @param string $filename
     */
    private function checkFilename(string $filename)
    {
        // 简单的过滤一下文件名是否合格
        if (preg_match('/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/u', $filename)) {
            header('HTTP/1.1 400 Invalid file name.');
            throw new Exception(__('无效文件名。'));
        }

        // 验证扩展名
        if (!in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), $this->ext)) {
            header('HTTP/1.1 400 Invalid extension.');
            throw new Exception(__('无效拓展名。'));
        }
    }

    /**
     * @DESC          # 处理文件
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/10/12 13:10
     * 参数区：
     *
     * @param string $module_name 需要设置上传的目录位置
     * @param string $base_dir
     * @param string $module_dir
     *
     * @return array
     * @throws Exception
     */
    public function saveFiles(string $module_name = '', string $module_dir = '', string $base_dir = ''): array|string
    {
        if (!$module_name) {
            $module_name = $this->getRequest()->getModuleName();
        }
        if (!$module_dir) {
            $module_dir = str_replace('/', DS, $this->getRequest()->getRouterData('class/controller_name'));
        }
        $this->setModuleDir($module_dir);
        if ($base_dir) {
            $this->setBaseUploaderDir($base_dir);
        }
        if ($module_name) {
            $this->setModuleName(str_replace('_', DS, $module_name));
        }
        $result = [];
        if (1 === count($_FILES)) {
            $file     = array_pop($_FILES);
            $filename = $file['name'];
            $result   = $this->saveFile($file['tmp_name'], $filename);
        } else {
            foreach ($_FILES as $FILE) {
                $filename = $FILE['name'];
                $result[] = $this->saveFile($FILE['tmp_name'], $filename);
            }
        }
        return $result;
    }

    function getUploadFilename(string $filename)
    {
        $this->checkFilename($filename);
        if (!str_starts_with($filename, BP)) {
            $filename = $this->getBaseUploaderDir() . $filename;
        }
        return $filename;
    }

    /**
     * @DESC          # 保存文件
     *
     * @AUTH    秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2021/10/12 13:10
     * 参数区：
     *
     * @param string $tmp_file
     * @param string $filename 存储位置
     *
     * @return string
     * @throws Exception
     */
    public function saveFile(string $tmp_file, string $filename): string
    {
        $this->checkDomain();
        $filename = $this->getUploadFilename($filename);
        $dir      = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            chmod($dir, 0777);
        }
        if (move_uploaded_file($tmp_file, $filename)) {
            $filename = str_replace(BP, '', $filename);
            return str_replace('\\', '/', $filename);
        } else {
            throw new Exception(__('文件上传失败:%1 ', $filename));
        }
    }

    /**
     * @return string
     */
    #[Pure] public function getBaseUploaderDir(): string
    {
        return $this->media_dir . rtrim($this->uploader_dir, DS) . DS . ($this->getModuleName() ? $this->getModuleName() . DS : '') . ($this->getModuleDir() ? $this->getModuleDir() . DS : '');
    }

    /**
     * @param string $uploader_dir
     *
     * @return Uploader
     */
    public function setBaseUploaderDir(string $uploader_dir): Uploader
    {
        $this->uploader_dir = rtrim($uploader_dir, DS) . DS;
        return $this;
    }

    /**
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->module_name ?: str_replace('_', DS, $this->getRequest()->getModuleName());
    }

    private function getRequest(): Request
    {
        return ObjectManager::getInstance(Request::class);
    }

    /**
     * @param string $module_name 模块名
     */
    public function setModuleName(string $module_name): Uploader
    {
        $this->module_name = $module_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getModuleDir(): string
    {
        return $this->module_dir;
    }

    /**
     * @param string $module_dir
     */
    public function setModuleDir(string $module_dir): void
    {
        $this->module_dir = $module_dir;
    }

    public function delete(string $filepath)
    {
        $filename = $this->getUploadFilename($filepath);
        if (is_file($filename)) {
            unlink($filename);
        }
        return true;
    }
}
