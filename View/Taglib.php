<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\View;

use Weline\Framework\Cache\CacheInterface;
use Weline\Framework\DataObject\DataObject;
use Weline\Framework\Event\EventsManager;
use Weline\Framework\Http\Request;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\View\Cache\ViewCache;
use Weline\Framework\View\Exception\TemplateException;

class Taglib
{
    const operators_symbol = [
        # 比较
        '>',
        '<',
        '===',
        '!==',
        '==',
        '!=',
        '<>',
        '>=',
        '<=>',
        '<=',
        # 逻辑
        '&&',
        '||',
        '!',
        ' and ',
        ' or ',
        ' xor ',
        # 算数运算
        '**',
        '%',
        '/',
        '*',
        '-',
        '+',
    ];

    public function checkFilter(string $name, string $filter = '|', $default = '\'\''): array
    {
        if (str_contains($name, $filter)) {
            $name_arr = explode('|', $name);
            $name     = $name_arr[0];
            $default  = $name_arr[1];
        }
        return [$name, $default];
    }

    public function checkVar(string $name): string
    {
        if (str_starts_with($name, '$')) {
            return $name;
        }
        # 有字母的，且不是字符串，不存在特殊字符内的，可以加$
        $special = ['null'];
        if (preg_match('/^[a-zA-Z]/', $name)) {
            if (!in_array($name, $special) and !str_starts_with($name, '"') and !str_starts_with($name, "'")) {
                $name = $name ? '$' . $name : $name;
            }
        }
        return $name;
    }

    public function varParser(string $name): string
    {
        $name_str = '';
        # 处理过滤器
        list($name, $default) = $this->checkFilter($name);
        # 去除空白以及空格
        $name = $this->checkVar($name);
        $names = explode(' ', $name);
        # 就近原则操作符
//        $near = [];
//        foreach (self::operators_symbol as $symbol) {
//            if ($position = strpos($name, $symbol)) {
//                $near[$position] = $symbol;
//            }
//        }
//        # 数组排序
//        $names = [];
//        foreach ($near as $symbol){
//            $names = array_merge($names,explode($symbol, $name));
//        }
        foreach ($names as $name_key=>$var) {
            # 排除字符串
            if(!str_contains($var, '"')&&!str_contains($var, '\'')){
                $var = $this->checkVar($var);
            }
            $pieces    = explode('.', $var);
            $has_piece = false;
            if(count($pieces) > 1) {
                $name_str .= '(';
                $has_piece = true;
            }
            foreach ($pieces as $key => $piece) {
                if (0 !== $key) {
                    if (str_contains($piece, '$')) {
                        $piece    = '[' . $this->varParser(implode('.', $pieces)) . ']';
                        $name_str .= $piece;
                        break;
                    } else {
                        $piece = '[\'' . $piece . '\']';
                    }
                }
                $name_str .= $piece;
                unset($pieces[$key]);
            }
//            if($has_piece) {
//                $name_str .= ")??{$default}";
//            }
            $name_str = $has_piece ? "{$name_str}??{$default}) " : $name_str . ' ';
//            $name_str = $default ? "({$name_str}?? {$default}) " : ($has_piece ? "({$name_str}??'') " : $name_str . ' ');
        }

        return $name_str;
    }

