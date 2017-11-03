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
    // 模板列表
    public static $tpls = [];

    public function __construct($setting = null)
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
    }

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
     * 获取模版文件路径
     * @param  String $filename 文件名
     * @return String           获取模板所在的全路径
     */
    private function getSourcefile($filename)
    {
        return $this->tpldir . DS . $filename . $this->suffix;
    }

    /**
     * 获取缓存文件路径
     * @param  String $filename 文件名
     * @return String           缓存后文件的全路径
     */
    private function getCompiledfile($filename)
    {
        return $this->cachedir . DS . $filename . '.php';
    }

    /**
     * 判断是否已经编译
     * @param  String  $name  模板文件
     * @return boolean        是否已经编译
     */
    protected function isCompiled($name)
    {
        // 获取模板文件
        $tpl = $this->tpldir . DS . $name . $this->suffix;
        // 获取缓存文件
        $cache = $this->cachedir . DS . $name . '.php';

        // 判断缓存是否存在
        if (!is_file($cache)) {
            return false;
        }
        // 判断缓存是否过期
        if (filemtime($tpl) > filemtime($cache)) {
            return false;
        }

        // 其余情况都不需要编译
        return true;
    }

    /**
     * 创建目录或者文件
     * @param  String  $dir  文件目录或文件全路径
     * @param  boolean $file 是否为文件
     * @param  integer $mode 权限，默认为 0775
     * @return Boolean       是否创建成功
     */
    private function createdir($dir, $file = false, $mode = 0775)
    {
        // 统一路径分隔符
        $path = str_replace("\\", "/", $dir);

        // 如果是目录并且存在，直接返回真
        if (is_dir($path) && $file == false) {
            return true;
        }

        // 如果是文件，获取文件所在目录
        if ($file) {
            // 如果文件存在，直接返回创建成功
            if (is_file($path)) {
                return true;
            }

            // 使用之前统一的分隔符分割字符串，并去除文件名，拼凑成目录
            $tempArr = explode('/', $path);
            array_pop($tempArr);
            $pathFile = implode('/', $tempArr);
        }
        // 获取需要创建的目录
        $mdir = isset($pathFile) ? $pathFile : $path;

        // 判断目录是否存在，不存在则创建
        if (!is_dir($mdir)) {
            @mkdir($mdir, $mode, true);
            @chmod($mdir, $mode);
        }

        if ($file) {
            // 判断文件是否创建完成
            $fileHandle = @fopen($path, 'a');
            if ($fileHandle) {
                fclose($fileHandle);
                return true;
            }
        }

        return false;
    }

    /**
     * 写入缓存
     * @param  String $name    文件名
     * @param  String $content 文件内容
     */
    private function cache($name, $content)
    {
        $sourceFile   = $this->tpl . DS . $name . $this->suffix;
        $compiledFile = $this->cachedir . DS . $name . '.php';
        if (!$this->createdir($compiledFile, true)) {
            return 'Warning: file generation fails, check permissions to' . $compiledFile;
        }
        // 添加访问校验
        $content = "<?php\r\n if(!defined('DESPOTE')){\r\n die('Forbidden access');\r\n}\r\n?>\r\n" . $content;
        file_put_contents($compiledFile, $content);
    }

    /**
     * 编译并写入到缓存
     * @param  String $name 文件名
     */
    private function compile($name)
    {
        if ($this->isCompiled($name)) {
            $this->cache($name, $this->compileds($name));
        }
    }

    private function compileds($name)
    {
    }

    private function escape($val)
    {
        return preg_quote($val, '/');
    }
}
