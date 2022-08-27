<?php

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\System\File;

use Symfony\Component\Finder\Finder;
use Weline\Framework\Register\RegisterInterface;
use Weline\Framework\System\File\Data\File;

class Scan
{
    private array $dirs = [];

    private int $keepLevel = 0;

    /**
     * @DESC         |初始化
     *
     * 参数区：
     */
    public function __init()
    {
        $this->dirs      = [];
        $this->keepLevel = 0;
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
     * @param string $dirPath
     * @param int    $level
     *
     * @return array
     */
    public function scanDirTree(string $dirPath, int $level = 0): array
    {
        $this->keepLevel += 1;
        $dirPath         = rtrim($dirPath, DS);
        if (is_dir($dirPath) && $file_handler = opendir($dirPath)) {
            while (false !== ($file = readdir($file_handler))) {
                // 排除"."".."
                if ($file !== '.' && $file !== '..') {
                    $filename       = $dirPath . DS . $file;
                    $relateFilename = str_replace(APP_CODE_PATH, '', $filename);
                    if (is_int(strpos($filename, VENDOR_PATH))) {
                        $relateFilename = str_replace(VENDOR_PATH, '', $filename);
                    }
                    if (IS_WIN) {
                        $relateFilename = str_replace('/', DS, $relateFilename);
                    }
                    if (is_dir($filename)) {
                        // 目录层级：是否扫描
                        if ($level) {
                            if ($this->keepLevel < $level) {
                                $this->scanDirTree($filename, $level);//递归调用;
                            }
                        } else {
                            // 扫描全部目录
                            $this->scanDirTree($filename);
                        }
                    } else {
                        // 文件
                        $file     = new File();
                        $pathInfo = pathinfo($filename);
                        $file->setBasename($pathInfo['basename']);
                        $file->setFilename($pathInfo['filename']);
                        $file->setDirname($pathInfo['dirname']);
                        $file->setExtension($pathInfo['extension'] ?? '');
                        $file->setOrigin($filename);
                        $file->setNamespace(str_replace('/', '\\', dirname($relateFilename)));
                        $file->setRelate($relateFilename);
                        $file->setSize(filesize($filename));
                        $file->setType(filetype($filename));
                        $this->dirs[dirname($relateFilename)][] = $file;
                    }
                }
            }
        }

        return $this->dirs;
    }

    /**
     * @DESC         |扫描目录
     *
     * @Author       秋枫雁飞
     * @Email        aiweline@qq.com
     * @Forum        https://bbs.aiweline.com
     * @Description  此文件源码由Aiweline（秋枫雁飞）开发，请勿随意修改源码！
     *
     * 参数区：
     *
     * @param string $dirPath
     *
     * @return array
     */
    public function scanDir(string $dirPath): array
    {
        if (!is_dir(rtrim($dirPath, DS))) {
            return [];
        }
        if ($this->dirs = (scandir($dirPath)) ? scandir($dirPath) : []) {
            // 排除"."".."
            array_shift($this->dirs);
            array_shift($this->dirs);
        }

        return $this->dirs;
    }

    public function dirToArray($dir)
    {
        $contents = [];
        # Foreach node in $dir
        foreach (scandir($dir) as $node) {
            # Skip link to current and parent folder
            if ($node === '.') {
                continue;
            }
            if ($node === '..') {
                continue;
            }
            # Check if it's a node or a folder
            if (is_dir($dir . DS . $node)) {
                # Add directory recursively, be sure to pass a valid path
                # to the function, not just the folder's name
                $contents[$node] = $this->dirToArray($dir . DS . $node);
            } else {
                # Add node, the keys will be updated automatically
                $contents[] = $node;
            }
        }
        # done
        return $contents;
    }

    function globFile($pattern_dir, &$files=[], string $ext = '.php', string $remove_path = '',string $replace_path='', bool $remove_ext = false,
                      bool $class_path = false)
    {
        foreach (glob($pattern_dir) as $file) {
            if (is_dir($file)) {
                $this->globFile($file . DS . '*', $files,$ext, $remove_path,$replace_path, $remove_ext, $class_path);
            }
            if (str_ends_with($file, $ext)) {
                if ($remove_path) {
                    $file = str_replace($remove_path, $replace_path, $file);
                }
                if ($remove_ext) {
                    $file = str_replace($ext,'',$file );
                    $file = str_replace(strtoupper($ext),'',$file );
                }
                if ($class_path) {
                    $file = str_replace(DS, '\\', $file);
                }
                $files[] = $file;
            }
        }
        return $files;
    }
}