    public function getTags(Template $template, string $fileName = '', $content = ''): array
    {
        $tags = [
            'php'       => [
                'tag-start' => 1,
                'tag-end'   => 1,
                'callback'  =>
                    function ($tag_key, $config, $tag_data, $attributes) {
                        return match ($tag_key) {
                            'tag-start' => '<?php ',
                            'tag-end'   => '?>',
                            default     => "<?php {$tag_data[1]} ?>"
                        };
                    }
            ],
            'include'   => [
                'tag-start' => 1,
                'tag-end'   => 1,
                'callback'  =>
                    function ($tag_key, $config, $tag_data, $attributes) {
                        return match ($tag_key) {
                            'tag-start' => '<?php include(',
                            'tag-end'   => ');?>',
                            default     => "<?php include({$tag_data[1]});?>"
                        };
                    }
            ],
            'var'       => [
                'tag'      => 1,
                'callback' =>
                    function ($tag_key, $config, $tag_data, $attributes) {
                        switch ($tag_key) {
                            case '@tag()':
                            case '@tag{}':
                                $var_name = $this->varParser($tag_data[1]);
                                return "<?=$var_name?>";
                            default:
                                $var_name = $this->varParser($this->checkVar($tag_data[2]));
                                return "<?=$var_name?>";
                        }
                    }
            ],
            'pp'        => [
                'tag'      => 1,
                'callback' =>
                    function ($tag_key, $config, $tag_data, $attributes) {
                        switch ($tag_key) {
                            case '@tag{}':
                            case '@tag()':
                                $var_name = $tag_data[1];
                                if (!str_starts_with($var_name, '$')) {
                                    $var_name .= '$' . $var_name;
                                }
                                $var_name = $this->varParser($var_name);
                                return "<?=p({$var_name})?>";
                            default:
                                $var_name = $tag_data[2];
                                if (!str_starts_with($var_name, '$')) {
                                    $var_name = '$' . $var_name;
                                }
                                $var_name = $this->varParser($var_name);
                                return "<?=p({$var_name})?>";
                        }
                    }],
            'if'        => [
                'tag-start' => 1,
                'tag-end'   => 1,
                'attr'      => ['condition' => 1],
                'callback'  => function ($tag_key, $config, $tag_data, $attributes) {
                    $result = '';
                    switch ($tag_key) {
                        // @if{$a === 1=><li><var>$a</var></li>|$a===2=><li><var>$a</var></li>}
                        case '@tag{}':
                        case '@tag()':
                            $content_arr = explode('|', $tag_data[1]);
                            foreach ($content_arr as $key => $item) {
                                $content_arr[$key] = explode('=>', $item);
                            }
                            if (1 === count($content_arr)) {
                                $condition = $this->varParser($content_arr[0][0]);
                                $result    = "<?php if({$condition}):echo {$content_arr[0][1]};endif;?>";
                            } else {
                                foreach ($content_arr as $key => $data) {
                                    if (0 === $key) {
                                        $condition = $this->varParser($data[0]);
                                        $result    = "<?php if($condition):echo " . $data[1] . ';';
                                    } else {
                                        if (count($data) > 1) {
                                            $condition = $this->varParser($data[0]);
                                            $result    .= " elseif($condition):echo " . $data[1] . ';';
                                        } else {
                                            $result .= ' else: echo ' . $data[0] . ';';
                                        }
                                    }
                                    if (end($content_arr) === $data) {
                                        $result .= ' endif;?>';
                                    }
                                }
                            }
                            break;
                        case 'tag-self-close-with-attrs':
                            throw new TemplateException(__('if没有自闭合标签。示例：%1', '<if condition="$a>$b"><var>a</var><elseif condition="$b>$a"/><var>b</var><else/><var>a</var><var>b</var></if>'));
                        case 'tag-start':
                            # 排除非if和属性标签的情况
                            if (!str_starts_with($tag_data[0], '<if ')) {
                                $result = $tag_data[0];
                                break;
                            }
                            if (!isset($attributes['condition'])) {
                                if (str_starts_with($tag_data[0], '<if '))
                                    throw new TemplateException(__('if标签缺少condition条件属性，示例：%1', '<if condition="$a>$b"><var>a</var><elseif condition="$b>$a"/><var>b</var><else/><var>a</var><var>b</var></if>'));
                            }
                            $condition = $this->varParser($attributes['condition']);
                            $result    = "<?php if({$condition}):?>";
                            break;
                        case 'tag-end':
                            $result = '<?php endif;?>';
                            break;
                        default:
                    }
                    return $result;
                }],
            'empty'     => [
                'tag'      => 1,
                'tag-end'  => 1,
                'callback' => function ($tag_key, $config, $tag_data, $attributes) use ($template) {
                    switch ($tag_key) {
                        // @empty{$name|<li>空的</li>}
                        case '@tag{}':
                        case '@tag()':
                            $content_arr = explode('|', $tag_data[1]);
                            $name        = $this->varParser($this->checkVar($content_arr[0]));
                            return "<?php if(empty({$name}))echo '" . $template->tmp_replace(trim($content_arr[1] ?? '')) . "'?>";
                        case 'tag':
                            if (!isset($attributes['name'])) {
                                throw new TemplateException(__('empty标签需要设置name属性！例如：%1', htmlentities('<empty name="catalogs"><li>没有数据</li></empty>')));
                            }
                            $name = $this->varParser($this->checkVar($attributes['name']));
                            return '<?php if(empty(' . $name . ')): ?>' . $tag_data[2] . '<?php endif;?>';
                        case 'tag-end':
                            return '<?php endif; ?>';
                        default:
                            return '';
                    }
                }
            ],
            'notempty'  => [
                'tag'      => 1,
                'tag-end'  => 1,
                'callback' => function ($tag_key, $config, $tag_data, $attributes) use ($template) {
                    switch ($tag_key) {
                        // @empty{$name|<li>空的</li>}
                        case '@tag{}':
                        case '@tag()':
                            $content_arr = explode('|', $tag_data[1]);
                            $name        = $this->varParser($this->checkVar($content_arr[0]));
                            return "<?php if(isset($name) && !empty({$name}))echo '" . $template->tmp_replace(trim($content_arr[1] ?? '')) . "'?>";
                        case 'tag':
                            if (!isset($attributes['name'])) {
                                throw new TemplateException(__('empty标签需要设置name属性！例如：%1', htmlentities('<empty name="catalogs"><li>没有数据</li></empty>')));
                            }
                            $name = $this->varParser($this->checkVar($attributes['name']));
                            return '<?php if(isset($name) && !empty(' . $name . ')): ?>' . $tag_data[2] . '<?php endif;?>';
                        case 'tag-end':
                            return '<?php endif; ?>';
                        default:
                            return '';
                    }
                }
            ],
            'elseif'    => [
                'attr'                      => ['condition' => 1],
                'tag-self-close-with-attrs' => 1,
                'callback'                  =>
                    function ($tag_key, $config, $tag_data, $attributes) {
                        $result = '';
                        switch ($tag_key) {
                            // @if{$a === 1=><li><var>$a</var></li>|$a===2=><li><var>$a</var></li>}
                            case '@tag{}':
                            case '@tag()':
                                throw new TemplateException(__('elseif没有@elseif()和@elseif{}用法。示例：%1', '<if condition="$a>$b"><var>a</var><elseif condition="$b>$a"/><var>b</var><else/><var>a</var><var>b</var></if>'));
                            case 'tag-self-close-with-attrs':
                                $condition = $this->varParser($this->checkVar($attributes['condition']));
                                $result    = "<?php elseif({$condition}):?>";
                                break;
                            default:
                        }
                        return $result;
                    }],
            'else'      => [
                'tag-self-close' => 1,
                'callback'       =>
                    function ($tag_key, $config, $tag_data, $attributes) {
                        $result = '';
                        switch ($tag_key) {
                            // @if{$a === 1=><li><var>$a</var></li>|$a===2=><li><var>$a</var></li>}
                            case '@tag{}':
                            case '@tag()':
                                throw new TemplateException(__('elseif没有@elseif()和@elseif{}用法。示例：%1', '<if condition="$a>$b"><var>a</var><elseif condition="$b>$a"/><var>b</var><else/><var>a</var><var>b</var></if>'));
                            // <else/>
                            case 'tag-self-close':
                                $result = '<?php else:?>';
                                break;
                            default:
                        }
                        return $result;
                    }],
            'block'     => [
                'doc'                       => '@block{Weline\Admin\Block\Demo|Weline_Admin::block/demo.phtml}或者@block(Weline\Admin\Block\Demo|Weline_Admin::block/demo.phtml)或者' . htmlentities('<block class="Weline\Admin\Block\Demo" template="Weline_Admin::block/demo.phtml"/>') . '或者' . htmlentities('<block>Weline\Admin\Block\Demo|Weline_Admin::block/demo.phtml</block>'),
                'tag'                       => 1,
                'attr'                      => ['class' => 0, 'template' => 0, 'cache' => 0],
                'tag-self-close-with-attrs' => 1,
                'callback'                  =>
                    function ($tag_key, $config, $tag_data, $attributes) {
                        $result = '';
                        switch ($tag_key) {
                            //<block>Weline\Admin\Block\Demo|template=Weline_Admin::block/demo.phtml|cache=300</block>
                            case 'tag':
//                                throw new TemplateException(
//                                    __(
//                                        'block不支持内容标签：请使用自闭合标签。示例：%1 ',
//                                        [
//                                            htmlentities("<w:block class='Weline\Demo\Block\Demo' template='Weline_Demo::templates/demo.phtml' cache='300'/>")
//                                        ]
//                                    )
//                                );
                                $data   = explode('|', $tag_data[2]);
                                $data   = array_merge($data, $attributes);
                                $result = '<?php echo framework_view_process_block(' . w_var_export($data, true) . ');?>';
                                break;
                            // @block{Weline\Admin\Block\Demo|Weline_Admin::block/demo.phtml}
                            case '@tag{}':
                            case '@tag()':
                                $data = explode('|', $tag_data[1]);
                                if (!isset($data[0]) || !$data[0]) {
                                    throw new TemplateException(
                                        __(
                                            '@block标签语法使用错误：未指定block类。示例：%1或者%2',
                                            ['@block(Weline\Admin\Block\Demo|template=Weline_Admin::block/demo.phtml)', '@block{Weline\Admin\Block\Demo|template=Weline_Admin::block/demo.phtml}']
                                        )
                                    );
                                }
                                $result = '<?php echo framework_view_process_block(' . w_var_export($data, true) . ');?>';
                                break;
                            // <block class='Weline\Demo\Block\Demo' template='Weline_Demo::templates/demo.phtml'/>
                            case 'tag-self-close-with-attrs':
                                if (!isset($attributes['class']) || !$attributes['class']) {
                                    throw new TemplateException(__('block标签语法使用错误：未指定block类。示例：%1', htmlentities("<block class='Weline\Demo\Block\Demo' template='Weline_Demo::templates/demo.phtml' cache='300'/>")));
                                }
                                $result = '<?php echo framework_view_process_block(' . w_var_export($attributes, true) . ');?>';
                                break;
                            default:
                        }
                        return $result;
                    }
            ],
            'foreach'   => [
                'attr'      => ['name' => 1, 'key' => 0, 'item' => 0],
                'tag-start' => 1,
                'tag-end'   => 1,
                'callback'  => function ($tag_key, $config, $tag_data, $attributes) use ($template) {
                    switch ($tag_key) {
                        // @foreach{$name as $key=>$v|<li><var>$k</var>:<var>$v</var></li>}
                        case '@tag{}':
                        case '@tag()':
                            $content_arr = explode('|', $tag_data[1]);
                            $foreach_str = $this->varParser($this->checkVar($content_arr[0]));
                            return "<?php
                        foreach({$foreach_str}){
                        ?>
                            {$template->tmp_replace($content_arr[1]??'')}
                            <?php
                        }
                        ?>";
                        case 'tag-self-close-with-attrs':
                            throw new TemplateException(__('foreach没有自闭合标签。示例：%1', '<foreach name="catalogs" key="key" item="v"><li><var>name</var></li></foreach>'));
                        case 'tag-start':
                            if (!isset($attributes['item'])) {
                                $attributes['item'] = 'v';
                            }
                            if (!isset($attributes['name'])) {
                                throw new TemplateException(__('foreach标签需要指定要循环的变量name属性。例如：需要循环catalogs变量则%1', '<foreach name="catalogs" key="key" item="v"><li><var>name</var></li></foreach>'));
                            }
                            foreach ($attributes as $key => $attribute) {
                                $attributes[$key] = $this->checkVar($attribute);
                            }
                            $vars = $this->varParser($this->checkVar($attributes['name']));
                            $k_i  = isset($attributes['key']) ? $attributes['key'] . ' => ' . $attributes['item'] : $attributes['item'];
                            return "<?php foreach($vars as $k_i):?>";
                        case 'tag-end':
                            return '<?php endforeach;?>';
                        default:
                            return '';
                    }
                }
            ],
            'static'    => [
                'tag'      => 1,
                'callback' =>
                    function ($tag_key, $config, $tag_data, $attributes) use ($template) {
                        return match ($tag_key) {
                            'tag'   => $template->fetchTagSource('statics', trim($tag_data[2])),
                            default => $template->fetchTagSource('statics', trim($tag_data[1]))
                        };
                    }
            ],
            'template'  => [
                'tag'      => 1,
                'callback' =>
                    function ($tag_key, $config, $tag_data, $attributes) use ($template) {
                        return match ($tag_key) {
                            'tag'   => file_get_contents($template->fetchTagSource(\Weline\Framework\View\Data\DataInterface::dir_type_TEMPLATE, trim($tag_data[2]))),
                            default => file_get_contents($template->fetchTagSource(\Weline\Framework\View\Data\DataInterface::dir_type_TEMPLATE, trim($tag_data[1])))
                        };
                    }
            ],
            'js'        => [
                'tag'      => 1,
                'callback' =>
                    function ($tag_key, $config, $tag_data, $attributes) use ($template) {
                        return match ($tag_key) {
                            'tag'   => "<script {$tag_data[1]} src='{$template->fetchTagSource(\Weline\Framework\View\Data\DataInterface::dir_type_STATICS, trim($tag_data[2]))}'></script>",
                            default => "<script src='{$template->fetchTagSource(\Weline\Framework\View\Data\DataInterface::dir_type_STATICS, trim($tag_data[1]))}'></script>"
                        };
                    }
            ],
            'css'       => [
                'tag'      => 1,
                'callback' =>
                    function ($tag_key, $config, $tag_data, $attributes) use ($template) {
                        return match ($tag_key) {
                            'tag'   => "<link {$tag_data[1]} href='{$template->fetchTagSource(\Weline\Framework\View\Data\DataInterface::dir_type_STATICS, trim($tag_data[2]))}' rel=\"stylesheet\" type=\"text/css\"/>",
                            default => "<link href='{$template->fetchTagSource(\Weline\Framework\View\Data\DataInterface::dir_type_STATICS, trim($tag_data[1]))}' rel=\"stylesheet\" type=\"text/css\"/>"
                        };
                    }
            ],
            'lang'      => [
                'tag'      => 1,
                'callback' =>
                    function ($tag_key, $config, $tag_data, $attributes) {
                        return match ($tag_key) {
                            'tag'   => __($tag_data[2]),
                            default => __($tag_data[1])
                        };
                    }
            ],
            'url'       => [
                'tag'       => 1,
                'tag-start' => 1,
                'tag-end'   => 1,
                'callback'  =>
                    function ($tag_key, $config, $tag_data, $attributes) use ($template) {
                        $result = '';
                        switch ($tag_key) {
                            case 'tag':
                                $data = explode('|', $tag_data[2]);
                                $var  = $data[0] ?? '';
                                $var  = trim($var, "'\"");
                                $var  = str_replace(' ', '', $var);
                                if (isset($data[1]) && $arr_str = $data[1]) {
                                    $result .= "<?=\$this->getUrl('{$var}',{$arr_str})?>";
                                } else {
                                    $result .= "<?=\$this->getUrl('{$var}')?>";
                                }
                                break;
                            case  'tag-start':
                                $result .= "<?=\$this->getUrl(";
                                break;
                            case 'tag-end':
                                $result .= ")?>";
                                break;
                            default:
                                $data   = str_replace(' ', '', $tag_data[1]);
                                $result .= "<?=\$this->getUrl({$data})?>";
                        };
                        return $result;
                    }
            ],
            'admin-url' => [
                'tag'       => 1,
                'tag-start' => 1,
                'tag-end'   => 1,
                'callback'  =>
                    function ($tag_key, $config, $tag_data, $attributes) use ($template) {
                        switch ($tag_key) {
                            case 'tag':
                                $data = $this->varParser(str_replace(' ', '', $tag_data[2]));
                                if (str_starts_with($data, '"') || str_starts_with($data, "'")) {
                                    return "<?=\$this->getAdminUrl({$data})?>";
                                } else {
                                    return "<?=\$this->getAdminUrl({$this->varParser($data)})?>";
                                }
                            case 'tag-start':
                                return "<?=\$this->getAdminUrl(";
                            case 'tag-end':
                                return ")?>";
                            default:
                                $data = str_replace(' ', '', $tag_data[1]);
                                if (str_starts_with($data, '"') || str_starts_with($data, "'")) {
                                    return "<?=\$this->getAdminUrl({$data})?>";
                                } else {
                                    return "<?=\$this->getAdminUrl({$this->varParser($data)})?>";
                                }
                        }
                    }
            ],
            'hook'      => [
                'tag'      => 1,
                'callback' =>
                    function ($tag_key, $config, $tag_data, $attributes) {
                        return match ($tag_key) {
                            'tag'   => "<?=\$this->getHook('" . trim($tag_data[2]) . "')?>",
                            default => "<?=\$this->getHook('" . trim($tag_data[1]) . "')?>"
                        };
                    }
            ],
            'string'    => [
                'tag'      => 1,
                'callback' =>
                    function ($tag_key, $config, $tag_data, $attributes) {
                        switch ($tag_key) {
                            case 'tag':
                                $string  = $tag_data[2];
                                $str_arr = explode('|', $string);
                                $str_var = $this->varParser($this->checkVar(array_shift($str_arr)));
                                $str_len = intval(array_shift($str_arr));

                                return "<?php if({$str_var}&&$str_len>0 && strlen({$str_var})>{$str_len}){
                                    echo mb_substr({$str_var},0,{$str_len},'UTF8').'...';
                                }else{
                                echo {$str_var};
                                }?>";
                            default:
                                $string  = $tag_data[1];
                                $str_arr = explode('|', $string);
                                $str_var = $this->checkVar(array_shift($str_arr));
                                $str_len = intval(array_shift($str_arr));

                                return "<?php if($str_len>0 && strlen({$str_var})>{$str_len}){
                                    echo mb_substr({$str_var},0,{$str_len},'UTF8').'...';
                                }else{
                                echo {$str_var};
                                }?>";
                        }
                    }
            ],
        ];
        # 兼容自定义tag
        /**@var EventsManager $event */
        $event = ObjectManager::getInstance(EventsManager::class);
        $data  = (new DataObject(['template' => $template, 'tags' => $tags]));
        $event->dispatch('Framework_Template::after_tags_config', ['data' => $data, 'Taglib' => $this]);
        $tags = $data->getData('tags');
        # 构造w:tag
        foreach ($tags as $tag => $tag_data) {
            $tags["w:$tag"] = $tag_data;
        }
        return $tags;
    }

    public function tagReplace(Template &$template, string &$content, string &$fileName = '')
    {
        # FIXME 可以将所有标签相对应的正则一次性存到属性，节省循环调用多次处理的时间
        # 替换{{key}}标签
        preg_match_all("/\{\{([\s\S]*?)\}\}/", $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $key => $value) {
            $content = str_replace($value[0], "<?={$this->varParser(trim($value[1]))};?>", $content);
        }
        # 系统自带的标签
        $tags = $this->getTags($template, $fileName, $content);
        foreach ($tags as $tag => $tag_configs) {
            $tag_patterns = [
                'tag-self-close-with-attrs' => '/<' . $tag . '([\s\S]*?)\/>/m',
                'tag'                       => '/<' . $tag . '([\s\S]*?)>([\s\S]*?)<\/' . $tag . '>/m',
                'tag-start'                 => '/<' . $tag . '([\s\S]*?)>/m',
                'tag-end'                   => '/<\/' . $tag . '>/m',
                'tag-self-close'            => '/<' . $tag . '\/>/m',
                '@tag()'                    => '/\@' . $tag . '\(([\s\S]*?)\)/m',
                '@tag{}'                    => '/\@' . $tag . '\{([\s\S]*?)\}/m',
            ];
            # 检测标签所需要的元素，不需要的就跳过
            foreach ($tag_patterns as $tag_key => $tag_pattern) {
                if (str_starts_with($tag_key, 'tag') && !isset($tag_configs[$tag_key])) {
                    unset($tag_patterns[$tag_key]);
                }
            }
            # 匹配标签所需处理的tag
            $tag_config_patterns = [];
            foreach ($tag_configs as $config_name => $tag_config) {
                if (str_starts_with($config_name, 'tag') && $tag_config) {
                    $tag_config_patterns[$config_name] = $tag_patterns[$config_name];
                }
            }
//            foreach ($tag_config_patterns as &$tag_config_pattern) {
//                $tag_config_pattern = htmlentities($tag_config_pattern);
//            }
//            if($tag=='if'){
//                p( $tag_config_patterns);
//            }
            # 默认匹配@tag()和@tag{}
            $tag_config_patterns['@tag()'] = $tag_patterns['@tag()'];
            $tag_config_patterns['@tag{}'] = $tag_patterns['@tag{}'];

            # 标签验证测试
//            if('var'===$tag){
//                foreach ($tag_config_patterns as &$tag_config_pattern) {
//                    $tag_config_pattern = htmlentities($tag_config_pattern);
//                }
//                p($tag_config_patterns);
//            }
            # 匹配处理
            $format_function = $tag_configs['callback'];
            foreach ($tag_config_patterns as $tag_key => $tag_pattern) {
                preg_match_all($tag_pattern, $content, $customTags, PREG_SET_ORDER);
                foreach ($customTags as $customTag) {
                    $originalTag = $customTag[0];
                    if (isset($customTag[1])) {
                        $customTag[1] = str_replace(PHP_EOL, '', $customTag[1]);
                        $customTag[1] = str_replace(array("\r\n", "\r", "\n", "\t"), '', $customTag[1]);
                    }
                    $rawAttributes = $customTag[1] ?? '';
                    # 如果有属性接下来的字母就不会和标签紧贴着，而如果没有属性那么应该是>括号和标签紧贴着，如果都不是说明并非tag标签
                    if ($rawAttributes && (
                            'tag' === $tag_key ||
                            'tar-start' === $tag_key ||
                            'tag-self-close-with-attrs' === $tag_key ||
                            'tag-self-close' === $tag_key
                        ) && !str_starts_with($rawAttributes, ' ')) {
                        continue;
                    }

                    if (isset($customTag[2])) {
                        $customTag[2] = str_replace(PHP_EOL, '', $customTag[2]);
                        $customTag[2] = str_replace(array("\r\n", "\r", "\n", "\t"), '', $customTag[2]);
                    }
                    # 标签支持匹配->
                    $customTag[1]       = $rawAttributes;
                    $formatedAttributes = array();
                    # 兼容：属性值单双引号
                    preg_match_all("/(\S*?)='([\s\S]*?)'/", $rawAttributes, $attributes, PREG_SET_ORDER);
                    foreach ($attributes as $attribute) {
                        if (isset($attribute[2])) {
                            $attr                      = trim($attribute[1]);
                            $formatedAttributes[$attr] = trim($attribute[2]);
                        }
                    }
                    preg_match_all('/(\S*?)="([\s\S]*?)"/', $rawAttributes, $attributes, PREG_SET_ORDER);
                    foreach ($attributes as $attribute) {
                        if (isset($attribute[2])) {
                            $attr                      = trim($attribute[1]);
                            $formatedAttributes[$attr] = trim($attribute[2]);
                        }
                    }

//                    if($tag_key==='tag-self-close-with-attrs'&&$tag==='block') {
//                        p( $rawAttributes,1);
//                        p( $attributes);
//                        if(str_contains($rawAttributes, "item='sub_menu'")){
//                            p( $formatedAttributes,1);
//                            p( $attributes);
//                        };
//                    }
                    # 验证标签属性
                    $attrs = $tag_configs['attr'] ?? [];
                    if ($attrs && ('tar-start' === $tag_key || 'tag-self-close-with-attrs' === $tag_key)) {
                        $attributes_keys = array_keys($formatedAttributes);
                        foreach ($attrs as $attr => $required) {
                            if ($required && !in_array($attr, $attributes_keys)) {
                                $provide_attr = implode(',', $attributes_keys);
                                throw new TemplateException(__('%1:标签必须设置属性%2, 提供的属性：3% 文件：%4', [$tag, $attr, $provide_attr, $fileName]));
                            }
                        }
                    }
                    $content = str_replace($originalTag, $format_function($tag_key, $tag_configs, $customTag, $formatedAttributes), $content);
                }
            }
        }
        return $content;
    }

}
