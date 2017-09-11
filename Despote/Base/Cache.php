<?php
/**
 *
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace Despote\Base;

use \Memcache;

class Cache
{
    //////////////////
    // 缓存属性配置 //
    //////////////////
    // 缓存对象
    private $obj;
    // 自动清理设置，取值为 0-100，数值越小清理越频繁
    private $gc = 100;
    // 缓存类型
    private $type = 'file';

    //////////////////
    // 文件缓存设置 //
    //////////////////
    // 缓存文件目录，默认值为 /Despote/Runtime/Caches/
    private $path = PATH_CACHE;

    ///////////////////////
    // MemCache 缓存设置 //
    ///////////////////////
    // MemCache 缓存服务器信息
    private $servers = [];

    /**
     * 构造函数，传入数组初始化缓存对象
     * @param array $setting 'path' => '缓存文件目录', 'gc' => '自动清理设置'
     */
    public function __construct($type = 'file', $setting = [])
    {
        $this->type = $type;

        switch ($type) {
            case 'file':
                empty($setting['path']) || $this->path = $setting['path'];
                is_dir($this->path) || @mkdir($this->path, 0777, true);
                (empty($setting['gc']) && $setting['gc'] >= 0 && $setting['gc'] <= 100) || $this->gc = $setting['gc'];
                break;

            case 'memcache':
                $this->memcache($setting);
                break;

            case 'redis':
                $this->redis($setting);
                break;

            default:
                trigger_error('暂不支持 ' . $this->type . ' 缓存', E_USER_ERROR);
                break;
        }
    }

    private function memcache($servers = [])
    {
        if (class_exists('Memcache')) {
            // 初始化服务器群组
            is_array($servers) && $this->servers = $servers['servers'];

            // 初始化服务器参数
            isset($this->servers[0]['host']) && $this->servers[0]['host']     = 'localhost';
            isset($this->servers[0]['port']) && $this->servers[0]['port']     = 11211;
            isset($this->servers[0]['pconn']) && $this->servers[0]['pconn']   = true;
            isset($this->servers[0]['weight']) && $this->servers[0]['weight'] = 100;

            $this->obj = new \Memcache;

            // 添加分布式缓存服务器
            foreach ($this->servers as $server) {
                @$this->obj->addServer($server['host'], $server['port'], $server['pconn'], $server['weight']);
            }
        } else {
            trigger_error('Memcached 未安装或 Memcache 扩展未开启', E_USER_ERROR);
        }
    }

    /**
     * 根据缓存键名获取缓存文件对应文件名
     * @param  String $key 缓存键名
     * @return String      缓存文件名 (含绝对路径)
     */
    private function getFilename($key)
    {
        return $this->path . DS . md5($key) . '.cache';
    }

    /**
     * 校验缓存文件有效性
     * @param  String  $key 缓存键名
     * @return Boolean      是否有效
     */
    private function check_key($key)
    {
        return @filemtime($this->getFilename($key)) > time();
    }

    /**
     * 设置文件缓存，该方法会覆盖原有的缓存文件
     * @param String  $key       缓存键名
     * @param Mixed   $value     缓存值
     * @param integer $cacheLife 缓存有效时间，时间戳格式
     */
    public function set($key, $value, $cacheLife = 259200)
    {
        $result = false;

        switch ($this->type) {
            case 'file':
                $result = $this->file_set($key, $value, $cacheLife);
                break;

            case 'memcache':
                // 第三个参数是是否开启 Zlib 压缩，开启则传入：MEMCACHE_COMPRESSED，不开启传入 0
                $result = $this->obj->set($key, $value, 0, $cacheLife > 0 ? $cacheLife : 259200);
                break;

            default:
                trigger_error('暂不支持 ' . $this->type . ' 缓存', E_USER_ERROR);
                break;
        }

        return $result;
    }

    /**
     * 设置文件缓存，该方法会覆盖原有的缓存文件
     * @param  String  $key       缓存键名
     * @param  Mixed   $value     缓存值
     * @param  integer $cacheLife 缓存有效时间，时间戳格式
     * @return Boolean            缓存设置结果
     */
    private function file_set($key, $value, $cacheLife)
    {
        // 启动随机清理缓存机制
        $this->gc();

        $filename = $this->getFilename($key);
        if (file_put_contents($filename, serialize($value), LOCK_EX) !== false) {
            $expire = time() + ($cacheLife > 0 ? $cacheLife : 259200);

            return touch($filename, $expire);
        }
        return false;
    }

    /**
     * 批量设置文件缓存，该方法会覆盖原有的缓存文件
     * @param  Array   $datas     缓存键值对数组
     * @param  integer $cacheLife 缓存有效时间，时间戳格式
     * @return Array              添加失败的缓存键名列表
     */
    public function mset($datas, $cacheLife = 0)
    {
        // 添加缓存失败的键名列表
        $failKeys = [];

        foreach ($datas as $key => $value) {
            if (false === $this->set($key, $value, $cacheLife)) {
                $failKeys[] = $key;
            }
        }

        return $failKeys;
    }

    /**
     * 添加文件缓存，如果原缓存未失效，则不覆盖
     * @param String  $key       缓存键名
     * @param Mixed   $value     缓存值
     * @param integer $cacheLife 缓存有效时间，时间戳格式
     */
    public function add($key, $value, $cacheLife = 0)
    {
        $result = false;

        switch ($this->type) {
            case 'file':
                // 启动随机清理缓存机制
                $this->gc();
                $result = $this->check_key($key) ? false : $this->set($key, $value, $cacheLife);
                break;

            case 'memcache':
                // 第三个参数是是否开启 Zlib 压缩，开启则传入：MEMCACHE_COMPRESSED，不开启传入 0
                $result = $this->obj->add($key, $value, 0, $cacheLife > 0 ? $cacheLife : 259200);

            default:
                trigger_error('暂不支持 ' . $this->type . ' 缓存', E_USER_ERROR);
                break;
        }

        return $result;
    }

    /**
     * 批量添加文件缓存，如果原缓存未失效，则不覆盖
     * @param  Array   $datas     缓存键值对数组
     * @param  integer $cacheLife 缓存有效时间，时间戳格式
     * @return Array              添加失败的缓存键名列表
     */
    public function madd($datas, $cacheLife = 0)
    {
        // 添加缓存失败的键名列表
        $failKeys = [];

        foreach ($datas as $key => $value) {
            if (false === $this->add($key, $value, $cacheLife)) {
                $failKeys[] = $key;
            }
        }

        return $failKeys;
    }

    /**
     * 获取缓存值
     * @param  String $key 缓存键名
     * @return Mixed       缓存值
     */
    public function get($key)
    {
        $result = false;

        switch ($this->type) {
            case 'file':
                $result = $this->file_get($key);
                break;

            case 'memcache':
                $result = $this->obj->get($key);;
                break;

            default:
                trigger_error('暂不支持 ' . $this->type . ' 缓存', E_USER_ERROR);
                break;
        }

        return $result;
    }

    private function file_get($key)
    {
        $filename = $this->getFilename($key);

        // 使用文件独占锁读取文件并反序列化
        if ($this->check_key($key) && $fp = @fopen($filename, 'r')) {
            @flock($fp, LOCK_SH);
            $value = unserialize(stream_get_contents($fp));
            @flock($fp, LOCK_UN);
            @fclose($fp);

            return $value;
        }
        return false;
    }

    /**
     * 批量获取缓存值
     * @param  Array  $keys 缓存键名列表
     * @return Array        缓存键值对数组
     */
    public function mget($keys)
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    /**
     * 删除文件缓存
     * @param  String  $keys 缓存键名
     * @return Boolean       缓存是否删除成功
     */
    public function del($keys)
    {
        // 如果参数为数组，则递归删除
        if (is_array($keys)) {
            foreach ($keys as $key) {
                $this->del($key);
            }
        }

        $result = false;

        switch ($this->type) {
            case 'file':
                @unlink($this->getFilename($keys));
                break;

            case 'memcache':
                $this->obj->delete($keys);
                break;

            default:
                trigger_error('暂不支持 ' . $this->type . ' 缓存', E_USER_ERROR);
                break;
        }

        return $result;
    }

    /**
     * 自动随机清理
     * @param  boolean $all 是否清除所有缓存，传入 false 时将随机调用缓存清理
     */
    public function gc($all = false)
    {
        if ($all) {
            foreach (glob($this->path . DS . '*.cache') as $file) {
                @unlink($file);
            }
        } else if (mt_rand(0, 100) > $this->gc) {
            foreach (glob($this->path . DS . '*.cache') as $file) {
                if (@filemtime($file) < time()) {
                    @unlink($file);
                }
            }
        }
    }

    /**
     * 刷新缓冲区(清除所有缓存)
     */
    public function flush()
    {
        switch ($this->type) {
            case 'file':
                $this->gc(true);
                break;

            case 'memcache':
                $this->obj->flush();
                break;

            default:
                trigger_error('暂不支持 ' . $this->type . ' 缓存', E_USER_ERROR);
                break;
        }
    }
}
