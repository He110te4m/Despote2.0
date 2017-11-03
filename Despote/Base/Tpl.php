<?php
/**
 * 模板引擎
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace Despote\Base;

class Tpl
{
    public $tplVars = [];

    public static $tpls;

    private $binds;

    private $instances;

    public static function getInstance($tplName = 'default')
    {
        if (!isset(self::$tpls[$tplName]) || empty($tplName)) {
            $class                = __CLASS__;
            $tplName              = empty($tplName) ? 'default' : $tplName;
            self::$tpls[$tplName] = new $class;
            return self::$tpls[$tplName];
        } else {
            return self::$tpls[$tplName];
        }
    }

    /**
     * 动态创建方法对私有属性读写，如：
     * getdirs()：获取 dirs 的值
     * setdirs('./tpl/')：修改 dirs 的值
     * 方法调用失败返回 false
     */
    public function __call($method, $args)
    {
        // 获取操作与属性
        $operate  = substr($method, 0, 3);
        $propName = substr($method, 3);

        // 操作与属性都不能为空，为空返回 false
        if (empty($operate) || empty($propName)) {
            return false;
        }

        // 根据操作名执行对应操作
        if ($operate == 'set') {
            return $this->$propName = $args[0];
        } else if ($operate == 'get') {
            return isset($this->$propName) ? $this->$propName : '';
        }
    }

    /**
     * 加载配置
     * @param  Array $setting 配置导入
     */
    public function config($setting = null)
    {
        // 字符编码
        $this->charset = (!empty($setting['charset'])) ? $setting['charset'] : null;
        // 模板开始标记
        $this->tplbegin = (!empty($setting['tplbegin'])) ? $setting['tplbegin'] : '<{';
        // 模板结束标记
        $this->tplend = (!empty($setting['tplend'])) ? $setting['tplend'] : '}>';
        // 模板存放路径
        $this->tpldir = (!empty($setting['tpldir'])) ? $setting['tpldir'] : './templates';
        // 模板缓存路径
        $this->cachedir = (!empty($setting['cachedir'])) ? $setting['cachedir'] : './templates_c';
        // 模板后缀
        $this->suffix = (!empty($setting['suffix'])) ? $setting['suffix'] : 'tpl';
        // 强制编译页面,调试时使用,每次执行,模版文件将会强制编译,true为开启，false为关闭，默认关闭,此项不可随意开启,太消耗资源
        $this->tempopen = (!empty($setting['tempopen'])) ? $setting['tempopen'] : false;
        // 定义模版引用方式,为true则采用实时编译，当模板文件改变，引用此模板文件的文件将进行重新编译
        $this->includeopen = (!empty($setting['includeopen'])) ? $setting['includeopen'] : true;

        // 链式操作
        return $this;
    }

    /**
     * 获取缓存文件路径
     * @param  String $filename 文件名
     * @return String           缓存的路径名
     */
    private function getCacheUrl($filename)
    {
        return $this->cachedir . '/' . $filename . '.php';
    }
    //获取模版文件路径
    private function getTplUrl($filename)
    {
        return $this->tpldir . '/' . $filename . '.' . $this->suffix;
    }
    /**
     * 判断是否需要编译
     * @param  String $tplUrl   模板文件路径
     * @param  String $cacheUrl 缓存文件路径
     * @return Boolean          如果需要编译返回 true,不需要不返回
     */
    private function shouldCache($tplUrl, $cacheUrl)
    {
        if (!is_readable($tplUrl)) {
            $this->showMessages('Template file not readable to ' . $tplUrl);
        }
        if ($this->tempopen or !is_file($cacheUrl)) {
            return true;
        }
        if (filemtime($tplUrl) > filemtime($cacheUrl)) {
            return true;
        }
    }

    /**
     * 编译并写入到缓存
     * @param  String $tplUrl   模板文件路径
     * @param  String $cacheUrl 缓存文件路径
     */
    private function cache($tplUrl, $cacheUrl)
    {
        if ($this->shouldCache($tplUrl, $cacheUrl)) {
            $this->writeFile($cacheUrl, $this->compile($tplUrl));
        }
    }

    /**
     * 导入文件
     * @param  String $tplUrl   模板文件路径
     * @param  String $cacheUrl 缓存文件路径
     * @return String           缓存文件路径
     */
    private function linkTo($tplUrl, $cacheUrl)
    {
        $this->cache($tplUrl, $cacheUrl);
        return $cacheUrl;
    }

    /**
     * 自带正则匹配
     * @param  String $tplUrl 模板文件路径
     * @return String         编译后的文件内容
     */
    private function compile($tplUrl)
    {
        // 获取定界符
        $tplbegin = self::quote($this->tplbegin);
        $tplend   = self::quote($this->tplend);
        // 优先进行自定义正则处理
        $content = $this->place(file_get_contents($tplUrl));

        ////////////////////
        // 开始系统默认处理 //
        ///////////////////

        // 处理文件包含
        if (strpos($content, $this->tplbegin . 'include') !== false) {
            // 构造包含文件正则表达式
            $linkRegular = '/' . $tplbegin . 'include\s+file\s*=\s*["](.+?)["]' . $tplend . '/i';
            // 匹配所有文件包含语句
            if (preg_match_all($linkRegular, $content, $includes)) {
                // 获取全匹配模式的值，即：标签头include file="文件名"标签尾 这种形式，获取后去除重复引用项
                $includes[1] = array_unique($includes[1]);

                // 遍历引用语句
                foreach ($includes[1] as $file) {
                    // 模板中的包含语句
                    $str = $this->tplbegin . 'include file="' . $file . '"' . $this->tplend;

                    // 获取文件对应的模板路径
                    $tpl = $this->getTplUrl($file);

                    // 判断被引用文件是否编译，没有则引用模板，有则使用缓存
                    if (is_file($tpl)) {
                        $cache = $this->cachedir . '/' . $file . '.php';
                    } else {
                        $tpl = $this->tpldir . '/' . $file . '.' . $this->suffix;

                        // 判断文件是否存在
                        if (is_file($tpl)) {
                            $cache = $this->cachedir . '/' . $file . '.php';
                        } else {
                            $cache = null;
                        }
                    }

                    // 判断引用文件是否存在
                    if ($cache) {
                        if ($this->includeopen) {
                            $regular = '<?php if($this->linkTo(\'' . $tpl . '\',' . '\'' . $cache . '\')){ require(\'' . $cache . '\'); } ?>';
                        } else {
                            $this->cache($tpl, $cache);
                            $regular = "<?php\r\nrequire('{$cache}');\r\n?>";
                        }
                    } else {
                        if (is_file($file)) {
                            $regular = "<?php\r\nrequire('{$file}');\r\n?>";
                        } else {
                            $regular = null;
                        }

                    }
                    $content = str_ireplace($str, $regular, $content);
                }
            }
        }

        // 处理 else 语句
        $elseRegular = $this->tplbegin . 'else' . $this->tplend;
        if (strpos($content, $elseRegular)) {
            $else_rep = "<?php\r\n}else{\r\n?>";
            $content  = str_ireplace($elseRegular, $else_rep, $content);
        }

        // 处理 if、for、foreach、while、end 语句结束标志
        $tagEndRegular = '/' . $tplbegin . '\/(if|for|foreach|while|end)' . $tplend . '/i';
        if (preg_match_all($tagEndRegular, $content, $endTags)) {
            $endTags[0] = array_unique($endTags[0]);
            foreach ($endTags[0] as $tagEnd) {
                $content = str_replace($tagEnd, '<?php } ?>', $content);
            }
        }

        // 处理 if 标签
        if (strpos($content, $this->tplbegin . 'if') !== false) {
            $ifRegular = '/' . $tplbegin . 'if (.*)' . $tplend . '/isU';
            if (preg_match_all($ifRegular, $content, $vars)) {
                foreach ($vars[1] as $key => $value) {
                    $values  = $this->parseVars($value);
                    $content = str_replace($vars[0][$key], '<?php if(' . $values . ') { ?>', $content);
                }
            }
        }

        // 处理 else if 标签
        if (strpos($content, $this->tplbegin . 'elseif') !== false) {
            $elseifRegular = '/' . $tplbegin . 'elseif (.*)' . $tplend . '/isU';
            if (preg_match_all($elseifRegular, $content, $vars)) {
                foreach ($vars[1] as $key => $value) {
                    $values  = $this->parseVars($value);
                    $content = str_replace($vars[0][$key], '<?php } else if(' . $values . ') { ?>', $content);
                }
            }
        }

        // 处理 foreach 标签
        if (strpos($content, $this->tplbegin . 'foreach') !== false) {
            $foreachRegular = '/' . $tplbegin . 'foreach (.*)' . $tplend . '/isU';
            if (preg_match_all($foreachRegular, $content, $vars)) {
                foreach ($vars[1] as $key => $value) {
                    if (strpos($value, ' as') === false) {
                        $value .= ' as $key=>$value';
                    }

                    $values  = $this->parseVars($value);
                    $content = str_replace($vars[0][$key], '<?php foreach(' . $values . ') { ?>', $content);
                }
            }
        }

        // 处理 for 标签
        if (strpos($content, $this->tplbegin . 'for') !== false) {
            $forRegular = '/' . $tplbegin . 'for (.*)' . $tplend . '/isU';
            if (preg_match_all($forRegular, $content, $vars)) {
                foreach ($vars[1] as $key => $value) {
                    $values  = $this->parseVars($value);
                    $content = str_replace($vars[0][$key], '<?php for(' . $values . ') { ?>', $content);
                }
            }
        }

        // 处理 while 标签
        if (strpos($content, $this->tplbegin . 'while') !== false) {
            $whileRegular = '/' . $tplbegin . 'while (.*)' . $tplend . '/isU';
            if (preg_match_all($whileRegular, $content, $vars)) {
                foreach ($vars[1] as $key => $value) {
                    $values  = $this->parseVars($value);
                    $content = str_replace($vars[0][$key], '<?php while(' . $values . ') { ?>', $content);
                }
            }
        }

        // 分配变量
        $assignRegular = '/' . $tplbegin . '(((\$|\@)[\w\.\[\]\$]+)=\s*([\'"].+?[\'"]|.+?))' . $tplend . '/';
        if (preg_match_all($assignRegular, $content, $vars)) {
            foreach ($vars[0] as $key => $value) {
                $rep     = '<?php ' . $this->parseVars($vars[1][$key]) . '; ?>';
                $content = str_replace($value, $rep, $content);
            }
        }

        // 处理不输出的语句
        $varc_regular = '/' . $tplbegin . '\!(.*)' . $tplend . '/isU';
        if (preg_match_all($varc_regular, $content, $vars)) {
            foreach ($vars[1] as $key => $value) {
                $values  = $this->parseVars($value);
                $content = str_replace($vars[0][$key], '<?php ' . $values . ';?>', $content);
            }
        }

        // 处理输出的语句
        $var_regular = '/' . $tplbegin . '(.*)' . $tplend . '/U';
        if (preg_match_all($var_regular, $content, $vars)) {
            foreach ($vars[1] as $key => $value) {
                $values  = $this->parseVars($value);
                $content = str_replace($vars[0][$key], '<?= ' . $values . ';?>', $content);
            }
        }

        // 返回文件内容
        return $content;
    }

    /**
     * 模版输出
     * @param  String $filename 文件名
     * @return String            模板缓存地址
     */
    public function display($filename)
    {
        $tplUrl   = $this->getTplUrl($filename);
        $cacheUrl = $this->getCacheUrl($filename);
        $this->cache($tplUrl, $cacheUrl);
        // 如果设置了字符编码，就设置文件头
        $this->charset && header('Content-Type:text/html;charset=' . $this->charset);

        require $cacheUrl;
    }

    /**
     * 获取内存使用
     * @return String 内存占用信息，单位为 MB
     */
    public static function cpu()
    {
        $memory = (!function_exists('memory_get_usage')) ? '0' : round(memory_get_usage() / 1024 / 1024, 5);
        return $memory . ' MB';
    }

    /**
     * 获取模版内容
     * @param  String $tplUrl 模板文件路径
     * @return String         模板文件内容
     */
    private function getContents($tplUrl)
    {
        $content = file_get_contents($tplUrl);
        return $content;
    }

    /**
     * 自定义的正则编译
     * @param  String $content 需要处理的内容
     * @return String          使用用户自定义正则处理后的内容
     */
    private function place($content)
    {
        if (is_array($this->instances) && count($this->instances) >= 1) {
            $regulars = $replaces = [];
            foreach ($this->instances as $regular => $replace) {
                $regulars[] = $regular;
                $replaces[] = $replace;
            }
            $content = preg_replace($regulars, $replaces, $content);
        }

        if (is_array($this->binds) && count($this->binds) >= 1) {
            foreach ($this->binds as $regular => $replace) {
                $content = preg_replace_callback($regular, $replace, $content);
            }
        }

        return $content;
    }

    //参数绑定判断
    public function binds($abstract, $concrete)
    {
        if ($concrete instanceof \Closure) {
            $this->binds[$abstract] = $concrete;
        } else {
            $this->instances[$abstract] = $concrete;
        }
    }

    //参数绑定
    public function bind($abstract, $concrete = ' ')
    {
        if (is_array($abstract)) {
            foreach ($abstract as $key => $value) {
                $this->binds($key, $value);
            }
        } else {
            $this->binds($abstract, $concrete);
        }
    }

    /**
     * 模版变量赋值
     * @param  Array  $vars   变量名，也可传入键值对，省略变量值数组
     * @param  Array  $values 变量值数组，和变量名数组一一对应
     */
    public function assign($vars, $values = null)
    {
        if (is_array($vars)) {
            foreach ($vars as $key => $val) {
                if ($key != null) {
                    $this->tplVars[$key] = $val;
                }

            }
        } else {
            if ($vars != null) {
                if ($values != null) {
                    $this->tplVars[$vars] = $values;
                } else {
                    $this->tplVars['var'] = $vars;
                }

            }
        }
    }

    /**
     * 正则字符串转义
     * @param  String $regular 正则表达式
     * @return String          转义后的正则表达式
     */
    private static function quote($regular)
    {
        return preg_quote($regular, '/');
    }

    /**
     * 变量解析
     * @param  String $content 模板内容
     * @return String          变量解析后的模板内容
     */
    private function parseVars($content)
    {
        $vars = [
            '$post'    => '$_POST',
            '$get'     => '$_GET',
            '$cookie'  => '$_COOKIE',
            '$session' => '$_SESSION',
            '$files'   => '$_FILES',
            '$server'  => '$_SERVER',
            '$this'    => '$this',
            '$cpu'     => 'self::cpu()',
        ];

        // 匹配一维数组的 数组名.下标
        if (preg_match_all('/\$(\w+)\.(\w+)/', $content, $results)) {
            foreach ($results[2] as $key => $value) {
                $content = preg_replace('/\.' . $value . '/', '[\'' . $value . '\']', $content);
            }
        }

        // 匹配常规变量与数组
        if (preg_match_all('/\$(\w+)/', $content, $results)) {
            foreach ($results[0] as $key => $value) {
                if (array_key_exists($value, $vars)) {
                    $rep = $vars[$value];
                } else {
                    $rep = $value;
                }
                $content = preg_replace('/' . self::quote($value) . '/', $rep, $content, 1);
            }
        }

        $content = $this->parseInternalVar($content);

        return $content;
    }

    /**
     * 模板类内部变量解析
     * @param  String $content 模板内容
     * @return String          解析完内部变量的模板内容
     */
    private function parseInternalVar($content)
    {
        // 匹配一维数组的 数组名.下标
        if (preg_match_all('/\@(\w+)\.(\w+)/', $content, $arr)) {
            foreach ($arr[2] as $key => $value) {
                $content = preg_replace('/\.' . $value . '/', '[\'' . $value . '\']', $content);
            }
        }

        // 匹配常规变量、数组
        if (preg_match_all('/\@(\w+)/', $content, $vars)) {
            foreach ($vars[0] as $key => $value) {
                $rep     = '$this->tplVars[\'' . $vars[1][$key] . '\']';
                $content = preg_replace('/' . self::quote($value) . '/', $rep, $content, 1);
            }
        }

        return $content;
    }

    /**
     * 创建目录或者文件
     * @param  String  $dir  文件或目录路径
     * @param  boolean $file 是否为文件
     * @param  integer $mode 文件/目录的权限
     * @return Boolean       创建成功返回 true，失败不返回
     */
    public function createdir($dir, $file = false, $mode = 0775)
    {
        // 统一路径分隔符
        $path = str_replace("\\", "/", $dir);
        if (is_dir($path) && $file == false) {
            return true;
        }

        if ($file) {
            // 文件存在，直接返回 true
            if (is_file($path)) {
                return true;
            }

            // 获取文件所在目录
            $tmps = explode('/', $path);
            array_pop($tmps);
            $file_path = implode('/', $tmps);
        }

        // 创建目录，并赋予权限
        $mdir = isset($file_path) ? $file_path : $path;
        if (!is_dir($mdir)) {
            @mkdir($mdir, $mode, true);
            @chmod($mdir, $mode);
        }

        // 如果是文件，则创建文件
        if ($file) {
            $fileHandle = @fopen($path, 'a');
            if ($fileHandle) {
                fclose($fileHandle);
                return true;
            }
        }
    }

    /**
     * 写入缓存
     * @param  String $filePath    缓存目录/文件路径
     * @param  String $content 缓存内容
     */
    private function writeFile($filePath, $content)
    {
        $cacheUrl = $filePath;
        if ($this->createdir($cacheUrl, true) == false && is_readable($cacheUrl) == false) {
            $this->error('Warning: file generation fails, check permissions to' . $cacheUrl);
        }

        // 访问校验
        $content = "<?php\r\n if(!defined('DESPOTE')){\r\n die('Forbidden access');\r\n}\r\n?>\r\n" . $content;
        file_put_contents($filePath, $content);
    }

    //消息输出或者跳转
    private function showMessages($message = null)
    {
        $this->error($message);
    }

    //错误输出
    public function error($message)
    {
        throw new \Exception($message);
    }
}
